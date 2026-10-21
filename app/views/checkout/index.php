<?php

$pageTitle = "Thanh toán | Laptop Store";
$old = $old ?? [];
$items = $items ?? [];
$total = (float) ($total ?? 0);
$error = $error ?? "";
$paymentMethod = $old["payment_method"] ?? "COD";

require __DIR__ . "/../layouts/header.php";

?>

<main class="checkout-page">
    <div class="checkout-container">
        <header class="checkout-heading">
            <div>
                <span class="checkout-kicker">HOÀN TẤT LỰA CHỌN CỦA BẠN</span>
                <h1>Thanh toán</h1>
                <p>Điền thông tin giao hàng và chọn cách thanh toán phù hợp.</p>
            </div>
            <a class="checkout-back-link" href="<?= BASE_URL ?>index.php?controller=cart&action=index">
                <span aria-hidden="true">←</span> Quay lại giỏ hàng
            </a>
        </header>

        <div class="checkout-progress" aria-label="Tiến trình đặt hàng">
            <span class="checkout-progress-step is-complete"><i>✓</i> Giỏ hàng</span>
            <span class="checkout-progress-line is-active" aria-hidden="true"></span>
            <span class="checkout-progress-step is-current"><i>2</i> Thanh toán</span>
            <span class="checkout-progress-line" aria-hidden="true"></span>
            <span class="checkout-progress-step"><i>3</i> Hoàn tất</span>
        </div>

        <?php if ($error !== ""): ?>
            <div class="checkout-alert" role="alert">
                <span aria-hidden="true">!</span>
                <?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?>
            </div>
        <?php endif; ?>

        <form
            class="checkout-layout"
            method="POST"
            action="<?= BASE_URL ?>index.php?controller=checkout&action=place"
        >
            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars($_SESSION["csrf_token"] ?? "", ENT_QUOTES, "UTF-8") ?>"
            >
            <?php if (!empty($selectionProvided)): ?>
                <input type="hidden" name="selection" value="1">
                <?php foreach (($selectedProductIds ?? []) as $selectedProductId): ?>
                    <input type="hidden" name="selected_product_ids[]" value="<?= (int) $selectedProductId ?>">
                <?php endforeach; ?>
            <?php endif; ?>

            <div class="checkout-main">
                <section class="checkout-card" aria-labelledby="checkout-shipping-heading">
                    <div class="checkout-card-heading">
                        <span class="checkout-heading-icon" aria-hidden="true">01</span>
                        <div>
                            <span class="checkout-kicker">GIAO HÀNG</span>
                            <h2 id="checkout-shipping-heading">Thông tin nhận hàng</h2>
                        </div>
                    </div>

                    <div class="checkout-fields">
                        <div class="checkout-field checkout-field-wide">
                            <label for="customer_name">Họ và tên <span>*</span></label>
                            <input
                                type="text"
                                id="customer_name"
                                name="customer_name"
                                autocomplete="name"
                                required
                                maxlength="100"
                                value="<?= htmlspecialchars($old["customer_name"] ?? "", ENT_QUOTES, "UTF-8") ?>"
                            >
                        </div>

                        <div class="checkout-field">
                            <label for="phone">Số điện thoại <span>*</span></label>
                            <input
                                type="tel"
                                id="phone"
                                name="phone"
                                autocomplete="tel"
                                required
                                maxlength="20"
                                value="<?= htmlspecialchars($old["phone"] ?? "", ENT_QUOTES, "UTF-8") ?>"
                            >
                        </div>

                        <div class="checkout-field">
                            <label for="email">Email</label>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                autocomplete="email"
                                maxlength="150"
                                value="<?= htmlspecialchars($old["email"] ?? "", ENT_QUOTES, "UTF-8") ?>"
                            >
                        </div>

                        <div class="checkout-field checkout-field-wide">
                            <label for="address">Địa chỉ giao hàng <span>*</span></label>
                            <input
                                type="text"
                                id="address"
                                name="address"
                                autocomplete="street-address"
                                required
                                maxlength="255"
                                value="<?= htmlspecialchars($old["address"] ?? "", ENT_QUOTES, "UTF-8") ?>"
                            >
                        </div>

                        <div class="checkout-field checkout-field-wide">
                            <label for="note">Ghi chú <span class="checkout-optional">(không bắt buộc)</span></label>
                            <textarea
                                id="note"
                                name="note"
                                rows="3"
                                placeholder="Lưu ý thêm cho đơn hàng của bạn..."
                            ><?= htmlspecialchars($old["note"] ?? "", ENT_QUOTES, "UTF-8") ?></textarea>
                        </div>
                    </div>
                </section>

                <section class="checkout-card" aria-labelledby="checkout-payment-heading">
                    <div class="checkout-card-heading">
                        <span class="checkout-heading-icon" aria-hidden="true">02</span>
                        <div>
                            <span class="checkout-kicker">THANH TOÁN</span>
                            <h2 id="checkout-payment-heading">Chọn phương thức</h2>
                        </div>
                    </div>

                    <div class="checkout-payment-options">
                        <label class="checkout-payment-option <?= $paymentMethod === "COD" ? "is-selected" : "" ?>">
                            <input
                                type="radio"
                                name="payment_method"
                                value="COD"
                                <?= $paymentMethod === "COD" ? "checked" : "" ?>
                            >
                            <span class="checkout-payment-icon" aria-hidden="true">₫</span>
                            <span class="checkout-payment-copy">
                                <strong>Thanh toán khi nhận hàng</strong>
                                <small>Thanh toán trực tiếp với nhân viên giao hàng.</small>
                            </span>
                            <span class="checkout-radio-mark" aria-hidden="true"></span>
                        </label>

                        <label class="checkout-payment-option <?= $paymentMethod === "vnpay" ? "is-selected" : "" ?>">
                            <input
                                type="radio"
                                name="payment_method"
                                value="vnpay"
                                <?= $paymentMethod === "vnpay" ? "checked" : "" ?>
                            >
                            <span class="checkout-payment-icon checkout-payment-icon-vnpay" aria-hidden="true">V</span>
                            <span class="checkout-payment-copy">
                                <strong>VNPay</strong>
                                <small>Thanh toán trực tuyến an toàn. Đơn chỉ tạo sau khi thanh toán thành công.</small>
                            </span>
                            <span class="checkout-radio-mark" aria-hidden="true"></span>
                        </label>
                    </div>

                    <div class="checkout-test-note">
                        <span aria-hidden="true">i</span>
                        <div>
                            <strong>Thông tin thẻ thử nghiệm VNPay (Sandbox)</strong>
                            <p>NCB · 9704198526191432198 · NGUYEN VAN A · 07/15 · OTP 123456</p>
                        </div>
                    </div>
                </section>
            </div>

            <aside class="checkout-summary" aria-labelledby="checkout-summary-heading">
                <span class="checkout-kicker">ĐƠN HÀNG CỦA BẠN</span>
                <h2 id="checkout-summary-heading">Tóm tắt đơn hàng</h2>

                <div class="checkout-order-items">
                    <?php foreach ($items as $item): ?>
                        <?php
                        $product = $item["product"] ?? [];
                        $quantity = (int) ($item["quantity"] ?? 0);
                        $itemTotal = (float) ($item["price"] ?? 0) * $quantity;
                        ?>
                        <div class="checkout-order-item">
                            <div>
                                <strong><?= htmlspecialchars($product["name"] ?? "", ENT_QUOTES, "UTF-8") ?></strong>
                                <span>Số lượng: <?= $quantity ?></span>
                            </div>
                            <strong><?= number_format($itemTotal, 0, ",", ".") ?> đ</strong>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="checkout-summary-row">
                    <span>Phí vận chuyển</span>
                    <strong>Tính khi xác nhận</strong>
                </div>
                <div class="checkout-summary-total">
                    <span>Tổng cộng</span>
                    <strong><?= number_format($total, 0, ",", ".") ?> đ</strong>
                </div>
                <p class="checkout-summary-note">Thông tin giao hàng và tổng tiền sẽ được xác nhận trước khi hoàn tất.</p>

                <button type="submit" class="checkout-submit" data-checkout-submit>
                    <span data-checkout-submit-label>
                        <?= $paymentMethod === "vnpay" ? "Thanh toán qua VNPay" : "Đặt hàng" ?>
                    </span>
                    <span aria-hidden="true">→</span>
                </button>
                <div class="checkout-secure-note">
                    <span aria-hidden="true">✓</span>
                    Thông tin của bạn được bảo mật
                </div>
            </aside>
        </form>
    </div>
</main>

<?php require __DIR__ . "/../layouts/footer.php"; ?>
