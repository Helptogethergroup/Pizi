<?php
namespace App\Http\Controllers\Owner;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TokenReportController extends Controller
{
    public function index()
    {
        $ownerId = Auth::id();

        // owner ki properties ke ids
        $propIds = DB::table('properties')->where('owner_id', $ownerId)->pluck('id');

        $base = DB::table('token_payments as tp')
            ->join('tenants as t', 't.id', '=', 'tp.tenant_id')
            ->leftJoin('properties as p', 'p.id', '=', 'tp.property_id')
            ->leftJoin('users as u', 'u.id', '=', 'tp.collected_by')
            ->whereIn('tp.property_id', $propIds);

        $payments = (clone $base)
            ->select('tp.*', 't.name as tenant_name', 't.phone as tenant_phone',
                     'p.name as property_name', 'u.name as collector_name')
            ->orderByDesc('tp.id')->get();

        $stats = [
            'total'   => (clone $base)->where('tp.status','paid')->sum('tp.amount'),
            'online'  => (clone $base)->where('tp.status','paid')->where('tp.payment_method','online')->sum('tp.amount'),
            'cash'    => (clone $base)->where('tp.status','paid')->where('tp.payment_method','cash')->sum('tp.amount'),
            'pending' => (clone $base)->where('tp.status','pending')->count(),
        ];

        return view('owner.tokens', compact('payments', 'stats'));
    }
}