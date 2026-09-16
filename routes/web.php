<?php

Route::get('/debug-properties', function () {
    return response()->json(
        \App\Models\Property::select('id', 'name', 'owner_id')->orderBy('id')->get()
    );
});


Route::get('/check-reviews-table', function() {
    try {
        // Check if table exists
        $exists = \Illuminate\Support\Facades\Schema::hasTable('reviews');
        
        // Get columns
        $columns = $exists ? \Illuminate\Support\Facades\Schema::getColumnListing('reviews') : [];
        
        // Check properties table columns
        $propsHasRatingAvg = \Illuminate\Support\Facades\Schema::hasColumn('properties', 'rating_avg');
        $propsHasRatingCount = \Illuminate\Support\Facades\Schema::hasColumn('properties', 'rating_count');
        
        return response()->json([
            'reviews_table_exists' => $exists,
            'reviews_columns' => $columns,
            'properties_has_rating_avg' => $propsHasRatingAvg,
            'properties_has_rating_count' => $propsHasRatingCount,
        ]);
    } catch (\Exception $e) {
        return '❌ Error: ' . $e->getMessage();
    }
});




// // TEMPORARY // code ///
// Route::get('/pizi-debug-faridabad-2026', function () {
//     $count = \App\Models\Property::where('is_active', true)->where('is_verified', true)
//         ->whereHas('city', fn($c) => $c->where('name', 'Faridabad'))
//         ->count();
//     return "Faridabad verified+active count: " . $count;
// });




Route::get('/pizi-debug-sector21-2026', function () {
    $locality = \App\Models\Locality::where('id', 43)
        ->withCount(['properties' => fn ($q) => $q->where('properties.is_active', true)->where('properties.is_verified', true)])
        ->first();
    return "Sector 21 (locality id 43) live count: " . $locality->properties_count;
});








// 🔍 DEBUG: List all admin routes
Route::get('/debug-admin-routes', function () {
    $routes = collect(\Route::getRoutes())->filter(function($r) {
        return str_starts_with($r->uri(), 'admin/');
    })->map(function($r) {
        return [
            'method' => implode('|', $r->methods()),
            'uri' => $r->uri(),
            'name' => $r->getName(),
        ];
    })->values();
    
    return response()->json($routes);
});































// // ⚠️ TEMPORARY — DELETE AFTER USE
// Route::get('/fix-everything-pizi-{secret}', function ($secret) {
//     if ($secret !== 'atul2026') abort(404);
    
//     try {
//         \Artisan::call('view:clear');
//         \Artisan::call('cache:clear');
//         \Artisan::call('config:clear');
//         \Artisan::call('route:clear');
//         \Artisan::call('optimize:clear');
        
//         // Reset Field Exec password
//         $field = \App\Models\User::where('email', 'ankit@gmail.com')->first();
//         if ($field) {
//             $field->password = bcrypt('ankit123');
//             $field->is_active = 1;
//             $field->save();
//         }
        
//         // Create/Update SEO Manager
//         $seo = \App\Models\User::updateOrCreate(
//             ['email' => 'seo@pizi.in'],
//             [
//                 'name' => 'SEO Manager',
//                 'phone' => '9999900005',
//                 'role' => 'seo_manager',
//                 'password' => bcrypt('seo123'),
//                 'is_active' => 1,
//             ]
//         );
        
//         return response()->json([
//             'ok' => true,
//             'message' => '✓ All cleared + users updated!',
//             'field_executive' => [
//                 'email' => 'ankit@gmail.com',
//                 'password' => 'ankit123',
//             ],
//             'seo_manager' => [
//                 'email' => 'seo@pizi.in',
//                 'password' => 'seo123',
//             ],
//             'time' => now()->format('d M Y, h:i A'),
//         ]);
//     } catch (\Exception $e) {
//         return response()->json([
//             'ok' => false,
//             'error' => $e->getMessage(),
//         ], 500);
//     }
// });

















































use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\PropertyController;
use App\Http\Controllers\ReviewWebController;
use App\Http\Controllers\Owner\OwnerReviewController;
use App\Http\Controllers\Admin\AdminReviewController;
use App\Http\Controllers\ChatController;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


/* ---------- PUBLIC ---------- */
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/about', [HomeController::class, 'about'])->name('about');
Route::get('/contact', [HomeController::class, 'contact'])->name('contact');
Route::post('/contact', [HomeController::class, 'contactSubmit'])->name('contact.submit');
Route::post('/newsletter', [HomeController::class, 'newsletterSubscribe'])->name('newsletter.subscribe');

Route::get('/search', [PropertyController::class, 'search'])->name('search');
Route::get('/search/suggestions', [PropertyController::class, 'suggest'])->name('search.suggestions');

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
Route::get('/pay/{bill_number}', [\App\Http\Controllers\PublicPaymentController::class, 'show'])->name('public.pay');
Route::post('/pay/{bill_number}/callback', [\App\Http\Controllers\PublicPaymentController::class, 'callback'])->name('public.pay.callback');
Route::get('/pay/{bill_number}/failed', [\App\Http\Controllers\PublicPaymentController::class, 'failed'])->name('public.pay.failed');

/* ---------- AUTH ---------- */
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/auth/google/redirect', [\App\Http\Controllers\Auth\GoogleAuthController::class, 'redirect'])->name('google.redirect');
    Route::get('/auth/google/callback', [\App\Http\Controllers\Auth\GoogleAuthController::class, 'callback'])->name('google.callback');
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
    
Route::get('/register-free', function () {
    return view('auth.register-free');
})->name('register.free');

Route::post('/register/send-otp',   [AuthController::class, 'registerSendOtp'])->name('register.sendotp');
Route::post('/register/verify-otp', [AuthController::class, 'registerVerifyOtp'])->name('register.verifyotp');
    
    // ✅ PASSWORD RESET
    Route::get('/forgot-password', [App\Http\Controllers\Auth\PasswordResetController::class, 'request'])->name('password.request');
    Route::post('/forgot-password', [App\Http\Controllers\Auth\PasswordResetController::class, 'email'])->name('password.email');
    Route::get('/reset-password/{token}', [App\Http\Controllers\Auth\PasswordResetController::class, 'show'])->name('password.reset');
    Route::post('/reset-password', [App\Http\Controllers\Auth\PasswordResetController::class, 'update'])->name('password.update');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

