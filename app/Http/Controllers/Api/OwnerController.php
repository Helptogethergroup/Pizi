<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\AdminController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class OwnerController extends Controller
{
    private function ownerId(Request $request): int
    {
        return $request->user()->id;
    }

    // ─── Image URL helper ────────────────────────────────────────────────────
    private function resolveUrl(?string $path): ?string
    {
        if (!$path) return null;
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }
        return url('storage/' . ltrim($path, '/'));
    }

    // ─── Map images to full URLs ─────────────────────────────────────────────
    private function mapImages($images): array
    {
        return collect($images)->map(function ($img) {
            $img = (array) $img;
            $img['url'] = $this->resolveUrl($img['image_path'] ?? $img['path'] ?? null);
            return $img;
        })->toArray();
    }

    // ─── Map properties cover_image to full URL ──────────────────────────────
    private function mapPropertyImages($properties)
    {
        return collect($properties)->map(function ($p) {
            $p = (array) $p;
            $p['cover_image_url'] = $this->resolveUrl($p['cover_image'] ?? null);
            return (object) $p;
        });
    }

    // ═══════════════════════════════════════════════════════════════
    //  DASHBOARD
    // ═══════════════════════════════════════════════════════════════
    public function dashboard(Request $request)
    {
        $oid = $this->ownerId($request);

        $stats = [
            'total_properties'  => DB::table('properties')->where('owner_id', $oid)->whereNull('deleted_at')->count(),
            'active_properties' => DB::table('properties')->where('owner_id', $oid)->where('is_active', 1)->whereNull('deleted_at')->count(),
            'total_views'       => DB::table('properties')->where('owner_id', $oid)->whereNull('deleted_at')->sum('view_count') ?? 0,
            'total_leads'       => DB::table('leads')->whereNull('leads.deleted_at')->whereIn('property_id', function ($q) use ($oid) {
                $q->select('id')->from('properties')->where('owner_id', $oid);
            })->count(),
            'active_tenants'    => DB::table('tenants')->where('owner_id', $oid)->where('status', 'active')->count(),
            'pending_dues'      => (float) DB::table('rent_bills')->where('owner_id', $oid)->whereIn('status', ['pending', 'partial', 'overdue'])->sum('due_amount'),
            'open_complaints'   => DB::table('complaints')->where('owner_id', $oid)->whereIn('status', ['open', 'assigned', 'in_progress'])->count(),
            'total_rooms'       => DB::table('rooms')->where('owner_id', $oid)->whereNull('deleted_at')->count(),
            'vacant_beds'       => DB::table('beds')->where('owner_id', $oid)->whereNull('deleted_at')->where('status', 'vacant')->count(),
            'occupied_beds'     => DB::table('beds')->where('owner_id', $oid)->whereNull('deleted_at')->where('status', 'occupied')->count(),
            'month_collection'  => (float) DB::table('rent_payments')->where('owner_id', $oid)
                ->whereRaw("DATE_FORMAT(paid_at, '%Y-%m') = ?", [date('Y-m')])->sum('amount'),
        ];

        $properties = DB::table('properties')
            ->where('owner_id', $oid)
            ->whereNull('deleted_at')
            ->select('id', 'name', 'slug', 'cover_image', 'is_active', 'is_verified', 'view_count')
            ->limit(10)
            ->get();

        // Fix: add full URL for cover_image
        $properties = $this->mapPropertyImages($properties);

        $recentLeads = DB::table('leads')
            ->leftJoin('properties', 'leads.property_id', '=', 'properties.id')
            ->where('properties.owner_id', $oid)
            ->whereNull('leads.deleted_at')
            ->select('leads.*', 'properties.name as property_name')
            ->orderBy('leads.created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(fn ($lead) => $this->formatLeadForApp($lead));

        return $this->ok([
            'stats'        => $stats,
            'properties'   => $properties,
            'recent_leads' => $recentLeads,
        ]);
    }

    // ═══════════════════════════════════════════════════════════════
    //  ANALYTICS
    // ═══════════════════════════════════════════════════════════════
    public function analytics(Request $request)
    {
        $oid = $this->ownerId($request);
        $propertyIds = DB::table('properties')->where('owner_id', $oid)->pluck('id');

        $leadsByDay = DB::table('leads')
            ->whereIn('property_id', $propertyIds)
            ->whereNull('deleted_at')
            ->where('created_at', '>=', now()->subDays(30))
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $monthCollection = DB::table('rent_payments')
            ->where('owner_id', $oid)
            ->whereRaw("DATE_FORMAT(paid_at, '%Y-%m') = ?", [now()->format('Y-m')])
            ->sum('amount');

        // Same figures the web dashboard's Analytics page shows — kept in
        // sync here so the app matches it: total/closed leads, credit
        // spend over the last 6 months, lead-type breakdown, per-property
        // performance, and current wallet totals.
        $web = app(\App\Services\AnalyticsService::class)->ownerAnalytics($request->user());

        $propertyPerformance = collect($web['properties'])->map(fn ($p) => [
            'id'                 => $p->id,
            'name'               => $p->name,
            'leads_count'        => $p->leads_count,
            'closed_leads_count' => $p->closed_leads_count,
            'view_count'         => $p->view_count,
        ])->values();

        return $this->ok([
            'leads_by_day'         => $leadsByDay,
            'month_collection'     => (float) $monthCollection,
            'total_leads'          => $web['totals']['total_leads'],
            'closed_leads'         => $web['totals']['closed_leads'],
            'credits_spent'        => (int) $web['totals']['total_spent'],
            'current_balance'      => (int) $web['totals']['current_balance'],
            'credit_usage_6mo'     => $web['credit_usage'],
            'lead_types_breakdown' => $web['lead_types'],
            'property_performance' => $propertyPerformance,
        ]);
    }

    // ═══════════════════════════════════════════════════════════════
    //  PROFILE
    // ═══════════════════════════════════════════════════════════════
    public function profile(Request $request)
    {
        $user = $request->user();
        return $this->ok([
            'id'     => $user->id,
            'name'   => $user->name,
            'email'  => $user->email,
            'phone'  => $user->phone,
            'role'   => $user->role,
            'avatar' => $this->resolveUrl($user->avatar),
        ]);
    }

    public function profileUpdate(Request $request)
    {
        $user = $request->user();
        $data = $request->only(['name', 'email', 'phone']);

        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('avatars', 'public');
            $data['avatar'] = $path;
        }

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $data['updated_at'] = now();
        DB::table('users')->where('id', $user->id)->update($data);

        return $this->ok(['message' => 'Profile updated']);
    }

    // ═══════════════════════════════════════════════════════════════
    //  PROPERTIES — with fixed image URLs
    // ═══════════════════════════════════════════════════════════════
    public function properties(Request $request)
    {
        $items = DB::table('properties')
            ->leftJoin('cities', 'properties.city_id', '=', 'cities.id')
            ->leftJoin('localities', 'properties.locality_id', '=', 'localities.id')
            ->where('properties.owner_id', $this->ownerId($request))
            ->whereNull('properties.deleted_at')
            ->select('properties.*', 'cities.name as city_name', 'localities.name as locality_name')
            ->orderBy('properties.created_at', 'desc')
            ->get();

        // Fix: add full URL for cover_image
        $items = $this->mapPropertyImages($items);

        return $this->ok($items);
    }

    public function propertyShow(Request $request, $id)
    {
        $p = DB::table('properties')
            ->leftJoin('cities', 'properties.city_id', '=', 'cities.id')
            ->leftJoin('localities', 'properties.locality_id', '=', 'localities.id')
            ->leftJoin('universities', 'properties.nearby_university_id', '=', 'universities.id')
            ->where('properties.id', $id)
            ->where('properties.owner_id', $this->ownerId($request))
            ->select('properties.*', 'cities.name as city_name', 'localities.name as locality_name', 'universities.name as nearby_university_name')
            ->first();

        if (!$p) return $this->notFound();

        $p = (array) $p;
        $p['cover_image_url'] = $this->resolveUrl($p['cover_image'] ?? null);

        $p['amenities'] = DB::table('property_amenities')
            ->join('amenities', 'property_amenities.amenity_id', '=', 'amenities.id')
            ->where('property_amenities.property_id', $id)
            ->select('amenities.*')
            ->get();

        // Fix: images with full URLs
        $rawImages = DB::table('property_images')->where('property_id', $id)->orderBy('display_order')->get();
        $p['images'] = $this->mapImages($rawImages);

        if (is_string($p['sharing_options'] ?? null)) {
            $p['sharing_options'] = json_decode($p['sharing_options'], true);
        }
        if (is_string($p['food_timing'] ?? null)) {
            $p['food_timing'] = json_decode($p['food_timing'], true);
        }

        $p['landmarks'] = DB::table('property_landmarks')
            ->join('landmarks', 'property_landmarks.landmark_id', '=', 'landmarks.id')
            ->where('property_landmarks.property_id', $id)
            ->select('landmarks.id', 'landmarks.name', 'landmarks.type', 'property_landmarks.distance_km')
            ->get();

        return $this->ok((object) $p);
    }

    public function propertyStore(Request $request)
{
   
    $validator = Validator::make($request->all(), [

        'name' => 'required|string|max:255',

        'city_id' => 'required|integer|exists:cities,id',

        'locality_id' => 'required|integer|exists:localities,id',

        'nearby_university_id' => 'nullable|integer',

        'address_line' => 'required|string',

        'property_type' => 'required|in:pg,hostel,coliving,flatmate',

        'gender' => 'required|in:male,female,unisex',

        'rent_min' => 'required|numeric|min:0',

        'rent_max' => 'required|numeric|min:0',

        'security_deposit' => 'nullable|numeric|min:0',

        'food_included' => 'nullable|boolean',

        'food_type' => 'nullable|in:veg,non_veg,both',

        // { "breakfast": {"timing":"8-9AM","days":"all"}, "lunch": {...}, "dinner": {...} }
        // days: all | weekdays | weekends | none
        'food_timing' => 'nullable|array',

        'construction_year' => 'nullable|integer|min:1950|max:' . (date('Y') + 1),

        'pet_allowed' => 'nullable|boolean',

        'guest_entry_allowed' => 'nullable|boolean',

        'sharing_options' => 'nullable|array',

        'pincode' => 'nullable|string',

        'latitude' => 'nullable|string',

        'longitude' => 'nullable|string',

        'google_map_link' => 'nullable|string',

        'total_rooms' => 'nullable|integer',

        'available_rooms' => 'nullable|integer',

        'cover_image' => 'nullable|string',

    ]);


    if ($validator->fails()) {

        return response()->json([
            'success' => false,
            'errors' => $validator->errors()
        ], 422);

    }



    $slug = Str::slug($request->name) . '-' . uniqid();



    $id = DB::table('properties')->insertGetId([


        'owner_id' => $this->ownerId($request),


        'city_id' => $request->city_id,

        'locality_id' => $request->locality_id,

        'nearby_university_id' => $request->nearby_university_id,



        'name' => $request->name,

        'slug' => $slug,


        'description' => $request->description,

        'rules' => $request->rules,



        'gender' => $request->gender,

        'property_type' => $request->property_type,



        'rent_min' => $request->rent_min,

        'rent_max' => $request->rent_max,


        'security_deposit' => $request->security_deposit ?? 0,



        'food_included' => $request->food_included ?? false,

        'food_type' => $request->food_type,

        'food_timing' => $request->has('food_timing') ? json_encode($request->food_timing) : null,

        'construction_year' => $request->construction_year,

        'pet_allowed' => $request->pet_allowed ?? false,

        'guest_entry_allowed' => $request->guest_entry_allowed ?? false,

        'sharing_options' => json_encode($request->sharing_options),



        'address_line' => $request->address_line,

        'landmark' => $request->landmark,

        'pincode' => $request->pincode,



        'latitude' => $request->latitude,

        'longitude' => $request->longitude,

        'google_map_link' => $request->google_map_link,



        'is_active' => true,

        'is_verified' => false,

        'verified_at' => null,

        'is_featured' => false,



        'total_rooms' => $request->total_rooms ?? 0,

        'available_rooms' => $request->available_rooms ?? 0,



        'meta_title' => $request->meta_title,

        'meta_description' => $request->meta_description,



        'cover_image' => $request->cover_image,



        'view_count' => 0,

        'rating_avg' => 0,

        'rating_count' => 0,

        'lead_count' => 0,



        'created_at' => now(),

        'updated_at' => now(),


    ]);

    $this->syncLandmarks($request, $id);

    return $this->ok([

        'id' => $id,

        'slug' => $slug

    ]);

}

    // Nearby locations (metro/hospital/market/etc) — expects
    // landmarks: [{"landmark_id": 6, "distance_km": 1.2}, ...]
    private function syncLandmarks(Request $request, int $propertyId): void
    {
        if (!$request->has('landmarks')) return;

        DB::table('property_landmarks')->where('property_id', $propertyId)->delete();
        $rows = collect($request->landmarks ?? [])
            ->filter(fn ($l) => !empty($l['landmark_id']))
            ->map(fn ($l) => [
                'property_id' => $propertyId,
                'landmark_id' => $l['landmark_id'],
                'distance_km' => $l['distance_km'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ])->toArray();
        if ($rows) DB::table('property_landmarks')->insert($rows);
    }

    public function propertyUpdate(Request $request, $id)
    {
        $property = DB::table('properties')->where('id', $id)->where('owner_id', $this->ownerId($request))->first();
        if (!$property) return $this->notFound();

        $validator = Validator::make($request->all(), [
            'name'                  => 'sometimes|required|string|max:255',
            'description'           => 'nullable|string',
            'rules'                 => 'nullable|string',
            'city_id'               => 'nullable|integer|exists:cities,id',
            'locality_id'           => 'nullable|integer|exists:localities,id',
            'nearby_university_id'  => 'nullable|integer',
            'gender'                => 'sometimes|required|in:male,female,unisex',
            'property_type'         => 'sometimes|required|in:pg,hostel,coliving,flatmate',
            'rent_min'              => 'sometimes|required|numeric|min:0',
            'rent_max'              => 'sometimes|required|numeric|min:0',
            'security_deposit'      => 'nullable|numeric|min:0',
            'food_included'         => 'nullable|boolean',
            'food_type'             => 'nullable|in:veg,non_veg,both',
            'food_timing'           => 'nullable|array',
            'construction_year'     => 'nullable|integer|min:1950|max:' . (date('Y') + 1),
            'pet_allowed'           => 'nullable|boolean',
            'guest_entry_allowed'   => 'nullable|boolean',
            'sharing_options'       => 'nullable|array',
            'address_line'          => 'nullable|string',
            'landmark'              => 'nullable|string',
            'nearby_police_station' => 'nullable|string',
            'pincode'               => 'nullable|string',
            'latitude'              => 'nullable',
            'longitude'             => 'nullable',
            'google_map_link'       => 'nullable|string',
            'total_rooms'           => 'nullable|integer',
            'available_rooms'       => 'nullable|integer',
            'meta_title'            => 'nullable|string',
            'meta_description'      => 'nullable|string',
            'cover_image'           => 'nullable|string',
            'is_active'             => 'nullable|boolean',
            'amenities'             => 'nullable|array',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $data = $request->only([
            'name', 'description', 'rules', 'city_id', 'locality_id', 'nearby_university_id',
            'gender', 'property_type', 'rent_min', 'rent_max', 'security_deposit', 'food_included',
            'food_type', 'construction_year', 'pet_allowed', 'guest_entry_allowed',
            'address_line', 'landmark', 'nearby_police_station', 'pincode', 'latitude', 'longitude',
            'google_map_link', 'total_rooms', 'available_rooms', 'meta_title', 'meta_description',
            'cover_image', 'is_active',
        ]);
        if ($request->has('sharing_options')) {
            $data['sharing_options'] = json_encode($request->sharing_options);
        }
        if ($request->has('food_timing')) {
            $data['food_timing'] = json_encode($request->food_timing);
        }
        $data['updated_at'] = now();
        DB::table('properties')->where('id', $id)->update($data);

        if ($request->has('amenities')) {
            DB::table('property_amenities')->where('property_id', $id)->delete();
            $rows = collect($request->amenities ?? [])->map(fn ($aid) => [
                'property_id' => $id, 'amenity_id' => $aid,
            ])->toArray();
            if ($rows) DB::table('property_amenities')->insert($rows);
        }

        $this->syncLandmarks($request, $id);

        return $this->ok(['message' => 'Updated']);
    }

    public function propertyDelete(Request $request, $id)
    {
        $property = DB::table('properties')->where('id', $id)->where('owner_id', $this->ownerId($request))->first();
        if (!$property) return $this->notFound();
        DB::table('properties')->where('id', $id)->update(['deleted_at' => now()]);
        return $this->ok(['message' => 'Deleted']);
    }

    public function propertyPause(Request $request, $id)
    {
        DB::table('properties')->where('id', $id)->where('owner_id', $this->ownerId($request))->update(['is_active' => DB::raw('1 - is_active')]);
        return $this->ok(['message' => 'Toggled']);
    }

    public function uploadPropertyImage(Request $request, $id)
    {
        $property = DB::table('properties')->where('id', $id)->where('owner_id', $this->ownerId($request))->first();
        if (!$property) return $this->notFound();

        if (!$request->hasFile('image')) {
            return response()->json(['success' => false, 'message' => 'No image provided'], 422);
        }

        $path = $request->file('image')->store('properties/gallery', 'public');
        $imgId = DB::table('property_images')->insertGetId([
            'property_id'   => $id,
            'image_path'    => $path,
            'display_order' => 0,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        return $this->ok(['id' => $imgId, 'url' => $this->resolveUrl($path)]);
    }

    public function deletePropertyImage(Request $request, $imageId)
    {
        $img = DB::table('property_images')->where('id', $imageId)->first();
        if (!$img) return $this->notFound();

        $property = DB::table('properties')->where('id', $img->property_id)->where('owner_id', $this->ownerId($request))->first();
        if (!$property) return response()->json(['success' => false, 'message' => 'Forbidden'], 403);

        Storage::disk('public')->delete($img->image_path);
        DB::table('property_images')->where('id', $imageId)->delete();
        return $this->ok(['message' => 'Deleted']);
    }

    // ═══════════════════════════════════════════════════════════════
    //  LEADS
    // ═══════════════════════════════════════════════════════════════
    public function leads(Request $request)
    {
        $oid = $this->ownerId($request);

        $query = DB::table('leads')
            ->leftJoin('properties', 'leads.property_id', '=', 'properties.id')
            ->leftJoin('cities', 'properties.city_id', '=', 'cities.id')
            ->leftJoin('localities', 'properties.locality_id', '=', 'localities.id')
            ->where('properties.owner_id', $oid)
            ->whereNull('leads.deleted_at')
            ->select(
                'leads.id',
                'leads.name',
                'leads.phone',
                'leads.email',
                'leads.preferred_locality',
                'leads.preferred_city',
                'leads.preferred_gender',
                'leads.budget_min',
                'leads.budget_max',
                'leads.move_in_date',
                'leads.message',
                'leads.source',
                'leads.lead_type',
                'leads.status',
                'leads.is_locked',
                'leads.locked_at',
                'leads.created_at',
                'leads.updated_at',
                // NOTE: the `leads.is_locked` DB column is misleadingly named —
                // it is actually set to TRUE once the owner has PAID/unlocked
                // the lead (see leadUnlock()). So "is_locked = 1" really means
                // "unlocked", which is why phone/email are revealed on that
                // condition. We don't rename the DB column (too many other
                // places depend on it) — instead we expose it to the app as
                // the correctly-named `is_unlocked` field below.
                DB::raw("CASE WHEN leads.is_locked = 1 THEN leads.phone ELSE NULL END as phone_visible"),
                DB::raw("CASE WHEN leads.is_locked = 1 THEN leads.email ELSE NULL END as email_visible"),
                'properties.id as property_id',
                'properties.name as property_name',
                'properties.slug as property_slug',
                'properties.cover_image as property_cover_image',
                'properties.property_type',
                'properties.gender as property_gender',
                'properties.rent_min',
                'properties.rent_max',
                'properties.security_deposit',
                'properties.address_line',
                'properties.landmark',
                'properties.pincode',
                'properties.latitude',
                'properties.longitude',
                'properties.is_verified',
                'properties.rating_avg',
                'properties.rating_count',
                'properties.total_rooms',
                'properties.available_rooms',
                'cities.name as city_name',
                'localities.name as locality_name'
            )
            ->orderBy('leads.created_at', 'desc');

        if ($request->status) $query->where('leads.status', $request->status);
        if ($request->property_id) $query->where('leads.property_id', $request->property_id);

        $paginated = $query->paginate(20);

        // credit_cost per lead_type (direct/verified/converted/manual) — used
        // below to tell the app exactly how many credits unlocking THIS lead
        // will cost, without it having to know the pricing table itself.
        $pricing = DB::table('lead_pricing')->where('is_active', true)->pluck('credit_cost', 'lead_type');

        // Reshape into a clean structure — lead fields + a full "property" object,
        // instead of a flat row with only property_name.
        $paginated->getCollection()->transform(function ($row) use ($pricing) {
            return [
                'id' => $row->id,
                'name' => $row->name,
                // phone/email stay masked (null) until the owner unlocks this lead —
                // that business rule is unchanged, just applied consistently here.
                'phone' => $row->phone_visible,
                'email' => $row->email_visible,
                // Correctly-named field — true once the owner has paid to
                // unlock this lead (phone/email become visible above).
                'is_unlocked' => (bool) $row->is_locked,
                'unlocked_at' => $row->locked_at,
                // Set by admin/telecaller when they verify a lead's genuineness
                // (Owner\LeadController::markVerified() / telecaller equivalent) —
                // NOT related to unlock/payment status above.
                'is_lead_verified' => $row->lead_type === 'verified',
                // Credits it costs THIS owner to unlock this specific lead —
                // varies by lead_type (verified leads cost more, e.g. 40 vs 20).
                'credit_value' => (int) ($pricing[$row->lead_type] ?? $pricing['direct'] ?? 20),
                'preferred_locality' => $row->preferred_locality,
                'preferred_city' => $row->preferred_city,
                'preferred_gender' => $row->preferred_gender,
                'budget_min' => $row->budget_min,
                'budget_max' => $row->budget_max,
                'move_in_date' => $row->move_in_date,
                'message' => $row->message,
                'source' => $row->source,
                'lead_type' => $row->lead_type,
                'status' => $row->status,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
                'property' => $row->property_id ? [
                    'id' => $row->property_id,
                    'name' => $row->property_name,
                    'slug' => $row->property_slug,
                    'cover_image_url' => $row->property_cover_image
                        ? (str_starts_with($row->property_cover_image, 'http')
                            ? $row->property_cover_image
                            : asset('storage/' . $row->property_cover_image))
                        : null,
                    'property_type' => $row->property_type,
                    'gender' => $row->property_gender,
                    'rent_min' => $row->rent_min,
                    'rent_max' => $row->rent_max,
                    'security_deposit' => $row->security_deposit,
                    'address_line' => $row->address_line,
                    'landmark' => $row->landmark,
                    'city' => $row->city_name,
                    'locality' => $row->locality_name,
                    'pincode' => $row->pincode,
                    'latitude' => $row->latitude,
                    'longitude' => $row->longitude,
                    'is_verified' => (bool) $row->is_verified,
                    'rating_avg' => $row->rating_avg,
                    'rating_count' => $row->rating_count,
                    'total_rooms' => $row->total_rooms,
                    'available_rooms' => $row->available_rooms,
                ] : null,
            ];
        });

        return $this->ok($paginated);
    }

    public function leadUnlock(Request $request, $id)
    {
        $oid = $this->ownerId($request);
        $lead = DB::table('leads')->where('id', $id)->first();
        if (!$lead) return $this->notFound();

        // A lead must either belong to one of this owner's own properties,
        // or have no property_id yet (unmatched pool lead) — otherwise any
        // owner could pay to unlock a lead that was never actually theirs.
        if ($lead->property_id) {
            $ownsProperty = DB::table('properties')->where('id', $lead->property_id)->where('owner_id', $oid)->exists();
            if (!$ownsProperty) return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        if ($lead->is_locked) {
            return $this->ok(['message' => 'Already unlocked', 'lead' => $this->formatLeadForApp($lead)]);
        }

        $wallet = DB::table('wallets')->where('user_id', $oid)->first();
        $leadType = $lead->lead_type ?? 'direct';
        $cost = DB::table('lead_pricing')->where('lead_type', $leadType)->where('is_active', true)->value('credit_cost') ?? 1;

        if (!$wallet || $wallet->balance < $cost) {
            return response()->json(['success' => false, 'message' => 'Insufficient credits'], 402);
        }

        DB::transaction(function () use ($oid, $id, $cost, $wallet, $lead) {
            $newBalance = $wallet->balance - $cost;
            DB::table('wallets')->where('user_id', $oid)->update([
                'balance'        => $newBalance,
                'lifetime_spent' => $wallet->lifetime_spent + $cost,
                'updated_at'     => now(),
            ]);
            DB::table('wallet_transactions')->insert([
                'wallet_id'     => $wallet->id,
                'user_id'       => $oid,
                'type'          => 'debit',
                'amount'        => $cost,
                'balance_after' => $newBalance,
                'source'        => 'lead_unlock',
                'lead_id'       => $id,
                'notes'         => 'Lead unlock #' . $id,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
            DB::table('leads')->where('id', $id)->update([
                'is_locked' => true,
                'locked_by_user_id' => $oid,
                'locked_at' => now(),
                'updated_at'  => now(),
            ]);
        });

        return $this->ok(['message' => 'Unlocked', 'lead' => $this->formatLeadForApp(DB::table('leads')->where('id', $id)->first())]);
    }

    /**
     * Rewrites the raw `leads` row's confusingly-named `is_locked` column
     * (true = actually UNLOCKED/paid — see the note in leads() above) into
     * a correctly-named `is_unlocked` field, and masks phone/email until
     * unlocked. Used anywhere a single raw lead row needs to go to the app.
     */
    private function formatLeadForApp($lead)
    {
        $lead = (array) $lead;
        $unlocked = (bool) ($lead['is_locked'] ?? false);
        $lead['is_unlocked'] = $unlocked;
        $lead['unlocked_at'] = $lead['locked_at'] ?? null;
        unset($lead['is_locked'], $lead['locked_at']);
        if (!$unlocked) {
            $lead['phone'] = null;
            $lead['email'] = null;
        }

        // Same verified-flag + per-lead credit cost as the leads() list —
        // kept here too so a single-lead response (unlock, dashboard) has
        // the same shape.
        $leadType = $lead['lead_type'] ?? 'direct';
        $lead['is_lead_verified'] = $leadType === 'verified';
        $lead['credit_value'] = (int) (DB::table('lead_pricing')->where('lead_type', $leadType)->where('is_active', true)->value('credit_cost') ?? 20);

        return (object) $lead;
    }

    // ═══════════════════════════════════════════════════════════════
    //  WALLET / CREDITS
    // ═══════════════════════════════════════════════════════════════
    public function wallet(Request $request)
    {
        $oid = $this->ownerId($request);
        $wallet = DB::table('wallets')->where('user_id', $oid)->first();
        $transactions = DB::table('wallet_transactions')->where('user_id', $oid)->orderBy('created_at', 'desc')->limit(20)->get();
        return $this->ok(['wallet' => $wallet, 'transactions' => $transactions]);
    }

    public function credits()
    {
        $packages = DB::table('credit_packages')->where('is_active', 1)->orderBy('credits')->get();
        return $this->ok($packages);
    }

    public function createOrder(Request $request)
    {
        $request->validate(['package_id' => 'required|integer']);
        $package = DB::table('credit_packages')->where('id', $request->package_id)->where('is_active', 1)->first();
        if (!$package) return $this->notFound();

        try {
            $razorpay = new \Razorpay\Api\Api(config('services.razorpay.key'), config('services.razorpay.secret'));
            $order = $razorpay->order->create([
                'amount'   => $package->price_inr * 100,
                'currency' => 'INR',
                'receipt'  => 'credits_' . $this->ownerId($request) . '_' . time(),
                'notes'    => ['package_id' => $package->id, 'credits' => $package->credits],
            ]);
            return $this->ok(['order_id' => $order->id, 'amount' => $package->price_inr * 100, 'package' => $package]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function verifyPayment(Request $request)
    {
        $request->validate([
            'package_id'           => 'required|integer',
            'razorpay_order_id'    => 'required|string',
            'razorpay_payment_id'  => 'required|string',
            'razorpay_signature'   => 'required|string',
        ]);

        // Verify the payment actually happened with Razorpay before crediting
        // anything — without this check, anyone with a valid login token
        // could call this endpoint directly and get free credits.
        try {
            $razorpay = new \Razorpay\Api\Api(config('services.razorpay.key'), config('services.razorpay.secret'));
            $razorpay->utility->verifyPaymentSignature([
                'razorpay_order_id'   => $request->razorpay_order_id,
                'razorpay_payment_id' => $request->razorpay_payment_id,
                'razorpay_signature'  => $request->razorpay_signature,
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Payment verification failed: ' . $e->getMessage()], 422);
        }

        $oid = $this->ownerId($request);
        $order = DB::table('credit_packages')->where('id', $request->package_id)->first();
        if (!$order) return $this->notFound();

        $totalCredits = $order->credits + ($order->bonus_credits ?? 0);
        $paymentRef = $request->razorpay_payment_id;

        DB::transaction(function () use ($oid, $order, $totalCredits, $paymentRef) {
            $wallet = DB::table('wallets')->where('user_id', $oid)->first();
            if ($wallet) {
                $newBalance = $wallet->balance + $totalCredits;
                DB::table('wallets')->where('user_id', $oid)->update([
                    'balance' => $newBalance,
                    'lifetime_added' => $wallet->lifetime_added + $totalCredits,
                    'updated_at' => now(),
                ]);
                $walletId = $wallet->id;
            } else {
                $newBalance = $totalCredits;
                $walletId = DB::table('wallets')->insertGetId([
                    'user_id' => $oid, 'balance' => $totalCredits,
                    'lifetime_added' => $totalCredits,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
            DB::table('wallet_transactions')->insert([
                'wallet_id' => $walletId,
                'user_id' => $oid, 'type' => 'credit', 'amount' => $totalCredits,
                'balance_after' => $newBalance,
                'source' => 'purchase',
                'reference' => $paymentRef,
                'notes' => 'Credits purchase: ' . $order->name,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        });

        return $this->ok(['message' => 'Credits added']);
    }

    // ═══════════════════════════════════════════════════════════════
    //  RENT BILLS
    // ═══════════════════════════════════════════════════════════════
    public function rent(Request $request)
    {
        $oid = $this->ownerId($request);
        $query = DB::table('rent_bills as rb')
            ->leftJoin('tenants as t', 'rb.tenant_id', '=', 't.id')
            ->leftJoin('properties as p', 'rb.property_id', '=', 'p.id')
            ->where('rb.owner_id', $oid)
            ->select('rb.*', 't.name as tenant_name', 'p.name as property_name')
            ->orderBy('rb.created_at', 'desc');

        if ($request->status)    $query->where('rb.status', $request->status);
        if ($request->month)     $query->where('rb.month', $request->month);
        if ($request->tenant_id) $query->where('rb.tenant_id', $request->tenant_id);

        return $this->ok($query->get());
    }

    public function rentShow(Request $request, $id)
    {
        $bill = DB::table('rent_bills')->where('id', $id)->where('owner_id', $this->ownerId($request))->first();
        if (!$bill) return $this->notFound();
        $bill->payments = DB::table('rent_payments')->where('rent_bill_id', $id)->orderBy('paid_at', 'desc')->get();
        return $this->ok($bill);
    }

    public function rentStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tenant_id'   => 'required|integer|exists:tenants,id',
            'month'       => 'required|string',
            'rent_amount' => 'required|numeric|min:0',
            'due_date'    => 'required|date',
        ]);
        if ($validator->fails()) return response()->json(['success' => false, 'errors' => $validator->errors()], 422);

        $oid    = $this->ownerId($request);
        $tenant = DB::table('tenants')->where('id', $request->tenant_id)->first();
        $rent   = $request->rent_amount;
        $total  = $rent + ($request->electricity ?? 0) + ($request->water ?? 0) + ($request->maintenance ?? 0) + ($request->food_charges ?? 0) + ($request->other_charges ?? 0) + ($request->late_fee ?? 0) - ($request->discount ?? 0);
        $billNo = 'BILL-' . date('Ym') . '-' . str_pad($tenant->id, 4, '0', STR_PAD_LEFT) . '-' . substr(uniqid(), -4);

        $id = DB::table('rent_bills')->insertGetId([
            'tenant_id' => $tenant->id, 'property_id' => $tenant->property_id, 'owner_id' => $oid,
            'bill_number' => $billNo, 'month' => $request->month, 'rent_amount' => $rent,
            'electricity' => $request->electricity ?? 0, 'water' => $request->water ?? 0,
            'maintenance' => $request->maintenance ?? 0, 'food_charges' => $request->food_charges ?? 0,
            'other_charges' => $request->other_charges ?? 0, 'other_charges_label' => $request->other_charges_label,
            'late_fee' => $request->late_fee ?? 0,
            'discount' => $request->discount ?? 0, 'total_amount' => $total, 'paid_amount' => 0,
            'due_amount' => $total, 'due_date' => $request->due_date, 'status' => 'pending',
            'notes' => $request->notes, 'created_at' => now(), 'updated_at' => now(),
        ]);
        return $this->ok(['id' => $id, 'bill_number' => $billNo]);
    }

    public function rentPayment(Request $request, $id)
    {
        $bill = DB::table('rent_bills')->where('id', $id)->where('owner_id', $this->ownerId($request))->first();
        if (!$bill) return $this->notFound();

        $amount   = (float) $request->amount;
        $receiptNo = 'RCPT-' . date('Ymd') . '-' . substr(uniqid(), -6);
        $newPaid   = $bill->paid_amount + $amount;
        $newDue    = $bill->total_amount - $newPaid;
        $status    = $newDue <= 0 ? 'paid' : ($newPaid > 0 ? 'partial' : 'pending');

        DB::table('rent_payments')->insertGetId([
            'rent_bill_id' => $id, 'tenant_id' => $bill->tenant_id, 'owner_id' => $this->ownerId($request),
            'receipt_number' => $receiptNo, 'amount' => $amount,
            'payment_method' => $request->payment_method ?? 'cash',
            'transaction_ref' => $request->transaction_ref, 'paid_at' => $request->paid_at ?? now(),
            'received_by_id' => $this->ownerId($request), 'notes' => $request->notes,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        DB::table('rent_bills')->where('id', $id)->update([
            'paid_amount' => $newPaid, 'due_amount' => max(0, $newDue), 'status' => $status, 'updated_at' => now(),
        ]);
        return $this->ok(['receipt_number' => $receiptNo]);
    }

    public function rentGenerateAll(Request $request)
    {
        $oid     = $this->ownerId($request);
        $month   = $request->month ?? now()->format('Y-m');
        $dueDate = $request->due_date ?? now()->format('Y-m-05');

        $tenants = DB::table('tenants')->where('owner_id', $oid)->where('status', 'active')->whereNotNull('monthly_rent')->where('monthly_rent', '>', 0)->get();

        $generated = 0; $skipped = 0;
        foreach ($tenants as $t) {
            if (DB::table('rent_bills')->where('tenant_id', $t->id)->where('month', $month)->exists()) { $skipped++; continue; }
            $billNo = 'BILL-' . str_replace('-', '', $month) . '-' . str_pad($t->id, 4, '0', STR_PAD_LEFT);
            DB::table('rent_bills')->insert([
                'tenant_id' => $t->id, 'property_id' => $t->property_id, 'owner_id' => $oid,
                'bill_number' => $billNo, 'month' => $month, 'rent_amount' => $t->monthly_rent,
                'total_amount' => $t->monthly_rent, 'paid_amount' => 0, 'due_amount' => $t->monthly_rent,
                'due_date' => $dueDate, 'status' => 'pending', 'created_at' => now(), 'updated_at' => now(),
            ]);
            $generated++;
        }
        return $this->ok(['generated' => $generated, 'skipped' => $skipped]);
    }

    // ═══════════════════════════════════════════════════════════════
    //  TENANTS
    // ═══════════════════════════════════════════════════════════════
    public function tenants(Request $request)
    {
        $oid = $this->ownerId($request);
        $query = DB::table('tenants as t')
            ->leftJoin('properties as p', 't.property_id', '=', 'p.id')
            ->where('t.owner_id', $oid)
            ->select('t.*', 'p.name as property_name')
            ->orderBy('t.created_at', 'desc');

        if ($request->status)      $query->where('t.status', $request->status);
        if ($request->property_id) $query->where('t.property_id', $request->property_id);
        if ($request->q) {
            $q = $request->q;
            $query->where(fn($qu) => $qu->where('t.name', 'like', "%$q%")->orWhere('t.phone', 'like', "%$q%"));
        }

        return $this->ok($query->get());
    }

    public function tenantShow(Request $request, $id)
    {
        $tenant = DB::table('tenants')->where('id', $id)->where('owner_id', $this->ownerId($request))->first();
        if (!$tenant) return $this->notFound();

        $tenant = (array) $tenant;
        $tenant['property']   = DB::table('properties')->where('id', $tenant['property_id'])->first();
        $tenant['documents']  = DB::table('tenant_documents')->where('tenant_id', $id)->get();
        $tenant['bills']      = DB::table('rent_bills')->where('tenant_id', $id)->orderBy('due_date', 'desc')->limit(12)->get();
        $tenant['complaints'] = DB::table('complaints')->where('tenant_id', $id)->orderBy('created_at', 'desc')->limit(10)->get();
        $tenant['agreement']  = DB::table('rent_agreements')->where('tenant_id', $id)->latest()->first();

        return $this->ok((object) $tenant);
    }

    public function tenantStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'property_id'             => 'required|integer|exists:properties,id',
            'bed_id'                  => 'nullable|integer|exists:beds,id',
            'name'                    => 'required|string|max:200',
            'phone'                   => 'required|string|max:20',
            'email'                   => 'nullable|email|max:150',
            'dob'                     => 'nullable|date',
            'gender'                  => 'nullable|in:male,female,other',
            'occupation'              => 'nullable|string|max:100',
            'company_college'         => 'nullable|string|max:200',
            'address_line'            => 'nullable|string',
            'city'                    => 'nullable|string|max:100',
            'state'                   => 'nullable|string|max:100',
            'pincode'                 => 'nullable|string|max:10',
            'emergency_name'          => 'nullable|string|max:200',
            'emergency_phone'         => 'nullable|string|max:20',
            'emergency_relation'      => 'nullable|string|max:50',
            'room_number'             => 'nullable|string|max:50',
            'bed_number'              => 'nullable|string|max:20',
            'monthly_rent'            => 'nullable|numeric|min:0',
            'security_deposit'        => 'nullable|numeric|min:0',
            'security_deposit_status' => 'nullable|in:full,half,pending',
            'advance_rent_months'     => 'nullable|numeric|min:0|max:12',
            'advance_rent_amount'     => 'nullable|numeric|min:0',
            'move_in_date'            => 'nullable|date',
            'notes'                   => 'nullable|string',
        ]);
        if ($validator->fails()) return response()->json(['success' => false, 'errors' => $validator->errors()], 422);

        // Bed select ki hai to uski room_number/bed_number/rent nikal ke bhar do,
        // aur usko occupied mark kr do (web form ka wahi behaviour).
        $bed = null;
        if ($request->filled('bed_id')) {
            $bed = DB::table('beds')->where('id', $request->bed_id)
                ->where('property_id', $request->property_id)
                ->where('status', 'vacant')
                ->first();
            if (!$bed) {
                return response()->json(['success' => false, 'errors' => ['bed_id' => ['This bed is no longer available.']]], 422);
            }
        }

        $ownerId = $this->ownerId($request);

        // Phone se pehle se koi tenant user account ho to usi se link karo,
        // warna naya tenant-role user bana do (web form jaisa behaviour).
        $phone = substr(preg_replace('/[^0-9]/', '', $request->phone), -10);
        $user = DB::table('users')->where('phone', $phone)->first();
        if ($user && $user->role !== 'tenant') {
            return response()->json(['success' => false, 'errors' => ['phone' => ["This number is already registered as a '{$user->role}' account."]]], 422);
        }
        if (!$user) {
            $userId = DB::table('users')->insertGetId([
                'name'       => $request->name,
                'phone'      => $phone,
                'email'      => $phone . '@temp.pizi.in',
                'password'   => bcrypt(Str::random(32)),
                'role'       => 'tenant',
                'is_active'  => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $userId = $user->id;
        }

        $fields = $request->only([
            'property_id', 'name', 'phone', 'email', 'dob', 'gender', 'occupation',
            'company_college', 'address_line', 'city', 'state', 'pincode',
            'room_number', 'bed_number', 'monthly_rent', 'security_deposit', 'move_in_date',
            'emergency_name', 'emergency_phone', 'emergency_relation', 'notes',
        ]);

        if ($bed) {
            $fields['room_number']  = DB::table('rooms')->where('id', $bed->room_id)->value('room_number') ?? ($fields['room_number'] ?? null);
            $fields['bed_number']   = $bed->bed_number;
            $fields['monthly_rent'] = $fields['monthly_rent'] ?? $bed->monthly_rent;
        }

        // Security deposit status — Tenant table me alag column nahi hai,
        // web form ki tarah notes me append kar do.
        if ($request->filled('security_deposit_status')) {
            $fields['notes'] = trim(($fields['notes'] ?? '') . "\nSecurity Deposit Status: " . ucfirst($request->security_deposit_status) . " paid.");
        }

        $id = DB::table('tenants')->insertGetId(array_merge($fields, [
            'owner_id'   => $ownerId,
            'user_id'    => $userId,
            'kyc_status' => 'pending',
            'status'     => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]));

        if ($bed) {
            DB::table('beds')->where('id', $bed->id)->update([
                'tenant_id'      => $id,
                'status'         => 'occupied',
                'occupied_since' => now(),
                'updated_at'     => now(),
            ]);
        }

        // Advance rent liya ho to RentBill + RentPayment bana do (web form jaisa).
        $advanceMonths = (float) $request->input('advance_rent_months', 0);
        $advanceAmount = (float) $request->input('advance_rent_amount', 0);
        if ($advanceAmount > 0) {
            $billId = DB::table('rent_bills')->insertGetId([
                'tenant_id'    => $id,
                'property_id'  => $request->property_id,
                'owner_id'     => $ownerId,
                'bill_number'  => 'PIZI-' . date('Ymd') . '-' . strtoupper(Str::random(4)),
                'month'        => now()->format('Y-m'),
                'rent_amount'  => $advanceAmount,
                'total_amount' => $advanceAmount,
                'paid_amount'  => $advanceAmount,
                'due_amount'   => 0,
                'due_date'     => now(),
                'status'       => 'paid',
                'notes'        => 'Advance rent (' . $advanceMonths . ' month(s)) collected at onboarding.',
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);

            DB::table('rent_payments')->insert([
                'rent_bill_id'   => $billId,
                'tenant_id'      => $id,
                'owner_id'       => $ownerId,
                'amount'         => $advanceAmount,
                'payment_method' => 'cash',
                'received_by_id' => $ownerId,
                'receipt_number' => 'RCP-' . date('Ymd') . '-' . strtoupper(Str::random(5)),
                'paid_at'        => now(),
                'notes'          => 'Advance rent collected at tenant onboarding.',
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
        }

        return $this->ok(['id' => $id]);
    }

    public function tenantUpdate(Request $request, $id)
    {
        $tenant = DB::table('tenants')->where('id', $id)->where('owner_id', $this->ownerId($request))->first();
        if (!$tenant) return $this->notFound();

        $data = $request->only([
            'name', 'phone', 'email', 'dob', 'gender', 'occupation',
            'company_college', 'address_line', 'city', 'state', 'pincode',
            'room_number', 'bed_number', 'monthly_rent', 'security_deposit',
            'move_in_date', 'move_out_date', 'notice_date', 'notice_reason', 'notes', 'status',
            'emergency_name', 'emergency_phone', 'emergency_relation',
        ]);
        $data['updated_at'] = now();
        DB::table('tenants')->where('id', $id)->update($data);

        return $this->ok(['message' => 'Updated']);
    }

    public function tenantDelete(Request $request, $id)
    {
        $tenant = DB::table('tenants')->where('id', $id)->where('owner_id', $this->ownerId($request))->first();
        if (!$tenant) return $this->notFound();
        DB::table('tenants')->where('id', $id)->delete();
        return $this->ok(['message' => 'Deleted']);
    }

    // ═══════════════════════════════════════════════════════════════
    //  ROOMS
    // ═══════════════════════════════════════════════════════════════
    public function rooms(Request $request)
    {
        $oid = $this->ownerId($request);
        $query = DB::table('rooms')->where('owner_id', $oid)->whereNull('deleted_at')->orderBy('created_at', 'desc');
        if ($request->property_id) $query->where('property_id', $request->property_id);
        $rooms = $query->get();
        $roomIds = $rooms->pluck('id');
        $beds = DB::table('beds')->whereIn('room_id', $roomIds)->whereNull('deleted_at')->get()->groupBy('room_id');
        $rooms = $rooms->map(function ($r) use ($beds) {
            $r = (array) $r;
            $r['beds'] = $beds->get($r['id'], collect([]))->values();
            return (object) $r;
        });
        return $this->ok($rooms);
    }

    public function roomShow(Request $request, $id)
    {
        $room = DB::table('rooms')->where('id', $id)->where('owner_id', $this->ownerId($request))->whereNull('deleted_at')->first();
        if (!$room) return $this->notFound();
        $room = (array) $room;
        $room['beds'] = DB::table('beds')->where('room_id', $id)->whereNull('deleted_at')->get();
        return $this->ok((object) $room);
    }

    public function roomStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'property_id'            => 'required|integer|exists:properties,id',
            'room_number'            => 'required|string|max:50',
            'floor'                  => 'nullable|string|max:50',
            'room_type'              => 'required|in:single,double,triple,quad,quint,dorm',
            'gender'                 => 'required|in:male,female,unisex',
            'monthly_rent'           => 'nullable|numeric|min:0',
            'security_deposit'       => 'nullable|numeric|min:0',
            'has_ac'                 => 'nullable|boolean',
            'has_attached_bathroom'  => 'nullable|boolean',
            'has_balcony'            => 'nullable|boolean',
            'has_geyser'             => 'nullable|boolean',
            'has_wifi'               => 'nullable|boolean',
            'amenities'              => 'nullable|array',
            'amenities.*'            => 'integer|exists:amenities,id',
            'notes'                  => 'nullable|string',
            'auto_create_beds'       => 'nullable|boolean',
        ]);
        if ($validator->fails()) return response()->json(['success' => false, 'errors' => $validator->errors()], 422);

        $exists = DB::table('rooms')->where('property_id', $request->property_id)
            ->where('room_number', $request->room_number)->exists();
        if ($exists) {
            return response()->json(['success' => false, 'errors' => ['room_number' => ['Room already exists.']]], 422);
        }

        $fields = $request->only([
            'property_id', 'room_number', 'floor', 'room_type', 'monthly_rent', 'security_deposit',
            'gender', 'has_ac', 'has_attached_bathroom', 'has_balcony', 'has_geyser', 'has_wifi', 'notes',
        ]);
        foreach (['has_ac', 'has_attached_bathroom', 'has_balcony', 'has_geyser', 'has_wifi'] as $f) {
            $fields[$f] = $request->boolean($f);
        }

        $id = DB::table('rooms')->insertGetId(array_merge($fields, [
            'owner_id'   => $this->ownerId($request),
            'status'     => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]));

        if ($request->filled('amenities')) {
            $rows = collect($request->amenities)->map(fn ($aid) => [
                'room_id' => $id, 'amenity_id' => $aid,
            ])->toArray();
            if ($rows) DB::table('room_amenities')->insert($rows);
        }

        // Auto create beds based on room type capacity — same as owner dashboard.
        if ($request->boolean('auto_create_beds')) {
            $capacityMap = ['single' => 1, 'double' => 2, 'triple' => 3, 'quad' => 4, 'quint' => 5, 'dorm' => 8];
            $capacity = $capacityMap[$request->room_type] ?? 2;
            $rent = (float) ($request->monthly_rent ?? 0);
            $bedRent = $rent > 0 ? round($rent / $capacity) : 0;

            for ($i = 1; $i <= $capacity; $i++) {
                DB::table('beds')->insert([
                    'room_id'      => $id,
                    'property_id'  => $request->property_id,
                    'owner_id'     => $this->ownerId($request),
                    'bed_number'   => chr(64 + $i), // A, B, C...
                    'bed_type'     => 'single',
                    'monthly_rent' => $bedRent,
                    'status'       => 'vacant',
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]);
            }
        }

        return $this->ok(['id' => $id]);
    }

    public function roomUpdate(Request $request, $id)
    {
        $room = DB::table('rooms')->where('id', $id)->where('owner_id', $this->ownerId($request))->first();
        if (!$room) return $this->notFound();

        $validator = Validator::make($request->all(), [
            'room_number'            => 'sometimes|required|string|max:50',
            'floor'                  => 'nullable|string|max:50',
            'room_type'              => 'sometimes|required|in:single,double,triple,quad,quint,dorm',
            'gender'                 => 'sometimes|required|in:male,female,unisex',
            'monthly_rent'           => 'nullable|numeric|min:0',
            'security_deposit'       => 'nullable|numeric|min:0',
            'has_ac'                 => 'nullable|boolean',
            'has_attached_bathroom'  => 'nullable|boolean',
            'has_balcony'            => 'nullable|boolean',
            'has_geyser'             => 'nullable|boolean',
            'has_wifi'               => 'nullable|boolean',
            'amenities'              => 'nullable|array',
            'amenities.*'            => 'integer|exists:amenities,id',
            'notes'                  => 'nullable|string',
            'status'                 => 'nullable|in:active,maintenance,closed',
        ]);
        if ($validator->fails()) return response()->json(['success' => false, 'errors' => $validator->errors()], 422);

        $data = $request->only([
            'room_number', 'floor', 'room_type', 'monthly_rent', 'security_deposit',
            'gender', 'has_ac', 'has_attached_bathroom', 'has_balcony', 'has_geyser', 'has_wifi', 'notes', 'status',
        ]);
        foreach (['has_ac', 'has_attached_bathroom', 'has_balcony', 'has_geyser', 'has_wifi'] as $f) {
            if ($request->has($f)) $data[$f] = $request->boolean($f);
        }
        $data['updated_at'] = now();
        DB::table('rooms')->where('id', $id)->update($data);

        if ($request->has('amenities')) {
            DB::table('room_amenities')->where('room_id', $id)->delete();
            $rows = collect($request->amenities ?? [])->map(fn ($aid) => [
                'room_id' => $id, 'amenity_id' => $aid,
            ])->toArray();
            if ($rows) DB::table('room_amenities')->insert($rows);
        }

        return $this->ok(['message' => 'Updated']);
    }

    public function roomDelete(Request $request, $id)
    {
        $room = DB::table('rooms')->where('id', $id)->where('owner_id', $this->ownerId($request))->first();
        if (!$room) return $this->notFound();

        $occupied = DB::table('beds')->where('room_id', $id)->where('status', 'occupied')->exists();
        if ($occupied) {
            return response()->json(['success' => false, 'errors' => ['delete' => ['Cannot delete: room has occupied beds.']]], 422);
        }

        // Soft-delete (not a hard delete) — matches the website's behaviour,
        // where a deleted room lands in Trash and can be restored within 30
        // days instead of being gone forever.
        DB::table('beds')->where('room_id', $id)->update(['deleted_at' => now()]);
        DB::table('rooms')->where('id', $id)->update(['deleted_at' => now()]);
        return $this->ok(['message' => 'Deleted']);
    }

    // ═══════════════════════════════════════════════════════════════
    //  BEDS
    // ═══════════════════════════════════════════════════════════════
    public function bedStore(Request $request, $roomId)
    {
        $room = DB::table('rooms')->where('id', $roomId)->where('owner_id', $this->ownerId($request))->first();
        if (!$room) return $this->notFound();

        $validator = Validator::make($request->all(), [
            'bed_number'   => 'required|string|max:20',
            'bed_type'     => 'required|in:single,bunk_lower,bunk_upper,double',
            'monthly_rent' => 'nullable|numeric|min:0',
        ]);
        if ($validator->fails()) return response()->json(['success' => false, 'errors' => $validator->errors()], 422);

        $exists = DB::table('beds')->where('room_id', $roomId)->where('bed_number', $request->bed_number)->exists();
        if ($exists) {
            return response()->json(['success' => false, 'errors' => ['bed_number' => ['Bed already exists.']]], 422);
        }

        $id = DB::table('beds')->insertGetId([
            'room_id'      => $roomId,
            'property_id'  => $room->property_id,
            'owner_id'     => $this->ownerId($request),
            'bed_number'   => $request->bed_number,
            'bed_type'     => $request->bed_type,
            'monthly_rent' => $request->monthly_rent ?? 0,
            'status'       => 'vacant',
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        return $this->ok(['id' => $id]);
    }

    public function bedUpdate(Request $request, $id)
    {
        $bed = DB::table('beds')->where('id', $id)->where('owner_id', $this->ownerId($request))->first();
        if (!$bed) return $this->notFound();

        $validator = Validator::make($request->all(), [
            'bed_number'   => 'sometimes|required|string|max:20',
            'bed_type'     => 'sometimes|required|in:single,bunk_lower,bunk_upper,double',
            'monthly_rent' => 'nullable|numeric|min:0',
            'status'       => 'nullable|in:vacant,occupied,reserved,maintenance',
            'tenant_id'    => 'nullable|integer|exists:tenants,id',
        ]);
        if ($validator->fails()) return response()->json(['success' => false, 'errors' => $validator->errors()], 422);

        $data = $request->only(['bed_number', 'bed_type', 'monthly_rent']);

        // status change ke saath tenant assign/unassign bhi handle karo (web jaisa).
        if ($request->filled('status')) {
            $data['status'] = $request->status;
            if ($request->status === 'occupied' && $request->filled('tenant_id')) {
                $tenant = DB::table('tenants')->where('id', $request->tenant_id)->where('owner_id', $this->ownerId($request))->first();
                if (!$tenant) return response()->json(['success' => false, 'errors' => ['tenant_id' => ['Tenant not found.']]], 422);
                $data['tenant_id'] = $tenant->id;
                $data['occupied_since'] = now();
                $room = DB::table('rooms')->where('id', $bed->room_id)->first();
                DB::table('tenants')->where('id', $tenant->id)->update([
                    'room_number' => $room->room_number, 'bed_number' => $data['bed_number'] ?? $bed->bed_number,
                    'property_id' => $room->property_id, 'updated_at' => now(),
                ]);
            } elseif ($request->status === 'vacant') {
                $data['tenant_id'] = null;
                $data['occupied_since'] = null;
            }
        }

        $data['updated_at'] = now();
        DB::table('beds')->where('id', $id)->update($data);

        return $this->ok(['message' => 'Updated']);
    }

    public function bedDelete(Request $request, $id)
    {
        $bed = DB::table('beds')->where('id', $id)->where('owner_id', $this->ownerId($request))->first();
        if (!$bed) return $this->notFound();
        if ($bed->status === 'occupied') {
            return response()->json(['success' => false, 'errors' => ['delete' => ['Cannot delete occupied bed.']]], 422);
        }
        DB::table('beds')->where('id', $id)->update(['deleted_at' => now()]);
        return $this->ok(['message' => 'Deleted']);
    }

    // ═══════════════════════════════════════════════════════════════
    //  COMPLAINTS
    // ═══════════════════════════════════════════════════════════════
    public function complaints(Request $request)
    {
        $oid = $this->ownerId($request);
        $query = DB::table('complaints as c')
            ->leftJoin('tenants as t', 'c.tenant_id', '=', 't.id')
            ->leftJoin('properties as p', 'c.property_id', '=', 'p.id')
            ->where('c.owner_id', $oid)
            ->select('c.*', 't.name as tenant_name', 'p.name as property_name')
            ->orderBy('c.created_at', 'desc');
        if ($request->status) $query->where('c.status', $request->status);
        return $this->ok($query->get());
    }

    public function complaintShow(Request $request, $id)
    {
        $c = DB::table('complaints')->where('id', $id)->where('owner_id', $this->ownerId($request))->first();
        if (!$c) return $this->notFound();
        return $this->ok($c);
    }

    public function complaintUpdateStatus(Request $request, $id)
    {
        DB::table('complaints')->where('id', $id)->where('owner_id', $this->ownerId($request))->update([
            'status' => $request->status, 'resolution_note' => $request->resolution_note, 'updated_at' => now(),
        ]);
        return $this->ok(['message' => 'Status updated']);
    }

    // ═══════════════════════════════════════════════════════════════
    //  AGREEMENTS
    // ═══════════════════════════════════════════════════════════════
    public function agreements(Request $request)
    {
        $oid = $this->ownerId($request);
        $agreements = DB::table('rent_agreements as a')
            ->leftJoin('tenants as t', 'a.tenant_id', '=', 't.id')
            ->leftJoin('properties as p', 'a.property_id', '=', 'p.id')
            ->where('a.owner_id', $oid)
            ->select('a.*', 't.name as tenant_name', 'p.name as property_name')
            ->orderBy('a.created_at', 'desc')
            ->get();
        return $this->ok($agreements);
    }

    public function agreementShow(Request $request, $id)
    {
        $a = DB::table('rent_agreements')->where('id', $id)->where('owner_id', $this->ownerId($request))->first();
        if (!$a) return $this->notFound();
        $a = (array) $a;
        $a['tenant']   = DB::table('tenants')->where('id', $a['tenant_id'])->first();
        $a['property'] = DB::table('properties')->where('id', $a['property_id'])->first();
        return $this->ok((object) $a);
    }

    public function agreementStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tenant_id'        => 'required|integer|exists:tenants,id',
            'monthly_rent'     => 'required|numeric|min:0',
            'security_deposit' => 'required|numeric|min:0',
            'start_date'       => 'required|date',
            'end_date'         => 'required|date|after:start_date',
        ]);
        if ($validator->fails()) return response()->json(['success' => false, 'errors' => $validator->errors()], 422);

        $oid    = $this->ownerId($request);
        $tenant = DB::table('tenants')->where('id', $request->tenant_id)->first();
        $agrNo  = 'AGR-' . date('Ymd') . '-' . str_pad($tenant->id, 4, '0', STR_PAD_LEFT);

        $id = DB::table('rent_agreements')->insertGetId([
            'agreement_number' => $agrNo,
            'tenant_id'        => $tenant->id,
            'property_id'      => $tenant->property_id,
            'owner_id'         => $oid,
            'monthly_rent'     => $request->monthly_rent,
            'security_deposit' => $request->security_deposit,
            'maintenance_fee'  => $request->maintenance_fee ?? 0,
            'start_date'       => $request->start_date,
            'end_date'         => $request->end_date,
            'lock_in_months'   => $request->lock_in_months ?? 3,
            'notice_period_days' => $request->notice_period_days ?? 30,
            'rent_due_day'     => $request->rent_due_day ?? 5,
            'terms_template'   => $request->terms_template ?? 'delhi_standard',
            'additional_terms' => $request->additional_terms,
            'house_rules'      => $request->house_rules,
            'status'           => 'draft',
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);
        return $this->ok(['id' => $id, 'agreement_number' => $agrNo]);
    }

    public function agreementUpdate(Request $request, $id)
    {
        $a = DB::table('rent_agreements')->where('id', $id)->where('owner_id', $this->ownerId($request))->first();
        if (!$a) return $this->notFound();

        $data = $request->only(['monthly_rent', 'security_deposit', 'maintenance_fee', 'start_date', 'end_date', 'lock_in_months', 'notice_period_days', 'additional_terms', 'house_rules']);
        $data['updated_at'] = now();
        DB::table('rent_agreements')->where('id', $id)->update($data);
        return $this->ok(['message' => 'Updated']);
    }

    // ═══════════════════════════════════════════════════════════════
    //  TOKEN PAYMENTS
    // ═══════════════════════════════════════════════════════════════
    public function tokenPayments(Request $request)
    {
        $oid = $this->ownerId($request);
        $payments = DB::table('token_payments as tp')
            ->leftJoin('users as u', 'tp.user_id', '=', 'u.id')
            ->leftJoin('properties as p', 'tp.property_id', '=', 'p.id')
            ->where('p.owner_id', $oid)
            ->select('tp.*', 'u.name as user_name', 'u.phone as user_phone', 'p.name as property_name')
            ->orderBy('tp.created_at', 'desc')
            ->get();
        return $this->ok($payments);
    }

    // ═══════════════════════════════════════════════════════════════
    //  REVIEWS
    // ═══════════════════════════════════════════════════════════════
    public function reviews(Request $request)
    {
        $oid = $this->ownerId($request);
        $propertyIds = DB::table('properties')->where('owner_id', $oid)->pluck('id');
        $reviews = DB::table('reviews as r')
            ->leftJoin('users as u', 'r.user_id', '=', 'u.id')
            ->leftJoin('properties as p', 'r.property_id', '=', 'p.id')
            ->whereIn('r.property_id', $propertyIds)
            ->select('r.*', 'u.name as user_name', 'p.name as property_name')
            ->orderBy('r.created_at', 'desc')
            ->get();
        return $this->ok($reviews);
    }

    public function reviewReply(Request $request, $id)
    {
        $request->validate(['reply' => 'required|string|max:1000']);

        $oid = $this->ownerId($request);
        $review = DB::table('reviews')
            ->join('properties', 'reviews.property_id', '=', 'properties.id')
            ->where('reviews.id', $id)
            ->where('properties.owner_id', $oid)
            ->first();
        if (!$review) return $this->notFound();

        DB::table('reviews')->where('id', $id)->update([
            'owner_response'      => $request->reply,
            'owner_responded_at'  => now(),
            'updated_at'          => now(),
        ]);
        return $this->ok(['message' => 'Reply added']);
    }

    // ═══════════════════════════════════════════════════════════════
    //  BLOGS
    // ═══════════════════════════════════════════════════════════════
    public function blogs(Request $request)
    {
        return $this->ok(DB::table('blogs')->where('author_id', $this->ownerId($request))->orderBy('created_at', 'desc')->get());
    }

    public function blogShow(Request $request, $id)
    {
        $blog = DB::table('blogs')->where('id', $id)->where('author_id', $this->ownerId($request))->first();
        if (!$blog) return $this->notFound();
        return $this->ok($blog);
    }

    public function blogStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title'             => 'required|string|max:255',
            'excerpt'           => 'nullable|string',
            'content'           => 'required|string',
            'meta_title'        => 'nullable|string|max:255',
            'meta_description'  => 'nullable|string|max:320',
            'is_published'      => 'nullable|boolean',
        ]);
        if ($validator->fails()) return response()->json(['success' => false, 'errors' => $validator->errors()], 422);

        $data = $request->only(['title', 'excerpt', 'content', 'meta_title', 'meta_description']);
        $data['is_published'] = $request->boolean('is_published');
        $data['author_id']    = $this->ownerId($request);
        $data['slug']         = Str::slug($request->title) . '-' . Str::random(6);
        $data['published_at'] = $data['is_published'] ? now() : null;
        $data['created_at']   = now();
        $data['updated_at']   = now();

        if ($request->hasFile('cover_image')) {
            $data['cover_image'] = $request->file('cover_image')->store('blogs', 'public');
        }

        $id = DB::table('blogs')->insertGetId($data);
        return $this->ok(['id' => $id]);
    }

    public function blogUpdate(Request $request, $id)
    {
        $blog = DB::table('blogs')->where('id', $id)->where('author_id', $this->ownerId($request))->first();
        if (!$blog) return $this->notFound();

        // Only touch fields actually sent — sending just {"title": "..."} must
        // not blank out content/excerpt/etc like the old shared saveBlog() did.
        $data = $request->only(['title', 'excerpt', 'content', 'meta_title', 'meta_description']);
        if ($request->has('is_published')) {
            $data['is_published'] = $request->boolean('is_published');
            $data['published_at'] = $data['is_published'] ? ($blog->published_at ?? now()) : null;
        }
        if ($request->hasFile('cover_image')) {
            $data['cover_image'] = $request->file('cover_image')->store('blogs', 'public');
        }
        $data['updated_at'] = now();

        DB::table('blogs')->where('id', $id)->update($data);
        return $this->ok(['message' => 'Updated']);
    }

    public function blogDelete(Request $request, $id)
    {
        $blog = DB::table('blogs')->where('id', $id)->where('author_id', $this->ownerId($request))->first();
        if (!$blog) return $this->notFound();
        DB::table('blogs')->where('id', $id)->delete();
        return $this->ok(['message' => 'Deleted']);
    }

    public function blogToggle(Request $request, $id)
    {
        $blog = DB::table('blogs')->where('id', $id)->where('author_id', $this->ownerId($request))->first();
        if (!$blog) return $this->notFound();
        $new = $blog->is_published ? 0 : 1;
        DB::table('blogs')->where('id', $id)->update([
            'is_published' => $new,
            'published_at' => $new ? now() : null,
            'updated_at'   => now(),
        ]);
        return $this->ok(['message' => 'Toggled']);
    }

    // ═══════════════════════════════════════════════════════════════
    //  PRIVATE HELPERS
    // ═══════════════════════════════════════════════════════════════
    private function ok($data)
    {
        return response()->json(['success' => true, 'data' => $data]);
    }

    private function notFound()
    {
        return response()->json(['success' => false, 'message' => 'Not found'], 404);
    }
}