<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lead extends Model
{
    use HasFactory, SoftDeletes;

protected $fillable = [
        'property_id', 'assigned_telecaller_id', 'assigned_field_executive_id',
        'created_by_user_id',
        'name', 'phone', 'email',
        'preferred_locality', 'preferred_city', 'preferred_gender',
        'budget_min', 'budget_max', 'move_in_date',
        'message', 'source', 'inquiry_type', 'status',
        'telecaller_notes', 'last_contacted_at', 'next_follow_up_at', 'stale_notified_at',
        'lead_type', 'is_locked', 'locked_by_user_id',
        'call_status', 'called_at', 'call_attempts', 'call_notes', 'rejection_reason',
        'user_id','credit_cost', 'converted_tenant_id',
    ];
    

    protected $casts = [
        'move_in_date' => 'date',
        'last_contacted_at' => 'datetime',
        'next_follow_up_at' => 'datetime',
         'called_at' => 'datetime',
        'edit_locked_at' => 'datetime',
        'budget_min' => 'float',
        'budget_max' => 'float',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function telecaller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_telecaller_id');
    }

    public function fieldExecutive(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_field_executive_id');
    }

    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class);
    }

    public function assignmentLogs(): HasMany
    {
        return $this->hasMany(LeadAssignmentLog::class)->latest('created_at');
    }

    /**
     * Auto-log every assigned_telecaller_id change — covers admin manual
     * assign, round-robin auto-assign, and manual-lead self-assign, since
     * all of them go through Eloquent's update()/save() eventually.
     */
    protected static function booted(): void
    {
        static::updating(function (Lead $lead) {
            if ($lead->isDirty('assigned_telecaller_id')) {
                LeadAssignmentLog::create([
                    'lead_id' => $lead->id,
                    'from_user_id' => $lead->getOriginal('assigned_telecaller_id'),
                    'to_user_id' => $lead->assigned_telecaller_id,
                    'changed_by_user_id' => auth()->id(),
                    'created_at' => now(),
                ]);
            }

            // Log every status / call_status change so admin can see, in
            // real time, exactly what a telecaller updated on a lead —
            // not just aggregated daily counts.
            foreach (['status', 'call_status', 'lead_type'] as $field) {
                if ($lead->isDirty($field)) {
                    \App\Models\LeadStatusLog::create([
                        'lead_id' => $lead->id,
                        'changed_by_user_id' => auth()->id(),
                        'field' => $field,
                        'old_value' => $lead->getOriginal($field),
                        'new_value' => $lead->{$field},
                        'created_at' => now(),
                    ]);
                }
            }
        });

        static::created(function (Lead $lead) {
            if ($lead->assigned_telecaller_id) {
                LeadAssignmentLog::create([
                    'lead_id' => $lead->id,
                    'from_user_id' => null,
                    'to_user_id' => $lead->assigned_telecaller_id,
                    'changed_by_user_id' => auth()->id(),
                    'created_at' => now(),
                ]);
            }
        });
    }

    public function statusBadge(): string
    {
        return match ($this->status) {
            'new' => 'bg-sky-100 text-sky-800',
            'contacted' => 'bg-blue-100 text-blue-800',
            'interested' => 'bg-emerald-100 text-emerald-800',
            'follow_up' => 'bg-amber-100 text-amber-800',
            'visit_scheduled' => 'bg-violet-100 text-violet-800',
            'visit_done' => 'bg-indigo-100 text-indigo-800',
            'closed_won' => 'bg-green-200 text-green-900',
            'closed_lost' => 'bg-rose-100 text-rose-800',
            'junk' => 'bg-slate-200 text-slate-700',
            default => 'bg-slate-100 text-slate-700',
        };
    }


    public function unlocks()
    {
        return $this->hasMany(LeadUnlock::class);
    }

    public function lockedBy()
    {
        return $this->belongsTo(User::class, 'locked_by_user_id');
    }

    /**
     * Check if this lead is unlocked by a specific owner.
     */
    public function isUnlockedBy(int $userId): bool
    {
        return $this->unlocks()->where('user_id', $userId)->exists();
    }

    /**
     * Mask phone for display (show first 2 + last 2 digits only).
     */
    public function getMaskedPhoneAttribute(): string
    {
        $phone = $this->phone;
        if (strlen($phone) < 6) return str_repeat('X', strlen($phone));
        return substr($phone, 0, 2) . str_repeat('X', strlen($phone) - 4) . substr($phone, -2);
    }

    /**
     * Mask email for display.
     */
    public function getMaskedEmailAttribute(): ?string
    {
        if (!$this->email) return null;
        [$user, $domain] = explode('@', $this->email);
        $maskedUser = strlen($user) > 2
            ? substr($user, 0, 2) . str_repeat('X', strlen($user) - 2)
            : $user;
        return $maskedUser . '@' . $domain;
    }


    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * Which city/locality this lead is actually about — falls back through
     * whichever info is available, so it's never blank on the lead cards
     * even when the lead didn't fill in preferred_city/locality directly.
     */
    public function getDisplayLocationAttribute(): string
    {
        if ($this->property) {
            return trim(($this->property->locality?->name ?? '') . ', ' . ($this->property->city?->name ?? ''), ', ');
        }
        if ($this->preferred_locality || $this->preferred_city) {
            // preferred_locality is free-typed text (from ad forms, chat
            // widgets) and often already contains the city name, e.g.
            // "Sector 126, Noida" — appending preferred_city ("Noida")
            // again then reads as "Sector 126, Noida, Noida".
            if ($this->preferred_locality && $this->preferred_city
                && stripos($this->preferred_locality, $this->preferred_city) !== false) {
                return $this->preferred_locality;
            }
            return trim(($this->preferred_locality ?? '') . ', ' . ($this->preferred_city ?? ''), ', ');
        }
        return '—';
    }

    public function inquiryTypeBadge(): array
    {
        return match ($this->inquiry_type) {
            'owner' => ['label' => '🏠 Owner Lead', 'class' => 'bg-violet-100 text-violet-800'],
            'tenant' => ['label' => '🧳 Tenant Lead', 'class' => 'bg-sky-100 text-sky-800'],
            default => ['label' => '❓ Unknown', 'class' => 'bg-slate-100 text-slate-600'],
        };
    }

    public function getSourceLabelAttribute(): string
    {
        return match ($this->source) {
            'website' => 'Website',
            'whatsapp' => 'WhatsApp',
            'meta_ads' => 'Meta Ads',
            'google_ads' => 'Google Ads',
            'referral' => 'Referral',
            'walk_in' => 'Walk-in',
            'offline_campaign' => 'Offline Campaign',
            'tele_inbound' => 'Inbound Call',
            'manual' => 'Manually Added',
            default => ucfirst(str_replace('_', ' ', $this->source ?? 'unknown')),
        };
    }

    /**
     * Message text safe to show an owner — hides the technical "Auto-imported
     * from meta_ads/google_ads (ref: ...)" notes so owners can't see which
     * ad platform a lead came from. Admin/telecaller views can keep using
     * $lead->message directly; this is only for owner-facing screens.
     */
    public function getOwnerSafeMessageAttribute(): ?string
    {
        if (empty($this->message)) {
            return null;
        }
        if (preg_match('/^(Auto-imported from|Backfilled from)/i', $this->message)) {
            return null;
        }

        $text = $this->message;

        // Strip email addresses — a lead's free-text message must never
        // leak contact info before the owner actually pays to unlock it.
        // Loose on purpose: people type "demo @gmail" or skip the .com,
        // so we match anything shaped like "word @ word[.tld]", not just
        // a strictly well-formed address.
        $text = preg_replace('/[\w.+-]*\s?@\s?[\w-]+(\.[a-zA-Z]{2,})?/', '[email hidden]', $text);

        // Strip phone numbers — Indian mobiles always start with 6-9 and
        // run exactly 10 digits, optionally split "98765 43210" or
        // prefixed "+91-9876543210". Anchored to that shape (not just
        // "any long run of digits") so a budget range like "8000-10000"
        // is left alone.
        $text = preg_replace('/(?:\+?91[\s-]?)?[6-9]\d{4}[\s-]?\d{5}\b/', '[phone hidden]', $text);

        return trim($text);
    }

    public function sourceBadge(): array
    {
        return match ($this->source) {
            'meta_ads' => ['label' => '📘 Meta Ads', 'class' => 'bg-blue-100 text-blue-800'],
            'google_ads' => ['label' => '🔴 Google Ads', 'class' => 'bg-red-100 text-red-800'],
            'website' => ['label' => '🌐 Website', 'class' => 'bg-emerald-100 text-emerald-800'],
            'whatsapp' => ['label' => '💬 WhatsApp', 'class' => 'bg-green-100 text-green-800'],
            'contact_form' => ['label' => '✉️ Contact Form', 'class' => 'bg-amber-100 text-amber-800'],
            'referral' => ['label' => '🤝 Referral', 'class' => 'bg-violet-100 text-violet-800'],
            'walk_in' => ['label' => '🚶 Walk-in', 'class' => 'bg-orange-100 text-orange-800'],
            'offline_campaign' => ['label' => '📢 Offline Campaign', 'class' => 'bg-fuchsia-100 text-fuchsia-800'],
            'tele_inbound' => ['label' => '📞 Inbound Call', 'class' => 'bg-cyan-100 text-cyan-800'],
            'manual' => ['label' => '✏️ Manually Added', 'class' => 'bg-slate-200 text-slate-800'],
            default => ['label' => $this->source_label, 'class' => 'bg-slate-100 text-slate-600'],
        };
    }

    public function user()
{
    return $this->belongsTo(User::class);
}
}
