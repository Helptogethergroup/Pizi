<?php

namespace App\Http\Controllers\Seo;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BlogController extends Controller
{
    public function index()
    {
        $blogs = Blog::latest()->paginate(20);
        return view('seo.blogs.index', compact('blogs'));
    }

    public function create()
    {
        return view('seo.blogs.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:200',
            'excerpt' => 'nullable|string|max:500',
            'content' => 'required|string',
            'meta_title' => 'nullable|string|max:200',
            'meta_description' => 'nullable|string|max:500',
            'cover_image' => 'nullable|image|max:4096',
            'is_published' => 'nullable|boolean',
        ]);

        // Slug
        $baseSlug = Str::slug($data['title']);
        $slug = $baseSlug;
        $i = 1;
        while (Blog::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $i++;
        }
        $data['slug'] = $slug;

        if ($request->hasFile('cover_image')) {
            $data['cover_image'] = $request->file('cover_image')->store('blogs', 'public');
        }

        $data['is_published'] = $request->boolean('is_published');
        $data['author_id'] = auth()->id();
        if ($data['is_published']) {
            $data['published_at'] = now();
        }

        Blog::create($data);

        return redirect()->route('seo.blogs.index')->with('success', '✓ Blog created.');
    }

    public function edit(Blog $blog)
    {
        return view('seo.blogs.edit', compact('blog'));
    }

    public function update(Request $request, Blog $blog)
    {
        $data = $request->validate([
            'title' => 'required|string|max:200',
            'excerpt' => 'nullable|string|max:500',
            'content' => 'required|string',
            'meta_title' => 'nullable|string|max:200',
            'meta_description' => 'nullable|string|max:500',
            'cover_image' => 'nullable|image|max:4096',
            'is_published' => 'nullable|boolean',
        ]);

        if ($request->hasFile('cover_image')) {
            $data['cover_image'] = $request->file('cover_image')->store('blogs', 'public');
        }

        $wasPublished = $blog->is_published;
        $data['is_published'] = $request->boolean('is_published');
        
        if (!$wasPublished && $data['is_published']) {
            $data['published_at'] = now();
        }

        $blog->update($data);

        return redirect()->route('seo.blogs.index')->with('success', '✓ Blog updated.');
    }

    public function togglePublish(Blog $blog)
    {
        $blog->is_published = !$blog->is_published;
        if ($blog->is_published && !$blog->published_at) {
            $blog->published_at = now();
        }
        $blog->save();

        return back()->with('success', $blog->is_published ? '✓ Published' : '⏸ Unpublished');
    }

    public function destroy(Blog $blog)
    {
        $blog->delete();
        return back()->with('success', '✓ Blog deleted.');
    }
}