/* ---------- OWNER ---------- */
Route::middleware(['auth', 'role:owner,admin,pg_manager', 'owner.paid'])->prefix('owner')->name('owner.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Owner\DashboardController::class, 'index'])->name('dashboard');
    Route::resource('properties', \App\Http\Controllers\Owner\PropertyController::class)
        ->except(['show']);
    Route::patch('properties/{property}/toggle', [\App\Http\Controllers\Owner\PropertyController::class, 'toggle'])
        ->name('properties.toggle');
        
//         Route::get('/pg-managers', [\App\Http\Controllers\Owner\PgManagerController::class, 'index'])->name('pg-managers.index');
// Route::post('/pg-managers', [\App\Http\Controllers\Owner\PgManagerController::class, 'store'])->name('pg-managers.store');
// Route::patch('/pg-managers/{manager}', [\App\Http\Controllers\Owner\PgManagerController::class, 'updateProperties'])->name('pg-managers.update');
// Route::patch('/pg-managers/{manager}/toggle', [\App\Http\Controllers\Owner\PgManagerController::class, 'toggle'])->name('pg-managers.toggle');
// Route::delete('/pg-managers/{manager}', [\App\Http\Controllers\Owner\PgManagerController::class, 'destroy'])->name('pg-managers.destroy');
        
        
        // ============ RENT AGREEMENTS ============
    Route::get('/agreements', [\App\Http\Controllers\Owner\AgreementController::class, 'index'])->name('agreements.index');
    Route::get('/agreements/create', [\App\Http\Controllers\Owner\AgreementController::class, 'create'])->name('agreements.create');
    Route::post('/agreements', [\App\Http\Controllers\Owner\AgreementController::class, 'store'])->name('agreements.store');
    Route::get('/agreements/{agreement}', [\App\Http\Controllers\Owner\AgreementController::class, 'show'])->name('agreements.show');
    Route::get('/agreements/{agreement}/edit', [\App\Http\Controllers\Owner\AgreementController::class, 'edit'])->name('agreements.edit');
    Route::patch('/agreements/{agreement}', [\App\Http\Controllers\Owner\AgreementController::class, 'update'])->name('agreements.update');
    Route::get('/agreements/{agreement}/preview', [\App\Http\Controllers\Owner\AgreementController::class, 'preview'])->name('agreements.preview');
    Route::post('/agreements/{agreement}/sign-owner', [\App\Http\Controllers\Owner\AgreementController::class, 'signAsOwner'])->name('agreements.sign.owner');
    Route::post('/agreements/{agreement}/sign-tenant', [\App\Http\Controllers\Owner\AgreementController::class, 'signAsTenant'])->name('agreements.sign.tenant');
    Route::patch('/agreements/{agreement}/terminate', [\App\Http\Controllers\Owner\AgreementController::class, 'terminate'])->name('agreements.terminate');
    Route::post('/agreements/{agreement}/renew', [\App\Http\Controllers\Owner\AgreementController::class, 'renew'])->name('agreements.renew');
    Route::delete('/agreements/{agreement}', [\App\Http\Controllers\Owner\AgreementController::class, 'destroy'])->name('agreements.destroy');
    Route::get('/agreements/{agreement}/download-pdf', [\App\Http\Controllers\Owner\AgreementController::class, 'downloadPdf'])->name('agreements.download-pdf');
        
        
        // ============ ROOMS & BEDS ============
    Route::get('/rooms', [\App\Http\Controllers\Owner\RoomController::class, 'index'])->name('rooms.index');
    Route::get('/rooms/create', [\App\Http\Controllers\Owner\RoomController::class, 'create'])->name('rooms.create');
    Route::get('/rooms/trash', [\App\Http\Controllers\Owner\RoomController::class, 'trash'])->name('rooms.trash');
    Route::post('/rooms', [\App\Http\Controllers\Owner\RoomController::class, 'store'])->name('rooms.store');
    Route::get('/rooms/{room}', [\App\Http\Controllers\Owner\RoomController::class, 'show'])->name('rooms.show');
    Route::get('/rooms/{room}/edit', [\App\Http\Controllers\Owner\RoomController::class, 'edit'])->name('rooms.edit');
    Route::patch('/rooms/{room}', [\App\Http\Controllers\Owner\RoomController::class, 'update'])->name('rooms.update');
    Route::delete('/rooms/{room}', [\App\Http\Controllers\Owner\RoomController::class, 'destroy'])->name('rooms.destroy');
    Route::post('/rooms/bulk-delete', [\App\Http\Controllers\Owner\RoomController::class, 'bulkDestroy'])->name('rooms.bulkDestroy');
    Route::post('/rooms/{room}/restore', [\App\Http\Controllers\Owner\RoomController::class, 'restore'])->name('rooms.restore');
    Route::delete('/rooms/{room}/force-delete', [\App\Http\Controllers\Owner\RoomController::class, 'forceDelete'])->name('rooms.force-delete');
    Route::post('/rooms/{room}/beds', [\App\Http\Controllers\Owner\RoomController::class, 'storeBed'])->name('rooms.beds.store');
    Route::patch('/beds/{bed}/assign', [\App\Http\Controllers\Owner\RoomController::class, 'assignBed'])->name('rooms.beds.assign');
    Route::patch('/beds/{bed}/unassign', [\App\Http\Controllers\Owner\RoomController::class, 'unassignBed'])->name('rooms.beds.unassign');
    Route::patch('/beds/{bed}/status', [\App\Http\Controllers\Owner\RoomController::class, 'changeBedStatus'])->name('rooms.beds.status');
    Route::delete('/beds/{bed}', [\App\Http\Controllers\Owner\RoomController::class, 'destroyBed'])->name('rooms.beds.destroy');
        
        
        
        // ============ TENANTS ============
    Route::get('/tenants', [\App\Http\Controllers\Owner\TenantController::class, 'index'])->name('tenants.index');
    Route::get('/tenants/create', [\App\Http\Controllers\Owner\TenantController::class, 'create'])->name('tenants.create');
    Route::post('/tenants', [\App\Http\Controllers\Owner\TenantController::class, 'store'])->name('tenants.store');
    Route::get('/tenants/{tenant}', [\App\Http\Controllers\Owner\TenantController::class, 'show'])->name('tenants.show');
    Route::get('/tenants/{tenant}/edit', [\App\Http\Controllers\Owner\TenantController::class, 'edit'])->name('tenants.edit');
    Route::patch('/tenants/{tenant}', [\App\Http\Controllers\Owner\TenantController::class, 'update'])->name('tenants.update');
    Route::post('/tenants/{tenant}/claim', [\App\Http\Controllers\Owner\TenantController::class, 'claim'])->name('tenants.claim');
    Route::get('/tenants/vacant-beds/{property}', [\App\Http\Controllers\Owner\TenantController::class, 'vacantBeds'])->name('tenants.vacant-beds');
    Route::post('/tenants/aadhaar/start', [\App\Http\Controllers\Owner\TenantController::class, 'aadhaarStart'])->name('tenants.aadhaar.start');
    Route::post('/tenants/aadhaar/verify-captcha', [\App\Http\Controllers\Owner\TenantController::class, 'aadhaarVerifyCaptcha'])->name('tenants.aadhaar.verifycaptcha');
    Route::post('/tenants/aadhaar/verify-otp', [\App\Http\Controllers\Owner\TenantController::class, 'aadhaarVerifyOtp'])->name('tenants.aadhaar.verifyotp');
    Route::get('/guide', [\App\Http\Controllers\Owner\GuideController::class, 'show'])->name('guide.show');
Route::post('/guide/dismiss', [\App\Http\Controllers\Owner\GuideController::class, 'dismiss'])->name('guide.dismiss');
  // ---- KYC ----
    Route::get('/kyc',           [\App\Http\Controllers\TenantPortalController::class, 'kycPage'])->name('kyc');
    Route::get('/kyc/aadhaar',              [\App\Http\Controllers\TenantPortalController::class, 'aadhaarKycPage'])->name('kyc.aadhaar');
   Route::post('/kyc/aadhaar/send-otp',    [\App\Http\Controllers\TenantPortalController::class, 'aadhaarSendOtp'])->name('kyc.aadhaar.sendotp');
   Route::post('/kyc/aadhaar/verify-otp',  [\App\Http\Controllers\TenantPortalController::class, 'aadhaarVerifyOtp'])->name('kyc.aadhaar.verifyotp');
  
    Route::post('/kyc/upload',   [\App\Http\Controllers\TenantPortalController::class, 'kycUpload'])->name('kyc.upload');
    Route::delete('/kyc/{type}', [\App\Http\Controllers\TenantPortalController::class, 'kycDelete'])->name('kyc.delete');
    Route::post('/kyc/submit',   [\App\Http\Controllers\TenantPortalController::class, 'kycSubmit'])->name('kyc.submit');
    
    Route::delete('/tenants/{tenant}', [\App\Http\Controllers\Owner\TenantController::class, 'destroy'])->name('tenants.destroy');
    Route::post('/tenants/{tenant}/documents', [\App\Http\Controllers\Owner\TenantController::class, 'uploadDocument'])->name('tenants.documents.upload');
    Route::delete('/tenants/documents/{document}', [\App\Http\Controllers\Owner\TenantController::class, 'deleteDocument'])->name('tenants.documents.delete');
    Route::patch('/tenants/{tenant}/kyc/approve', [\App\Http\Controllers\Owner\TenantController::class, 'approveKyc'])->name('tenants.kyc.approve');
    Route::patch('/tenants/{tenant}/kyc/reject', [\App\Http\Controllers\Owner\TenantController::class, 'rejectKyc'])->name('tenants.kyc.reject');
    Route::patch('/tenants/{tenant}/status', [\App\Http\Controllers\Owner\TenantController::class, 'changeStatus'])->name('tenants.status');
    
    
    // ============ RENT COLLECTION ============
    Route::get('/rent', [\App\Http\Controllers\Owner\RentController::class, 'index'])->name('rent.index');
    Route::get('/rent/create', [\App\Http\Controllers\Owner\RentController::class, 'create'])->name('rent.create');
    Route::post('/rent', [\App\Http\Controllers\Owner\RentController::class, 'store'])->name('rent.store');
    Route::post('/rent/generate-all', [\App\Http\Controllers\Owner\RentController::class, 'generateAll'])->name('rent.generateAll');
    Route::get('/rent/{bill}', [\App\Http\Controllers\Owner\RentController::class, 'show'])->name('rent.show');
    Route::delete('/rent/{bill}', [\App\Http\Controllers\Owner\RentController::class, 'destroy'])->name('rent.destroy');
    Route::get('/rent-archive', [\App\Http\Controllers\Owner\RentController::class, 'archive'])->name('rent.archive');
    Route::post('/rent/{id}/restore', [\App\Http\Controllers\Owner\RentController::class, 'restore'])->name('rent.restore');
    Route::post('/rent/{bill}/payment', [\App\Http\Controllers\Owner\RentController::class, 'recordPayment'])->name('rent.payment');
    Route::delete('/rent/payments/{payment}', [\App\Http\Controllers\Owner\RentController::class, 'deletePayment'])->name('rent.payment.delete');
    Route::get('/rent/receipt/{payment}', [\App\Http\Controllers\Owner\RentController::class, 'receipt'])->name('rent.receipt');
   Route::post('/rent/{bill}/reminder', [\App\Http\Controllers\Owner\RentController::class, 'sendReminder'])->name('rent.reminder');


// ============ COMPLAINTS ============
    Route::get('/complaints', [\App\Http\Controllers\Owner\ComplaintController::class, 'index'])->name('complaints.index');
    Route::get('/complaints/create', [\App\Http\Controllers\Owner\ComplaintController::class, 'create'])->name('complaints.create');
    Route::post('/complaints', [\App\Http\Controllers\Owner\ComplaintController::class, 'store'])->name('complaints.store');
    Route::get('/complaints/{complaint}', [\App\Http\Controllers\Owner\ComplaintController::class, 'show'])->name('complaints.show');
    Route::patch('/complaints/{complaint}/assign', [\App\Http\Controllers\Owner\ComplaintController::class, 'assign'])->name('complaints.assign');
    Route::patch('/complaints/{complaint}/status', [\App\Http\Controllers\Owner\ComplaintController::class, 'changeStatus'])->name('complaints.status');
    Route::patch('/complaints/{complaint}/priority', [\App\Http\Controllers\Owner\ComplaintController::class, 'changePriority'])->name('complaints.priority');
    Route::post('/complaints/{complaint}/comments', [\App\Http\Controllers\Owner\ComplaintController::class, 'addComment'])->name('complaints.comments');
    Route::post('/complaints/{complaint}/media', [\App\Http\Controllers\Owner\ComplaintController::class, 'uploadMedia'])->name('complaints.media');
    Route::delete('/complaints/media/{media}', [\App\Http\Controllers\Owner\ComplaintController::class, 'deleteMedia'])->name('complaints.media.delete');
    Route::delete('/complaints/{complaint}', [\App\Http\Controllers\Owner\ComplaintController::class, 'destroy'])->name('complaints.destroy');

        // Blogs
    Route::get('/blogs', [\App\Http\Controllers\Owner\BlogController::class, 'index'])->name('blogs.index');
    Route::get('/blogs/create', [\App\Http\Controllers\Owner\BlogController::class, 'create'])->name('blogs.create');
    Route::post('/blogs', [\App\Http\Controllers\Owner\BlogController::class, 'store'])->name('blogs.store');
    Route::get('/blogs/{blog}/edit', [\App\Http\Controllers\Owner\BlogController::class, 'edit'])->name('blogs.edit');
    Route::patch('/blogs/{blog}', [\App\Http\Controllers\Owner\BlogController::class, 'update'])->name('blogs.update');
    Route::delete('/blogs/{blog}', [\App\Http\Controllers\Owner\BlogController::class, 'destroy'])->name('blogs.destroy');


        // Wallet
    Route::get('/wallet', [\App\Http\Controllers\Owner\WalletController::class, 'index'])->name('wallet');
    
    // Leads (with unlock) — PG Manager needs 'leads' feature
    Route::middleware([\App\Http\Middleware\CheckFeature::class . ':leads'])->group(function () {
        Route::get('/leads', [\App\Http\Controllers\Owner\LeadController::class, 'index'])->name('leads.index');
        Route::get('/leads/{lead}', [\App\Http\Controllers\Owner\LeadController::class, 'show'])->name('leads.show');
        Route::post('/leads/{lead}/unlock', [\App\Http\Controllers\Owner\LeadController::class, 'unlock'])->name('leads.unlock');
        Route::post('/leads/{lead}/report-junk', [\App\Http\Controllers\Owner\LeadController::class, 'reportJunk'])->name('leads.reportJunk');
        Route::patch('/leads/{lead}/status', [\App\Http\Controllers\Owner\LeadController::class, 'updateStatus'])->name('leads.updateStatus');
        Route::patch('/leads/{lead}/inquiry-type', [\App\Http\Controllers\Owner\LeadController::class, 'updateInquiryType'])->name('leads.updateInquiryType');
        Route::post('/leads/{lead}/remark', [\App\Http\Controllers\Owner\LeadController::class, 'addRemark'])->name('leads.addRemark');
        Route::get('/leads/{lead}/timeline', [\App\Http\Controllers\Owner\LeadController::class, 'timeline'])->name('leads.timeline');
    });

    // Analytics
    Route::get('/analytics', [\App\Http\Controllers\Owner\AnalyticsController::class, 'index'])->name('analytics');

    // Credit packages & Razorpay
    Route::get('/packages', [\App\Http\Controllers\Owner\PaymentController::class, 'packages'])->name('packages');
    Route::get('/checkout/{package}', [\App\Http\Controllers\Owner\PaymentController::class, 'checkout'])->name('checkout');

    Route::get('/invoices', [\App\Http\Controllers\Owner\InvoiceController::class, 'index'])->name('invoices.index');
    Route::get('/invoices/{invoice}/download', [\App\Http\Controllers\InvoiceDownloadController::class, 'download'])->name('invoices.download');

    Route::get('/billing', [\App\Http\Controllers\Owner\BillingController::class, 'edit'])->name('billing.edit');
    Route::post('/billing', [\App\Http\Controllers\Owner\BillingController::class, 'update'])->name('billing.update');
    Route::post('/payment/callback', [\App\Http\Controllers\Owner\PaymentController::class, 'callback'])
    ->name('payment.callback')
    ->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
    Route::get('/payment/failed', [\App\Http\Controllers\Owner\PaymentController::class, 'failed'])->name('payment.failed');
});

