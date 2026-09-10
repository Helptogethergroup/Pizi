<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// ============================================
// PIZI REST API
// Base URL: https://pizi.in/api
// All routes return JSON
// ============================================

// ============ AUTH ROUTES ============
Route::prefix('auth')->group(function () {
    Route::post('/register', [App\Http\Controllers\Api\AuthController::class, 'register']);
    Route::post('/login', [App\Http\Controllers\Api\AuthController::class, 'login']);
    Route::post('/send-otp', [App\Http\Controllers\Api\AuthController::class, 'sendOtp']);
    Route::post('/verify-otp', [App\Http\Controllers\Api\AuthController::class, 'verifyOtp']);
    Route::post('/forgot-password', [App\Http\Controllers\Api\AuthController::class, 'forgotPassword']);
    
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [App\Http\Controllers\Api\AuthController::class, 'logout']);
        Route::get('/me', [App\Http\Controllers\Api\AuthController::class, 'me']);
        Route::patch('/profile', [App\Http\Controllers\Api\AuthController::class, 'updateProfile']);
    });
});

// ============ PUBLIC ROUTES (NO AUTH) ============

// Properties (public listing + search)
Route::prefix('properties')->group(function () {
    Route::get('/', [App\Http\Controllers\Api\PropertyController::class, 'index']);
    Route::get('/featured', [App\Http\Controllers\Api\PropertyController::class, 'featured']);
    Route::get('/{slug}', [App\Http\Controllers\Api\PropertyController::class, 'show']);
    Route::post('/{id}/view', [App\Http\Controllers\Api\PropertyController::class, 'trackView']);
});

// Cities & Localities
Route::get('/cities', [App\Http\Controllers\Api\LocationController::class, 'cities']);
Route::get('/cities/{slug}', [App\Http\Controllers\Api\LocationController::class, 'cityDetail']);
Route::get('/cities/{cityId}/localities', [App\Http\Controllers\Api\LocationController::class, 'localities']);
Route::get('/localities/{slug}', [App\Http\Controllers\Api\LocationController::class, 'localityDetail']);

// Amenities
Route::get('/amenities', [App\Http\Controllers\Api\AmenityController::class, 'index']);

// Blogs
Route::get('/blogs', [App\Http\Controllers\Api\BlogController::class, 'index']);
Route::get('/blogs/{slug}', [App\Http\Controllers\Api\BlogController::class, 'show']);

// Lead submission (contact form)
Route::post('/leads', [App\Http\Controllers\Api\LeadController::class, 'store']);

// SEO settings
Route::get('/seo/{page_key}', [App\Http\Controllers\Api\SeoController::class, 'show']);

