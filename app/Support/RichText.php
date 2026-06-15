<?php

namespace App\Support;

use Illuminate\Support\Str;

class RichText
{
    public static function clean(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        $plainText = trim(html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        return $plainText === '' ? null : $value;
    }

    public static function excerpt(?string $value, int $limit = 120): string
    {
        $plainText = trim(html_entity_decode(strip_tags((string) $value), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $plainText = preg_replace('/\s+/u', ' ', $plainText) ?? $plainText;

        return $plainText === '' ? 'No notes provided.' : Str::limit($plainText, $limit);
    }
}
