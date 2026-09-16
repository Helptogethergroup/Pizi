<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\Api\LeadController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\OwnerController;
use App\Http\Controllers\Api\TelecallerController;
use App\Http\Controllers\Api\FieldController;
use App\Http\Controllers\Api\SeoController;
use App\Http\Controllers\Api\PublicController;
use App\Http\Controllers\Api\SharedController;

/*
|--------------------------------------------------------------------------
| API Routes — Pizi Complete REST API
|--------------------------------------------------------------------------
| Mounted under /api/* by Laravel
*/

// Health check
Route::get('/', fn() => response()->json([
    'success' => true,
    'app' => 'Pizi API',
    'version' => '1.0.0',
    'time' => now()->toIso8601String(),
]));

// ============ AUTH ============
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/login/password', [AuthController::class, 'loginWithPassword']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/otp/send', [AuthController::class, 'otpSend']);
    Route::post('/otp/verify', [AuthController::class, 'otpVerify']);
    Route::post('/otp/resend', [AuthController::class, 'otpResend']);
    Route::post('/register/complete', [AuthController::class, 'registerComplete']);

    Route::middleware('pizi.auth')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

// ============ PUBLIC (no auth) ============
Route::prefix('public')->group(function () {
    Route::get('/home', [PublicController::class, 'home']);
    Route::get('/search', [PublicController::class, 'search']);
    Route::get('/properties/{slug}', [PublicController::class, 'propertyShow']);
    Route::get('/city/{slug}', [PublicController::class, 'cityShow']);
    Route::get('/locality/{slug}', [PublicController::class, 'localityShow']);
    Route::get('/landmark/{slug}', [PublicController::class, 'landmarkShow']);
    Route::get('/landmarks', [PublicController::class, 'landmarksIndex']);
    Route::get('/blogs', [PublicController::class, 'blogIndex']);
    Route::get('/blogs/{slug}', [PublicController::class, 'blogShow']);
    Route::get('/sitemap', [PublicController::class, 'sitemap']);
    Route::post('/contact', [PublicController::class, 'contact']);
    Route::post('/leads', [PublicController::class, 'leadStore']);
});

// ============ MASTER DATA (public) ============
Route::get('/cities', [PublicController::class, 'cities']);
Route::get('/localities', [PublicController::class, 'localities']);
Route::get('/amenities', [PublicController::class, 'amenities']);
Route::get('/universities', [PublicController::class, 'universities']);
Route::get('/properties', [PublicController::class, 'allProperties']);

