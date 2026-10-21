
<?php

require_once __DIR__ . "/../middleware/AuthMiddleware.php";
require_once __DIR__ . "/../models/AdminProduct.php";

class AdminProductController
{
    private $productModel;
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
        $this->productModel = new AdminProduct($conn);
    }

    // Danh sách sản phẩm, tìm kiếm và phân trang
    public function products()
    {
        $limit = 10;
        $search = trim($_GET["q"] ?? "");
        $search = mb_substr($search, 0, 100);

        $currentPage = filter_input(
            INPUT_GET,
            "page",
            FILTER_VALIDATE_INT
        );

        $currentPage = ($currentPage && $currentPage > 0)
            ? $currentPage
            : 1;

        if ($search !== "") {
            $totalProducts =
                $this->productModel->searchTotalCount($search);
        } else {
            $totalProducts = $this->productModel->getTotalCount();
        }

        $totalPages = max(
            1,
            (int) ceil($totalProducts / $limit)
        );

        $currentPage = min($currentPage, $totalPages);
        $offset = ($currentPage - 1) * $limit;

        if ($search !== "") {
            $products = $this->productModel->searchPaginated(
                $search,
                $limit,
                $offset
            );
        } else {
            $products = $this->productModel->getPaginated(
                $limit,
                $offset
            );
        }

        require __DIR__ . "/../views/admin/products/index.php";
    }

    public function inventory()
    {
        $limit = 10;
        $searchInput = $_GET["q"] ?? "";
        $search = is_string($searchInput) ? mb_substr(trim($searchInput), 0, 100) : "";
        $pageInput = filter_input(INPUT_GET, "page", FILTER_VALIDATE_INT);
        $currentPage = ($pageInput && $pageInput > 0) ? $pageInput : 1;
        $totalProducts = $this->productModel->getInventoryTotalCount($search);
        $inventorySummary = $this->productModel->getInventorySummary();
        $totalUnits = $inventorySummary["unit_count"];
        $totalPages = max(1, (int) ceil($totalProducts / $limit));
        $currentPage = min($currentPage, $totalPages);
        $offset = ($currentPage - 1) * $limit;
        $products = $this->productModel->getInventoryPaginated($search, $limit, $offset);
        $startProduct = $totalProducts > 0 ? $offset + 1 : 0;
        $endProduct = min($offset + $limit, $totalProducts);
        $startPage = max(1, $currentPage - 2);
        $endPage = min($totalPages, $currentPage + 2);

        $paginationUrl = function ($page) use ($search) {
            return "index.php?" . http_build_query([
                "controller" => "adminProduct",
                "action" => "inventory",
                "q" => $search,
                "page" => (int) $page,
            ]);
        };

        require __DIR__ . "/../views/admin/products/inventory.php";
    }

    // Hiển thị form thêm sản phẩm
    public function createProduct()
    {
        $categories = $this->productModel->getCategories();
        $brands = $this->productModel->getBrands();
        $error = "";

        require __DIR__ . "/../views/admin/products/create.php";
    }

    // Lưu sản phẩm mới
    public function storeProduct()
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            http_response_code(405);
            exit("Phương thức không được hỗ trợ.");
        }

        if (!$this->checkCsrf()) {
            http_response_code(403);
            exit("Yêu cầu không hợp lệ. Vui lòng tải lại trang.");
        }

        $result = $this->productModel->create($_POST, $_FILES);

        if ($result === true) {
            $_SESSION["admin_success"] = "Thêm sản phẩm thành công.";
            $this->redirect("products");
        }

        $error = $result;
        $categories = $this->productModel->getCategories();
        $brands = $this->productModel->getBrands();

        require __DIR__ . "/../views/admin/products/create.php";
    }

    // Hiển thị form sửa sản phẩm
    public function editProduct()
    {
        $id = (int) ($_GET["id"] ?? 0);
        $product = $this->productModel->getById($id);

        if (!$product) {
            http_response_code(404);
            exit("Không tìm thấy sản phẩm.");
        }

        $images = $this->productModel->getImages($id);
        $categories = $this->productModel->getCategories();
        $brands = $this->productModel->getBrands();
        $error = "";

        require __DIR__ . "/../views/admin/products/edit.php";
    }

    // Cập nhật sản phẩm
    public function updateProduct()
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            http_response_code(405);
            exit("Phương thức không được hỗ trợ.");
        }

        if (!$this->checkCsrf()) {
            http_response_code(403);
            exit("Yêu cầu không hợp lệ.");
        }

        $id = (int) ($_GET["id"] ?? 0);
        $product = $this->productModel->getById($id);

        if (!$product) {
            http_response_code(404);
            exit("Không tìm thấy sản phẩm.");
        }

        $result = $this->productModel->updateDetails($id, $_POST);

        if ($result !== true) {
            $error = $result;
            $product = $this->productModel->getById($id);
            $images = $this->productModel->getImages($id);
            $categories = $this->productModel->getCategories();
            $brands = $this->productModel->getBrands();

            require __DIR__ . "/../views/admin/products/edit.php";
            return;
        }

        $imageResult = $this->productModel->addImages($id, $_FILES);

        if ($imageResult !== true) {
            $_SESSION["admin_error"] =
                "Đã cập nhật thông tin nhưng ảnh mới chưa được lưu: "
                . $imageResult;
        } else {
            $_SESSION["admin_success"] = "Cập nhật sản phẩm thành công.";
        }

        // Chọn ảnh đại diện trong danh sách ảnh sản phẩm
        $mainImageId = (int) ($_POST["main_image_id"] ?? 0);
        $images = $this->productModel->getImages($id);

        foreach ($images as $image) {
            if ((int) $image["id"] === $mainImageId) {
                $stmt = $this->conn->prepare(
                    "UPDATE products SET image = ? WHERE id = ?"
                );

                $stmt->bind_param("si", $image["image"], $id);
                $stmt->execute();
                $stmt->close();
                break;
            }
        }

        $this->redirect("products");
    }

    // Xóa ảnh của sản phẩm
    public function deleteProductImage()
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            http_response_code(405);
            exit("Phương thức không được hỗ trợ.");
        }

        if (!$this->checkCsrf()) {
            http_response_code(403);
            exit("Yêu cầu không hợp lệ.");
        }

        $productId = (int) ($_POST["product_id"] ?? 0);
        $imageId = (int) ($_POST["image_id"] ?? 0);

        $result = $this->productModel->deleteImage(
            $productId,
            $imageId
        );

        if ($result === true) {
            $_SESSION["admin_success"] = "Đã xóa ảnh.";
        } else {
            $_SESSION["admin_error"] = $result;
        }

        header(
            "Location: " . BASE_URL .
            "index.php?controller=adminProduct&action=editProduct&id="
            . $productId
        );
        exit;
    }

    // Xóa hoặc ngừng kinh doanh sản phẩm
    public function deleteProduct()
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            http_response_code(405);
            exit("Phương thức không được hỗ trợ.");
        }

        if (!$this->checkCsrf()) {
            $_SESSION["admin_error"] = "Phiên xác thực không hợp lệ.";
            $this->redirect("products");
        }

        $id = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);

        if (!$id || $id <= 0) {
            $_SESSION["admin_error"] = "ID sản phẩm không hợp lệ.";
            $this->redirect("products");
        }

        $result = $this->productModel->deleteProduct($id);

        if (
            $result["status"] === "deleted" ||
            $result["status"] === "deactivated"
        ) {
            $_SESSION["admin_success"] = $result["message"];
        } else {
            $_SESSION["admin_error"] = $result["message"];
        }

        $this->redirect("products");
    }

    // Kiểm tra CSRF token
    private function checkCsrf()
    {
        return isset($_SESSION["csrf_token"])
            && hash_equals(
                $_SESSION["csrf_token"],
                $_POST["csrf_token"] ?? ""
            );
    }

    // Chuyển về trang quản lý sản phẩm
    private function redirect($action)
    {
        header(
            "Location: " . BASE_URL .
            "index.php?controller=adminProduct&action=" . $action
        );
        exit;
    }
}
