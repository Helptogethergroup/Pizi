<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class BillingController extends Controller
{
    public function edit()
    {
        return view('owner.billing', ['user' => auth()->user()]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'gst_number' => 'nullable|string|max:20',
            'billing_business_name' => 'nullable|string|max:150',
            'billing_address' => 'nullable|string|max:500',
            'billing_state' => 'nullable|string|max:100',
            'billing_pincode' => 'nullable|string|max:10',
        ]);

        auth()->user()->update($data);

        return back()->with('success', '✓ Billing & GST info saved.');
    }
}
