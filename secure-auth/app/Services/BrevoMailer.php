<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\App;
use App\Core\Env;
use App\Core\Http;

final class BrevoMailer
{
    private const ENDPOINT = 'https://api.brevo.com/v3/smtp/email';

    public static function send(string $toEmail, string $toName, string $subject, string $html, string $text): bool
    {
        $apiKey = Env::get('BREVO_API_KEY');
        $from = Env::get('MAIL_FROM_ADDRESS');
        if (!$apiKey || !$from) {
            error_log('Brevo is not configured (BREVO_API_KEY / MAIL_FROM_ADDRESS). Email not sent: ' . $subject);
            error_log('Sending to ' . $toEmail . ' from ' . $from);
            return false;
        }

        try {
            [$status, $body] = Http::request('POST', self::ENDPOINT, [
                'api-key: ' . $apiKey,
                'accept: application/json',
                'content-type: application/json',
            ], json_encode([
                'sender'      => ['name' => Env::get('MAIL_FROM_NAME', app_name()), 'email' => $from],
                'to'          => [['email' => $toEmail, 'name' => $toName]],
                'subject'     => $subject,
                'htmlContent' => $html,
                'textContent' => $text,
            ], JSON_UNESCAPED_UNICODE));
        } catch (\Throwable $e) {
            error_log('Brevo request failed: ' . $e->getMessage());
            return false;
        }

        if ($status < 200 || $status >= 300) {
            error_log('Brevo error ' . $status . ': ' . json_encode($body));
            return false;
        }
        return true;
    }

    public static function sendPasswordReset(array $user, string $link, int $minutes): bool
    {
        if (!Env::get('BREVO_API_KEY') && App::debug()) {
            error_log('[dev] Password reset link for ' . $user['email'] . ': ' . $link);
        }
        $app = app_name();
        $name = e($user['name']);
        $safeLink = e($link);

        $html = <<<HTML
<div style="font-family:Arial,Helvetica,sans-serif;max-width:480px;margin:0 auto;padding:24px;color:#141B2D">
  <h2 style="margin:0 0 16px">Reset your password</h2>
  <p>Hi {$name},</p>
  <p>We received a request to reset the password for your {$app} account. This link works for {$minutes} minutes.</p>
  <p style="margin:28px 0"><a href="{$safeLink}" style="background:#2F4BFF;color:#ffffff;text-decoration:none;padding:12px 22px;border-radius:8px;font-weight:bold;display:inline-block">Choose a new password</a></p>
  <p style="font-size:13px;color:#5A6478">If the button doesn't work, copy this link into your browser:<br>{$safeLink}</p>
  <p style="font-size:13px;color:#5A6478">Didn't ask for this? You can ignore this email. Your password won't change.</p>
</div>
HTML;
        $text = "Hi {$user['name']},\n\nReset your {$app} password using this link (valid for {$minutes} minutes):\n{$link}\n\n"
              . "Didn't ask for this? Ignore this email and your password stays the same.";

        return self::send($user['email'], $user['name'], 'Reset your ' . $app . ' password', $html, $text);
    }

    public static function sendPasswordChanged(array $user): bool
    {
        $app = app_name();
        $name = e($user['name']);
        $html = '<div style="font-family:Arial,Helvetica,sans-serif;max-width:480px;margin:0 auto;padding:24px;color:#141B2D">'
              . '<h2 style="margin:0 0 16px">Your password was changed</h2>'
              . "<p>Hi {$name},</p><p>The password for your {$app} account was just changed. "
              . "If this was you, no action is needed.</p>"
              . '<p>If it wasn\'t you, reset your password right away using the "Forgot password?" link on the sign-in page.</p></div>';
        $text = "Hi {$user['name']},\n\nThe password for your {$app} account was just changed. If this wasn't you, reset it right away from the sign-in page.";

        return self::send($user['email'], $user['name'], 'Your ' . $app . ' password was changed', $html, $text);
    }
}
