<?php

class AuthMiddleware
{
    public static function requireLogin()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user'])) {
            header(
                "Location: " . BASE_URL .
                "index.php?controller=auth&action=login"
            );
            exit;
        }
    }

    public static function requireAdmin()
    {
        self::requireLogin();

        if (($_SESSION['user']['role'] ?? '') !== 'admin') {
            http_response_code(403);
            exit("403 - Bạn không có quyền truy cập trang này.");
        }
    }
}
