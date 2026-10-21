<?php

require_once __DIR__ . "/../../helpers/ProductImage.php";

$pageTitle = "Đơn hàng #" . (int) $order["id"] . " | Laptop Store";
$orderId = (int) $order["id"];
$status = $order["status"] ?? "";
$paymentStatus = $order["payment_status"] ?? "";
$statusClasses = [
    "pending" => "pending",
    "confirmed" => "confirmed",
    "shipping" => "shipping",
    "completed" => "completed",
    "cancelled" => "cancelled",
];
$paymentStatusClasses = [
    "pending" => "pending",
    "paid" => "paid",
    "failed" => "failed",
    "refunded" => "refunded",
];
$statusClass = $statusClasses[$status] ?? "neutral";
$paymentStatusClass = $paymentStatusClasses[$paymentStatus] ?? "neutral";

require __DIR__ . "/../layouts/header.php";

?>

<main class="orders-page">
    <div class="orders-container">
        <header class="orders-heading orders-detail-heading">
            <div>
                <a class="orders-back-link orders-detail-back" href="<?= BASE_URL ?>index.php?controller=order&action=index">
                    <span aria-hidden="true">←</span> Danh sách đơn hàng
                </a>
                <span class="orders-kicker">THÔNG TIN ĐƠN HÀNG</span>
                <h1>Đơn hàng #<?= $orderId ?></h1>
                <p>
                    Đặt lúc
                    <?= htmlspecialchars(
                        date("d/m/Y · H:i", strtotime($order["created_at"])),
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>
                </p>
            </div>
            <div class="orders-detail-badges">
                <span class="orders-status orders-status-<?= $statusClass ?>">
                    <?= htmlspecialchars($statusLabels[$status] ?? $status, ENT_QUOTES, "UTF-8") ?>
                </span>
                <span class="orders-status orders-payment-status orders-payment-status-<?= $paymentStatusClass ?>">
                    <?= htmlspecialchars($paymentStatusLabels[$paymentStatus] ?? $paymentStatus, ENT_QUOTES, "UTF-8") ?>
                </span>
            </div>
        </header>

        <?php if (!empty($success)): ?>
            <div class="orders-notice orders-notice-success" role="status">
                <?= htmlspecialchars($success, ENT_QUOTES, "UTF-8") ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="orders-notice orders-notice-error" role="alert">
                <?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?>
            </div>
        <?php endif; ?>

        <div class="orders-detail-layout">
            <section class="orders-detail-main">
                <section class="orders-panel" aria-labelledby="orders-items-heading">
                    <div class="orders-panel-heading">
                        <div>
                            <span class="orders-kicker">ĐƠN HÀNG #<?= $orderId ?></span>
                            <h2 id="orders-items-heading">Sản phẩm đã đặt</h2>
                        </div>
                        <span class="orders-item-count"><?= number_format(count($items)) ?> mặt hàng</span>
                    </div>

                    <div class="orders-detail-items">
                        <?php foreach ($items as $item): ?>
                            <?php
                            $productId = (int) ($item["product_id"] ?? 0);
                            $productUrl = BASE_URL . "index.php?controller=product&action=detail&id=" . $productId;
                            $productImage = productImageUrl($item["product_image"] ?? "");
                            ?>
                            <article class="orders-detail-item">
                                <a
                                    class="orders-detail-item-image"
                                    href="<?= htmlspecialchars($productUrl, ENT_QUOTES, "UTF-8") ?>"
                                    aria-label="Xem chi tiết <?= htmlspecialchars($item["product_name"] ?? "", ENT_QUOTES, "UTF-8") ?>"
                                >
                                    <?php if ($productImage !== ""): ?>
                                        <img
                                            src="<?= htmlspecialchars($productImage, ENT_QUOTES, "UTF-8") ?>"
                                            alt="<?= htmlspecialchars($item["product_name"] ?? "", ENT_QUOTES, "UTF-8") ?>"
                                            loading="lazy"
                                        >
                                    <?php else: ?>
                                        <span aria-hidden="true">LS</span>
                                    <?php endif; ?>
                                </a>
                                <div class="orders-detail-item-copy">
                                    <h3>
                                        <a href="<?= htmlspecialchars($productUrl, ENT_QUOTES, "UTF-8") ?>">
                                            <?= htmlspecialchars($item["product_name"] ?? "", ENT_QUOTES, "UTF-8") ?>
                                        </a>
                                    </h3>
                                    <span>
                                        <?= number_format((float) ($item["price"] ?? 0), 0, ",", ".") ?> đ
                                        <span aria-hidden="true">·</span>
                                        Số lượng <?= (int) ($item["quantity"] ?? 0) ?>
                                    </span>
                                </div>
                                <strong><?= number_format((float) ($item["subtotal"] ?? 0), 0, ",", ".") ?> đ</strong>
                            </article>
                        <?php endforeach; ?>
                    </div>

                    <div class="orders-detail-total">
                        <span>Tổng cộng</span>
                        <strong><?= number_format((float) $order["total_amount"], 0, ",", ".") ?> đ</strong>
                    </div>
                </section>

                <?php if (!empty($order["note"])): ?>
                    <section class="orders-panel orders-note-panel" aria-labelledby="orders-note-heading">
                        <div class="orders-panel-heading">
                            <div>
                                <span class="orders-kicker">LƯU Ý</span>
                                <h2 id="orders-note-heading">Ghi chú đơn hàng</h2>
                            </div>
                        </div>
                        <p><?= nl2br(htmlspecialchars($order["note"], ENT_QUOTES, "UTF-8")) ?></p>
                    </section>
                <?php endif; ?>
            </section>

            <aside class="orders-detail-sidebar">
                <section class="orders-panel" aria-labelledby="orders-status-heading">
                    <div class="orders-panel-heading">
                        <div>
                            <span class="orders-kicker">CẬP NHẬT</span>
                            <h2 id="orders-status-heading">Trạng thái đơn</h2>
                        </div>
                    </div>
                    <div class="orders-status-summary">
                        <div class="orders-status-summary-icon orders-status-summary-icon-<?= $statusClass ?>" aria-hidden="true">
                            <?= $status === "completed" ? "✓" : ($status === "cancelled" ? "×" : "↗") ?>
                        </div>
                        <div>
                            <strong><?= htmlspecialchars($statusLabels[$status] ?? $status, ENT_QUOTES, "UTF-8") ?></strong>
                            <span>Trạng thái xử lý hiện tại</span>
                        </div>
                    </div>
                    <div class="orders-info-row">
                        <span>Phương thức thanh toán</span>
                        <strong>
                            <?= htmlspecialchars(
                                $paymentMethodLabels[$order["payment_method"]]
                                    ?? $order["payment_method"],
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>
                        </strong>
                    </div>
                    <div class="orders-info-row">
                        <span>Thanh toán</span>
                        <strong class="orders-payment-text orders-payment-text-<?= $paymentStatusClass ?>">
                            <?= htmlspecialchars($paymentStatusLabels[$paymentStatus] ?? $paymentStatus, ENT_QUOTES, "UTF-8") ?>
                        </strong>
                    </div>
                    <?php if ($status === "pending" && $paymentStatus !== "paid"): ?>
                        <form
                            class="orders-cancel-form"
                            method="POST"
                            action="<?= BASE_URL ?>index.php?controller=order&action=cancel"
                            onsubmit="return confirm('Bạn có chắc muốn hủy đơn hàng này không?');"
                        >
                            <input type="hidden" name="id" value="<?= $orderId ?>">
                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?= htmlspecialchars($_SESSION["csrf_token"] ?? "", ENT_QUOTES, "UTF-8") ?>"
                            >
                            <button class="orders-cancel-button" type="submit">Hủy đơn hàng</button>
                            <span>Chỉ có thể hủy trước khi đơn được xác nhận.</span>
                        </form>
                    <?php endif; ?>
                </section>

                <section class="orders-panel orders-delivery-panel" aria-labelledby="orders-delivery-heading">
                    <div class="orders-panel-heading">
                        <div>
                            <span class="orders-kicker">NHẬN HÀNG</span>
                            <h2 id="orders-delivery-heading">Thông tin giao hàng</h2>
                        </div>
                    </div>
                    <div class="orders-customer-name">
                        <span class="orders-customer-avatar" aria-hidden="true">⌖</span>
                        <strong><?= htmlspecialchars($order["customer_name"] ?? "", ENT_QUOTES, "UTF-8") ?></strong>
                    </div>
                    <div class="orders-delivery-line">
                        <span>Điện thoại</span>
                        <strong><?= htmlspecialchars($order["phone"] ?? "", ENT_QUOTES, "UTF-8") ?></strong>
                    </div>
                    <?php if (!empty($order["email"])): ?>
                        <div class="orders-delivery-line">
                            <span>Email</span>
                            <strong><?= htmlspecialchars($order["email"], ENT_QUOTES, "UTF-8") ?></strong>
                        </div>
                    <?php endif; ?>
                    <div class="orders-delivery-line orders-address-line">
                        <span>Địa chỉ giao hàng</span>
                        <strong><?= htmlspecialchars($order["address"] ?? "", ENT_QUOTES, "UTF-8") ?></strong>
                    </div>
                </section>
            </aside>
        </div>
    </div>
</main>

<?php require __DIR__ . "/../layouts/footer.php"; ?>
