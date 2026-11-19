<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Models\User;
use App\Services\Auth;
use App\Services\OAuthService;
use App\Services\RateLimiter;

final class AuthController extends Controller
{
    public function home(): void
    {
        $this->redirect(Auth::check() ? '/dashboard' : '/login');
    }

    public function showLogin(): void
    {
        if (Auth::check()) {
            $this->redirect('/dashboard');
        }
        $this->render('auth/login', [
            'title'     => 'Sign in',
            'providers' => OAuthService::enabled(),
            'old'       => Session::pull('_old', []),
        ]);
    }

    public function login(): void
    {
        $email = strtolower(trim($this->input('email')));
        $password = $this->input('password');
        $remember = $this->input('remember') === '1';

        $ipKey = 'login:ip:' . $this->ip();
        $emailKey = 'login:email:' . substr($email, 0, 255);

        if (RateLimiter::tooMany($ipKey, 20, 900) || RateLimiter::tooMany($emailKey, 5, 900)) {
            Session::flash('error', 'Too many sign-in attempts. Please try again in 15 minutes.');
            Session::set('_old', ['email' => $email]);
            $this->redirect('/login');
        }

        $user = Auth::attempt($email, $password);
        if ($user === null) {
            RateLimiter::hit($ipKey);
            RateLimiter::hit($emailKey);
            Session::flash('error', 'Incorrect email or password.');
            Session::set('_old', ['email' => $email]);
            $this->redirect('/login');
        }

        RateLimiter::clear($emailKey);
        Auth::login($user, $remember);
        $this->redirect('/dashboard');
    }

    public function showRegister(): void
    {
        if (Auth::check()) {
            $this->redirect('/dashboard');
        }
        $this->render('auth/register', [
            'title'     => 'Create account',
            'providers' => OAuthService::enabled(),
            'old'       => Session::pull('_old', []),
        ]);
    }

    public function register(): void
    {
        $name = trim($this->input('name'));
        $email = strtolower(trim($this->input('email')));
        $password = $this->input('password');

        $ipKey = 'register:ip:' . $this->ip();
        if (RateLimiter::tooMany($ipKey, 10, 3600)) {
            Session::flash('error', 'Too many sign-up attempts. Please try again later.');
            $this->redirect('/register');
        }
        RateLimiter::hit($ipKey);

        $errors = [];
        if ($name === '' || mb_strlen($name) > 100) {
            $errors[] = 'Enter your name (up to 100 characters).';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255) {
            $errors[] = 'Enter a valid email address.';
        }
        if (strlen($password) < 8 || strlen($password) > 72) {
            $errors[] = 'Use a password between 8 and 72 characters.';
        }
        if (!$errors && User::findByEmail($email) !== null) {
            $errors[] = 'An account with this email already exists. Sign in or reset your password.';
        }

        if (!$errors) {
            try {
                $id = User::create($name, $email, Auth::hashPassword($password), null, false);
                Auth::login(User::find($id), false);
                $this->redirect('/dashboard');
            } catch (\PDOException $e) {
                if (($e->errorInfo[1] ?? 0) !== 1062) { // 1062 = duplicate email (race condition)
                    throw $e;
                }
                $errors[] = 'An account with this email already exists. Sign in or reset your password.';
            }
        }

        Session::flash('error', $errors);
        Session::set('_old', ['name' => $name, 'email' => $email]);
        $this->redirect('/register');
    }

    public function logout(): void
    {
        Auth::logout();
        Session::flash('success', 'You have been signed out.');
        $this->redirect('/login');
    }
}
