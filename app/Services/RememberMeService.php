<?php
// path: app/Services/RememberMeService.php

namespace App\Services;

use App\Core\Database;
use PDO;

class RememberMeService
{
    private const COOKIE_NAME = 'remember_token';
    private const LIFETIME_DAYS = 180; // 6 months

    /**
     * Create and set a persistent remember-me cookie and store hashed validator in the database.
     */
    public static function createToken(int $userId): void
    {
        self::ensureTableExists();

        $selector = bin2hex(random_bytes(16));
        $validator = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $validator);
        $expiresAt = date('Y-m-d H:i:s', time() + (86400 * self::LIFETIME_DAYS));

        $db = Database::getInstance();
        $stmt = $db->prepare("
            INSERT INTO user_remember_tokens (user_id, selector, token_hash, expires_at)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$userId, $selector, $tokenHash, $expiresAt]);

        self::setCookie("{$selector}:{$validator}", time() + (86400 * self::LIFETIME_DAYS));
    }

    /**
     * Verify persistent cookie and restore session if valid.
     * Rotates validator token upon successful verification.
     */
    public static function verifyAndLogin(): bool
    {
        if (!empty($_SESSION['user_id'])) {
            return true;
        }

        $cookie = $_COOKIE[self::COOKIE_NAME] ?? null;
        if (!$cookie || !str_contains($cookie, ':')) {
            return false;
        }

        [$selector, $validator] = explode(':', $cookie, 2);
        if (empty($selector) || empty($validator)) {
            self::removeToken();
            return false;
        }

        self::ensureTableExists();

        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT t.id, t.user_id, t.token_hash, t.expires_at, u.name, u.role
            FROM user_remember_tokens t
            JOIN users u ON u.id = t.user_id
            WHERE t.selector = ? AND t.expires_at > NOW()
            LIMIT 1
        ");
        $stmt->execute([$selector]);
        $tokenRecord = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$tokenRecord) {
            self::removeToken();
            return false;
        }

        $calcHash = hash('sha256', $validator);
        if (!hash_equals($tokenRecord['token_hash'], $calcHash)) {
            // Potential compromised token: invalidate selector
            $del = $db->prepare("DELETE FROM user_remember_tokens WHERE selector = ?");
            $del->execute([$selector]);
            self::removeToken();
            return false;
        }

        // Restore user session
        $_SESSION['user_id']   = (int)$tokenRecord['user_id'];
        $_SESSION['user_name'] = $tokenRecord['name'];
        $_SESSION['user_role'] = $tokenRecord['role'];

        // Token rotation: regenerate validator for next requests
        $newValidator = bin2hex(random_bytes(32));
        $newTokenHash = hash('sha256', $newValidator);

        $update = $db->prepare("UPDATE user_remember_tokens SET token_hash = ? WHERE id = ?");
        $update->execute([$newTokenHash, $tokenRecord['id']]);

        $cookieLifetime = strtotime($tokenRecord['expires_at']);
        self::setCookie("{$selector}:{$newValidator}", $cookieLifetime);

        return true;
    }

    /**
     * Remove remember-me token from DB and unset cookie.
     */
    public static function removeToken(): void
    {
        $cookie = $_COOKIE[self::COOKIE_NAME] ?? null;
        if ($cookie && str_contains($cookie, ':')) {
            [$selector] = explode(':', $cookie, 2);
            try {
                $db = Database::getInstance();
                $stmt = $db->prepare("DELETE FROM user_remember_tokens WHERE selector = ?");
                $stmt->execute([$selector]);
            } catch (\Throwable) {
                // Table might not exist or DB down during logout
            }
        }

        self::setCookie('', time() - 3600);
        unset($_COOKIE[self::COOKIE_NAME]);
    }

    /**
     * Helper to set cookie with secure flags.
     */
    private static function setCookie(string $value, int $expires): void
    {
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

        setcookie(self::COOKIE_NAME, $value, [
            'expires'  => $expires,
            'path'     => '/',
            'domain'   => '',
            'secure'   => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }

    /**
     * Automatically ensure that the remember-me table exists in the database.
     */
    private static function ensureTableExists(): void
    {
        try {
            $db = Database::getInstance();
            $db->exec("
                CREATE TABLE IF NOT EXISTS `user_remember_tokens` (
                    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `user_id` INT UNSIGNED NOT NULL,
                    `selector` VARCHAR(32) NOT NULL UNIQUE,
                    `token_hash` VARCHAR(64) NOT NULL,
                    `expires_at` DATETIME NOT NULL,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX `idx_selector` (`selector`),
                    INDEX `idx_user` (`user_id`),
                    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");
        } catch (\Throwable) {
            // Suppress if already exists or during early initialization
        }
    }
}