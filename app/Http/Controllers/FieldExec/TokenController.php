<?php

namespace App\Http\Controllers\FieldExec;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Field Executive — Token: set amount + collect cash
 * File: app/Http/Controllers/FieldExecutive/TokenController.php
 */
class TokenController extends Controller
{
    public function index()
    {
        // Pending CASH collections (tenant ne cash choose kiya, abhi tak collect nahi hua)
        $pendingCash = DB::table('token_payments as tp')
            ->join('tenants as t', 't.id', '=', 'tp.tenant_id')
            ->where('tp.status', 'pending')
            ->where('tp.payment_method', 'cash')
            ->select('tp.*', 't.name as tenant_name', 't.phone as tenant_phone')
            ->orderByDesc('tp.id')
            ->get();

        // Recently collected/paid (sab modes)
        $collected = DB::table('token_payments as tp')
            ->join('tenants as t', 't.id', '=', 'tp.tenant_id')
            ->where('tp.status', 'paid')
            ->select('tp.*', 't.name as tenant_name', 't.phone as tenant_phone')
            ->orderByDesc('tp.paid_at')
            ->limit(25)
            ->get();

        return view('field.token-collection', compact('pendingCash', 'collected'));
    }

    /**
     * Field exec sets token amount for a tenant (by phone)
     */
    public function setToken(Request $request)
    {
        $request->validate([
            'phone'  => 'required|string',
            'amount' => 'required|numeric|min:1',
        ]);

        $last10 = substr(preg_replace('/[^0-9]/', '', $request->phone), -10);

        $tenant = Tenant::whereRaw('RIGHT(REPLACE(REPLACE(phone, "+91", ""), " ", ""), 10) = ?', [$last10])->first();
      
      
      
      if (!$tenant) {
            return back()->with('error', 'No tenant found with this phone.');
        }

        // ===== CHAIN: token amount tabhi set ho jab visit (Step 5) complete ho =====
        if (!\App\Http\Controllers\TenantPortalController::isStepComplete($tenant->user_id, 5)) {
            return back()->with('error', '⛔ Visit not completed yet (Step 5). Complete the visit before setting token.');
        }
        // ===== END CHAIN =====

           
           
           
        if (!$tenant) {
            return back()->with('error', 'No tenant found with this phone number.');
        }

        $existing = DB::table('token_payments')
            ->where('user_id', $tenant->user_id)
            ->where('status', 'pending')
            ->first();

        if ($existing) {
            DB::table('token_payments')->where('id', $existing->id)->update([
                'amount'     => $request->amount,
                'updated_at' => now(),
            ]);
        } else {
            DB::table('token_payments')->insert([
                'tenant_id'      => $tenant->id,
                'user_id'        => $tenant->user_id,
                'property_id'    => $tenant->property_id,
                'amount'         => $request->amount,
                'payment_method' => null,
                'status'         => 'pending',
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
        }

        return back()->with('success', "Token amount ₹{$request->amount} set for {$tenant->name}. Tenant can now pay online or cash.");
    }

    /**
     * Mark a cash token as collected
     */
    public function collectCash(Request $request)
    {
        $request->validate(['token_payment_id' => 'required']);

        DB::table('token_payments')->where('id', $request->token_payment_id)->update([
            'payment_method' => 'cash',
            'status'         => 'paid',
            'collected_by'   => Auth::id(),
            'paid_at'        => now(),
            'notes'          => $request->notes,
            'updated_at'     => now(),
        ]);

        return back()->with('success', 'Cash token marked as collected ✅');
    }
}
