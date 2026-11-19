<?php
declare(strict_types=1);

namespace App\Core;

final class Csrf
{
    public static function token(): string
    {
        if (!Session::has('_csrf')) {
            Session::set('_csrf', bin2hex(random_bytes(32)));
        }
        return (string) Session::get('_csrf');
    }

    public static function field(): string
    {
        return '<input type="hidden" name="_csrf" value="' . htmlspecialchars(self::token(), ENT_QUOTES) . '">';
    }

    public static function verify(mixed $submitted): bool
    {
        $stored = Session::get('_csrf');
        return is_string($submitted) && is_string($stored) && hash_equals($stored, $submitted);
    }
}
