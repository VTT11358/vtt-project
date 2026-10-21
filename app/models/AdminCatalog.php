
<?php

class AdminCatalog
{
    private mysqli $conn;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }

    private function getConfig(string $type): array
    {
        if ($type === "category") {
            return [
                "table" => "categories",
                "foreign_key" => "category_id",
                "label" => "danh mục"
            ];
        }

        if ($type === "brand") {
            return [
                "table" => "brands",
                "foreign_key" => "brand_id",
                "label" => "thương hiệu"
            ];
        }

        throw new InvalidArgumentException("Loại dữ liệu không hợp lệ.");
    }

    public function getAll(string $type): array
    {
        $config = $this->getConfig($type);
        $table = $config["table"];

        $result = $this->conn->query(
            "SELECT * FROM `$table` ORDER BY id DESC"
        );

        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getById(string $type, int $id): ?array
    {
        $config = $this->getConfig($type);
        $table = $config["table"];

        $stmt = $this->conn->prepare(
            "SELECT * FROM `$table` WHERE id = ?"
        );

        $stmt->bind_param("i", $id);
        $stmt->execute();

        $item = $stmt->get_result()->fetch_assoc();

        $stmt->close();

        return $item ?: null;
    }

    public function save(
        string $type,
        array $data,
        ?int $id = null
    ): bool {
        $config = $this->getConfig($type);
        $table = $config["table"];

        $name = trim($data["name"] ?? "");
        $slug = trim($data["slug"] ?? "");
        $description = trim($data["description"] ?? "");
        $status = isset($data["status"]) ? 1 : 0;

        if ($name === "" || $slug === "") {
            throw new InvalidArgumentException(
                "Tên và slug không được để trống."
            );
        }

        if (mb_strlen($name) > 100 || mb_strlen($slug) > 150) {
            throw new InvalidArgumentException(
                "Tên hoặc slug vượt quá độ dài cho phép."
            );
        }

        if ($id !== null) {
            $sql = "
                UPDATE `$table`
                SET name = ?, slug = ?, description = ?, status = ?
                WHERE id = ?
            ";

            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param(
                "sssii",
                $name,
                $slug,
                $description,
                $status,
                $id
            );
        } else {
            $sql = "
                INSERT INTO `$table`
                    (name, slug, description, status)
                VALUES (?, ?, ?, ?)
            ";

            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param(
                "sssi",
                $name,
                $slug,
                $description,
                $status
            );
        }

        $success = $stmt->execute();
        $stmt->close();

        return $success;
    }

    public function delete(string $type, int $id): array
    {
        $config = $this->getConfig($type);
        $table = $config["table"];
        $foreignKey = $config["foreign_key"];
        $label = $config["label"];

        // Không cho xóa nếu còn sản phẩm tham chiếu
        $stmt = $this->conn->prepare(
            "SELECT COUNT(*) AS total
             FROM products
             WHERE `$foreignKey` = ?"
        );

        $stmt->bind_param("i", $id);
        $stmt->execute();

        $count = (int) $stmt->get_result()
            ->fetch_assoc()["total"];

        $stmt->close();

        if ($count > 0) {
            return [
                "success" => false,
                "message" => "Không thể xóa $label vì vẫn còn sản phẩm sử dụng."
            ];
        }

        $stmt = $this->conn->prepare(
            "DELETE FROM `$table` WHERE id = ?"
        );

        $stmt->bind_param("i", $id);
        $success = $stmt->execute();
        $stmt->close();

        return [
            "success" => $success,
            "message" => $success
                ? "Xóa $label thành công."
                : "Không thể xóa $label."
        ];
    }
}
