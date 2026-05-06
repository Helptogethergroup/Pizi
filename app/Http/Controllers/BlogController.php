<?php

namespace App\Http\Controllers;

use App\Models\Blog;

class BlogController extends Controller
{
    public function index()
    {
        $blogs = Blog::published()->latest('published_at')->paginate(9);
        return view('blog.index', compact('blogs'));
    }

    public function show(string $slug)
    {
        $blog = Blog::where('slug', $slug)->published()->firstOrFail();
        $blog->increment('view_count');
        $related = Blog::published()
            ->where('id', '!=', $blog->id)
            ->latest('published_at')->take(3)->get();
        return view('blog.show', compact('blog', 'related'));
    }
}