// ============ AUTHED ROUTES ============
Route::middleware('pizi.auth')->group(function () {

    // SHARED
    Route::get('/user/notifications', [SharedController::class, 'notifications']);
    Route::post('/user/notifications/{id}/read', [SharedController::class, 'markRead']);
    Route::post('/leads/manual', [SharedController::class, 'leadManual']);

    // ============ ADMIN ============
    Route::prefix('admin')->middleware('role:admin')->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard']);
        Route::get('/analytics', [AdminController::class, 'analytics']);
        Route::get('/pg-overview', [AdminController::class, 'pgOverview']);
        Route::get('/field-tracker', [AdminController::class, 'fieldTracker']);

        // Properties
        Route::get('/properties', [AdminController::class, 'properties']);
        Route::get('/properties/{id}', [AdminController::class, 'propertyShow']);
        Route::post('/properties', [AdminController::class, 'propertyStore']);
        Route::post('/properties/{id}', [AdminController::class, 'propertyUpdate']);
        Route::delete('/properties/{id}', [AdminController::class, 'propertyDelete']);
        Route::patch('/properties/{id}/verify', [AdminController::class, 'propertyVerify']);
        Route::patch('/properties/{id}/feature', [AdminController::class, 'propertyFeature']);
        Route::patch('/properties/{id}/pause', [AdminController::class, 'propertyPause']);
        Route::patch('/properties/{id}/assign', [AdminController::class, 'propertyAssign']);

        // Leads
        Route::get('/leads', [AdminController::class, 'leads']);
        Route::get('/leads/{id}', [AdminController::class, 'leadShow']);
        Route::patch('/leads/{id}/assign', [AdminController::class, 'leadAssign']);

        // Users
        Route::get('/users', [AdminController::class, 'users']);
        Route::post('/users', [AdminController::class, 'userStore']);
        Route::patch('/users/{id}/toggle', [AdminController::class, 'userToggle']);
        Route::patch('/users/{id}/role', [AdminController::class, 'userChangeRole']);

        // Wallets
        Route::get('/wallets', [AdminController::class, 'wallets']);
        Route::post('/wallets/{ownerId}/adjust', [AdminController::class, 'walletAdjust']);

        // Pricing
        Route::get('/pricing', [AdminController::class, 'pricing']);
        Route::post('/pricing/{id}', [AdminController::class, 'pricingUpdate']);

        // Packages
        Route::get('/packages', [AdminController::class, 'packages']);
        Route::post('/packages', [AdminController::class, 'packageStore']);
        Route::post('/packages/{id}', [AdminController::class, 'packageUpdate']);
        Route::delete('/packages/{id}', [AdminController::class, 'packageDelete']);
        Route::patch('/packages/{id}/toggle', [AdminController::class, 'packageToggle']);

        // Blogs
        Route::get('/blogs', [AdminController::class, 'blogs']);
        Route::get('/blogs/{id}', [AdminController::class, 'blogShow']);
        Route::post('/blogs', [AdminController::class, 'blogStore']);
        Route::post('/blogs/{id}', [AdminController::class, 'blogUpdate']);
        Route::delete('/blogs/{id}', [AdminController::class, 'blogDelete']);
        Route::patch('/blogs/{id}/toggle', [AdminController::class, 'blogToggle']);

        // Rooms, Tenants, Agreements, Rent, Complaints
        Route::get('/rooms', [AdminController::class, 'rooms']);
        Route::get('/tenants', [AdminController::class, 'tenants']);
        Route::get('/tenants/{id}', [AdminController::class, 'tenantShow']);
        Route::get('/agreements', [AdminController::class, 'agreements']);
        Route::get('/agreements/{id}', [AdminController::class, 'agreementShow']);
        Route::get('/rent', [AdminController::class, 'rent']);
        Route::get('/rent/{id}', [AdminController::class, 'rentShow']);
        Route::get('/complaints', [AdminController::class, 'complaints']);
        Route::get('/complaints/{id}', [AdminController::class, 'complaintShow']);
    });

    // ============ OWNER ============

// ============ OWNER ============
// Ye block routes/api.php mein existing owner block ko REPLACE karo
// Dhundho: Route::prefix('owner')->middleware('role:owner,admin')->group(function () {
// Aur poora block (closing }); tak) replace karo

