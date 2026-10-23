<?php
require __DIR__ . "/../../layouts/admin_header.php";

$user = $user ?? [];
$isEditing = !empty($user["id"]);
$baseUrl = BASE_URL . "index.php?controller=adminUser";

$formUrl = $baseUrl . "&action="
    . ($isEditing ? "update&id=" . (int) $user["id"] : "store");

$name = $user["name"] ?? "";
$email = $user["email"] ?? "";
$phone = $user["phone"] ?? "";
$address = $user["address"] ?? "";
$role = $user["role"] ?? "customer";
$status = (int) ($user["status"] ?? 1);
$currentUserId = (int) ($_SESSION["user"]["id"] ?? 0);
$isSelf = $isEditing && (int) ($user["id"] ?? 0) === $currentUserId;
?>

<main class="container py-4 user-management">
    <div class="user-page-header">
        <div>
            <span class="user-eyebrow">TÀI KHOẢN CỬA HÀNG</span>
            <h1><?= $isEditing ? "Cập nhật người dùng" : "Thêm người dùng" ?></h1>
            <p>
                <?= $isEditing
                    ? "Chỉnh sửa thông tin tài khoản. Để trống mật khẩu nếu không muốn đổi."
                    : "Nhập thông tin tài khoản mới." ?>
            </p>
        </div>
        <a href="<?= htmlspecialchars($baseUrl . '&action=index', ENT_QUOTES, "UTF-8") ?>"
           class="user-secondary-link">
            <span aria-hidden="true">←</span> Quay lại danh sách
        </a>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?>
        </div>
    <?php endif; ?>

    <section class="user-form-panel">
        <div class="user-form-intro">
            <span class="user-form-icon" aria-hidden="true"><?= $isEditing ? "✎" : "＋" ?></span>
            <div>
                <h2>Thông tin tài khoản</h2>
                <p>Quản lý thông tin cá nhân, quyền truy cập và bảo mật.</p>
            </div>
        </div>
        <form class="user-form" method="POST" action="<?= htmlspecialchars($formUrl, ENT_QUOTES, "UTF-8") ?>">
                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars($_SESSION["csrf_token"] ?? "", ENT_QUOTES, "UTF-8") ?>"
                >

                <div class="mb-3">
                    <label for="name" class="form-label">Họ và tên *</label>
                    <input
                        type="text"
                        class="form-control"
                        id="name"
                        name="name"
                        maxlength="100"
                        required
                        value="<?= htmlspecialchars($name, ENT_QUOTES, "UTF-8") ?>"
                    >
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">Email *</label>
                    <input
                        type="email"
                        class="form-control"
                        id="email"
                        name="email"
                        maxlength="150"
                        required
                        value="<?= htmlspecialchars($email, ENT_QUOTES, "UTF-8") ?>"
                    >
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="phone" class="form-label">Số điện thoại</label>
                        <input
                            type="text"
                            class="form-control"
                            id="phone"
                            name="phone"
                            maxlength="20"
                            value="<?= htmlspecialchars($phone, ENT_QUOTES, "UTF-8") ?>"
                        >
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="address" class="form-label">Địa chỉ</label>
                        <input
                            type="text"
                            class="form-control"
                            id="address"
                            name="address"
                            maxlength="255"
                            value="<?= htmlspecialchars($address, ENT_QUOTES, "UTF-8") ?>"
                        >
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="role" class="form-label">Vai trò</label>
                        <select class="form-select" id="role" name="role" <?= $isSelf ? "disabled" : "" ?>>
                            <option value="customer" <?= $role === "customer" ? "selected" : "" ?>>
                                Khách hàng
                            </option>
                            <option value="admin" <?= $role === "admin" ? "selected" : "" ?>>
                                Quản trị viên
                            </option>
                        </select>
                        <?php if ($isSelf): ?>
                            <input type="hidden" name="role" value="admin">
                            <div class="user-form-help">Bạn không thể tự hạ quyền quản trị của mình.</div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="status" class="form-label">Trạng thái</label>
                        <select class="form-select" id="status" name="status" <?= $isSelf ? "disabled" : "" ?>>
                            <option value="1" <?= $status === 1 ? "selected" : "" ?>>Hoạt động</option>
                            <option value="0" <?= $status === 0 ? "selected" : "" ?>>Đã khóa</option>
                        </select>
                        <?php if ($isSelf): ?>
                            <input type="hidden" name="status" value="1">
                            <div class="user-form-help">Bạn không thể khóa tài khoản đang đăng nhập.</div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="password" class="form-label">
                            Mật khẩu <?= $isEditing ? "" : "*" ?>
                        </label>
                        <input
                            type="password"
                            class="form-control"
                            id="password"
                            name="password"
                            minlength="8"
                            <?= $isEditing ? "" : "required" ?>
                        >
                        <?php if ($isEditing): ?>
                            <div class="user-form-help">Để trống nếu không muốn đổi mật khẩu.</div>
                        <?php else: ?>
                            <div class="user-form-help">Tối thiểu 8 ký tự.</div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="confirm_password" class="form-label">
                            Xác nhận mật khẩu <?= $isEditing ? "" : "*" ?>
                        </label>
                        <input
                            type="password"
                            class="form-control"
                            id="confirm_password"
                            name="confirm_password"
                            minlength="8"
                            <?= $isEditing ? "" : "required" ?>
                        >
                    </div>
                </div>

                <div class="user-form-actions">
                    <button type="submit" class="btn btn-primary">
                        <?= $isEditing ? "Lưu thay đổi" : "Thêm người dùng" ?>
                    </button>

                    <a href="<?= htmlspecialchars($baseUrl . '&action=index') ?>" class="btn btn-secondary user-cancel-button">
                        Hủy
                    </a>
                </div>
        </form>
    </section>
</main>

<?php require __DIR__ . "/../../layouts/admin_footer.php"; ?>
