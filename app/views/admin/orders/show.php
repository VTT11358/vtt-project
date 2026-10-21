<?php
require __DIR__ . "/../../layouts/admin_header.php";

$order = $order ?? [];
$items = $items ?? [];
$payments = $payments ?? [];
$nextStatuses = $nextStatuses ?? [];
$baseUrl = BASE_URL . "index.php?controller=adminOrder";
$orderId = (int) ($order["id"] ?? 0);
$orderStatus = $order["status"] ?? "";
$payStatus = $order["payment_status"] ?? "";
$isCancelled = $orderStatus === "cancelled";
$isCompleted = $orderStatus === "completed";
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div>
            <h2 class="mb-1">Đơn hàng #<?= $orderId ?></h2>
            <p class="text-muted mb-0">
                Tạo lúc
                <?= htmlspecialchars(
                    !empty($order["created_at"])
                        ? date("d/m/Y H:i", strtotime($order["created_at"]))
                        : "",
                    ENT_QUOTES,
                    "UTF-8"
                ) ?>
            </p>
        </div>

        <a href="<?= htmlspecialchars($baseUrl . '&action=index') ?>" class="btn btn-outline-secondary">
            ← Danh sách đơn hàng
        </a>
    </div>

    <?php if (!empty($_SESSION["admin_success"])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= htmlspecialchars($_SESSION["admin_success"], ENT_QUOTES, "UTF-8") ?>
            <button type="button" class="btn-close" data-dismiss-alert aria-label="Đóng"></button>
        </div>
        <?php unset($_SESSION["admin_success"]); ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION["admin_error"])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?= htmlspecialchars($_SESSION["admin_error"], ENT_QUOTES, "UTF-8") ?>
            <button type="button" class="btn-close" data-dismiss-alert aria-label="Đóng"></button>
        </div>
        <?php unset($_SESSION["admin_error"]); ?>
    <?php endif; ?>

    <div class="row g-3 mb-3">
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title">Thông tin khách hàng</h5>
                    <p class="mb-1">
                        <strong><?= htmlspecialchars($order["customer_name"] ?? "", ENT_QUOTES, "UTF-8") ?></strong>
                    </p>
                    <p class="mb-1">SĐT: <?= htmlspecialchars($order["phone"] ?? "", ENT_QUOTES, "UTF-8") ?></p>
                    <p class="mb-1">Email: <?= htmlspecialchars($order["email"] ?? "—", ENT_QUOTES, "UTF-8") ?></p>
                    <p class="mb-1">
                        Địa chỉ: <?= htmlspecialchars($order["address"] ?? "", ENT_QUOTES, "UTF-8") ?>
                    </p>
                    <?php if (!empty($order["user_name"])): ?>
                        <p class="mb-0 text-muted">
                            Tài khoản: <?= htmlspecialchars($order["user_name"], ENT_QUOTES, "UTF-8") ?>
                            (<?= htmlspecialchars($order["user_email"] ?? "", ENT_QUOTES, "UTF-8") ?>)
                        </p>
                    <?php endif; ?>
                    <?php if (!empty($order["note"])): ?>
                        <hr>
                        <p class="mb-0">
                            <strong>Ghi chú:</strong>
                            <?= nl2br(htmlspecialchars($order["note"], ENT_QUOTES, "UTF-8")) ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title">Trạng thái đơn hàng</h5>
                    <p class="mb-2">
                        Đơn hàng:
                        <span class="badge <?= AdminOrder::statusBadge($orderStatus) ?>">
                            <?= htmlspecialchars(AdminOrder::STATUSES[$orderStatus] ?? $orderStatus, ENT_QUOTES, "UTF-8") ?>
                        </span>
                    </p>
                    <p class="mb-3">
                        Thanh toán:
                        <span class="badge <?= AdminOrder::paymentBadge($payStatus) ?>">
                            <?= htmlspecialchars(AdminOrder::PAYMENT_STATUSES[$payStatus] ?? $payStatus, ENT_QUOTES, "UTF-8") ?>
                        </span>
                        <span class="text-muted">
                            · <?= htmlspecialchars(
                                AdminOrder::PAYMENT_METHODS[$order["payment_method"] ?? ""]
                                    ?? ($order["payment_method"] ?? ""),
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>
                        </span>
                    </p>

                    <?php if (!empty($nextStatuses)): ?>
                        <form
                            method="POST"
                            action="<?= htmlspecialchars($baseUrl . '&action=updateStatus', ENT_QUOTES, 'UTF-8') ?>"
                            class="row g-2 mb-3"
                        >
                            <input type="hidden" name="id" value="<?= $orderId ?>">
                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?= htmlspecialchars($_SESSION["csrf_token"] ?? "", ENT_QUOTES, "UTF-8") ?>"
                            >
                            <div class="col-8">
                                <select name="status" class="form-select" required>
                                    <option value="">Chuyển trạng thái...</option>
                                    <?php foreach ($nextStatuses as $next): ?>
                                        <option value="<?= htmlspecialchars($next, ENT_QUOTES, "UTF-8") ?>">
                                            <?= htmlspecialchars(AdminOrder::STATUSES[$next] ?? $next, ENT_QUOTES, "UTF-8") ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-4">
                                <button type="submit" class="btn btn-primary w-100">Cập nhật</button>
                            </div>
                        </form>
                    <?php else: ?>
                        <p class="text-muted">Đơn hàng đã kết thúc, không thể đổi trạng thái.</p>
                    <?php endif; ?>

                    <?php if (!$isCancelled): ?>
                        <form
                            method="POST"
                            action="<?= htmlspecialchars($baseUrl . '&action=updatePayment', ENT_QUOTES, 'UTF-8') ?>"
                            class="row g-2"
                        >
                            <input type="hidden" name="id" value="<?= $orderId ?>">
                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?= htmlspecialchars($_SESSION["csrf_token"] ?? "", ENT_QUOTES, "UTF-8") ?>"
                            >
                            <div class="col-8">
                                <select name="payment_status" class="form-select" required>
                                    <?php foreach (AdminOrder::PAYMENT_STATUSES as $key => $label): ?>
                                        <?php if ($isCompleted && $key === "failed") { continue; } ?>
                                        <option value="<?= htmlspecialchars($key, ENT_QUOTES, "UTF-8") ?>"
                                            <?= $payStatus === $key ? "selected" : "" ?>>
                                            <?= htmlspecialchars($label, ENT_QUOTES, "UTF-8") ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-4">
                                <button type="submit" class="btn btn-outline-primary w-100">Lưu TT</button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <h5 class="card-title">Sản phẩm trong đơn</h5>
            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Sản phẩm</th>
                            <th class="text-end" style="width: 150px;">Đơn giá</th>
                            <th class="text-center" style="width: 100px;">SL</th>
                            <th class="text-end" style="width: 160px;">Thành tiền</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($items)): ?>
                            <tr>
                                <td colspan="4" class="text-center py-3">Đơn hàng chưa có sản phẩm.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($items as $item): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <?php if (!empty($item["product_image"])): ?>
                                                <img
                                                    src="<?= BASE_URL . htmlspecialchars($item["product_image"], ENT_QUOTES, "UTF-8") ?>"
                                                    alt=""
                                                    style="width: 48px; height: 48px; object-fit: cover;"
                                                    class="rounded border"
                                                >
                                            <?php endif; ?>
                                            <div>
                                                <?= htmlspecialchars($item["product_name"] ?? "", ENT_QUOTES, "UTF-8") ?>
                                                <div class="small text-muted">ID SP: <?= (int) ($item["product_id"] ?? 0) ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        <?= number_format((float) ($item["price"] ?? 0), 0, ",", ".") ?> đ
                                    </td>
                                    <td class="text-center"><?= (int) ($item["quantity"] ?? 0) ?></td>
                                    <td class="text-end">
                                        <?= number_format((float) ($item["subtotal"] ?? 0), 0, ",", ".") ?> đ
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="3" class="text-end">Tổng cộng</th>
                            <th class="text-end">
                                <?= number_format((float) ($order["total_amount"] ?? 0), 0, ",", ".") ?> đ
                            </th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <?php if (!empty($payments)): ?>
        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <h5 class="card-title">Lịch sử thanh toán</h5>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Mã giao dịch</th>
                                <th>Phương thức</th>
                                <th>Số tiền</th>
                                <th>Trạng thái</th>
                                <th>Thời gian</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payments as $payment): ?>
                                <tr>
                                    <td><?= htmlspecialchars($payment["transaction_code"] ?? "—", ENT_QUOTES, "UTF-8") ?></td>
                                    <td>
                                        <?= htmlspecialchars(
                                            AdminOrder::PAYMENT_METHODS[$payment["payment_method"] ?? ""]
                                                ?? ($payment["payment_method"] ?? ""),
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>
                                    </td>
                                    <td><?= number_format((float) ($payment["amount"] ?? 0), 0, ",", ".") ?> đ</td>
                                    <td>
                                        <span class="badge <?= AdminOrder::paymentBadge($payment["status"] ?? "") ?>">
                                            <?= htmlspecialchars(
                                                AdminOrder::PAYMENT_STATUSES[$payment["status"] ?? ""]
                                                    ?? ($payment["status"] ?? ""),
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars(
                                            !empty($payment["paid_at"])
                                                ? date("d/m/Y H:i", strtotime($payment["paid_at"]))
                                                : (!empty($payment["created_at"])
                                                    ? date("d/m/Y H:i", strtotime($payment["created_at"]))
                                                    : ""),
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . "/../../layouts/admin_footer.php"; ?>
