<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BlogController extends Controller
{
    public function index(Request $request)
    {
        $q = Blog::where('author_id', auth()->id());

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
            'total' => Blog::where('author_id', auth()->id())->count(),
            'published' => Blog::where('author_id', auth()->id())->where('is_published', true)->count(),
            'drafts' => Blog::where('author_id', auth()->id())->where('is_published', false)->count(),
            'views' => Blog::where('author_id', auth()->id())->sum('view_count'),
        ];

        return view('owner.blogs.index', compact('blogs', 'stats'));
    }

    public function create()
    {
        return view('owner.blogs.create');
    }

    public function store(Request $request)
    {
        $data = $this->validateBlog($request);

        if ($request->hasFile('cover_image')) {
            $data['cover_image'] = $request->file('cover_image')->store('blogs/covers', 'public');
        }

        $data['author_id'] = auth()->id();
        $data['slug'] = Str::slug($data['title']);

        $base = $data['slug'];
        $i = 1;
        while (Blog::where('slug', $data['slug'])->exists()) {
            $data['slug'] = $base . '-' . $i++;
        }

        // Owners ka blog by default DRAFT — admin approve karega
        $data['is_published'] = false;
        $data['published_at'] = null;

        Blog::create($data);

        return redirect()->route('owner.blogs.index')->with('success', '✓ Blog draft saved. Admin will review and publish.');
    }

    public function edit(Blog $blog)
    {
        $this->authorizeOwner($blog);
        return view('owner.blogs.edit', compact('blog'));
    }

    public function update(Request $request, Blog $blog)
    {
        $this->authorizeOwner($blog);

        $data = $this->validateBlog($request);

        if ($request->hasFile('cover_image')) {
            if ($blog->cover_image && !str_starts_with($blog->cover_image, 'http')) {
                Storage::disk('public')->delete($blog->cover_image);
            }
            $data['cover_image'] = $request->file('cover_image')->store('blogs/covers', 'public');
        }

        if ($data['title'] !== $blog->title) {
            $newSlug = Str::slug($data['title']);
            $base = $newSlug;
            $i = 1;
            while (Blog::where('slug', $newSlug)->where('id', '!=', $blog->id)->exists()) {
                $newSlug = $base . '-' . $i++;
            }
            $data['slug'] = $newSlug;
        }

        // Owner can't self-publish — only admin can
        unset($data['is_published']);

        $blog->update($data);

        return redirect()->route('owner.blogs.index')->with('success', '✓ Blog updated. Pending admin review.');
    }

    public function destroy(Blog $blog)
    {
        $this->authorizeOwner($blog);

        if ($blog->cover_image && !str_starts_with($blog->cover_image, 'http')) {
            Storage::disk('public')->delete($blog->cover_image);
        }
        $blog->delete();
        return redirect()->route('owner.blogs.index')->with('success', '✓ Blog deleted.');
    }

    private function authorizeOwner(Blog $blog): void
    {
        if ($blog->author_id !== auth()->id() && !auth()->user()->isAdmin()) {
            abort(403, 'You can only edit your own blogs.');
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
        ]);
    }
}