<?php

namespace App\Support;

/**
 * Money helpers. Amounts are stored as integers in minor units (2 decimals).
 */
final class Money
{
    /**
     * Currencies offered as suggestions. Any 3-letter ISO code is accepted.
     *
     * @var array<string, string>
     */
    public const CURRENCIES = [
        'GHS' => 'Ghanaian cedi',
        'USD' => 'US dollar',
        'EUR' => 'Euro',
        'GBP' => 'British pound',
        'NGN' => 'Nigerian naira',
        'KES' => 'Kenyan shilling',
        'ZAR' => 'South African rand',
        'XOF' => 'West African CFA franc',
        'CAD' => 'Canadian dollar',
        'AED' => 'UAE dirham',
    ];

    /**
     * "1,250.5" or "1250.50" → 125050. Returns null for anything that is not a number.
     */
    public static function toMinor(mixed $value): ?int
    {
        if (is_int($value) || is_float($value)) {
            return (int) round($value * 100);
        }

        $clean = str_replace([',', ' '], '', trim((string) $value));

        if ($clean === '' || ! is_numeric($clean)) {
            return null;
        }

        return (int) round((float) $clean * 100);
    }

    /**
     * 125050 → "1250.50" (for form fields).
     */
    public static function toDecimal(int $minor): string
    {
        return number_format($minor / 100, 2, '.', '');
    }

    /**
     * 125050, "GHS" → "GHS 1,250.50".
     */
    public static function format(int $minor, string $currency): string
    {
        $sign = $minor < 0 ? '-' : '';

        return $sign.strtoupper($currency).' '.number_format(abs($minor) / 100, 2);
    }
}
