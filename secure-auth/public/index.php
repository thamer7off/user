<?php
declare(strict_types=1);

define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');

spl_autoload_register(static function (string $class): void {
    if (!str_starts_with($class, 'App\\')) {
        return;
    }
    $file = APP_PATH . '/' . str_replace('\\', '/', substr($class, 4)) . '.php';
    if (is_file($file)) {
        require $file;
    }
});
require APP_PATH . '/helpers.php';

use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\OAuthController;
use App\Controllers\PasswordController;
use App\Core\App;
use App\Core\Env;
use App\Core\Router;
use App\Core\Session;
use App\Core\View;

Env::load(ROOT_PATH . '/.env');

error_reporting(E_ALL);
ini_set('display_errors', App::debug() ? '1' : '0');

set_exception_handler(static function (Throwable $e): void {
    error_log((string) $e);
    http_response_code(500);
    View::render('errors/error', [
        'title'   => 'Something went wrong',
        'message' => App::debug() ? $e->getMessage() : 'Please try again in a moment.',
    ]);
});

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: no-referrer');
header('Cache-Control: no-store');
header("Content-Security-Policy: default-src 'self'; img-src 'self' data: https:; "
     . "style-src 'self' https://fonts.googleapis.com; font-src https://fonts.gstatic.com; "
     . "script-src 'self'; frame-ancestors 'none'; base-uri 'self'; form-action 'self'");
if (App::isHttps()) {
    header('Strict-Transport-Security: max-age=31536000');
}

Session::start();

$router = new Router();
$router->get('/', [AuthController::class, 'home']);
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/register', [AuthController::class, 'showRegister']);
$router->post('/register', [AuthController::class, 'register']);
$router->post('/logout', [AuthController::class, 'logout']);
$router->get('/dashboard', [DashboardController::class, 'index']);
$router->get('/forgot-password', [PasswordController::class, 'showForgot']);
$router->post('/forgot-password', [PasswordController::class, 'sendLink']);
$router->get('/reset-password', [PasswordController::class, 'showReset']);
$router->post('/reset-password', [PasswordController::class, 'reset']);
$router->get('/auth/{provider}', [OAuthController::class, 'start']);
$router->get('/auth/{provider}/callback', [OAuthController::class, 'callback']);

$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');
