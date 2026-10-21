<?php

class Order
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function getById($id)
    {
        $id = (int) $id;
        $stmt = $this->conn->prepare("SELECT * FROM orders WHERE id = ? LIMIT 1");
        if (!$stmt) {
            return null;
        }

        $stmt->bind_param("i", $id);
        $stmt->execute();
        $order = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $order ?: null;
    }

    public function getTotalCountByUserId($userId)
    {
        $userId = (int) $userId;
        $stmt = $this->conn->prepare(
            "SELECT COUNT(*) AS total
             FROM orders
             WHERE user_id = ?"
        );
        if (!$stmt) {
            return 0;
        }

        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return (int) ($row["total"] ?? 0);
    }

    public function getByUserId($userId, $limit = 10, $offset = 0)
    {
        $userId = (int) $userId;
        $limit = max(1, min(10, (int) $limit));
        $offset = max(0, (int) $offset);
        $stmt = $this->conn->prepare(
            "SELECT id, total_amount, payment_method, payment_status,
                    status, created_at
             FROM orders
             WHERE user_id = ?
             ORDER BY created_at DESC, id DESC
             LIMIT ? OFFSET ?"
        );
        if (!$stmt) {
            return [];
        }

        $stmt->bind_param("iii", $userId, $limit, $offset);
        $stmt->execute();
        $orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $orders;
    }

    public function getByIdForUser($id, $userId)
    {
        $id = (int) $id;
        $userId = (int) $userId;
        $stmt = $this->conn->prepare(
            "SELECT *
             FROM orders
             WHERE id = ? AND user_id = ?
             LIMIT 1"
        );
        if (!$stmt) {
            return null;
        }

        $stmt->bind_param("ii", $id, $userId);
        $stmt->execute();
        $order = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $order ?: null;
    }

    public function getItems($orderId)
    {
        $orderId = (int) $orderId;
        $stmt = $this->conn->prepare(
            "SELECT order_items.*, products.image AS product_image
             FROM order_items
             LEFT JOIN products ON products.id = order_items.product_id
             WHERE order_items.order_id = ?
             ORDER BY order_items.id ASC"
        );
        if (!$stmt) {
            return [];
        }

        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $items;
    }

    public function cancelPendingOrderForUser(int $orderId, int $userId): bool
    {
        $this->conn->begin_transaction();

        try {
            $stmt = $this->conn->prepare(
                "UPDATE orders
                 SET status = 'cancelled'
                 WHERE id = ? AND user_id = ? AND status = 'pending'
                   AND payment_status != 'paid'"
            );
            if (!$stmt) {
                throw new RuntimeException("Không thể chuẩn bị hủy đơn hàng.");
            }
            $stmt->bind_param("ii", $orderId, $userId);
            if (!$stmt->execute()) {
                $stmt->close();
                throw new RuntimeException("Không thể hủy đơn hàng.");
            }
            $cancelled = $stmt->affected_rows === 1;
            $stmt->close();

            if (!$cancelled) {
                $this->conn->rollback();
                return false;
            }

            $itemsStmt = $this->conn->prepare(
                "SELECT product_id, quantity
                 FROM order_items
                 WHERE order_id = ?"
            );
            if (!$itemsStmt) {
                throw new RuntimeException("Không thể tải sản phẩm trong đơn hàng.");
            }
            $itemsStmt->bind_param("i", $orderId);
            if (!$itemsStmt->execute()) {
                $itemsStmt->close();
                throw new RuntimeException("Không thể tải sản phẩm trong đơn hàng.");
            }
            $items = $itemsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $itemsStmt->close();

            if (!$items) {
                throw new RuntimeException("Đơn hàng không có sản phẩm để hoàn kho.");
            }

            $stockStmt = $this->conn->prepare(
                "UPDATE products
                 SET quantity = quantity + ?,
                     sold_quantity = GREATEST(sold_quantity - ?, 0)
                 WHERE id = ?"
            );
            if (!$stockStmt) {
                throw new RuntimeException("Không thể chuẩn bị hoàn tồn kho.");
            }

            foreach ($items as $item) {
                $quantity = (int) $item["quantity"];
                $productId = (int) $item["product_id"];
                $stockStmt->bind_param("iii", $quantity, $quantity, $productId);
                if (!$stockStmt->execute()) {
                    $stockStmt->close();
                    throw new RuntimeException("Không thể hoàn tồn kho sản phẩm.");
                }
            }
            $stockStmt->close();

            $this->conn->commit();
            return true;
        } catch (Throwable $e) {
            $this->conn->rollback();
            throw $e;
        }
    }

    public function findPaymentByTxn($txnRef)
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM payments WHERE transaction_code = ? LIMIT 1"
        );
        if (!$stmt) {
            return null;
        }

        $stmt->bind_param("s", $txnRef);
        $stmt->execute();
        $payment = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $payment ?: null;
    }

    public function createVnPayIntent(array $orderData, array $cartItems): array
    {
        try {
            $total = 0;
            $items = [];

            foreach ($cartItems as $item) {
                $productId = (int) $item["product_id"];
                $quantity = (int) $item["quantity"];
                $stmt = $this->conn->prepare(
                    "SELECT id, name, price, sale_price, quantity
                     FROM products
                     WHERE id = ? AND is_active = 1"
                );
                if (!$stmt) {
                    throw new RuntimeException("Không thể kiểm tra sản phẩm.");
                }

                $stmt->bind_param("i", $productId);
                if (!$stmt->execute()) {
                    $stmt->close();
                    throw new RuntimeException("Không thể kiểm tra sản phẩm.");
                }
                $product = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if (!$product || $quantity < 1 || $quantity > (int) $product["quantity"]) {
                    throw new RuntimeException(
                        "Sản phẩm \"" . ($product["name"] ?? "") . "\" không đủ tồn kho."
                    );
                }

                $price = (float) (
                    !empty($product["sale_price"])
                        ? $product["sale_price"]
                        : $product["price"]
                );
                $total += $price * $quantity;
                $items[] = [
                    "product_id" => $productId,
                    "quantity" => $quantity,
                    "price" => $price,
                ];
            }

            if ($total <= 0) {
                throw new RuntimeException("Giỏ hàng không hợp lệ.");
            }

            $txn = "VNPAY-" . date("YmdHis") . "-" . bin2hex(random_bytes(8));
            $orderJson = json_encode($orderData, JSON_THROW_ON_ERROR);
            $itemsJson = json_encode($items, JSON_THROW_ON_ERROR);
            $expiresAt = date("Y-m-d H:i:s", time() + 900);
            $stmt = $this->conn->prepare(
                "INSERT INTO vnpay_payment_intents
                    (transaction_code, user_id, order_data, cart_items, amount, status, expires_at)
                 VALUES (?, ?, ?, ?, ?, 'pending', ?)"
            );
            if (!$stmt) {
                throw new RuntimeException("Không thể khởi tạo giao dịch VNPay.");
            }
            $userId = (int) $orderData["user_id"];
            $stmt->bind_param("sissds", $txn, $userId, $orderJson, $itemsJson, $total, $expiresAt);
            if (!$stmt->execute()) {
                $stmt->close();
                throw new RuntimeException("Không thể lưu thông tin thanh toán VNPay.");
            }
            $stmt->close();

            return ["ok" => true, "total" => $total, "transaction_code" => $txn];
        } catch (Throwable $e) {
            return ["ok" => false, "error" => $e->getMessage()];
        }
    }

    public function findVnPayIntent(string $txnRef): ?array
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM vnpay_payment_intents WHERE transaction_code = ? LIMIT 1"
        );
        if (!$stmt) {
            throw new RuntimeException("Không thể tra cứu giao dịch VNPay.");
        }
        $stmt->bind_param("s", $txnRef);
        if (!$stmt->execute()) {
            $stmt->close();
            throw new RuntimeException("Không thể tra cứu giao dịch VNPay.");
        }
        $intent = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $intent ?: null;
    }

    public function completeVnPayIntent(string $txnRef, int $amount): array
    {
        $this->conn->begin_transaction();
        try {
            $stmt = $this->conn->prepare(
                "SELECT * FROM vnpay_payment_intents
                 WHERE transaction_code = ? FOR UPDATE"
            );
            if (!$stmt) {
                throw new RuntimeException("Không thể xác nhận giao dịch VNPay.");
            }
            $stmt->bind_param("s", $txnRef);
            if (!$stmt->execute()) {
                $stmt->close();
                throw new RuntimeException("Không thể xác nhận giao dịch VNPay.");
            }
            $intent = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$intent) {
                throw new RuntimeException("Không tìm thấy giao dịch VNPay.");
            }
            if ((int) round((float) $intent["amount"] * 100) !== $amount) {
                throw new RuntimeException("Số tiền thanh toán không khớp.");
            }
            if ($intent["status"] === "paid") {
                $this->conn->commit();
                return [
                    "ok" => true,
                    "order_id" => (int) $intent["order_id"],
                    "already_paid" => true,
                ];
            }
            if ($intent["status"] !== "pending") {
                throw new RuntimeException("Giao dịch VNPay không còn ở trạng thái chờ.");
            }

            $orderData = json_decode($intent["order_data"], true, 512, JSON_THROW_ON_ERROR);
            $items = json_decode($intent["cart_items"], true, 512, JSON_THROW_ON_ERROR);
            $result = $this->insertOrderFromCart(
                $orderData,
                $items,
                "vnpay",
                $txnRef,
                true,
                false
            );
            $this->removePurchasedCartItems((int) $orderData["user_id"], $items);

            $update = $this->conn->prepare(
                "UPDATE vnpay_payment_intents
                 SET status = 'paid', order_id = ? WHERE transaction_code = ?"
            );
            if (!$update) {
                throw new RuntimeException("Không thể cập nhật trạng thái giao dịch VNPay.");
            }
            $update->bind_param("is", $result["order_id"], $txnRef);
            if (!$update->execute()) {
                $update->close();
                throw new RuntimeException("Không thể cập nhật trạng thái giao dịch VNPay.");
            }
            $update->close();

            $this->conn->commit();
            return $result;
        } catch (Throwable $e) {
            $this->conn->rollback();
            return ["ok" => false, "error" => $e->getMessage()];
        }
    }

    public function failVnPayIntent(string $txnRef): void
    {
        $stmt = $this->conn->prepare(
            "UPDATE vnpay_payment_intents SET status = 'failed'
             WHERE transaction_code = ? AND status = 'pending'"
        );
        if (!$stmt) {
            throw new RuntimeException("Không thể cập nhật giao dịch VNPay thất bại.");
        }
        $stmt->bind_param("s", $txnRef);
        if (!$stmt->execute()) {
            $stmt->close();
            throw new RuntimeException("Không thể cập nhật giao dịch VNPay thất bại.");
        }
        $stmt->close();
    }

    public function createFromCart(array $orderData, array $cartItems, string $paymentMethod): array
    {
        $this->conn->begin_transaction();
        try {
            $result = $this->insertOrderFromCart(
                $orderData,
                $cartItems,
                $paymentMethod,
                null,
                false,
                false
            );
            $this->removePurchasedCartItems((int) $orderData["user_id"], $cartItems);
            $this->conn->commit();
            return $result;
        } catch (Throwable $e) {
            $this->conn->rollback();
            return ["ok" => false, "error" => $e->getMessage()];
        }
    }

    private function insertOrderFromCart(
        array $orderData,
        array $cartItems,
        string $paymentMethod,
        ?string $transactionCode = null,
        bool $paid = false,
        bool $clearCart = true
    ): array {
        $total = 0;
        $preparedItems = [];

        foreach ($cartItems as $item) {
            $productId = (int) $item["product_id"];
            $quantity = (int) $item["quantity"];
            $stmt = $this->conn->prepare(
                "SELECT id, name, price, sale_price, quantity
                 FROM products
                 WHERE id = ? AND is_active = 1
                 FOR UPDATE"
            );
            if (!$stmt) {
                throw new RuntimeException("Không thể kiểm tra sản phẩm.");
            }
            $stmt->bind_param("i", $productId);
            if (!$stmt->execute()) {
                $stmt->close();
                throw new RuntimeException("Không thể kiểm tra sản phẩm.");
            }
            $product = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$product || $quantity < 1 || $quantity > (int) ($product["quantity"] ?? 0)) {
                throw new RuntimeException(
                    "Sản phẩm \"" . ($product["name"] ?? "") . "\" không đủ tồn kho."
                );
            }
            $price = isset($item["price"])
                ? (float) $item["price"]
                : (float) (!empty($product["sale_price"])
                    ? $product["sale_price"]
                    : $product["price"]);
            $subtotal = $price * $quantity;
            $total += $subtotal;
            $preparedItems[] = [
                "product_id" => $productId,
                "product_name" => $product["name"],
                "price" => $price,
                "quantity" => $quantity,
                "subtotal" => $subtotal,
            ];
        }

        if ($total <= 0) {
            throw new RuntimeException("Giỏ hàng không hợp lệ.");
        }

        $paymentStatus = $paid ? "paid" : "pending";
        $stmt = $this->conn->prepare(
            "INSERT INTO orders
                (user_id, customer_name, phone, email, address, note,
                 total_amount, payment_method, payment_status, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')"
        );
        if (!$stmt) {
            throw new RuntimeException("Không thể tạo đơn hàng.");
        }
        $stmt->bind_param(
            "isssssdss",
            $orderData["user_id"],
            $orderData["customer_name"],
            $orderData["phone"],
            $orderData["email"],
            $orderData["address"],
            $orderData["note"],
            $total,
            $paymentMethod,
            $paymentStatus
        );
        if (!$stmt->execute()) {
            $stmt->close();
            throw new RuntimeException("Không thể tạo đơn hàng.");
        }
        $orderId = (int) $this->conn->insert_id;
        $stmt->close();

        $itemStmt = $this->conn->prepare(
            "INSERT INTO order_items
                (order_id, product_id, product_name, price, quantity, subtotal)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stockStmt = $this->conn->prepare(
            "UPDATE products
             SET quantity = quantity - ?, sold_quantity = sold_quantity + ?
             WHERE id = ? AND quantity >= ?"
        );
        if (!$itemStmt || !$stockStmt) {
            throw new RuntimeException("Không thể lưu sản phẩm trong đơn hàng.");
        }

        foreach ($preparedItems as $row) {
            $itemStmt->bind_param(
                "iisdid",
                $orderId,
                $row["product_id"],
                $row["product_name"],
                $row["price"],
                $row["quantity"],
                $row["subtotal"]
            );
            if (!$itemStmt->execute()) {
                throw new RuntimeException("Không thể lưu sản phẩm trong đơn.");
            }
            $stockStmt->bind_param(
                "iiii",
                $row["quantity"],
                $row["quantity"],
                $row["product_id"],
                $row["quantity"]
            );
            if (!$stockStmt->execute() || $stockStmt->affected_rows !== 1) {
                throw new RuntimeException("Không thể trừ tồn kho.");
            }
        }
        $itemStmt->close();
        $stockStmt->close();

        $txn = $transactionCode
            ?? strtoupper($paymentMethod) . "-" . $orderId . "-" . date("YmdHis");
        $payStatus = $paid ? "paid" : "pending";
        $paidAt = $paid ? date("Y-m-d H:i:s") : null;
        $payStmt = $this->conn->prepare(
            "INSERT INTO payments
                (order_id, transaction_code, amount, payment_method, status, paid_at)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        if (!$payStmt) {
            throw new RuntimeException("Không thể lưu thông tin thanh toán.");
        }
        $payStmt->bind_param("isdsss", $orderId, $txn, $total, $paymentMethod, $payStatus, $paidAt);
        if (!$payStmt->execute()) {
            $payStmt->close();
            throw new RuntimeException("Không thể lưu thông tin thanh toán.");
        }
        $payStmt->close();

        if ($clearCart) {
            $clearCartStmt = $this->conn->prepare(
                "DELETE cart_items
                 FROM cart_items
                 INNER JOIN cart ON cart.id = cart_items.cart_id
                 WHERE cart.user_id = ?"
            );
            if (!$clearCartStmt) {
                throw new RuntimeException("Không thể làm trống giỏ hàng sau khi đặt.");
            }
            $userId = (int) $orderData["user_id"];
            $clearCartStmt->bind_param("i", $userId);
            if (!$clearCartStmt->execute()) {
                $clearCartStmt->close();
                throw new RuntimeException("Không thể làm trống giỏ hàng sau khi đặt.");
            }
            $clearCartStmt->close();
        }

        return [
            "ok" => true,
            "order_id" => $orderId,
            "total" => $total,
            "transaction_code" => $txn,
        ];
    }

    private function removePurchasedCartItems(int $userId, array $items): void
    {
        $delete = $this->conn->prepare(
            "DELETE ci
             FROM cart_items ci
             INNER JOIN cart c ON c.id = ci.cart_id
             WHERE c.user_id = ? AND ci.product_id = ? AND ci.quantity <= ?"
        );
        $update = $this->conn->prepare(
            "UPDATE cart_items ci
             INNER JOIN cart c ON c.id = ci.cart_id
             SET ci.quantity = ci.quantity - ?
             WHERE c.user_id = ? AND ci.product_id = ? AND ci.quantity > ?"
        );
        if (!$delete || !$update) {
            throw new RuntimeException("Không thể đồng bộ giỏ hàng sau thanh toán.");
        }

        foreach ($items as $item) {
            $productId = (int) $item["product_id"];
            $quantity = (int) $item["quantity"];
            $delete->bind_param("iii", $userId, $productId, $quantity);
            if (!$delete->execute()) {
                throw new RuntimeException("Không thể đồng bộ giỏ hàng sau thanh toán.");
            }
            $update->bind_param("iiii", $quantity, $userId, $productId, $quantity);
            if (!$update->execute()) {
                throw new RuntimeException("Không thể đồng bộ giỏ hàng sau thanh toán.");
            }
        }
        $delete->close();
        $update->close();
    }

    public function markPaid($orderId, $txnRef)
    {
        $orderId = (int) $orderId;
        $paidAt = date("Y-m-d H:i:s");

        $orderStmt = $this->conn->prepare(
            "UPDATE orders SET payment_status = 'paid' WHERE id = ? AND payment_status != 'paid'"
        );
        $orderStmt->bind_param("i", $orderId);
        $orderStmt->execute();
        $orderStmt->close();

        $payStmt = $this->conn->prepare(
            "UPDATE payments
             SET status = 'paid', paid_at = ?, transaction_code = ?
             WHERE order_id = ? AND payment_method = 'vnpay'"
        );
        $payStmt->bind_param("ssi", $paidAt, $txnRef, $orderId);
        $payStmt->execute();
        $payStmt->close();
    }

    public function markPaymentFailed($orderId, $txnRef)
    {
        $orderId = (int) $orderId;
        $order = $this->getById($orderId);

        if (!$order || ($order["payment_status"] ?? "") === "paid") {
            return;
        }

        $this->conn->begin_transaction();

        try {
            $ord = $this->conn->prepare(
                "UPDATE orders
                 SET payment_status = 'failed', status = 'cancelled'
                 WHERE id = ? AND payment_status = 'pending'"
            );
            $ord->bind_param("i", $orderId);
            $ord->execute();
            $changed = $ord->affected_rows;
            $ord->close();

            $pay = $this->conn->prepare(
                "UPDATE payments
                 SET status = 'failed', transaction_code = ?
                 WHERE order_id = ? AND payment_method = 'vnpay'"
            );
            $pay->bind_param("si", $txnRef, $orderId);
            $pay->execute();
            $pay->close();

            if ($changed === 1) {
                $this->restoreStock($orderId);
            }

            $this->conn->commit();
        } catch (Throwable $e) {
            $this->conn->rollback();
        }
    }

    private function restoreStock($orderId)
    {
        $items = $this->getItems($orderId);

        $stmt = $this->conn->prepare(
            "UPDATE products
             SET quantity = quantity + ?, sold_quantity = GREATEST(sold_quantity - ?, 0)
             WHERE id = ?"
        );

        foreach ($items as $item) {
            $qty = (int) $item["quantity"];
            $productId = (int) $item["product_id"];
            $stmt->bind_param("iii", $qty, $qty, $productId);
            $stmt->execute();
        }

        $stmt->close();
    }
}
