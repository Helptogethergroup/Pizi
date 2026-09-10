<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class GuideController extends Controller
{
    /**
     * Standalone printable guide page — owner uses browser's
     * "Print → Save as PDF" to get a PDF copy. No PDF library
     * needed on the server (safer on shared hosting).
     */
    public function show()
    {
        return view('owner.guide');
    }

    /**
     * Called when owner closes the popup — so it doesn't show
     * again on every dashboard visit.
     */
    public function dismiss(Request $request)
    {
        auth()->user()->update(['dashboard_guide_seen_at' => now()]);
        return response()->json(['ok' => true]);
    }
}