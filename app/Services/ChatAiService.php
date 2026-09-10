<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatAiService
{
    private $geminiApiKey;
    private $geminiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent';

    public function __construct()
    {
        $this->geminiApiKey = env('GEMINI_API_KEY');
    }

    public function processMessage($message, $language = 'en', array $history = [])
    {
        // First, use Gemini to understand the intent
        $intent = $this->detectIntent($message, $language);

        // Keyword safety-net only kicks in if Gemini's own classification call
        // failed (network error / bad JSON) — when Gemini DID respond, trust
        // its judgement. Previously this ran unconditionally and words like
        // "room", "stay", "accommodation" (common in general questions too)
        // hijacked every message into a property-search dump.
        $looksLikePgQuery = false;
        if (($intent['_source'] ?? 'gemini') === 'fallback') {
            $pgKeywords = ['pg ', ' pg', 'hostel', 'kiraye', 'coliving', 'girls pg', 'boys pg', 'paying guest'];
            $lowerMsg = strtolower($message);
            foreach ($pgKeywords as $kw) {
                if (str_contains($lowerMsg, $kw)) {
                    $looksLikePgQuery = true;
                    break;
                }
            }
        }

        if ($intent['type'] === 'pg_search' || $looksLikePgQuery) {
            // It's a PG search query - extract parameters and search
            return $this->searchPGsSmart($message, $language, $intent);
        } else {
            // It's a general question - use Gemini directly, with recent
            // conversation context so follow-up questions make sense
            return $this->getGeminiResponse($message, $language, $history);
        }
    }

    private function detectIntent($message, $language)
    {
        $prompt = "Analyze this user message and determine if they're looking for PGs/hostels or asking a general question.

User message: \"$message\"

Respond ONLY with JSON (no markdown, no extra text):
{
  \"type\": \"pg_search\" or \"general\",
  \"keywords\": [list of relevant keywords],
  \"city\": \"city name if mentioned or null\",
  \"locality\": \"locality/sector/area/neighbourhood name if mentioned, e.g. 'Sector 58', 'Laxmi Nagar' — or null\",
  \"budget_min\": null or number,
  \"budget_max\": null or number,
  \"gender\": \"male\" or \"female\" or \"unisex\" or null,
  \"property_type\": \"pg\" or \"hostel\" or \"coliving\" or null
}";

        try {
            $response = Http::timeout(20)->retry(2, 500)->post(
                $this->geminiUrl . '?key=' . $this->geminiApiKey,
                [
                    'contents' => [[
                        'parts' => [['text' => $prompt]]
                    ]],
                    'generationConfig' => [
                        'maxOutputTokens' => 500,
                        'thinkingConfig' => ['thinkingBudget' => 0]
                    ]
                ]
            );

            if ($response->successful()) {
                $text = $response->json('candidates.0.content.parts.0.text');

                // Gemini kabhi kabhi ```json ... ``` mein wrap kar deta hai, usko clean karo
                $text = preg_replace('/```json\s*|```\s*/', '', $text);
                $text = trim($text);

                $json = json_decode($text, true);

                if (is_array($json)) {
                    $json['_source'] = 'gemini';
                    return $json;
                }

                Log::warning('Intent detection JSON parse failed. Raw text: ' . json_encode($text));
            }
        } catch (\Exception $e) {
            Log::error('Intent detection error: ' . $e->getMessage());
        }

        return ['type' => 'general', '_source' => 'fallback'];
    }

    private function searchPGsSmart($message, $language, $intent)
    {
        try {
            \Log::info('CHATBOT DEBUG', ['message' => $message, 'intent' => $intent]);

            $query = DB::table('properties')
                ->join('cities', 'cities.id', '=', 'properties.city_id')
                ->leftJoin('localities', 'localities.id', '=', 'properties.locality_id')
                ->where('properties.is_active', 1)
                ->select('properties.*');

            $filtersApplied = false;

            // City filter — EXACT match on cities.name (case-insensitive), taaki "Noida" search karne par
            // "Greater Noida" ya "Noida Extension" jaisi alag cities galti se na aa jaayein
            if (!empty($intent['city'])) {
                $query->whereRaw('LOWER(cities.name) = ?', [strtolower(trim($intent['city']))]);
                $filtersApplied = true;
            }

            // Locality/sector filter — matches against the locality name, or
            // falls back to address/landmark text (many properties don't have
            // a locality_id set but do mention it in the address).
            if (!empty($intent['locality'])) {
                $locality = trim($intent['locality']);
                $query->where(function ($q) use ($locality) {
                    $q->where('localities.name', 'like', "%{$locality}%")
                      ->orWhere('properties.address_line', 'like', "%{$locality}%")
                      ->orWhere('properties.landmark', 'like', "%{$locality}%");
                });
                $filtersApplied = true;
            }

            // Budget extraction: Gemini se, warna message se regex fallback
            $budgetMax = $intent['budget_max'] ?? null;
            $budgetMin = $intent['budget_min'] ?? null;

            if (!$budgetMax && preg_match('/(\d+)\s*k\b/i', $message, $m)) {
                $budgetMax = (int) $m[1] * 1000;
            } elseif (!$budgetMax && preg_match('/(\d{4,6})/', $message, $m)) {
                $budgetMax = (int) $m[1];
            }

            // Budget filter — property ka POORA range (rent_min se rent_max tak) budget ke andar hona chahiye,
            // sirf starting price (rent_min) check karna kaafi nahi tha — isi wajah se "under 20000" bolne par
            // bhi 25-26k wali property dikh jaati thi
            if ($budgetMax) {
                $query->where('properties.rent_max', '<=', $budgetMax);
                $filtersApplied = true;
            }
            if ($budgetMin) {
                $query->where('properties.rent_min', '>=', $budgetMin);
                $filtersApplied = true;
            }

            // Gender filter
            if (!empty($intent['gender']) && in_array($intent['gender'], ['male', 'female', 'unisex'])) {
                $query->where('properties.gender', $intent['gender']);
                $filtersApplied = true;
            }

            // Apply property type filter
            if (!empty($intent['property_type'])) {
                $query->where('properties.property_type', $intent['property_type']);
                $filtersApplied = true;
            }

            // No specific filter extracted at all ("show me some pgs") — cap
            // the unfiltered dump to a short preview instead of listing
            // everything, and nudge the user to narrow it down.
            $resultLimit = $filtersApplied ? 15 : 5;

            $properties = $query->orderBy('properties.rent_min', 'asc')->limit($resultLimit)->get();

            if ($properties->count() > 0) {
                // Format results nicely
                if (!$filtersApplied) {
                    $response = $language === 'hi'
                        ? "यहाँ Pizi पर कुछ PGs हैं। बेहतर results के लिए city, sector/locality या budget बताइए:\n\n"
                        : "Here are a few PGs on Pizi. Tell me a city, sector/locality, or budget for better matches:\n\n";
                } elseif ($language === 'hi') {
                    $response = "✅ Pizi पर ये results मिल रहे हैं:\n\n";
                } else {
                    $response = "✅ Here are the results on Pizi:\n\n";
                }

               foreach ($properties as $prop) {
                    $response .= "🏠 " . $prop->name . "\n";
                    $response .= "📍 " . substr($prop->address_line, 0, 50) . "\n";
                    $response .= "💰 ₹" . number_format($prop->rent_min) . " - ₹" . number_format($prop->rent_max) . "\n";
                    $response .= "👥 " . ucfirst($prop->gender) . "\n";
                    $response .= "──────────\n\n";
                }

                if ($language === 'hi') {
                    $response .= "🔗 और देखने के लिए Pizi.in visit करें!";
                } else {
                    $response .= "🔗 Visit Pizi.in to view more details!";
                }

                return $response;
            } else {
                if ($language === 'hi') {
                    return "😢 इस budget/location के लिए कोई result नहीं मिला। Pizi.in पर सभी PGs browse करें!";
                } else {
                    return "😢 No results found for this budget/location. Browse all PGs on Pizi.in!";
                }
            }
        } catch (\Exception $e) {
            Log::error('PG search error: ' . $e->getMessage());
            return $language === 'hi' ? 'कोई error आई। कृपया फिर से कोशिश करें।' : 'An error occurred. Please try again.';
        }
    }

    private function getGeminiResponse($message, $language, array $history = [])
    {
        if (!$this->geminiApiKey) {
            return $language === 'hi' ? 'API key missing' : 'API key missing';
        }

        $systemPrompt = $language === 'hi'
            ? "आप Pizi.in का एक helpful AI assistant हैं। Pizi एक PG/hostel finder platform है। हमेशा user-friendly, short और informative जवाब दें। बहुत ज़रूरी: कभी भी किसी specific PG/property का नाम मत बताओ (चाहे Pizi का हो या किसी और platform का), कभी भी 99acres, NoBroker, MagicBricks जैसी किसी और वेबसाइट का ज़िक्र मत करो। अगर PG के बारे में सवाल हो, तो सिर्फ इतना कहो कि Pizi.in पर जाकर search करें — actual listings कभी खुद मत बनाओ। HARD RULE: हमेशा हिंदी में ही जवाब दो, चाहे पुरानी conversation में कोई भी भाषा इस्तेमाल हुई हो।"
            : "You are a helpful AI assistant for Pizi.in. Pizi is a PG/hostel finder platform. Always be friendly, concise and informative. IMPORTANT: Never mention specific property names, addresses, or listings — those must only come from Pizi's own database search. Never mention or recommend any other website or platform (like 99acres, NoBroker, MagicBricks, etc.). If asked about PGs, only say to search on Pizi.in — never invent listing details yourself. HARD RULE: Always reply in English only, regardless of what language was used earlier in this conversation.";

        // Build multi-turn contents: system prompt, then recent conversation
        // history, then the new user message — so follow-up questions like
        // "what about under 10k?" understand what was asked before.
        $contents = [[
            'role' => 'user',
            'parts' => [['text' => $systemPrompt]],
        ]];

        foreach (array_slice($history, -8) as $turn) {
            $contents[] = [
                'role' => ($turn['sender'] ?? $turn['role'] ?? 'user') === 'bot' ? 'model' : 'user',
                'parts' => [['text' => (string) ($turn['message'] ?? $turn['text'] ?? '')]],
            ];
        }

        // Language reminder attached right next to the actual message (not
        // just at the very top) — AI models weigh recent context more, so a
        // rule stated once at the start can get drowned out by several turns
        // of history that happen to be in the other language.
        $langReminder = $language === 'hi' ? ' (कृपया सिर्फ हिंदी में जवाब दें।)' : ' (Please reply in English only.)';
        $contents[] = ['role' => 'user', 'parts' => [['text' => $message . $langReminder]]];

        try {
            $response = Http::timeout(30)->retry(2, 500)->post(
                $this->geminiUrl . '?key=' . $this->geminiApiKey,
                [
                    'contents' => $contents,
                    'generationConfig' => [
                        'temperature' => 0.7,
                        'maxOutputTokens' => 1024,
                        // Without this, gemini-2.5-flash burns most of the token
                        // budget on internal "thinking" and cuts the real answer
                        // off mid-sentence (finishReason: MAX_TOKENS).
                        'thinkingConfig' => ['thinkingBudget' => 0],
                    ]
                ]
            );

            if ($response->successful()) {
                $body = $response->json();
                if (isset($body['candidates'][0]['content']['parts'][0]['text'])) {
                    return $body['candidates'][0]['content']['parts'][0]['text'];
                }
            }

            return $language === 'hi' ? 'कोशिश करें फिर से। 🙏' : 'Please try again. 🙏';
        } catch (\Exception $e) {
            Log::error('Gemini Error: ' . $e->getMessage());
            return $language === 'hi' ? 'Error आई। 🙏' : 'Error occurred. 🙏';
        }
    }
}