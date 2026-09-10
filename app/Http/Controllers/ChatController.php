<?php

namespace App\Http\Controllers;

use App\Models\ChatSession;
use App\Services\ChatAiService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ChatController extends Controller
{
    private $chatAiService;

    public function __construct(ChatAiService $chatAiService)
    {
        $this->chatAiService = $chatAiService;
    }

    // ---- EXISTING METHODS ----
    
    public function page()
    {
        // Your existing page method
        return redirect('/');
    }

    public function history($id)
    {
        // Your existing history method
        return view('chat.history', ['id' => $id]);
    }

    // ---- NEW API METHODS ----

    public function startChat(Request $request)
    {
        $sessionId = $request->input('session_id') ?? (string) Str::uuid();
        
        $session = ChatSession::firstOrCreate(
            ['session_id' => $sessionId],
            ['language' => $request->input('language', 'en'), 'customer_phone' => $request->input('phone'), 'customer_name' => $request->input('name', 'Guest')]
        );

        return response()->json(['success' => true, 'session_id' => $session->session_id, 'language' => $session->language, 'greeting' => $this->getGreeting($session->language)]);
    }

    public function sendMessage(Request $request)
    {
        $request->validate(['session_id' => 'required|string', 'message' => 'required|string|max:5000']);

        $session = ChatSession::where('session_id', $request->session_id)->first();
        if (!$session) return response()->json(['success' => false, 'message' => 'Session not found'], 404);

        $history = $session->messages()->latest()->take(8)->get()->reverse()->values()
            ->map(fn ($m) => ['sender' => $m->sender, 'message' => $m->message])->all();

        $response = $this->chatAiService->processMessage($request->message, $session->language ?? 'en', $history);

        \App\Models\ChatMessage::create(['conversation_id' => $session->id, 'sender' => 'user', 'message' => $request->message]);
        \App\Models\ChatMessage::create(['conversation_id' => $session->id, 'sender' => 'bot', 'message' => $response]);
        $session->update(['last_activity_at' => now()]);

        return response()->json(['success' => true, 'message' => $response, 'timestamp' => now()->toIso8601String()]);
    }

    public function getHistory(Request $request)
    {
        $request->validate(['session_id' => 'required|string']);

        $session = ChatSession::where('session_id', $request->session_id)->first();
        if (!$session) return response()->json(['success' => false, 'message' => 'Session not found'], 404);

        $messages = $session->messages()->orderBy('created_at')->get(['sender', 'message', 'created_at']);
        return response()->json(['success' => true, 'messages' => $messages, 'language' => $session->language]);
    }

    public function changeLanguage(Request $request)
    {
        $request->validate(['session_id' => 'required|string', 'language' => 'required|in:en,hi']);

        $session = ChatSession::where('session_id', $request->session_id)->first();
        if (!$session) return response()->json(['success' => false, 'message' => 'Session not found'], 404);

        $session->update(['language' => $request->language]);
        return response()->json(['success' => true, 'message' => 'Language changed', 'language' => $session->language]);
    }

    private function getGreeting(string $language): string
    {
        return $language === 'hi'
            ? "नमस्ते! 👋 मैं Pizi का AI असिस्टेंट हूं। आप क्या करना चाहते हैं?"
            : "Hello! 👋 I'm Pizi's AI Assistant. How can I help you today?";
    }

    /**
     * Admin dashboard — Chat Analytics.
     * Covers only conversations logged since the widget started persisting
     * sessions properly; older orphaned rows (pre-fix, mismatched schema)
     * are excluded from the "sessions" metrics since they have no session.
     */
    public function adminAnalytics()
    {
        $totalSessions = \App\Models\ChatSession::count();
        $totalMessages = \App\Models\ChatMessage::count();
        $messagesToday = \App\Models\ChatMessage::whereDate('created_at', today())->count();

        $languageBreakdown = \App\Models\ChatSession::selectRaw('language, count(*) as total')
            ->groupBy('language')
            ->pluck('total', 'language');

        $messagesByDay = \App\Models\ChatMessage::selectRaw('DATE(created_at) as day, count(*) as total')
            ->where('created_at', '>=', now()->subDays(6)->startOfDay())
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('total', 'day');

        // Fill in missing days with 0 so the chart has a full 7-day axis
        $chartLabels = [];
        $chartData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $chartLabels[] = now()->subDays($i)->format('D');
            $chartData[] = $messagesByDay[$date] ?? 0;
        }

        $recentSessions = \App\Models\ChatSession::withCount('messages')
            ->with(['messages' => fn ($q) => $q->latest()->limit(1)])
            ->latest('last_activity_at')
            ->take(15)
            ->get();

        return view('admin.chat-analytics', compact(
            'totalSessions', 'totalMessages', 'messagesToday',
            'languageBreakdown', 'chartLabels', 'chartData', 'recentSessions'
        ));
    }
}