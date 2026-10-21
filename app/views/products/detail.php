<?php

require_once __DIR__ . "/../../helpers/ProductImage.php";

$productName = (string) ($product["name"] ?? "");
$productPrice = (float) (
    !empty($product["sale_price"])
        ? $product["sale_price"]
        : $product["price"]
);
$originalPrice = (float) ($product["price"] ?? 0);
$stockQuantity = (int) ($product["quantity"] ?? 0);
$mainImageUrl = $mainImage !== "" ? productImageUrl($mainImage) : "";
$galleryImages = [];
if ($mainImageUrl !== "") {
    $galleryImages[] = $mainImageUrl;
}
foreach ($images as $image) {
    $imageUrl = productImageUrl($image["image"]);
    if ($imageUrl !== "" && !in_array($imageUrl, $galleryImages, true)) {
        $galleryImages[] = $imageUrl;
    }
}
$specifications = [
    "Bộ vi xử lý" => $product["cpu"] ?? "",
    "Bộ nhớ RAM" => $product["ram"] ?? "",
    "Ổ cứng" => $product["storage"] ?? "",
    "Màn hình" => $product["screen"] ?? "",
    "Card đồ họa" => $product["gpu"] ?? "",
    "Hệ điều hành" => $product["operating_system"] ?? "",
    "Trọng lượng" => $product["weight"] ?? "",
    "Màu sắc" => $product["color"] ?? "",
];

require_once __DIR__ . "/../layouts/header.php";
?>

