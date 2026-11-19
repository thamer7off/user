<?php
declare(strict_types=1);

namespace App\Core;

final class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.gc_maxlifetime', '7200');
        session_name('app_sid');
        // SameSite=Lax is required so the cookie is sent when the OAuth provider redirects back.
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => App::isHttps(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public static function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public static function pull(string $key, mixed $default = null): mixed
    {
        $value = $_SESSION[$key] ?? $default;
        unset($_SESSION[$key]);
        return $value;
    }

    public static function regenerate(): void
    {
        session_regenerate_id(true);
        unset($_SESSION['_csrf']);
    }

    public static function flash(string $type, string|array $messages): void
    {
        foreach ((array) $messages as $message) {
            $_SESSION['_flash'][$type][] = $message;
        }
    }

    public static function pullFlash(): array
    {
        return self::pull('_flash', []);
    }

    public static function close(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
    }

    public static function destroy(): void
    {
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        $params = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires'  => time() - 3600,
            'path'     => $params['path'],
            'secure'   => $params['secure'],
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        self::start(); // fresh, empty session (used for flash messages after logout)
    }
}
