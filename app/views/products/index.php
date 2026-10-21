<?php

require_once __DIR__ . "/../../helpers/ProductImage.php";

$search = trim((string) ($search ?? ""));
$categoryId = (string) ($category_id ?? "");
$brandId = (string) ($brand_id ?? "");
$totalProducts = (int) ($totalProducts ?? ($products ? $products->num_rows : 0));
$currentPage = (int) ($currentPage ?? 1);
$totalPages = (int) ($totalPages ?? 1);
$firstProduct = (int) ($firstProduct ?? 0);
$lastProduct = (int) ($lastProduct ?? 0);
$safeSearch = htmlspecialchars($search, ENT_QUOTES, "UTF-8");
$productRows = $products ? $products->fetch_all(MYSQLI_ASSOC) : [];
$paginationParams = [
    "controller" => "product",
    "action" => "index",
];
if ($search !== "") {
    $paginationParams["search"] = $search;
}
if ($categoryId !== "") {
    $paginationParams["category_id"] = $categoryId;
}
if ($brandId !== "") {
    $paginationParams["brand_id"] = $brandId;
}
$pageWindowStart = max(1, $currentPage - 2);
$pageWindowEnd = min($totalPages, $currentPage + 2);

require_once __DIR__ . "/../layouts/header.php";
?>

