<?php

require_once __DIR__ . "/../../helpers/ProductImage.php";

$pageTitle = "Giỏ hàng | Laptop Store";
$selectableItems = array_values(array_filter(
    $items,
    static fn (array $item): bool =>
        $item["product"] !== null && (int) $item["product"]["quantity"] > 0
));
$selectableCount = count($selectableItems);
$selectableTotal = array_reduce(
    $selectableItems,
    static fn (float $sum, array $item): float =>
        $sum + (float) $item["price"] * (int) $item["quantity"],
    0.0
);
$formatPrice = static fn (float $price): string => number_format($price, 0, ",", ".") . " đ";

require_once __DIR__ . "/../layouts/header.php";

?>

<main class="cart-page">
    <div class="cart-container">
        <header class="cart-heading">
            <div>
                <span class="cart-kicker">SẮP HOÀN TẤT ĐƠN HÀNG</span>
                <h1>Giỏ hàng của bạn</h1>
                <p>Kiểm tra lại lựa chọn trước khi đến bước thanh toán.</p>
            </div>
            <a class="cart-continue-link" href="<?= BASE_URL ?>index.php?controller=product&action=index">
                <span aria-hidden="true">←</span> Tiếp tục mua sắm
            </a>
        </header>

        <?php if ($success !== ""): ?>
            <div class="cart-alert cart-alert-success" role="status">
                <span aria-hidden="true">✓</span>
                <?= htmlspecialchars($success, ENT_QUOTES, "UTF-8") ?>
            </div>
        <?php endif; ?>

        <?php if ($error !== ""): ?>
            <div class="cart-alert cart-alert-error" role="alert">
                <span aria-hidden="true">!</span>
                <?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?>
            </div>
        <?php endif; ?>

        <?php if (empty($items)): ?>
            <section class="cart-empty">
                <div class="cart-empty-icon" aria-hidden="true">🛒</div>
                <span class="cart-kicker">CHƯA CÓ SẢN PHẨM</span>
                <h2>Giỏ hàng đang chờ bạn chọn</h2>
                <p>Khám phá bộ sưu tập laptop và tìm chiếc máy phù hợp với nhu cầu của bạn.</p>
                <a class="cart-primary-button" href="<?= BASE_URL ?>index.php?controller=product&action=index">
                    Khám phá sản phẩm <span aria-hidden="true">↗</span>
                </a>
            </section>
        <?php else: ?>
            <div class="cart-layout">
                <section class="cart-items" aria-labelledby="cart-items-heading">
                    <div class="cart-items-heading">
                        <div>
                            <span class="cart-kicker">LỰA CHỌN CỦA BẠN</span>
                            <h2 id="cart-items-heading">Sản phẩm trong giỏ</h2>
                        </div>
                        <label class="cart-select-all">
                            <input
                                type="checkbox"
                                data-cart-select-all
                                <?= $selectableCount > 0 ? "checked" : "disabled" ?>
                            >
                            <span>Chọn tất cả</span>
                        </label>
                    </div>

                    <?php foreach ($items as $item): ?>
                        <?php
                        $product = $item["product"];
                        $price = $product ? (float) $item["price"] : 0;
                        $productName = $product["name"] ?? "Sản phẩm không còn khả dụng";
                        $availableQuantity = $product ? (int) $product["quantity"] : 0;
                        $isSelectable = $product && $availableQuantity > 0;
                        $lineTotal = $price * (int) $item["quantity"];
                        ?>
                        <article
                            class="cart-item"
                            data-cart-item
                            data-line-total="<?= $lineTotal ?>"
                            data-line-quantity="<?= (int) $item["quantity"] ?>"
                        >
                            <div class="cart-item-visual">
                                <?php if ($product): ?>
                                    <label class="cart-item-select">
                                        <input
                                            type="checkbox"
                                            name="selected_product_ids[]"
                                            value="<?= (int) $item["product_id"] ?>"
                                            form="cart-checkout-form"
                                            data-cart-select
                                            <?= $isSelectable ? "checked" : "disabled" ?>
                                            aria-label="Chọn <?= htmlspecialchars($productName, ENT_QUOTES, "UTF-8") ?> để thanh toán"
                                        >
                                    </label>
                                <?php endif; ?>
                                <a
                                    class="cart-item-image"
                                    href="<?= $product
                                        ? BASE_URL . "index.php?controller=product&action=detail&id=" . (int) $item["product_id"]
                                        : "#" ?>"
                                    aria-label="<?= htmlspecialchars($productName, ENT_QUOTES, "UTF-8") ?>"
                                >
                                    <?php if ($product && !empty($product["image"])): ?>
                                        <img
                                            src="<?= htmlspecialchars(productImageUrl($product["image"]), ENT_QUOTES, "UTF-8") ?>"
                                            alt="<?= htmlspecialchars($productName, ENT_QUOTES, "UTF-8") ?>"
                                            loading="lazy"
                                        >
                                    <?php else: ?>
                                        <span aria-hidden="true">LS</span>
                                    <?php endif; ?>
                                </a>
                            </div>

                            <div class="cart-item-details">
                                <div class="cart-item-copy">
                                    <span class="cart-item-label">
                                        <?= $product ? "LAPTOP STORE" : "KHÔNG KHẢ DỤNG" ?>
                                    </span>
                                    <h3>
                                        <?php if ($product): ?>
                                            <a href="<?= BASE_URL ?>index.php?controller=product&action=detail&id=<?= (int) $item["product_id"] ?>">
                                                <?= htmlspecialchars($productName, ENT_QUOTES, "UTF-8") ?>
                                            </a>
                                        <?php else: ?>
                                            <?= htmlspecialchars($productName, ENT_QUOTES, "UTF-8") ?>
                                        <?php endif; ?>
                                    </h3>
                                    <?php if ($product && $availableQuantity > 0): ?>
                                        <span class="cart-stock"><i aria-hidden="true"></i>Còn <?= $availableQuantity ?> sản phẩm</span>
                                    <?php elseif ($product): ?>
                                        <span class="cart-stock cart-stock-unavailable">Hiện đã hết hàng</span>
                                    <?php else: ?>
                                        <span class="cart-stock cart-stock-unavailable">Vui lòng xóa sản phẩm này khỏi giỏ</span>
                                    <?php endif; ?>
                                </div>

                                <div class="cart-item-price">
                                    <span>Đơn giá</span>
                                    <?= $product ? $formatPrice($price) : "—" ?>
                                </div>

                                <div class="cart-item-quantity">
                                    <?php if ($product && $availableQuantity > 0): ?>
                                        <form
                                            action="<?= BASE_URL ?>index.php?controller=cart&action=update"
                                            method="POST"
                                            class="cart-quantity-form"
                                        >
                                            <input
                                                type="hidden"
                                                name="csrf_token"
                                                value="<?= htmlspecialchars($_SESSION["csrf_token"] ?? "", ENT_QUOTES, "UTF-8") ?>"
                                            >
                                            <input type="hidden" name="product_id" value="<?= (int) $item["product_id"] ?>">
                                            <button
                                                type="submit"
                                                name="quantity"
                                                value="<?= max(1, (int) $item["quantity"] - 1) ?>"
                                                aria-label="Giảm số lượng <?= htmlspecialchars($productName, ENT_QUOTES, "UTF-8") ?>"
                                                <?= (int) $item["quantity"] <= 1 ? "disabled" : "" ?>
                                            >−</button>
                                            <output aria-live="polite"><?= (int) $item["quantity"] ?></output>
                                            <button
                                                type="submit"
                                                name="quantity"
                                                value="<?= min($availableQuantity, (int) $item["quantity"] + 1) ?>"
                                                aria-label="Tăng số lượng <?= htmlspecialchars($productName, ENT_QUOTES, "UTF-8") ?>"
                                                <?= (int) $item["quantity"] >= $availableQuantity ? "disabled" : "" ?>
                                            >+</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="cart-quantity-static">Số lượng: <?= (int) $item["quantity"] ?></span>
                                    <?php endif; ?>
                                </div>

                                <div class="cart-item-subtotal">
                                    <span>Thành tiền</span>
                                    <strong><?= $product ? $formatPrice($price * (int) $item["quantity"]) : "—" ?></strong>
                                </div>

                                <form
                                    action="<?= BASE_URL ?>index.php?controller=cart&action=remove"
                                    method="POST"
                                    class="cart-remove-form"
                                >
                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?= htmlspecialchars($_SESSION["csrf_token"] ?? "", ENT_QUOTES, "UTF-8") ?>"
                                    >
                                    <input type="hidden" name="product_id" value="<?= (int) $item["product_id"] ?>">
                                    <button type="submit" aria-label="Xóa <?= htmlspecialchars($productName, ENT_QUOTES, "UTF-8") ?> khỏi giỏ hàng">
                                        <span aria-hidden="true">×</span><span class="cart-remove-label">Xóa khỏi giỏ</span>
                                    </button>
                                </form>
                            </div>
                        </article>
                    <?php endforeach; ?>

                    <a class="cart-continue-link cart-continue-mobile" href="<?= BASE_URL ?>index.php?controller=product&action=index">
                        <span aria-hidden="true">←</span> Tiếp tục mua sắm
                    </a>
                </section>

                <aside class="cart-summary" aria-labelledby="cart-summary-heading">
                    <span class="cart-kicker">TÓM TẮT</span>
                    <h2 id="cart-summary-heading">Đơn hàng</h2>
                    <div class="cart-summary-line">
                        <span>Sản phẩm đã chọn</span>
                        <strong data-cart-selected-count><?= $selectableCount ?></strong>
                    </div>
                    <div class="cart-summary-line">
                        <span>Phí vận chuyển</span>
                        <strong class="cart-shipping-note">Tính khi đặt hàng</strong>
                    </div>
                    <div class="cart-summary-total">
                        <span>Tạm tính</span>
                        <strong data-cart-selected-total data-currency-suffix=" đ"><?= $formatPrice($selectableTotal) ?></strong>
                    </div>
                    <p class="cart-summary-note">
                        Tổng đơn hàng và thông tin giao hàng sẽ được xác nhận ở bước tiếp theo.
                    </p>
                    <form id="cart-checkout-form" method="GET" action="<?= BASE_URL ?>index.php">
                        <input type="hidden" name="controller" value="checkout">
                        <input type="hidden" name="action" value="index">
                        <input type="hidden" name="selection" value="1">
                        <button
                            class="cart-primary-button"
                            type="submit"
                            data-cart-checkout-button
                            <?= $selectableCount === 0 ? "disabled" : "" ?>
                        >
                            Thanh toán <span aria-hidden="true">→</span>
                        </button>
                    </form>
                    <div class="cart-trust-note">
                        <span aria-hidden="true">✓</span>
                        Thanh toán an toàn · Hỗ trợ tận tâm
                    </div>
                </aside>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php require_once __DIR__ . "/../layouts/footer.php"; ?>
