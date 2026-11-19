<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class PasswordReset
{
    /** Replaces any earlier reset links for this user. */
    public static function create(int $userId, string $tokenHash, int $expiresAt): void
    {
        self::deleteForUser($userId);
        $stmt = Database::pdo()->prepare('INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (?, ?, ?)');
        $stmt->execute([$userId, $tokenHash, $expiresAt]);
    }

    public static function findValid(string $tokenHash): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM password_resets WHERE token_hash = ? AND expires_at > ?');
        $stmt->execute([$tokenHash, time()]);
        return $stmt->fetch() ?: null;
    }

    public static function deleteForUser(int $userId): void
    {
        $stmt = Database::pdo()->prepare('DELETE FROM password_resets WHERE user_id = ?');
        $stmt->execute([$userId]);
    }
}
