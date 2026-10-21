<?php

require_once __DIR__ . "/Product.php";

class Cart
{
    private $conn;
    private $productModel;
    private $userId;
    private $cartId;

    public function __construct($conn, bool $mergeSessionCart = true)
    {
        $this->conn = $conn;
        $this->productModel = new Product($conn);
        $userId = filter_var(
            $_SESSION["user"]["id"] ?? null,
            FILTER_VALIDATE_INT,
            ["options" => ["min_range" => 1]]
        );
        $this->userId = $userId === false ? 0 : (int) $userId;
        $this->cartId = 0;

        if (!isset($_SESSION["cart"]) || !is_array($_SESSION["cart"])) {
            $_SESSION["cart"] = [];
        }

        if ($this->userId > 0) {
            $this->cartId = $this->getOrCreateCartId();
            if ((int) ($_SESSION["cart_owner_id"] ?? 0) !== $this->userId && $mergeSessionCart) {
                $warnings = $this->mergeGuestCart($_SESSION["cart"]);
                if ($warnings) {
                    $_SESSION["cart_success"] =
                        "Đã gộp giỏ hàng. " . implode(" ", $warnings);
                }
            } else {
                $this->syncSessionCache($this->getStoredQuantities());
            }
        }
    }

    public function add(int $productId, int $quantity): string
    {
        if ($quantity < 1) {
            return "Số lượng sản phẩm không hợp lệ.";
        }

        $product = $this->productModel->getById($productId);
        if (!$product) {
            return "Không tìm thấy sản phẩm.";
        }

        if ($this->userId > 0) {
            return $this->changeStoredQuantity($productId, $quantity, true);
        }

        $currentQuantity = (int) ($_SESSION["cart"][$productId] ?? 0);
        if ($currentQuantity + $quantity > (int) $product["quantity"]) {
            return "Số lượng vượt quá tồn kho hiện có.";
        }

        $_SESSION["cart"][$productId] = $currentQuantity + $quantity;
        return "";
    }

    public function getItems(): array
    {
        $items = [];
        $quantities = $this->userId > 0
            ? $this->getStoredQuantities()
            : $_SESSION["cart"];

        if ($this->userId > 0) {
            $this->syncSessionCache($quantities);
        }

        foreach ($quantities as $productId => $quantity) {
            $productId = filter_var(
                $productId,
                FILTER_VALIDATE_INT,
                ["options" => ["min_range" => 1]]
            );
            $quantity = filter_var(
                $quantity,
                FILTER_VALIDATE_INT,
                ["options" => ["min_range" => 1]]
            );

            if (!$productId || !$quantity) {
                continue;
            }

            $product = $this->productModel->getById($productId);
            $price = $product
                ? (float) (!empty($product["sale_price"])
                    ? $product["sale_price"]
                    : $product["price"])
                : 0;

            $items[] = [
                "product" => $product,
                "product_id" => $productId,
                "quantity" => $quantity,
                "price" => $price,
            ];
        }

        return $items;
    }

    public function update(int $productId, int $quantity): string
    {
        $quantities = $this->userId > 0
            ? $this->getStoredQuantities()
            : $_SESSION["cart"];

        if ($quantity < 1 || !isset($quantities[$productId])) {
            return "Sản phẩm hoặc số lượng không hợp lệ.";
        }

        $product = $this->productModel->getById($productId);
        if (!$product || $quantity > (int) $product["quantity"]) {
            return "Số lượng vượt quá tồn kho hoặc sản phẩm không còn khả dụng.";
        }

        if ($this->userId > 0) {
            return $this->changeStoredQuantity($productId, $quantity, false);
        }

        $_SESSION["cart"][$productId] = $quantity;
        return "";
    }

