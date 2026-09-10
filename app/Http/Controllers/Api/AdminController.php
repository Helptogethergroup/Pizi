<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AdminController extends Controller
{
    // ========== DASHBOARD ==========
    public function dashboard()
    {
        $stats = [
            'total_properties' => DB::table('properties')->whereNull('deleted_at')->count(),
            'active_properties' => DB::table('properties')->where('is_active', 1)->whereNull('deleted_at')->count(),
            'pending_verification' => DB::table('properties')->where('is_verified', 0)->whereNull('deleted_at')->count(),
            'total_leads' => DB::table('leads')->count(),
            'new_leads' => DB::table('leads')->where('status', 'new')->count(),
            'closed_won' => DB::table('leads')->where('status', 'closed_won')->count(),
            'conversion_rate' => $this->conversionRate(),
            'active_telecallers' => DB::table('users')->where('role', 'telecaller')->where('is_active', 1)->count(),
        ];

        $leadsByStatus = DB::table('leads')
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get();

        $recentLeads = DB::table('leads')
            ->leftJoin('properties', 'leads.property_id', '=', 'properties.id')
            ->select('leads.*', 'properties.name as property_name')
            ->orderBy('leads.created_at', 'desc')
            ->limit(10)
            ->get();

        $unmatchedLeads = DB::table('leads')
            ->whereNull('property_id')
            ->where('status', 'new')
            ->count();

        return $this->ok([
            'stats' => $stats,
            'leads_by_status' => $leadsByStatus,
            'recent_leads' => $recentLeads,
            'unmatched_leads' => $unmatchedLeads,
        ]);
    }

    private function conversionRate(): float
    {
        $total = DB::table('leads')->count();
        if ($total === 0) return 0;
        $won = DB::table('leads')->where('status', 'closed_won')->count();
        return round(($won / $total) * 100, 1);
    }

    // ========== ANALYTICS ==========
    public function analytics()
    {
        $thirtyDaysAgo = now()->subDays(30);

        $revenue = DB::table('credit_transactions')
            ->where('type', 'purchase')
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->sum('amount') ?? 0;

        $totalLeads = DB::table('leads')->where('created_at', '>=', $thirtyDaysAgo)->count();
        $closedWon = DB::table('leads')->where('status', 'closed_won')->where('created_at', '>=', $thirtyDaysAgo)->count();
        $avgDaily = round($revenue / 30, 0);

        $funnel = [
            'new' => DB::table('leads')->where('status', 'new')->count(),
            'contacted' => DB::table('leads')->where('status', 'contacted')->count(),
            'qualified' => DB::table('leads')->where('status', 'qualified')->count(),
            'visit_scheduled' => DB::table('leads')->where('status', 'visit_scheduled')->count(),
            'closed_won' => DB::table('leads')->where('status', 'closed_won')->count(),
        ];

        return $this->ok([
            'kpis' => [
                'revenue_30d' => $revenue,
                'total_leads' => $totalLeads,
                'closed_won' => $closedWon,
                'avg_daily_revenue' => $avgDaily,
            ],
            'funnel' => $funnel,
        ]);
    }

    // ========== PROPERTIES ==========
    public function properties(Request $request)
    {
        $q = DB::table('properties')
            ->leftJoin('cities', 'properties.city_id', '=', 'cities.id')
            ->leftJoin('localities', 'properties.locality_id', '=', 'localities.id')
            ->leftJoin('users', 'properties.owner_id', '=', 'users.id')
            ->select('properties.*', 'cities.name as city_name', 'localities.name as locality_name', 'users.name as owner_name')
            ->whereNull('properties.deleted_at');

        if ($s = $request->q) {
            $q->where(fn($w) => $w->where('properties.name', 'like', "%$s%")
                ->orWhere('properties.address_line', 'like', "%$s%"));
        }
        if ($filter = $request->filter) {
            if ($filter === 'unassigned') $q->whereNull('properties.owner_id');
            elseif ($filter === 'my') $q->where('properties.owner_id', $request->user->id);
            elseif ($filter === 'owners') $q->whereNotNull('properties.owner_id')->where('properties.owner_id', '!=', $request->user->id);
        }

        $items = $q->orderBy('properties.created_at', 'desc')->limit(100)->get();
        return $this->ok($items);
    }

    public function propertyShow($id)
    {
        $p = DB::table('properties')
            ->leftJoin('cities', 'properties.city_id', '=', 'cities.id')
            ->leftJoin('localities', 'properties.locality_id', '=', 'localities.id')
            ->leftJoin('users', 'properties.owner_id', '=', 'users.id')
            ->select('properties.*', 'cities.name as city_name', 'localities.name as locality_name', 'users.name as owner_name')
            ->where('properties.id', $id)
            ->first();
        if (!$p) return $this->notFound();

        $p->amenities = DB::table('property_amenities')
            ->join('amenities', 'property_amenities.amenity_id', '=', 'amenities.id')
            ->where('property_amenities.property_id', $id)
            ->select('amenities.*')
            ->get();

        $p->images = DB::table('property_images')->where('property_id', $id)->orderBy('display_order')->get();

        return $this->ok($p);
    }

    public function propertyStore(Request $request) { return $this->savePropertyData($request, null); }
    public function propertyUpdate(Request $request, $id) { return $this->savePropertyData($request, $id); }

    private function savePropertyData(Request $request, $id)
    {
        $data = $request->only([
            'name', 'city_id', 'locality_id', 'owner_id', 'gender', 'property_type',
            'description', 'rent_min', 'rent_max', 'security_deposit', 'food_included',
            'address_line', 'landmark', 'pincode', 'total_rooms', 'available_rooms',
            'google_map_link', 'latitude', 'longitude', 'rules',
        ]);

        // Handle new locality
        if ($request->locality_name && !$request->locality_id) {
            $localityId = DB::table('localities')->insertGetId([
                'name' => $request->locality_name,
                'slug' => Str::slug($request->locality_name),
                'city_id' => $request->city_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $data['locality_id'] = $localityId;
        }

        // Sharing options as JSON
        $sharing = array_filter([
            'single' => $request->sharing_single,
            'double' => $request->sharing_double,
            'triple' => $request->sharing_triple,
        ]);
        if ($sharing) $data['sharing_options'] = json_encode($sharing);

        $data['food_included'] = $request->boolean('food_included') ? 1 : 0;
        $data['updated_at'] = now();

        if ($id) {
            DB::table('properties')->where('id', $id)->update($data);
        } else {
            $data['slug'] = Str::slug($request->name) . '-' . Str::random(6);
            $data['created_at'] = now();
            $data['is_active'] = 1;
            $data['is_verified'] = 0;
            if (!isset($data['owner_id'])) $data['owner_id'] = $request->user->id;
            $id = DB::table('properties')->insertGetId($data);
        }

        // Cover image
        if ($request->hasFile('cover_image')) {
            $path = $request->file('cover_image')->store('properties/covers', 'public');
            DB::table('properties')->where('id', $id)->update(['cover_image' => $path]);
        }

        // Gallery images
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $img) {
                $path = $img->store('properties/gallery', 'public');
                DB::table('property_images')->insert([
                    'property_id' => $id,
                    'image_path' => $path,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Amenities
        if ($request->amenities) {
            DB::table('property_amenities')->where('property_id', $id)->delete();
            foreach ($request->amenities as $aid) {
                DB::table('property_amenities')->insert(['property_id' => $id, 'amenity_id' => $aid]);
            }
        }

        return $this->ok(['id' => $id, 'message' => 'Saved']);
    }

    public function propertyDelete($id)
    {
        DB::table('properties')->where('id', $id)->update(['deleted_at' => now()]);
        return $this->ok(['message' => 'Deleted']);
    }

    public function propertyVerify($id) { DB::table('properties')->where('id', $id)->update(['is_verified' => DB::raw('1 - is_verified')]); return $this->ok(['message' => 'Toggled']); }
    public function propertyFeature($id) { DB::table('properties')->where('id', $id)->update(['is_featured' => DB::raw('1 - is_featured')]); return $this->ok(['message' => 'Toggled']); }
    public function propertyPause($id) { DB::table('properties')->where('id', $id)->update(['is_active' => DB::raw('1 - is_active')]); return $this->ok(['message' => 'Toggled']); }

    public function propertyAssign(Request $request, $id)
    {
        DB::table('properties')->where('id', $id)->update(['owner_id' => $request->owner_id]);
        return $this->ok(['message' => 'Assigned']);
    }

    // ========== LEADS ==========
    public function leads(Request $request)
    {
        $q = DB::table('leads')
            ->leftJoin('properties', 'leads.property_id', '=', 'properties.id')
            ->leftJoin('users as telecallers', 'leads.assigned_to', '=', 'telecallers.id')
            ->select('leads.*', 'properties.name as property_name', 'telecallers.name as telecaller_name');

        if ($s = $request->q) {
            $q->where(fn($w) => $w->where('leads.name', 'like', "%$s%")
                ->orWhere('leads.phone', 'like', "%$s%")
                ->orWhere('leads.email', 'like', "%$s%"));
        }
        if ($status = $request->status) $q->where('leads.status', $status);

        $items = $q->orderBy('leads.created_at', 'desc')->limit(200)->get();
        return $this->ok($items);
    }

    public function leadShow($id)
    {
        $lead = DB::table('leads')->where('id', $id)->first();
        return $lead ? $this->ok($lead) : $this->notFound();
    }

    public function leadAssign(Request $request, $id)
    {
        DB::table('leads')->where('id', $id)->update([
            'assigned_to' => $request->telecaller_id,
            'status' => 'contacted',
            'updated_at' => now(),
        ]);
        return $this->ok(['message' => 'Assigned']);
    }

    // ========== USERS ==========
    public function users(Request $request)
    {
        $q = DB::table('users');
        if ($s = $request->q) {
            $q->where(fn($w) => $w->where('name', 'like', "%$s%")->orWhere('email', 'like', "%$s%")->orWhere('phone', 'like', "%$s%"));
        }
        if ($role = $request->role) $q->where('role', $role);
        return $this->ok($q->orderBy('created_at', 'desc')->limit(200)->get());
    }

    public function userStore(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string|unique:users,phone',
            'password' => 'required|min:6',
            'role' => 'required|string',
        ]);

        $id = DB::table('users')->insertGetId([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'is_active' => 1,
            'email_verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $this->ok(['id' => $id, 'message' => 'Created']);
    }

    public function userToggle($id) { DB::table('users')->where('id', $id)->update(['is_active' => DB::raw('1 - is_active')]); return $this->ok(['message' => 'Toggled']); }
    public function userChangeRole(Request $request, $id) { DB::table('users')->where('id', $id)->update(['role' => $request->role]); return $this->ok(['message' => 'Role updated']); }

    // ========== WALLETS ==========
    public function wallets()
    {
        $items = DB::table('users')
            ->leftJoin('credit_wallets', 'users.id', '=', 'credit_wallets.owner_id')
            ->where('users.role', 'owner')
            ->select('users.id', 'users.name', 'users.email', 'users.phone',
                'credit_wallets.balance', 'credit_wallets.lifetime_added', 'credit_wallets.lifetime_spent')
            ->orderBy('credit_wallets.balance', 'desc')
            ->limit(100)
            ->get();

        return $this->ok($items);
    }

    public function walletAdjust(Request $request, $ownerId)
    {
        $amount = (int) $request->amount;
        $type = $request->type; // 'add' or 'deduct'
        $reason = $request->reason;

        $wallet = DB::table('credit_wallets')->where('owner_id', $ownerId)->first();
        if (!$wallet) {
            DB::table('credit_wallets')->insert([
                'owner_id' => $ownerId, 'balance' => 0, 'lifetime_added' => 0, 'lifetime_spent' => 0,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            $wallet = (object) ['balance' => 0, 'lifetime_added' => 0, 'lifetime_spent' => 0];
        }

        if ($type === 'add') {
            DB::table('credit_wallets')->where('owner_id', $ownerId)->update([
                'balance' => $wallet->balance + $amount,
                'lifetime_added' => $wallet->lifetime_added + $amount,
                'updated_at' => now(),
            ]);
        } else {
            DB::table('credit_wallets')->where('owner_id', $ownerId)->update([
                'balance' => max(0, $wallet->balance - $amount),
                'lifetime_spent' => $wallet->lifetime_spent + $amount,
                'updated_at' => now(),
            ]);
        }

        DB::table('credit_transactions')->insert([
            'owner_id' => $ownerId,
            'amount' => $amount,
            'type' => $type === 'add' ? 'admin_credit' : 'admin_debit',
            'description' => $reason ?? 'Admin adjustment',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $this->ok(['message' => 'Wallet adjusted']);
    }

    // ========== PRICING ==========
    public function pricing()
    {
        $items = DB::table('lead_pricing_configs')->orderBy('id')->get();
        return $this->ok($items);
    }

    public function pricingUpdate(Request $request, $id)
    {
        DB::table('lead_pricing_configs')->where('id', $id)->update([
            'credit_cost' => (int) $request->credit_cost,
            'updated_at' => now(),
        ]);
        return $this->ok(['message' => 'Updated']);
    }

    // ========== PACKAGES ==========
    public function packages()
    {
        $packages = DB::table('credit_packages')->orderBy('price')->get();

        $stats = [
            'total_revenue' => DB::table('credit_transactions')->where('type', 'purchase')->sum('amount') ?? 0,
            'successful_payments' => DB::table('payment_orders')->where('status', 'paid')->count(),
            'failed_payments' => DB::table('payment_orders')->where('status', 'failed')->count(),
        ];

        return $this->ok([
            'packages' => $packages,
            'stats' => $stats,
        ]);
    }

    public function packageStore(Request $request)
    {
        $id = DB::table('credit_packages')->insertGetId([
            'name' => $request->name,
            'price' => (int) $request->price,
            'credits' => (int) $request->credits,
            'bonus_credits' => (int) $request->bonus_credits,
            'is_popular' => $request->boolean('is_popular') ? 1 : 0,
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        return $this->ok(['id' => $id]);
    }

    public function packageUpdate(Request $request, $id)
    {
        DB::table('credit_packages')->where('id', $id)->update([
            'name' => $request->name,
            'price' => (int) $request->price,
            'credits' => (int) $request->credits,
            'bonus_credits' => (int) $request->bonus_credits,
            'is_popular' => $request->boolean('is_popular') ? 1 : 0,
            'updated_at' => now(),
        ]);
        return $this->ok(['message' => 'Updated']);
    }

    public function packageDelete($id) { DB::table('credit_packages')->where('id', $id)->delete(); return $this->ok(['message' => 'Deleted']); }
    public function packageToggle($id) { DB::table('credit_packages')->where('id', $id)->update(['is_active' => DB::raw('1 - is_active')]); return $this->ok(['message' => 'Toggled']); }

    // ========== BLOGS ==========
    public function blogs() { return $this->ok(DB::table('blogs')->orderBy('created_at', 'desc')->limit(100)->get()); }
    public function blogShow($id) { return $this->ok(DB::table('blogs')->where('id', $id)->first()); }

    public function blogStore(Request $request) { return $this->saveBlog($request, null); }
    public function blogUpdate(Request $request, $id) { return $this->saveBlog($request, $id); }

    private function saveBlog(Request $request, $id)
    {
        $data = [
            'title' => $request->title,
            'excerpt' => $request->excerpt,
            'content' => $request->content,
            'meta_title' => $request->meta_title,
            'meta_description' => $request->meta_description,
            'is_published' => $request->boolean('is_published') ? 1 : 0,
            'updated_at' => now(),
        ];

        if ($request->hasFile('cover_image')) {
            $data['cover_image'] = $request->file('cover_image')->store('blogs', 'public');
        }

        if ($id) {
            DB::table('blogs')->where('id', $id)->update($data);
        } else {
            $data['slug'] = Str::slug($request->title) . '-' . Str::random(6);
            $data['author_id'] = $request->user->id;
            $data['created_at'] = now();
            $data['published_at'] = $data['is_published'] ? now() : null;
            $id = DB::table('blogs')->insertGetId($data);
        }

        return $this->ok(['id' => $id]);
    }

    public function blogDelete($id) { DB::table('blogs')->where('id', $id)->delete(); return $this->ok(['message' => 'Deleted']); }
    public function blogToggle($id)
    {
        $blog = DB::table('blogs')->where('id', $id)->first();
        $new = $blog->is_published ? 0 : 1;
        DB::table('blogs')->where('id', $id)->update([
            'is_published' => $new,
            'published_at' => $new ? now() : null,
        ]);
        return $this->ok(['message' => 'Toggled']);
    }

    // ========== FIELD TRACKER ==========
    public function fieldTracker()
    {
        $activeVisits = DB::table('visits')
            ->leftJoin('users as exec', 'visits.field_executive_id', '=', 'exec.id')
            ->leftJoin('properties', 'visits.property_id', '=', 'properties.id')
            ->leftJoin('leads', 'visits.lead_id', '=', 'leads.id')
            ->leftJoin('localities', 'properties.locality_id', '=', 'localities.id')
            ->where('visits.status', 'in_progress')
            ->select('visits.*',
                'exec.name as exec_name', 'exec.phone as exec_phone',
                'properties.name as property_name',
                'leads.name as lead_name', 'leads.phone as lead_phone',
                'localities.name as locality_name')
            ->get();

        $execs = DB::table('users')
            ->where('role', 'field_executive')
            ->where('is_active', 1)
            ->select('id', 'name', 'phone')
            ->get()
            ->map(function ($e) {
                $today = now()->toDateString();
                $e->today_total = DB::table('visits')->where('field_executive_id', $e->id)->whereDate('scheduled_at', $today)->count();
                $e->today_done = DB::table('visits')->where('field_executive_id', $e->id)->whereDate('completed_at', $today)->count();
                $e->today_closed = DB::table('visits')->where('field_executive_id', $e->id)
                    ->whereDate('completed_at', $today)
                    ->whereExists(function ($q) {
                        $q->select(DB::raw(1))->from('leads')->whereColumn('leads.id', 'visits.lead_id')->where('status', 'closed_won');
                    })->count();
                return $e;
            });

        return $this->ok([
            'active_visits' => $activeVisits,
            'execs' => $execs,
        ]);
    }

    // ========== PG OVERVIEW ==========
    public function pgOverview()
    {
        $month = now()->startOfMonth();

        $stats = [
            'total_tenants' => DB::table('tenants')->count(),
            'active_tenants' => DB::table('tenants')->where('status', 'active')->count(),
            'pending_kyc' => DB::table('tenants')->where('kyc_status', 'submitted')->count(),
            'urgent_complaints' => DB::table('complaints')->where('priority', 'urgent')->whereIn('status', ['open', 'in_progress'])->count(),
            'open_complaints' => DB::table('complaints')->whereIn('status', ['open', 'in_progress'])->count(),
            'resolved_month' => DB::table('complaints')->where('status', 'resolved')->where('resolved_at', '>=', $month)->count(),
            'collected_month' => DB::table('rent_bills')->where('status', 'paid')->where('paid_at', '>=', $month)->sum('total_amount') ?? 0,
            'pending_dues' => DB::table('rent_bills')->whereIn('status', ['pending', 'partial', 'overdue'])->sum('due_amount') ?? 0,
            'overdue_bills' => DB::table('rent_bills')->where('status', 'overdue')->count(),
        ];

        $topOwners = DB::table('users')
            ->leftJoin('tenants', 'users.id', '=', 'tenants.owner_id')
            ->where('users.role', 'owner')
            ->select('users.id', 'users.name', 'users.email', 'users.phone', DB::raw('count(tenants.id) as tenants_count'))
            ->groupBy('users.id', 'users.name', 'users.email', 'users.phone')
            ->orderBy('tenants_count', 'desc')
            ->limit(10)
            ->get();

        $urgentComplaints = DB::table('complaints')
            ->leftJoin('tenants', 'complaints.tenant_id', '=', 'tenants.id')
            ->leftJoin('users as owners', 'complaints.owner_id', '=', 'owners.id')
            ->where('complaints.priority', 'urgent')
            ->whereIn('complaints.status', ['open', 'in_progress'])
            ->select('complaints.*', 'tenants.name as tenant_name', 'owners.name as owner_name')
            ->orderBy('complaints.created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($c) {
                $c->tenant = (object) ['name' => $c->tenant_name];
                $c->owner = (object) ['name' => $c->owner_name];
                return $c;
            });

        $recentTenants = DB::table('tenants')
            ->leftJoin('properties', 'tenants.property_id', '=', 'properties.id')
            ->leftJoin('users as owners', 'tenants.owner_id', '=', 'owners.id')
            ->select('tenants.*', 'properties.name as property_name', 'owners.name as owner_name')
            ->orderBy('tenants.created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($t) {
                $t->property = (object) ['name' => $t->property_name];
                $t->owner = (object) ['name' => $t->owner_name];
                return $t;
            });

        $recentBills = DB::table('rent_bills')
            ->leftJoin('tenants', 'rent_bills.tenant_id', '=', 'tenants.id')
            ->leftJoin('users as owners', 'rent_bills.owner_id', '=', 'owners.id')
            ->select('rent_bills.*', 'tenants.name as tenant_name', 'owners.name as owner_name')
            ->orderBy('rent_bills.created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($b) {
                $b->tenant = (object) ['name' => $b->tenant_name];
                $b->owner = (object) ['name' => $b->owner_name];
                $b->month_label = $b->month ?? '';
                return $b;
            });

        return $this->ok([
            'stats' => $stats,
            'top_owners' => $topOwners,
            'urgent_complaints' => $urgentComplaints,
            'recent_tenants' => $recentTenants,
            'recent_bills' => $recentBills,
        ]);
    }

    // ========== ROOMS / TENANTS / AGREEMENTS / RENT / COMPLAINTS ==========
    public function rooms(Request $request)
    {
        $rooms = DB::table('rooms')
            ->leftJoin('properties', 'rooms.property_id', '=', 'properties.id')
            ->leftJoin('users as owners', 'properties.owner_id', '=', 'owners.id')
            ->select('rooms.*', 'properties.name as property_name', 'owners.name as owner_name', 'owners.id as owner_id')
            ->when($request->q, fn($q) => $q->where('rooms.room_number', 'like', "%$request->q%"))
            ->when($request->owner_id, fn($q) => $q->where('properties.owner_id', $request->owner_id))
            ->when($request->property_id, fn($q) => $q->where('rooms.property_id', $request->property_id))
            ->orderBy('rooms.id', 'desc')
            ->limit(200)
            ->get();

        foreach ($rooms as $r) {
            $r->property = (object) ['name' => $r->property_name];
            $r->owner = (object) ['name' => $r->owner_name];
            $r->beds = DB::table('beds')->where('room_id', $r->id)->get();
        }

        $stats = [
            'total_rooms' => DB::table('rooms')->count(),
            'total_beds' => DB::table('beds')->count(),
            'occupied' => DB::table('beds')->where('status', 'occupied')->count(),
            'vacant' => DB::table('beds')->where('status', 'vacant')->count(),
        ];

        return $this->ok(['rooms' => $rooms, 'stats' => $stats]);
    }

    public function tenants(Request $request)
    {
        $q = DB::table('tenants')
            ->leftJoin('properties', 'tenants.property_id', '=', 'properties.id')
            ->leftJoin('users as owners', 'tenants.owner_id', '=', 'owners.id')
            ->select('tenants.*', 'properties.name as property_name', 'owners.name as owner_name');

        if ($s = $request->q) $q->where(fn($w) => $w->where('tenants.name', 'like', "%$s%")->orWhere('tenants.phone', 'like', "%$s%")->orWhere('tenants.email', 'like', "%$s%"));
        if ($request->owner_id) $q->where('tenants.owner_id', $request->owner_id);
        if ($request->status) $q->where('tenants.status', $request->status);
        if ($request->kyc) $q->where('tenants.kyc_status', $request->kyc);

        $tenants = $q->orderBy('tenants.created_at', 'desc')->limit(200)->get();
        foreach ($tenants as $t) {
            $t->property = (object) ['name' => $t->property_name];
            $t->owner = (object) ['name' => $t->owner_name];
        }

        $stats = [
            'total' => DB::table('tenants')->count(),
            'active' => DB::table('tenants')->where('status', 'active')->count(),
            'pending_kyc' => DB::table('tenants')->where('kyc_status', 'submitted')->count(),
            'approved_kyc' => DB::table('tenants')->where('kyc_status', 'approved')->count(),
        ];

        return $this->ok(['tenants' => $tenants, 'stats' => $stats]);
    }

    public function tenantShow($id)
    {
        $t = DB::table('tenants')
            ->leftJoin('properties', 'tenants.property_id', '=', 'properties.id')
            ->leftJoin('users as owners', 'tenants.owner_id', '=', 'owners.id')
            ->where('tenants.id', $id)
            ->select('tenants.*', 'properties.name as property_name', 'owners.name as owner_name')
            ->first();
        return $t ? $this->ok($t) : $this->notFound();
    }

    public function agreements(Request $request)
    {
        $q = DB::table('rent_agreements')
            ->leftJoin('tenants', 'rent_agreements.tenant_id', '=', 'tenants.id')
            ->leftJoin('properties', 'rent_agreements.property_id', '=', 'properties.id')
            ->leftJoin('users as owners', 'rent_agreements.owner_id', '=', 'owners.id')
            ->select('rent_agreements.*', 'tenants.name as tenant_name', 'properties.name as property_name', 'owners.name as owner_name');

        if ($s = $request->q) $q->where(fn($w) => $w->where('rent_agreements.agreement_number', 'like', "%$s%")->orWhere('tenants.name', 'like', "%$s%"));
        if ($request->owner_id) $q->where('rent_agreements.owner_id', $request->owner_id);
        if ($request->status) $q->where('rent_agreements.status', $request->status);

        $items = $q->orderBy('rent_agreements.created_at', 'desc')->limit(200)->get();
        foreach ($items as $a) {
            $a->tenant = (object) ['name' => $a->tenant_name];
            $a->property = (object) ['name' => $a->property_name];
            $a->owner = (object) ['name' => $a->owner_name];
        }

        $stats = [
            'total' => DB::table('rent_agreements')->count(),
            'active' => DB::table('rent_agreements')->where('status', 'active')->count(),
            'expiring' => DB::table('rent_agreements')->where('status', 'active')->where('end_date', '<=', now()->addDays(30))->count(),
            'draft' => DB::table('rent_agreements')->where('status', 'draft')->count(),
        ];

        return $this->ok(['agreements' => $items, 'stats' => $stats]);
    }

    public function agreementShow($id)
    {
        $a = DB::table('rent_agreements')->where('id', $id)->first();
        return $a ? $this->ok($a) : $this->notFound();
    }

    public function rent(Request $request)
    {
        $q = DB::table('rent_bills')
            ->leftJoin('tenants', 'rent_bills.tenant_id', '=', 'tenants.id')
            ->leftJoin('users as owners', 'rent_bills.owner_id', '=', 'owners.id')
            ->select('rent_bills.*', 'tenants.name as tenant_name', 'tenants.phone as tenant_phone', 'owners.name as owner_name');

        if ($s = $request->q) $q->where(fn($w) => $w->where('rent_bills.bill_number', 'like', "%$s%")->orWhere('tenants.name', 'like', "%$s%"));
        if ($request->owner_id) $q->where('rent_bills.owner_id', $request->owner_id);
        if ($request->month) $q->where('rent_bills.month', $request->month);
        if ($request->status) $q->where('rent_bills.status', $request->status);

        $bills = $q->orderBy('rent_bills.created_at', 'desc')->limit(200)->get();
        foreach ($bills as $b) {
            $b->tenant = (object) ['name' => $b->tenant_name, 'phone' => $b->tenant_phone];
            $b->owner = (object) ['name' => $b->owner_name];
            $b->month_label = $b->month ?? '';
        }

        $month = now()->startOfMonth();
        $stats = [
            'total_collected_month' => DB::table('rent_bills')->where('status', 'paid')->where('paid_at', '>=', $month)->sum('total_amount') ?? 0,
            'total_pending' => DB::table('rent_bills')->whereIn('status', ['pending', 'partial', 'overdue'])->sum('due_amount') ?? 0,
            'overdue_count' => DB::table('rent_bills')->where('status', 'overdue')->count(),
            'total_bills' => DB::table('rent_bills')->count(),
        ];

        return $this->ok(['bills' => $bills, 'stats' => $stats]);
    }

    public function rentShow($id) { return $this->ok(DB::table('rent_bills')->where('id', $id)->first()); }

    public function complaints(Request $request)
    {
        $q = DB::table('complaints')
            ->leftJoin('tenants', 'complaints.tenant_id', '=', 'tenants.id')
            ->leftJoin('users as owners', 'complaints.owner_id', '=', 'owners.id')
            ->select('complaints.*', 'tenants.name as tenant_name', 'owners.name as owner_name');

        if ($s = $request->q) $q->where(fn($w) => $w->where('complaints.title', 'like', "%$s%")->orWhere('complaints.ticket_number', 'like', "%$s%"));
        if ($request->owner_id) $q->where('complaints.owner_id', $request->owner_id);
        if ($request->status) $q->where('complaints.status', $request->status);
        if ($request->priority) $q->where('complaints.priority', $request->priority);

        $items = $q->orderBy('complaints.created_at', 'desc')->limit(200)->get();
        foreach ($items as $c) {
            $c->tenant = (object) ['name' => $c->tenant_name];
            $c->owner = (object) ['name' => $c->owner_name];
        }

        $month = now()->startOfMonth();
        $stats = [
            'open' => DB::table('complaints')->where('status', 'open')->count(),
            'urgent' => DB::table('complaints')->where('priority', 'urgent')->whereIn('status', ['open', 'in_progress'])->count(),
            'in_progress' => DB::table('complaints')->where('status', 'in_progress')->count(),
            'resolved_month' => DB::table('complaints')->where('status', 'resolved')->where('resolved_at', '>=', $month)->count(),
        ];

        return $this->ok(['complaints' => $items, 'stats' => $stats]);
    }

    public function complaintShow($id) { return $this->ok(DB::table('complaints')->where('id', $id)->first()); }

    // ========== HELPERS ==========
    private function ok($data) { return response()->json(['success' => true, 'data' => $data]); }
    private function notFound() { return response()->json(['success' => false, 'message' => 'Not found'], 404); }
}
