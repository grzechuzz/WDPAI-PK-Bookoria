<?php

require_once __DIR__ . '/Config.php';


final class Auth
{
    public static function requireLogin(): void
    {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login', true, 302);
            exit;
        }
    }

    public static function requireRole(array $roleIds): void
    {
        self::requireLogin();

        $roleId = $_SESSION['role_id'] ?? null;
        if ($roleId === null || !in_array((int)$roleId, $roleIds, true)) {
            http_response_code(403);
            echo '403 Forbidden';
            exit;
        }
    }

    public static function requireAdmin(): void
    {
        self::requireRole([Config::ROLE_ADMIN]);
    }

    public static function requireLibrarian(): void
    {
        self::requireRole([Config::ROLE_LIBRARIAN]);
    }

    public static function requireReader(): void
    {
        self::requireRole([Config::ROLE_READER]);
    }

    public static function userId(): ?int
    {
        return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
    }

    public static function roleId(): ?int
    {
        return isset($_SESSION['role_id']) ? (int)$_SESSION['role_id'] : null;
    }

   
    public static function isLoggedIn(): bool
    {
        return isset($_SESSION['user_id']);
    }

    public static function isAdmin(): bool
    {
        return Config::isAdmin(self::roleId());
    }

    public static function isLibrarian(): bool
    {
        return Config::isLibrarian(self::roleId());
    }

    public static function isReader(): bool
    {
        return Config::isReader(self::roleId());
    }
}