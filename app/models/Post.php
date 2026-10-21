<?php

class Post
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function getPublishedCount()
    {
        $result = $this->conn->query("SELECT COUNT(*) AS total FROM posts WHERE status = 1");

        if (!$result) {
            throw new RuntimeException("Không thể đếm số bài viết.");
        }

        $row = $result->fetch_assoc();
        return (int) ($row["total"] ?? 0);
    }

    public function getPublishedPaginated($limit, $offset)
    {
        $limit = max(1, (int) $limit);
        $offset = max(0, (int) $offset);

        $sql = "SELECT p.id, p.title, p.slug, p.image, p.content, p.created_at,
                       u.name AS author_name
                FROM posts p
                LEFT JOIN users u ON p.author_id = u.id
                WHERE p.status = 1
                ORDER BY p.created_at DESC, p.id DESC
                LIMIT ? OFFSET ?";

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            throw new RuntimeException("Không thể chuẩn bị truy vấn danh sách bài viết.");
        }

        if (!$stmt->bind_param("ii", $limit, $offset) || !$stmt->execute()) {
            $stmt->close();
            throw new RuntimeException("Không thể tải danh sách bài viết.");
        }

        $posts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $posts;
    }

    public function getPublishedById($id)
    {
        $id = (int) $id;

        $sql = "SELECT p.id, p.title, p.slug, p.image, p.content, p.created_at,
                       u.name AS author_name
                FROM posts p
                LEFT JOIN users u ON p.author_id = u.id
                WHERE p.id = ? AND p.status = 1
                LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            throw new RuntimeException("Không thể chuẩn bị truy vấn bài viết.");
        }

        if (!$stmt->bind_param("i", $id) || !$stmt->execute()) {
            $stmt->close();
            throw new RuntimeException("Không thể tải bài viết.");
        }

        $post = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $post ?: null;
    }
}
