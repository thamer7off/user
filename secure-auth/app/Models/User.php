<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class User
{
    public static function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function findByEmail(string $email): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        return $stmt->fetch() ?: null;
    }

    public static function create(string $name, string $email, ?string $passwordHash, ?string $avatar, bool $verified): int
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO users (name, email, password_hash, avatar_url, email_verified_at)
             VALUES (?, ?, ?, ?, ' . ($verified ? 'NOW()' : 'NULL') . ')'
        );
        $stmt->execute([mb_substr($name, 0, 100), $email, $passwordHash, $avatar]);
        return (int) Database::pdo()->lastInsertId();
    }

    public static function setPassword(int $id, string $hash): void
    {
        $stmt = Database::pdo()->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
        $stmt->execute([$hash, $id]);
    }

    public static function markVerified(int $id): void
    {
        $stmt = Database::pdo()->prepare('UPDATE users SET email_verified_at = COALESCE(email_verified_at, NOW()) WHERE id = ?');
        $stmt->execute([$id]);
    }

    /** Used when a verified social login claims an email that was only ever registered locally. */
    public static function claimUnverified(int $id): void
    {
        $stmt = Database::pdo()->prepare('UPDATE users SET password_hash = NULL, email_verified_at = NOW() WHERE id = ?');
        $stmt->execute([$id]);
    }

    public static function setAvatarIfEmpty(int $id, ?string $avatar): void
    {
        if ($avatar === null) {
            return;
        }
        $stmt = Database::pdo()->prepare('UPDATE users SET avatar_url = COALESCE(avatar_url, ?) WHERE id = ?');
        $stmt->execute([$avatar, $id]);
    }
}
model kalna nhoto fih consturct wel getters wel setteres kahaw
tanrkhouh el code ana nahki al api kol ham rakhin