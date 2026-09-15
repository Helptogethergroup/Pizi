<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BlogController extends Controller
{
    public function index(Request $request)
    {
        $q = Blog::with('author');

        if ($request->filled('search')) {
            $q->where('title', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('status')) {
            if ($request->status === 'published') {
                $q->where('is_published', true);
            } elseif ($request->status === 'draft') {
                $q->where('is_published', false);
            }
        }

        $blogs = $q->latest()->paginate(15)->withQueryString();

        $stats = [
            'total' => Blog::count(),
            'published' => Blog::where('is_published', true)->count(),
            'drafts' => Blog::where('is_published', false)->count(),
            'views' => Blog::sum('view_count'),
        ];

        return view('admin.blogs.index', compact('blogs', 'stats'));
    }

    public function create()
    {
        return view('admin.blogs.create');
    }

    public function store(Request $request)
    {
        $data = $this->validateBlog($request);

        if ($request->hasFile('cover_image')) {
            $data['cover_image'] = $request->file('cover_image')->store('blogs/covers', 'public');
        }

        $data['author_id'] = auth()->id();
        $data['slug'] = Str::slug($data['title']);

        // Ensure unique slug
        $base = $data['slug'];
        $i = 1;
        while (Blog::where('slug', $data['slug'])->exists()) {
            $data['slug'] = $base . '-' . $i++;
        }

        if (!empty($data['is_published']) && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        Blog::create($data);

        return redirect()->route('admin.blogs.index')->with('success', '✓ Blog post created.');
    }

    public function edit(Blog $blog)
    {
        return view('admin.blogs.edit', compact('blog'));
    }

    public function update(Request $request, Blog $blog)
    {
        $data = $this->validateBlog($request);

        if ($request->hasFile('cover_image')) {
            if ($blog->cover_image && !str_starts_with($blog->cover_image, 'http')) {
                Storage::disk('public')->delete($blog->cover_image);
            }
            $data['cover_image'] = $request->file('cover_image')->store('blogs/covers', 'public');
        }

        // Slug change only if title changes
        if ($data['title'] !== $blog->title) {
            $newSlug = Str::slug($data['title']);
            $base = $newSlug;
            $i = 1;
            while (Blog::where('slug', $newSlug)->where('id', '!=', $blog->id)->exists()) {
                $newSlug = $base . '-' . $i++;
            }
            $data['slug'] = $newSlug;
        }

        if (!empty($data['is_published']) && empty($blog->published_at)) {
            $data['published_at'] = now();
        }

        $blog->update($data);

        return redirect()->route('admin.blogs.index')->with('success', '✓ Blog post updated.');
    }

    public function togglePublish(Blog $blog)
    {
        $blog->is_published = !$blog->is_published;
        if ($blog->is_published && !$blog->published_at) {
            $blog->published_at = now();
        }
        $blog->save();
        $msg = $blog->is_published ? '✓ Blog published.' : '⏸ Blog unpublished (draft mode).';
        return back()->with('success', $msg);
    }

    public function destroy(Blog $blog)
    {
        if ($blog->cover_image && !str_starts_with($blog->cover_image, 'http')) {
            Storage::disk('public')->delete($blog->cover_image);
        }
        $blog->delete();
        return redirect()->route('admin.blogs.index')->with('success', '✓ Blog deleted permanently.');
    }

    /**
     * AI-assist: given a title + plain-text content, auto-draft an
     * excerpt, SEO meta title/description and keywords via Gemini.
     * Admin can accept/edit before saving — nothing is auto-saved here.
     */
    public function aiAssist(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:200',
            'content_text' => 'required|string',
        ]);

        $apiKey = env('GEMINI_API_KEY');
        if (!$apiKey) {
            return response()->json(['success' => false, 'message' => 'AI not configured on this server.'], 422);
        }

        $prompt = "You are writing SEO metadata for a PG/hostel-rental blog post (Pizi.in, India).\n"
            . "Title: {$data['title']}\n"
            . "Content:\n" . \Illuminate\Support\Str::limit($data['content_text'], 3000) . "\n\n"
            . "Reply with ONLY a raw JSON object (no markdown fences), exactly these keys:\n"
            . '{"excerpt": "1-2 sentence summary, max 160 chars", "meta_title": "SEO title, max 60 chars", "meta_description": "SEO meta description, max 160 chars", "keywords": "5-8 comma-separated keywords relevant to PG/hostel search in India"}';

        try {
            $response = \Illuminate\Support\Facades\Http::timeout(30)->retry(2, 500)->post(
                'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=' . $apiKey,
                [
                    'contents' => [['role' => 'user', 'parts' => [['text' => $prompt]]]],
                    'generationConfig' => [
                        'temperature' => 0.6,
                        'maxOutputTokens' => 512,
                        'thinkingConfig' => ['thinkingBudget' => 0],
                    ],
                ]
            );

            $text = $response->json('candidates.0.content.parts.0.text');
            if (!$text) {
                return response()->json(['success' => false, 'message' => 'AI did not return a result. Try again.'], 502);
            }

            // Strip ```json fences if the model added them anyway.
            $clean = trim(preg_replace('/^```json\s*|```$/m', '', trim($text)));
            $parsed = json_decode($clean, true);

            if (!is_array($parsed)) {
                return response()->json(['success' => false, 'message' => 'AI response could not be parsed.'], 502);
            }

            return response()->json(['success' => true, 'data' => [
                'excerpt' => \Illuminate\Support\Str::limit($parsed['excerpt'] ?? '', 160, ''),
                'meta_title' => \Illuminate\Support\Str::limit($parsed['meta_title'] ?? '', 60, ''),
                'meta_description' => \Illuminate\Support\Str::limit($parsed['meta_description'] ?? '', 160, ''),
                'keywords' => $parsed['keywords'] ?? '',
            ]]);
        } catch (\Exception $e) {
            \Log::error('Blog AI-assist failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'AI request failed. Try again.'], 500);
        }
    }

    private function validateBlog(Request $request): array
    {
        return $request->validate([
            'title' => 'required|string|max:200',
            'excerpt' => 'nullable|string|max:500',
            'content' => 'required|string',
            'cover_image' => 'nullable|image|max:4096',
            'meta_title' => 'nullable|string|max:160',
            'meta_description' => 'nullable|string|max:320',
            'keywords' => 'nullable|string|max:255',
            'is_published' => 'nullable|boolean',
        ]);
    }
}