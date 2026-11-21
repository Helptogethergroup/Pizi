<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * One owner entered advance rent for every tenant while adding them (the tenant form's "advance" box instead of the
 * security deposit), so every bill reads "Paid" and nothing can be billed. This puts those bills back to unpaid.
 *
 * Only the bills the add-tenant form made for the advance (notes "Advance rent ...") and that are paid are touched.
 * The payment rows that form made are removed, the bill goes back to pending with the full amount due, and a new due
 * date is set so the bill is not already "overdue". Nothing changes unless --apply is given; before it changes
 * anything, the bills and payments are saved to a JSON file (storage/app/backups) so they can be put back.
 */
class RevertAdvancePaidBills extends Command
{
    protected $signature = 'rent:revert-advance-paid {phone : The owner\'s phone number}
        {--apply : Really change the bills (without it, only a report)}
        {--due= : New due date for these bills, YYYY-MM-DD (default: 7 days from today)}';
    protected $description = 'Put an owner\'s advance-entered "paid" bills back to unpaid (report first, --apply to change)';

    public function handle(): int
    {
        $phone = substr(preg_replace('/\D+/', '', (string) $this->argument('phone')), -10);
        $owner = DB::table('users')->where('role', 'owner')->where('phone', 'like', '%' . $phone)->get();
        if ($owner->count() !== 1) {
            $this->error($owner->count() === 0 ? 'No owner with that phone number.' : 'More than one owner has that phone number: ' . $owner->pluck('name', 'id')->toJson());

            return self::FAILURE;
        }
        $owner = $owner->first();

        $due = $this->option('due') ?: now()->addDays(7)->toDateString();
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $due) || strtotime($due) === false || $due <= now()->toDateString()) {
            $this->error('--due must be a date after today, like ' . now()->addDays(7)->toDateString());

            return self::FAILURE;
        }

        $base = DB::table('rent_bills')->where('owner_id', $owner->id)->whereNull('deleted_at');
        $allPaid = (clone $base)->where('status', 'paid')->count();
        $bills = (clone $base)->where('status', 'paid')->where('notes', 'like', 'Advance rent%')->get();

        $this->info("Owner: {$owner->name} ({$owner->phone})");
        $this->line("Paid bills in all: {$allPaid}. Advance-entered paid bills (the ones that will change): {$bills->count()}.");
        if ($allPaid !== $bills->count()) {
            $this->warn(($allPaid - $bills->count()) . ' other paid bills stay as they are (they are not advance entries).');
        }
        $this->line('Total amount in these bills: ₹' . number_format((float) $bills->sum('total_amount'), 2) . '. New due date: ' . $due . '.');
        foreach ($bills->take(10) as $b) {
            $this->line("  #{$b->bill_number}  tenant {$b->tenant_id}  {$b->month}  ₹{$b->total_amount}");
        }
        if ($bills->count() > 10) {
            $this->line('  ... and ' . ($bills->count() - 10) . ' more');
        }

        if (!$this->option('apply')) {
            $this->warn('Report only. Nothing was changed. Add --apply to change these bills.');

            return self::SUCCESS;
        }
        if ($bills->isEmpty()) {
            $this->info('Nothing to change.');

            return self::SUCCESS;
        }

        $ids = $bills->pluck('id')->all();
        $payments = DB::table('rent_payments')->whereIn('rent_bill_id', $ids)->get();

        // a copy first, so this can be undone
        File::ensureDirectoryExists(storage_path('app/backups'));
        $file = storage_path('app/backups/advance-bills-owner-' . $owner->id . '-' . now()->format('Ymd-His') . '.json');
        File::put($file, json_encode(['bills' => $bills, 'payments' => $payments], JSON_PRETTY_PRINT));
        $this->info('Saved a copy to ' . $file);

        DB::transaction(function () use ($ids, $due) {
            DB::table('rent_payments')->whereIn('rent_bill_id', $ids)->delete();
            DB::table('rent_bills')->whereIn('id', $ids)->update([
                'paid_amount' => 0,
                'due_amount' => DB::raw('total_amount'),
                'status' => 'pending',
                'due_date' => $due,
                'notes' => null,
                'updated_at' => now(),
            ]);
        });

        $this->info("Done: {$bills->count()} bills are unpaid now (due {$due}), {$payments->count()} payment rows removed.");

        return self::SUCCESS;
    }
}
