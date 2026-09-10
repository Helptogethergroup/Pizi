<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RentBill;
use App\Models\RentPayment;
use App\Models\User;
use Illuminate\Http\Request;

class RentController extends Controller
{
    public function index(Request $request)
    {
        $query = RentBill::with('tenant', 'property', 'owner');

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($month = $request->get('month')) {
            $query->where('month', $month);
        }

        if ($ownerId = $request->get('owner_id')) {
            $query->where('owner_id', $ownerId);
        }

        if ($search = $request->get('q')) {
            $query->where(function($q) use ($search) {
                $q->where('bill_number', 'like', "%{$search}%")
                  ->orWhereHas('tenant', fn($t) => $t->where('name', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%"));
            });
        }

        $bills = $query->latest('month')->paginate(20)->withQueryString();

        $stats = [
            'total_collected_month' => RentPayment::whereMonth('paid_at', now()->month)
                ->whereYear('paid_at', now()->year)
                ->sum('amount'),
            'total_pending' => RentBill::where('status', '!=', 'paid')->sum('due_amount'),
            'overdue_count' => RentBill::where('status', 'overdue')->count(),
            'total_bills' => RentBill::count(),
            'total_collected_all' => RentPayment::sum('amount'),
        ];

        $owners = User::whereIn('role', ['owner', 'admin'])
            ->whereHas('rentBills')
            ->orderBy('name')
            ->get();

        return view('admin.rent.index', compact('bills', 'stats', 'owners'));
    }

    public function show(RentBill $bill)
    {
        $bill->load('tenant', 'property', 'owner', 'payments');
        return view('admin.rent.show', compact('bill'));
    }

    public function destroy(RentBill $bill)
    {
        $bill->payments()->delete();
        $bill->delete();
        return redirect()->route('admin.rent.index')->with('success', '✓ Bill deleted.');
    }

    public function deletePayment(RentPayment $payment)
    {
        $bill = $payment->bill;
        $payment->delete();
        $bill->refresh()->recalculate();
        return back()->with('success', '✓ Payment removed.');
    }

    public function receipt(RentPayment $payment)
    {
        $payment->load('bill.property', 'tenant');
        return view('owner.rent.receipt', compact('payment'));
    }
}