<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\PropertyNotice;
use App\Services\NoticeService;
use Illuminate\Http\Request;

/** Website page where an owner or PG Manager posts notices that tenants see in the Pizi tenant app. */
class NoticeController extends Controller
{
    public function __construct(private NoticeService $notices)
    {
    }

    public function index()
    {
        $user = auth()->user();

        return view('owner.notices.index', [
            'notices' => $this->notices->listFor($user),
            'properties' => Property::whereIn('id', $user->getManagedPropertyIds())->orderBy('name')->get(['id', 'name']),
            'canPostToAll' => $user->role !== 'pg_manager',
            'kinds' => PropertyNotice::KINDS,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate(NoticeService::rules());
        $this->notices->post($request->user(), $data);

        return back()->with('success', '✓ Notice posted. Tenants will see it in their app.');
    }

    public function destroy(Request $request, int $id)
    {
        abort_unless($this->notices->delete($request->user(), $id), 404);

        return back()->with('success', 'Notice removed.');
    }
}