/* ---------- TELECALLER ---------- */
Route::middleware(['auth', 'role:telecaller,admin'])->prefix('telecaller')->name('telecaller.')->group(function () {
    Route::get('/', [\App\Http\Controllers\TeleCaller\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/leads', [\App\Http\Controllers\TeleCaller\LeadController::class, 'index'])->name('leads.index');
    Route::get('/leads/{lead}', [\App\Http\Controllers\TeleCaller\LeadController::class, 'show'])->name('leads.show');
    Route::patch('/leads/{lead}', [\App\Http\Controllers\TeleCaller\LeadController::class, 'update'])->name('leads.update');
    Route::post('/leads/{lead}/visit', [\App\Http\Controllers\TeleCaller\LeadController::class, 'scheduleVisit'])->name('leads.visit');
    Route::post('/leads/{lead}/call', [\App\Http\Controllers\TeleCaller\LeadController::class, 'markCalled'])->name('leads.call');
    Route::get('/leads/{lead}/whatsapp/{property}', [\App\Http\Controllers\TeleCaller\LeadController::class, 'whatsappLink'])->name('leads.whatsapp');
    Route::patch('/leads/{lead}/verify', [\App\Http\Controllers\TeleCaller\LeadController::class, 'markVerified'])->name('leads.verify');

    Route::get('/owner-prospects', [\App\Http\Controllers\TeleCaller\OwnerProspectController::class, 'index'])->name('owner-prospects.index');
    Route::get('/owner-prospects/create', [\App\Http\Controllers\TeleCaller\OwnerProspectController::class, 'create'])->name('owner-prospects.create');
    Route::post('/owner-prospects', [\App\Http\Controllers\TeleCaller\OwnerProspectController::class, 'store'])->name('owner-prospects.store');
    Route::get('/owner-prospects/{ownerProspect}', [\App\Http\Controllers\TeleCaller\OwnerProspectController::class, 'show'])->name('owner-prospects.show');
    Route::post('/owner-prospects/{ownerProspect}/call', [\App\Http\Controllers\TeleCaller\OwnerProspectController::class, 'markCalled'])->name('owner-prospects.call');
    Route::post('/owner-prospects/{ownerProspect}/stage', [\App\Http\Controllers\TeleCaller\OwnerProspectController::class, 'markStage'])->name('owner-prospects.stage');
});

/* ---------- ADMIN ---------- */
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard');

     Route::post('/admin/tenants/{tenant}/approve-kyc', [\App\Http\Controllers\Admin\TenantController::class, 'approveKyc'])->name('admin.tenants.approve-kyc');
    Route::get('/properties', [\App\Http\Controllers\Admin\PropertyController::class, 'index'])->name('properties.index');
   Route::get('/chat-analytics', [App\Http\Controllers\ChatController::class, 'adminAnalytics'])->name('chat-analytics'); 
    Route::get('/properties/{property}/assign', [\App\Http\Controllers\Admin\PropertyController::class, 'showAssignForm'])->name('properties.assignForm');
    Route::patch('/properties/{property}/assign', [\App\Http\Controllers\Admin\PropertyController::class, 'assignOwner'])->name('properties.assign');
    Route::patch('/properties/{property}/verify', [\App\Http\Controllers\Admin\PropertyController::class, 'verify'])->name('properties.verify');
    Route::patch('/properties/{property}/feature', [\App\Http\Controllers\Admin\PropertyController::class, 'feature'])->name('properties.feature');
    Route::delete('/properties/{property}', [\App\Http\Controllers\Admin\PropertyController::class, 'destroy'])->name('properties.destroy');
    Route::patch('/properties/{property}/toggle', [\App\Http\Controllers\Admin\PropertyController::class, 'toggle'])->name('properties.toggle');

    Route::get('/ad-lead-forms', [\App\Http\Controllers\Admin\AdLeadFormController::class, 'index'])->name('ad-lead-forms.index');
    Route::post('/ad-lead-forms', [\App\Http\Controllers\Admin\AdLeadFormController::class, 'store'])->name('ad-lead-forms.store');
    Route::delete('/ad-lead-forms/{adLeadForm}', [\App\Http\Controllers\Admin\AdLeadFormController::class, 'destroy'])->name('ad-lead-forms.destroy');

    Route::get('/telecallers', [\App\Http\Controllers\Admin\TelecallerMonitoringController::class, 'index'])->name('telecallers.index');
    Route::patch('/telecallers/{telecaller}/target', [\App\Http\Controllers\Admin\TelecallerMonitoringController::class, 'updateTarget'])->name('telecallers.target');

    Route::get('/invoices', [\App\Http\Controllers\Admin\InvoiceController::class, 'index'])->name('invoices.index');
    Route::get('/invoices/create', [\App\Http\Controllers\Admin\InvoiceController::class, 'create'])->name('invoices.create');
    Route::post('/invoices', [\App\Http\Controllers\Admin\InvoiceController::class, 'store'])->name('invoices.store');
    Route::get('/invoices/{invoice}/edit', [\App\Http\Controllers\Admin\InvoiceController::class, 'edit'])->name('invoices.edit');
    Route::put('/invoices/{invoice}', [\App\Http\Controllers\Admin\InvoiceController::class, 'update'])->name('invoices.update');
    Route::delete('/invoices/{invoice}', [\App\Http\Controllers\Admin\InvoiceController::class, 'destroy'])->name('invoices.destroy');
    Route::post('/invoices/{invoice}/send', [\App\Http\Controllers\Admin\InvoiceController::class, 'send'])->name('invoices.send');
    Route::get('/invoices/{invoice}/download', [\App\Http\Controllers\InvoiceDownloadController::class, 'download'])->name('invoices.download');

    Route::get('/leads', [\App\Http\Controllers\Admin\LeadController::class, 'index'])->name('leads.index');
    Route::patch('/leads/{lead}/assign', [\App\Http\Controllers\Admin\LeadController::class, 'assign'])->name('leads.assign');
    Route::patch('/leads/{lead}/verify', [\App\Http\Controllers\Admin\LeadController::class, 'markVerified'])->name('leads.verify');
    Route::delete('/leads/{lead}', [\App\Http\Controllers\Admin\LeadController::class, 'destroy'])->name('leads.destroy');
   

    Route::get('/users', [\App\Http\Controllers\Admin\UserController::class, 'index'])->name('users.index');
    Route::post('/users', [\App\Http\Controllers\Admin\UserController::class, 'store'])->name('users.store');
    Route::patch('/users/{user}/toggle', [\App\Http\Controllers\Admin\UserController::class, 'toggle'])->name('users.toggle');
    Route::get('/users/{user}', [\App\Http\Controllers\Admin\UserController::class, 'show'])->name('users.show');
    Route::patch('/users/{user}', [\App\Http\Controllers\Admin\UserController::class, 'update'])->name('users.update');
    Route::patch('/users/{user}/reset-password', [\App\Http\Controllers\Admin\UserController::class, 'resetPassword'])->name('users.resetPassword');
    Route::delete('/users/{user}', [\App\Http\Controllers\Admin\UserController::class, 'destroy'])->name('users.destroy');
    Route::get('/login-activity', [\App\Http\Controllers\Admin\UserController::class, 'loginActivity'])->name('users.activity');
    
    Route::get('/properties/create', [\App\Http\Controllers\Admin\PropertyController::class, 'create'])->name('properties.create');
    Route::post('/properties', [\App\Http\Controllers\Admin\PropertyController::class, 'store'])->name('properties.store');
    
    
    // ============ ADMIN: AGREEMENTS ============
    Route::get('/agreements', [\App\Http\Controllers\Admin\AgreementController::class, 'index'])->name('agreements.index');
    Route::get('/agreements/{agreement}', [\App\Http\Controllers\Admin\AgreementController::class, 'show'])->name('agreements.show');
    Route::get('/agreements/{agreement}/preview', [\App\Http\Controllers\Admin\AgreementController::class, 'preview'])->name('agreements.preview');
    Route::delete('/agreements/{agreement}', [\App\Http\Controllers\Admin\AgreementController::class, 'destroy'])->name('agreements.destroy');
    
    
    // ============ ADMIN: TENANTS ============
    Route::get('/tenants', [\App\Http\Controllers\Admin\TenantController::class, 'index'])->name('tenants.index');
    Route::get('/tenants/{tenant}', [\App\Http\Controllers\Admin\TenantController::class, 'show'])->name('tenants.show');
    Route::patch('/tenants/{tenant}/kyc/approve', [\App\Http\Controllers\Admin\TenantController::class, 'approveKyc'])->name('tenants.kyc.approve');
    Route::patch('/tenants/{tenant}/kyc/reject', [\App\Http\Controllers\Admin\TenantController::class, 'rejectKyc'])->name('tenants.kyc.reject');
    Route::patch('/tenants/{tenant}/status', [\App\Http\Controllers\Admin\TenantController::class, 'changeStatus'])->name('tenants.status');
    Route::delete('/tenants/{tenant}', [\App\Http\Controllers\Admin\TenantController::class, 'destroy'])->name('tenants.destroy');

    // ============ ADMIN: RENT ============
    Route::get('/rent', [\App\Http\Controllers\Admin\RentController::class, 'index'])->name('rent.index');
    Route::get('/rent/{bill}', [\App\Http\Controllers\Admin\RentController::class, 'show'])->name('rent.show');
    Route::get('/rent/receipt/{payment}', [\App\Http\Controllers\Admin\RentController::class, 'receipt'])->name('rent.receipt');
    Route::delete('/rent/{bill}', [\App\Http\Controllers\Admin\RentController::class, 'destroy'])->name('rent.destroy');
    Route::delete('/rent/payments/{payment}', [\App\Http\Controllers\Admin\RentController::class, 'deletePayment'])->name('rent.payment.delete');

    // ============ ADMIN: COMPLAINTS ============
    Route::get('/complaints', [\App\Http\Controllers\Admin\ComplaintController::class, 'index'])->name('complaints.index');
    Route::get('/complaints/{complaint}', [\App\Http\Controllers\Admin\ComplaintController::class, 'show'])->name('complaints.show');
    Route::patch('/complaints/{complaint}/status', [\App\Http\Controllers\Admin\ComplaintController::class, 'changeStatus'])->name('complaints.status');
    Route::patch('/complaints/{complaint}/priority', [\App\Http\Controllers\Admin\ComplaintController::class, 'changePriority'])->name('complaints.priority');
    Route::post('/complaints/{complaint}/comments', [\App\Http\Controllers\Admin\ComplaintController::class, 'addComment'])->name('complaints.comments');
    Route::delete('/complaints/{complaint}', [\App\Http\Controllers\Admin\ComplaintController::class, 'destroy'])->name('complaints.destroy');
    
    
    // ============ ADMIN: ROOMS ============
    Route::get('/rooms', [\App\Http\Controllers\Admin\RoomController::class, 'index'])->name('rooms.index');
    Route::get('/rooms/{room}', [\App\Http\Controllers\Admin\RoomController::class, 'show'])->name('rooms.show');
    Route::delete('/rooms/{room}', [\App\Http\Controllers\Admin\RoomController::class, 'destroy'])->name('rooms.destroy');

    // ============ ADMIN: PG MANAGEMENT OVERVIEW ============
    Route::get('/pg-management', [\App\Http\Controllers\Admin\PgManagementController::class, 'index'])->name('pg.overview');

    // Analytics
    Route::get('/analytics', [\App\Http\Controllers\Admin\AnalyticsController::class, 'index'])->name('analytics.index');
    
    // Blogs
    Route::get('/blogs', [\App\Http\Controllers\Admin\BlogController::class, 'index'])->name('blogs.index');
    Route::post('/blogs/ai-assist', [\App\Http\Controllers\Admin\BlogController::class, 'aiAssist'])->name('blogs.ai-assist');
    Route::get('/blogs/create', [\App\Http\Controllers\Admin\BlogController::class, 'create'])->name('blogs.create');
    Route::post('/blogs', [\App\Http\Controllers\Admin\BlogController::class, 'store'])->name('blogs.store');
    Route::get('/blogs/{blog}/edit', [\App\Http\Controllers\Admin\BlogController::class, 'edit'])->name('blogs.edit');
    Route::patch('/blogs/{blog}', [\App\Http\Controllers\Admin\BlogController::class, 'update'])->name('blogs.update');
    Route::patch('/blogs/{blog}/toggle', [\App\Http\Controllers\Admin\BlogController::class, 'togglePublish'])->name('blogs.toggle');
    Route::delete('/blogs/{blog}', [\App\Http\Controllers\Admin\BlogController::class, 'destroy'])->name('blogs.destroy');

    // Wallet management
    Route::get('/wallets', [\App\Http\Controllers\Admin\WalletController::class, 'index'])->name('wallets.index');
    Route::post('/wallets/{user}/adjust', [\App\Http\Controllers\Admin\WalletController::class, 'adjust'])->name('wallets.adjust');
    
    // Lead pricing
    Route::get('/pricing', [\App\Http\Controllers\Admin\PricingController::class, 'index'])->name('pricing.index');
    Route::patch('/pricing', [\App\Http\Controllers\Admin\PricingController::class, 'update'])->name('pricing.update');

    // Credit packages management
    Route::get('/packages', [\App\Http\Controllers\Admin\CreditPackageController::class, 'index'])->name('packages.index');
    Route::post('/packages', [\App\Http\Controllers\Admin\CreditPackageController::class, 'store'])->name('packages.store');
    Route::patch('/packages/{package}', [\App\Http\Controllers\Admin\CreditPackageController::class, 'update'])->name('packages.update');
    Route::patch('/packages/{package}/toggle', [\App\Http\Controllers\Admin\CreditPackageController::class, 'toggle'])->name('packages.toggle');
    Route::delete('/packages/{package}', [\App\Http\Controllers\Admin\CreditPackageController::class, 'destroy'])->name('packages.destroy');

    // Field tracker
    Route::get('/field-tracker', [\App\Http\Controllers\Admin\FieldTrackerController::class, 'index'])->name('field-tracker.index');
});


   Route::middleware(['auth', 'role:owner,admin'])->prefix('owner')->name('owner.')->group(function () {
    Route::get('/pg-managers', [\App\Http\Controllers\Owner\PgManagerController::class, 'index'])->name('pg-managers.index');
    Route::post('/pg-managers', [\App\Http\Controllers\Owner\PgManagerController::class, 'store'])->name('pg-managers.store');
    Route::patch('/pg-managers/{manager}', [\App\Http\Controllers\Owner\PgManagerController::class, 'updateProperties'])->name('pg-managers.update');
    Route::patch('/pg-managers/{manager}/toggle', [\App\Http\Controllers\Owner\PgManagerController::class, 'toggle'])->name('pg-managers.toggle');
    Route::delete('/pg-managers/{manager}', [\App\Http\Controllers\Owner\PgManagerController::class, 'destroy'])->name('pg-managers.destroy');
});




