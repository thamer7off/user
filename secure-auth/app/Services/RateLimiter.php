<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

final class RateLimiter
{
    private static function key(string $bucket): string
    {
        return hash('sha256', $bucket);
    }

    public static function hit(string $bucket): void
    {
        $stmt = Database::pdo()->prepare('INSERT INTO rate_limits (bucket, created_at) VALUES (?, ?)');
        $stmt->execute([self::key($bucket), time()]);

        if (random_int(1, 100) === 1) { // occasional cleanup
            Database::pdo()->prepare('DELETE FROM rate_limits WHERE created_at < ?')->execute([time() - 86400]);
        }
    }

    public static function tooMany(string $bucket, int $max, int $windowSeconds): bool
    {
        $stmt = Database::pdo()->prepare('SELECT COUNT(*) FROM rate_limits WHERE bucket = ? AND created_at > ?');
        $stmt->execute([self::key($bucket), time() - $windowSeconds]);
        return (int) $stmt->fetchColumn() >= $max;
    }

    public static function clear(string $bucket): void
    {
        Database::pdo()->prepare('DELETE FROM rate_limits WHERE bucket = ?')->execute([self::key($bucket)]);
    }
}
