<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadReport extends Model
{
    protected $fillable = [
        'lead_id', 'owner_id', 'reported_by', 'paid_by', 'credits_spent', 'reason', 'note',
        'status', 'admin_note', 'reviewed_by', 'reviewed_at', 'credits_refunded',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    /** reason key => what the owner sees */
    public const REASONS = [
        'wrong_number' => 'Wrong, switched-off or unreachable number',
        'not_interested' => 'Person says they never enquired / not interested',
        'not_looking' => 'Not looking for a PG, or looking in a different area',
        'fake' => 'Fake or test enquiry',
        'other' => 'Other (write it below)',
    ];

    /** Reasons for which an approved report also removes the lead from everyone's list. */
    public const JUNK_REASONS = ['wrong_number', 'fake'];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function reasonLabel(): string
    {
        return self::REASONS[$this->reason] ?? $this->reason;
    }

    /** ids of the leads this owner (team) unlocked recently enough to still report. */
    public static function reportableIdsFor(User $user): array
    {
        $teamOwnerId = $user->role === 'pg_manager' ? $user->owner_id : $user->id;
        $hours = (int) config('leads.report_hours', 72);

        return \Illuminate\Support\Facades\DB::table('lead_unlocks')
            ->where('owner_id', $teamOwnerId)
            ->where('created_at', '>=', now()->subHours($hours))
            ->pluck('lead_id')->all();
    }

    /** lead id => status, for the leads one owner (team) has reported. */
    public static function statusesFor(User $user): array
    {
        $teamOwnerId = $user->role === 'pg_manager' ? $user->owner_id : $user->id;

        return static::where('owner_id', $teamOwnerId)->pluck('status', 'lead_id')->all();
    }
}
