<?php

class AdminOrder
{
    public const STATUSES = [
        "pending" => "Chờ xác nhận",
        "confirmed" => "Đã xác nhận",
        "shipping" => "Đang giao",
        "completed" => "Hoàn thành",
        "cancelled" => "Đã hủy",
    ];

    public const PAYMENT_STATUSES = [
        "pending" => "Chưa thanh toán",
        "paid" => "Đã thanh toán",
        "failed" => "Thất bại",
        "refunded" => "Đã hoàn tiền",
    ];

    public const PAYMENT_METHODS = [
        "COD" => "Thanh toán khi nhận hàng",
        "bank" => "Chuyển khoản",
        "momo" => "Ví MoMo",
        "vnpay" => "VNPay",
    ];

    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function getTotalCount($search = "", $status = "", $paymentStatus = "", $dateFrom = "", $dateTo = "")
    {
        [$whereSql, $types, $params] = $this->buildFilters(
            $search,
            $status,
            $paymentStatus,
            $dateFrom,
            $dateTo
        );

        $sql = "SELECT COUNT(*) AS total FROM orders o {$whereSql}";
        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            return 0;
        }

        if ($types !== "") {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return (int) ($row["total"] ?? 0);
    }

    public function getStatistics(string $dateFrom, string $dateTo): array
    {
        $sql = "SELECT status, COUNT(*) AS order_count,
                       SUM(total_amount) AS order_amount
                FROM orders
                WHERE created_at >= ? AND created_at < DATE_ADD(?, INTERVAL 1 DAY)
                GROUP BY status";
        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            throw new RuntimeException("Không thể chuẩn bị truy vấn thống kê đơn hàng.");
        }

        $stmt->bind_param("ss", $dateFrom, $dateTo);

        if (!$stmt->execute()) {
            $stmt->close();
            throw new RuntimeException("Không thể tải thống kê đơn hàng.");
        }

