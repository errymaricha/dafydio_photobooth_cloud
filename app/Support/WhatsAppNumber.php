<?php

namespace App\Support;

class WhatsAppNumber
{
    public static function normalizedIndonesia(string $value): string
    {
        $digits = preg_replace('/\D+/', '', $value) ?? '';

        if (str_starts_with($digits, '0')) {
            return '62'.substr($digits, 1);
        }

        if (str_starts_with($digits, '8')) {
            return '62'.$digits;
        }

        return $digits;
    }

    public static function lookupVariants(string $value): array
    {
        $normalized = self::normalizedIndonesia($value);
        $local = str_starts_with($normalized, '62')
            ? '0'.substr($normalized, 2)
            : $normalized;

        return collect([
            trim($value),
            $normalized,
            sprintf('+%s', $normalized),
            $local,
        ])
            ->filter()
            ->uniqueStrict()
            ->values()
            ->all();
    }
}
