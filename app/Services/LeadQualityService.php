<?php

namespace App\Services;

use App\Models\Lead;

/**
 * First line of defence against leads that are not real: a lead is what an owner
 * pays credits for, so a typed-in test number or a bot's name must never reach one.
 *
 * - phone problems are shown to the person filling the form (they may just have
 *   made a typo), so a real person can fix it;
 * - name / message problems are handled quietly: the lead is saved as "junk" for
 *   the team to see, but no owner or telecaller is told about it.
 */
class LeadQualityService
{
    /** Words a person types when testing a form, not their name. */
    private const FAKE_NAMES = [
        'test', 'testing', 'tester', 'demo', 'fake', 'asdf', 'asd', 'sdf', 'qwerty', 'qwe',
        'xyz', 'abc', 'abcd', 'none', 'na', 'n/a', 'nothing', 'no name', 'noname', 'name',
        'hello', 'hi', 'ok', 'okay', 'nil', 'null', 'unknown', 'dummy', 'sample',
    ];

    /** Words that only spam messages contain. */
    private const SPAM_WORDS = [
        'casino', 'bitcoin', 'crypto', 'forex', 'viagra', 'backlink', 'seo service', 'loan offer',
        'click here', 'free money', 'lottery', 'adult', 'escort', 'porn', 'sex ',
    ];

    /**
     * The 10 digit Indian mobile number inside what was typed (spaces, +91, 0091,
     * 91 and a leading 0 are all accepted), or null when it is not one.
     */
    public function normalizePhone(?string $raw): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $raw);

        if (strlen($digits) === 14 && str_starts_with($digits, '0091')) {
            $digits = substr($digits, 4);
        } elseif (strlen($digits) === 12 && str_starts_with($digits, '91')) {
            $digits = substr($digits, 2);
        } elseif (strlen($digits) === 11 && str_starts_with($digits, '0')) {
            $digits = substr($digits, 1);
        }

        return preg_match('/^[6-9]\d{9}$/', $digits) ? $digits : null;
    }

    /** True for numbers somebody typed to get past the form: 9999999999, 9876543210, 9000000000... */
    public function looksFakePhone(string $phone10): bool
    {
        // seven or more times the same digit
        if (preg_match('/(\d)\1{6,}/', $phone10)) {
            return true;
        }
        // seven digits going straight up or down in a row (1234567, 9876543)
        $up = '0123456789';
        $down = '9876543210';
        for ($i = 0; $i <= strlen($phone10) - 7; $i++) {
            $part = substr($phone10, $i, 7);
            if (str_contains($up, $part) || str_contains($down, $part)) {
                return true;
            }
        }
        // 9876500000 style: six zeros at the end
        if (preg_match('/0{6}$/', $phone10)) {
            return true;
        }
        // the same two digits again and again: 9898989898
        if (preg_match('/^(\d\d)\1{4}$/', $phone10)) {
            return true;
        }

        return false;
    }

    /** Why this phone cannot be a real person's, or null if it can. */
    public function phoneProblem(?string $raw): ?string
    {
        $phone = $this->normalizePhone($raw);
        if ($phone === null) {
            return 'not_a_mobile_number';
        }
        return $this->looksFakePhone($phone) ? 'fake_pattern' : null;
    }

    /** Why this name / message looks like a test or a bot, or null. */
    public function contentProblem(?string $name, ?string $message = null): ?string
    {
        $name = trim((string) $name);
        $lower = mb_strtolower($name);

        if (mb_strlen($name) < 2) {
            return 'name_too_short';
        }
        if (!preg_match('/\p{L}/u', $name)) {
            return 'name_has_no_letters';
        }
        if (in_array($lower, self::FAKE_NAMES, true) || preg_match('/\b(test|testing|demo|dummy)\b/i', $name)) {
            return 'test_name';
        }
        if (preg_match('/(.)\1{3,}/u', $lower)) {
            return 'repeated_letters';
        }
        // letters mixed with four or more digits: random strings such as NARYTHY133048NEHTYHYHTR
        if (preg_match('/\p{L}/u', $name) && preg_match_all('/\d/', $name) >= 4) {
            return 'name_with_digits';
        }
        // a long stretch without any vowel is keyboard mashing
        if (preg_match('/[bcdfghjklmnpqrstvwxyz]{7,}/i', $name)) {
            return 'random_letters';
        }

        $text = mb_strtolower((string) $message);
        if ($text !== '') {
            if (preg_match('#(https?://|www\.)#i', $text)) {
                return 'link_in_message';
            }
            foreach (self::SPAM_WORDS as $word) {
                if (str_contains($text, $word)) {
                    return 'spam_message';
                }
            }
        }

        return null;
    }

    /**
     * The same person (phone) asking about the same PG (or no PG at all) in the last
     * [$days] days. Returns that earlier lead, so a second one is not created and sold twice.
     */
    public function recentDuplicate(string $phone10, $propertyId = null, int $days = 30): ?Lead
    {
        return Lead::whereRaw("RIGHT(REPLACE(REPLACE(REPLACE(phone, '+', ''), ' ', ''), '-', ''), 10) = ?", [$phone10])
            ->where('property_id', $propertyId)
            ->where('created_at', '>=', now()->subDays($days))
            ->where('status', '!=', 'junk')
            ->latest()
            ->first();
    }

    /** Human wording for the team, shown in the lead's notes. */
    public function describe(string $reason): string
    {
        return match ($reason) {
            'not_a_mobile_number' => 'Phone is not a valid Indian mobile number',
            'fake_pattern' => 'Phone looks typed in (repeated or sequential digits)',
            'name_too_short', 'name_has_no_letters' => 'Name is empty or has no letters',
            'test_name' => 'Name looks like a test entry',
            'repeated_letters', 'random_letters', 'name_with_digits' => 'Name looks random (bot or typing test)',
            'link_in_message' => 'Message contains a link',
            'spam_message' => 'Message looks like spam',
            default => $reason,
        };
    }
}