/* ---------- MANUAL LEAD ENTRY (shared by admin/telecaller/field_executive) ---------- */
Route::middleware(['auth', 'role:admin,telecaller,field_executive'])->group(function () {
    Route::get('/leads/manual/create', [\App\Http\Controllers\ManualLeadController::class, 'create'])
        ->name('leads.manual.create');
    Route::post('/leads/manual', [\App\Http\Controllers\ManualLeadController::class, 'store'])
        ->name('leads.manual.store');
});


// ============ FIELD EXECUTIVE ============
Route::middleware(['auth', 'role:field_executive,admin'])
    ->prefix('field')
    ->name('field.')
    ->group(function () {
        Route::get('/', [\App\Http\Controllers\Field\DashboardController::class, 'index'])->name('dashboard');
        Route::get('/visits', [\App\Http\Controllers\Field\VisitController::class, 'index'])->name('visits.index');
        Route::get('/visits/{visit}', [\App\Http\Controllers\Field\VisitController::class, 'show'])->name('visits.show');
        Route::post('/visits/{visit}/start', [\App\Http\Controllers\Field\VisitController::class, 'start'])->name('visits.start');
        Route::post('/visits/{visit}/verify', [\App\Http\Controllers\Field\VisitController::class, 'verify'])->name('visits.verify');
        Route::post('/visits/{visit}/media', [\App\Http\Controllers\Field\VisitController::class, 'uploadMedia'])->name('visits.media');
        Route::post('/visits/{visit}/complete', [\App\Http\Controllers\Field\VisitController::class, 'complete'])->name('visits.complete');
    });

