<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LeadPricing;
use Illuminate\Http\Request;

class PricingController extends Controller
{
    public function index()
    {
        // Ensure all 4 lead types exist
        foreach (['direct', 'verified', 'converted', 'manual'] as $type) {
            LeadPricing::firstOrCreate(
                ['lead_type' => $type],
                ['credit_cost' => 0, 'is_active' => true]
            );
        }

        $pricing = LeadPricing::orderBy('lead_type')->get();
        return view('admin.pricing', compact('pricing'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'pricing' => 'required|array',
            'pricing.*.id' => 'required|exists:lead_pricing,id',
            'pricing.*.credit_cost' => 'required|integer|min:0',
            'pricing.*.is_active' => 'nullable|boolean',
        ]);

        foreach ($data['pricing'] as $row) {
            LeadPricing::where('id', $row['id'])->update([
                'credit_cost' => $row['credit_cost'],
                'is_active' => !empty($row['is_active']),
            ]);
        }

        return back()->with('success', '✓ Pricing updated successfully.');
    }
}