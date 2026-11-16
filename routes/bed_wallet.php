<?php

use App\Http\Controllers\Admin\BedPlanController as AdminBedPlan;
use App\Http\Controllers\Owner\BedWalletController as OwnerBedWallet;
use Illuminate\Support\Facades\Route;

/*
 * Bed Plan: the Bed Wallet, the monthly bed price, and everything the admin controls about it.
 * Loaded from routes/web.php.
 */

// ── Owner: wallet page, top-up with Razorpay ──────────────────────────────
Route::middleware(['auth', 'role:owner'])->prefix('owner')->name('owner.')->group(function () {
    Route::get('/bed-wallet', [OwnerBedWallet::class, 'index'])->name('bed-wallet');
    Route::post('/bed-wallet/order', [OwnerBedWallet::class, 'createOrder'])->middleware('throttle:20,1')->name('bed-wallet.order');
    Route::post('/bed-wallet/verify', [OwnerBedWallet::class, 'verify'])->middleware('throttle:20,1')->name('bed-wallet.verify');
});

// ── Admin: full control ───────────────────────────────────────────────────
Route::middleware(['auth', 'role:admin'])->prefix('admin/bed-plans')->name('admin.bed-plans.')->group(function () {
    Route::get('/', [AdminBedPlan::class, 'index'])->name('index');
    Route::post('/settings', [AdminBedPlan::class, 'settings'])->name('settings');

    Route::post('/plans', [AdminBedPlan::class, 'planStore'])->name('plans.store');
    Route::patch('/plans/{plan}', [AdminBedPlan::class, 'planUpdate'])->name('plans.update');

    Route::post('/packages', [AdminBedPlan::class, 'packageStore'])->name('packages.store');
    Route::patch('/packages/{package}', [AdminBedPlan::class, 'packageUpdate'])->name('packages.update');

    Route::post('/activate-all', [AdminBedPlan::class, 'activateAll'])->name('activate-all');
    Route::get('/owners/{owner}', [AdminBedPlan::class, 'owner'])->name('owner');
    Route::post('/owners/{owner}/activate', [AdminBedPlan::class, 'activate'])->name('owner.activate');
    Route::post('/owners/{owner}/disable', [AdminBedPlan::class, 'disable'])->name('owner.disable');
    Route::post('/owners/{owner}/price', [AdminBedPlan::class, 'price'])->name('owner.price');
    Route::post('/owners/{owner}/adjust', [AdminBedPlan::class, 'adjust'])->name('owner.adjust');
});
