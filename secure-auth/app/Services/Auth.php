<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\App;
use App\Core\Database;
use App\Core\Session;
use App\Models\OAuthAccount;
use App\Models\RememberToken;
use App\Models\User;

final class Auth
{
    private const REMEMBER_COOKIE = 'remember_me';
    private const REMEMBER_DAYS = 30;
    // Verified against when the email is unknown, so response time doesn't reveal which emails exist.
    private const DUMMY_HASH = '$2y$10$irdepyuXbf/IgFzoD.irfOtGo5Nh.JjPAhwWMOduBRhQQrdh.Oo.a';

    private static ?array $user = null;
    private static bool $resolved = false;

    public static function hashPassword(string $plain): string
    {
        return password_hash($plain, PASSWORD_DEFAULT);
    }

    public static function user(): ?array
    {
        if (self::$resolved) {
            return self::$user;
        }
        self::$resolved = true;

        $id = Session::get('user_id');
        if (is_int($id)) {
            self::$user = User::find($id);
        }
        if (self::$user === null) {
            Session::forget('user_id');
            self::$user = self::loginFromRememberCookie();
        }
        return self::$user;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    /** Checks email + password. Returns the user on success, null otherwise. */
    public static function attempt(string $email, string $password): ?array
    {
        $user = User::findByEmail($email);
        $hash = $user['password_hash'] ?? null;
        $valid = password_verify($password, $hash ?: self::DUMMY_HASH);
        if ($user === null || $hash === null || !$valid) {
            return null;
        }
        if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
            User::setPassword((int) $user['id'], self::hashPassword($password));
        }
        return $user;
    }

    public static function login(array $user, bool $remember = false): void
    {
        Session::regenerate(); // prevents session fixation
        Session::set('user_id', (int) $user['id']);
        self::$user = $user;
        self::$resolved = true;

        if ($remember) {
            self::issueRememberToken((int) $user['id']);
        }
    }

    public static function logout(): void
    {
        $cookie = $_COOKIE[self::REMEMBER_COOKIE] ?? null;
        if (is_string($cookie) && str_contains($cookie, '.')) {
            RememberToken::delete(explode('.', $cookie, 2)[0]);
        }
        self::clearRememberCookie();
        Session::destroy();
        self::$user = null;
        self::$resolved = true;
    }

    /**
     * Finds or creates the local user for a social profile, then signs them in.
     * @throws OAuthException when the provider gives no verified email
     */
    public static function loginWithOAuth(string $provider, array $profile, bool $remember): array
    {
        if ($profile['id'] === '') {
            throw new OAuthException('Could not read your profile from ' . OAuthService::label($provider) . '.');
        }

        $link = OAuthAccount::find($provider, $profile['id']);
        $user = $link ? User::find((int) $link['user_id']) : null;

        if ($user === null) {
            $email = strtolower(trim((string) $profile['email']));
            if ($email === '' || !$profile['verified'] || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new OAuthException(
                    OAuthService::label($provider) . ' did not share a verified email address. '
                    . 'Verify your email there, or sign up with email instead.'
                );
            }

            $pdo = Database::pdo();
            $pdo->beginTransaction();
            try {
                $user = User::findByEmail($email);
                if ($user !== null) {
                    if ($user['email_verified_at'] === null) {
                        // Someone may have pre-registered this address without proving they own it.
                        // The verified social login wins: drop the unproven password and sessions.
                        User::claimUnverified((int) $user['id']);
                        RememberToken::deleteForUser((int) $user['id']);
                    }
                } else {
                    $name = trim($profile['name']) !== '' ? $profile['name'] : explode('@', $email)[0];
                    $id = User::create($name, $email, null, $profile['avatar'], true);
                    $user = User::find($id);
                }
                OAuthAccount::link((int) $user['id'], $provider, $profile['id']);
                $pdo->commit();
            } catch (\Throwable $e) {
                $pdo->rollBack();
                throw $e;
            }
        }

        User::setAvatarIfEmpty((int) $user['id'], $profile['avatar']);
        $user = User::find((int) $user['id']);
        self::login($user, $remember);
        return $user;
    }

    // ---- "Remember me" -------------------------------------------------------------------
    // Cookie holds "selector.validator". Only a SHA-256 of the validator is stored, so a
    // database leak cannot be turned into working cookies.

    private static function issueRememberToken(int $userId): void
    {
        $selector = bin2hex(random_bytes(12));
        $validator = bin2hex(random_bytes(32));
        $expires = time() + self::REMEMBER_DAYS * 86400;

        RememberToken::create($userId, $selector, hash('sha256', $validator), $expires);
        self::setRememberCookie($selector . '.' . $validator, $expires);

        if (random_int(1, 20) === 1) {
            RememberToken::purgeExpired();
        }
    }

    private static function loginFromRememberCookie(): ?array
    {
        $cookie = $_COOKIE[self::REMEMBER_COOKIE] ?? null;
        if (!is_string($cookie) || !preg_match('/^[a-f0-9]{24}\.[a-f0-9]{64}$/', $cookie)) {
            return null;
        }
        [$selector, $validator] = explode('.', $cookie, 2);

        $row = RememberToken::findBySelector($selector);
        if ($row === null || (int) $row['expires_at'] < time()
            || !hash_equals($row['validator_hash'], hash('sha256', $validator))) {
            if ($row !== null) {
                RememberToken::delete($selector);
            }
            self::clearRememberCookie();
            return null;
        }

        $user = User::find((int) $row['user_id']);
        if ($user === null) {
            RememberToken::delete($selector);
            self::clearRememberCookie();
            return null;
        }

        Session::regenerate();
        Session::set('user_id', (int) $user['id']);
        return $user;
    }

    private static function setRememberCookie(string $value, int $expires): void
    {
        setcookie(self::REMEMBER_COOKIE, $value, [
            'expires'  => $expires,
            'path'     => '/',
            'secure'   => App::isHttps(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    private static function clearRememberCookie(): void
    {
        setcookie(self::REMEMBER_COOKIE, '', [
            'expires'  => time() - 3600,
            'path'     => '/',
            'secure'   => App::isHttps(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
}
