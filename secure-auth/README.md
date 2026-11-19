# Secure Auth (PHP · MVC · HTML/CSS)

Login system with Google, GitHub and Discord sign-in, email + password, "keep me signed in",
and password reset emails sent through Brevo. No Composer packages needed.

## Requirements
PHP 8.1+ (extensions: pdo_mysql, curl, mbstring) and MySQL/MariaDB.

## Setup
1. **Database:** `mysql -u root -p < database/schema.sql`
2. **Config:** copy `.env.example` to `.env` and fill in the values (see below).
3. **Run:** `php -S localhost:8000 -t public`, then open http://localhost:8000
   On Apache/Nginx, point the document root at the `public/` folder.
   If the app lives in a subfolder, set `APP_URL` to include it (e.g. `http://localhost/secure-auth/public`).

## Provider setup
Redirect URIs must match exactly. Replace `APP_URL` with your own.

| Provider | Where | Redirect URI |
|---|---|---|
| Google | console.cloud.google.com → APIs & Services → Credentials → OAuth client ID (Web). Set up the consent screen first. | `APP_URL/auth/google/callback` |
| GitHub | Settings → Developer settings → OAuth Apps | `APP_URL/auth/github/callback` |
| Discord | discord.com/developers → your app → OAuth2 → Redirects | `APP_URL/auth/discord/callback` |

Put each Client ID/Secret in `.env`. A provider with empty values is hidden automatically.

## Brevo (password reset emails)
1. Create a free account at brevo.com → SMTP & API → create an **API key** → `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`.
2. Add and verify your sender address (Senders, Domains & Dedicated IPs) → `MAIL_FROM_ADDRESS`.
3. For production, authenticate your domain (SPF/DKIM) so emails don't land in spam.

While developing with `APP_DEBUG=true` and no API key, the reset link is written to the PHP error log.

## Structure
```
app/Controllers   Auth, OAuth, Password, Dashboard
app/Models        User, OAuthAccount, PasswordReset, RememberToken
app/Views         layout, auth/*, dashboard, partials
app/Services      Auth, OAuthService, BrevoMailer, RateLimiter
app/Core          Router, Controller, View, Session, Csrf, Database, Env, Http
public/           index.php (front controller), css/, js/
```

## Security built in
- Passwords hashed with `password_hash`; login timing doesn't reveal unknown emails.
- CSRF token on every POST; OAuth `state` + PKCE.
- Session ID regenerated on login; cookies are HttpOnly, SameSite=Lax (Secure on HTTPS).
- "Keep me signed in": random selector + hashed validator stored in the database (30 days).
- Reset tokens: random, stored hashed, single-use, expire in 60 minutes; same response for known and unknown emails.
- Rate limiting on sign-in, sign-up and reset requests.
- Social login only links to an existing account when the provider confirms the email. If that account was never verified, its password is removed, which stops pre-registration takeover.
- CSP and other security headers on every response.

## Known limits / next steps
- Sign-up doesn't verify email ownership yet. Registering with an existing email reveals that it exists.
- Resetting a password signs out "keep me signed in" devices, but not other active PHP sessions.
- Behind a reverse proxy, make the server pass the real client IP as `REMOTE_ADDR` (rate limiting relies on it).
- Apple and X (Twitter) sign-in are not included (Apple needs a paid developer account; X API is no longer free).

## Troubleshooting
- **cURL SSL error on Windows/XAMPP:** set `curl.cainfo` in php.ini to a CA bundle (cacert.pem).
- **Brevo 401 / "sender not valid":** check the API key and that the sender is verified.
- **`redirect_uri_mismatch`:** the URI in the provider console must equal `APP_URL` + the callback path.
