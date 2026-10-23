<?php require __DIR__ . "/../../layouts/admin_header.php"; ?>

<?php
$search = $search ?? "";
$status = $status ?? "";
$paymentStatus = $paymentStatus ?? "";
$dateFrom = $dateFrom ?? "";
$dateTo = $dateTo ?? "";
$dateError = $dateError ?? "";
$currentPage = (int) ($currentPage ?? 1);
$limit = (int) ($limit ?? 10);
$totalOrders = (int) ($totalOrders ?? 0);
$totalPages = (int) ($totalPages ?? 1);
$orders = $orders ?? [];

$currentPage = max(1, $currentPage);
$totalPages = max(1, $totalPages);

$startOrder = $totalOrders > 0
    ? ($currentPage - 1) * $limit + 1
    : 0;

$endOrder = min($currentPage * $limit, $totalOrders);

$paginationUrl = function ($page) use ($search, $status, $paymentStatus, $dateFrom, $dateTo) {
    return "index.php?" . http_build_query([
        "controller" => "adminOrder",
        "action" => "index",
        "q" => $search,
        "status" => $status,
        "payment_status" => $paymentStatus,
        "date_from" => $dateFrom,
        "date_to" => $dateTo,
        "page" => (int) $page
    ]);
};

$startPage = max(1, $currentPage - 2);
$endPage = min($totalPages, $currentPage + 2);
$baseUrl = BASE_URL . "index.php?controller=adminOrder";
?>