// ============ AUTHENTICATED ROUTES ============
Route::middleware('auth:sanctum')->group(function () {

    // ===== USER =====
    Route::get('/user/wishlist', [App\Http\Controllers\Api\WishlistController::class, 'index']);
    Route::post('/user/wishlist/{propertyId}', [App\Http\Controllers\Api\WishlistController::class, 'toggle']);
    Route::get('/user/notifications', [App\Http\Controllers\Api\NotificationController::class, 'index']);
    Route::patch('/user/notifications/{id}/read', [App\Http\Controllers\Api\NotificationController::class, 'markRead']);

    // ===== OWNER ROUTES =====
    Route::prefix('owner')->middleware('role:owner,admin')->group(function () {
        // Properties (owner's own)
        Route::get('/properties', [App\Http\Controllers\Api\Owner\PropertyController::class, 'index']);
        Route::post('/properties', [App\Http\Controllers\Api\Owner\PropertyController::class, 'store']);
        Route::get('/properties/{id}', [App\Http\Controllers\Api\Owner\PropertyController::class, 'show']);
        Route::patch('/properties/{id}', [App\Http\Controllers\Api\Owner\PropertyController::class, 'update']);
        Route::delete('/properties/{id}', [App\Http\Controllers\Api\Owner\PropertyController::class, 'destroy']);
        Route::post('/properties/{id}/images', [App\Http\Controllers\Api\Owner\PropertyController::class, 'uploadImages']);
        Route::delete('/properties/images/{imageId}', [App\Http\Controllers\Api\Owner\PropertyController::class, 'deleteImage']);

        // Dashboard stats
        Route::get('/dashboard', [App\Http\Controllers\Api\Owner\DashboardController::class, 'index']);

        // Tenants
        Route::get('/tenants', [App\Http\Controllers\Api\Owner\TenantController::class, 'index']);
        Route::post('/tenants', [App\Http\Controllers\Api\Owner\TenantController::class, 'store']);
        Route::get('/tenants/{id}', [App\Http\Controllers\Api\Owner\TenantController::class, 'show']);
        Route::patch('/tenants/{id}', [App\Http\Controllers\Api\Owner\TenantController::class, 'update']);
        Route::delete('/tenants/{id}', [App\Http\Controllers\Api\Owner\TenantController::class, 'destroy']);
        Route::patch('/tenants/{id}/kyc/approve', [App\Http\Controllers\Api\Owner\TenantController::class, 'approveKyc']);
        Route::patch('/tenants/{id}/kyc/reject', [App\Http\Controllers\Api\Owner\TenantController::class, 'rejectKyc']);
        Route::patch('/tenants/{id}/status', [App\Http\Controllers\Api\Owner\TenantController::class, 'changeStatus']);
        Route::post('/tenants/{id}/documents', [App\Http\Controllers\Api\Owner\TenantController::class, 'uploadDocument']);
        Route::delete('/tenants/documents/{docId}', [App\Http\Controllers\Api\Owner\TenantController::class, 'deleteDocument']);

        // Rent Bills & Payments
        Route::get('/rent', [App\Http\Controllers\Api\Owner\RentController::class, 'index']);
        Route::post('/rent', [App\Http\Controllers\Api\Owner\RentController::class, 'store']);
        Route::get('/rent/{id}', [App\Http\Controllers\Api\Owner\RentController::class, 'show']);
        Route::delete('/rent/{id}', [App\Http\Controllers\Api\Owner\RentController::class, 'destroy']);
        Route::post('/rent/generate-all', [App\Http\Controllers\Api\Owner\RentController::class, 'generateAll']);
        Route::post('/rent/{id}/payment', [App\Http\Controllers\Api\Owner\RentController::class, 'recordPayment']);
        Route::delete('/rent/payments/{paymentId}', [App\Http\Controllers\Api\Owner\RentController::class, 'deletePayment']);
        Route::get('/rent/receipt/{paymentId}', [App\Http\Controllers\Api\Owner\RentController::class, 'receipt']);

        // Complaints
        Route::get('/complaints', [App\Http\Controllers\Api\Owner\ComplaintController::class, 'index']);
        Route::post('/complaints', [App\Http\Controllers\Api\Owner\ComplaintController::class, 'store']);
        Route::get('/complaints/{id}', [App\Http\Controllers\Api\Owner\ComplaintController::class, 'show']);
        Route::patch('/complaints/{id}/assign', [App\Http\Controllers\Api\Owner\ComplaintController::class, 'assign']);
        Route::patch('/complaints/{id}/status', [App\Http\Controllers\Api\Owner\ComplaintController::class, 'changeStatus']);
        Route::patch('/complaints/{id}/priority', [App\Http\Controllers\Api\Owner\ComplaintController::class, 'changePriority']);
        Route::post('/complaints/{id}/comments', [App\Http\Controllers\Api\Owner\ComplaintController::class, 'addComment']);
        Route::post('/complaints/{id}/media', [App\Http\Controllers\Api\Owner\ComplaintController::class, 'uploadMedia']);
        Route::delete('/complaints/media/{mediaId}', [App\Http\Controllers\Api\Owner\ComplaintController::class, 'deleteMedia']);
        Route::delete('/complaints/{id}', [App\Http\Controllers\Api\Owner\ComplaintController::class, 'destroy']);

        // Rooms & Beds
        Route::get('/rooms', [App\Http\Controllers\Api\Owner\RoomController::class, 'index']);
        Route::post('/rooms', [App\Http\Controllers\Api\Owner\RoomController::class, 'store']);
        Route::get('/rooms/{id}', [App\Http\Controllers\Api\Owner\RoomController::class, 'show']);
        Route::patch('/rooms/{id}', [App\Http\Controllers\Api\Owner\RoomController::class, 'update']);
        Route::delete('/rooms/{id}', [App\Http\Controllers\Api\Owner\RoomController::class, 'destroy']);
        Route::post('/rooms/{id}/beds', [App\Http\Controllers\Api\Owner\RoomController::class, 'storeBed']);
        Route::patch('/beds/{bedId}/assign', [App\Http\Controllers\Api\Owner\RoomController::class, 'assignBed']);
        Route::patch('/beds/{bedId}/unassign', [App\Http\Controllers\Api\Owner\RoomController::class, 'unassignBed']);
        Route::patch('/beds/{bedId}/status', [App\Http\Controllers\Api\Owner\RoomController::class, 'changeBedStatus']);
        Route::delete('/beds/{bedId}', [App\Http\Controllers\Api\Owner\RoomController::class, 'destroyBed']);

        // Agreements
        Route::get('/agreements', [App\Http\Controllers\Api\Owner\AgreementController::class, 'index']);
        Route::post('/agreements', [App\Http\Controllers\Api\Owner\AgreementController::class, 'store']);
        Route::get('/agreements/{id}', [App\Http\Controllers\Api\Owner\AgreementController::class, 'show']);
        Route::patch('/agreements/{id}', [App\Http\Controllers\Api\Owner\AgreementController::class, 'update']);
        Route::post('/agreements/{id}/sign-owner', [App\Http\Controllers\Api\Owner\AgreementController::class, 'signAsOwner']);
        Route::post('/agreements/{id}/sign-tenant', [App\Http\Controllers\Api\Owner\AgreementController::class, 'signAsTenant']);
        Route::patch('/agreements/{id}/terminate', [App\Http\Controllers\Api\Owner\AgreementController::class, 'terminate']);
        Route::post('/agreements/{id}/renew', [App\Http\Controllers\Api\Owner\AgreementController::class, 'renew']);
        Route::delete('/agreements/{id}', [App\Http\Controllers\Api\Owner\AgreementController::class, 'destroy']);

        // Leads (for owner)
        Route::get('/leads', [App\Http\Controllers\Api\Owner\LeadController::class, 'index']);
        Route::get('/leads/{id}', [App\Http\Controllers\Api\Owner\LeadController::class, 'show']);
        Route::post('/leads/{id}/unlock', [App\Http\Controllers\Api\Owner\LeadController::class, 'unlock']);

        // Wallet & Payments
        Route::get('/wallet', [App\Http\Controllers\Api\Owner\WalletController::class, 'index']);
        Route::get('/wallet/transactions', [App\Http\Controllers\Api\Owner\WalletController::class, 'transactions']);
        Route::get('/credit-packages', [App\Http\Controllers\Api\Owner\WalletController::class, 'creditPackages']);
        Route::post('/wallet/purchase', [App\Http\Controllers\Api\Owner\WalletController::class, 'initiatePurchase']);
        Route::post('/wallet/verify-payment', [App\Http\Controllers\Api\Owner\WalletController::class, 'verifyPayment']);
    });

    // ===== ADMIN ROUTES =====
    Route::prefix('admin')->middleware('role:admin')->group(function () {
        Route::get('/dashboard', [App\Http\Controllers\Api\Admin\DashboardController::class, 'index']);
        Route::get('/pg-management', [App\Http\Controllers\Api\Admin\PgManagementController::class, 'index']);

        // All Properties
        Route::get('/properties', [App\Http\Controllers\Api\Admin\PropertyController::class, 'index']);
        Route::get('/properties/{id}', [App\Http\Controllers\Api\Admin\PropertyController::class, 'show']);
        Route::patch('/properties/{id}/verify', [App\Http\Controllers\Api\Admin\PropertyController::class, 'verify']);
        Route::patch('/properties/{id}/feature', [App\Http\Controllers\Api\Admin\PropertyController::class, 'feature']);
        Route::patch('/properties/{id}/assign-owner', [App\Http\Controllers\Api\Admin\PropertyController::class, 'assignOwner']);
        Route::patch('/properties/{id}/pause', [App\Http\Controllers\Api\Admin\PropertyController::class, 'pause']);
        Route::delete('/properties/{id}', [App\Http\Controllers\Api\Admin\PropertyController::class, 'destroy']);

        // All Tenants
        Route::get('/tenants', [App\Http\Controllers\Api\Admin\TenantController::class, 'index']);
        Route::get('/tenants/{id}', [App\Http\Controllers\Api\Admin\TenantController::class, 'show']);
        Route::patch('/tenants/{id}/kyc/approve', [App\Http\Controllers\Api\Admin\TenantController::class, 'approveKyc']);
        Route::patch('/tenants/{id}/kyc/reject', [App\Http\Controllers\Api\Admin\TenantController::class, 'rejectKyc']);
        Route::patch('/tenants/{id}/status', [App\Http\Controllers\Api\Admin\TenantController::class, 'changeStatus']);
        Route::delete('/tenants/{id}', [App\Http\Controllers\Api\Admin\TenantController::class, 'destroy']);

        // All Rent Bills
        Route::get('/rent', [App\Http\Controllers\Api\Admin\RentController::class, 'index']);
        Route::get('/rent/{id}', [App\Http\Controllers\Api\Admin\RentController::class, 'show']);
        Route::delete('/rent/{id}', [App\Http\Controllers\Api\Admin\RentController::class, 'destroy']);
        Route::delete('/rent/payments/{paymentId}', [App\Http\Controllers\Api\Admin\RentController::class, 'deletePayment']);

        // All Complaints
        Route::get('/complaints', [App\Http\Controllers\Api\Admin\ComplaintController::class, 'index']);
        Route::get('/complaints/{id}', [App\Http\Controllers\Api\Admin\ComplaintController::class, 'show']);
        Route::patch('/complaints/{id}/status', [App\Http\Controllers\Api\Admin\ComplaintController::class, 'changeStatus']);
        Route::patch('/complaints/{id}/priority', [App\Http\Controllers\Api\Admin\ComplaintController::class, 'changePriority']);
        Route::post('/complaints/{id}/comments', [App\Http\Controllers\Api\Admin\ComplaintController::class, 'addComment']);
        Route::delete('/complaints/{id}', [App\Http\Controllers\Api\Admin\ComplaintController::class, 'destroy']);

        // All Rooms
        Route::get('/rooms', [App\Http\Controllers\Api\Admin\RoomController::class, 'index']);
        Route::get('/rooms/{id}', [App\Http\Controllers\Api\Admin\RoomController::class, 'show']);
        Route::delete('/rooms/{id}', [App\Http\Controllers\Api\Admin\RoomController::class, 'destroy']);

        // All Agreements
        Route::get('/agreements', [App\Http\Controllers\Api\Admin\AgreementController::class, 'index']);
        Route::get('/agreements/{id}', [App\Http\Controllers\Api\Admin\AgreementController::class, 'show']);
        Route::delete('/agreements/{id}', [App\Http\Controllers\Api\Admin\AgreementController::class, 'destroy']);

        // Users management
        Route::get('/users', [App\Http\Controllers\Api\Admin\UserController::class, 'index']);
        Route::get('/users/{id}', [App\Http\Controllers\Api\Admin\UserController::class, 'show']);
        Route::patch('/users/{id}', [App\Http\Controllers\Api\Admin\UserController::class, 'update']);
        Route::patch('/users/{id}/wallet/credit', [App\Http\Controllers\Api\Admin\UserController::class, 'creditWallet']);
        Route::delete('/users/{id}', [App\Http\Controllers\Api\Admin\UserController::class, 'destroy']);

        // Leads
        Route::get('/leads', [App\Http\Controllers\Api\Admin\LeadController::class, 'index']);
        Route::get('/leads/{id}', [App\Http\Controllers\Api\Admin\LeadController::class, 'show']);
        Route::patch('/leads/{id}/assign', [App\Http\Controllers\Api\Admin\LeadController::class, 'assign']);
        Route::patch('/leads/{id}/status', [App\Http\Controllers\Api\Admin\LeadController::class, 'changeStatus']);
        Route::delete('/leads/{id}', [App\Http\Controllers\Api\Admin\LeadController::class, 'destroy']);

        // Cities & Localities CRUD
        Route::post('/cities', [App\Http\Controllers\Api\Admin\LocationController::class, 'storeCity']);
        Route::patch('/cities/{id}', [App\Http\Controllers\Api\Admin\LocationController::class, 'updateCity']);
        Route::delete('/cities/{id}', [App\Http\Controllers\Api\Admin\LocationController::class, 'deleteCity']);
        Route::post('/localities', [App\Http\Controllers\Api\Admin\LocationController::class, 'storeLocality']);
        Route::patch('/localities/{id}', [App\Http\Controllers\Api\Admin\LocationController::class, 'updateLocality']);
        Route::delete('/localities/{id}', [App\Http\Controllers\Api\Admin\LocationController::class, 'deleteLocality']);

        // Amenities CRUD
        Route::post('/amenities', [App\Http\Controllers\Api\Admin\AmenityController::class, 'store']);
        Route::patch('/amenities/{id}', [App\Http\Controllers\Api\Admin\AmenityController::class, 'update']);
        Route::delete('/amenities/{id}', [App\Http\Controllers\Api\Admin\AmenityController::class, 'destroy']);

        // Blogs CRUD
        Route::get('/blogs', [App\Http\Controllers\Api\Admin\BlogController::class, 'index']);
        Route::post('/blogs', [App\Http\Controllers\Api\Admin\BlogController::class, 'store']);
        Route::patch('/blogs/{id}', [App\Http\Controllers\Api\Admin\BlogController::class, 'update']);
        Route::delete('/blogs/{id}', [App\Http\Controllers\Api\Admin\BlogController::class, 'destroy']);

        // SEO
        Route::get('/seo', [App\Http\Controllers\Api\Admin\SeoController::class, 'index']);
        Route::patch('/seo/{page_key}', [App\Http\Controllers\Api\Admin\SeoController::class, 'update']);

        // Credit Packages
        Route::get('/credit-packages', [App\Http\Controllers\Api\Admin\CreditPackageController::class, 'index']);
        Route::post('/credit-packages', [App\Http\Controllers\Api\Admin\CreditPackageController::class, 'store']);
        Route::patch('/credit-packages/{id}', [App\Http\Controllers\Api\Admin\CreditPackageController::class, 'update']);
        Route::delete('/credit-packages/{id}', [App\Http\Controllers\Api\Admin\CreditPackageController::class, 'destroy']);
    });

    // ===== TELECALLER ROUTES =====
    Route::prefix('telecaller')->middleware('role:telecaller,admin')->group(function () {
        Route::get('/dashboard', [App\Http\Controllers\Api\Telecaller\DashboardController::class, 'index']);
        Route::get('/leads', [App\Http\Controllers\Api\Telecaller\LeadController::class, 'index']);
        Route::get('/leads/{id}', [App\Http\Controllers\Api\Telecaller\LeadController::class, 'show']);
        Route::patch('/leads/{id}/status', [App\Http\Controllers\Api\Telecaller\LeadController::class, 'changeStatus']);
        Route::patch('/leads/{id}/notes', [App\Http\Controllers\Api\Telecaller\LeadController::class, 'updateNotes']);
        Route::post('/leads/{id}/schedule-visit', [App\Http\Controllers\Api\Telecaller\LeadController::class, 'scheduleVisit']);
    });

    // ===== FIELD EXECUTIVE ROUTES =====
    Route::prefix('field')->middleware('role:field_executive,admin')->group(function () {
        Route::get('/dashboard', [App\Http\Controllers\Api\Field\DashboardController::class, 'index']);
        Route::get('/visits', [App\Http\Controllers\Api\Field\VisitController::class, 'index']);
        Route::get('/visits/{id}', [App\Http\Controllers\Api\Field\VisitController::class, 'show']);
        Route::post('/visits/{id}/checkin', [App\Http\Controllers\Api\Field\VisitController::class, 'checkin']);
        Route::post('/visits/{id}/checkout', [App\Http\Controllers\Api\Field\VisitController::class, 'checkout']);
        Route::post('/visits/{id}/media', [App\Http\Controllers\Api\Field\VisitController::class, 'uploadMedia']);
        Route::patch('/visits/{id}/complete', [App\Http\Controllers\Api\Field\VisitController::class, 'complete']);
    });

    // ===== SEO MANAGER ROUTES =====
    Route::prefix('seo-manager')->middleware('role:seo_manager,admin')->group(function () {
        Route::get('/dashboard', [App\Http\Controllers\Api\SeoManager\DashboardController::class, 'index']);
        Route::get('/blogs', [App\Http\Controllers\Api\SeoManager\BlogController::class, 'index']);
        Route::post('/blogs', [App\Http\Controllers\Api\SeoManager\BlogController::class, 'store']);
        Route::patch('/blogs/{id}', [App\Http\Controllers\Api\SeoManager\BlogController::class, 'update']);
        Route::delete('/blogs/{id}', [App\Http\Controllers\Api\SeoManager\BlogController::class, 'destroy']);
        Route::get('/seo-settings', [App\Http\Controllers\Api\SeoManager\SeoController::class, 'index']);
        Route::patch('/seo-settings/{page_key}', [App\Http\Controllers\Api\SeoManager\SeoController::class, 'update']);
    });
});

// ============ FALLBACK ============
Route::fallback(function () {
    return response()->json([
        'success' => false,
        'message' => 'API endpoint not found',
    ], 404);
});