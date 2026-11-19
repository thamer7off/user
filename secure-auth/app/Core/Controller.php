<?php
declare(strict_types=1);

namespace App\Core;

abstract class Controller
{
    protected function render(string $view, array $data = []): void
    {
        View::render($view, $data);
    }

    protected function redirect(string $path): never
    {
        header('Location: ' . App::url($path));
        exit;
    }

    /** Send the redirect immediately, then run slow work (e.g. sending email) afterwards. */
    protected function redirectThen(string $path, callable $after): never
    {
        header('Location: ' . App::url($path));
        Session::close();
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }
        $after();
        exit;
    }

    protected function ip(): string
    {
        // Behind a reverse proxy, map the real client IP into REMOTE_ADDR at the server level.
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    protected function input(string $key): string
    {
        $value = $_POST[$key] ?? '';
        return is_string($value) ? $value : '';
    }
}