Route::prefix('owner')->middleware('role:owner,admin')->group(function () {
    Route::get('/dashboard',  [OwnerController::class, 'dashboard']);
    Route::get('/analytics',  [OwnerController::class, 'analytics']);
    Route::get('/profile',    [OwnerController::class, 'profile']);
    Route::post('/profile',   [OwnerController::class, 'profileUpdate']);

    // Properties
    Route::get('/properties',                        [OwnerController::class, 'properties']);
    Route::get('/properties/{id}',                   [OwnerController::class, 'propertyShow']);
    Route::post('/properties',                       [OwnerController::class, 'propertyStore']);
    Route::post('/properties/{id}',                  [OwnerController::class, 'propertyUpdate']);
    Route::delete('/properties/{id}',                [OwnerController::class, 'propertyDelete']);
    Route::patch('/properties/{id}/pause',           [OwnerController::class, 'propertyPause']);
    Route::post('/properties/{id}/images',           [OwnerController::class, 'uploadPropertyImage']);
    Route::delete('/properties/images/{imageId}',    [OwnerController::class, 'deletePropertyImage']);

    // Leads
    Route::get('/leads',              [OwnerController::class, 'leads']);
    Route::post('/leads/{id}/unlock', [OwnerController::class, 'leadUnlock']);

    // Wallet / Credits
    Route::get('/wallet',          [OwnerController::class, 'wallet']);
    Route::get('/credits',         [OwnerController::class, 'credits']);
    Route::post('/credits/order',  [OwnerController::class, 'createOrder']);
    Route::post('/credits/verify', [OwnerController::class, 'verifyPayment']);

    // Rent / Bills
    Route::get('/rent',                    [OwnerController::class, 'rent']);
    Route::get('/rent/{id}',               [OwnerController::class, 'rentShow']);
    Route::post('/rent',                   [OwnerController::class, 'rentStore']);
    Route::post('/rent/{id}/payment',      [OwnerController::class, 'rentPayment']);
    Route::post('/rent/generate-all',      [OwnerController::class, 'rentGenerateAll']);

    // Tenants
    Route::get('/tenants',          [OwnerController::class, 'tenants']);
    Route::get('/tenants/{id}',     [OwnerController::class, 'tenantShow']);
    Route::post('/tenants',         [OwnerController::class, 'tenantStore']);
    Route::post('/tenants/{id}',    [OwnerController::class, 'tenantUpdate']);
    Route::delete('/tenants/{id}',  [OwnerController::class, 'tenantDelete']);

    // Rooms
    Route::get('/rooms',          [OwnerController::class, 'rooms']);
    Route::get('/rooms/{id}',     [OwnerController::class, 'roomShow']);
    Route::post('/rooms',         [OwnerController::class, 'roomStore']);
    Route::post('/rooms/{id}',    [OwnerController::class, 'roomUpdate']);
    Route::delete('/rooms/{id}',  [OwnerController::class, 'roomDelete']);

    // Beds
    Route::post('/rooms/{roomId}/beds', [OwnerController::class, 'bedStore']);
    Route::post('/beds/{id}',           [OwnerController::class, 'bedUpdate']);
    Route::delete('/beds/{id}',         [OwnerController::class, 'bedDelete']);

    // Complaints
    Route::get('/complaints',                    [OwnerController::class, 'complaints']);
    Route::get('/complaints/{id}',               [OwnerController::class, 'complaintShow']);
    Route::post('/complaints/{id}/status',       [OwnerController::class, 'complaintUpdateStatus']);

    // Agreements
    Route::get('/agreements',        [OwnerController::class, 'agreements']);
    Route::get('/agreements/{id}',   [OwnerController::class, 'agreementShow']);
    Route::post('/agreements',       [OwnerController::class, 'agreementStore']);
    Route::post('/agreements/{id}',  [OwnerController::class, 'agreementUpdate']);

    // Token Payments
    Route::get('/token-payments',  [OwnerController::class, 'tokenPayments']);

    // Reviews
    Route::get('/reviews',               [OwnerController::class, 'reviews']);
    Route::post('/reviews/{id}/reply',   [OwnerController::class, 'reviewReply']);

    // Blogs
    Route::get('/blogs',              [OwnerController::class, 'blogs']);
    Route::get('/blogs/{id}',         [OwnerController::class, 'blogShow']);
    Route::post('/blogs',             [OwnerController::class, 'blogStore']);
    Route::post('/blogs/{id}',        [OwnerController::class, 'blogUpdate']);
    Route::delete('/blogs/{id}',      [OwnerController::class, 'blogDelete']);
    Route::patch('/blogs/{id}/toggle',[OwnerController::class, 'blogToggle']);
});

    // ============ TELECALLER ============
    Route::prefix('telecaller')->middleware('role:telecaller,admin')->group(function () {
        Route::get('/dashboard', [TelecallerController::class, 'dashboard']);
        Route::get('/leads', [TelecallerController::class, 'leads']);
        Route::get('/leads/{id}', [TelecallerController::class, 'leadShow']);
        Route::patch('/leads/{id}', [TelecallerController::class, 'leadUpdate']);
        Route::get('/leads/{id}/matching-properties', [TelecallerController::class, 'matchingProperties']);
        Route::post('/leads/{id}/schedule-visit', [TelecallerController::class, 'scheduleVisit']);
    });

    // ============ FIELD EXECUTIVE ============
    Route::prefix('field')->middleware('role:field_executive,admin')->group(function () {
        Route::get('/dashboard', [FieldController::class, 'dashboard']);
        Route::get('/visits', [FieldController::class, 'visits']);
        Route::get('/visits/{id}', [FieldController::class, 'visitShow']);
        Route::post('/visits/{id}/start', [FieldController::class, 'visitStart']);
        Route::post('/visits/{id}/complete', [FieldController::class, 'visitComplete']);
        Route::post('/visits/{id}/verify', [FieldController::class, 'visitVerify']);
        Route::post('/visits/{id}/media', [FieldController::class, 'visitMedia']);
    });

    // ============ SEO MANAGER ============
    Route::prefix('seo')->middleware('role:seo_manager,admin')->group(function () {
        Route::get('/dashboard', [SeoController::class, 'dashboard']);
        Route::get('/settings', [SeoController::class, 'settings']);
        Route::get('/settings/{id}', [SeoController::class, 'settingShow']);
        Route::post('/settings', [SeoController::class, 'settingStore']);
        Route::post('/settings/{id}', [SeoController::class, 'settingUpdate']);
        Route::delete('/settings/{id}', [SeoController::class, 'settingDelete']);

        Route::get('/blogs', [SeoController::class, 'blogs']);
        Route::get('/blogs/{id}', [SeoController::class, 'blogShow']);
        Route::post('/blogs', [SeoController::class, 'blogStore']);
        Route::post('/blogs/{id}', [SeoController::class, 'blogUpdate']);
        Route::delete('/blogs/{id}', [SeoController::class, 'blogDelete']);
        Route::patch('/blogs/{id}/toggle', [SeoController::class, 'blogToggle']);
    });
});