<main class="product-detail-page">
    <div class="product-detail-container">

        <section class="detail-product-layout">
            <div class="detail-gallery">
                <div class="detail-image-stage">
                    <?php if ($mainImageUrl !== ""): ?>
                        <img
                            id="product-main-image"
                            src="<?= htmlspecialchars($mainImageUrl, ENT_QUOTES, "UTF-8") ?>"
                            alt="<?= htmlspecialchars($productName, ENT_QUOTES, "UTF-8") ?>"
                            data-detail-main-image
                        >
                    <?php else: ?>
                        <span class="detail-image-placeholder" aria-hidden="true">LAPTOP</span>
                    <?php endif; ?>
                    <?php if ($productPrice < $originalPrice): ?>
                        <span class="detail-image-badge">ƯU ĐÃI</span>
                    <?php endif; ?>
                    <span class="detail-image-caption">Hình ảnh sản phẩm</span>
                    <?php if (count($galleryImages) > 1): ?>
                        <button
                            class="detail-image-nav detail-image-prev"
                            type="button"
                            aria-label="Xem ảnh trước"
                            data-detail-image-step="-1"
                        >‹</button>
                        <button
                            class="detail-image-nav detail-image-next"
                            type="button"
                            aria-label="Xem ảnh tiếp theo"
                            data-detail-image-step="1"
                        >›</button>
                    <?php endif; ?>
                </div>

                <?php if (count($galleryImages) > 1): ?>
                    <div class="detail-gallery-strip">
                        <?php if (count($galleryImages) > 4): ?>
                            <button
                                class="detail-strip-nav"
                                type="button"
                                aria-label="Cuộn ảnh sang trái"
                                data-detail-strip-step="-1"
                            >‹</button>
                        <?php endif; ?>
                        <div class="detail-gallery-thumbnails" aria-label="Ảnh sản phẩm" data-detail-thumbnail-track>
                            <?php foreach ($galleryImages as $thumbnailIndex => $imageUrl): ?>
                                <button
                                    type="button"
                                    class="detail-thumbnail<?= $thumbnailIndex === 0 ? " is-active" : "" ?>"
                                    data-detail-index="<?= $thumbnailIndex ?>"
                                    data-detail-image="<?= htmlspecialchars($imageUrl, ENT_QUOTES, "UTF-8") ?>"
                                    aria-label="Xem ảnh <?= $thumbnailIndex + 1 ?> của <?= htmlspecialchars($productName, ENT_QUOTES, "UTF-8") ?>"
                                    aria-pressed="<?= $thumbnailIndex === 0 ? "true" : "false" ?>"
                                >
                                    <img
                                        src="<?= htmlspecialchars($imageUrl, ENT_QUOTES, "UTF-8") ?>"
                                        alt="<?= htmlspecialchars($productName, ENT_QUOTES, "UTF-8") ?>"
                                        loading="lazy"
                                    >
                                </button>
                            <?php endforeach; ?>
                        </div>
                        <?php if (count($galleryImages) > 4): ?>
                            <button
                                class="detail-strip-nav"
                                type="button"
                                aria-label="Cuộn ảnh sang phải"
                                data-detail-strip-step="1"
                            >›</button>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="detail-summary">
                <div class="detail-tags">
                    <?php if (!empty($product["brand_name"])): ?>
                        <span><?= htmlspecialchars($product["brand_name"], ENT_QUOTES, "UTF-8") ?></span>
                    <?php endif; ?>
                    <?php if (!empty($product["category_name"])): ?>
                        <span><?= htmlspecialchars($product["category_name"], ENT_QUOTES, "UTF-8") ?></span>
                    <?php endif; ?>
                </div>

                <span class="detail-eyebrow">LAPTOP CHÍNH HÃNG · BẢO HÀNH TẬN TÂM</span>
                <h1><?= htmlspecialchars($productName, ENT_QUOTES, "UTF-8") ?></h1>

                <div class="detail-price-block">
                    <?php if ($productPrice < $originalPrice): ?>
                        <del><?= number_format($originalPrice, 0, ",", ".") ?>₫</del>
                    <?php endif; ?>
                    <strong><?= number_format($productPrice, 0, ",", ".") ?>₫</strong>
                    <?php if ($productPrice < $originalPrice && $originalPrice > 0): ?>
                        <span class="detail-discount">
                            -<?= (int) round((1 - $productPrice / $originalPrice) * 100) ?>%
                        </span>
                    <?php endif; ?>
                </div>

                <div class="detail-quick-specs">
                    <?php foreach (["CPU" => "cpu", "RAM" => "ram", "SSD" => "storage"] as $label => $field): ?>
                        <?php if (!empty($product[$field])): ?>
                            <div>
                                <span><?= $label ?></span>
                                <strong><?= htmlspecialchars($product[$field], ENT_QUOTES, "UTF-8") ?></strong>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>

                <div class="detail-stock <?= $stockQuantity > 0 ? "is-available" : "is-unavailable" ?>">
                    <span aria-hidden="true"></span>
                    <?php if ($stockQuantity > 0): ?>
                        Còn hàng <span>·</span> <?= number_format($stockQuantity) ?> sản phẩm sẵn sàng
                    <?php else: ?>
                        Sản phẩm hiện đã hết hàng
                    <?php endif; ?>
                </div>

                <?php if ($stockQuantity > 0): ?>
                    <form
                        class="detail-cart-form"
                        action="<?= BASE_URL ?>index.php?controller=cart&action=add"
                        method="POST"
                    >
                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= htmlspecialchars($_SESSION["csrf_token"] ?? "", ENT_QUOTES, "UTF-8") ?>"
                        >
                        <input type="hidden" name="product_id" value="<?= (int) $product["id"] ?>">

                        <div class="detail-purchase-row">
                            <div class="detail-quantity">
                                <label for="quantity">Số lượng</label>
                                <div class="detail-quantity-control">
                                    <button type="button" data-quantity-step="-1" aria-label="Giảm số lượng">−</button>
                                    <input
                                        type="number"
                                        name="quantity"
                                        id="quantity"
                                        value="1"
                                        min="1"
                                        max="<?= $stockQuantity ?>"
                                        inputmode="numeric"
                                        data-detail-quantity
                                    >
                                    <button type="button" data-quantity-step="1" aria-label="Tăng số lượng">+</button>
                                </div>
                            </div>
                            <button class="detail-add-cart" type="submit">
                                <span aria-hidden="true">＋</span> Thêm vào giỏ hàng
                            </button>
                        </div>
                    </form>
                <?php else: ?>
                    <button class="detail-add-cart is-disabled" type="button" disabled>
                        Hiện đang hết hàng
                    </button>
                <?php endif; ?>

                <div class="detail-trust-list">
                    <div><span aria-hidden="true">◇</span> Sản phẩm chính hãng</div>
                    <div><span aria-hidden="true">♡</span> Hỗ trợ tận tâm sau mua</div>
                    <div><span aria-hidden="true">⌖</span> Giao hàng toàn quốc</div>
                </div>
            </div>
        </section>

        <section class="detail-spec-section">
            <div class="detail-section-heading">
                <div>
                    <span class="detail-section-kicker">THÔNG TIN CHI TIẾT</span>
                    <h2>Thông số kỹ thuật</h2>
                </div>
                <span class="detail-section-mark" aria-hidden="true">LS / SPECS</span>
            </div>
            <div class="detail-spec-grid">
                <?php foreach ($specifications as $label => $value): ?>
                    <div class="detail-spec-item">
                        <span><?= htmlspecialchars($label, ENT_QUOTES, "UTF-8") ?></span>
                        <strong>
                            <?= htmlspecialchars(trim((string) $value) !== "" ? $value : "Đang cập nhật", ENT_QUOTES, "UTF-8") ?>
                        </strong>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="detail-description-section">
            <div class="detail-section-heading">
                <div>
                    <span class="detail-section-kicker">TÌM HIỂU THÊM</span>
                    <h2>Mô tả sản phẩm</h2>
                </div>
            </div>
            <div class="detail-description">
                <?php if (!empty($product["description"])): ?>
                    <?= nl2br(htmlspecialchars($product["description"], ENT_QUOTES, "UTF-8")) ?>
                <?php else: ?>
                    <p class="detail-muted">Thông tin mô tả sản phẩm đang được cập nhật.</p>
                <?php endif; ?>
            </div>
        </section>
    </div>
</main>

<?php require_once __DIR__ . "/../layouts/footer.php"; ?>
