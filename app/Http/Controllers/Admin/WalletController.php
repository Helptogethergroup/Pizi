<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\WalletService;
use Illuminate\Http\Request;
use RuntimeException;

class WalletController extends Controller
{
    public function index(Request $request)
    {
        $owners = User::where('role', 'owner')
            ->with('wallet')
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%' . $request->search . '%';
                $q->where(fn ($qb) => $qb->where('name', 'like', $term)->orWhere('email', 'like', $term));
            })
            ->paginate(20);

        $recentTransactions = WalletTransaction::with('user', 'actionedBy')
            ->latest()->take(20)->get();

        return view('admin.wallets', compact('owners', 'recentTransactions'));
    }

    public function adjust(Request $request, User $user, WalletService $service)
    {
        $data = $request->validate([
            'action' => 'required|in:credit,debit',
            'amount' => 'required|integer|min:1',
            'notes' => 'nullable|string|max:255',
        ]);

        try {
            if ($data['action'] === 'credit') {
                $service->credit($user, $data['amount'], 'admin_credit', null, $data['notes'] ?? null, auth()->user());
                return back()->with('success', "✓ Added {$data['amount']} credits to {$user->name}.");
            } else {
                $service->debit($user, $data['amount'], 'admin_debit', $data['notes'] ?? null, auth()->user());
                return back()->with('success', "✓ Deducted {$data['amount']} credits from {$user->name}.");
            }
        } catch (RuntimeException $e) {
            return back()->withErrors(['amount' => $e->getMessage()]);
        }
    }
}