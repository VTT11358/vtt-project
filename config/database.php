<?php

$host = "127.0.0.1";
$username = "root";
$password = "";
$database = "laptop_store";

$conn = new mysqli(
    $host,
    $username,
    $password,
    $database
);

if ($conn->connect_error) {
    die("Kết nối database thất bại: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

?>