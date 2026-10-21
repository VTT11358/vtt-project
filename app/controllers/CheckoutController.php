<?php

require_once __DIR__ . "/../middleware/AuthMiddleware.php";
require_once __DIR__ . "/../models/Cart.php";
require_once __DIR__ . "/../models/User.php";
require_once __DIR__ . "/../models/Order.php";
require_once __DIR__ . "/../helpers/VnPay.php";

class CheckoutController
{
    private $conn;
    private $cartModel;
    private $userModel;
    private $orderModel;

    public function __construct($conn)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION["csrf_token"])) {
            $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
        }

        $this->conn = $conn;
        $this->cartModel = new Cart($conn);
        $this->userModel = new User($conn);
        $this->orderModel = new Order($conn);
    }

    public function index()
    {
        $selectionProvided = ($_GET["selection"] ?? "") === "1";
        $selectedProductIds = $selectionProvided
            ? $this->parseProductIds($_GET["selected_product_ids"] ?? [])
            : null;
        if ($selectionProvided && !$selectedProductIds) {
            $_SESSION["cart_error"] = "Vui lòng chọn ít nhất một sản phẩm để thanh toán.";
            $this->redirectCart();
        }

        $this->requireCustomerLogin($selectedProductIds);

        $cartData = $this->buildCart($selectedProductIds);

        if (empty($cartData["items"]) || $cartData["total"] <= 0) {
            $_SESSION["cart_error"] = $selectionProvided
                ? "Sản phẩm đã chọn không còn trong giỏ hoặc không thể thanh toán."
                : "Giỏ hàng trống hoặc sản phẩm không còn khả dụng.";
            $this->redirectCart();
        }

        $profile = $this->userModel->findById((int) $_SESSION["user"]["id"]);
        $old = [
            "customer_name" => $profile["name"] ?? ($_SESSION["user"]["name"] ?? ""),
            "email" => $profile["email"] ?? ($_SESSION["user"]["email"] ?? ""),
            "phone" => $profile["phone"] ?? "",
            "address" => $profile["address"] ?? "",
            "note" => "",
            "payment_method" => "COD",
        ];
        $error = $_SESSION["checkout_error"] ?? "";
        unset($_SESSION["checkout_error"]);

        $items = $cartData["items"];
        $total = $cartData["total"];

        require __DIR__ . "/../views/checkout/index.php";
    }

    public function place()
    {
        $selectionProvided = ($_POST["selection"] ?? "") === "1";
        $selectedProductIds = $selectionProvided
            ? $this->parseProductIds($_POST["selected_product_ids"] ?? [])
            : null;
        if ($selectionProvided && !$selectedProductIds) {
            $_SESSION["cart_error"] = "Vui lòng chọn ít nhất một sản phẩm để thanh toán.";
            $this->redirectCart();
        }

        $this->requireCustomerLogin($selectedProductIds);

        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            http_response_code(405);
            exit("Phương thức không được hỗ trợ.");
        }

        $token = $_POST["csrf_token"] ?? "";
        if (!is_string($token) || !hash_equals($_SESSION["csrf_token"], $token)) {
            $_SESSION["checkout_error"] = "Phiên xác thực không hợp lệ. Vui lòng thử lại.";
            $this->redirectCheckout();
        }

        $cartData = $this->buildCart($selectedProductIds);
        if (empty($cartData["items"]) || $cartData["total"] <= 0) {
            $_SESSION["cart_error"] = $selectionProvided
                ? "Sản phẩm đã chọn không còn trong giỏ hoặc không thể thanh toán."
                : "Giỏ hàng trống hoặc sản phẩm không còn khả dụng.";
            $this->redirectCart();
        }

        $data = [
            "customer_name" => trim($_POST["customer_name"] ?? ""),
            "email" => trim($_POST["email"] ?? ""),
            "phone" => trim($_POST["phone"] ?? ""),
            "address" => trim($_POST["address"] ?? ""),
            "note" => trim($_POST["note"] ?? ""),
            "payment_method" => trim($_POST["payment_method"] ?? "COD"),
        ];

        $error = $this->validate($data);
        if ($error !== "") {
            $old = $data;
            $items = $cartData["items"];
            $total = $cartData["total"];
            $selectedProductIds = $selectedProductIds ?? [];
            require __DIR__ . "/../views/checkout/index.php";
            return;
        }

        $orderData = [
            "user_id" => (int) $_SESSION["user"]["id"],
            "customer_name" => $data["customer_name"],
            "phone" => $data["phone"],
            "email" => $data["email"],
            "address" => $data["address"],
            "note" => $data["note"],
        ];
        $result = $data["payment_method"] === "vnpay"
            ? $this->orderModel->createVnPayIntent($orderData, $cartData["items"])
            : $this->orderModel->createFromCart($orderData, $cartData["items"], "COD");

        if (empty($result["ok"])) {
            $old = $data;
            $error = $result["error"] ?? "Không thể đặt hàng. Vui lòng thử lại.";
            $items = $cartData["items"];
            $total = $cartData["total"];
            $selectedProductIds = $selectedProductIds ?? [];
            require __DIR__ . "/../views/checkout/index.php";
            return;
        }

        if ($data["payment_method"] === "vnpay") {
            $this->redirectToVnPay(
                (float) $result["total"],
                (string) $result["transaction_code"]
            );
        }

        $this->cartModel->refreshSessionCache();
        $_SESSION["checkout_order_id"] = (int) $result["order_id"];
        header(
            "Location: " . BASE_URL .
            "index.php?controller=checkout&action=success&id=" . (int) $result["order_id"]
        );
        exit;
    }

    public function vnpayReturn()
    {
        $input = $_GET;
        unset($input["controller"], $input["action"]);

        if (!VnPay::verify($input)) {
            $message = "Chữ ký VNPay không hợp lệ. Giao dịch không được xác nhận.";
            $success = false;
            $order = null;
            require __DIR__ . "/../views/checkout/result.php";
            return;
        }

        $txnRef = (string) ($input["vnp_TxnRef"] ?? "");
        $responseCode = (string) ($input["vnp_ResponseCode"] ?? "");
        $txnStatus = (string) ($input["vnp_TransactionStatus"] ?? "");
        $amount = (int) ($input["vnp_Amount"] ?? 0);
        $intent = $this->orderModel->findVnPayIntent($txnRef);

        if (!$intent) {
            $message = "Không tìm thấy giao dịch thanh toán tương ứng.";
            $success = false;
            $order = null;
            require __DIR__ . "/../views/checkout/result.php";
            return;
        }

        if ($responseCode === "00" && $txnStatus === "00") {
            $result = $this->orderModel->completeVnPayIntent($txnRef, $amount);
            $success = !empty($result["ok"]);
            $order = $success
                ? $this->orderModel->getById((int) $result["order_id"])
                : null;
            $items = $success
                ? $this->orderModel->getItems((int) $result["order_id"])
                : [];
            $message = $success
                ? "Thanh toán VNPay thành công, đơn hàng đã được tạo."
                : ($result["error"] ?? "Không thể xác nhận đơn hàng sau thanh toán.");
            if ($success) {
                $this->cartModel->refreshSessionCache();
            }
        } else {
            $this->orderModel->failVnPayIntent($txnRef);
            $order = null;
            $success = false;
            $message = "Thanh toán VNPay không thành công hoặc đã bị hủy (mã " . $responseCode . ").";
        }

        require __DIR__ . "/../views/checkout/result.php";
    }

    public function vnpayIpn()
    {
        $input = $_GET;
        unset($input["controller"], $input["action"]);
        header("Content-Type: application/json; charset=utf-8");

        if (!VnPay::verify($input)) {
            echo json_encode(["RspCode" => "97", "Message" => "Invalid signature"]);
            return;
        }

        $txnRef = (string) ($input["vnp_TxnRef"] ?? "");
        $responseCode = (string) ($input["vnp_ResponseCode"] ?? "");
        $txnStatus = (string) ($input["vnp_TransactionStatus"] ?? "");
        $amount = (int) ($input["vnp_Amount"] ?? 0);
        $intent = $this->orderModel->findVnPayIntent($txnRef);

        if (!$intent) {
            echo json_encode(["RspCode" => "01", "Message" => "Transaction not found"]);
            return;
        }

        $expected = (int) round(((float) $intent["amount"]) * 100);
        if ($expected !== $amount) {
            echo json_encode(["RspCode" => "04", "Message" => "Invalid amount"]);
            return;
        }

        if (($intent["status"] ?? "") === "paid") {
            echo json_encode(["RspCode" => "02", "Message" => "Transaction already confirmed"]);
            return;
        }

        if ($responseCode === "00" && $txnStatus === "00") {
            $result = $this->orderModel->completeVnPayIntent($txnRef, $amount);
            if (empty($result["ok"])) {
                echo json_encode(["RspCode" => "99", "Message" => "Could not create order"]);
                return;
            }
        } else {
            $this->orderModel->failVnPayIntent($txnRef);
        }

        echo json_encode(["RspCode" => "00", "Message" => "Confirm Success"]);
    }

    public function success()
    {
        $this->requireCustomerLogin();

        $id = (int) ($_GET["id"] ?? 0);
        $order = $this->orderModel->getById($id);

        if (
            !$order
            || (int) $order["user_id"] !== (int) $_SESSION["user"]["id"]
        ) {
            http_response_code(404);
            exit("Không tìm thấy đơn hàng.");
        }

        $items = $this->orderModel->getItems($id);
        $success = true;
        $message = $order["payment_method"] === "COD"
            ? "Đặt hàng thành công. Bạn sẽ thanh toán khi nhận hàng."
            : "Đơn hàng đã được ghi nhận.";

        require __DIR__ . "/../views/checkout/result.php";
    }

    private function redirectToVnPay(float $total, string $txnRef): void
    {
        $expire = (new DateTime("now", new DateTimeZone("Asia/Ho_Chi_Minh")))
            ->modify("+15 minutes")
            ->format("YmdHis");
        $created = (new DateTime("now", new DateTimeZone("Asia/Ho_Chi_Minh")))
            ->format("YmdHis");

        $url = VnPay::createPaymentUrl([
            "vnp_Amount" => (int) round($total * 100),
            "vnp_CreateDate" => $created,
            "vnp_ExpireDate" => $expire,
            "vnp_IpAddr" => VnPay::clientIp(),
            "vnp_OrderInfo" => "Thanh toan don hang " . $txnRef,
            "vnp_OrderType" => "other",
            "vnp_ReturnUrl" => VnPay::returnUrl(),
            "vnp_TxnRef" => $txnRef,
        ]);

        header("Location: " . $url);
        exit;
    }

    private function validate(array $data): string
    {
        if ($data["customer_name"] === "" || $data["phone"] === "" || $data["address"] === "") {
            return "Vui lòng nhập họ tên, số điện thoại và địa chỉ giao hàng.";
        }

        if (mb_strlen($data["customer_name"]) > 100) {
            return "Họ tên không được vượt quá 100 ký tự.";
        }

        if (mb_strlen($data["phone"]) > 20) {
            return "Số điện thoại không hợp lệ.";
        }

        if ($data["email"] !== "" && !filter_var($data["email"], FILTER_VALIDATE_EMAIL)) {
            return "Email không đúng định dạng.";
        }

        if (mb_strlen($data["address"]) > 255) {
            return "Địa chỉ không được vượt quá 255 ký tự.";
        }

        if (!in_array($data["payment_method"], ["COD", "vnpay"], true)) {
            return "Phương thức thanh toán không hợp lệ.";
        }

        return "";
    }

    private function buildCart(?array $selectedProductIds = null): array
    {
        $items = array_values(array_filter(
            $this->cartModel->getItems(),
            static function (array $item) use ($selectedProductIds): bool {
                return $item["product"] !== null
                    && ($selectedProductIds === null
                        || in_array((int) $item["product_id"], $selectedProductIds, true));
            }
        ));
        $total = array_reduce(
            $items,
            static fn (float $sum, array $item): float =>
                $sum + (float) $item["price"] * (int) $item["quantity"],
            0.0
        );

        return [
            "items" => $items,
            "total" => $total,
        ];
    }

    private function parseProductIds($value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $ids = [];
        foreach ($value as $productId) {
            if (!is_string($productId) && !is_int($productId)) {
                continue;
            }

            $productId = filter_var(
                $productId,
                FILTER_VALIDATE_INT,
                ["options" => ["min_range" => 1]]
            );
            if ($productId !== false) {
                $ids[] = (int) $productId;
            }
        }

        return array_values(array_unique($ids));
    }

    private function requireCustomerLogin(?array $selectedProductIds = null): void
    {
        if (!empty($_SESSION["user"]["id"])) {
            return;
        }

        $checkoutParams = [
            "controller" => "checkout",
            "action" => "index",
        ];
        if ($selectedProductIds !== null) {
            $checkoutParams["selection"] = "1";
            $checkoutParams["selected_product_ids"] = $selectedProductIds;
        }
        $_SESSION["redirect_after_login"] = BASE_URL . "index.php?"
            . http_build_query($checkoutParams);
        $_SESSION["auth_success"] = "Vui lòng đăng nhập để thanh toán.";

        header(
            "Location: " . BASE_URL .
            "index.php?controller=auth&action=login"
        );
        exit;
    }

    private function redirectCart(): void
    {
        header("Location: " . BASE_URL . "index.php?controller=cart&action=index");
        exit;
    }

    private function redirectCheckout(): void
    {
        header("Location: " . BASE_URL . "index.php?controller=checkout&action=index");
        exit;
    }
}
