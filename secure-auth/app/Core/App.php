<?php
declare(strict_types=1);

namespace App\Core;

final class App
{
    public static function baseUrl(): string
    {
        return rtrim(Env::get('APP_URL', 'http://localhost:8000') ?? '', '/');
    }

    /** Path part of APP_URL, e.g. "" or "/my-app/public". */
    public static function basePath(): string
    {
        return rtrim((string) parse_url(self::baseUrl(), PHP_URL_PATH), '/');
    }

    public static function isHttps(): bool
    {
        return str_starts_with(self::baseUrl(), 'https://');
    }

    public static function url(string $path = ''): string
    {
        return self::baseUrl() . '/' . ltrim($path, '/');
    }

    public static function debug(): bool
    {
        return Env::get('APP_DEBUG', 'false') === 'true';
    }
}
