<?php
require_once __DIR__ . "/../../../helpers/ProductImage.php";
require __DIR__ . "/../../layouts/admin_header.php";

$paginationUrl = $paginationUrl ?? static function ($page) use ($search) {
    return "index.php?" . http_build_query([
        "controller" => "adminProduct",
        "action" => "inventory",
        "q" => $search,
        "page" => (int) $page,
    ]);
};
?>

<main class="container py-4 product-management">
    <div class="product-page-header">
        <div>
            <span class="product-eyebrow">THEO DÕI HÀNG HÓA</span>
            <h1>Sản phẩm còn tồn kho</h1>
            <p>
                Tổng cộng <?= number_format($totalUnits) ?> sản phẩm trong <?= number_format($totalProducts) ?> mặt hàng.
            </p>
        </div>
        <a href="<?= BASE_URL ?>index.php?controller=adminOrder&action=stats" class="product-secondary-link">
            <span aria-hidden="true">←</span>
            Quay lại thống kê
        </a>
    </div>

    <form method="GET" action="<?= BASE_URL ?>index.php" class="product-search-panel">
        <input type="hidden" name="controller" value="adminProduct">
        <input type="hidden" name="action" value="inventory">
        <input type="search" name="q" class="form-control inventory-search"
            aria-label="Tìm kiếm hàng tồn kho"
            placeholder="Tìm tên sản phẩm, danh mục hoặc thương hiệu..."
            value="<?= htmlspecialchars($search, ENT_QUOTES, "UTF-8") ?>">
        <button type="submit" class="btn btn-primary product-search-button">Tìm kiếm</button>
        <?php if ($search !== ""): ?>
            <a href="<?= BASE_URL ?>index.php?controller=adminProduct&action=inventory"
                class="btn btn-outline-secondary product-clear-search">Xóa tìm kiếm</a>
        <?php endif; ?>
    </form>

    <section class="product-table-panel" aria-label="Danh sách hàng tồn kho">
        <div class="table-responsive">
        <table class="table align-middle product-table">
            <thead>
                <tr>
                    <th>Mã</th>
                    <th>Ảnh</th>
                    <th>Tên sản phẩm</th>
                    <th>Danh mục</th>
                    <th>Thương hiệu</th>
                    <th class="text-end">Đã bán</th>
                    <th class="text-end">Còn tồn</th>
                    <th>Trạng thái</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($products)): ?>
                    <tr>
                        <td colspan="9" class="text-center py-4">
                            <?= $search !== ""
                                ? "Không tìm thấy mặt hàng còn tồn phù hợp."
                                : "Hiện không có sản phẩm nào còn tồn kho." ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td>#<?= (int) $product["id"] ?></td>
                            <td>
                                <?php if (!empty($product["image"])): ?>
                                    <img src="<?= htmlspecialchars(productImageUrl($product["image"]), ENT_QUOTES, "UTF-8") ?>"
                                        alt="<?= htmlspecialchars($product["name"] ?? "", ENT_QUOTES, "UTF-8") ?>"
                                        width="64" height="48" class="inventory-image product-table-image">
                                <?php else: ?>
                                    <span class="text-muted">Chưa có ảnh</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($product["name"] ?? "", ENT_QUOTES, "UTF-8") ?></td>
                            <td><?= htmlspecialchars($product["category_name"] ?? "", ENT_QUOTES, "UTF-8") ?></td>
                            <td><?= htmlspecialchars($product["brand_name"] ?? "", ENT_QUOTES, "UTF-8") ?></td>
                            <td class="text-end"><?= number_format((int) ($product["sold_quantity"] ?? 0)) ?></td>
                            <td class="text-end"><strong><?= number_format((int) $product["quantity"]) ?></strong></td>
                            <td>
                                <?php if ((int) $product["is_active"] === 1): ?>
                                    <span class="badge bg-success">Đang bán</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Đã ẩn</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a class="btn btn-sm btn-outline-primary"
                                    href="<?= BASE_URL ?>index.php?controller=adminProduct&action=editProduct&id=<?= (int) $product["id"] ?>">
                                    Sửa
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        </div>

    <div class="product-pagination">
        <span class="text-muted">
            Hiển thị <?= $startProduct ?>–<?= $endProduct ?> trong <?= number_format($totalProducts) ?> mặt hàng còn tồn
        </span>

        <?php if ($totalPages > 1): ?>
            <nav aria-label="Phân trang sản phẩm còn tồn">
                <ul class="pagination mb-0">
                    <li class="page-item <?= $currentPage <= 1 ? "disabled" : "" ?>">
                        <?php if ($currentPage > 1): ?>
                            <a class="page-link" href="<?= htmlspecialchars($paginationUrl($currentPage - 1), ENT_QUOTES, "UTF-8") ?>">« Trước</a>
                        <?php else: ?>
                            <span class="page-link">« Trước</span>
                        <?php endif; ?>
                    </li>

                    <?php for ($page = $startPage; $page <= $endPage; $page++): ?>
                        <li class="page-item <?= $page === $currentPage ? "active" : "" ?>">
                            <?php if ($page === $currentPage): ?>
                                <span class="page-link"><?= $page ?></span>
                            <?php else: ?>
                                <a class="page-link" href="<?= htmlspecialchars($paginationUrl($page), ENT_QUOTES, "UTF-8") ?>"><?= $page ?></a>
                            <?php endif; ?>
                        </li>
                    <?php endfor; ?>

                    <li class="page-item <?= $currentPage >= $totalPages ? "disabled" : "" ?>">
                        <?php if ($currentPage < $totalPages): ?>
                            <a class="page-link" href="<?= htmlspecialchars($paginationUrl($currentPage + 1), ENT_QUOTES, "UTF-8") ?>">Tiếp »</a>
                        <?php else: ?>
                            <span class="page-link">Tiếp »</span>
                        <?php endif; ?>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
    </section>
</main>

<?php require __DIR__ . "/../../layouts/admin_footer.php"; ?>
