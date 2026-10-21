<?php

require_once __DIR__ . "/../middleware/AuthMiddleware.php";
require_once __DIR__ . "/../models/AdminOrder.php";
require_once __DIR__ . "/../models/AdminProduct.php";

class AdminOrderController
{
    private $orderModel;
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
        $this->orderModel = new AdminOrder($conn);
    }

    public function index()
    {
        $limit = 10;
        $search = trim($_GET["q"] ?? "");
        $search = mb_substr($search, 0, 100);
        $status = trim($_GET["status"] ?? "");
        $paymentStatus = trim($_GET["payment_status"] ?? "");

        if ($status !== "" && !isset(AdminOrder::STATUSES[$status])) {
            $status = "";
        }

        if ($paymentStatus !== "" && !isset(AdminOrder::PAYMENT_STATUSES[$paymentStatus])) {
            $paymentStatus = "";
        }

        $currentPage = filter_input(INPUT_GET, "page", FILTER_VALIDATE_INT);
        $currentPage = ($currentPage && $currentPage > 0) ? $currentPage : 1;

        $totalOrders = $this->orderModel->getTotalCount($search, $status, $paymentStatus);
        $totalPages = max(1, (int) ceil($totalOrders / $limit));
        $currentPage = min($currentPage, $totalPages);
        $offset = ($currentPage - 1) * $limit;

        $orders = $this->orderModel->getPaginated(
            $limit,
            $offset,
            $search,
            $status,
            $paymentStatus
        );

        require __DIR__ . "/../views/admin/orders/index.php";
    }

    public function stats()
    {
        $today = new DateTimeImmutable("today");
        $defaultFrom = $today->modify("first day of this month")->format("Y-m-d");
        $defaultTo = $today->format("Y-m-d");
        $dateFromInput = $_GET["date_from"] ?? $defaultFrom;
        $dateToInput = $_GET["date_to"] ?? $defaultTo;
        $dateFrom = is_string($dateFromInput) ? trim($dateFromInput) : "";
        $dateTo = is_string($dateToInput) ? trim($dateToInput) : "";
        $currentYear = (int) $today->format("Y");
        $chartYearInput = $_GET["chart_year"] ?? (string) $currentYear;
        $chartYearValue = is_string($chartYearInput)
            ? filter_var($chartYearInput, FILTER_VALIDATE_INT)
            : false;
        $chartYear = $chartYearValue !== false ? (int) $chartYearValue : 0;
        $chartYearError = "";

        if ($chartYear < 2000 || $chartYear > $currentYear + 1) {
            $chartYearError = "Năm biểu đồ không hợp lệ.";
            $chartYear = $currentYear;
        }

        $chartYears = range($currentYear, max(2000, $currentYear - 10));
        if ($chartYear === $currentYear + 1) {
            $chartYears[] = $chartYear;
        }
        if (!in_array($chartYear, $chartYears, true)) {
            $chartYears[] = $chartYear;
        }
        rsort($chartYears);
        $dateError = "";

        $fromDate = DateTimeImmutable::createFromFormat("!Y-m-d", $dateFrom);
        $toDate = DateTimeImmutable::createFromFormat("!Y-m-d", $dateTo);
        $validFrom = $fromDate && $fromDate->format("Y-m-d") === $dateFrom;
        $validTo = $toDate && $toDate->format("Y-m-d") === $dateTo;

        if (!$validFrom || !$validTo || $fromDate > $toDate) {
            $dateError = "Khoảng ngày không hợp lệ. Vui lòng chọn ngày bắt đầu trước hoặc bằng ngày kết thúc.";
            $dateFrom = $defaultFrom;
            $dateTo = $defaultTo;
        }

        $statistics = $this->orderModel->getStatistics(
            $dateFrom . " 00:00:00",
            $dateTo
        );
        $revenueChart = $this->orderModel->getMonthlyRevenue($chartYear);
        $inventorySummary = (new AdminProduct($this->conn))->getInventorySummary();

        require __DIR__ . "/../views/admin/orders/stats.php";
    }

    public function show()
    {
        $id = (int) ($_GET["id"] ?? 0);
        $order = $this->orderModel->getById($id);

        if (!$order) {
            http_response_code(404);
            exit("Không tìm thấy đơn hàng.");
        }

        $items = $this->orderModel->getItems($id);
        $payments = $this->orderModel->getPayments($id);
        $nextStatuses = AdminOrder::allowedNextStatuses($order["status"] ?? "");

        require __DIR__ . "/../views/admin/orders/show.php";
    }

    public function updateStatus()
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            http_response_code(405);
            exit("Phương thức không được hỗ trợ.");
        }

        if (!$this->checkCsrf()) {
            $_SESSION["admin_error"] = "Phiên xác thực không hợp lệ.";
            $this->redirectIndex();
        }

        $id = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);
        $status = trim($_POST["status"] ?? "");

        if (!$id || $id <= 0) {
            $_SESSION["admin_error"] = "ID đơn hàng không hợp lệ.";
            $this->redirectIndex();
        }

        $order = $this->orderModel->getById($id);
        if (!$order) {
            $_SESSION["admin_error"] = "Không tìm thấy đơn hàng.";
            $this->redirectIndex();
        }

        $allowed = AdminOrder::allowedNextStatuses($order["status"] ?? "");

        if (!in_array($status, $allowed, true)) {
            $_SESSION["admin_error"] = "Không thể chuyển đơn hàng sang trạng thái này.";
            $this->redirectShow($id);
        }

        try {
            $updated = $this->orderModel->updateStatus($id, $status);
        } catch (Throwable $e) {
            $updated = false;
        }

        $_SESSION[$updated ? "admin_success" : "admin_error"] = $updated
            ? "Đã cập nhật trạng thái đơn hàng."
            : "Không thể cập nhật trạng thái đơn hàng.";

        $this->redirectShow($id);
    }

    public function updatePayment()
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            http_response_code(405);
            exit("Phương thức không được hỗ trợ.");
        }

        if (!$this->checkCsrf()) {
            $_SESSION["admin_error"] = "Phiên xác thực không hợp lệ.";
            $this->redirectIndex();
        }

        $id = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);
        $paymentStatus = trim($_POST["payment_status"] ?? "");

        if (!$id || $id <= 0) {
            $_SESSION["admin_error"] = "ID đơn hàng không hợp lệ.";
            $this->redirectIndex();
        }

        $order = $this->orderModel->getById($id);
        if (!$order) {
            $_SESSION["admin_error"] = "Không tìm thấy đơn hàng.";
            $this->redirectIndex();
        }

        if (!isset(AdminOrder::PAYMENT_STATUSES[$paymentStatus])) {
            $_SESSION["admin_error"] = "Trạng thái thanh toán không hợp lệ.";
            $this->redirectShow($id);
        }

        if (($order["status"] ?? "") === "cancelled" && $paymentStatus === "paid") {
            $_SESSION["admin_error"] = "Không thể đánh dấu đã thanh toán cho đơn đã hủy.";
            $this->redirectShow($id);
        }

        try {
            $updated = $this->orderModel->updatePaymentStatus($id, $paymentStatus);
        } catch (Throwable $e) {
            $updated = false;
        }

        $_SESSION[$updated ? "admin_success" : "admin_error"] = $updated
            ? "Đã cập nhật trạng thái thanh toán."
            : "Không thể cập nhật trạng thái thanh toán.";

        $this->redirectShow($id);
    }

    private function checkCsrf(): bool
    {
        return isset($_SESSION["csrf_token"])
            && hash_equals(
                $_SESSION["csrf_token"],
                $_POST["csrf_token"] ?? ""
            );
    }

    private function redirectIndex(): void
    {
        header(
            "Location: " . BASE_URL .
            "index.php?controller=adminOrder&action=index"
        );
        exit;
    }

    private function redirectShow(int $id): void
    {
        header(
            "Location: " . BASE_URL .
            "index.php?controller=adminOrder&action=show&id=" . $id
        );
        exit;
    }
}
