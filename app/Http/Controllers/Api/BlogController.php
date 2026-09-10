<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BlogController extends Controller
{
    public function index(Request $request)
    {
        $page = (int) ($request->page ?? 1);
        $limit = min((int) ($request->limit ?? 10), 50);

        $query = DB::table('blogs as b')
            ->leftJoin('users as u', 'b.author_id', '=', 'u.id')
            ->select('b.*', 'u.name as author_name')
            ->where('b.is_published', true)
            ->orderBy('b.published_at', 'desc');

        $total = $query->count();
        $items = $query->offset(($page - 1) * $limit)->limit($limit)->get();

        return response()->json([
            'success' => true,
            'data' => [
                'items' => $items,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'pages' => ceil($total / $limit),
                ]
            ]
        ]);
    }

    public function show($slug)
    {
        $blog = DB::table('blogs as b')
            ->leftJoin('users as u', 'b.author_id', '=', 'u.id')
            ->select('b.*', 'u.name as author_name', 'u.avatar as author_avatar')
            ->where('b.slug', $slug)
            ->where('b.is_published', true)
            ->first();

        if (!$blog) return response()->json(['success' => false, 'message' => 'Blog not found'], 404);

        DB::table('blogs')->where('id', $blog->id)->increment('view_count');

        return response()->json(['success' => true, 'data' => $blog]);
    }
}
