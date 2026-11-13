<?php

namespace App\Services;

use App\Models\PropertyNotice;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Notices from an owner or PG Manager to the people living in a PG. They show in the tenant app
 * and each tenant gets a push notification. Used by the website and the owner API alike.
 */
class NoticeService
{
    /** Rules for the incoming form / JSON. */
    public static function rules(): array
    {
        return [
            'property_id' => 'nullable|integer',
            'title' => 'required|string|min:3|max:150',
            'body' => 'required|string|min:3|max:3000',
            'kind' => 'nullable|in:' . implode(',', PropertyNotice::KINDS),
            'pinned' => 'nullable|boolean',
            'expires_at' => 'nullable|date|after:now',
        ];
    }

    public function ownerIdOf(User $actor): int
    {
        return $actor->role === 'pg_manager' ? (int) $actor->owner_id : (int) $actor->id;
    }

    /** The notices this person may see and manage: those for the PGs they run. */
    public function listFor(User $actor, int $limit = 100)
    {
        $mine = $actor->getManagedPropertyIds()->map(fn ($i) => (int) $i)->all();
        $ownerId = $this->ownerIdOf($actor);

        return PropertyNotice::with('property:id,name')
            ->where('owner_id', $ownerId)
            ->where(function ($q) use ($mine, $actor) {
                $q->whereIn('property_id', $mine);
                // "All my PGs" notices belong to the owner; a manager sees only their own PGs' ones
                if ($actor->role !== 'pg_manager') {
                    $q->orWhereNull('property_id');
                }
            })
            ->orderByDesc('pinned')->orderByDesc('created_at')
            ->limit($limit)->get();
    }

    /**
     * @param  array  $data  validated by self::rules()
     * @throws ValidationException when the PG is not one this person runs
     */
    public function post(User $actor, array $data): PropertyNotice
    {
        $mine = $actor->getManagedPropertyIds()->map(fn ($i) => (int) $i)->all();
        $propertyId = !empty($data['property_id']) ? (int) $data['property_id'] : null;

        if ($propertyId !== null && !in_array($propertyId, $mine, true)) {
            throw ValidationException::withMessages(['property_id' => 'Choose one of your own PGs.']);
        }
        // A manager must name a PG; "all PGs" is the owner's call.
        if ($propertyId === null && $actor->role === 'pg_manager') {
            throw ValidationException::withMessages(['property_id' => 'Choose the PG this notice is for.']);
        }

        $notice = PropertyNotice::create([
            'owner_id' => $this->ownerIdOf($actor),
            'property_id' => $propertyId,
            'created_by' => $actor->id,
            'title' => trim($data['title']),
            'body' => trim($data['body']),
            'kind' => $data['kind'] ?? 'notice',
            'pinned' => (bool) ($data['pinned'] ?? false),
            'expires_at' => $data['expires_at'] ?? null,
        ]);

        $this->push($notice);

        return $notice;
    }

    public function delete(User $actor, int $id): bool
    {
        $notice = $this->listFor($actor, 1000)->firstWhere('id', $id);
        if (!$notice) {
            return false;
        }
        $notice->delete();

        return true;
    }

    /** Push to every active tenant of the PG (or of all the owner's PGs). Never blocks posting. */
    private function push(PropertyNotice $notice): void
    {
        try {
            $fcm = app(FcmService::class);
            if (!$fcm->isConfigured()) {
                return;
            }
            $tenants = Tenant::where('owner_id', $notice->owner_id)->where('status', 'active')->whereNotNull('user_id')
                ->when($notice->property_id, fn ($q) => $q->where('property_id', $notice->property_id))
                ->with('user')->get();

            foreach ($tenants as $t) {
                if ($t->user) {
                    $fcm->sendToUser($t->user, ($notice->kind === 'urgent' ? 'Urgent: ' : '') . $notice->title, mb_strimwidth($notice->body, 0, 120, '...'), ['type' => 'notice', 'notice_id' => $notice->id]);
                }
            }
        } catch (\Throwable $e) {
            \Log::warning('Notice push failed: ' . $e->getMessage());
        }
    }
}