<main class="container py-4 order-management">
    <div class="order-page-header">
        <div>
            <span class="order-eyebrow">THEO DÕI KINH DOANH</span>
            <h1>Quản lý đơn hàng</h1>
            <p>Xem thông tin, lọc đơn hàng và cập nhật trạng thái xử lý.</p>
        </div>
        <a href="<?= BASE_URL ?>index.php?controller=adminOrder&action=stats" class="order-secondary-link">
            <span aria-hidden="true">▦</span> Thống kê đơn hàng
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

    <?php if ($dateError !== ""): ?>
        <div class="alert alert-warning" role="alert">
            <?= htmlspecialchars($dateError, ENT_QUOTES, "UTF-8") ?>
        </div>
    <?php endif; ?>

    <form method="GET" action="<?= BASE_URL ?>index.php" class="order-filter-panel">
        <input type="hidden" name="controller" value="adminOrder">
        <input type="hidden" name="action" value="index">

        <div class="order-search-field">
            <input
                type="text"
                name="q"
                class="form-control"
                aria-label="Tìm kiếm đơn hàng"
                placeholder="Mã đơn, tên, SĐT hoặc email..."
                value="<?= htmlspecialchars($search, ENT_QUOTES, "UTF-8") ?>"
            >
        </div>

        <div>
            <select name="status" class="form-select">
                <option value="">Tất cả trạng thái</option>
                <?php foreach (AdminOrder::STATUSES as $key => $label): ?>
                    <option value="<?= htmlspecialchars($key, ENT_QUOTES, "UTF-8") ?>"
                        <?= $status === $key ? "selected" : "" ?>>
                        <?= htmlspecialchars($label, ENT_QUOTES, "UTF-8") ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <select name="payment_status" class="form-select">
                <option value="">Tất cả thanh toán</option>
                <?php foreach (AdminOrder::PAYMENT_STATUSES as $key => $label): ?>
                    <option value="<?= htmlspecialchars($key, ENT_QUOTES, "UTF-8") ?>"
                        <?= $paymentStatus === $key ? "selected" : "" ?>>
                        <?= htmlspecialchars($label, ENT_QUOTES, "UTF-8") ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="order-date-field">
            <label for="order-date-from">Từ ngày</label>
            <input
                type="date"
                id="order-date-from"
                name="date_from"
                class="form-control"
                value="<?= htmlspecialchars($dateFrom, ENT_QUOTES, "UTF-8") ?>"
            >
        </div>

        <div class="order-date-field">
            <label for="order-date-to">Đến ngày</label>
            <input
                type="date"
                id="order-date-to"
                name="date_to"
                class="form-control"
                value="<?= htmlspecialchars($dateTo, ENT_QUOTES, "UTF-8") ?>"
            >
        </div>

        <div class="order-filter-actions">
            <button type="submit" class="btn btn-primary order-filter-button">Lọc đơn hàng</button>
            <?php if (
                $search !== ""
                || $status !== ""
                || $paymentStatus !== ""
                || $dateFrom !== ""
                || $dateTo !== ""
            ): ?>
                <a href="<?= htmlspecialchars($baseUrl . '&action=index') ?>" class="btn btn-outline-secondary order-clear-button">
                    Xóa lọc
                </a>
            <?php endif; ?>
        </div>
    </form>

    <section class="order-table-panel" aria-label="Danh sách đơn hàng">
        <div class="table-responsive">
            <table class="table align-middle order-table">
            <thead>
                <tr>
                    <th>Mã</th>
                    <th>Khách hàng</th>
                    <th>Điện thoại</th>
                    <th>Tổng tiền</th>
                    <th>Thanh toán</th>
                    <th>Trạng thái</th>
                    <th>Ngày tạo</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($orders)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-4">
                            Chưa có đơn hàng nào.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($orders as $item): ?>
                        <?php
                        $itemId = (int) ($item["id"] ?? 0);
                        $orderStatus = $item["status"] ?? "";
                        $payStatus = $item["payment_status"] ?? "";
                        ?>
                        <tr>
                            <td>#<?= $itemId ?></td>
                            <td>
                                <strong><?= htmlspecialchars($item["customer_name"] ?? "", ENT_QUOTES, "UTF-8") ?></strong>
                                <?php if (!empty($item["user_name"])): ?>
                                    <div class="small text-muted">
                                        TK: <?= htmlspecialchars($item["user_name"], ENT_QUOTES, "UTF-8") ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($item["phone"] ?? "", ENT_QUOTES, "UTF-8") ?></td>
                            <td>
                                <?= number_format((float) ($item["total_amount"] ?? 0), 0, ",", ".") ?> đ
                            </td>
                            <td>
                                <span class="badge <?= AdminOrder::paymentBadge($payStatus) ?>">
                                    <?= htmlspecialchars(AdminOrder::PAYMENT_STATUSES[$payStatus] ?? $payStatus, ENT_QUOTES, "UTF-8") ?>
                                </span>
                                <div class="small text-muted mt-1">
                                    <?= htmlspecialchars(
                                        AdminOrder::PAYMENT_METHODS[$item["payment_method"] ?? ""]
                                            ?? ($item["payment_method"] ?? ""),
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ) ?>
                                </div>
                            </td>
                            <td>
                                <span class="badge <?= AdminOrder::statusBadge($orderStatus) ?>">
                                    <?= htmlspecialchars(AdminOrder::STATUSES[$orderStatus] ?? $orderStatus, ENT_QUOTES, "UTF-8") ?>
                                </span>
                            </td>
                            <td>
                                <?= htmlspecialchars(
                                    !empty($item["created_at"])
                                        ? date("d/m/Y H:i", strtotime($item["created_at"]))
                                        : "",
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>
                            </td>
                            <td>
                                <a
                                    class="btn btn-sm order-detail-button"
                                    href="<?= htmlspecialchars($baseUrl . '&action=show&id=' . $itemId, ENT_QUOTES, 'UTF-8') ?>"
                                >
                                    Chi tiết
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            </table>
        </div>

    <div class="order-pagination">
        <div class="text-muted">
            Hiển thị <?= $startOrder ?>–<?= $endOrder ?>
            trong tổng số <?= $totalOrders ?> đơn hàng
        </div>

        <nav aria-label="Phân trang đơn hàng">
            <ul class="pagination mb-0 flex-wrap">
                <li class="page-item <?= $currentPage <= 1 ? "disabled" : "" ?>">
                    <?php if ($currentPage > 1): ?>
                        <a class="page-link" href="<?= htmlspecialchars($paginationUrl($currentPage - 1), ENT_QUOTES, "UTF-8") ?>">
                            &laquo; Trước
                        </a>
                    <?php else: ?>
                        <span class="page-link">&laquo; Trước</span>
                    <?php endif; ?>
                </li>

                <?php if ($startPage > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="<?= htmlspecialchars($paginationUrl(1), ENT_QUOTES, "UTF-8") ?>">1</a>
                    </li>
                    <?php if ($startPage > 2): ?>
                        <li class="page-item disabled"><span class="page-link">...</span></li>
                    <?php endif; ?>
                <?php endif; ?>

                <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                    <li class="page-item <?= $i === $currentPage ? "active" : "" ?>">
                        <?php if ($i === $currentPage): ?>
                            <span class="page-link"><?= $i ?></span>
                        <?php else: ?>
                            <a class="page-link" href="<?= htmlspecialchars($paginationUrl($i), ENT_QUOTES, "UTF-8") ?>">
                                <?= $i ?>
                            </a>
                        <?php endif; ?>
                    </li>
                <?php endfor; ?>

                <?php if ($endPage < $totalPages): ?>
                    <?php if ($endPage < $totalPages - 1): ?>
                        <li class="page-item disabled"><span class="page-link">...</span></li>
                    <?php endif; ?>
                    <li class="page-item">
                        <a class="page-link" href="<?= htmlspecialchars($paginationUrl($totalPages), ENT_QUOTES, "UTF-8") ?>">
                            <?= $totalPages ?>
                        </a>
                    </li>
                <?php endif; ?>

                <li class="page-item <?= $currentPage >= $totalPages ? "disabled" : "" ?>">
                    <?php if ($currentPage < $totalPages): ?>
                        <a class="page-link" href="<?= htmlspecialchars($paginationUrl($currentPage + 1), ENT_QUOTES, "UTF-8") ?>">
                            Tiếp &raquo;
                        </a>
                    <?php else: ?>
                        <span class="page-link">Tiếp &raquo;</span>
                    <?php endif; ?>
                </li>
            </ul>
        </nav>
    </div>

    </section>
    <a href="<?= BASE_URL ?>index.php?controller=admin&action=index" class="order-back-admin">
        <span aria-hidden="true">←</span> Quay lại trang quản trị
    </a>
</main>

<?php require __DIR__ . "/../../layouts/admin_footer.php"; ?>
