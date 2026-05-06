<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CreditPackage;
use App\Models\Payment;
use Illuminate\Http\Request;

class CreditPackageController extends Controller
{
    public function index()
    {
        $packages = CreditPackage::orderBy('display_order')->orderBy('price_inr')->get();
        
        $stats = [
            'total_revenue' => Payment::where('status', 'paid')->sum('amount_inr'),
            'total_payments' => Payment::where('status', 'paid')->count(),
            'failed_payments' => Payment::where('status', 'failed')->count(),
        ];

        $recentPayments = Payment::with('user', 'package')->latest()->take(15)->get();

        return view('admin.packages', compact('packages', 'stats', 'recentPayments'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'price_inr' => 'required|integer|min:1',
            'credits' => 'required|integer|min:1',
            'bonus_credits' => 'nullable|integer|min:0',
            'description' => 'nullable|string|max:500',
            'is_popular' => 'nullable|boolean',
            'display_order' => 'nullable|integer',
        ]);

        $data['is_popular'] = $request->boolean('is_popular');
        $data['is_active'] = true;
        $data['bonus_credits'] = $data['bonus_credits'] ?? 0;
        $data['display_order'] = $data['display_order'] ?? 0;

        CreditPackage::create($data);

        return back()->with('success', '✓ Package created.');
    }

    public function toggle(CreditPackage $package)
    {
        $package->update(['is_active' => !$package->is_active]);
        return back()->with('success', '✓ Package status updated.');
    }

    public function destroy(CreditPackage $package)
    {
        $package->delete();
        return back()->with('success', '✓ Package deleted.');
    }
}