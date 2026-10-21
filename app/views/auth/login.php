
<?php

$pageTitle = "Đăng nhập | Laptop Store";
require __DIR__ . "/../layouts/header.php";

?>

<main class="auth-page">
    <div class="auth-shell">
        <aside class="auth-showcase">
            <a class="auth-showcase-brand" href="<?= BASE_URL ?>" aria-label="Laptop Store - Trang chủ">
                <span class="auth-brand-mark" aria-hidden="true">L</span>
                <span>LAPTOP<span>STORE</span></span>
            </a>
            <div class="auth-showcase-copy">
                <span class="auth-kicker">CHÀO MỪNG BẠN TRỞ LẠI</span>
                <h1>Không gian mua sắm công nghệ của bạn.</h1>
                <p>Đăng nhập để theo dõi đơn hàng, lưu sản phẩm yêu thích và tiếp tục hành trình chọn chiếc laptop phù hợp.</p>
            </div>
            <div class="auth-device-art" aria-hidden="true">
                <div class="auth-device-glow"></div>
                <div class="auth-device-screen">
                    <span class="auth-device-logo">L</span>
                    <span class="auth-device-line auth-device-line-long"></span>
                    <span class="auth-device-line"></span>
                    <span class="auth-device-chip">LAPTOP STORE</span>
                </div>
                <div class="auth-device-base"></div>
            </div>
            <div class="auth-showcase-foot">
                <span>01</span>
                <span>Mua sắm dễ dàng · An tâm từng lựa chọn</span>
            </div>
        </aside>

        <section class="auth-form-panel" aria-labelledby="auth-title">
            <div class="auth-form-wrap">
                <a class="auth-mobile-brand" href="<?= BASE_URL ?>">
                    <span class="auth-brand-mark" aria-hidden="true">L</span>
                    LAPTOP STORE
                </a>
                <span class="auth-kicker">TÀI KHOẢN CỦA BẠN</span>
                <h2 id="auth-title">Đăng nhập</h2>
                <p class="auth-intro">Nhập thông tin để tiếp tục đến tài khoản của bạn.</p>

                <?php if (!empty($success)): ?>
                    <div class="auth-notice auth-notice-success" role="status">
                        <span aria-hidden="true">✓</span>
                        <?= htmlspecialchars($success, ENT_QUOTES, "UTF-8") ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($error)): ?>
                    <div class="auth-notice auth-notice-error" role="alert">
                        <span aria-hidden="true">!</span>
                        <?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?>
                    </div>
                <?php endif; ?>

                <form class="auth-form" method="POST" action="<?= BASE_URL ?>index.php?controller=auth&action=login">
                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= htmlspecialchars($_SESSION["csrf_token"] ?? "", ENT_QUOTES, "UTF-8") ?>"
                    >

                    <div class="auth-field">
                        <label for="auth-email">Email</label>
                        <input
                            id="auth-email"
                            type="email"
                            name="email"
                            required
                            maxlength="150"
                            autocomplete="email"
                            placeholder="ban@example.com"
                            value="<?= htmlspecialchars($email ?? "", ENT_QUOTES, "UTF-8") ?>"
                        >
                    </div>

                    <div class="auth-field">
                        <label for="auth-password">Mật khẩu</label>
                        <input
                            id="auth-password"
                            type="password"
                            name="password"
                            required
                            autocomplete="current-password"
                            placeholder="Nhập mật khẩu của bạn"
                        >
                    </div>

                    <a class="auth-forgot-link" href="<?= BASE_URL ?>index.php?controller=auth&action=forgotPassword">
                        Quên mật khẩu?
                    </a>

                    <button class="auth-submit" type="submit">
                        Đăng nhập <span aria-hidden="true">→</span>
                    </button>
                </form>

                <div class="auth-divider"><span>HOẶC</span></div>
                <a class="auth-google-button" href="<?= BASE_URL ?>index.php?controller=auth&action=googleLogin">
                    <span class="auth-google-mark" aria-hidden="true">G</span>
                    Tiếp tục với Google
                </a>

                <?php if (!empty($showResendVerification)): ?>
                    <form
                        class="auth-resend-form"
                        method="POST"
                        action="<?= BASE_URL ?>index.php?controller=auth&action=resendVerification"
                    >
                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= htmlspecialchars($_SESSION["csrf_token"] ?? "", ENT_QUOTES, "UTF-8") ?>"
                        >
                        <input
                            type="hidden"
                            name="email"
                            value="<?= htmlspecialchars($email ?? "", ENT_QUOTES, "UTF-8") ?>"
                        >
                        <p>Email của bạn chưa được xác nhận.</p>
                        <button type="submit">Gửi lại liên kết xác nhận</button>
                    </form>
                <?php endif; ?>

                <p class="auth-switch">
                    Chưa có tài khoản?
                    <a href="<?= BASE_URL ?>index.php?controller=auth&action=register">Tạo tài khoản</a>
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
