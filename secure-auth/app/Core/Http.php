<?php
declare(strict_types=1);

namespace App\Core;

use RuntimeException;

final class Http
{
    /**
     * @return array{0:int,1:?array} HTTP status and decoded JSON body (null if not JSON)
     */
    public static function request(string $method, string $url, array $headers = [], array|string|null $body = null): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_USERAGENT      => 'SecureAuth/1.0 (PHP)',
            CURLOPT_HTTPHEADER     => $headers,
        ]);
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, is_array($body) ? http_build_query($body) : $body);
        }
        $raw = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($raw === false) {
            throw new RuntimeException('HTTP request failed: ' . $error);
        }
        $json = json_decode((string) $raw, true);
        return [$status, is_array($json) ? $json : null];
    }
}