    public function remove(int $productId): bool
    {
        $quantities = $this->userId > 0
            ? $this->getStoredQuantities()
            : $_SESSION["cart"];

        if (!isset($quantities[$productId])) {
            return false;
        }

        if ($this->userId > 0) {
            $stmt = $this->conn->prepare(
                "DELETE FROM cart_items WHERE cart_id = ? AND product_id = ?"
            );

            if (!$stmt) {
                throw new RuntimeException("Không thể chuẩn bị thao tác xóa sản phẩm khỏi giỏ.");
            }

            $stmt->bind_param("ii", $this->cartId, $productId);
            if (!$stmt->execute()) {
                $stmt->close();
                throw new RuntimeException("Không thể xóa sản phẩm khỏi giỏ hàng.");
            }
            $stmt->close();
        }

        unset($_SESSION["cart"][$productId]);
        return true;
    }

    public function clear(): void
    {
        if ($this->userId > 0) {
            $stmt = $this->conn->prepare(
                "DELETE FROM cart_items WHERE cart_id = ?"
            );

            if (!$stmt) {
                throw new RuntimeException("Không thể chuẩn bị thao tác làm trống giỏ hàng.");
            }

            $stmt->bind_param("i", $this->cartId);
            if (!$stmt->execute()) {
                $stmt->close();
                throw new RuntimeException("Không thể làm trống giỏ hàng.");
            }
            $stmt->close();
        }

        $_SESSION["cart"] = [];
    }

    public function clearSessionCache(): void
    {
        $_SESSION["cart"] = [];
    }

    public function refreshSessionCache(): void
    {
        if ($this->userId > 0) {
            $this->syncSessionCache($this->getStoredQuantities());
        }
    }

    public function getCount(): int
    {
        if ($this->userId > 0) {
            $quantities = $this->getStoredQuantities();
            $this->syncSessionCache($quantities);
            return array_sum($quantities);
        }

        return array_sum(array_map("intval", $_SESSION["cart"]));
    }

    public function getTotal(): float
    {
        $total = 0;
        foreach ($this->getItems() as $item) {
            if ($item["product"]) {
                $total += $item["price"] * $item["quantity"];
            }
        }

        return $total;
    }

    public function mergeGuestCart(array $guestCart): array
    {
        if ($this->userId < 1) {
            throw new LogicException("Cần đăng nhập trước khi gộp giỏ hàng.");
        }

        $warnings = [];
        $this->conn->begin_transaction();

        try {
            foreach ($guestCart as $productId => $guestQuantity) {
                $productId = filter_var(
                    $productId,
                    FILTER_VALIDATE_INT,
                    ["options" => ["min_range" => 1]]
                );
                $guestQuantity = filter_var(
                    $guestQuantity,
                    FILTER_VALIDATE_INT,
                    ["options" => ["min_range" => 1]]
                );

                if (!$productId || !$guestQuantity) {
                    continue;
                }

                $stock = $this->getLockedProductStock($productId);
                if ($stock === null || $stock < 1) {
                    $warnings[] = "Một sản phẩm trong giỏ khách không còn hàng nên đã được bỏ qua.";
                    continue;
                }

                $existingQuantity = $this->getStoredQuantityForUpdate($productId);
                $requestedQuantity = $existingQuantity + $guestQuantity;
                $mergedQuantity = min($requestedQuantity, $stock);

                $this->storeQuantity($productId, $mergedQuantity);

                if ($mergedQuantity < $requestedQuantity) {
                    $warnings[] = "Một số sản phẩm đã được điều chỉnh số lượng theo tồn kho hiện có.";
                }
            }

            $this->conn->commit();
        } catch (Throwable $error) {
            $this->conn->rollback();
            throw $error;
        }

        $_SESSION["cart"] = [];
        $this->syncSessionCache($this->getStoredQuantities());
        $_SESSION["cart_owner_id"] = $this->userId;

        return array_values(array_unique($warnings));
    }

