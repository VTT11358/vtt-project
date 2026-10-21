<?php

require_once __DIR__ . "/../models/Cart.php";

class CartController
{
    private $cartModel;

    public function __construct($conn)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION["csrf_token"])) {
            $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
        }

        $this->cartModel = new Cart($conn);
    }

    public function index(): void
    {
        $items = $this->cartModel->getItems();
        $total = $this->cartModel->getTotal();

        $success = $_SESSION["cart_success"] ?? "";
        $error = $_SESSION["cart_error"] ?? "";
        unset($_SESSION["cart_success"], $_SESSION["cart_error"]);

        require __DIR__ . "/../views/cart/index.php";
    }

    public function add(): void
    {
        $this->requirePostRequest();

        if (!$this->checkCsrf()) {
            $this->setError("Phiên xác thực không hợp lệ. Vui lòng thử lại.");
            $this->redirectToCart();
        }

        $productId = $this->postInteger("product_id");
        $quantity = $this->postInteger("quantity");
        if (!$productId || !$quantity) {
            $this->setError("Sản phẩm hoặc số lượng không hợp lệ.");
            $this->redirectToCart();
        }

        $error = $this->cartModel->add($productId, $quantity);
        if ($error !== "") {
            $this->setError($error);
            header(
                "Location: " . BASE_URL .
                "index.php?controller=product&action=detail&id=" . $productId
            );
            exit;
        }

        $_SESSION["cart_success"] = "Đã thêm sản phẩm vào giỏ hàng.";
        $this->redirectToCart();
    }

    public function update(): void
    {
        $this->requirePostRequest();

        if (!$this->checkCsrf()) {
            $this->setError("Phiên xác thực không hợp lệ. Vui lòng thử lại.");
            $this->redirectToCart();
        }

        $productId = $this->postInteger("product_id");
        $quantity = $this->postInteger("quantity");
        if (!$productId || !$quantity) {
            $this->setError("Sản phẩm hoặc số lượng không hợp lệ.");
            $this->redirectToCart();
        }

        $error = $this->cartModel->update($productId, $quantity);
        if ($error !== "") {
            $this->setError($error);
            $this->redirectToCart();
        }

        $this->redirectToCart();
    }

    public function remove(): void
    {
        $this->requirePostRequest();

        if (!$this->checkCsrf()) {
            $this->setError("Phiên xác thực không hợp lệ. Vui lòng thử lại.");
            $this->redirectToCart();
        }

        $productId = $this->postInteger("product_id");
        if (!$productId || !$this->cartModel->remove($productId)) {
            $this->setError("Không tìm thấy sản phẩm trong giỏ hàng.");
            $this->redirectToCart();
        }

        $_SESSION["cart_success"] = "Đã xóa sản phẩm khỏi giỏ hàng.";
        $this->redirectToCart();
    }

    private function postInteger(string $key): int
    {
        $value = filter_var(
            $_POST[$key] ?? null,
            FILTER_VALIDATE_INT,
            ["options" => ["min_range" => 1]]
        );

        return $value === false ? 0 : (int) $value;
    }

    private function checkCsrf(): bool
    {
        $token = $_POST["csrf_token"] ?? "";

        return is_string($token)
            && hash_equals($_SESSION["csrf_token"], $token);
    }

    private function requirePostRequest(): void
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            http_response_code(405);
            header("Allow: POST");
            exit("Phương thức không được hỗ trợ.");
        }
    }

    private function setError(string $message): void
    {
        $_SESSION["cart_error"] = $message;
    }

    private function redirectToCart(): void
    {
        header("Location: " . BASE_URL . "index.php?controller=cart&action=index");
        exit;
    }
}
