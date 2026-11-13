<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\NoticeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Owner / PG Manager: post notices for the tenants of their PGs (shown in the tenant app). */
class OwnerNoticeController extends Controller
{
    public function __construct(private NoticeService $notices)
    {
    }

    private function row($n): array
    {
        return [
            'id' => $n->id,
            'property_id' => $n->property_id,
            'property_name' => $n->property?->name,
            'title' => $n->title,
            'body' => $n->body,
            'kind' => $n->kind,
            'pinned' => $n->pinned,
            'expires_at' => $n->expires_at?->toIso8601String(),
            'created_at' => $n->created_at?->toIso8601String(),
        ];
    }

    public function index(Request $request): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $this->notices->listFor($request->user())->map(fn ($n) => $this->row($n))->values()]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate(NoticeService::rules());
        $notice = $this->notices->post($request->user(), $data);

        return response()->json(['success' => true, 'data' => $this->row($notice->load('property:id,name'))], 201);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        if (!$this->notices->delete($request->user(), $id)) {
            return response()->json(['success' => false, 'message' => 'Notice not found.'], 404);
        }

        return response()->json(['success' => true]);
    }
}
