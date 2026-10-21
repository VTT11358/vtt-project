<?php

require_once __DIR__ . "/../models/User.php";
require_once __DIR__ . "/../models/Cart.php";
require_once __DIR__ . "/../helpers/GmailMailer.php";

class AuthController
{
    private $userModel;
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
        $this->userModel = new User($conn);

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION["csrf_token"])) {
            $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
        }
    }

    public function register(): void
    {
        $error = "";
        $old = [
            "name" => "",
            "email" => "",
            "phone" => "",
            "address" => "",
        ];

        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            if (!$this->checkCsrf()) {
                $error = "Yêu cầu không hợp lệ. Vui lòng tải lại trang.";
            } else {
                foreach (["name", "email", "phone", "address"] as $field) {
                    $value = $_POST[$field] ?? "";
                    $old[$field] = is_string($value) ? trim($value) : "";
                }
                $password = $_POST["password"] ?? "";
                $confirmPassword = $_POST["confirm_password"] ?? "";
                if (!is_string($password) || !is_string($confirmPassword)) {
                    $password = "";
                    $confirmPassword = "";
                }

                if (
                    $old["name"] === ""
                    || $old["email"] === ""
                    || $password === ""
                    || $confirmPassword === ""
                ) {
                    $error = "Vui lòng nhập đầy đủ thông tin bắt buộc.";
                } elseif (!filter_var($old["email"], FILTER_VALIDATE_EMAIL)) {
                    $error = "Email không đúng định dạng.";
                } elseif (strlen($password) < 8) {
                    $error = "Mật khẩu phải có ít nhất 8 ký tự.";
                } elseif ($password !== $confirmPassword) {
                    $error = "Mật khẩu xác nhận không khớp.";
                } else {
                    $existingUser = $this->userModel->findByEmail($old["email"]);
                    if ($existingUser && (int) ($existingUser["email_verified"] ?? 1) === 0) {
                        $error =
                            "Email này đã đăng ký nhưng chưa xác nhận. Hãy đăng nhập để gửi lại liên kết xác nhận.";
                    } elseif ($existingUser) {
                        $error = "Email này đã được đăng ký.";
                    } else {
                        $created = $this->userModel->create(
                            $old["name"],
                            $old["email"],
                            password_hash($password, PASSWORD_DEFAULT),
                            $old["phone"],
                            $old["address"],
                            false
                        );

                        if ($created) {
                            $user = $this->userModel->findByEmail($old["email"]);
                            try {
                                if (!$user) {
                                    throw new RuntimeException("Không thể tải tài khoản vừa tạo.");
                                }
                                $this->sendVerificationLink($user);
                                $_SESSION["auth_success"] =
                                    "Đăng ký thành công. Hãy mở email và nhấn liên kết xác nhận trước khi đăng nhập.";
                            } catch (Throwable $e) {
                                error_log("Email verification send failed: " . $e->getMessage());
                                $_SESSION["auth_success"] =
                                    "Tài khoản đã được tạo nhưng email xác nhận chưa gửi được. Hãy đăng nhập bằng email để gửi lại liên kết.";
                            }
                            $_SESSION["auth_email"] = $old["email"];
                            $this->redirectToLogin();
                        }

                        $error = "Không thể tạo tài khoản. Vui lòng thử lại.";
                    }
                }
            }
        }

        require __DIR__ . "/../views/auth/register.php";
    }

    public function login(): void
    {
        $error = "";
        $email = $_SESSION["auth_email"] ?? "";
        unset($_SESSION["auth_email"]);
        $showResendVerification = false;

        if (!empty($_SESSION["user"])) {
            header("Location: " . BASE_URL);
            exit;
        }

        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            if (!$this->checkCsrf()) {
                $error = "Yêu cầu không hợp lệ. Vui lòng tải lại trang.";
            } else {
                $emailInput = $_POST["email"] ?? "";
                $email = is_string($emailInput) ? trim($emailInput) : "";
                $password = $_POST["password"] ?? "";
                if (!is_string($password)) {
                    $password = "";
                }
                $user = $this->userModel->findByEmail($email);

                if (!$user || !password_verify($password, $user["password"])) {
                    $error = "Email hoặc mật khẩu không chính xác.";
                } elseif ((int) $user["status"] !== 1) {
                    $error = "Tài khoản đã bị khóa.";
                } elseif ((int) ($user["email_verified"] ?? 1) !== 1) {
                    $error = "Vui lòng xác nhận email qua liên kết đã gửi trước khi đăng nhập.";
                    $showResendVerification = true;
                } else {
                    $this->completeLogin($user);
                }
            }
        } elseif ($email !== "") {
            $user = $this->userModel->findByEmail($email);
            $showResendVerification = $user
                && (int) ($user["email_verified"] ?? 1) !== 1;
        }

        $success = $_SESSION["auth_success"] ?? "";
        unset($_SESSION["auth_success"]);
        if (!empty($_SESSION["auth_error"])) {
            $error = $_SESSION["auth_error"];
            unset($_SESSION["auth_error"]);
        }

        require __DIR__ . "/../views/auth/login.php";
    }

    public function forgotPassword(): void
    {
        if (!empty($_SESSION["user"])) {
            header("Location: " . BASE_URL . "index.php?controller=profile&action=index");
            exit;
        }

        $success = $_SESSION["password_reset_success"] ?? "";
        $error = $_SESSION["password_reset_error"] ?? "";
        $email = $_SESSION["password_reset_email"] ?? "";
        unset(
            $_SESSION["password_reset_success"],
            $_SESSION["password_reset_error"],
            $_SESSION["password_reset_email"]
        );
        $pageTitle = "Quên mật khẩu | Laptop Store";
        require __DIR__ . "/../views/auth/forgot-password.php";
    }

    public function requestPasswordReset(): void
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            http_response_code(405);
            header("Allow: POST");
            exit("Phương thức không được hỗ trợ.");
        }

        if (!$this->checkCsrf()) {
            $_SESSION["password_reset_error"] = "Phiên xác thực không hợp lệ. Vui lòng thử lại.";
            $this->redirectToForgotPassword();
        }

        $emailInput = $_POST["email"] ?? "";
        $email = is_string($emailInput) ? trim($emailInput) : "";
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION["password_reset_error"] = "Vui lòng nhập địa chỉ email hợp lệ.";
            $_SESSION["password_reset_email"] = $email;
            $this->redirectToForgotPassword();
        }

        try {
            $user = $this->userModel->findByEmail($email);
            if (
                $user
                && (int) ($user["status"] ?? 0) === 1
                && (int) ($user["email_verified"] ?? 1) === 1
                && !$this->userModel->hasRecentPasswordResetToken((int) $user["id"])
            ) {
                $this->sendPasswordResetLink($user);
            }
        } catch (Throwable $e) {
            error_log("Password reset request failed: " . $e->getMessage());
        }

        $_SESSION["password_reset_success"] =
            "Nếu email thuộc tài khoản đang hoạt động, hướng dẫn đặt lại mật khẩu sẽ được gửi tới hộp thư. Vui lòng kiểm tra cả mục thư rác.";
        $this->redirectToForgotPassword();
    }

    public function requestPasswordChange(): void
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            http_response_code(405);
            header("Allow: POST");
            exit("Phương thức không được hỗ trợ.");
        }

        if (empty($_SESSION["user"]["id"])) {
            $this->redirectToLogin();
        }
        if (!$this->checkCsrf()) {
            $_SESSION["profile_error"] = "Phiên xác thực không hợp lệ. Vui lòng thử lại.";
            $this->redirectToProfile();
        }

        try {
            $user = $this->userModel->findById((int) $_SESSION["user"]["id"]);
            if (!$user || (int) ($user["status"] ?? 0) !== 1) {
                throw new RuntimeException("Không tìm thấy tài khoản đang hoạt động.");
            }
            if ((int) ($user["email_verified"] ?? 1) !== 1) {
                throw new RuntimeException("Email cần được xác nhận trước khi đặt lại mật khẩu.");
            }
            if ($this->userModel->hasRecentPasswordResetToken((int) $user["id"])) {
                $_SESSION["profile_success"] =
                    "Email đặt lại mật khẩu vừa được gửi. Vui lòng chờ ít nhất 60 giây trước khi gửi lại.";
            } else {
                $this->sendPasswordResetLink($user);
                $_SESSION["profile_success"] =
                    "Đã gửi liên kết đặt lại mật khẩu tới email của bạn. Liên kết có hiệu lực trong 1 giờ.";
            }
        } catch (Throwable $e) {
            error_log("Profile password reset request failed: " . $e->getMessage());
            $_SESSION["profile_error"] =
                "Không thể gửi email đặt lại mật khẩu. Vui lòng kiểm tra cấu hình Gmail SMTP và thử lại.";
        }

        $this->redirectToProfile();
    }

    public function resetPassword(): void
    {
        $token = $_SERVER["REQUEST_METHOD"] === "POST"
            ? ($_POST["token"] ?? "")
            : ($_GET["token"] ?? "");
        $token = is_string($token) ? $token : "";
        $error = "";
        $success = "";
        $tokenValid = preg_match('/\A[a-f0-9]{64}\z/', $token) === 1;

        if (!$tokenValid) {
            $error = "Liên kết đặt lại mật khẩu không hợp lệ hoặc đã hết hạn.";
        } elseif ($_SERVER["REQUEST_METHOD"] === "POST") {
            if (!$this->checkCsrf()) {
                $error = "Phiên xác thực không hợp lệ. Vui lòng tải lại trang.";
            } else {
                $password = $_POST["password"] ?? "";
                $confirmPassword = $_POST["confirm_password"] ?? "";
                if (!is_string($password) || !is_string($confirmPassword)) {
                    $password = "";
                    $confirmPassword = "";
                }

                if (strlen($password) < 8) {
                    $error = "Mật khẩu phải có ít nhất 8 ký tự.";
                } elseif ($password !== $confirmPassword) {
                    $error = "Mật khẩu xác nhận không khớp.";
                } else {
                    try {
                        $updatedUserId = $this->userModel->updatePasswordWithResetToken(
                            hash("sha256", $token),
                            password_hash($password, PASSWORD_DEFAULT)
                        );
                        if ($updatedUserId !== null) {
                            if (
                                !empty($_SESSION["user"]["id"])
                                && (int) $_SESSION["user"]["id"] === $updatedUserId
                            ) {
                                $_SESSION["profile_success"] =
                                    "Mật khẩu đã được thay đổi thành công.";
                                $this->redirectToProfile();
                            }
                            $_SESSION["auth_success"] =
                                "Mật khẩu đã được thay đổi. Vui lòng đăng nhập bằng mật khẩu mới.";
                            if (!empty($_SESSION["user"])) {
                                unset($_SESSION["user"]);
                                session_regenerate_id(true);
                            }
                            $this->redirectToLogin();
                        }
                        $error = "Liên kết đặt lại mật khẩu không hợp lệ hoặc đã hết hạn.";
                        $tokenValid = false;
                    } catch (Throwable $e) {
                        error_log("Password reset completion failed: " . $e->getMessage());
                        $error = "Không thể cập nhật mật khẩu lúc này. Vui lòng thử lại.";
                    }
                }
            }
        } else {
            try {
                $tokenValid = $this->userModel->isPasswordResetTokenValid(
                    hash("sha256", $token)
                );
                if (!$tokenValid) {
                    $error = "Liên kết đặt lại mật khẩu không hợp lệ hoặc đã hết hạn.";
                }
            } catch (Throwable $e) {
                error_log("Password reset token validation failed: " . $e->getMessage());
                $error = "Không thể kiểm tra liên kết lúc này. Vui lòng thử lại.";
                $tokenValid = false;
            }
        }

        $pageTitle = "Đặt lại mật khẩu | Laptop Store";
        require __DIR__ . "/../views/auth/reset-password.php";
    }

    public function googleLogin(): void
    {
        try {
            $config = $this->authConfig();
            $this->requireGoogleConfig($config);
            $state = bin2hex(random_bytes(32));
            $nonce = bin2hex(random_bytes(32));
            $verifier = $this->base64UrlEncode(random_bytes(32));
            $_SESSION["google_oauth"] = [
                "state" => $state,
                "nonce" => $nonce,
                "verifier" => $verifier,
                "created_at" => time(),
            ];

            $query = http_build_query([
                "client_id" => $config["google_client_id"],
                "redirect_uri" => $config["google_redirect_uri"],
                "response_type" => "code",
                "scope" => "openid email profile",
                "state" => $state,
                "nonce" => $nonce,
                "code_challenge" => $this->base64UrlEncode(hash("sha256", $verifier, true)),
                "code_challenge_method" => "S256",
                "prompt" => "select_account",
            ]);
            header("Location: https://accounts.google.com/o/oauth2/v2/auth?" . $query);
            exit;
        } catch (Throwable $e) {
            error_log("Google OAuth start failed: " . $e->getMessage());
            $_SESSION["auth_error"] =
                "Không thể bắt đầu đăng nhập Google. Vui lòng kiểm tra cấu hình OAuth.";
            $this->redirectToLogin();
        }
    }

    public function googleCallback(): void
    {
        $oauth = $_SESSION["google_oauth"] ?? null;
        unset($_SESSION["google_oauth"]);
        $state = $_GET["state"] ?? "";
        $code = $_GET["code"] ?? "";

        if (
            !is_array($oauth)
            || !is_string($state)
            || !hash_equals($oauth["state"], $state)
            || time() - (int) $oauth["created_at"] > 600
            || !is_string($code)
            || $code === ""
            || isset($_GET["error"])
        ) {
            $_SESSION["auth_error"] =
                "Đăng nhập Google bị hủy hoặc phiên xác thực đã hết hạn. Vui lòng thử lại.";
            $this->redirectToLogin();
        }

        try {
            $config = $this->authConfig();
            $this->requireGoogleConfig($config);
            $tokens = $this->googleRequest(
                "https://oauth2.googleapis.com/token",
                [
                    "code" => $code,
                    "client_id" => $config["google_client_id"],
                    "client_secret" => $config["google_client_secret"],
                    "redirect_uri" => $config["google_redirect_uri"],
                    "grant_type" => "authorization_code",
                    "code_verifier" => $oauth["verifier"],
                ]
            );
            if (empty($tokens["id_token"])) {
                throw new RuntimeException("Google không trả về ID token.");
            }

            $claims = $this->googleRequest(
                "https://oauth2.googleapis.com/tokeninfo",
                ["id_token" => $tokens["id_token"]],
                false
            );
            $issuerValid = in_array(
                $claims["iss"] ?? "",
                ["accounts.google.com", "https://accounts.google.com"],
                true
            );
            $tokenValid = $issuerValid
                && hash_equals($config["google_client_id"], (string) ($claims["aud"] ?? ""))
                && (int) ($claims["exp"] ?? 0) > time()
                && hash_equals($oauth["nonce"], (string) ($claims["nonce"] ?? ""));
            $emailVerified = in_array(
                $claims["email_verified"] ?? false,
                [true, 1, "1", "true"],
                true
            );
            $email = trim((string) ($claims["email"] ?? ""));
            $name = trim((string) ($claims["name"] ?? ""));
            $avatarUrl = $this->trustedGoogleAvatar($claims["picture"] ?? "");

            if (!$tokenValid || !$emailVerified || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException("Google ID token verification failed.");
            }

            $user = $this->userModel->findByEmail($email);
            if (!$user) {
                $created = $this->userModel->create(
                    $name !== "" ? $name : $email,
                    $email,
                    password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT),
                    "",
                    "",
                    true
                );
                if (!$created) {
                    throw new RuntimeException("Không thể tạo tài khoản Google.");
                }
                $user = $this->userModel->findByEmail($email);
            } elseif ((int) ($user["email_verified"] ?? 1) !== 1) {
                $this->userModel->markEmailVerified((int) $user["id"]);
                $user["email_verified"] = 1;
            }

            if (!$user || (int) $user["status"] !== 1) {
                throw new RuntimeException("Google account is linked to an unavailable user.");
            }
            if ($avatarUrl !== "") {
                $this->userModel->updateGoogleAvatar((int) $user["id"], $avatarUrl);
                $user["avatar"] = $avatarUrl;
            }
            $this->completeLogin($user);
        } catch (Throwable $e) {
            error_log("Google OAuth callback failed: " . $e->getMessage());
            $_SESSION["auth_error"] =
                "Không thể xác thực tài khoản Google. Vui lòng thử lại hoặc đăng nhập bằng email.";
            $this->redirectToLogin();
        }
    }

    public function verifyEmail(): void
    {
        $token = $_GET["token"] ?? "";
        if (!is_string($token) || !preg_match('/\A[a-f0-9]{64}\z/', $token)) {
            $_SESSION["auth_error"] = "Liên kết xác nhận email không hợp lệ.";
            $this->redirectToLogin();
        }

        try {
            if (!$this->userModel->verifyEmailToken(hash("sha256", $token))) {
                $_SESSION["auth_error"] =
                    "Liên kết xác nhận đã hết hạn hoặc không hợp lệ. Hãy gửi lại email xác nhận.";
            } else {
                $_SESSION["auth_success"] =
                    "Email đã được xác nhận. Bạn có thể đăng nhập.";
            }
        } catch (Throwable $e) {
            error_log("Email verification failed: " . $e->getMessage());
            $_SESSION["auth_error"] =
                "Không thể xác nhận email lúc này. Vui lòng thử lại.";
        }
        $this->redirectToLogin();
    }

    public function verifyEmailChange(): void
    {
        $token = $_GET["token"] ?? "";
        if (!is_string($token) || !preg_match('/\A[a-f0-9]{64}\z/', $token)) {
            $this->setEmailChangeVerificationError(
                "Liên kết xác nhận email mới không hợp lệ."
            );
            return;
        }

        try {
            $updatedUser = $this->userModel->verifyEmailChangeToken(hash("sha256", $token));
            if (!$updatedUser) {
                $this->setEmailChangeVerificationError(
                    "Liên kết đã hết hạn hoặc email mới không còn khả dụng. Hãy cập nhật lại hồ sơ để gửi liên kết khác."
                );
                return;
            }

            if (
                !empty($_SESSION["user"]["id"])
                && (int) $_SESSION["user"]["id"] === (int) $updatedUser["id"]
            ) {
                $_SESSION["user"]["email"] = $updatedUser["email"];
                $_SESSION["profile_success"] = "Địa chỉ email mới đã được xác nhận và cập nhật.";
                $this->redirectToProfile();
            }

            $_SESSION["auth_success"] =
                "Địa chỉ email mới đã được xác nhận. Bạn có thể đăng nhập bằng email mới.";
            $this->redirectToLogin();
        } catch (Throwable $e) {
            error_log("Email change verification failed: " . $e->getMessage());
            $this->setEmailChangeVerificationError(
                "Không thể xác nhận email mới lúc này. Vui lòng thử lại."
            );
        }
    }

    public function resendVerification(): void
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            http_response_code(405);
            header("Allow: POST");
            exit("Phương thức không được hỗ trợ.");
        }

        if (!$this->checkCsrf()) {
            $_SESSION["auth_error"] = "Phiên xác thực không hợp lệ. Vui lòng thử lại.";
            $this->redirectToLogin();
        }

        $emailInput = $_POST["email"] ?? "";
        $email = is_string($emailInput) ? trim($emailInput) : "";
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION["auth_error"] = "Vui lòng nhập địa chỉ email hợp lệ.";
            $this->redirectToLogin($email);
        }

        try {
            $user = $this->userModel->findByEmail($email);
            if ($user && (int) ($user["email_verified"] ?? 1) !== 1) {
                if (!$this->userModel->hasRecentEmailVerificationToken((int) $user["id"])) {
                    $this->sendVerificationLink($user);
                }
            }
            $_SESSION["auth_success"] =
                "Nếu tài khoản cần xác nhận, liên kết mới sẽ được gửi tới email đó.";
        } catch (Throwable $e) {
            error_log("Email verification resend failed: " . $e->getMessage());
            $_SESSION["auth_error"] =
                "Không thể gửi email xác nhận lúc này. Vui lòng kiểm tra cấu hình Gmail SMTP.";
        }
        $this->redirectToLogin($email);
    }

    public function logout(): void
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            http_response_code(405);
            header("Allow: POST");
            exit("Phương thức không được hỗ trợ.");
        }

        if (!$this->checkCsrf()) {
            http_response_code(403);
            exit("Yêu cầu không hợp lệ.");
        }

        unset($_SESSION["user"]);
        $_SESSION["cart"] = [];
        unset($_SESSION["cart_owner_id"]);
        session_regenerate_id(true);
        header("Location: " . BASE_URL);
        exit;
    }

    private function completeLogin(array $user): void
    {
        $guestCart = $_SESSION["cart"] ?? [];
        session_regenerate_id(true);
        $_SESSION["user"] = [
            "id" => $user["id"],
            "name" => $user["name"],
            "email" => $user["email"],
            "role" => $user["role"],
            "avatar" => $user["avatar"] ?? "",
        ];

        $cart = new Cart($this->conn, false);
        $cartWarnings = $cart->mergeGuestCart(is_array($guestCart) ? $guestCart : []);
        $redirect = $_SESSION["redirect_after_login"] ?? "";
        unset($_SESSION["redirect_after_login"]);
        $hasValidRedirect = is_string($redirect)
            && str_starts_with($redirect, BASE_URL)
            && !str_contains($redirect, "://");

        if ($cartWarnings) {
            $_SESSION["cart_success"] =
                "Đã gộp giỏ hàng. " . implode(" ", $cartWarnings);
        }
        if ($hasValidRedirect) {
            header("Location: " . $redirect);
        } elseif ($cartWarnings) {
            header("Location: " . BASE_URL . "index.php?controller=cart&action=index");
        } elseif (($user["role"] ?? "") === "admin") {
            header("Location: " . BASE_URL . "index.php?controller=admin&action=index");
        } else {
            header("Location: " . BASE_URL);
        }
        exit;
    }

    private function sendVerificationLink(array $user): void
    {
        $config = $this->authConfig();
        if (empty($config["app_url"])) {
            throw new RuntimeException("Thiếu địa chỉ website trong cấu hình xác thực.");
        }
        $rawToken = bin2hex(random_bytes(32));
        $this->userModel->saveEmailVerificationToken(
            (int) $user["id"],
            hash("sha256", $rawToken)
        );
        $verificationUrl = rtrim($config["app_url"], "/")
            . "/index.php?controller=auth&action=verifyEmail&token="
            . rawurlencode($rawToken);

        (new GmailMailer())->sendVerificationEmail(
            $config,
            $user["email"],
            $user["name"],
            $verificationUrl
        );
    }

    private function sendPasswordResetLink(array $user): void
    {
        $config = $this->authConfig();
        if (empty($config["app_url"])) {
            throw new RuntimeException("Thiếu địa chỉ website trong cấu hình xác thực.");
        }

        $rawToken = bin2hex(random_bytes(32));
        $this->userModel->savePasswordResetToken(
            (int) $user["id"],
            hash("sha256", $rawToken)
        );
        $resetUrl = rtrim($config["app_url"], "/")
            . "/index.php?controller=auth&action=resetPassword&token="
            . rawurlencode($rawToken);

        try {
            (new GmailMailer())->sendPasswordResetEmail(
                $config,
                $user["email"],
                $user["name"],
                $resetUrl
            );
        } catch (Throwable $e) {
            $this->userModel->deletePasswordResetToken((int) $user["id"]);
            throw $e;
        }
    }

    private function authConfig(): array
    {
        $path = dirname(__DIR__, 2) . "/config/auth.local.php";
        if (!is_file($path)) {
            throw new RuntimeException("Thiếu file cấu hình config/auth.local.php.");
        }
        $config = require $path;
        if (!is_array($config)) {
            throw new RuntimeException("Cấu hình xác thực phải trả về một mảng.");
        }
        return $config;
    }

    private function requireGoogleConfig(array $config): void
    {
        foreach (["google_client_id", "google_client_secret", "google_redirect_uri"] as $key) {
            if (!isset($config[$key]) || trim((string) $config[$key]) === "") {
                throw new RuntimeException("Thiếu cấu hình Google OAuth: " . $key);
            }
        }
    }

    private function googleRequest(string $url, array $data, bool $post = true): array
    {
        $handle = curl_init();
        if ($handle === false) {
            throw new RuntimeException("Không thể khởi tạo kết nối Google OAuth.");
        }
        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HTTPHEADER => ["Accept: application/json"],
        ];
        if ($post) {
            $options[CURLOPT_URL] = $url;
            $options[CURLOPT_POST] = true;
            $options[CURLOPT_POSTFIELDS] = http_build_query($data);
        } else {
            $options[CURLOPT_URL] = $url . "?" . http_build_query($data);
        }
        curl_setopt_array($handle, $options);
        $response = curl_exec($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        $curlError = curl_error($handle);
        curl_close($handle);

        if (!is_string($response) || $status < 200 || $status >= 300) {
            throw new RuntimeException(
                "Yêu cầu Google OAuth thất bại (HTTP " . $status . "). "
                . ($curlError !== "" ? $curlError : "")
            );
        }
        $decoded = json_decode($response, true);
        if (!is_array($decoded) || isset($decoded["error"])) {
            throw new RuntimeException("Google OAuth trả về dữ liệu không hợp lệ.");
        }
        return $decoded;
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), "+/", "-_"), "=");
    }

    private function trustedGoogleAvatar($picture): string
    {
        if (!is_string($picture) || $picture === "" || strlen($picture) > 2048) {
            return "";
        }

        $parts = parse_url($picture);
        $host = strtolower((string) ($parts["host"] ?? ""));
        $isGoogleImageHost = $host === "googleusercontent.com"
            || str_ends_with($host, ".googleusercontent.com");

        if (
            ($parts["scheme"] ?? "") !== "https"
            || !$isGoogleImageHost
            || isset($parts["user"])
            || isset($parts["pass"])
        ) {
            return "";
        }

        return $picture;
    }

    private function checkCsrf(): bool
    {
        $token = $_POST["csrf_token"] ?? "";
        return is_string($token)
            && isset($_SESSION["csrf_token"])
            && hash_equals($_SESSION["csrf_token"], $token);
    }

    private function redirectToLogin(string $email = ""): void
    {
        if ($email !== "") {
            $_SESSION["auth_email"] = $email;
        }
        header("Location: " . BASE_URL . "index.php?controller=auth&action=login");
        exit;
    }

    private function redirectToForgotPassword(): void
    {
        header("Location: " . BASE_URL . "index.php?controller=auth&action=forgotPassword");
        exit;
    }

    private function redirectToProfile(): void
    {
        header("Location: " . BASE_URL . "index.php?controller=profile&action=index");
        exit;
    }

    private function setEmailChangeVerificationError(string $message): void
    {
        if (!empty($_SESSION["user"]["id"])) {
            $_SESSION["profile_error"] = $message;
            $this->redirectToProfile();
        }
        $_SESSION["auth_error"] = $message;
        $this->redirectToLogin();
    }
}
