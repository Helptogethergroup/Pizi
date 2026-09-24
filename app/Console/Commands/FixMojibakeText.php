<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixMojibakeText extends Command
{
    protected $signature = 'data:fix-mojibake {--apply : Actually write the fix (default is a dry run)}';

    protected $description = 'Repairs text that was accidentally double UTF-8 encoded (shows up as "â€"" instead of "—")';

    /** [table => [primary key, [columns to check]]] */
    private array $targets = [
        'properties' => ['id', ['name', 'address_line']],
        'cities' => ['id', ['meta_title', 'meta_description', 'description']],
        'blogs' => ['id', ['title', 'content', 'excerpt', 'meta_title', 'meta_description', 'keywords']],
        'landmarks' => ['id', ['name', 'meta_title', 'meta_description', 'description']],
        'seo_settings' => ['id', ['meta_title', 'meta_description', 'meta_keywords', 'og_title', 'og_description']],
    ];

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $this->info($apply ? 'Applying fixes...' : 'Dry run — pass --apply to actually save changes.');

        $totalRows = 0;
        $totalSkipped = 0;

        foreach ($this->targets as $table => [$pk, $columns]) {
            $rows = DB::table($table)->get();
            $shown = 0;

            foreach ($rows as $row) {
                $updates = [];
                $preview = [];

                foreach ($columns as $col) {
                    $original = $row->{$col} ?? null;
                    if (!$this->looksMojibake($original)) continue;

                    $fixed = $this->repair($original);
                    if ($fixed === null) {
                        $totalSkipped++;
                        $this->warn("  SKIPPED {$table}#{$row->{$pk}}.{$col} — repair didn't look safe, left untouched.");
                        continue;
                    }

                    $updates[$col] = $fixed;
                    $preview[] = "    {$col}: [{$original}] -> [{$fixed}]";
                }

                if (empty($updates)) continue;

                $totalRows++;
                if ($shown < 10) {
                    $this->line("{$table}#{$row->{$pk}}:");
                    foreach ($preview as $p) $this->line($p);
                    $shown++;
                }

                if ($apply) {
                    DB::table($table)->where($pk, $row->{$pk})->update($updates);
                }
            }
        }

        $this->line('');
        $this->info("Rows needing a fix: {$totalRows}" . ($totalSkipped ? ", skipped as unsafe: {$totalSkipped}" : ''));
        $this->info($apply ? 'Done — changes saved.' : 'Nothing saved. Re-run with --apply once this preview looks right.');

        return self::SUCCESS;
    }

    private function looksMojibake(?string $v): bool
    {
        if (!$v) return false;

        return str_contains($v, 'Ã¢') || str_contains($v, 'â€') || str_contains($v, 'Ã©')
            || str_contains($v, 'Ã¯') || str_contains($v, 'Â');
    }

    /**
     * Reverses a single accidental UTF-8 -> Windows-1252 -> UTF-8 double-encode.
     * Returns null if the repair isn't safe to apply (not valid UTF-8 afterwards,
     * or it introduced '?' placeholders that weren't there before — meaning some
     * character couldn't round-trip through Windows-1252 and would be destroyed).
     */
    private function repair(string $v): ?string
    {
        $fixed = @mb_convert_encoding($v, 'Windows-1252', 'UTF-8');

        if ($fixed === false || !mb_check_encoding($fixed, 'UTF-8')) return null;

        $newQuestionMarks = substr_count($fixed, '?') - substr_count($v, '?');
        if ($newQuestionMarks > 0) return null;

        return $fixed;
    }
}