        $statusRows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $sql = "SELECT payment_status, COUNT(*) AS order_count
                FROM orders
                WHERE created_at >= ? AND created_at < DATE_ADD(?, INTERVAL 1 DAY)
                GROUP BY payment_status";
        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            throw new RuntimeException("Không thể chuẩn bị thống kê thanh toán.");
        }

        $stmt->bind_param("ss", $dateFrom, $dateTo);

        if (!$stmt->execute()) {
            $stmt->close();
            throw new RuntimeException("Không thể tải thống kê thanh toán.");
        }

        $paymentRows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return [
            "statuses" => $statusRows,
            "payments" => $paymentRows,
        ];
    }

    public function getMonthlyRevenue(int $year): array
    {
        $sql = "SELECT MONTH(created_at) AS month_number,
                       SUM(total_amount) AS revenue,
                       COUNT(*) AS order_count
                FROM orders
                WHERE status = 'completed'
                  AND created_at >= ?
                  AND created_at < ?
                GROUP BY MONTH(created_at)
                ORDER BY MONTH(created_at)";
        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            throw new RuntimeException("Không thể chuẩn bị biểu đồ doanh thu.");
        }

        $dateFrom = sprintf("%04d-01-01 00:00:00", $year);
        $dateTo = sprintf("%04d-01-01 00:00:00", $year + 1);
        $stmt->bind_param("ss", $dateFrom, $dateTo);

        if (!$stmt->execute()) {
            $stmt->close();
            throw new RuntimeException("Không thể tải dữ liệu biểu đồ doanh thu.");
        }

        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $rows;
    }

    public function getPaginated(
        $limit,
        $offset,
        $search = "",
        $status = "",
        $paymentStatus = "",
        $dateFrom = "",
        $dateTo = ""
    )
    {
        $limit = max(1, (int) $limit);
        $offset = max(0, (int) $offset);

        [$whereSql, $types, $params] = $this->buildFilters(
            $search,
            $status,
            $paymentStatus,
            $dateFrom,
            $dateTo
        );

        $sql = "SELECT o.id, o.user_id, o.customer_name, o.phone, o.email,
                       o.address, o.total_amount, o.payment_method, o.payment_status,
                       o.status, o.created_at, u.name AS user_name
                FROM orders o
                LEFT JOIN users u ON o.user_id = u.id
                {$whereSql}
                ORDER BY o.id DESC
                LIMIT ? OFFSET ?";

        $types .= "ii";
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return [];
        }

        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $orders;
    }

    public function getById($id)
    {
        $id = (int) $id;
        $sql = "SELECT o.*, u.name AS user_name, u.email AS user_email
                FROM orders o
                LEFT JOIN users u ON o.user_id = u.id
                WHERE o.id = ?
                LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return null;
        }

        $stmt->bind_param("i", $id);
        $stmt->execute();
        $order = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $order ?: null;
    }

    public function getItems($orderId)
    {
        $orderId = (int) $orderId;
        $sql = "SELECT oi.*, p.image AS product_image
                FROM order_items oi
                LEFT JOIN products p ON oi.product_id = p.id
                WHERE oi.order_id = ?
                ORDER BY oi.id ASC";

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return [];
        }

        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $items;
    }

    public function getPayments($orderId)
    {
        $orderId = (int) $orderId;
        $sql = "SELECT * FROM payments WHERE order_id = ? ORDER BY id DESC";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return [];
        }

        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $payments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $payments;
    }

    public function updateStatus($id, $status)
    {
        $id = (int) $id;
        $sql = "UPDATE orders SET status = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("si", $status, $id);
        $success = $stmt->execute();
        $stmt->close();

        return $success;
    }

    public function updatePaymentStatus($id, $paymentStatus)
    {
        $id = (int) $id;
        $sql = "UPDATE orders SET payment_status = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("si", $paymentStatus, $id);
        $success = $stmt->execute();
        $stmt->close();

        if ($success) {
            $this->syncLatestPayment($id, $paymentStatus);
        }

        return $success;
    }

    public static function statusBadge(string $status): string
    {
        return match ($status) {
            "pending" => "bg-warning text-dark",
            "confirmed" => "bg-info text-dark",
            "shipping" => "bg-primary",
            "completed" => "bg-success",
            "cancelled" => "bg-secondary",
            default => "bg-light text-dark",
        };
    }

    public static function paymentBadge(string $status): string
    {
        return match ($status) {
            "pending" => "bg-warning text-dark",
            "paid" => "bg-success",
            "failed" => "bg-danger",
            "refunded" => "bg-secondary",
            default => "bg-light text-dark",
        };
    }

    public static function allowedNextStatuses(string $current): array
    {
        return match ($current) {
            "pending" => ["confirmed", "cancelled"],
            "confirmed" => ["shipping", "cancelled"],
            "shipping" => ["completed", "cancelled"],
            "completed" => [],
            "cancelled" => [],
            default => array_keys(self::STATUSES),
        };
    }

    private function syncLatestPayment($orderId, $paymentStatus)
    {
        $orderId = (int) $orderId;
        $find = $this->conn->prepare(
            "SELECT id FROM payments WHERE order_id = ? ORDER BY id DESC LIMIT 1"
        );

        if (!$find) {
            return;
        }

        $find->bind_param("i", $orderId);
        $find->execute();
        $row = $find->get_result()->fetch_assoc();
        $find->close();

        if (!$row) {
            return;
        }

        $paymentId = (int) $row["id"];
        $paidAt = $paymentStatus === "paid" ? date("Y-m-d H:i:s") : null;

        $update = $this->conn->prepare(
            "UPDATE payments SET status = ?, paid_at = ? WHERE id = ?"
        );

        if (!$update) {
            return;
        }

        $update->bind_param("ssi", $paymentStatus, $paidAt, $paymentId);
        $update->execute();
        $update->close();
    }

    private function buildFilters($search, $status, $paymentStatus, $dateFrom = "", $dateTo = ""): array
    {
        $where = [];
        $types = "";
        $params = [];

        $search = trim((string) $search);
        $status = trim((string) $status);
        $paymentStatus = trim((string) $paymentStatus);
        $dateFrom = trim((string) $dateFrom);
        $dateTo = trim((string) $dateTo);

        if ($search !== "") {
            if (ctype_digit($search)) {
                $where[] = "(o.id = ? OR o.customer_name LIKE ? OR o.phone LIKE ? OR o.email LIKE ?)";
                $types .= "isss";
                $like = "%" . $search . "%";
                $params[] = (int) $search;
                $params[] = $like;
                $params[] = $like;
                $params[] = $like;
            } else {
                $where[] = "(o.customer_name LIKE ? OR o.phone LIKE ? OR o.email LIKE ?)";
                $types .= "sss";
                $like = "%" . $search . "%";
                $params[] = $like;
                $params[] = $like;
                $params[] = $like;
            }
        }

        if ($status !== "" && isset(self::STATUSES[$status])) {
            $where[] = "o.status = ?";
            $types .= "s";
            $params[] = $status;
        }

        if ($paymentStatus !== "" && isset(self::PAYMENT_STATUSES[$paymentStatus])) {
            $where[] = "o.payment_status = ?";
            $types .= "s";
            $params[] = $paymentStatus;
        }

        if ($dateFrom !== "") {
            $where[] = "o.created_at >= ?";
            $types .= "s";
            $params[] = $dateFrom . " 00:00:00";
        }

        if ($dateTo !== "") {
            $where[] = "o.created_at < DATE_ADD(?, INTERVAL 1 DAY)";
            $types .= "s";
            $params[] = $dateTo;
        }

        $whereSql = $where ? ("WHERE " . implode(" AND ", $where)) : "";

        return [$whereSql, $types, $params];
    }
}
