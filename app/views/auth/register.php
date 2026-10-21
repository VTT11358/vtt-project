
<?php

$pageTitle = "Tạo tài khoản | Laptop Store";
require __DIR__ . "/../layouts/header.php";

?>

<main class="auth-page auth-register-page">
    <div class="auth-shell">
        <aside class="auth-showcase">
            <a class="auth-showcase-brand" href="<?= BASE_URL ?>" aria-label="Laptop Store - Trang chủ">
                <span class="auth-brand-mark" aria-hidden="true">L</span>
                <span>LAPTOP<span>STORE</span></span>
            </a>
            <div class="auth-showcase-copy">
                <span class="auth-kicker">BẮT ĐẦU HÀNH TRÌNH MỚI</span>
                <h1>Lựa chọn phù hợp, trải nghiệm khác biệt.</h1>
                <p>Tạo tài khoản để lưu thông tin nhận hàng, quản lý đơn mua và dễ dàng tìm lại sản phẩm bạn quan tâm.</p>
            </div>
            <div class="auth-device-art" aria-hidden="true">
                <div class="auth-device-glow"></div>
                <div class="auth-device-screen">
                    <span class="auth-device-logo">L</span>
                    <span class="auth-device-line auth-device-line-long"></span>
                    <span class="auth-device-line"></span>
                    <span class="auth-device-chip">YOUR NEXT LAPTOP</span>
                </div>
                <div class="auth-device-base"></div>
            </div>
            <div class="auth-showcase-foot">
                <span>02</span>
                <span>Tài khoản của bạn · Trải nghiệm của bạn</span>
            </div>
        </aside>

        <section class="auth-form-panel" aria-labelledby="auth-title">
            <div class="auth-form-wrap">
                <a class="auth-mobile-brand" href="<?= BASE_URL ?>">
                    <span class="auth-brand-mark" aria-hidden="true">L</span>
                    LAPTOP STORE
                </a>
                <span class="auth-kicker">THÀNH VIÊN LAPTOP STORE</span>
                <h2 id="auth-title">Tạo tài khoản</h2>
                <p class="auth-intro">Điền thông tin bên dưới. Bạn sẽ nhận được liên kết xác nhận qua email.</p>

                <?php if (!empty($error)): ?>
                    <div class="auth-notice auth-notice-error" role="alert">
                        <span aria-hidden="true">!</span>
                        <?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?>
                    </div>
                <?php endif; ?>

                <form class="auth-form auth-register-form" method="POST" action="<?= BASE_URL ?>index.php?controller=auth&action=register">
                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= htmlspecialchars($_SESSION["csrf_token"] ?? "", ENT_QUOTES, "UTF-8") ?>"
                    >

                    <div class="auth-field">
                        <label for="auth-name">Họ và tên <span>*</span></label>
                        <input
                            id="auth-name"
                            type="text"
                            name="name"
                            required
                            maxlength="100"
                            autocomplete="name"
                            placeholder="Nguyễn Văn A"
                            value="<?= htmlspecialchars($old["name"] ?? "", ENT_QUOTES, "UTF-8") ?>"
                        >
                    </div>

                    <div class="auth-field">
                        <label for="auth-email">Email <span>*</span></label>
                        <input
                            id="auth-email"
                            type="email"
                            name="email"
                            required
                            maxlength="150"
                            autocomplete="email"
                            placeholder="ban@example.com"
                            value="<?= htmlspecialchars($old["email"] ?? "", ENT_QUOTES, "UTF-8") ?>"
                        >
                    </div>

                    <div class="auth-field">
                        <label for="auth-phone">Số điện thoại</label>
                        <input
                            id="auth-phone"
                            type="tel"
                            name="phone"
                            autocomplete="tel"
                            placeholder="Nhập số điện thoại"
                            value="<?= htmlspecialchars($old["phone"] ?? "", ENT_QUOTES, "UTF-8") ?>"
                        >
                    </div>

                    <div class="auth-field">
                        <label for="auth-address">Địa chỉ</label>
                        <input
                            id="auth-address"
                            type="text"
                            name="address"
                            autocomplete="street-address"
                            placeholder="Địa chỉ nhận hàng"
                            value="<?= htmlspecialchars($old["address"] ?? "", ENT_QUOTES, "UTF-8") ?>"
                        >
                    </div>

                    <div class="auth-field">
                        <label for="auth-password">Mật khẩu <span>*</span></label>
                        <input
                            id="auth-password"
                            type="password"
                            name="password"
                            required
                            minlength="8"
                            autocomplete="new-password"
                            placeholder="Ít nhất 8 ký tự"
                        >
                    </div>

                    <div class="auth-field">
                        <label for="auth-confirm-password">Xác nhận mật khẩu <span>*</span></label>
                        <input
                            id="auth-confirm-password"
                            type="password"
                            name="confirm_password"
                            required
                            minlength="8"
                            autocomplete="new-password"
                            placeholder="Nhập lại mật khẩu"
                        >
                    </div>

                    <button class="auth-submit" type="submit">
                        Tạo tài khoản <span aria-hidden="true">→</span>
                    </button>
                </form>

                <p class="auth-switch">
                    Đã có tài khoản?
                    <a href="<?= BASE_URL ?>index.php?controller=auth&action=login">Đăng nhập</a>
                </p>
                <a class="auth-home-link" href="<?= BASE_URL ?>">
                    <span aria-hidden="true">←</span> Trở về trang chủ
                </a>
            </div>
            <span class="auth-copyright">© <?= date("Y") ?> Laptop Store</span>
        </section>
    </div>
</main>

<?php require __DIR__ . "/../layouts/footer.php"; ?>