// ============ SEO MANAGER ============
Route::middleware(['auth', 'role:seo_manager,admin'])
    ->prefix('seo')
    ->name('seo.')
    ->group(function () {
        Route::get('/', [\App\Http\Controllers\Seo\DashboardController::class, 'index'])->name('dashboard');
        Route::resource('settings', \App\Http\Controllers\Seo\SettingController::class);
        
        // 🔥 BLOGS for SEO Manager
        Route::get('/blogs', [\App\Http\Controllers\Seo\BlogController::class, 'index'])->name('blogs.index');
        Route::get('/blogs/create', [\App\Http\Controllers\Seo\BlogController::class, 'create'])->name('blogs.create');
        Route::post('/blogs', [\App\Http\Controllers\Seo\BlogController::class, 'store'])->name('blogs.store');
        Route::get('/blogs/{blog}/edit', [\App\Http\Controllers\Seo\BlogController::class, 'edit'])->name('blogs.edit');
        Route::patch('/blogs/{blog}', [\App\Http\Controllers\Seo\BlogController::class, 'update'])->name('blogs.update');
        Route::patch('/blogs/{blog}/toggle', [\App\Http\Controllers\Seo\BlogController::class, 'togglePublish'])->name('blogs.toggle');
        Route::delete('/blogs/{blog}', [\App\Http\Controllers\Seo\BlogController::class, 'destroy'])->name('blogs.destroy');
    });
