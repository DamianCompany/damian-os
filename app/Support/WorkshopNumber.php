<?php

namespace App\Support;

use Illuminate\Validation\ValidationException;

final class WorkshopNumber
{
    public static function scaled(mixed $value, int $digits = 2): int
    {
        $text = trim((string) $value);
        if (! preg_match('/^\d{1,8}(?:\.\d{1,6})?$/', $text)) {
            throw ValidationException::withMessages(['amount' => 'Ingresa un número positivo sin separadores de miles.']);
        }
        [$whole, $fraction] = array_pad(explode('.', $text, 2), 2, '');
        $padded = str_pad($fraction, $digits + 1, '0');

        return (int) $whole * (10 ** $digits) + (int) substr($padded, 0, $digits) + ((int) $padded[$digits] >= 5 ? 1 : 0);
    }

    public static function proportional(int $amount, int $quantity, int $base): int
    {
        if ($base <= 0 || $amount < 0 || $quantity < 0 || ($quantity && $amount > intdiv(PHP_INT_MAX - intdiv($base, 2), $quantity))) {
            throw ValidationException::withMessages(['amount' => 'Cantidad o base fuera del rango permitido.']);
        }

        return intdiv($amount * $quantity + intdiv($base, 2), $base);
    }

    public static function money(?int $cents): string
    {
        return $cents === null ? 'Incompleto' : 'S/ '.number_format($cents / 100, 2);
    }
}
