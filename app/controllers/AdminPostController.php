<?php

require_once __DIR__ . "/../middleware/AuthMiddleware.php";
require_once __DIR__ . "/../models/AdminPost.php";

class AdminPostController
{
    private $postModel;
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
        $this->postModel = new AdminPost($conn);
    }

    public function index()
    {
        $limit = 10;
        $search = trim($_GET["q"] ?? "");
        $search = mb_substr($search, 0, 100);

        $currentPage = filter_input(INPUT_GET, "page", FILTER_VALIDATE_INT);
        $currentPage = ($currentPage && $currentPage > 0) ? $currentPage : 1;

        $totalPosts = $this->postModel->getTotalCount($search);
        $totalPages = max(1, (int) ceil($totalPosts / $limit));
        $currentPage = min($currentPage, $totalPages);
        $offset = ($currentPage - 1) * $limit;

        $posts = $this->postModel->getPaginated($limit, $offset, $search);

        require __DIR__ . "/../views/admin/posts/index.php";
    }

    public function create()
    {
        $post = null;
        $error = "";

        require __DIR__ . "/../views/admin/posts/form.php";
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
        $error = $this->validate($data);

        $uploadedPath = null;

        if ($error === "") {
            $upload = $this->postModel->uploadImage($_FILES["image"] ?? []);

            if (!$upload["ok"]) {
                $error = $upload["error"];
            } else {
                $uploadedPath = $upload["path"];
                $data["image"] = $uploadedPath;
                $data["author_id"] = (int) ($_SESSION["user"]["id"] ?? 0);

                if ($data["author_id"] <= 0) {
                    $error = "Không xác định được tác giả bài viết.";
                } else {
                    try {
                        $created = $this->postModel->create($data);
                    } catch (Throwable $e) {
                        $created = false;
                    }

                    if ($created) {
                        $_SESSION["admin_success"] = "Thêm bài viết thành công.";
                        $this->redirect("index");
                    }

                    $error = "Không thể tạo bài viết. Vui lòng thử lại.";
                }
            }
        }

        if ($error !== "" && $uploadedPath) {
            $this->postModel->deleteImageFile($uploadedPath);
        }

        $post = $data;
        require __DIR__ . "/../views/admin/posts/form.php";
    }

    public function edit()
    {
        $id = (int) ($_GET["id"] ?? 0);
        $post = $this->postModel->getById($id);

        if (!$post) {
            http_response_code(404);
            exit("Không tìm thấy bài viết.");
        }

        $error = "";
        require __DIR__ . "/../views/admin/posts/form.php";
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
        $existing = $this->postModel->getById($id);

        if (!$existing) {
            http_response_code(404);
            exit("Không tìm thấy bài viết.");
        }

        $data = $this->collectInput($id);
        $error = $this->validate($data, $id);
        $uploadedPath = null;
        $removeOldImage = false;

        if ($error === "") {
            $upload = $this->postModel->uploadImage($_FILES["image"] ?? []);

            if (!$upload["ok"]) {
                $error = $upload["error"];
            } else {
                if (!empty($upload["path"])) {
                    $uploadedPath = $upload["path"];
                    $data["image"] = $uploadedPath;
                    $removeOldImage = true;
                } else {
                    $data["image"] = $existing["image"] ?? null;
                }

                try {
                    $updated = $this->postModel->update($id, $data);
                } catch (Throwable $e) {
                    $updated = false;
                }

                if ($updated) {
                    if ($removeOldImage && !empty($existing["image"])) {
                        $this->postModel->deleteImageFile($existing["image"]);
                    }

                    $_SESSION["admin_success"] = "Cập nhật bài viết thành công.";
                    $this->redirect("index");
                }

                $error = "Không thể cập nhật bài viết. Vui lòng thử lại.";
            }
        }

        if ($error !== "" && $uploadedPath) {
            $this->postModel->deleteImageFile($uploadedPath);
        }

        $post = array_merge($existing, $data);
        require __DIR__ . "/../views/admin/posts/form.php";
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

        if (!$id || $id <= 0) {
            $_SESSION["admin_error"] = "ID bài viết không hợp lệ.";
            $this->redirect("index");
        }

        $post = $this->postModel->getById($id);
        if (!$post) {
            $_SESSION["admin_error"] = "Không tìm thấy bài viết.";
            $this->redirect("index");
        }

        try {
            $deleted = $this->postModel->delete($id);
        } catch (Throwable $e) {
            $deleted = false;
        }

        $_SESSION[$deleted ? "admin_success" : "admin_error"] = $deleted
            ? "Đã xóa bài viết thành công."
            : "Không thể xóa bài viết.";

        $this->redirect("index");
    }

    private function collectInput(int $excludeId = 0): array
    {
        $title = trim($_POST["title"] ?? "");
        $slug = trim($_POST["slug"] ?? "");
        $content = trim($_POST["content"] ?? "");
        $status = (int) ($_POST["status"] ?? 1);
        $status = $status === 1 ? 1 : 0;

        if ($slug === "" && $title !== "") {
            $slug = $this->postModel->createSlug($title, $excludeId);
        } elseif ($slug !== "") {
            $slug = $this->normalizeSlug($slug);

            if ($slug !== "" && $this->postModel->slugExists($slug, $excludeId)) {
                $base = $slug;
                $i = 1;
                while ($this->postModel->slugExists($slug, $excludeId)) {
                    $slug = $base . "-" . $i;
                    $i++;
                }
            }
        }

        return [
            "title" => $title,
            "slug" => mb_substr($slug, 0, 255),
            "content" => $content,
            "status" => $status,
            "image" => null,
        ];
    }

    private function normalizeSlug(string $slug): string
    {
        $slug = mb_strtolower(trim($slug));
        $slug = iconv("UTF-8", "ASCII//TRANSLIT//IGNORE", $slug);
        $slug = preg_replace("/[^a-z0-9]+/", "-", (string) $slug);
        $slug = trim($slug, "-");

        return $slug ?: "";
    }

    private function validate(array $data, int $excludeId = 0): string
    {
        if ($data["title"] === "") {
            return "Vui lòng nhập tiêu đề bài viết.";
        }

        if (mb_strlen($data["title"]) > 255) {
            return "Tiêu đề không được vượt quá 255 ký tự.";
        }

        if ($data["slug"] === "") {
            return "Slug không hợp lệ.";
        }

        if (mb_strlen($data["slug"]) > 255) {
            return "Slug không được vượt quá 255 ký tự.";
        }

        if ($this->postModel->slugExists($data["slug"], $excludeId)) {
            return "Slug này đã được sử dụng.";
        }

        if ($data["content"] === "") {
            return "Vui lòng nhập nội dung bài viết.";
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
            "index.php?controller=adminPost&action=" . $action
        );
        exit;
    }
}