// ============ NOTIFICATIONS ============
Route::middleware('auth')->group(function () {
    Route::get('/notifications', function () {
        $notifications = auth()->user()->notifications()->latest()->paginate(30);
        return view('notifications.index', compact('notifications'));
    })->name('notifications.index');


   Route::get('/account', [\App\Http\Controllers\ProfileController::class, 'edit'])->name('account.edit');
    Route::patch('/account', [\App\Http\Controllers\ProfileController::class, 'update'])->name('account.update');
    Route::patch('/account/password', [\App\Http\Controllers\ProfileController::class, 'updatePassword'])->name('account.password');
    Route::post('/account/photo', [\App\Http\Controllers\ProfileController::class, 'updatePhoto'])->name('account.photo');

    // Recent notifications (for header dropdown)
    Route::get('/notifications/recent', function () {
        $notifications = auth()->user()->notifications()->latest()->take(10)->get()->map(function ($n) {
            return [
                'id' => $n->id,
                'type' => $n->data['type'] ?? null,
                'title' => $n->data['title'] ?? ucfirst(str_replace('_', ' ', $n->data['type'] ?? 'Notification')),
                'message' => $n->data['message'] ?? '',
                'icon' => $n->data['icon'] ?? '🔔',
                'url' => $n->data['url'] ?? route('notifications.index'),
                'read_at' => $n->read_at,
                'created_at' => $n->created_at->diffForHumans(),
            ];
        });

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => auth()->user()->unreadNotifications()->count(),
        ]);
    })->name('notifications.recent');

    // Unread count (for badge)
    Route::get('/notifications/unread-count', function () {
        return response()->json(['count' => auth()->user()->unreadNotifications()->count()]);
    })->name('notifications.unreadCount');

    // Mark as read
    Route::post('/notifications/{id}/read', function ($id) {
        $notification = auth()->user()->notifications()->findOrFail($id);
        $notification->markAsRead();
        return response()->json(['ok' => true]);
    })->name('notifications.read');

    // Mark all as read
    Route::post('/notifications/read-all', function () {
        auth()->user()->unreadNotifications->markAsRead();
        return response()->json(['ok' => true]);
    })->name('notifications.readAll');

    // Delete notification
    Route::delete('/notifications/{id}', function ($id) {
        auth()->user()->notifications()->findOrFail($id)->delete();
        return response()->json(['ok' => true]);
    })->name('notifications.destroy');
});



// ============ OTP AUTHENTICATION ============
Route::middleware('guest')->group(function () {
    // OTP Login
    Route::get('/otp-login', [\App\Http\Controllers\OtpController::class, 'showLoginForm'])->name('otp.login');
    Route::post('/otp/send', [\App\Http\Controllers\OtpController::class, 'sendOtp'])->name('otp.send');
    
    // OTP Verify
    Route::get('/otp/verify', [\App\Http\Controllers\OtpController::class, 'showVerifyForm'])->name('otp.verify.show');
    Route::post('/otp/verify', [\App\Http\Controllers\OtpController::class, 'verifyOtp'])->name('otp.verify');
    Route::post('/otp/resend', [\App\Http\Controllers\OtpController::class, 'resendOtp'])->name('otp.resend');
    
    // Register complete (after OTP verified)
    Route::get('/register/complete', [\App\Http\Controllers\OtpController::class, 'showRegisterComplete'])->name('register.complete');
    Route::post('/register/complete', [\App\Http\Controllers\OtpController::class, 'completeRegister'])->name('register.complete.submit');
});




// ===== REVIEWS =====
Route::post('/property/{property}/review', [\App\Http\Controllers\ReviewController::class, 'store'])->name('reviews.store');

// Owner reviews
Route::middleware(['auth', 'role:owner,admin'])->prefix('owner')->name('owner.')->group(function () {
    Route::get('/reviews', [\App\Http\Controllers\ReviewController::class, 'ownerIndex'])->name('reviews.index');
    Route::post('/reviews/{review}/respond', [\App\Http\Controllers\ReviewController::class, 'ownerRespond'])->name('reviews.respond');
});

// Admin reviews moderation
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/reviews', [\App\Http\Controllers\ReviewController::class, 'adminIndex'])->name('reviews.index');
    Route::post('/reviews/{review}/approve', [\App\Http\Controllers\ReviewController::class, 'adminApprove'])->name('reviews.approve');
    Route::post('/reviews/{review}/reject', [\App\Http\Controllers\ReviewController::class, 'adminReject'])->name('reviews.reject');
    Route::post('/reviews/{review}/spam', [\App\Http\Controllers\ReviewController::class, 'adminSpam'])->name('reviews.spam');
    Route::delete('/reviews/{review}', [\App\Http\Controllers\ReviewController::class, 'adminDelete'])->name('reviews.delete');
});


Route::post('/reviews', [ReviewWebController::class, 'store'])->name('reviews.store');
Route::post('/reviews/{review}/helpful', [ReviewWebController::class, 'helpful'])->name('reviews.helpful');

