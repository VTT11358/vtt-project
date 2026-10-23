<?php require __DIR__ . "/../../layouts/admin_header.php"; ?>

<?php
$statistics = $statistics ?? ["statuses" => [], "payments" => []];
$statusCounts = [];
$paymentCounts = [];
$totalOrders = 0;
$completedOrders = 0;
$cancelledOrders = 0;
$completedRevenue = 0.0;

foreach ($statistics["statuses"] as $row) {
    $status = (string) ($row["status"] ?? "");
    $count = (int) ($row["order_count"] ?? 0);
    $statusCounts[$status] = $count;
    $totalOrders += $count;

    if ($status === "completed") {
        $completedOrders = $count;
        $completedRevenue = (float) ($row["order_amount"] ?? 0);
    }

    if ($status === "cancelled") {
        $cancelledOrders = $count;
    }
}

foreach ($statistics["payments"] as $row) {
    $paymentCounts[(string) ($row["payment_status"] ?? "")] = (int) ($row["order_count"] ?? 0);
}

$statusColors = [
    "pending" => "#f0ad4e",
    "confirmed" => "#0dcaf0",
    "shipping" => "#0d6efd",
    "completed" => "#198754",
    "cancelled" => "#6c757d",
];
$revenueByMonth = array_fill(1, 12, 0.0);
foreach ($revenueChart ?? [] as $chartRow) {
    $monthNumber = (int) ($chartRow["month_number"] ?? 0);
    if ($monthNumber >= 1 && $monthNumber <= 12) {
        $revenueByMonth[$monthNumber] = (float) ($chartRow["revenue"] ?? 0);
    }
}

$chartRows = [];
$maximumRevenue = 0.0;
$yearRevenue = 0.0;

for ($monthNumber = 1; $monthNumber <= 12; $monthNumber++) {
    $revenue = $revenueByMonth[$monthNumber];
    $chartRows[] = [
        "month_number" => $monthNumber,
        "revenue" => $revenue,
    ];
    $maximumRevenue = max($maximumRevenue, $revenue);
    $yearRevenue += $revenue;
}
?>

