<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Owner\PgManagerController;
use App\Models\Property;
use App\Models\User;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

/**
 * An owner creates and runs their PG Managers from the app. Same rules as the
 * website's PG Managers page. The route group refuses PG Managers themselves
 * (see ManagerApiAccess), but every method also checks the owner again.
 */
class ManagerAdminController extends Controller
{
    private function ok($data = null)
    {
        return response()->json(['success' => true, 'data' => $data]);
    }

    private function fail(string $message, int $status = 422)
    {
        return response()->json(['success' => false, 'message' => $message], $status);
    }

    private function owner(Request $request): User
    {
        $u = $request->user();
        abort_unless($u && $u->role === 'owner', 403);
        return $u;
    }

    private function mine(Request $request, $id): ?User
    {
        $owner = $this->owner($request);
        return User::where('id', $id)->where('role', 'pg_manager')->where('owner_id', $owner->id)->first();
    }

    private function endSessions(User $m): void
    {
        DB::table('api_sessions')->where('user_id', $m->id)->whereNull('revoked_at')
            ->update(['revoked_at' => now(), 'revoked_reason' => 'manager_changed']);
    }

    private function shape(User $m): array
    {
        $ids = DB::table('property_managers')->where('manager_id', $m->id)->pluck('property_id');
        return [
            'id' => $m->id,
            'name' => $m->name,
            'email' => $m->email,
            'phone' => $m->phone,
            'is_active' => (bool) $m->is_active,
            'features' => json_decode($m->permissions ?? '[]', true) ?: [],
            'property_ids' => $ids->values(),
            'properties' => Property::whereIn('id', $ids)->orderBy('name')->get(['id', 'name']),
            'credit_balance' => (int) (DB::table('wallets')->where('user_id', $m->id)->value('balance') ?? 0),
        ];
    }

    public function index(Request $request)
    {
        $owner = $this->owner($request);
        $managers = User::where('role', 'pg_manager')->where('owner_id', $owner->id)->orderBy('name')->get()
            ->map(fn ($m) => $this->shape($m))->values();

        return $this->ok([
            'managers' => $managers,
            'available_features' => PgManagerController::AVAILABLE_FEATURES,
            'owner_balance' => (int) (DB::table('wallets')->where('user_id', $owner->id)->value('balance') ?? 0),
            'properties' => Property::where('owner_id', $owner->id)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $owner = $this->owner($request);
        $features = array_keys(PgManagerController::AVAILABLE_FEATURES);

        $data = $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string|max:15',
            'password' => 'required|string|min:8|regex:/[A-Za-z]/|regex:/[0-9]/',
            'property_ids' => 'required|array|min:1',
            'property_ids.*' => 'integer',
            'features' => 'nullable|array',
            'features.*' => Rule::in($features),
        ]);

        $last10 = substr(preg_replace('/\D/', '', $data['phone']), -10);
        if (strlen($last10) !== 10) {
            return $this->fail('Enter a valid 10 digit mobile number.');
        }
        $taken = User::whereRaw("RIGHT(REPLACE(REPLACE(REPLACE(phone, '+', ''), ' ', ''), '-', ''), 10) = ?", [$last10])->exists();
        if ($taken) {
            return $this->fail('Someone already uses this mobile number.');
        }

        $propertyIds = Property::where('owner_id', $owner->id)->whereIn('id', $data['property_ids'])->pluck('id');
        if ($propertyIds->isEmpty()) {
            return $this->fail('Choose at least one of your properties.');
        }

        $manager = DB::transaction(function () use ($data, $owner, $propertyIds, $last10) {
            $m = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $last10,
                'password' => Hash::make($data['password']),
                'role' => 'pg_manager',
                'owner_id' => $owner->id,
                'permissions' => json_encode(array_values($data['features'] ?? [])),
                'is_active' => 1,
            ]);
            foreach ($propertyIds as $pid) {
                DB::table('property_managers')->insert([
                    'manager_id' => $m->id, 'property_id' => $pid,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
            return $m;
        });

        return $this->ok($this->shape($manager));
    }

    public function update(Request $request, $id)
    {
        $owner = $this->owner($request);
        $manager = $this->mine($request, $id);
        if (!$manager) return $this->fail('Manager not found.', 404);

        $data = $request->validate([
            'property_ids' => 'array',
            'property_ids.*' => 'integer',
            'features' => 'nullable|array',
            'features.*' => Rule::in(array_keys(PgManagerController::AVAILABLE_FEATURES)),
            'password' => 'nullable|string|min:8|regex:/[A-Za-z]/|regex:/[0-9]/',
        ]);

        $propertyIds = Property::where('owner_id', $owner->id)->whereIn('id', $data['property_ids'] ?? [])->pluck('id');
        if ($propertyIds->isEmpty()) {
            return $this->fail('A manager needs at least one property.');
        }

        DB::transaction(function () use ($manager, $propertyIds, $data) {
            DB::table('property_managers')->where('manager_id', $manager->id)->delete();
            foreach ($propertyIds as $pid) {
                DB::table('property_managers')->insert([
                    'manager_id' => $manager->id, 'property_id' => $pid,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
            $changes = ['permissions' => json_encode(array_values($data['features'] ?? []))];
            if (!empty($data['password'])) {
                $changes['password'] = Hash::make($data['password']);
            }
            $manager->update($changes);
        });

        // a new password ends the manager's open sessions
        if (!empty($data['password'])) {
            $this->endSessions($manager);
        }

        return $this->ok($this->shape($manager->fresh()));
    }

    public function toggle(Request $request, $id)
    {
        $manager = $this->mine($request, $id);
        if (!$manager) return $this->fail('Manager not found.', 404);

        $manager->is_active = !$manager->is_active;
        $manager->save();
        if (!$manager->is_active) {
            $this->endSessions($manager);
        }

        return $this->ok($this->shape($manager));
    }

    public function credits(Request $request, $id, WalletService $wallets)
    {
        $owner = $this->owner($request);
        $manager = $this->mine($request, $id);
        if (!$manager) return $this->fail('Manager not found.', 404);

        $data = $request->validate([
            'amount' => 'required|integer|min:1|max:100000',
            'direction' => 'required|in:give,take_back',
        ]);

        try {
            if ($data['direction'] === 'give') {
                $wallets->transferCredits($owner, $manager, (int) $data['amount'], $owner);
            } else {
                $wallets->transferCredits($manager, $owner, (int) $data['amount'], $owner);
            }
        } catch (\RuntimeException $e) {
            return $this->fail($e->getMessage());
        }

        return $this->ok([
            'manager' => $this->shape($manager),
            'owner_balance' => (int) (DB::table('wallets')->where('user_id', $owner->id)->value('balance') ?? 0),
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $manager = $this->mine($request, $id);
        if (!$manager) return $this->fail('Manager not found.', 404);

        // credits the manager still holds go back to the owner
        $left = (int) (DB::table('wallets')->where('user_id', $manager->id)->value('balance') ?? 0);
        if ($left > 0) {
            try {
                app(WalletService::class)->transferCredits($manager, $request->user(), $left, $request->user());
            } catch (RuntimeException $e) {
                return $this->fail($e->getMessage());
            }
        }

        $this->endSessions($manager);
        DB::table('property_managers')->where('manager_id', $manager->id)->delete();
        $manager->delete();

        return $this->ok(['message' => 'Manager removed.']);
    }
}
