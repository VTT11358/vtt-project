<?php

$name = (string) ($profile["name"] ?? "");
$email = (string) ($profile["email"] ?? "");
$avatar = trim((string) ($profile["avatar"] ?? ""));
$initial = function_exists("mb_substr")
    ? mb_substr($name !== "" ? $name : "T", 0, 1, "UTF-8")
    : strtoupper(substr($name !== "" ? $name : "T", 0, 1));
$isAdmin = ($profile["role"] ?? "") === "admin";
$createdAt = !empty($profile["created_at"])
    ? date("d/m/Y", strtotime($profile["created_at"]))
    : "";
$roleLabel = $isAdmin ? "Quản trị viên" : "Khách hàng";

require __DIR__ . "/../layouts/header.php";

?>

<main class="profile-page">
    <div class="profile-container">
        <header class="profile-page-heading">
            <div>
                <span class="profile-kicker">TÀI KHOẢN CỦA BẠN</span>
                <h1>Hồ sơ cá nhân</h1>
                <p>Thông tin tài khoản và cách bạn sử dụng Laptop Store.</p>
            </div>
            <a class="profile-back-link" href="<?= BASE_URL ?>">
                <span aria-hidden="true">←</span> Về trang chủ
            </a>
        </header>

        <?php if (!empty($success)): ?>
            <div class="profile-notice profile-notice-success" role="status">
                <?= htmlspecialchars($success, ENT_QUOTES, "UTF-8") ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="profile-notice profile-notice-error" role="alert">
                <?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?>
            </div>
        <?php endif; ?>

        <div class="profile-layout">
            <section class="profile-card profile-overview" aria-labelledby="profile-name">
                <div class="profile-cover">
                    <span class="profile-cover-mark" aria-hidden="true">L</span>
                </div>
                <div class="profile-avatar">
                    <?php if ($avatar !== ""): ?>
                        <img
                            src="<?= htmlspecialchars($avatar, ENT_QUOTES, "UTF-8") ?>"
                            alt="Ảnh đại diện của <?= htmlspecialchars($name, ENT_QUOTES, "UTF-8") ?>"
                            referrerpolicy="no-referrer"
                            data-account-avatar-image
                            data-default-avatar="<?= BASE_URL ?>assets/images/default-avatar.svg"
                        >
                    <?php else: ?>
                        <span><?= htmlspecialchars($initial, ENT_QUOTES, "UTF-8") ?></span>
                    <?php endif; ?>
                </div>
                <div class="profile-overview-copy">
                    <span class="profile-role-badge">
                        <i aria-hidden="true"></i> <?= htmlspecialchars($roleLabel, ENT_QUOTES, "UTF-8") ?>
                    </span>
                    <h2 id="profile-name"><?= htmlspecialchars($name, ENT_QUOTES, "UTF-8") ?></h2>
                    <p><?= htmlspecialchars($email, ENT_QUOTES, "UTF-8") ?></p>
                    <?php if ($createdAt !== ""): ?>
                        <span class="profile-member-since">Thành viên từ <?= htmlspecialchars($createdAt, ENT_QUOTES, "UTF-8") ?></span>
                    <?php endif; ?>
                </div>
                <div class="profile-overview-footer">
                    <span class="profile-account-id">MÃ TÀI KHOẢN</span>
                    <strong>#<?= (int) $profile["id"] ?></strong>
                </div>
            </section>

            <div class="profile-details-column">
            <section class="profile-card profile-information" aria-labelledby="profile-information-heading">
                <div class="profile-card-heading">
                    <span class="profile-heading-icon" aria-hidden="true">i</span>
                    <div>
                        <span class="profile-kicker">THÔNG TIN TÀI KHOẢN</span>
                        <h2 id="profile-information-heading">Thông tin cá nhân</h2>
                    </div>
                </div>

                <form
                    class="profile-edit-form"
                    method="POST"
                    action="<?= BASE_URL ?>index.php?controller=profile&action=update"
                >
                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= htmlspecialchars($_SESSION["csrf_token"] ?? "", ENT_QUOTES, "UTF-8") ?>"
                    >
                    <div class="profile-edit-fields">
                        <label class="profile-edit-field">
                            <span>Họ và tên <b aria-hidden="true">*</b></span>
                            <input
                                type="text"
                                name="name"
                                value="<?= htmlspecialchars($name, ENT_QUOTES, "UTF-8") ?>"
                                maxlength="100"
                                autocomplete="name"
                                required
                            >
                        </label>
                        <label class="profile-edit-field">
                            <span>Email <b aria-hidden="true">*</b></span>
                            <input
                                type="email"
                                name="email"
                                value="<?= htmlspecialchars($email, ENT_QUOTES, "UTF-8") ?>"
                                maxlength="150"
                                autocomplete="email"
                                required
                            >
                        </label>
                        <label class="profile-edit-field">
                            <span>Số điện thoại</span>
                            <input
                                type="tel"
                                name="phone"
                                value="<?= htmlspecialchars((string) ($profile["phone"] ?? ""), ENT_QUOTES, "UTF-8") ?>"
                                maxlength="20"
                                autocomplete="tel"
                            >
                        </label>
                        <label class="profile-edit-field profile-edit-address">
                            <span>Địa chỉ</span>
                            <textarea
                                name="address"
                                maxlength="255"
                                rows="3"
                                autocomplete="street-address"
                            ><?= htmlspecialchars((string) ($profile["address"] ?? ""), ENT_QUOTES, "UTF-8") ?></textarea>
                        </label>
                    </div>
                    <p class="profile-edit-hint">
                        Nếu thay đổi email, chúng tôi sẽ gửi liên kết xác nhận đến địa chỉ mới. Email hiện tại vẫn được sử dụng cho đến khi bạn xác nhận.
                    </p>
                    <?php if ($pendingEmail !== null): ?>
                        <p class="profile-pending-email" role="status">
                            Đang chờ xác nhận email:
                            <strong><?= htmlspecialchars($pendingEmail, ENT_QUOTES, "UTF-8") ?></strong>
                        </p>
                    <?php endif; ?>
                    <div class="profile-edit-footer">
                        <span>Vai trò: <?= htmlspecialchars($roleLabel, ENT_QUOTES, "UTF-8") ?></span>
                        <button class="profile-primary-link" type="submit">
                            Lưu thay đổi <span aria-hidden="true">→</span>
                        </button>
                    </div>
                </form>

                <div class="profile-card-actions">
                    <?php if ($isAdmin): ?>
                        <a class="profile-primary-link" href="<?= BASE_URL ?>index.php?controller=admin&action=index">
                            Trang quản trị <span aria-hidden="true">→</span>
                        </a>
                    <?php else: ?>
                        <a class="profile-primary-link" href="<?= BASE_URL ?>index.php?controller=order&action=index">
                            Xem đơn hàng của tôi <span aria-hidden="true">→</span>
                        </a>
                    <?php endif; ?>
                    <span>Thông tin được lấy từ tài khoản của bạn.</span>
                </div>
            </section>

            <section class="profile-card profile-password-card" aria-labelledby="profile-password-heading">
                <div class="profile-card-heading">
                    <span class="profile-heading-icon profile-password-icon" aria-hidden="true">⌑</span>
                    <div>
                        <span class="profile-kicker">BẢO MẬT TÀI KHOẢN</span>
                        <h2 id="profile-password-heading">Đổi mật khẩu</h2>
                    </div>
                </div>
                <p class="profile-password-description">
                    Gửi liên kết bảo mật đến email của bạn để đặt mật khẩu mới. Liên kết chỉ dùng được một lần và hết hạn sau 1 giờ.
                </p>
                <form
                    class="profile-password-form"
                    method="POST"
                    action="<?= BASE_URL ?>index.php?controller=auth&action=requestPasswordChange"
                >
                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= htmlspecialchars($_SESSION["csrf_token"] ?? "", ENT_QUOTES, "UTF-8") ?>"
                    >
                    <button class="profile-primary-link" type="submit">
                        Gửi liên kết đổi mật khẩu <span aria-hidden="true">→</span>
                    </button>
                    <span class="profile-password-email">
                        Gửi tới <?= htmlspecialchars($email, ENT_QUOTES, "UTF-8") ?>
                    </span>
                </form>
            </section>
            </div>
        </div>
    </div>
</main>

<?php require __DIR__ . "/../layouts/footer.php"; ?>
