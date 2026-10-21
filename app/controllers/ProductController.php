<?php

require_once "app/models/Product.php";
require_once "app/models/Category.php";
require_once "app/models/Brand.php";

class ProductController
{
    private $productModel;
    private $categoryModel;
    private $brandModel;

    public function __construct($conn)
    {
        $this->productModel = new Product($conn);
        $this->categoryModel = new Category($conn);
        $this->brandModel = new Brand($conn);

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION["csrf_token"])) {
            $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
        }
    }


    /**
     * Danh sách sản phẩm
     */
    public function index()
    {
        $search = is_string($_GET["search"] ?? null)
            ? trim($_GET["search"])
            : "";
        $category_id = is_scalar($_GET["category_id"] ?? null)
            ? (string) ($_GET["category_id"] ?? "")
            : "";
        $brand_id = is_scalar($_GET["brand_id"] ?? null)
            ? (string) ($_GET["brand_id"] ?? "")
            : "";
        $perPage = 12;
        $totalProducts = $this->productModel->getFilteredCount(
            $search,
            $category_id,
            $brand_id
        );
        $totalPages = max(1, (int) ceil($totalProducts / $perPage));
        $requestedPage = filter_var(
            $_GET["page"] ?? 1,
            FILTER_VALIDATE_INT,
            ["options" => ["min_range" => 1]]
        );
        $currentPage = min(
            $requestedPage === false ? 1 : $requestedPage,
            $totalPages
        );
        $offset = ($currentPage - 1) * $perPage;
        $products = $this->productModel->getAll(
            $search,
            $category_id,
            $brand_id,
            $perPage,
            $offset
        );
        $firstProduct = $totalProducts > 0 ? $offset + 1 : 0;
        $lastProduct = min($offset + $products->num_rows, $totalProducts);

        $categories = $this->categoryModel->getAll();

        $brands = $this->brandModel->getAll();

        require_once "app/views/products/index.php";
    }


    /**
     * Chi tiết sản phẩm
     */
    public function detail()
    {
        $id = $_GET['id'] ?? '';

        if (!is_numeric($id)) {
            die("Sản phẩm không hợp lệ.");
        }

        $id = (int)$id;

        $product = $this->productModel->getById($id);

        if (!$product) {
            die("Không tìm thấy sản phẩm.");
        }

        $imageResult = $this->productModel->getImages($id);
        $images = $imageResult
            ? $imageResult->fetch_all(MYSQLI_ASSOC)
            : [];
        $mainImage = $product["image"] ?: ($images[0]["image"] ?? "");

        require_once "app/views/products/detail.php";
    }
}