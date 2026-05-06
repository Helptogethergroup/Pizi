<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\PropertyController;
use Illuminate\Support\Facades\Route;

/* ---------- PUBLIC ---------- */
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/about', [HomeController::class, 'about'])->name('about');
Route::get('/contact', [HomeController::class, 'contact'])->name('contact');
Route::post('/contact', [HomeController::class, 'contactSubmit'])->name('contact.submit');

Route::get('/search', [PropertyController::class, 'search'])->name('search');

// SEO-friendly URLs
Route::get('/pg-in-{city}', [PropertyController::class, 'city'])->name('city.show');
Route::get('/pg-in-{city}/{locality}', [PropertyController::class, 'locality'])->name('locality.show');
Route::get('/pg/{slug}', [PropertyController::class, 'show'])->name('property.show');
// Landmark-based SEO pages
Route::get('/landmarks', [\App\Http\Controllers\LandmarkController::class, 'index'])->name('landmarks.index');
Route::get('/pg-near-{slug}', [\App\Http\Controllers\LandmarkController::class, 'show'])->name('landmark.show');

Route::post('/leads', [LeadController::class, 'store'])->name('leads.store');

Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/{slug}', [BlogController::class, 'show'])->name('blog.show');

Route::get('/sitemap.xml', [PropertyController::class, 'sitemap'])->name('sitemap');

/* ---------- AUTH ---------- */
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

/* ---------- OWNER ---------- */
Route::middleware(['auth', 'role:owner,admin'])->prefix('owner')->name('owner.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Owner\DashboardController::class, 'index'])->name('dashboard');
    Route::resource('properties', \App\Http\Controllers\Owner\PropertyController::class)
        ->except(['show']);
    Route::patch('properties/{property}/toggle', [\App\Http\Controllers\Owner\PropertyController::class, 'toggle'])
        ->name('properties.toggle');


        // Wallet
    Route::get('/wallet', [\App\Http\Controllers\Owner\WalletController::class, 'index'])->name('wallet');
    
    // Leads (with unlock)
    Route::get('/leads', [\App\Http\Controllers\Owner\LeadController::class, 'index'])->name('leads.index');
    Route::post('/leads/{lead}/unlock', [\App\Http\Controllers\Owner\LeadController::class, 'unlock'])->name('leads.unlock');

    // Credit packages & Razorpay
    Route::get('/packages', [\App\Http\Controllers\Owner\PaymentController::class, 'packages'])->name('packages');
    Route::get('/checkout/{package}', [\App\Http\Controllers\Owner\PaymentController::class, 'checkout'])->name('checkout');
    Route::post('/payment/callback', [\App\Http\Controllers\Owner\PaymentController::class, 'callback'])->name('payment.callback');
    Route::get('/payment/failed', [\App\Http\Controllers\Owner\PaymentController::class, 'failed'])->name('payment.failed');
});

/* ---------- TELECALLER ---------- */
Route::middleware(['auth', 'role:telecaller,admin'])->prefix('telecaller')->name('telecaller.')->group(function () {
    Route::get('/', [\App\Http\Controllers\TeleCaller\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/leads', [\App\Http\Controllers\TeleCaller\LeadController::class, 'index'])->name('leads.index');
    Route::get('/leads/{lead}', [\App\Http\Controllers\TeleCaller\LeadController::class, 'show'])->name('leads.show');
    Route::patch('/leads/{lead}', [\App\Http\Controllers\TeleCaller\LeadController::class, 'update'])->name('leads.update');
    Route::post('/leads/{lead}/visit', [\App\Http\Controllers\TeleCaller\LeadController::class, 'scheduleVisit'])->name('leads.visit');
    Route::get('/leads/{lead}/whatsapp/{property}', [\App\Http\Controllers\TeleCaller\LeadController::class, 'whatsappLink'])->name('leads.whatsapp');
});

/* ---------- ADMIN ---------- */
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard');

    Route::get('/properties', [\App\Http\Controllers\Admin\PropertyController::class, 'index'])->name('properties.index');
    Route::patch('/properties/{property}/verify', [\App\Http\Controllers\Admin\PropertyController::class, 'verify'])->name('properties.verify');
    Route::patch('/properties/{property}/feature', [\App\Http\Controllers\Admin\PropertyController::class, 'feature'])->name('properties.feature');
    Route::delete('/properties/{property}', [\App\Http\Controllers\Admin\PropertyController::class, 'destroy'])->name('properties.destroy');

    Route::get('/leads', [\App\Http\Controllers\Admin\LeadController::class, 'index'])->name('leads.index');
    Route::patch('/leads/{lead}/assign', [\App\Http\Controllers\Admin\LeadController::class, 'assign'])->name('leads.assign');

    Route::get('/users', [\App\Http\Controllers\Admin\UserController::class, 'index'])->name('users.index');
    Route::post('/users', [\App\Http\Controllers\Admin\UserController::class, 'store'])->name('users.store');
    Route::patch('/users/{user}/toggle', [\App\Http\Controllers\Admin\UserController::class, 'toggle'])->name('users.toggle');

    // Wallet management
    Route::get('/wallets', [\App\Http\Controllers\Admin\WalletController::class, 'index'])->name('wallets.index');
    Route::post('/wallets/{user}/adjust', [\App\Http\Controllers\Admin\WalletController::class, 'adjust'])->name('wallets.adjust');
    
    // Lead pricing
    Route::get('/pricing', [\App\Http\Controllers\Admin\PricingController::class, 'index'])->name('pricing.index');
    Route::patch('/pricing', [\App\Http\Controllers\Admin\PricingController::class, 'update'])->name('pricing.update');

    // Credit packages management
    Route::get('/packages', [\App\Http\Controllers\Admin\CreditPackageController::class, 'index'])->name('packages.index');
    Route::post('/packages', [\App\Http\Controllers\Admin\CreditPackageController::class, 'store'])->name('packages.store');
    Route::patch('/packages/{package}/toggle', [\App\Http\Controllers\Admin\CreditPackageController::class, 'toggle'])->name('packages.toggle');
    Route::delete('/packages/{package}', [\App\Http\Controllers\Admin\CreditPackageController::class, 'destroy'])->name('packages.destroy');
});

/* ---------- MANUAL LEAD ENTRY (shared by admin/telecaller/field_executive) ---------- */
Route::middleware(['auth', 'role:admin,telecaller,field_executive'])->group(function () {
    Route::get('/leads/manual/create', [\App\Http\Controllers\ManualLeadController::class, 'create'])
        ->name('leads.manual.create');
    Route::post('/leads/manual', [\App\Http\Controllers\ManualLeadController::class, 'store'])
        ->name('leads.manual.store');
});
