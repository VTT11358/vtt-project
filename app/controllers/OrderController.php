<?php

require_once __DIR__ . "/../middleware/AuthMiddleware.php";
require_once __DIR__ . "/../models/Order.php";

class OrderController
{
    private $orderModel;

    public function __construct($conn)
    {
        AuthMiddleware::requireLogin();
        if (empty($_SESSION["csrf_token"])) {
            $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
        }
        $this->orderModel = new Order($conn);
    }

    public function index(): void
    {
        $limit = 10;
        $userId = (int) $_SESSION["user"]["id"];
        $totalOrders = $this->orderModel->getTotalCountByUserId($userId);
        $totalPages = max(1, (int) ceil($totalOrders / $limit));
        $requestedPage = filter_var(
            $_GET["page"] ?? 1,
            FILTER_VALIDATE_INT,
            ["options" => ["min_range" => 1]]
        );
        $currentPage = min(
            $requestedPage === false ? 1 : $requestedPage,
            $totalPages
        );
        $offset = ($currentPage - 1) * $limit;
        $orders = $this->orderModel->getByUserId(
            $userId,
            $limit,
            $offset
        );
        $startOrder = $totalOrders > 0 ? $offset + 1 : 0;
        $endOrder = min($offset + count($orders), $totalOrders);

        $statusLabels = [
            "pending" => "Chờ xác nhận",
            "confirmed" => "Đã xác nhận",
            "shipping" => "Đang giao",
            "completed" => "Hoàn thành",
            "cancelled" => "Đã hủy",
        ];
        $paymentStatusLabels = [
            "pending" => "Chưa thanh toán",
            "paid" => "Đã thanh toán",
            "failed" => "Thất bại",
            "refunded" => "Đã hoàn tiền",
        ];
        $paymentMethodLabels = [
            "COD" => "Thanh toán khi nhận hàng",
            "bank" => "Chuyển khoản",
            "momo" => "Ví MoMo",
            "vnpay" => "VNPay",
        ];

        require __DIR__ . "/../views/orders/index.php";
    }

    public function detail(): void
    {
        $id = filter_input(
            INPUT_GET,
            "id",
            FILTER_VALIDATE_INT,
            ["options" => ["min_range" => 1]]
        );

        if (!$id) {
            http_response_code(404);
            exit("Không tìm thấy đơn hàng.");
        }

        $order = $this->orderModel->getByIdForUser(
            $id,
            (int) $_SESSION["user"]["id"]
        );

        if (!$order) {
            http_response_code(404);
            exit("Không tìm thấy đơn hàng.");
        }

        $items = $this->orderModel->getItems($id);
        $statusLabels = [
            "pending" => "Chờ xác nhận",
            "confirmed" => "Đã xác nhận",
            "shipping" => "Đang giao",
            "completed" => "Hoàn thành",
            "cancelled" => "Đã hủy",
        ];
        $paymentStatusLabels = [
            "pending" => "Chưa thanh toán",
            "paid" => "Đã thanh toán",
            "failed" => "Thất bại",
            "refunded" => "Đã hoàn tiền",
        ];
        $paymentMethodLabels = [
            "COD" => "Thanh toán khi nhận hàng",
            "bank" => "Chuyển khoản",
            "momo" => "Ví MoMo",
            "vnpay" => "VNPay",
        ];
        $success = $_SESSION["order_success"] ?? "";
        $error = $_SESSION["order_error"] ?? "";
        unset($_SESSION["order_success"], $_SESSION["order_error"]);

        require __DIR__ . "/../views/orders/detail.php";
    }

    public function cancel(): void
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            http_response_code(405);
            header("Allow: POST");
            exit("Phương thức không được hỗ trợ.");
        }

        $token = $_POST["csrf_token"] ?? "";
        if (!is_string($token) || !hash_equals($_SESSION["csrf_token"], $token)) {
            $_SESSION["order_error"] = "Phiên xác thực không hợp lệ. Vui lòng thử lại.";
            $this->redirectToOrderDetail($_POST["id"] ?? null);
        }

        $id = filter_var(
            $_POST["id"] ?? null,
            FILTER_VALIDATE_INT,
            ["options" => ["min_range" => 1]]
        );
        if ($id === false) {
            $_SESSION["order_error"] = "ID đơn hàng không hợp lệ.";
            $this->redirectToOrderList();
        }

        $cancelled = $this->orderModel->cancelPendingOrderForUser(
            (int) $id,
            (int) $_SESSION["user"]["id"]
        );
        $_SESSION[$cancelled ? "order_success" : "order_error"] = $cancelled
            ? "Đơn hàng đã được hủy thành công."
            : "Không thể hủy đơn này. Chỉ đơn chưa xác nhận và chưa thanh toán mới được hủy.";
        $this->redirectToOrderDetail((int) $id);
    }

    private function redirectToOrderDetail($id): void
    {
        $validId = filter_var(
            $id,
            FILTER_VALIDATE_INT,
            ["options" => ["min_range" => 1]]
        );
        if ($validId === false) {
            $this->redirectToOrderList();
        }

        header(
            "Location: " . BASE_URL .
            "index.php?controller=order&action=detail&id=" . (int) $validId
        );
        exit;
    }

    private function redirectToOrderList(): void
    {
        header("Location: " . BASE_URL . "index.php?controller=order&action=index");
        exit;
    }
}
