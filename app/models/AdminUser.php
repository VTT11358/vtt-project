<?php

class AdminUser
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
            $result = $this->conn->query("SELECT COUNT(*) AS total FROM users");
            if (!$result) {
                return 0;
            }

            $row = $result->fetch_assoc();
            return (int) ($row["total"] ?? 0);
        }

        $sql = "SELECT COUNT(*) AS total
                FROM users
                WHERE name LIKE ?
                   OR email LIKE ?
                   OR phone LIKE ?";

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
            $sql = "SELECT id, name, email, phone, address, role, status, created_at
                    FROM users
                    ORDER BY id DESC
                    LIMIT ? OFFSET ?";

            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                return [];
            }

            $stmt->bind_param("ii", $limit, $offset);
        } else {
            $sql = "SELECT id, name, email, phone, address, role, status, created_at
                    FROM users
                    WHERE name LIKE ?
                       OR email LIKE ?
                       OR phone LIKE ?
                    ORDER BY id DESC
                    LIMIT ? OFFSET ?";

            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                return [];
            }

            $keyword = "%" . $search . "%";
            $stmt->bind_param("sssii", $keyword, $keyword, $keyword, $limit, $offset);
        }

        $stmt->execute();
        $users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $users;
    }

    public function getById($id)
    {
        $id = (int) $id;
        $sql = "SELECT id, name, email, phone, address, role, status, created_at
                FROM users
                WHERE id = ?
                LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return null;
        }

        $stmt->bind_param("i", $id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $user ?: null;
    }

    public function emailExists($email, $excludeId = 0)
    {
        $sql = "SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return false;
        }

        $excludeId = (int) $excludeId;
        $stmt->bind_param("si", $email, $excludeId);
        $stmt->execute();
        $exists = (bool) $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $exists;
    }

    public function countActiveAdmins($excludeId = 0)
    {
        $sql = "SELECT COUNT(*) AS total
                FROM users
                WHERE role = 'admin' AND status = 1 AND id != ?";

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return 0;
        }

        $excludeId = (int) $excludeId;
        $stmt->bind_param("i", $excludeId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return (int) ($row["total"] ?? 0);
    }

    public function hasRelatedRecords($id)
    {
        $id = (int) $id;

        $checks = [
            "SELECT COUNT(*) AS total FROM orders WHERE user_id = ?",
            "SELECT COUNT(*) AS total FROM posts WHERE author_id = ?",
        ];

        foreach ($checks as $sql) {
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                continue;
            }

            $stmt->bind_param("i", $id);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ((int) ($row["total"] ?? 0) > 0) {
                return true;
            }
        }

        return false;
    }

    public function create($data)
    {
        $sql = "INSERT INTO users (name, email, password, phone, address, role, status)
                VALUES (?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param(
            "ssssssi",
            $data["name"],
            $data["email"],
            $data["password"],
            $data["phone"],
            $data["address"],
            $data["role"],
            $data["status"]
        );

        $success = $stmt->execute();
        $stmt->close();

        return $success;
    }

    public function update($id, $data, $passwordHash = null)
    {
        $id = (int) $id;

        if ($passwordHash !== null && $passwordHash !== "") {
            $sql = "UPDATE users
                    SET name = ?, email = ?, phone = ?, address = ?, role = ?, status = ?, password = ?
                    WHERE id = ?";

            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                return false;
            }

            $stmt->bind_param(
                "sssssisi",
                $data["name"],
                $data["email"],
                $data["phone"],
                $data["address"],
                $data["role"],
                $data["status"],
                $passwordHash,
                $id
            );
        } else {
            $sql = "UPDATE users
                    SET name = ?, email = ?, phone = ?, address = ?, role = ?, status = ?
                    WHERE id = ?";

            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                return false;
            }

            $stmt->bind_param(
                "sssssii",
                $data["name"],
                $data["email"],
                $data["phone"],
                $data["address"],
                $data["role"],
                $data["status"],
                $id
            );
        }

        $success = $stmt->execute();
        $stmt->close();

        return $success;
    }

    public function delete($id)
    {
        $id = (int) $id;
        $sql = "DELETE FROM users WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("i", $id);
        $success = $stmt->execute();
        $stmt->close();

        return $success;
    }
}
