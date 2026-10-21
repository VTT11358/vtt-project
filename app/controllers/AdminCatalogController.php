
<?php

require_once __DIR__ . "/../middleware/AuthMiddleware.php";
require_once __DIR__ . "/../models/AdminCatalog.php";

abstract class AdminCatalogController
{
    protected $catalogModel;
    protected $conn;

    protected string $type;
    protected string $viewFolder;
    protected string $pageTitle;
    protected string $controllerName;

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
        $this->catalogModel = new AdminCatalog($conn);
    }

    // Danh sách danh mục hoặc thương hiệu
    public function index()
    {
        $catalogType = $this->type;
        $pageTitle = $this->pageTitle;
        $items = $this->catalogModel->getAll($this->type);

        require __DIR__ . "/../views/admin/" . $this->viewFolder . "/index.php";
    }

    // Hiển thị form thêm
    public function create()
    {
        $catalogType = $this->type;
        $pageTitle = "Thêm " . mb_strtolower($this->pageTitle);
        $item = null;
        $error = "";

        require __DIR__ . "/../views/admin/" . $this->viewFolder . "/form.php";
    }

    // Lưu dữ liệu mới
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

        try {
            $result = $this->catalogModel->save(
                $this->type,
                $_POST
            );
        } catch (Throwable $e) {
            $result = "Không thể lưu dữ liệu. Vui lòng kiểm tra thông tin.";
        }

        if ($result === true) {
            $_SESSION["admin_success"] =
                "Thêm " . mb_strtolower($this->pageTitle) . " thành công.";

            $this->redirect("index");
        }

        $catalogType = $this->type;
        $pageTitle = "Thêm " . mb_strtolower($this->pageTitle);
        $item = $_POST;
        $error = is_string($result)
            ? $result
            : "Không thể thêm dữ liệu.";

        require __DIR__ . "/../views/admin/" . $this->viewFolder . "/form.php";
    }

    // Hiển thị form sửa
    public function edit()
    {
        $id = (int) ($_GET["id"] ?? 0);
        $item = $this->catalogModel->getById($this->type, $id);

        if (!$item) {
            http_response_code(404);
            exit("Không tìm thấy dữ liệu.");
        }

        $catalogType = $this->type;
        $pageTitle = "Sửa " . mb_strtolower($this->pageTitle);
        $error = "";

        require __DIR__ . "/../views/admin/" . $this->viewFolder . "/form.php";
    }

    // Cập nhật dữ liệu
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
        $existingItem = $this->catalogModel->getById($this->type, $id);

        if (!$existingItem) {
            http_response_code(404);
            exit("Không tìm thấy dữ liệu.");
        }

        try {
            $result = $this->catalogModel->save(
                $this->type,
                $_POST,
                $id
            );
        } catch (Throwable $e) {
            $result = "Không thể cập nhật dữ liệu.";
        }

        if ($result === true) {
            $_SESSION["admin_success"] =
                "Cập nhật " . mb_strtolower($this->pageTitle) . " thành công.";

            $this->redirect("index");
        }

        $catalogType = $this->type;
        $pageTitle = "Sửa " . mb_strtolower($this->pageTitle);
        $item = array_merge($existingItem, $_POST);
        $error = is_string($result)
            ? $result
            : "Không thể cập nhật dữ liệu.";

        require __DIR__ . "/../views/admin/" . $this->viewFolder . "/form.php";
    }

    // Xóa dữ liệu
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

        if (!$id || $id <= 0) {
            $_SESSION["admin_error"] = "ID không hợp lệ.";
            $this->redirect("index");
        }

        try {
            $result = $this->catalogModel->delete($this->type, $id);
        } catch (Throwable $e) {
            $result = false;
        }

        // Hỗ trợ model trả về true/false hoặc mảng kết quả
        if (is_array($result)) {
            $success = (bool) (
                $result["success"]
                ?? (($result["status"] ?? "") === "deleted")
            );

            $message = $result["message"]
                ?? ($success ? "Đã xóa thành công." : "Không thể xóa dữ liệu.");
        } else {
            $success = ($result === true);
            $message = is_string($result)
                ? $result
                : ($success
                    ? "Đã xóa thành công."
                    : "Không thể xóa vì dữ liệu có thể đang được sử dụng.");
        }

        $_SESSION[$success ? "admin_success" : "admin_error"] = $message;

        $this->redirect("index");
    }

    protected function checkCsrf(): bool
    {
        return isset($_SESSION["csrf_token"])
            && hash_equals(
                $_SESSION["csrf_token"],
                $_POST["csrf_token"] ?? ""
            );
    }

    protected function redirect(string $action): void
    {
        header(
            "Location: " . BASE_URL .
            "index.php?controller=" . $this->controllerName .
            "&action=" . $action
        );
        exit;
    }
}
