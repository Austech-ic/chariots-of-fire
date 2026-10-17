<?php

namespace App\Support;

/** Amounts are stored as integers in the currency's minor unit. */
class Money
{
    private const ZERO_DECIMAL = ['JPY', 'KRW', 'XOF', 'XAF', 'UGX', 'RWF'];

    public static function exponent(string $currency): int
    {
        return in_array(strtoupper($currency), self::ZERO_DECIMAL, true) ? 0 : 2;
    }

    /** Convert a user-entered major amount (string to avoid float drift) into minor units. */
    public static function toMinor(string|int $major, string $currency): int
    {
        $major = trim((string) $major);
        $exp = self::exponent($currency);
        [$whole, $fraction] = array_pad(explode('.', $major, 2), 2, '');
        $fraction = substr(str_pad($fraction, $exp, '0'), 0, $exp);

        return (int) ($whole.$fraction);
    }

    public static function format(int $minor, string $currency): string
    {
        $exp = self::exponent($currency);
        $value = $minor / (10 ** $exp);
        $symbol = ['NGN' => '₦', 'USD' => '$', 'GBP' => '£', 'EUR' => '€', 'GHS' => 'GH₵', 'KES' => 'KSh', 'ZAR' => 'R'][strtoupper($currency)] ?? strtoupper($currency).' ';

        return $symbol.number_format($value, $exp);
    }
}
