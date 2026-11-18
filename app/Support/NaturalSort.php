<?php

namespace App\Support;

/**
 * Orders room and bed numbers the way people read them: 1, 02, 3, 11, 38, 42, F4 (numbers first, by value,
 * so "02" is 2), and A, B, C, A-2, A-10 (letters alphabetically, the numbers inside by value).
 * A plain text sort puts "3" after "38" and "10" before "2".
 */
class NaturalSort
{
    /** For usort: negative, zero or positive. */
    public static function compare(?string $a, ?string $b): int
    {
        $ta = self::tokens((string) $a);
        $tb = self::tokens((string) $b);

        $n = max(count($ta), count($tb));
        for ($i = 0; $i < $n; $i++) {
            if (!isset($ta[$i])) {
                return -1;
            }
            if (!isset($tb[$i])) {
                return 1;
            }
            [$aIsNum, $aVal] = $ta[$i];
            [$bIsNum, $bVal] = $tb[$i];

            if ($aIsNum && $bIsNum) {
                if ($aVal !== $bVal) {
                    return $aVal <=> $bVal;
                }
            } elseif ($aIsNum !== $bIsNum) {
                return $aIsNum ? -1 : 1;        // a number comes before text
            } else {
                $c = strcmp($aVal, $bVal);
                if ($c !== 0) {
                    return $c;
                }
            }
        }

        return strcmp((string) $a, (string) $b);   // "02" and "2" are the same size: keep a steady order
    }

    /** Sorts a collection by a field with the natural order. */
    public static function sortBy(\Illuminate\Support\Collection $items, callable $field): \Illuminate\Support\Collection
    {
        return $items->sort(fn ($x, $y) => self::compare($field($x), $field($y)))->values();
    }

    /** "A-10" becomes [text "a", text "-", number 10]. */
    private static function tokens(string $s): array
    {
        preg_match_all('/\d+|\D+/u', mb_strtolower(trim($s)), $m);

        return array_map(
            fn ($t) => ctype_digit($t) ? [true, (int) $t] : [false, $t],
            $m[0]
        );
    }
}
