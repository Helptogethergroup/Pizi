<?php

namespace App\Http\Controllers\Api\Owner;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RentController extends Controller
{
    private function ownerScope(Request $request, $query, $alias = '')
    {
        if ($request->user()->role !== 'admin') {
            $col = $alias ? $alias . '.owner_id' : 'owner_id';
            $query->where($col, $request->user()->id);
        }
        return $query;
    }

    public function index(Request $request)
    {
        $query = DB::table('rent_bills as rb')
            ->leftJoin('tenants as t', 'rb.tenant_id', '=', 't.id')
            ->leftJoin('properties as p', 'rb.property_id', '=', 'p.id')
            ->select('rb.*', 't.name as tenant_name', 't.phone as tenant_phone', 'p.name as property_name');
        $this->ownerScope($request, $query, 'rb');

        if ($request->status) $query->where('rb.status', $request->status);
        if ($request->month) $query->where('rb.month', $request->month);
        if ($request->tenant_id) $query->where('rb.tenant_id', $request->tenant_id);

        $bills = $query->orderBy('rb.due_date', 'desc')->get();

        // Add payments
        $billIds = $bills->pluck('id')->toArray();
        $payments = DB::table('rent_payments')->whereIn('rent_bill_id', $billIds)->get()->groupBy('rent_bill_id');
        $bills = $bills->map(function ($b) use ($payments) {
            $b->payments = $payments->get($b->id, collect([]));
            return $b;
        });

        return response()->json(['success' => true, 'data' => $bills]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $validator = Validator::make($request->all(), [
            'tenant_id' => 'required|integer|exists:tenants,id',
            'month' => 'required|string',
            'rent_amount' => 'required|numeric|min:0',
            'due_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $tenant = DB::table('tenants')->where('id', $request->tenant_id)->first();
        if (!$tenant) return response()->json(['success' => false, 'message' => 'Tenant not found'], 404);

        $rent = $request->rent_amount;
        $electricity = $request->electricity ?? 0;
        $water = $request->water ?? 0;
        $maintenance = $request->maintenance ?? 0;
        $food = $request->food_charges ?? 0;
        $other = $request->other_charges ?? 0;
        $lateFee = $request->late_fee ?? 0;
        $discount = $request->discount ?? 0;

        $total = $rent + $electricity + $water + $maintenance + $food + $other + $lateFee - $discount;

        $billNumber = 'BILL-' . date('Ym') . '-' . str_pad($tenant->id, 4, '0', STR_PAD_LEFT) . '-' . substr(uniqid(), -4);

        $id = DB::table('rent_bills')->insertGetId([
            'tenant_id' => $tenant->id,
            'property_id' => $tenant->property_id,
            'owner_id' => $user->id,
            'bill_number' => $billNumber,
            'month' => $request->month,
            'rent_amount' => $rent,
            'electricity' => $electricity,
            'water' => $water,
            'maintenance' => $maintenance,
            'food_charges' => $food,
            'other_charges' => $other,
            'other_charges_label' => $request->other_charges_label,
            'late_fee' => $lateFee,
            'discount' => $discount,
            'total_amount' => $total,
            'paid_amount' => 0,
            'due_amount' => $total,
            'due_date' => $request->due_date,
            'status' => 'pending',
            'notes' => $request->notes,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['success' => true, 'data' => ['id' => $id, 'bill_number' => $billNumber]]);
    }

    public function show(Request $request, $id)
    {
        $query = DB::table('rent_bills')->where('id', $id);
        $this->ownerScope($request, $query);
        $bill = $query->first();
        if (!$bill) return response()->json(['success' => false, 'message' => 'Not found'], 404);

        $bill->tenant = DB::table('tenants')->where('id', $bill->tenant_id)->first();
        $bill->payments = DB::table('rent_payments')->where('rent_bill_id', $id)->orderBy('paid_at', 'desc')->get();
        return response()->json(['success' => true, 'data' => $bill]);
    }

    public function generateAll(Request $request)
    {
        $user = $request->user();
        $month = $request->month ?? date('Y-m');
        $dueDate = $request->due_date ?? date('Y-m-05', strtotime($month));

        $tenants = DB::table('tenants')
            ->where('owner_id', $user->id)
            ->where('status', 'active')
            ->whereNotNull('monthly_rent')
            ->where('monthly_rent', '>', 0)
            ->get();

        $generated = 0;
        $skipped = 0;
        foreach ($tenants as $t) {
            $exists = DB::table('rent_bills')->where('tenant_id', $t->id)->where('month', $month)->exists();
            if ($exists) { $skipped++; continue; }

            $billNumber = 'BILL-' . str_replace('-', '', $month) . '-' . str_pad($t->id, 4, '0', STR_PAD_LEFT);
            DB::table('rent_bills')->insert([
                'tenant_id' => $t->id,
                'property_id' => $t->property_id,
                'owner_id' => $user->id,
                'bill_number' => $billNumber,
                'month' => $month,
                'rent_amount' => $t->monthly_rent,
                'total_amount' => $t->monthly_rent,
                'paid_amount' => 0,
                'due_amount' => $t->monthly_rent,
                'due_date' => $dueDate,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $generated++;
        }

        return response()->json(['success' => true, 'data' => compact('generated', 'skipped')]);
    }

    public function recordPayment(Request $request, $id)
    {
        $user = $request->user();
        $query = DB::table('rent_bills')->where('id', $id);
        $this->ownerScope($request, $query);
        $bill = $query->first();
        if (!$bill) return response()->json(['success' => false, 'message' => 'Not found'], 404);

        $amount = (float) $request->amount;
        $receiptNumber = 'RCPT-' . date('Ymd') . '-' . substr(uniqid(), -6);

        $payId = DB::table('rent_payments')->insertGetId([
            'rent_bill_id' => $id,
            'tenant_id' => $bill->tenant_id,
            'owner_id' => $user->id,
            'receipt_number' => $receiptNumber,
            'amount' => $amount,
            'payment_method' => $request->payment_method ?? 'cash',
            'transaction_ref' => $request->transaction_ref,
            'paid_at' => $request->paid_at ?? now(),
            'received_by_id' => $user->id,
            'notes' => $request->notes,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Update bill totals
        $newPaid = $bill->paid_amount + $amount;
        $newDue = $bill->total_amount - $newPaid;
        $status = $newDue <= 0 ? 'paid' : ($newPaid > 0 ? 'partial' : 'pending');

        DB::table('rent_bills')->where('id', $id)->update([
            'paid_amount' => $newPaid,
            'due_amount' => max(0, $newDue),
            'status' => $status,
            'updated_at' => now(),
        ]);

        return response()->json(['success' => true, 'data' => ['payment_id' => $payId, 'receipt_number' => $receiptNumber]]);
    }

    public function destroy(Request $request, $id)
    {
        $query = DB::table('rent_bills')->where('id', $id);
        $this->ownerScope($request, $query);
        if (!$query->first()) return response()->json(['success' => false, 'message' => 'Not found'], 404);
        DB::table('rent_payments')->where('rent_bill_id', $id)->delete();
        DB::table('rent_bills')->where('id', $id)->delete();
        return response()->json(['success' => true]);
    }

    public function deletePayment($paymentId)
    {
        $pay = DB::table('rent_payments')->where('id', $paymentId)->first();
        if (!$pay) return response()->json(['success' => false, 'message' => 'Not found'], 404);

        DB::table('rent_payments')->where('id', $paymentId)->delete();

        $bill = DB::table('rent_bills')->where('id', $pay->rent_bill_id)->first();
        if ($bill) {
            $newPaid = $bill->paid_amount - $pay->amount;
            $newDue = $bill->total_amount - $newPaid;
            $status = $newDue <= 0 ? 'paid' : ($newPaid > 0 ? 'partial' : 'pending');
            DB::table('rent_bills')->where('id', $pay->rent_bill_id)->update([
                'paid_amount' => max(0, $newPaid),
                'due_amount' => max(0, $newDue),
                'status' => $status,
                'updated_at' => now(),
            ]);
        }

        return response()->json(['success' => true]);
    }
}
