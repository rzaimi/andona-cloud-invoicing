<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Generates document numbers from a configurable format string.
 *
 * Supported tokens:
 *   {PREFIX}  – literal prefix character(s), no special meaning in the engine
 *   {YYYY}    – 4-digit year         e.g. 2025
 *   {YY}      – 2-digit year         e.g. 25
 *   {MM}      – 2-digit month        e.g. 01 … 12
 *   {DD}      – 2-digit day          e.g. 01 … 31
 *   {####}    – sequential counter with zero-padding equal to the number of '#' chars
 *              (minimum 4; the token MUST appear exactly once)
 *
 * Example formats:
 *   RE-{YYYY}-{####}            →  RE-2025-0001
 *   AN-{YYYY}-{MM}-{####}       →  AN-2025-01-0001  (resets monthly)
 *   KU-{YY}{MM}-{##}            →  KU-2501-01
 *   INV/{YYYY}/{MM}/{#####}     →  INV/2025/01/00001
 */
class NumberFormatService
{
    /** @var array<string, string> Date tokens and their Carbon format equivalents */
    private const DATE_TOKENS = [
        '{YYYY}' => 'Y',
        '{YY}'   => 'y',
        '{MM}'   => 'm',
        '{DD}'   => 'd',
    ];

    /** Regex that matches the sequential-counter token, e.g. {####} or {#} */
    private const SEQ_PATTERN = '/\{(#+)\}/';

    /**
     * Resolve the format string against a specific date and sequential number.
     *
     * @param  string  $format     Format string (e.g. "RE-{YYYY}-{####}")
     * @param  int     $seq        Sequential counter value (1-based)
     * @param  Carbon|null  $date  Date to use for token replacement (defaults to now())
     */
    public function resolve(string $format, int $seq, ?Carbon $date = null): string
    {
        $date ??= now();

        // 1. Replace date tokens
        $result = $format;
        foreach (self::DATE_TOKENS as $token => $carbonFmt) {
            $result = str_replace($token, $date->format($carbonFmt), $result);
        }

        // 2. Replace sequential counter token
        $padWidth = $this->seqPadWidth($format);
        $result   = preg_replace(self::SEQ_PATTERN, str_pad($seq, $padWidth, '0', STR_PAD_LEFT), $result);

        return $result;
    }

    /**
     * Determine the "scope prefix" — the resolved portion of the format up to and
     * including all date tokens but NOT the sequential counter.
     * This is used to find already-issued numbers that share the same counter scope.
     *
     * For "RE-{YYYY}-{####}"      on 2025-05-01 → "RE-2025-"
     * For "AN-{YYYY}-{MM}-{####}" on 2025-05-01 → "AN-2025-05-"
     */
    public function scopePrefix(string $format, ?Carbon $date = null): string
    {
        $date ??= now();

        // Replace date tokens
        $resolved = $format;
        foreach (self::DATE_TOKENS as $token => $carbonFmt) {
            $resolved = str_replace($token, $date->format($carbonFmt), $resolved);
        }

        // Cut off at the sequential-counter token
        if (preg_match(self::SEQ_PATTERN, $resolved, $m, PREG_OFFSET_CAPTURE)) {
            return substr($resolved, 0, $m[0][1]);
        }

        // No counter token → return the whole resolved string as the prefix
        return $resolved;
    }

    /**
     * Find the last used sequential number among a collection of existing document numbers
     * that share the current scope prefix.
     *
     * @param  string      $format   Format string
     * @param  Collection  $numbers  All existing numbers (strings) for this company
     * @param  Carbon|null $date     Date context (defaults to now())
     */
    public function lastSequential(string $format, Collection $numbers, ?Carbon $date = null): int
    {
        $prefix   = $this->scopePrefix($format, $date);
        $padWidth = $this->seqPadWidth($format);
        $prefixLen = strlen($prefix);

        $max = 0;
        foreach ($numbers as $number) {
            if (!str_starts_with((string) $number, $prefix)) {
                continue;
            }
            // The sequential number occupies the last $padWidth chars of the number
            $seq = (int) substr((string) $number, -$padWidth);
            if ($seq > $max) {
                $max = $seq;
            }
        }

        return $max;
    }

    /**
     * Generate the next number for a given set of existing numbers.
     *
     * @param  string      $format      Format string
     * @param  Collection  $numbers     All existing document numbers for this company
     * @param  Carbon|null $date        Date context (defaults to now())
     * @param  int         $minCounter  Minimum counter value (from settings override).
     *                                  The actual counter is max(DB-derived, $minCounter - 1) + 1,
     *                                  so the counter can only move forward, never create duplicates.
     */
    public function next(string $format, Collection $numbers, ?Carbon $date = null, int $minCounter = 1): string
    {
        $date ??= now();
        $last = max($this->lastSequential($format, $numbers, $date), $minCounter - 1);

        return $this->resolve($format, $last + 1, $date);
    }

    /**
     * Normalise a legacy plain-prefix (e.g. "RE-") to a full format string.
     * If the value already contains "{", it is returned unchanged.
     */
    public function normaliseToFormat(string $prefixOrFormat): string
    {
        if (str_contains($prefixOrFormat, '{')) {
            return $prefixOrFormat;
        }

        // Strip trailing dash/hyphen before appending the standard template
        $clean = rtrim($prefixOrFormat, '-/\\_ ');

        return $clean . '-{YYYY}-{####}';
    }

    /**
     * Preview: return what today's first number would look like with this format.
     * Useful for the settings UI live preview.
     */
    public function preview(string $format, int $sample = 1): string
    {
        return $this->resolve($format, $sample);
    }

    // -------------------------------------------------------------------------

    /** Return the zero-padding width determined by the counter token (default 4). */
    private function seqPadWidth(string $format): int
    {
        if (preg_match(self::SEQ_PATTERN, $format, $matches)) {
            return max(1, strlen($matches[1]));
        }

        return 4;
    }
}