<div class="container py-4 order-statistics">
    <div class="stats-page-header">
        <div>
            <span class="stats-eyebrow">TỔNG QUAN KINH DOANH</span>
            <h1>Thống kê đơn hàng</h1>
            <p>Theo dõi doanh thu, trạng thái đơn hàng và tình hình thanh toán.</p>
        </div>
        <a href="<?= BASE_URL ?>index.php?controller=adminOrder&action=index" class="stats-orders-link">
            <span aria-hidden="true">↗</span>
            Danh sách đơn hàng
        </a>
    </div>

    <?php if ($dateError !== ""): ?>
        <div class="alert alert-warning" role="alert">
            <?= htmlspecialchars($dateError, ENT_QUOTES, "UTF-8") ?>
        </div>
    <?php endif; ?>
    <?php if ($chartYearError !== ""): ?>
        <div class="alert alert-warning" role="alert">
            <?= htmlspecialchars($chartYearError, ENT_QUOTES, "UTF-8") ?>
        </div>
    <?php endif; ?>

    <form id="statistics-filter" method="GET" action="<?= BASE_URL ?>index.php" class="stats-filter">
        <input type="hidden" name="controller" value="adminOrder">
        <input type="hidden" name="action" value="stats">
        <div class="stats-filter-heading">
            <div>
                <h2>Lọc theo thời gian</h2>
                <p>Chọn khoảng ngày để cập nhật các chỉ số bên dưới.</p>
            </div>
            <span class="stats-filter-icon" aria-hidden="true">⌑</span>
        </div>
        <div class="stats-filter-fields">
            <div>
                <label class="form-label" for="date-from">Từ ngày</label>
                <input class="form-control" type="date" id="date-from" name="date_from"
                    value="<?= htmlspecialchars($dateFrom, ENT_QUOTES, "UTF-8") ?>" required>
            </div>
            <div>
                <label class="form-label" for="date-to">Đến ngày</label>
                <input class="form-control" type="date" id="date-to" name="date_to"
                    value="<?= htmlspecialchars($dateTo, ENT_QUOTES, "UTF-8") ?>" required>
            </div>
            <button class="btn btn-primary" type="submit">Xem thống kê</button>
        </div>
    </form>

    <section class="stats-cards" aria-label="Tổng quan đơn hàng">
        <article class="card stats-card">
            <div class="stats-card-top">
                <span class="stats-card-label">Tổng đơn hàng</span>
                <span class="stats-card-icon stats-icon-orders" aria-hidden="true">▤</span>
            </div>
            <strong><?= number_format($totalOrders) ?></strong>
            <span class="stats-card-note">Trong khoảng thời gian đã chọn</span>
        </article>
        <article class="card stats-card">
            <div class="stats-card-top">
                <span class="stats-card-label">Đơn hoàn thành</span>
                <span class="stats-card-icon stats-icon-completed" aria-hidden="true">✓</span>
            </div>
            <strong><?= number_format($completedOrders) ?></strong>
            <span class="stats-card-note">Đã giao thành công</span>
        </article>
        <article class="card stats-card">
            <div class="stats-card-top">
                <span class="stats-card-label">Đơn đã hủy</span>
                <span class="stats-card-icon stats-icon-cancelled" aria-hidden="true">×</span>
            </div>
            <strong><?= number_format($cancelledOrders) ?></strong>
            <span class="stats-card-note">Đơn hàng bị hủy</span>
        </article>
        <article class="card stats-card stats-revenue">
            <div class="stats-card-top">
                <span class="stats-card-label">Doanh thu hoàn thành</span>
                <span class="stats-card-icon stats-icon-revenue" aria-hidden="true">₫</span>
            </div>
            <strong><?= number_format($completedRevenue, 0, ",", ".") ?> đ</strong>
            <span class="stats-card-note">Từ các đơn đã hoàn thành</span>
        </article>
        <a class="card stats-card stats-inventory"
            href="<?= BASE_URL ?>index.php?controller=adminProduct&action=inventory">
            <div class="stats-card-top">
                <span class="stats-card-label">Sản phẩm còn tồn</span>
                <span class="stats-card-icon stats-icon-inventory" aria-hidden="true">▣</span>
            </div>
            <strong><?= number_format($inventorySummary["unit_count"] ?? 0) ?> sản phẩm</strong>
            <span class="stats-card-note">
                Trong <?= number_format($inventorySummary["product_count"] ?? 0) ?> mặt hàng
            </span>
        </a>
    </section>

    <section class="card stats-chart-panel">
        <div class="card-header stats-chart-header">
            <div>
                <h2 class="stats-panel-title">Doanh thu đơn hoàn thành theo tháng năm <?= $chartYear ?></h2>
                <p class="text-muted mb-0">Chọn tháng hoặc quý để làm nổi bật kỳ cần xem.</p>
            </div>
            <div class="stats-chart-controls">
                <div>
                    <label class="form-label" for="chart-year">Năm</label>
                    <select class="form-select" id="chart-year" name="chart_year"
                        form="statistics-filter" data-submit-form>
                        <?php foreach ($chartYears as $yearOption): ?>
                            <option value="<?= $yearOption ?>" <?= $chartYear === $yearOption ? "selected" : "" ?>>
                                <?= $yearOption ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="form-label" for="chart-period-filter">Lọc biểu đồ</label>
                    <select class="form-select" id="chart-period-filter" data-chart-filter>
                        <option value="">Cả năm</option>
                        <?php for ($month = 1; $month <= 12; $month++): ?>
                            <option value="month-<?= $month ?>">Tháng <?= $month ?></option>
                        <?php endfor; ?>
                        <?php for ($quarter = 1; $quarter <= 4; $quarter++): ?>
                            <option value="quarter-<?= $quarter ?>">Quý <?= $quarter ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>
        </div>
        <div class="card-body">
            <p class="stats-chart-total">
                Tổng doanh thu năm <?= $chartYear ?>:
                <strong><?= number_format($yearRevenue, 0, ",", ".") ?> đ</strong>
            </p>
            <div class="stats-chart" aria-label="Biểu đồ doanh thu theo 12 tháng năm <?= $chartYear ?>">
                <?php foreach ($chartRows as $chartRow): ?>
                    <?php
                    $monthNumber = $chartRow["month_number"];
                    $revenue = $chartRow["revenue"];
                    $barHeight = $maximumRevenue > 0
                        ? max(2, ($revenue / $maximumRevenue) * 100)
                        : 0;
                    ?>
                    <div class="stats-chart-column" data-month="<?= $monthNumber ?>"
                        title="<?= htmlspecialchars(
                            "Tháng " . $monthNumber . ": " . number_format($revenue, 0, ",", ".") . " đồng",
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>">
                        <span class="stats-chart-value">
                            <?= number_format($revenue, 0, ",", ".") ?> đ
                        </span>
                        <div class="stats-chart-track">
                            <span class="stats-chart-bar"
                                style="height: <?= number_format($barHeight, 2, ".", "") ?>%"></span>
                        </div>
                        <span class="stats-chart-label">Tháng <?= $monthNumber ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <div class="stats-details">
        <section class="card stats-panel">
            <div class="card-header">
                <h2 class="stats-panel-title">Đơn hàng theo trạng thái</h2>
            </div>
            <div class="card-body">
                <?php if ($totalOrders === 0): ?>
                    <p class="text-muted mb-0">Không có đơn hàng trong khoảng thời gian này.</p>
                <?php else: ?>
                    <?php foreach (AdminOrder::STATUSES as $status => $label): ?>
                        <?php
                        $count = $statusCounts[$status] ?? 0;
                        $percentage = ($count / $totalOrders) * 100;
                        ?>
                        <div class="stats-row">
                            <div class="stats-row-heading">
                                <span><?= htmlspecialchars($label, ENT_QUOTES, "UTF-8") ?></span>
                                <strong><?= number_format($count) ?> <small>(<?= number_format($percentage, 1) ?>%)</small></strong>
                            </div>
                            <div class="stats-track" role="img"
                                aria-label="<?= htmlspecialchars($label . ": " . $count . " đơn", ENT_QUOTES, "UTF-8") ?>">
                                <span style="width: <?= number_format($percentage, 2, ".", "") ?>%; background-color: <?= $statusColors[$status] ?>"></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

        <section class="card stats-panel">
            <div class="card-header">
                <h2 class="stats-panel-title">Đơn hàng theo thanh toán</h2>
            </div>
            <div class="card-body">
                <?php if ($totalOrders === 0): ?>
                    <p class="text-muted mb-0">Không có dữ liệu thanh toán trong khoảng thời gian này.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table stats-payment-table mb-0">
                            <thead>
                                <tr>
                                    <th>Trạng thái</th>
                                    <th class="text-end">Số đơn</th>
                                    <th class="text-end">Tỷ lệ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (AdminOrder::PAYMENT_STATUSES as $paymentStatus => $label): ?>
                                    <?php
                                    $count = $paymentCounts[$paymentStatus] ?? 0;
                                    $percentage = ($count / $totalOrders) * 100;
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars($label, ENT_QUOTES, "UTF-8") ?></td>
                                        <td class="text-end"><?= number_format($count) ?></td>
                                        <td class="text-end"><?= number_format($percentage, 1) ?>%</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <p class="stats-period text-muted">
        Thời gian thống kê:
        <?= htmlspecialchars(date("d/m/Y", strtotime($dateFrom)), ENT_QUOTES, "UTF-8") ?>
        – <?= htmlspecialchars(date("d/m/Y", strtotime($dateTo)), ENT_QUOTES, "UTF-8") ?>
    </p>
</div>

<?php require __DIR__ . "/../../layouts/admin_footer.php"; ?>
