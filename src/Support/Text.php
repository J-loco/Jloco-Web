<?php

declare(strict_types=1);

namespace JLoco\Web\Support;

/**
 * Text checks for the legacy schemas: most login-database columns are latin1, and game names are
 * utf8mb3. MariaDB rejects (error 1267/1366) values those charsets cannot represent, so callers
 * validate or filter first instead of letting a query fail.
 */
final class Text
{
    /** Replaces invalid UTF-8 byte sequences (malformed form submissions) with "?". */
    public static function scrub(string $value): string
    {
        return mb_check_encoding($value, 'UTF-8') ? $value : mb_scrub($value, 'UTF-8');
    }

    /** Whether every character exists in latin1 (ISO-8859-1), i.e. can be stored in or compared with a latin1 column. */
    public static function fitsLatin1(string $value): bool
    {
        return mb_check_encoding($value, 'UTF-8')
            && mb_convert_encoding(mb_convert_encoding($value, 'ISO-8859-1', 'UTF-8'), 'UTF-8', 'ISO-8859-1') === $value;
    }

    /** Removes characters outside the Basic Multilingual Plane (emoji…), which utf8mb3 columns cannot hold. */
    public static function withoutSupplementaryCharacters(string $value): string
    {
        return (string) preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', self::scrub($value));
    }
}
