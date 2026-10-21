<?php

require_once __DIR__ . "/../middleware/AuthMiddleware.php";
require_once __DIR__ . "/../models/AdminUser.php";

class AdminUserController
{
    private $userModel;
    private $conn;

    public function __construct($conn)
    {
        AuthMiddleware::requireAdmin();

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION["csrf_token"])) {
            $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
        }

        $this->conn = $conn;
        $this->userModel = new AdminUser($conn);
    }

    public function index()
    {
        $limit = 10;
        $search = trim($_GET["q"] ?? "");
        $search = mb_substr($search, 0, 100);

        $currentPage = filter_input(INPUT_GET, "page", FILTER_VALIDATE_INT);
        $currentPage = ($currentPage && $currentPage > 0) ? $currentPage : 1;

        $totalUsers = $this->userModel->getTotalCount($search);
        $totalPages = max(1, (int) ceil($totalUsers / $limit));
        $currentPage = min($currentPage, $totalPages);
        $offset = ($currentPage - 1) * $limit;

        $users = $this->userModel->getPaginated($limit, $offset, $search);
        $currentUserId = (int) ($_SESSION["user"]["id"] ?? 0);

        require __DIR__ . "/../views/admin/users/index.php";
    }

    public function create()
    {
        $user = null;
        $error = "";

        require __DIR__ . "/../views/admin/users/form.php";
    }

    public function store()
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            http_response_code(405);
            exit("Phương thức không được hỗ trợ.");
        }

        if (!$this->checkCsrf()) {
            http_response_code(403);
            exit("Yêu cầu không hợp lệ. Vui lòng tải lại trang.");
        }

        $data = $this->collectInput();
        $password = $_POST["password"] ?? "";
        $confirmPassword = $_POST["confirm_password"] ?? "";

        $error = $this->validate($data, $password, $confirmPassword, true);

        if ($error === "") {
            $data["password"] = password_hash($password, PASSWORD_DEFAULT);

            try {
                $created = $this->userModel->create($data);
            } catch (Throwable $e) {
                $created = false;
            }

            if ($created) {
                $_SESSION["admin_success"] = "Thêm người dùng thành công.";
                $this->redirect("index");
            }

            $error = "Không thể tạo tài khoản. Vui lòng thử lại.";
        }

        $user = $data;
        require __DIR__ . "/../views/admin/users/form.php";
    }

    public function edit()
    {
        $id = (int) ($_GET["id"] ?? 0);
        $user = $this->userModel->getById($id);

        if (!$user) {
            http_response_code(404);
            exit("Không tìm thấy người dùng.");
        }

        $error = "";
        require __DIR__ . "/../views/admin/users/form.php";
    }

    public function update()
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            http_response_code(405);
            exit("Phương thức không được hỗ trợ.");
        }

        if (!$this->checkCsrf()) {
            http_response_code(403);
            exit("Yêu cầu không hợp lệ. Vui lòng tải lại trang.");
        }

        $id = (int) ($_GET["id"] ?? 0);
        $existing = $this->userModel->getById($id);

        if (!$existing) {
            http_response_code(404);
            exit("Không tìm thấy người dùng.");
        }

        $data = $this->collectInput();
        $password = $_POST["password"] ?? "";
        $confirmPassword = $_POST["confirm_password"] ?? "";
        $currentUserId = (int) ($_SESSION["user"]["id"] ?? 0);

        $error = $this->validate($data, $password, $confirmPassword, false, $id);

        if ($id === $currentUserId) {
            if ((int) $data["status"] !== 1) {
                $error = "Bạn không thể khóa tài khoản đang đăng nhập.";
            } elseif ($data["role"] !== "admin") {
                $error = "Bạn không thể tự hạ quyền quản trị của chính mình.";
            }
        }

        if (
            $error === ""
            && $existing["role"] === "admin"
            && (int) $existing["status"] === 1
            && ($data["role"] !== "admin" || (int) $data["status"] !== 1)
            && $this->userModel->countActiveAdmins($id) === 0
        ) {
            $error = "Không thể khóa hoặc hạ quyền quản trị viên cuối cùng.";
        }

        if ($error === "") {
            $passwordHash = $password !== ""
                ? password_hash($password, PASSWORD_DEFAULT)
                : null;

            try {
                $updated = $this->userModel->update($id, $data, $passwordHash);
            } catch (Throwable $e) {
                $updated = false;
            }

            if ($updated) {
                if ($id === $currentUserId) {
                    $_SESSION["user"]["name"] = $data["name"];
                    $_SESSION["user"]["email"] = $data["email"];
                    $_SESSION["user"]["role"] = $data["role"];
                }

                $_SESSION["admin_success"] = "Cập nhật người dùng thành công.";
                $this->redirect("index");
            }

            $error = "Không thể cập nhật người dùng. Vui lòng thử lại.";
        }

        $user = array_merge($existing, $data);
        require __DIR__ . "/../views/admin/users/form.php";
    }

    public function delete()
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            http_response_code(405);
            exit("Phương thức không được hỗ trợ.");
        }

        if (!$this->checkCsrf()) {
            $_SESSION["admin_error"] = "Phiên xác thực không hợp lệ.";
            $this->redirect("index");
        }

        $id = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);
        $currentUserId = (int) ($_SESSION["user"]["id"] ?? 0);

        if (!$id || $id <= 0) {
            $_SESSION["admin_error"] = "ID người dùng không hợp lệ.";
            $this->redirect("index");
        }

        if ($id === $currentUserId) {
            $_SESSION["admin_error"] = "Bạn không thể xóa tài khoản đang đăng nhập.";
            $this->redirect("index");
        }

        $user = $this->userModel->getById($id);
        if (!$user) {
            $_SESSION["admin_error"] = "Không tìm thấy người dùng.";
            $this->redirect("index");
        }

        if (
            $user["role"] === "admin"
            && (int) $user["status"] === 1
            && $this->userModel->countActiveAdmins($id) === 0
        ) {
            $_SESSION["admin_error"] = "Không thể xóa quản trị viên cuối cùng.";
            $this->redirect("index");
        }

        if ($this->userModel->hasRelatedRecords($id)) {
            $_SESSION["admin_error"] =
                "Không thể xóa người dùng vì đã có đơn hàng hoặc bài viết liên quan.";
            $this->redirect("index");
        }

        try {
            $deleted = $this->userModel->delete($id);
        } catch (Throwable $e) {
            $deleted = false;
        }

        $_SESSION[$deleted ? "admin_success" : "admin_error"] = $deleted
            ? "Đã xóa người dùng thành công."
            : "Không thể xóa người dùng.";

        $this->redirect("index");
    }

    private function collectInput(): array
    {
        $role = $_POST["role"] ?? "customer";
        if (!in_array($role, ["admin", "customer"], true)) {
            $role = "customer";
        }

        $status = (int) ($_POST["status"] ?? 1);
        $status = $status === 1 ? 1 : 0;

        return [
            "name" => trim($_POST["name"] ?? ""),
            "email" => trim($_POST["email"] ?? ""),
            "phone" => trim($_POST["phone"] ?? ""),
            "address" => trim($_POST["address"] ?? ""),
            "role" => $role,
            "status" => $status,
        ];
    }

    private function validate(
        array $data,
        string $password,
        string $confirmPassword,
        bool $isCreate,
        int $excludeId = 0
    ): string {
        if ($data["name"] === "" || $data["email"] === "") {
            return "Vui lòng nhập họ tên và email.";
        }

        if (mb_strlen($data["name"]) > 100) {
            return "Họ tên không được vượt quá 100 ký tự.";
        }

        if (!filter_var($data["email"], FILTER_VALIDATE_EMAIL)) {
            return "Email không đúng định dạng.";
        }

        if (mb_strlen($data["email"]) > 150) {
            return "Email không được vượt quá 150 ký tự.";
        }

        if ($this->userModel->emailExists($data["email"], $excludeId)) {
            return "Email này đã được sử dụng.";
        }

        if ($data["phone"] !== "" && mb_strlen($data["phone"]) > 20) {
            return "Số điện thoại không được vượt quá 20 ký tự.";
        }

        if ($data["address"] !== "" && mb_strlen($data["address"]) > 255) {
            return "Địa chỉ không được vượt quá 255 ký tự.";
        }

        if ($isCreate || $password !== "") {
            if (strlen($password) < 8) {
                return "Mật khẩu phải có ít nhất 8 ký tự.";
            }

            if ($password !== $confirmPassword) {
                return "Mật khẩu xác nhận không khớp.";
            }
        }

        return "";
    }

    private function checkCsrf(): bool
    {
        return isset($_SESSION["csrf_token"])
            && hash_equals(
                $_SESSION["csrf_token"],
                $_POST["csrf_token"] ?? ""
            );
    }

    private function redirect(string $action): void
    {
        header(
            "Location: " . BASE_URL .
            "index.php?controller=adminUser&action=" . $action
        );
        exit;
    }
}