Route::prefix('chat')->group(function () {
    Route::post('/start', [ChatController::class, 'startChat']);
    Route::post('/send', function (Illuminate\Http\Request $request) {
    $request->validate([
        'message' => 'required|string|max:5000',
        'language' => 'nullable|in:en,hi',
        'session_id' => 'nullable|string|max:64',
        'history' => 'nullable|array',
    ]);

    $language = $request->input('language', 'en');
    $message = $request->input('message');

    // Track the conversation so admin analytics has real data and follow-up
    // questions can be answered with context.
    $session = null;
    if ($sessionId = $request->input('session_id')) {
        $session = \App\Models\ChatSession::firstOrCreate(
            ['session_id' => $sessionId],
            ['language' => $language]
        );
        $session->update(['language' => $language, 'last_activity_at' => now()]);
    }

    $service = new \App\Services\ChatAiService();
    $response = $service->processMessage($message, $language, $request->input('history', []));

    if ($session) {
        \App\Models\ChatMessage::create(['conversation_id' => $session->id, 'sender' => 'user', 'message' => $message]);
        \App\Models\ChatMessage::create(['conversation_id' => $session->id, 'sender' => 'bot', 'message' => $response]);
    }

    return response()->json([
        'success' => true,
        'message' => $response,
        'timestamp' => now()
    ]);
});
    Route::get('/history', [ChatController::class, 'getHistory']);
    Route::post('/language', [ChatController::class, 'changeLanguage']);
});



// Admin/telecaller lead management (edit modal, remarks) — used from the
// logged-in web dashboard only. SECURITY: previously had no auth check at
// all ("Simple routes (no auth required for now)"), meaning anyone on the
// internet could dump/edit/delete every lead's name, phone and email
// without logging in. Now requires a real admin/telecaller web session,
// same as the pages that call these.
Route::middleware(['web', 'auth', 'role:admin,telecaller'])->group(function () {
    Route::get('/leads', [LeadController::class, 'all']);
    Route::get('/leads/{id}/edit', [LeadController::class, 'edit']);
    Route::post('/leads/{id}/release-lock', [LeadController::class, 'releaseLock']);
    Route::put('/leads/{id}', [LeadController::class, 'update']);
    Route::delete('/leads/{id}', [LeadController::class, 'destroy']);
    Route::post('/leads/{id}/remark', [LeadController::class, 'addRemark']);
    Route::get('/leads/{id}/remarks', [LeadController::class, 'getRemarks']);
});

// Public — the property enquiry widget / lead-capture form posts a new
// lead without being logged in. Left open by design (no PII is exposed,
// only accepts new data).
Route::post('/leads', [LeadController::class, 'store']);

// ── Ad-platform lead webhooks (Meta + Google Ads) ────────────────────
// Public, unauthenticated by design — external platforms call these.
// Security is via the verify token (Meta) / shared key (Google), not
// Laravel auth. Sits under /api so it skips CSRF (api group has none).
Route::get('/webhooks/meta-leads', [\App\Http\Controllers\MetaLeadWebhookController::class, 'verify']);
Route::post('/webhooks/meta-leads', [\App\Http\Controllers\MetaLeadWebhookController::class, 'handle']);
Route::post('/webhooks/google-ads-leads', [\App\Http\Controllers\GoogleAdsLeadWebhookController::class, 'handle']);