    private function getOrCreateCartId(): int
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO cart (user_id) VALUES (?)
             ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)"
        );

        if (!$stmt) {
            throw new RuntimeException("Không thể chuẩn bị giỏ hàng cho tài khoản.");
        }

        $stmt->bind_param("i", $this->userId);
        if (!$stmt->execute()) {
            $stmt->close();
            throw new RuntimeException("Không thể tải giỏ hàng của tài khoản.");
        }

        $cartId = (int) $this->conn->insert_id;
        $stmt->close();

        if ($cartId < 1) {
            throw new RuntimeException("Không tìm thấy giỏ hàng của tài khoản.");
        }

        return $cartId;
    }

    private function getStoredQuantities(): array
    {
        $stmt = $this->conn->prepare(
            "SELECT product_id, quantity FROM cart_items WHERE cart_id = ?"
        );

        if (!$stmt) {
            throw new RuntimeException("Không thể chuẩn bị truy vấn giỏ hàng.");
        }

        $stmt->bind_param("i", $this->cartId);
        if (!$stmt->execute()) {
            $stmt->close();
            throw new RuntimeException("Không thể tải sản phẩm trong giỏ hàng.");
        }

        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $quantities = [];
        foreach ($rows as $row) {
            $quantities[(int) $row["product_id"]] = (int) $row["quantity"];
        }

        return $quantities;
    }

    private function syncSessionCache(array $quantities): void
    {
        $_SESSION["cart"] = $quantities;
    }

    private function changeStoredQuantity(int $productId, int $quantity, bool $add): string
    {
        $this->conn->begin_transaction();

        try {
            $stock = $this->getLockedProductStock($productId);
            if ($stock === null || $quantity > $stock) {
                $this->conn->rollback();
                return "Số lượng vượt quá tồn kho hoặc sản phẩm không còn khả dụng.";
            }

            $existingQuantity = $this->getStoredQuantityForUpdate($productId);
            $newQuantity = $add ? $existingQuantity + $quantity : $quantity;

            if ($newQuantity > $stock) {
                $this->conn->rollback();
                return "Số lượng vượt quá tồn kho hiện có.";
            }

            $this->storeQuantity($productId, $newQuantity);
            $this->conn->commit();
            $_SESSION["cart"][$productId] = $newQuantity;

            return "";
        } catch (Throwable $error) {
            $this->conn->rollback();
            throw $error;
        }
    }

    private function getLockedProductStock(int $productId): ?int
    {
        $stmt = $this->conn->prepare(
            "SELECT quantity FROM products WHERE id = ? FOR UPDATE"
        );

        if (!$stmt) {
            throw new RuntimeException("Không thể chuẩn bị kiểm tra tồn kho.");
        }

        $stmt->bind_param("i", $productId);
        if (!$stmt->execute()) {
            $stmt->close();
            throw new RuntimeException("Không thể kiểm tra tồn kho.");
        }

        $product = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $product ? (int) $product["quantity"] : null;
    }

    private function getStoredQuantityForUpdate(int $productId): int
    {
        $stmt = $this->conn->prepare(
            "SELECT quantity FROM cart_items
             WHERE cart_id = ? AND product_id = ?
             FOR UPDATE"
        );

        if (!$stmt) {
            throw new RuntimeException("Không thể chuẩn bị kiểm tra giỏ hàng.");
        }

        $stmt->bind_param("ii", $this->cartId, $productId);
        if (!$stmt->execute()) {
            $stmt->close();
            throw new RuntimeException("Không thể kiểm tra sản phẩm trong giỏ.");
        }

        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return (int) ($row["quantity"] ?? 0);
    }

    private function storeQuantity(int $productId, int $quantity): void
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO cart_items (cart_id, product_id, quantity)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE quantity = VALUES(quantity)"
        );

        if (!$stmt) {
            throw new RuntimeException("Không thể chuẩn bị lưu sản phẩm vào giỏ.");
        }

        $stmt->bind_param("iii", $this->cartId, $productId, $quantity);
        if (!$stmt->execute()) {
            $stmt->close();
            throw new RuntimeException("Không thể lưu sản phẩm vào giỏ hàng.");
        }
        $stmt->close();
    }
}
