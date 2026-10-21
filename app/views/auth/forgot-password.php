<?php require __DIR__ . "/../layouts/header.php"; ?>

<main class="auth-page">
    <div class="auth-shell auth-compact-shell">
        <aside class="auth-showcase">
            <a class="auth-showcase-brand" href="<?= BASE_URL ?>" aria-label="Laptop Store - Trang chủ">
                <span class="auth-brand-mark" aria-hidden="true">L</span>
                <span>LAPTOP<span>STORE</span></span>
            </a>
            <div class="auth-showcase-copy">
                <span class="auth-kicker">BẢO MẬT TÀI KHOẢN</span>
                <h1>Lấy lại quyền truy cập an toàn.</h1>
                <p>Chúng tôi sẽ gửi một liên kết riêng tới email tài khoản để bạn tạo mật khẩu mới.</p>
            </div>
            <div class="auth-device-art" aria-hidden="true">
                <div class="auth-device-glow"></div>
                <div class="auth-device-screen">
                    <span class="auth-device-logo">L</span>
                    <span class="auth-device-line auth-device-line-long"></span>
                    <span class="auth-device-line"></span>
                    <span class="auth-device-chip">ACCOUNT SECURITY</span>
                </div>
                <div class="auth-device-base"></div>
            </div>
            <div class="auth-showcase-foot">
                <span>03</span>
                <span>Liên kết riêng · Chỉ sử dụng một lần</span>
            </div>
        </aside>

        <section class="auth-form-panel" aria-labelledby="auth-title">
            <div class="auth-form-wrap">
                <a class="auth-mobile-brand" href="<?= BASE_URL ?>">
                    <span class="auth-brand-mark" aria-hidden="true">L</span>
                    LAPTOP STORE
                </a>
                <span class="auth-kicker">KHÔI PHỤC TÀI KHOẢN</span>
                <h2 id="auth-title">Quên mật khẩu?</h2>
                <p class="auth-intro">Nhập email tài khoản, chúng tôi sẽ gửi hướng dẫn đặt mật khẩu mới.</p>

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

                <form class="auth-form" method="POST" action="<?= BASE_URL ?>index.php?controller=auth&action=requestPasswordReset">
                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= htmlspecialchars($_SESSION["csrf_token"] ?? "", ENT_QUOTES, "UTF-8") ?>"
                    >
                    <div class="auth-field">
                        <label for="reset-email">Email tài khoản</label>
                        <input
                            id="reset-email"
                            type="email"
                            name="email"
                            required
                            maxlength="150"
                            autocomplete="email"
                            placeholder="ban@example.com"
                            value="<?= htmlspecialchars($email ?? "", ENT_QUOTES, "UTF-8") ?>"
                        >
                    </div>
                    <button class="auth-submit" type="submit">
                        Gửi liên kết đặt lại <span aria-hidden="true">→</span>
                    </button>
                </form>

                <a class="auth-home-link" href="<?= BASE_URL ?>index.php?controller=auth&action=login">
                    <span aria-hidden="true">←</span> Quay lại đăng nhập
                </a>
            </div>
            <span class="auth-copyright">© <?= date("Y") ?> Laptop Store</span>
        </section>
    </div>
</main>

<?php require __DIR__ . "/../layouts/footer.php"; ?>
