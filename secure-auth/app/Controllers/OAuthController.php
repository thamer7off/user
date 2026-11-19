<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Services\Auth;
use App\Services\OAuthException;
use App\Services\OAuthService;

final class OAuthController extends Controller
{
    public function start(array $params = []): void
    {
        $provider = $params['provider'] ?? '';
        if (!OAuthService::isEnabled($provider)) {
            Session::flash('error', 'That sign-in method is not available.');
            $this->redirect('/login');
        }
        $remember = ($_GET['remember'] ?? '') === '1';
        header('Location: ' . OAuthService::authorizationUrl($provider, $remember));
        exit;
    }

    public function callback(array $params = []): void
    {
        $provider = $params['provider'] ?? '';
        if (!OAuthService::isEnabled($provider)) {
            Session::flash('error', 'That sign-in method is not available.');
            $this->redirect('/login');
        }

        try {
            $result = OAuthService::handleCallback($provider, $_GET);
            Auth::loginWithOAuth($provider, $result['profile'], $result['remember']);
        } catch (OAuthException $e) {
            Session::flash('error', $e->getMessage());
            $this->redirect('/login');
        } catch (\Throwable $e) {
            error_log('OAuth login failed: ' . $e);
            Session::flash('error', 'Something went wrong while signing you in. Please try again.');
            $this->redirect('/login');
        }

        $this->redirect('/dashboard');
    }
}