<main class="catalog-page">
    <section class="catalog-main">
        <div class="catalog-container">
            <div class="catalog-toolbar">
                <div>
                    <span class="catalog-section-kicker">BỘ SƯU TẬP CỦA CHÚNG TÔI</span>
                    <h2>
                        <?php if ($search !== ""): ?>
                            Kết quả tìm kiếm
                        <?php elseif ($categoryId !== ""): ?>
                            Laptop theo danh mục
                        <?php elseif ($brandId !== ""): ?>
                            Laptop theo thương hiệu
                        <?php else: ?>
                            Tất cả laptop
                        <?php endif; ?>
                    </h2>
                    <p>
                        <?php if ($totalProducts > 0): ?>
                            Hiển thị <?= number_format($firstProduct) ?>–<?= number_format($lastProduct) ?>
                            trên <?= number_format($totalProducts) ?> sản phẩm
                        <?php else: ?>
                            0 sản phẩm
                        <?php endif; ?>
                        <?php if ($search !== ""): ?>
                            cho “<?= $safeSearch ?>”
                        <?php endif; ?>
                    </p>
                </div>
                <div class="catalog-toolbar-actions">
                    <button
                        class="catalog-filter-toggle"
                        type="button"
                        aria-expanded="false"
                        aria-controls="catalogFilters"
                        data-catalog-filter-toggle
                    >
                        <span aria-hidden="true">☷</span> Bộ lọc
                    </button>
                    <label class="catalog-sort">
                        <span>Sắp xếp</span>
                        <select data-catalog-sort aria-label="Sắp xếp sản phẩm">
                            <option value="default">Mặc định</option>
                            <option value="price-asc">Giá: thấp đến cao</option>
                            <option value="price-desc">Giá: cao đến thấp</option>
                            <option value="name-asc">Tên: A đến Z</option>
                        </select>
                    </label>
                </div>
            </div>

            <div class="catalog-layout">
                <aside class="catalog-sidebar" id="catalogFilters" data-catalog-sidebar>
                    <form class="catalog-filter-form" action="<?= BASE_URL ?>index.php" method="GET">
                        <input type="hidden" name="controller" value="product">
                        <input type="hidden" name="action" value="index">
                        <?php if ($search !== ""): ?>
                            <input type="hidden" name="search" value="<?= $safeSearch ?>">
                        <?php endif; ?>

                        <div class="catalog-filter-heading">
                            <div>
                                <span class="catalog-section-kicker">LỌC SẢN PHẨM</span>
                                <h3>Bộ lọc</h3>
                            </div>
                            <a href="<?= BASE_URL ?>index.php?controller=product&action=index">Xóa lọc</a>
                        </div>

                        <fieldset class="catalog-filter-group">
                            <legend>Danh mục</legend>
                            <label class="catalog-radio">
                                <input type="radio" name="category_id" value="" <?= $categoryId === "" ? "checked" : "" ?>>
                                <span>Tất cả danh mục</span>
                            </label>
                            <?php if ($categories): ?>
                                <?php while ($category = $categories->fetch_assoc()): ?>
                                    <label class="catalog-radio">
                                        <input
                                            type="radio"
                                            name="category_id"
                                            value="<?= (int) $category["id"] ?>"
                                            <?= $categoryId === (string) $category["id"] ? "checked" : "" ?>
                                        >
                                        <span><?= htmlspecialchars($category["name"], ENT_QUOTES, "UTF-8") ?></span>
                                    </label>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </fieldset>

                        <fieldset class="catalog-filter-group">
                            <legend>Thương hiệu</legend>
                            <label class="catalog-radio">
                                <input type="radio" name="brand_id" value="" <?= $brandId === "" ? "checked" : "" ?>>
                                <span>Tất cả thương hiệu</span>
                            </label>
                            <?php if ($brands): ?>
                                <?php while ($brand = $brands->fetch_assoc()): ?>
                                    <label class="catalog-radio">
                                        <input
                                            type="radio"
                                            name="brand_id"
                                            value="<?= (int) $brand["id"] ?>"
                                            <?= $brandId === (string) $brand["id"] ? "checked" : "" ?>
                                        >
                                        <span><?= htmlspecialchars($brand["name"], ENT_QUOTES, "UTF-8") ?></span>
                                    </label>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </fieldset>

                        <button class="catalog-apply-filter" type="submit">Áp dụng bộ lọc <span aria-hidden="true">→</span></button>
                    </form>
                </aside>

                <div class="catalog-results">
                    <?php if ($productRows): ?>
                        <div class="catalog-product-grid" data-catalog-grid>
                            <?php foreach ($productRows as $product): ?>
                                <?php
                                $price = (float) (
                                    !empty($product["sale_price"])
                                        ? $product["sale_price"]
                                        : $product["price"]
                                );
                                $originalPrice = (float) $product["price"];
                                $productName = (string) $product["name"];
                                $stock = (int) ($product["quantity"] ?? 0);
                                ?>
                                <article
                                    class="catalog-product-card"
                                    data-catalog-product
                                    data-price="<?= htmlspecialchars((string) $price, ENT_QUOTES, "UTF-8") ?>"
                                    data-name="<?= htmlspecialchars(mb_strtolower($productName, "UTF-8"), ENT_QUOTES, "UTF-8") ?>"
                                >
                                    <a
                                        class="catalog-product-image"
                                        href="<?= BASE_URL ?>index.php?controller=product&action=detail&id=<?= (int) $product["id"] ?>"
                                        aria-label="Xem <?= htmlspecialchars($productName, ENT_QUOTES, "UTF-8") ?>"
                                    >
                                        <?php if ($price < $originalPrice): ?>
                                            <span class="catalog-product-badge">ƯU ĐÃI</span>
                                        <?php endif; ?>
                                        <?php if (!empty($product["image"])): ?>
                                            <img
                                                src="<?= htmlspecialchars(productImageUrl($product["image"]), ENT_QUOTES, "UTF-8") ?>"
                                                alt="<?= htmlspecialchars($productName, ENT_QUOTES, "UTF-8") ?>"
                                                loading="lazy"
                                            >
                                        <?php else: ?>
                                            <span class="catalog-image-placeholder" aria-hidden="true">LAPTOP</span>
                                        <?php endif; ?>
                                    </a>
                                    <div class="catalog-product-info">
                                        <div class="catalog-product-meta">
                                            <span><?= htmlspecialchars($product["brand_name"] ?? "", ENT_QUOTES, "UTF-8") ?></span>
                                            <span><?= htmlspecialchars($product["category_name"] ?? "", ENT_QUOTES, "UTF-8") ?></span>
                                        </div>
                                        <h3>
                                            <a href="<?= BASE_URL ?>index.php?controller=product&action=detail&id=<?= (int) $product["id"] ?>">
                                                <?= htmlspecialchars($productName, ENT_QUOTES, "UTF-8") ?>
                                            </a>
                                        </h3>
                                        <div class="catalog-product-specs">
                                            <?php if (!empty($product["cpu"])): ?>
                                                <span><?= htmlspecialchars($product["cpu"], ENT_QUOTES, "UTF-8") ?></span>
                                            <?php endif; ?>
                                            <?php if (!empty($product["ram"])): ?>
                                                <span><?= htmlspecialchars($product["ram"], ENT_QUOTES, "UTF-8") ?> RAM</span>
                                            <?php endif; ?>
                                            <?php if (!empty($product["storage"])): ?>
                                                <span><?= htmlspecialchars($product["storage"], ENT_QUOTES, "UTF-8") ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="catalog-product-bottom">
                                            <div>
                                                <strong><?= number_format($price, 0, ",", ".") ?>₫</strong>
                                                <?php if ($price < $originalPrice): ?>
                                                    <del><?= number_format($originalPrice, 0, ",", ".") ?>₫</del>
                                                <?php endif; ?>
                                            </div>
                                            <span class="catalog-stock <?= $stock > 0 ? "is-available" : "is-unavailable" ?>">
                                                <?= $stock > 0 ? "Còn hàng" : "Tạm hết" ?>
                                            </span>
                                        </div>
                                        <a
                                            class="catalog-product-link"
                                            href="<?= BASE_URL ?>index.php?controller=product&action=detail&id=<?= (int) $product["id"] ?>"
                                        >
                                            Xem chi tiết <span aria-hidden="true">↗</span>
                                        </a>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="catalog-empty">
                            <span aria-hidden="true">⌕</span>
                            <h3>Chưa tìm thấy sản phẩm phù hợp</h3>
                            <p>Thử đổi từ khóa hoặc xóa bộ lọc để xem thêm lựa chọn.</p>
                            <a href="<?= BASE_URL ?>index.php?controller=product&action=index">Xem tất cả laptop</a>
                        </div>
                    <?php endif; ?>

                    <?php if ($totalPages > 1): ?>
                        <nav class="catalog-pagination" aria-label="Phân trang sản phẩm">
                            <a
                                class="catalog-page-control<?= $currentPage <= 1 ? " is-disabled" : "" ?>"
                                href="<?= $currentPage > 1
                                    ? BASE_URL . "index.php?" . htmlspecialchars(
                                        http_build_query(array_merge($paginationParams, ["page" => $currentPage - 1])),
                                        ENT_QUOTES,
                                        "UTF-8"
                                    )
                                    : "#" ?>"
                                <?= $currentPage <= 1 ? 'aria-disabled="true" tabindex="-1"' : "" ?>
                            >
                                <span aria-hidden="true">←</span> Trước
                            </a>
                            <div class="catalog-page-numbers">
                                <?php if ($pageWindowStart > 1): ?>
                                    <a href="<?= BASE_URL ?>index.php?<?= htmlspecialchars(http_build_query(array_merge($paginationParams, ["page" => 1])), ENT_QUOTES, "UTF-8") ?>">1</a>
                                    <?php if ($pageWindowStart > 2): ?>
                                        <span class="catalog-page-ellipsis" aria-hidden="true">…</span>
                                    <?php endif; ?>
                                <?php endif; ?>

                                <?php for ($page = $pageWindowStart; $page <= $pageWindowEnd; $page++): ?>
                                    <?php if ($page === $currentPage): ?>
                                        <span class="is-current" aria-current="page"><?= $page ?></span>
                                    <?php else: ?>
                                        <a href="<?= BASE_URL ?>index.php?<?= htmlspecialchars(http_build_query(array_merge($paginationParams, ["page" => $page])), ENT_QUOTES, "UTF-8") ?>"><?= $page ?></a>
                                    <?php endif; ?>
                                <?php endfor; ?>

                                <?php if ($pageWindowEnd < $totalPages): ?>
                                    <?php if ($pageWindowEnd < $totalPages - 1): ?>
                                        <span class="catalog-page-ellipsis" aria-hidden="true">…</span>
                                    <?php endif; ?>
                                    <a href="<?= BASE_URL ?>index.php?<?= htmlspecialchars(http_build_query(array_merge($paginationParams, ["page" => $totalPages])), ENT_QUOTES, "UTF-8") ?>"><?= $totalPages ?></a>
                                <?php endif; ?>
                            </div>
                            <a
                                class="catalog-page-control<?= $currentPage >= $totalPages ? " is-disabled" : "" ?>"
                                href="<?= $currentPage < $totalPages
                                    ? BASE_URL . "index.php?" . htmlspecialchars(
                                        http_build_query(array_merge($paginationParams, ["page" => $currentPage + 1])),
                                        ENT_QUOTES,
                                        "UTF-8"
                                    )
                                    : "#" ?>"
                                <?= $currentPage >= $totalPages ? 'aria-disabled="true" tabindex="-1"' : "" ?>
                            >
                                Sau <span aria-hidden="true">→</span>
                            </a>
                        </nav>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
</main>

<?php require_once __DIR__ . "/../layouts/footer.php"; ?>
