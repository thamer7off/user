<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\App;
use App\Core\Controller;
use App\Core\Session;
use App\Models\PasswordReset;
use App\Models\RememberToken;
use App\Models\User;
use App\Services\Auth;
use App\Services\BrevoMailer;
use App\Services\RateLimiter;

final class PasswordController extends Controller
{
    private const TOKEN_MINUTES = 60;

    public function showForgot(): void
    {
        $this->render('auth/forgot', ['title' => 'Reset password']);
    }

    public function sendLink(): void
    {
        $email = strtolower(trim($this->input('email')));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255) {
            Session::flash('error', 'Enter a valid email address.');
            $this->redirect('/forgot-password');
        }

        // The same message is shown whether or not the account exists (no user enumeration).
        $generic = 'If an account exists for that email, we sent a link to reset the password. It expires in '
                 . self::TOKEN_MINUTES . ' minutes.';

        $ipKey = 'reset:ip:' . $this->ip();
        $emailKey = 'reset:email:' . $email;
        if (RateLimiter::tooMany($ipKey, 10, 3600) || RateLimiter::tooMany($emailKey, 3, 3600)) {
            error_log('[reset-debug] rate limited: ' . $email); // DEBUG (remove later)
            Session::flash('success', $generic);
            $this->redirect('/login');
        }
        RateLimiter::hit($ipKey);
        RateLimiter::hit($emailKey);

        $user = User::findByEmail($email);
        Session::flash('success', $generic);

        $this->redirectThen('/login', function () use ($user, $email): void {
            error_log('[reset-debug] email=' . $email . ' user_found=' . ($user ? 'yes' : 'NO')); // DEBUG (remove later)
            if ($user === null) {
                return;
            }
            $token = bin2hex(random_bytes(32));
            PasswordReset::create((int) $user['id'], hash('sha256', $token), time() + self::TOKEN_MINUTES * 60);
            $link = App::url('/reset-password') . '?token=' . $token;
            BrevoMailer::sendPasswordReset($user, $link, self::TOKEN_MINUTES);
        });
    }

    public function showReset(): void
    {
        $token = is_string($_GET['token'] ?? null) ? $_GET['token'] : '';
        if ($this->validToken($token) === null) {
            Session::flash('error', 'This reset link is invalid or has expired. Request a new one.');
            $this->redirect('/forgot-password');
        }
        $this->render('auth/reset', ['title' => 'New password', 'token' => $token]);
    }

    public function reset(): void
    {
        $ipKey = 'reset-submit:ip:' . $this->ip();
        if (RateLimiter::tooMany($ipKey, 10, 3600)) {
            Session::flash('error', 'Too many attempts. Please try again later.');
            $this->redirect('/forgot-password');
        }
        RateLimiter::hit($ipKey);

        $token = $this->input('token');
        $row = $this->validToken($token);
        if ($row === null) {
            Session::flash('error', 'This reset link is invalid or has expired. Request a new one.');
            $this->redirect('/forgot-password');
        }

        $password = $this->input('password');
        $errors = [];
        if (strlen($password) < 8 || strlen($password) > 72) {
            $errors[] = 'Use a password between 8 and 72 characters.';
        }
        if (!hash_equals($password, $this->input('password_confirm'))) {
            $errors[] = 'The two passwords do not match.';
        }
        if ($errors) {
            Session::flash('error', $errors);
            $this->redirect('/reset-password?token=' . $token);
        }

        $userId = (int) $row['user_id'];
        User::setPassword($userId, Auth::hashPassword($password));
        User::markVerified($userId); // they proved they own the inbox
        PasswordReset::deleteForUser($userId);
        RememberToken::deleteForUser($userId); // signs out every "keep me signed in" device

        $user = User::find($userId);
        Session::flash('success', 'Password updated. Sign in with your new password.');
        $this->redirectThen('/login', function () use ($user): void {
            if ($user !== null) {
                BrevoMailer::sendPasswordChanged($user);
            }
        });
    }

    private function validToken(string $token): ?array
    {
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            return null;
        }
        return PasswordReset::findValid(hash('sha256', $token));
    }
}