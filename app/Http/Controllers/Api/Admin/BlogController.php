<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BlogController extends Controller
{
    public function index()
    {
        $blogs = DB::table('blogs as b')
            ->leftJoin('users as u', 'b.author_id', '=', 'u.id')
            ->select('b.*', 'u.name as author_name')
            ->orderBy('b.created_at', 'desc')
            ->get();
        return response()->json(['success' => true, 'data' => $blogs]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $id = DB::table('blogs')->insertGetId([
            'author_id' => $user->id,
            'title' => $request->title,
            'slug' => Str::slug($request->title) . '-' . substr(uniqid(), -4),
            'excerpt' => $request->excerpt,
            'content' => $request->content,
            'cover_image' => $request->cover_image,
            'meta_title' => $request->meta_title,
            'meta_description' => $request->meta_description,
            'keywords' => $request->keywords,
            'is_published' => $request->is_published ?? false,
            'published_at' => $request->is_published ? now() : null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        return response()->json(['success' => true, 'data' => ['id' => $id]]);
    }

    public function update(Request $request, $id)
    {
        $data = $request->only(['title', 'excerpt', 'content', 'cover_image', 'meta_title', 'meta_description', 'keywords', 'is_published']);
        if (isset($data['is_published']) && $data['is_published']) {
            $existing = DB::table('blogs')->where('id', $id)->first();
            if (!$existing->published_at) $data['published_at'] = now();
        }
        $data['updated_at'] = now();
        DB::table('blogs')->where('id', $id)->update($data);
        return response()->json(['success' => true]);
    }

    public function destroy($id)
    {
        DB::table('blogs')->where('id', $id)->delete();
        return response()->json(['success' => true]);
    }
}
