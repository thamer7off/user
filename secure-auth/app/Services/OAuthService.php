<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\App;
use App\Core\Env;
use App\Core\Http;
use App\Core\Session;

final class OAuthService
{
    private const PROVIDERS = [
        'google' => [
            'label'     => 'Google',
            'authorize' => 'https://accounts.google.com/o/oauth2/v2/auth',
            'token'     => 'https://oauth2.googleapis.com/token',
            'scope'     => 'openid email profile',
            'extra'     => ['prompt' => 'select_account'],
        ],
        'github' => [
            'label'     => 'GitHub',
            'authorize' => 'https://github.com/login/oauth/authorize',
            'token'     => 'https://github.com/login/oauth/access_token',
            'scope'     => 'read:user user:email',
            'extra'     => [],
        ],
        'discord' => [
            'label'     => 'Discord',
            'authorize' => 'https://discord.com/oauth2/authorize',
            'token'     => 'https://discord.com/api/oauth2/token',
            'scope'     => 'identify email',
            'extra'     => [],
        ],
    ];

    /** Providers that have credentials in .env. @return array<string,array{label:string}> */
    public static function enabled(): array
    {
        $enabled = [];
        foreach (self::PROVIDERS as $key => $cfg) {
            if (self::clientId($key) && self::clientSecret($key)) {
                $enabled[$key] = ['label' => $cfg['label']];
            }
        }
        return $enabled;
    }

    public static function isEnabled(string $provider): bool
    {
        return isset(self::enabled()[$provider]);
    }

    public static function label(string $provider): string
    {
        return self::PROVIDERS[$provider]['label'] ?? ucfirst($provider);
    }

    private static function clientId(string $p): ?string
    {
        return Env::get(strtoupper($p) . '_CLIENT_ID');
    }

    private static function clientSecret(string $p): ?string
    {
        return Env::get(strtoupper($p) . '_CLIENT_SECRET');
    }

    public static function redirectUri(string $provider): string
    {
        return App::url('/auth/' . $provider . '/callback');
    }

    private static function b64url(string $bin): string
    {
        return rtrim(strtr(base64_encode($bin), '+/', '-_'), '=');
    }

    public static function authorizationUrl(string $provider, bool $remember): string
    {
        $cfg = self::PROVIDERS[$provider];
        $state = bin2hex(random_bytes(16));
        $verifier = self::b64url(random_bytes(48));

        Session::set('oauth', [
            'provider' => $provider,
            'state'    => $state,
            'verifier' => $verifier,
            'remember' => $remember,
        ]);

        $params = [
            'response_type'         => 'code',
            'client_id'             => self::clientId($provider),
            'redirect_uri'          => self::redirectUri($provider),
            'scope'                 => $cfg['scope'],
            'state'                 => $state,
            'code_challenge'        => self::b64url(hash('sha256', $verifier, true)),
            'code_challenge_method' => 'S256',
        ] + $cfg['extra'];

        return $cfg['authorize'] . '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * Validates state, exchanges the code and loads the user's profile.
     * @return array{profile:array,remember:bool}
     */
    public static function handleCallback(string $provider, array $query): array
    {
        $saved = Session::pull('oauth');
        if (!is_array($saved) || ($saved['provider'] ?? null) !== $provider) {
            throw new OAuthException('Your sign-in session expired. Please try again.');
        }
        if (isset($query['error'])) {
            throw new OAuthException('Sign-in with ' . self::label($provider) . ' was cancelled.');
        }
        $state = $query['state'] ?? '';
        $code = $query['code'] ?? '';
        if (!is_string($state) || !is_string($code) || $code === '' || !hash_equals((string) $saved['state'], $state)) {
            throw new OAuthException('We could not verify that sign-in attempt. Please try again.');
        }

        $token = self::exchangeCode($provider, $code, (string) $saved['verifier']);
        return ['profile' => self::fetchProfile($provider, $token), 'remember' => (bool) $saved['remember']];
    }

    private static function exchangeCode(string $provider, string $code, string $verifier): string
    {
        [$status, $data] = Http::request('POST', self::PROVIDERS[$provider]['token'], ['Accept: application/json'], [
            'grant_type'    => 'authorization_code',
            'code'          => $code,
            'redirect_uri'  => self::redirectUri($provider),
            'client_id'     => self::clientId($provider),
            'client_secret' => self::clientSecret($provider),
            'code_verifier' => $verifier,
        ]);
        if ($status !== 200 || !isset($data['access_token']) || !is_string($data['access_token'])) {
            error_log(sprintf('OAuth token exchange failed for %s: HTTP %d %s', $provider, $status, json_encode($data['error'] ?? null)));
            throw new OAuthException('Could not complete sign-in with ' . self::label($provider) . '. Please try again.');
        }
        return $data['access_token'];
    }

    /** @return array{id:string,email:?string,verified:bool,name:string,avatar:?string} */
    private static function fetchProfile(string $provider, string $token): array
    {
        $bearer = 'Authorization: Bearer ' . $token;

        if ($provider === 'google') {
            $d = self::getJson('https://openidconnect.googleapis.com/v1/userinfo', [$bearer, 'Accept: application/json']);
            return [
                'id'       => (string) ($d['sub'] ?? ''),
                'email'    => $d['email'] ?? null,
                'verified' => filter_var($d['email_verified'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'name'     => (string) ($d['name'] ?? ''),
                'avatar'   => $d['picture'] ?? null,
            ];
        }

        if ($provider === 'github') {
            $headers = [$bearer, 'Accept: application/vnd.github+json', 'X-GitHub-Api-Version: 2022-11-28', 'User-Agent: ' . app_name()];
            $u = self::getJson('https://api.github.com/user', $headers);
            $email = null;
            // /user may hide the email; /user/emails tells us which ones are verified.
            [$status, $emails] = Http::request('GET', 'https://api.github.com/user/emails', $headers);
            if ($status === 200 && is_array($emails)) {
                foreach ($emails as $row) {
                    if (!empty($row['verified']) && !empty($row['primary'])) {
                        $email = $row['email'];
                        break;
                    }
                }
                if ($email === null) {
                    foreach ($emails as $row) {
                        if (!empty($row['verified'])) {
                            $email = $row['email'];
                            break;
                        }
                    }
                }
            }
            return [
                'id'       => (string) ($u['id'] ?? ''),
                'email'    => $email,
                'verified' => $email !== null,
                'name'     => (string) (($u['name'] ?? '') ?: ($u['login'] ?? '')),
                'avatar'   => $u['avatar_url'] ?? null,
            ];
        }

        // discord
        $d = self::getJson('https://discord.com/api/users/@me', [$bearer, 'Accept: application/json']);
        $avatar = (!empty($d['avatar']) && !empty($d['id']))
            ? 'https://cdn.discordapp.com/avatars/' . $d['id'] . '/' . $d['avatar'] . '.png?size=128'
            : null;
        return [
            'id'       => (string) ($d['id'] ?? ''),
            'email'    => $d['email'] ?? null,
            'verified' => ($d['verified'] ?? false) === true,
            'name'     => (string) (($d['global_name'] ?? '') ?: ($d['username'] ?? '')),
            'avatar'   => $avatar,
        ];
    }

    private static function getJson(string $url, array $headers): array
    {
        [$status, $data] = Http::request('GET', $url, $headers);
        if ($status !== 200 || $data === null) {
            error_log("OAuth profile request failed: HTTP $status for $url");
            throw new OAuthException('Could not read your profile from the provider. Please try again.');
        }
        return $data;
    }
}
