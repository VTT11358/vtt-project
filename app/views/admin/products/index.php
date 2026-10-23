
<?php
require_once __DIR__ . "/../../../helpers/ProductImage.php";
require __DIR__ . "/../../layouts/admin_header.php";
?>

<?php
$search = $search ?? "";
$currentPage = (int) ($currentPage ?? 1);
$limit = (int) ($limit ?? 10);
$totalProducts = (int) ($totalProducts ?? 0);
$totalPages = (int) ($totalPages ?? 1);

$currentPage = max(1, $currentPage);
$totalPages = max(1, $totalPages);

$startProduct = $totalProducts > 0
    ? ($currentPage - 1) * $limit + 1
    : 0;

$endProduct = min($currentPage * $limit, $totalProducts);

// Tạo URL phân trang và giữ từ khóa tìm kiếm
$paginationUrl = function ($page) use ($search) {
    return "index.php?" . http_build_query([
        "controller" => "adminProduct",
        "action" => "products",
        "q" => $search,
        "page" => (int) $page
    ]);
};

$startPage = max(1, $currentPage - 2);
$endPage = min($totalPages, $currentPage + 2);
?>

<main class="container py-4 product-management">
    <div class="product-page-header">
        <div>
            <span class="product-eyebrow">DANH MỤC KINH DOANH</span>
            <h1>Quản lý sản phẩm</h1>
            <p>Quản lý thông tin, giá bán và tình trạng sản phẩm trong cửa hàng.</p>
        </div>

        <a
            href="<?= BASE_URL ?>index.php?controller=adminProduct&action=createProduct"
            class="product-primary-link"
        >
            <span aria-hidden="true">＋</span> Thêm sản phẩm
        </a>
    </div>

