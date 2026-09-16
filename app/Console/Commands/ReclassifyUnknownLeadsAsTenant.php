<?php

namespace App\Console\Commands;

use App\Models\Lead;
use Illuminate\Console\Command;

/**
 * One-time cleanup: leads sitting at inquiry_type='unknown' never reach
 * any owner's dashboard (owners only see 'tenant' leads). Since almost
 * every enquiry Pizi gets is someone looking for a PG (not someone
 * wanting to list one), default all currently-unknown leads to 'tenant'
 * so they actually reach an owner instead of sitting invisible forever.
 */
class ReclassifyUnknownLeadsAsTenant extends Command
{
    protected $signature = 'leads:reclassify-unknown-as-tenant {--dry-run : Preview the count without saving anything}';

    protected $description = 'Bulk-convert all inquiry_type=unknown leads to tenant so they show up on owner dashboards';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $count = Lead::where('inquiry_type', 'unknown')->count();

        if ($count === 0) {
            $this->info('No unknown leads found — nothing to do.');
            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->info("DRY RUN — would reclassify {$count} lead(s) from 'unknown' to 'tenant'.");
            return self::SUCCESS;
        }

        $updated = Lead::where('inquiry_type', 'unknown')->update(['inquiry_type' => 'tenant']);

        $this->info("Reclassified {$updated} lead(s) from 'unknown' to 'tenant'.");
        return self::SUCCESS;
    }
}
