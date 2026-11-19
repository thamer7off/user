<?php
declare(strict_types=1);

namespace App\Core;

final class Router
{
    private array $routes = [];

    public function get(string $path, array $handler): void
    {
        $this->routes['GET'][$path] = $handler;
    }

    public function post(string $path, array $handler): void
    {
        $this->routes['POST'][$path] = $handler;
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = (string) parse_url($uri, PHP_URL_PATH);
        $base = App::basePath();
        if ($base !== '' && str_starts_with($path, $base)) {
            $path = substr($path, strlen($base));
        }
        $path = '/' . trim($path, '/');

        foreach ($this->routes[$method] ?? [] as $pattern => $handler) {
            $regex = '#^' . preg_replace('#\{(\w+)\}#', '(?P<$1>[A-Za-z0-9_-]+)', $pattern) . '$#';
            if (!preg_match($regex, $path, $matches)) {
                continue;
            }
            if ($method === 'POST' && !Csrf::verify($_POST['_csrf'] ?? null)) {
                http_response_code(419);
                View::render('errors/error', [
                    'title'   => 'Page expired',
                    'code'    => 419,
                    'message' => 'This form is no longer valid. Go back, reload the page, and try again.',
                ]);
                return;
            }
            $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
            [$class, $action] = $handler;
            (new $class())->$action($params);
            return;
        }

        http_response_code(404);
        View::render('errors/error', [
            'title'   => 'Page not found',
            'code'    => 404,
            'message' => 'The page you are looking for does not exist.',
        ]);
    }
}
