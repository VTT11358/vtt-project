<?php

class Brand
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
            FROM brands
            WHERE status = 1
            ORDER BY name ASC
        ";

        return $this->conn->query($sql);
    }
}