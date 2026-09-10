<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    public function index(Request $request)
    {
        $query = Blog::published();

        if ($search = $request->get('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('excerpt', 'like', "%{$search}%");
            });
        }

        // First (latest) post gets the featured hero treatment — only on
        // an unfiltered, first-page view so search results stay a plain grid.
        $featured = (!$search && !$request->get('page'))
            ? Blog::published()->latest('published_at')->first()
            : null;

        $blogs = $query->latest('published_at')
            ->when($featured, fn ($q) => $q->where('id', '!=', $featured->id))
            ->paginate(9);

        $popular = Blog::published()->orderByDesc('view_count')->take(5)->get();

        return view('blog.index', compact('blogs', 'featured', 'popular', 'search'));
    }

    public function show(string $slug)
    {
        $blog = Blog::where('slug', $slug)->published()->firstOrFail();
        $blog->increment('view_count');

        $related = Blog::published()
            ->where('id', '!=', $blog->id)
            ->latest('published_at')->take(3)->get();

        $popular = Blog::published()
            ->where('id', '!=', $blog->id)
            ->orderByDesc('view_count')->take(5)->get();

        return view('blog.show', compact('blog', 'related', 'popular'));
    }
}