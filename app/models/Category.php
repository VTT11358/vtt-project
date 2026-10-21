<?php

class Category
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function getAll()
    {
        $sql = "
            SELECT *
            FROM categories
            WHERE status = 1
            ORDER BY name ASC
        ";

        return $this->conn->query($sql);
    }

    public function getById($id)
    {
        $sql = "
            SELECT *
            FROM categories
            WHERE id = ?
              AND status = 1
            LIMIT 1
        ";

        $stmt = $this->conn->prepare($sql);

        $stmt->bind_param("i", $id);

        $stmt->execute();

        $result = $stmt->get_result();

        return $result->fetch_assoc();
    }
}