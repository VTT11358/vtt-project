<?php

$pageTitle = "Đơn hàng của tôi | Laptop Store";
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

require __DIR__ . "/../layouts/header.php";

?>

<main class="orders-page">
    <div class="orders-container">
        <header class="orders-heading">
            <div>
                <span class="orders-kicker">HÀNH TRÌNH MUA SẮM CỦA BẠN</span>
                <h1>Đơn hàng của tôi</h1>
                <p>Theo dõi trạng thái và xem lại thông tin các đơn hàng bạn đã đặt.</p>
            </div>
            <a class="orders-back-link" href="<?= BASE_URL ?>index.php?controller=product&action=index">
                Tiếp tục mua sắm <span aria-hidden="true">↗</span>
            </a>
        </header>

        <?php if (empty($orders)): ?>
            <section class="orders-empty">
                <div class="orders-empty-icon" aria-hidden="true">▣</div>
                <span class="orders-kicker">CHƯA CÓ ĐƠN HÀNG</span>
                <h2>Đơn hàng đầu tiên đang chờ bạn</h2>
                <p>Khi bạn hoàn tất đặt hàng, thông tin và trạng thái đơn sẽ xuất hiện tại đây.</p>
                <a class="orders-primary-link" href="<?= BASE_URL ?>index.php?controller=product&action=index">
                    Khám phá sản phẩm <span aria-hidden="true">→</span>
                </a>
            </section>
        <?php else: ?>
            <div class="orders-list-heading">
                <div>
                    <span class="orders-kicker">LỊCH SỬ ĐẶT HÀNG</span>
                    <h2>Danh sách đơn hàng</h2>
                </div>
                <span class="orders-range">
                    <?= number_format($startOrder) ?>–<?= number_format($endOrder) ?>
                    <span>trong</span>
                    <?= number_format($totalOrders) ?> đơn
                </span>
            </div>

            <section class="orders-list" aria-label="Danh sách đơn hàng">
                <?php foreach ($orders as $order): ?>
                    <?php
                    $status = $order["status"] ?? "";
                    $paymentStatus = $order["payment_status"] ?? "";
                    $orderId = (int) $order["id"];
                    $statusClass = $statusClasses[$status] ?? "neutral";
                    $paymentStatusClass = $paymentStatusClasses[$paymentStatus] ?? "neutral";
                    ?>
                    <article class="orders-card">
                        <div class="orders-card-top">
                            <div class="orders-card-id">
                                <span>MÃ ĐƠN HÀNG</span>
                                <strong>#<?= $orderId ?></strong>
                            </div>
                            <time datetime="<?= htmlspecialchars(date("c", strtotime($order["created_at"])), ENT_QUOTES, "UTF-8") ?>">
                                <?= htmlspecialchars(
                                    date("d/m/Y · H:i", strtotime($order["created_at"])),
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>
                            </time>
                        </div>

                        <div class="orders-card-main">
                            <div class="orders-card-total">
                                <span>Tổng thanh toán</span>
                                <strong><?= number_format((float) $order["total_amount"], 0, ",", ".") ?> đ</strong>
                            </div>
                            <div class="orders-card-payment">
                                <span>Phương thức</span>
                                <strong>
                                    <?= htmlspecialchars(
                                        $paymentMethodLabels[$order["payment_method"]]
                                            ?? $order["payment_method"],
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ) ?>
                                </strong>
                            </div>
                            <div class="orders-card-statuses">
                                <span class="orders-status orders-status-<?= $statusClass ?>">
                                    <?= htmlspecialchars($statusLabels[$status] ?? $status, ENT_QUOTES, "UTF-8") ?>
                                </span>
                                <span class="orders-status orders-payment-status orders-payment-status-<?= $paymentStatusClass ?>">
                                    <?= htmlspecialchars(
                                        $paymentStatusLabels[$paymentStatus] ?? $paymentStatus,
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ) ?>
                                </span>
                            </div>
                        </div>

                        <div class="orders-card-footer">
                            <span><i aria-hidden="true">✓</i> Đơn hàng được cập nhật theo trạng thái mới nhất</span>
                            <a href="<?= BASE_URL ?>index.php?controller=order&action=detail&id=<?= $orderId ?>">
                                Xem chi tiết <span aria-hidden="true">→</span>
                            </a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </section>

            <?php if ($totalPages > 1): ?>
                <nav class="orders-pagination" aria-label="Phân trang đơn hàng">
                    <a
                        class="orders-page-control <?= $currentPage <= 1 ? "is-disabled" : "" ?>"
                        href="<?= BASE_URL ?>index.php?controller=order&action=index&page=<?= max(1, $currentPage - 1) ?>"
                        <?= $currentPage <= 1 ? 'aria-disabled="true"' : "" ?>
                    ><span aria-hidden="true">←</span> Trước</a>

                    <div class="orders-page-numbers">
                        <?php for ($page = 1; $page <= $totalPages; $page++): ?>
                            <?php if ($page === $currentPage): ?>
                                <span class="is-current" aria-current="page"><?= $page ?></span>
                            <?php else: ?>
                                <a href="<?= BASE_URL ?>index.php?controller=order&action=index&page=<?= $page ?>">
                                    <?= $page ?>
                                </a>
                            <?php endif; ?>
                        <?php endfor; ?>
                    </div>

                    <a
                        class="orders-page-control <?= $currentPage >= $totalPages ? "is-disabled" : "" ?>"
                        href="<?= BASE_URL ?>index.php?controller=order&action=index&page=<?= min($totalPages, $currentPage + 1) ?>"
                        <?= $currentPage >= $totalPages ? 'aria-disabled="true"' : "" ?>
                    >Tiếp <span aria-hidden="true">→</span></a>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</main>

<?php require __DIR__ . "/../layouts/footer.php"; ?>
