<?php

$success = !empty($success);
$order = $order ?? null;
$items = $items ?? [];
$message = $message ?? "";
$orderId = (int) ($order["id"] ?? 0);
$pageTitle = $success ? "Đặt hàng thành công | Laptop Store" : "Kết quả thanh toán | Laptop Store";
$paymentMethod = ($order["payment_method"] ?? "") === "vnpay"
    ? "VNPay"
    : "Thanh toán khi nhận hàng";
$paymentStatus = $order["payment_status"] ?? "";
$paymentLabels = [
    "pending" => "Chờ thanh toán",
    "paid" => "Đã thanh toán",
    "failed" => "Thanh toán thất bại",
];

require __DIR__ . "/../layouts/header.php";

?>

<main class="checkout-page checkout-result-page">
    <div class="checkout-container checkout-result-container">
        <section class="checkout-result-card <?= $success ? "is-success" : "is-failed" ?>">
            <div class="checkout-result-icon" aria-hidden="true">
                <?= $success ? "✓" : "!" ?>
            </div>
            <span class="checkout-kicker">
                <?= $success ? "CẢM ƠN BẠN ĐÃ LỰA CHỌN LAPTOP STORE" : "TRẠNG THÁI GIAO DỊCH" ?>
            </span>
            <h1>
                <?= $success ? "Đặt hàng thành công!" : "Thanh toán chưa hoàn tất" ?>
            </h1>
            <?php if ($message !== ""): ?>
                <p class="checkout-result-message">
                    <?= htmlspecialchars($message, ENT_QUOTES, "UTF-8") ?>
                </p>
            <?php endif; ?>

            <?php if ($order): ?>
                <div class="checkout-result-details">
                    <div class="checkout-result-order-id">
                        <span>MÃ ĐƠN HÀNG</span>
                        <strong>#<?= $orderId ?></strong>
                    </div>
                    <div class="checkout-result-detail-row">
                        <span>Phương thức thanh toán</span>
                        <strong><?= htmlspecialchars($paymentMethod, ENT_QUOTES, "UTF-8") ?></strong>
                    </div>
                    <div class="checkout-result-detail-row">
                        <span>Trạng thái thanh toán</span>
                        <strong class="checkout-payment-status status-<?= htmlspecialchars($paymentStatus, ENT_QUOTES, "UTF-8") ?>">
                            <?= htmlspecialchars($paymentLabels[$paymentStatus] ?? $paymentStatus, ENT_QUOTES, "UTF-8") ?>
                        </strong>
                    </div>
                    <div class="checkout-result-detail-row checkout-result-total">
                        <span>Tổng đơn hàng</span>
                        <strong><?= number_format((float) ($order["total_amount"] ?? 0), 0, ",", ".") ?> đ</strong>
                    </div>
                    <div class="checkout-result-address">
                        <span>ĐỊA CHỈ GIAO HÀNG</span>
                        <strong><?= htmlspecialchars($order["customer_name"] ?? "", ENT_QUOTES, "UTF-8") ?></strong>
                        <p>
                            <?= htmlspecialchars($order["phone"] ?? "", ENT_QUOTES, "UTF-8") ?>
                            <span aria-hidden="true">·</span>
                            <?= htmlspecialchars($order["address"] ?? "", ENT_QUOTES, "UTF-8") ?>
                        </p>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($items)): ?>
                <div class="checkout-result-items">
                    <h2>Sản phẩm trong đơn</h2>
                    <?php foreach ($items as $item): ?>
                        <div class="checkout-result-item">
                            <span>
                                <?= htmlspecialchars($item["product_name"] ?? "", ENT_QUOTES, "UTF-8") ?>
                                <small>× <?= (int) ($item["quantity"] ?? 0) ?></small>
                            </span>
                            <strong><?= number_format((float) ($item["subtotal"] ?? 0), 0, ",", ".") ?> đ</strong>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="checkout-result-actions">
                <?php if ($order): ?>
                    <a
                        class="checkout-submit"
                        href="<?= BASE_URL ?>index.php?controller=order&action=detail&id=<?= $orderId ?>"
                    >
                        Xem chi tiết đơn hàng <span aria-hidden="true">→</span>
                    </a>
                <?php else: ?>
                    <a class="checkout-submit" href="<?= BASE_URL ?>index.php?controller=cart&action=index">
                        Quay lại giỏ hàng <span aria-hidden="true">→</span>
                    </a>
                <?php endif; ?>
                <a class="checkout-secondary-link" href="<?= BASE_URL ?>index.php?controller=product&action=index">
                    Tiếp tục mua sắm
                </a>
                <?php if (
                    $order
                    && !empty($_SESSION["user"]["role"])
                    && $_SESSION["user"]["role"] === "admin"
                ): ?>
                    <a
                        class="checkout-admin-link"
                        href="<?= BASE_URL ?>index.php?controller=adminOrder&action=show&id=<?= $orderId ?>"
                    >
                        Xem đơn trong quản trị
                    </a>
                <?php endif; ?>
            </div>
        </section>
    </div>
</main>

<?php require __DIR__ . "/../layouts/footer.php"; ?>