Route::middleware(['auth', 'role:owner,admin'])->prefix('owner')->name('owner.')->group(function () {
    Route::get('/reviews', [OwnerReviewController::class, 'index'])->name('reviews.index');
    Route::post('/reviews/{review}/respond', [OwnerReviewController::class, 'respond'])->name('reviews.respond');
});

// ⚠️ Was `auth` only (no role check) — any logged-in user, including a
// telecaller, could hit review moderation actions. Locked to admin.
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/reviews', [AdminReviewController::class, 'index'])->name('reviews.index');
    Route::post('/reviews/{review}/status', [AdminReviewController::class, 'updateStatus'])->name('reviews.status');
    Route::post('/reviews/bulk', [AdminReviewController::class, 'bulk'])->name('reviews.bulk');
});



Route::get('/pizi-clear-2026', function () {
    \Illuminate\Support\Facades\Artisan::call('clear-compiled');
    \Illuminate\Support\Facades\Artisan::call('optimize:clear');
    \Illuminate\Support\Facades\Artisan::call('view:clear');
    \Illuminate\Support\Facades\Artisan::call('route:clear');
    \Illuminate\Support\Facades\Artisan::call('config:clear');

    $opcacheStatus = 'Not available';
    if (function_exists('opcache_reset')) {
        opcache_reset();
        $opcacheStatus = 'Cleared ✅';
    }

    $exists = \Illuminate\Support\Facades\Route::has('reviews.store') ? 'YES ✅' : 'NO ❌';
    return 'Cache cleared. OPcache: ' . $opcacheStatus . '. reviews.store route ready: ' . $exists;
});

Route::get('/pizi-send-rent-reminders-2026', function () {
    \Illuminate\Support\Facades\Artisan::call('rent:send-due-reminders', [
        '--days' => request('days', 3),
    ]);
    return \Illuminate\Support\Facades\Artisan::output();
});


Route::get('/pizi-generate-monthly-bills-2026', function () {
    \Illuminate\Support\Facades\Artisan::call('rent:generate-monthly', [
        '--due-day' => request('due_day', 5),
    ]);
    return \Illuminate\Support\Facades\Artisan::output();
});

Route::get('/pizi-send-kyc-reminders-2026', function () {
    \Illuminate\Support\Facades\Artisan::call('kyc:send-pending-reminders', [
        '--days' => request('days', 3),
    ]);
    return \Illuminate\Support\Facades\Artisan::output();
});

Route::get('/pizi-send-pg-reminders-2026', function () {
    \Illuminate\Support\Facades\Artisan::call('owner:send-pg-reminder', [
        '--days' => request('days', 1),
    ]);
    return \Illuminate\Support\Facades\Artisan::output();
});


// ===== TENANT PORTAL =====
// ===== TENANT PORTAL =====



Route::middleware(['auth', 'role:tenant'])->prefix('tenant')->name('tenant.')->group(function () {
    
    // ONBOARDING (always accessible)
    Route::get('/onboarding', [\App\Http\Controllers\TenantPortalController::class, 'onboarding'])->name('onboarding');
    Route::post('/onboarding/update', [\App\Http\Controllers\TenantPortalController::class, 'onboardingUpdate'])->name('onboarding.update');
       
       
       
       Route::get('/kyc/aadhaar',              [\App\Http\Controllers\TenantPortalController::class, 'aadhaarKycPage'])->name('kyc.aadhaar');
    Route::post('/kyc/aadhaar/send-otp',    [\App\Http\Controllers\TenantPortalController::class, 'aadhaarSendOtp'])->name('kyc.aadhaar.sendotp');
    Route::post('/kyc/aadhaar/verify-otp',  [\App\Http\Controllers\TenantPortalController::class, 'aadhaarVerifyOtp'])->name('kyc.aadhaar.verifyotp');
    
    Route::get('/agreement/sign',   [\App\Http\Controllers\TenantPortalController::class, 'agreementSignPage'])->name('agreement.sign');
    Route::post('/agreement/sign', [\App\Http\Controllers\TenantPortalController::class, 'agreementSignSubmit'])->name('agreement.sign.submit');
    Route::get('/agreement/callback', [\App\Http\Controllers\TenantPortalController::class, 'agreementCallback'])->withoutMiddleware(['auth', 'verified'])->name('agreement.callback');
    Route::post('/kyc/aadhaar/skip-testing', [\App\Http\Controllers\TenantPortalController::class, 'aadhaarSkipForTesting'])->name('kyc.aadhaar.skiptesting');
// Route::post('/agreement/sign',  [\App\Http\Controllers\TenantPortalController::class, 'agreementSignSubmit'])->name('agreement.sign.submit');
// Route::get('/agreement/callback', [\App\Http\Controllers\TenantPortalController::class, 'agreementCallback'])->name('agreement.callback');
    
    // Profile (always accessible)
    Route::get('/profile', [\App\Http\Controllers\TenantPortalController::class, 'profile'])->name('profile');
    Route::post('/profile', [\App\Http\Controllers\TenantPortalController::class, 'profileUpdate'])->name('profile.update');
  Route::post('/profile/setup-password', [\App\Http\Controllers\TenantPortalController::class, 'setupPasswordLogin'])->name('profile.setup-password');
    
    // KYC upload (always accessible)
   // ---- KYC ----
    Route::get('/kyc',           [\App\Http\Controllers\TenantPortalController::class, 'kycPage'])->name('kyc');
    Route::post('/kyc/upload',   [\App\Http\Controllers\TenantPortalController::class, 'kycUpload'])->name('kyc.upload');
    Route::delete('/kyc/{type}', [\App\Http\Controllers\TenantPortalController::class, 'kycDelete'])->name('kyc.delete');
    Route::post('/kyc/submit',   [\App\Http\Controllers\TenantPortalController::class, 'kycSubmit'])->name('kyc.submit');
    
    // Token payment (always accessible)
    Route::get('/token/pay/{property?}', [\App\Http\Controllers\TenantPortalController::class, 'tokenPay'])->name('token.pay');
    Route::post('/token/verify', [\App\Http\Controllers\TenantPortalController::class, 'tokenVerify'])->name('token.verify');
    
    // PROTECTED (only after journey complete)
    Route::middleware(['tenant.journey'])->group(function () {
        Route::get('/', [\App\Http\Controllers\TenantPortalController::class, 'dashboard'])->name('dashboard');
        Route::get('/dashboard', [\App\Http\Controllers\TenantPortalController::class, 'dashboard'])->name('dashboard');
        Route::get('/room', [\App\Http\Controllers\TenantPortalController::class, 'myRoom'])->name('room');
        Route::get('/rent', [\App\Http\Controllers\TenantPortalController::class, 'rentHistory'])->name('rent.history');
        Route::get('/rent/{bill}/pay', [\App\Http\Controllers\TenantPortalController::class, 'payRent'])->name('pay-rent');
        Route::post('/rent/{bill}/razorpay-order',  [\App\Http\Controllers\TenantPortalController::class, 'createRazorpayOrder'])->name('rent.razorpay.order');
        Route::post('/rent/{bill}/razorpay-verify', [\App\Http\Controllers\TenantPortalController::class, 'verifyRentPayment'])->name('rent.razorpay.verify');
        Route::get('/complaints', [\App\Http\Controllers\TenantPortalController::class, 'complaints'])->name('complaints.index');
        Route::get('/complaints/create', [\App\Http\Controllers\TenantPortalController::class, 'complaintCreate'])->name('complaints.create');
        Route::post('/complaints', [\App\Http\Controllers\TenantPortalController::class, 'complaintStore'])->name('complaints.store');
        Route::get('/agreement', [\App\Http\Controllers\TenantPortalController::class, 'agreement'])->name('agreement');
        Route::get('/agreement/{id}/preview',  [\App\Http\Controllers\TenantPortalController::class, 'agreementPreview'])->name('agreement.preview');
        Route::get('/agreement/{id}/download', [\App\Http\Controllers\TenantPortalController::class, 'agreementDownload'])->name('agreement.download');
        Route::get('/notice', [\App\Http\Controllers\TenantPortalController::class, 'noticeForm'])->name('notice');
        Route::post('/notice', [\App\Http\Controllers\TenantPortalController::class, 'noticeSubmit'])->name('notice.submit');
    });
});


