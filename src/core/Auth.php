<?php


final class Auth
{
    public static function requireLogin()
    {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login', true, 302);
            exit;
        }
    }

    public static function requireRole(array $roleIds)
    {
        self::requireLogin();

        $roleId = $_SESSION['role_id'] ?? null;
        if ($roleId === null || !in_array((int)$roleId, $roleIds, true)) {
            http_response_code(403);
            echo '403 Forbidden';
            exit;
        }
    }

    public static function userId()
    {
        return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
    }

    public static function roleId()
    {
        return isset($_SESSION['role_id']) ? (int)$_SESSION['role_id'] : null;
    }
}
