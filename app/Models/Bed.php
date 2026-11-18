<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Bed extends Model
{
    use SoftDeletes;

    /**
     * The database keeps one bed number per room and a bed in the Trash still holds its number. Whoever makes
     * (or renames to) a number that only a Trash bed has gets it: the Trash bed is renamed "A [del 5]".
     *
     * @return bool true when a Trash bed held the number and was renamed
     */
    public static function releaseTrashedNumber(int $roomId, string $number, ?int $exceptBedId = null): bool
    {
        $trashed = \Illuminate\Support\Facades\DB::table('beds')
            ->where('room_id', $roomId)->where('bed_number', $number)
            ->whereNotNull('deleted_at')
            ->when($exceptBedId, fn ($q) => $q->where('id', '!=', $exceptBedId))
            ->get(['id']);

        foreach ($trashed as $row) {
            // the column holds 20 characters: keep the room for " [del 123]" and cut the number to fit
            $suffix = ' [del ' . $row->id . ']';
            \Illuminate\Support\Facades\DB::table('beds')->where('id', $row->id)->update([
                'bed_number' => mb_substr($number, 0, max(1, 20 - strlen($suffix))) . $suffix,
                'updated_at' => now(),
            ]);
        }

        return $trashed->isNotEmpty();
    }

    protected $fillable = [
        'room_id', 'property_id', 'owner_id',
        'bed_number', 'bed_type',
        'monthly_rent', 'status',
        'tenant_id', 'occupied_since', 'notes',
    ];

    protected $casts = [
        'monthly_rent' => 'decimal:2',
        'occupied_since' => 'date',
    ];

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'occupied' => 'rose',
            'vacant' => 'emerald',
            'reserved' => 'amber',
            'maintenance' => 'ink',
            default => 'ink',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'occupied' => '🛏️ Occupied',
            'vacant' => '✅ Vacant',
            'reserved' => '⏳ Reserved',
            'maintenance' => '🔧 Maintenance',
            default => $this->status,
        };
    }
}