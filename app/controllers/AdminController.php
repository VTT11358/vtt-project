
<?php

require_once __DIR__ . "/../middleware/AuthMiddleware.php";

class AdminController
{
    public function __construct($conn)
    {
        AuthMiddleware::requireAdmin();

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION["csrf_token"])) {
            $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
        }
    }

    public function index()
    {
        header(
            "Location: " . BASE_URL .
            "index.php?controller=adminOrder&action=stats"
        );
        exit;
    }
}
