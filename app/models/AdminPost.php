<?php

class AdminPost
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function getTotalCount($search = "")
    {
        $search = trim($search);

        if ($search === "") {
            $result = $this->conn->query("SELECT COUNT(*) AS total FROM posts");
            if (!$result) {
                return 0;
            }

            $row = $result->fetch_assoc();
            return (int) ($row["total"] ?? 0);
        }

        $sql = "SELECT COUNT(*) AS total
                FROM posts p
                LEFT JOIN users u ON p.author_id = u.id
                WHERE p.title LIKE ?
                   OR p.slug LIKE ?
                   OR u.name LIKE ?";

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return 0;
        }

        $keyword = "%" . $search . "%";
        $stmt->bind_param("sss", $keyword, $keyword, $keyword);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return (int) ($row["total"] ?? 0);
    }

    public function getPaginated($limit, $offset, $search = "")
    {
        $limit = max(1, (int) $limit);
        $offset = max(0, (int) $offset);
        $search = trim($search);

        if ($search === "") {
            $sql = "SELECT p.id, p.title, p.slug, p.image, p.status,
                           p.created_at, p.updated_at, p.author_id,
                           u.name AS author_name
                    FROM posts p
                    LEFT JOIN users u ON p.author_id = u.id
                    ORDER BY p.id DESC
                    LIMIT ? OFFSET ?";

            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                return [];
            }

            $stmt->bind_param("ii", $limit, $offset);
        } else {
            $sql = "SELECT p.id, p.title, p.slug, p.image, p.status,
                           p.created_at, p.updated_at, p.author_id,
                           u.name AS author_name
                    FROM posts p
                    LEFT JOIN users u ON p.author_id = u.id
                    WHERE p.title LIKE ?
                       OR p.slug LIKE ?
                       OR u.name LIKE ?
                    ORDER BY p.id DESC
                    LIMIT ? OFFSET ?";

            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                return [];
            }

            $keyword = "%" . $search . "%";
            $stmt->bind_param(
                "sssii",
                $keyword,
                $keyword,
                $keyword,
                $limit,
                $offset
            );
        }

        $stmt->execute();
        $posts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $posts;
    }

    public function getById($id)
    {
        $id = (int) $id;
        $sql = "SELECT p.*, u.name AS author_name
                FROM posts p
                LEFT JOIN users u ON p.author_id = u.id
                WHERE p.id = ?
                LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return null;
        }

        $stmt->bind_param("i", $id);
        $stmt->execute();
        $post = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $post ?: null;
    }

    public function slugExists($slug, $excludeId = 0)
    {
        $sql = "SELECT id FROM posts WHERE slug = ? AND id != ? LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return false;
        }

        $excludeId = (int) $excludeId;
        $stmt->bind_param("si", $slug, $excludeId);
        $stmt->execute();
        $exists = (bool) $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $exists;
    }

    public function createSlug($title, $excludeId = 0)
    {
        $slug = mb_strtolower(trim($title));
        $slug = iconv("UTF-8", "ASCII//TRANSLIT//IGNORE", $slug);
        $slug = preg_replace("/[^a-z0-9]+/", "-", (string) $slug);
        $slug = trim($slug, "-");

        if ($slug === "") {
            $slug = "bai-viet";
        }

        $base = $slug;
        $i = 1;

        while ($this->slugExists($slug, (int) $excludeId)) {
            $slug = $base . "-" . $i;
            $i++;
        }

        return mb_substr($slug, 0, 255);
    }

    public function create($data)
    {
        $sql = "INSERT INTO posts (author_id, title, slug, image, content, status)
                VALUES (?, ?, ?, ?, ?, ?)";

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param(
            "issssi",
            $data["author_id"],
            $data["title"],
            $data["slug"],
            $data["image"],
            $data["content"],
            $data["status"]
        );

        $success = $stmt->execute();
        $stmt->close();

        return $success;
    }

    public function update($id, $data)
    {
        $id = (int) $id;

        $sql = "UPDATE posts
                SET title = ?, slug = ?, image = ?, content = ?, status = ?
                WHERE id = ?";

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param(
            "ssssii",
            $data["title"],
            $data["slug"],
            $data["image"],
            $data["content"],
            $data["status"],
            $id
        );

        $success = $stmt->execute();
        $stmt->close();

        return $success;
    }

    public function delete($id)
    {
        $id = (int) $id;
        $post = $this->getById($id);

        if (!$post) {
            return false;
        }

        $sql = "DELETE FROM posts WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("i", $id);
        $success = $stmt->execute();
        $stmt->close();

        if ($success && !empty($post["image"])) {
            $this->deleteImageFile($post["image"]);
        }

        return $success;
    }

    public function uploadImage($file)
    {
        if (
            empty($file)
            || !isset($file["error"])
            || $file["error"] === UPLOAD_ERR_NO_FILE
        ) {
            return ["ok" => true, "path" => null];
        }

        if ($file["error"] !== UPLOAD_ERR_OK) {
            return ["ok" => false, "error" => "Không thể tải ảnh lên. Vui lòng thử lại."];
        }

        if (($file["size"] ?? 0) > 5 * 1024 * 1024) {
            return ["ok" => false, "error" => "Ảnh không được vượt quá 5 MB."];
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file["tmp_name"]);

        $allowed = [
            "image/jpeg" => "jpg",
            "image/png" => "png",
            "image/webp" => "webp",
        ];

        if (!isset($allowed[$mime])) {
            return [
                "ok" => false,
                "error" => "Chỉ chấp nhận ảnh JPG, PNG hoặc WEBP.",
            ];
        }

        $uploadDir = dirname(__DIR__, 2) . "/uploads/posts/";

        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
            return ["ok" => false, "error" => "Không thể tạo thư mục lưu ảnh."];
        }

        if (!is_writable($uploadDir)) {
            return ["ok" => false, "error" => "Thư mục uploads/posts không có quyền ghi."];
        }

        $fileName = "post_" . date("YmdHis") . "_" . bin2hex(random_bytes(4))
            . "." . $allowed[$mime];
        $dest = $uploadDir . $fileName;

        if (!move_uploaded_file($file["tmp_name"], $dest)) {
            return ["ok" => false, "error" => "Không thể lưu ảnh bài viết."];
        }

        return [
            "ok" => true,
            "path" => "uploads/posts/" . $fileName,
        ];
    }

    public function deleteImageFile($relativePath)
    {
        if ($relativePath === "" || $relativePath === null) {
            return;
        }

        $relativePath = str_replace("\\", "/", $relativePath);

        if (strpos($relativePath, "uploads/posts/") !== 0) {
            return;
        }

        $fullPath = dirname(__DIR__, 2) . "/" . $relativePath;

        if (is_file($fullPath)) {
            @unlink($fullPath);
        }
    }
}
