<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class RememberToken
{
    public static function create(int $userId, string $selector, string $validatorHash, int $expiresAt): void
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO remember_tokens (user_id, selector, validator_hash, expires_at) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$userId, $selector, $validatorHash, $expiresAt]);
    }

    public static function findBySelector(string $selector): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM remember_tokens WHERE selector = ?');
        $stmt->execute([$selector]);
        return $stmt->fetch() ?: null;
    }

    public static function delete(string $selector): void
    {
        $stmt = Database::pdo()->prepare('DELETE FROM remember_tokens WHERE selector = ?');
        $stmt->execute([$selector]);
    }

    public static function deleteForUser(int $userId): void
    {
        $stmt = Database::pdo()->prepare('DELETE FROM remember_tokens WHERE user_id = ?');
        $stmt->execute([$userId]);
    }

    public static function purgeExpired(): void
    {
        $stmt = Database::pdo()->prepare('DELETE FROM remember_tokens WHERE expires_at < ?');
        $stmt->execute([time()]);
    }
}
