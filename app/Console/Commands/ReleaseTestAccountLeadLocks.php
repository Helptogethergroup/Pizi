<?php

namespace App\Console\Commands;

use App\Models\Lead;
use App\Models\LeadUnlock;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * One-off cleanup: leads already unlocked by an account now marked
 * is_test_account still show "Claimed" in the admin panel (the leads
 * table's own is_locked/locked_by_user_id columns are separate from the
 * lead_unlocks table that LeadMatchingService actually checks). This
 * resets those cosmetic columns so the admin UI matches reality — the
 * lead unlock itself (LeadUnlock row, credits spent) is left untouched.
 */
class ReleaseTestAccountLeadLocks extends Command
{
    protected $signature = 'leads:release-test-account-locks';
    protected $description = 'Un-claim (in the admin UI) any lead locked by a user marked is_test_account';

    public function handle(): int
    {
        $testUserIds = User::where('is_test_account', true)->pluck('id');

        if ($testUserIds->isEmpty()) {
            $this->info('No users are marked is_test_account — nothing to release.');
            return self::SUCCESS;
        }

        $released = Lead::whereIn('locked_by_user_id', $testUserIds)
            ->update(['is_locked' => false, 'locked_by_user_id' => null]);

        $this->info("Released {$released} lead(s) claimed by test account(s): " . $testUserIds->implode(', ') . '.');
        $this->info('Their lead_unlocks history (credits spent) was left untouched — only the leads table claim flag was reset.');

        return self::SUCCESS;
    }
}