Route::get('/debug-journey/{userId}', function ($userId) {
    $user = \App\Models\User::find($userId);
    if (!$user) return 'User not found';

    $byUserId = \DB::table('leads')->where('user_id', $user->id)->pluck('id', 'phone');

    $normalizedPhone = preg_replace('/[^0-9]/', '', $user->phone ?? '');
    $last10 = strlen($normalizedPhone) >= 10 ? substr($normalizedPhone, -10) : $normalizedPhone;

    $byPhone = \DB::table('leads')
        ->whereRaw('RIGHT(REPLACE(REPLACE(phone, "+91", ""), " ", ""), 10) = ?', [$last10])
        ->get(['id', 'name', 'phone', 'user_id', 'call_status', 'called_at']);

    return response()->json([
        'tenant_user_id'      => $user->id,
        'tenant_name'         => $user->name,
        'tenant_phone_raw'    => $user->phone,
        'tenant_phone_last10' => $last10,
        'leads_by_user_id'    => $byUserId,
        'leads_by_phone'      => $byPhone,
    ], 200, [], JSON_PRETTY_PRINT);
});




// ---- TENANT TOKEN (auth) ----
Route::middleware(['auth'])->group(function () {
    Route::get('/tenant/token/pay',    [App\Http\Controllers\TenantPortalController::class, 'tokenPay'])->name('tenant.token.pay');
    Route::post('/tenant/token/verify',[App\Http\Controllers\TenantPortalController::class, 'tokenVerify'])->name('tenant.token.verify');
    Route::post('/tenant/token/cash',  [App\Http\Controllers\TenantPortalController::class, 'tokenCash'])->name('tenant.token.cash');

    // Resolves short Google Maps links (share.google, maps.app.goo.gl) into
    // real lat/lng — used by the property location map picker (owner + admin).
    Route::post('/resolve-map-link', [App\Http\Controllers\MapLinkController::class, 'resolve'])->name('map.resolve');
});



// ---- CHAT ROUTES ----
// The AI Assistant is a floating widget included on every page (see
// layouts/app.blade.php) — '/chat' itself just sends visitors home.
Route::get('/chat', [App\Http\Controllers\ChatController::class, 'page'])->name('chat.page');
Route::get('/chat/history/{id}', [ChatController::class, 'history'])->name('chat.history');


// University-based PG filtering
Route::get('/universities', [App\Http\Controllers\UniversityPropertyController::class, 'index'])->name('universities.index');
Route::get('/universities/{id}', [App\Http\Controllers\UniversityPropertyController::class, 'show'])->name('universities.show');
Route::post('/api/properties-by-universities', [App\Http\Controllers\UniversityPropertyController::class, 'filterByUniversities'])->name('api.properties.by-universities');

// Note: '/admin/chat-analytics' with proper role:admin protection is
// already registered above inside the admin route group.


// ---- CHAT API ROUTES ----
Route::prefix('chat')->group(function () {
    Route::post('/start', [ChatController::class, 'startChat']);
    Route::post('/send', [ChatController::class, 'sendMessage']);
    Route::get('/history', [ChatController::class, 'getHistory']);
    Route::post('/language', [ChatController::class, 'changeLanguage']);
});

Route::get('/register-packages', function () {
    $packages = DB::table('credit_packages')->where('is_active', 1)->get();
    return view('auth.register-with-packages', ['packages' => $packages]);
});

Route::get('/privacy-policy', function () {
    return view('legal.privacy-policy');  // ← CHANGE THIS LINE
})->name('privacy-policy');



Route::middleware('auth')->post('/owner/purchase-package', function(Request $request) {
    try {
        $package = DB::table('credit_packages')->find($request->package_id);
        
        if (!$package) {
            return response()->json(['success' => false, 'message' => 'Package not found'], 404);
        }

        // Create Razorpay order
        $razorpay = new \Razorpay\Api\Api(
            env('RAZORPAY_KEY_ID'),
            env('RAZORPAY_KEY_SECRET')
        );

        $order = $razorpay->order->create([
            'amount' => $package->price_inr * 100, // In paise
            'currency' => 'INR',
            'receipt' => 'order_' . time(),
            'notes' => [
                'package_id' => $package->id,
                'user_id' => auth()->id()
            ]
        ]);

        return response()->json([
            'success' => true,
            'order_id' => $order['id'],
            'amount' => $order['amount'],
            'currency' => $order['currency']
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage()
        ], 500);
    }
});

Route::post('/owner/verify-payment', function(Request $request) {
    try {
        $razorpay = new \Razorpay\Api\Api(
            env('RAZORPAY_KEY_ID'),
            env('RAZORPAY_KEY_SECRET')
        );

        $attributes = [
            'razorpay_order_id' => $request->razorpay_order_id,
            'razorpay_payment_id' => $request->razorpay_payment_id,
            'razorpay_signature' => $request->razorpay_signature
        ];

        $razorpay->utility->verifyPaymentSignature($attributes);

      // Payment verified - add credits
        $user = auth()->user();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Session expired, please login again'], 401);
        }

        $package = DB::table('credit_packages')->find($request->package_id ?? null);
        $creditsToAdd = $package ? ((int) $package->credits + (int) $package->bonus_credits) : 5500;

        \Log::info('VERIFY PAYMENT DEBUG', [
            'user_id' => $user->id,
            'package_id' => $request->package_id,
            'credits' => $creditsToAdd,
        ]);

        DB::table('payments')->insert([
            'user_id' => $user->id,
            'credit_package_id' => $package->id ?? null,
            'amount_inr' => $package->price_inr ?? 0,
            'credits_to_add' => $creditsToAdd,
            'razorpay_order_id' => $request->razorpay_order_id,
            'razorpay_payment_id' => $request->razorpay_payment_id,
            'razorpay_signature' => $request->razorpay_signature,
            'status' => 'paid',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

       app(\App\Services\WalletService::class)->credit(
            $user,
            (int) $creditsToAdd,
            'purchase',
            $request->razorpay_payment_id,
            'Package purchased: ' . ($package->name ?? 'Unknown')
        );

        // 🧾 Auto-generate GST invoice + send it to the owner's WhatsApp.
        try {
            $invoiceService = app(\App\Services\InvoiceService::class);
            $invoice = $invoiceService->generate([
                'owner' => $user,
                'credit_package_id' => $package->id ?? null,
                'title' => $package->name ?? 'Credit Package',
                'total_amount' => $package->price_inr ?? 0,
                'type' => 'auto',
            ]);
            $invoiceService->sendViaWhatsApp($invoice);
        } catch (\Exception $e) {
            \Log::warning('Invoice generation/send failed (verify-payment route): ' . $e->getMessage());
        }

        return response()->json(['success' => true, 'message' => 'Payment successful']);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage()
        ], 500);
    }
});
// Signed, un-authenticated invoice download — this is the URL sent to WhatsApp
// so Meta's servers can fetch the PDF to attach it (valid 7 days, see InvoiceService).
Route::get('/invoices/{invoice}/signed-download', [\App\Http\Controllers\InvoiceDownloadController::class, 'signed'])
    ->middleware('signed')
    ->name('invoices.download.signed');
