<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class OAuthAccount
{
    public static function find(string $provider, string $providerUserId): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM oauth_accounts WHERE provider = ? AND provider_user_id = ?');
        $stmt->execute([$provider, $providerUserId]);
        return $stmt->fetch() ?: null;
    }

    public static function link(int $userId, string $provider, string $providerUserId): void
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO oauth_accounts (user_id, provider, provider_user_id) VALUES (?, ?, ?)'
        );
        $stmt->execute([$userId, $provider, $providerUserId]);
    }

    /** @return string[] */
    public static function providersFor(int $userId): array
    {
        $stmt = Database::pdo()->prepare('SELECT provider FROM oauth_accounts WHERE user_id = ? ORDER BY provider');
        $stmt->execute([$userId]);
        return array_column($stmt->fetchAll(), 'provider');
    }
}
