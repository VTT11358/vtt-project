<?php

require_once __DIR__ . "/../middleware/AuthMiddleware.php";
require_once __DIR__ . "/../models/User.php";
require_once __DIR__ . "/../helpers/GmailMailer.php";

class ProfileController
{
    private $userModel;

    public function __construct($conn)
    {
        AuthMiddleware::requireLogin();
        $this->userModel = new User($conn);

        if (empty($_SESSION["csrf_token"])) {
            $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
        }
    }

    public function index(): void
    {
        $userId = (int) ($_SESSION["user"]["id"] ?? 0);
        $profile = $this->userModel->getProfileById($userId);

        if (!$profile) {
            unset($_SESSION["user"]);
            $_SESSION["auth_error"] = "Không tìm thấy hồ sơ tài khoản. Vui lòng đăng nhập lại.";
            header("Location: " . BASE_URL . "index.php?controller=auth&action=login");
            exit;
        }

        $success = $_SESSION["profile_success"] ?? "";
        $error = $_SESSION["profile_error"] ?? "";
        unset($_SESSION["profile_success"], $_SESSION["profile_error"]);
        $pendingEmail = $this->userModel->getPendingEmailChange($userId);

        $pageTitle = "Hồ sơ cá nhân | Laptop Store";
        require __DIR__ . "/../views/profile/index.php";
    }

    public function update(): void
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            http_response_code(405);
            header("Allow: POST");
            exit("Phương thức không được hỗ trợ.");
        }

        if (!$this->checkCsrf()) {
            $_SESSION["profile_error"] = "Phiên xác thực không hợp lệ. Vui lòng thử lại.";
            $this->redirectToProfile();
        }

        $userId = (int) ($_SESSION["user"]["id"] ?? 0);
        $profile = $this->userModel->getProfileById($userId);
        if (!$profile) {
            unset($_SESSION["user"]);
            $_SESSION["auth_error"] = "Không tìm thấy hồ sơ tài khoản. Vui lòng đăng nhập lại.";
            header("Location: " . BASE_URL . "index.php?controller=auth&action=login");
            exit;
        }

        $name = $this->postText("name");
        $email = strtolower($this->postText("email"));
        $phone = $this->postText("phone");
        $address = $this->postText("address");
        $currentEmail = (string) $profile["email"];

        if (
            !$this->isValidText($name, 100)
            || !$this->isValidText($email, 150)
            || !$this->isValidText($phone, 20)
            || !$this->isValidText($address, 255)
            || $name === ""
            || !filter_var($email, FILTER_VALIDATE_EMAIL)
        ) {
            $_SESSION["profile_error"] =
                "Vui lòng kiểm tra lại họ tên, email, số điện thoại và địa chỉ.";
            $this->redirectToProfile();
        }

        $emailChanged = strcasecmp($email, $currentEmail) !== 0;
        try {
            if ($emailChanged && $this->userModel->emailIsInUseByAnotherUser($email, $userId)) {
                $_SESSION["profile_error"] = "Địa chỉ email này đã được sử dụng bởi tài khoản khác.";
                $this->redirectToProfile();
            }
            if ($emailChanged && $this->userModel->hasRecentEmailChangeToken($userId)) {
                $_SESSION["profile_error"] =
                    "Bạn vừa yêu cầu xác nhận email. Vui lòng chờ ít nhất 60 giây trước khi gửi lại.";
                $this->redirectToProfile();
            }

            $this->userModel->updateProfileDetails($userId, $name, $phone, $address);
            $_SESSION["user"]["name"] = $name;

            if (!$emailChanged) {
                $_SESSION["profile_success"] = "Thông tin cá nhân đã được cập nhật.";
                $this->redirectToProfile();
            }

            try {
                $this->sendEmailChangeVerificationLink($userId, $email, $name);
                $_SESSION["profile_success"] =
                    "Thông tin cá nhân đã được cập nhật. Hãy mở email mới để xác nhận thay đổi; email hiện tại vẫn được giữ cho đến khi xác nhận.";
            } catch (Throwable $e) {
                error_log("Profile email change request failed: " . $e->getMessage());
                $_SESSION["profile_error"] =
                    "Thông tin cá nhân đã được lưu nhưng không thể gửi email xác nhận. Vui lòng kiểm tra cấu hình Gmail SMTP và thử lại sau.";
            }
        } catch (Throwable $e) {
            error_log("Profile update failed: " . $e->getMessage());
            $_SESSION["profile_error"] = "Không thể cập nhật hồ sơ lúc này. Vui lòng thử lại.";
        }

        $this->redirectToProfile();
    }

    private function sendEmailChangeVerificationLink(
        int $userId,
        string $email,
        string $name
    ): void {
        $configPath = dirname(__DIR__, 2) . "/config/auth.local.php";
        if (!is_file($configPath)) {
            throw new RuntimeException("Thiếu file cấu hình xác thực.");
        }
        $config = require $configPath;
        if (!is_array($config) || empty($config["app_url"])) {
            throw new RuntimeException("Thiếu URL website trong cấu hình xác thực.");
        }

        $rawToken = bin2hex(random_bytes(32));
        $this->userModel->saveEmailChangeToken(
            $userId,
            $email,
            hash("sha256", $rawToken)
        );
        $verificationUrl = rtrim($config["app_url"], "/")
            . "/index.php?controller=auth&action=verifyEmailChange&token="
            . rawurlencode($rawToken);

        try {
            (new GmailMailer())->sendEmailChangeVerificationEmail(
                $config,
                $email,
                $name,
                $verificationUrl
            );
        } catch (Throwable $e) {
            try {
                $this->userModel->deleteEmailChangeToken($userId);
            } catch (Throwable $cleanupError) {
                error_log("Email change token cleanup failed: " . $cleanupError->getMessage());
            }
            throw $e;
        }
    }

    private function postText(string $field): string
    {
        $value = $_POST[$field] ?? "";
        return is_string($value) ? trim($value) : "";
    }

    private function isValidText(string $value, int $maxLength): bool
    {
        if (preg_match('//u', $value) !== 1) {
            return false;
        }
        $length = function_exists("mb_strlen")
            ? mb_strlen($value, "UTF-8")
            : preg_match_all('/./us', $value);
        return $length !== false && $length <= $maxLength;
    }

    private function checkCsrf(): bool
    {
        $token = $_POST["csrf_token"] ?? "";
        return is_string($token)
            && isset($_SESSION["csrf_token"])
            && hash_equals($_SESSION["csrf_token"], $token);
    }

    private function redirectToProfile(): void
    {
        header("Location: " . BASE_URL . "index.php?controller=profile&action=index");
        exit;
    }
}
