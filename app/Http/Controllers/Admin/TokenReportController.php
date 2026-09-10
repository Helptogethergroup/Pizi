<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class TokenReportController extends Controller
{
    public function index()
    {
        $payments = DB::table('token_payments as tp')
            ->join('tenants as t', 't.id', '=', 'tp.tenant_id')
            ->leftJoin('properties as p', 'p.id', '=', 'tp.property_id')
            ->leftJoin('users as u', 'u.id', '=', 'tp.collected_by')
            ->select('tp.*', 't.name as tenant_name', 't.phone as tenant_phone',
                     'p.name as property_name', 'u.name as collector_name')
            ->orderByDesc('tp.id')
            ->get();

        $stats = [
            'total'   => DB::table('token_payments')->where('status','paid')->sum('amount'),
            'online'  => DB::table('token_payments')->where('status','paid')->where('payment_method','online')->sum('amount'),
            'cash'    => DB::table('token_payments')->where('status','paid')->where('payment_method','cash')->sum('amount'),
            'pending' => DB::table('token_payments')->where('status','pending')->count(),
        ];

        return view('admin.tokens', compact('payments', 'stats'));
    }
}