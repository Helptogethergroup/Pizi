<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Services\WalletService;

class WalletController extends Controller
{
    public function index(WalletService $service)
    {
        $owner = auth()->user();
        $wallet = $service->walletFor($owner);
        $transactions = $wallet->transactions()->paginate(20);

        return view('owner.wallet', compact('wallet', 'transactions'));
    }
}