<!-- Thông báo thành công -->
<?php if (!empty($_SESSION["admin_success"])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <?= htmlspecialchars(
            $_SESSION["admin_success"],
            ENT_QUOTES,
            "UTF-8"
        ) ?>
        <button
            type="button"
            class="btn-close"
            data-dismiss-alert
            aria-label="Đóng"
        ></button>
    </div>
    <?php unset($_SESSION["admin_success"]); ?>
<?php endif; ?>

<!-- Thông báo lỗi -->
<?php if (!empty($_SESSION["admin_error"])): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <?= htmlspecialchars(
            $_SESSION["admin_error"],
            ENT_QUOTES,
            "UTF-8"
        ) ?>
        <button
            type="button"
            class="btn-close"
            data-dismiss-alert
            aria-label="Đóng"
        ></button>
    </div>
    <?php unset($_SESSION["admin_error"]); ?>
<?php endif; ?>


    <!-- Tìm kiếm -->
    <form
        method="GET"
        action="<?= BASE_URL ?>index.php"
        class="product-search-panel"
    >
        <input type="hidden" name="controller" value="adminProduct">
        <input type="hidden" name="action" value="products">

        <input
            type="text"
            name="q"
            class="form-control"
            aria-label="Tìm kiếm sản phẩm"
            placeholder="Tên sản phẩm, danh mục hoặc thương hiệu..."
            value="<?= htmlspecialchars($search, ENT_QUOTES, "UTF-8") ?>"
        >

        <button type="submit" class="btn btn-primary product-search-button">
            Tìm kiếm
        </button>

        <?php if ($search !== ""): ?>
            <a
                href="<?= BASE_URL ?>index.php?controller=adminProduct&action=products"
                class="btn btn-outline-secondary product-clear-search"
            >
                Xóa tìm kiếm
            </a>
        <?php endif; ?>
    </form>

    <!-- Bảng sản phẩm -->
    <section class="product-table-panel" aria-label="Danh sách sản phẩm">
        <div class="table-responsive">
            <table class="table align-middle product-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Ảnh</th>
                        <th>Tên sản phẩm</th>
                        <th>Danh mục</th>
                        <th>Thương hiệu</th>
                        <th>Giá</th>
                        <th>Tồn kho</th>
                        <th>Trạng thái</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if (empty($products)): ?>
                        <tr>
                            <td colspan="9" class="text-center py-4">
                                <?= $search !== ""
                                    ? "Không tìm thấy sản phẩm phù hợp."
                                    : "Chưa có sản phẩm." ?>
                            </td>
                        </tr>
                    <?php else: ?>

                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td><?= (int) $product["id"] ?></td>

                                <td>
                                    <?php if (!empty($product["image"])): ?>
                                        <img
                                            src="<?= htmlspecialchars(productImageUrl($product["image"]), ENT_QUOTES, "UTF-8") ?>"
                                            alt="Ảnh sản phẩm"
                                            width="80"
                                            height="60"
                                            class="product-table-image"
                                        >
                                    <?php else: ?>
                                        Chưa có ảnh
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($product["name"] ?? "", ENT_QUOTES, "UTF-8") ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($product["category_name"] ?? "", ENT_QUOTES, "UTF-8") ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($product["brand_name"] ?? "", ENT_QUOTES, "UTF-8") ?>
                                </td>

                                <td>
                                    <?= number_format((float) $product["price"], 0, ",", ".") ?> đ

                                    <?php if ($product["sale_price"] !== null): ?>
                                        <br>
                                        <small class="text-danger">
                                            Khuyến mãi:
                                            <?= number_format((float) $product["sale_price"], 0, ",", ".") ?> đ
                                        </small>
                                    <?php endif; ?>
                                </td>

                                <td><?= (int) $product["quantity"] ?></td>

                                <td>
                                    <?php if ((int) $product["is_active"] === 1): ?>
                                        <span class="badge bg-success">Đang bán</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Đã ẩn</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <div class="d-flex flex-wrap gap-2">
                                        <a
                                            class="btn btn-sm product-edit-button"
                                            href="<?= BASE_URL ?>index.php?controller=adminProduct&action=editProduct&id=<?= (int) $product["id"] ?>"
                                        >
                                            Sửa
                                        </a>

                                        <form
                                            method="POST"
                                            action="<?= BASE_URL ?>index.php?controller=adminProduct&action=deleteProduct"
                                            onsubmit="return confirm('Bạn có chắc chắn muốn xóa sản phẩm này?');"
                                        >
                                            <input
                                                type="hidden"
                                                name="id"
                                                value="<?= (int) $product["id"] ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="csrf_token"
                                                value="<?= htmlspecialchars($_SESSION["csrf_token"] ?? "", ENT_QUOTES, "UTF-8") ?>"
                                            >

                                            <button
                                                type="submit"
                                                class="btn btn-sm btn-danger product-delete-button"
                                            >
                                                Xóa
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="product-pagination">
            <div class="text-muted">
                Hiển thị <?= $startProduct ?>–<?= $endProduct ?>
                trong tổng số <?= $totalProducts ?> sản phẩm
            </div>

            <nav aria-label="Phân trang sản phẩm">
                <ul class="pagination mb-0 flex-wrap">

                    <!-- Trang trước -->
                    <li class="page-item <?= $currentPage <= 1 ? "disabled" : "" ?>">
                        <?php if ($currentPage > 1): ?>
                            <a
                                class="page-link"
                                href="<?= htmlspecialchars($paginationUrl($currentPage - 1), ENT_QUOTES, "UTF-8") ?>"
                            >
                                &laquo; Trước
                            </a>
                        <?php else: ?>
                            <span class="page-link">&laquo; Trước</span>
                        <?php endif; ?>
                    </li>

                <!-- Trang đầu -->
                <?php if ($startPage > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="<?= htmlspecialchars($paginationUrl(1), ENT_QUOTES, "UTF-8") ?>">
                            1
                        </a>
                    </li>

                    <?php if ($startPage > 2): ?>
                        <li class="page-item disabled">
                            <span class="page-link">...</span>
                        </li>
                    <?php endif; ?>
                <?php endif; ?>

                <!-- Các trang xung quanh trang hiện tại -->
                <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                    <li class="page-item <?= $i === $currentPage ? "active" : "" ?>">
                        <?php if ($i === $currentPage): ?>
                            <span class="page-link"><?= $i ?></span>
                        <?php else: ?>
                            <a
                                class="page-link"
                                href="<?= htmlspecialchars($paginationUrl($i), ENT_QUOTES, "UTF-8") ?>"
                            >
                                <?= $i ?>
                            </a>
                        <?php endif; ?>
                    </li>
                <?php endfor; ?>

                <!-- Trang cuối -->
                <?php if ($endPage < $totalPages): ?>
                    <?php if ($endPage < $totalPages - 1): ?>
                        <li class="page-item disabled">
                            <span class="page-link">...</span>
                        </li>
                    <?php endif; ?>

                    <li class="page-item">
                        <a
                            class="page-link"
                            href="<?= htmlspecialchars($paginationUrl($totalPages), ENT_QUOTES, "UTF-8") ?>"
                        >
                            <?= $totalPages ?>
                        </a>
                    </li>
                <?php endif; ?>

                <!-- Trang tiếp -->
                <li class="page-item <?= $currentPage >= $totalPages ? "disabled" : "" ?>">
                    <?php if ($currentPage < $totalPages): ?>
                        <a
                            class="page-link"
                            href="<?= htmlspecialchars($paginationUrl($currentPage + 1), ENT_QUOTES, "UTF-8") ?>"
                        >
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
</main>

<?php require __DIR__ . "/../../layouts/admin_footer.php"; ?>
