<?php require __DIR__ . "/../layouts/header.php"; ?>

<main class="auth-page">
    <div class="auth-shell auth-compact-shell">
        <aside class="auth-showcase">
            <a class="auth-showcase-brand" href="<?= BASE_URL ?>" aria-label="Laptop Store - Trang chủ">
                <span class="auth-brand-mark" aria-hidden="true">L</span>
                <span>LAPTOP<span>STORE</span></span>
            </a>
            <div class="auth-showcase-copy">
                <span class="auth-kicker">CẬP NHẬT BẢO MẬT</span>
                <h1>Tạo mật khẩu mới cho tài khoản.</h1>
                <p>Hãy chọn mật khẩu mạnh và không dùng chung với những tài khoản khác.</p>
            </div>
            <div class="auth-device-art" aria-hidden="true">
                <div class="auth-device-glow"></div>
                <div class="auth-device-screen">
                    <span class="auth-device-logo">L</span>
                    <span class="auth-device-line auth-device-line-long"></span>
                    <span class="auth-device-line"></span>
                    <span class="auth-device-chip">NEW PASSWORD</span>
                </div>
                <div class="auth-device-base"></div>
            </div>
            <div class="auth-showcase-foot">
                <span>04</span>
                <span>Liên kết sẽ hết hạn sau 1 giờ</span>
            </div>
        </aside>

        <section class="auth-form-panel" aria-labelledby="auth-title">
            <div class="auth-form-wrap">
                <a class="auth-mobile-brand" href="<?= BASE_URL ?>">
                    <span class="auth-brand-mark" aria-hidden="true">L</span>
                    LAPTOP STORE
                </a>
                <span class="auth-kicker">KHÔI PHỤC TÀI KHOẢN</span>
                <h2 id="auth-title">Đặt mật khẩu mới</h2>
                <p class="auth-intro">Mật khẩu mới cần có ít nhất 8 ký tự.</p>

                <?php if (!empty($error)): ?>
                    <div class="auth-notice auth-notice-error" role="alert">
                        <span aria-hidden="true">!</span>
                        <?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($tokenValid)): ?>
                    <form class="auth-form" method="POST" action="<?= BASE_URL ?>index.php?controller=auth&action=resetPassword">
                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= htmlspecialchars($_SESSION["csrf_token"] ?? "", ENT_QUOTES, "UTF-8") ?>"
                        >
                        <input
                            type="hidden"
                            name="token"
                            value="<?= htmlspecialchars($token ?? "", ENT_QUOTES, "UTF-8") ?>"
                        >
                        <div class="auth-field">
                            <label for="new-password">Mật khẩu mới</label>
                            <input
                                id="new-password"
                                type="password"
                                name="password"
                                required
                                minlength="8"
                                autocomplete="new-password"
                                placeholder="Ít nhất 8 ký tự"
                            >
                        </div>
                        <div class="auth-field">
                            <label for="confirm-new-password">Xác nhận mật khẩu</label>
                            <input
                                id="confirm-new-password"
                                type="password"
                                name="confirm_password"
                                required
                                minlength="8"
                                autocomplete="new-password"
                                placeholder="Nhập lại mật khẩu mới"
                            >
                        </div>
                        <button class="auth-submit" type="submit">
                            Lưu mật khẩu mới <span aria-hidden="true">→</span>
                        </button>
                    </form>
                <?php endif; ?>

                <a class="auth-home-link" href="<?= BASE_URL ?>index.php?controller=auth&action=login">
                    <span aria-hidden="true">←</span> Quay lại đăng nhập
                </a>
            </div>
            <span class="auth-copyright">© <?= date("Y") ?> Laptop Store</span>
        </section>
    </div>
</main>

<?php require __DIR__ . "/../layouts/footer.php"; ?>
