<?php
require __DIR__ . "/../../layouts/admin_header.php";

$post = $post ?? [];
$isEditing = !empty($post["id"]);
$baseUrl = BASE_URL . "index.php?controller=adminPost";

$formUrl = $baseUrl . "&action="
    . ($isEditing ? "update&id=" . (int) $post["id"] : "store");

$title = $post["title"] ?? "";
$slug = $post["slug"] ?? "";
$content = $post["content"] ?? "";
$status = (int) ($post["status"] ?? 1);
$image = $post["image"] ?? "";
?>

<main class="container py-4 post-management">
    <div class="post-page-header">
        <div>
            <span class="post-eyebrow">NỘI DUNG CỬA HÀNG</span>
            <h1><?= $isEditing ? "Cập nhật bài viết" : "Thêm bài viết" ?></h1>
            <p>
                <?= $isEditing
                    ? "Chỉnh sửa nội dung bài viết. Để trống ảnh nếu không muốn thay đổi."
                    : "Nhập tiêu đề, nội dung và ảnh đại diện cho bài viết." ?>
            </p>
        </div>
        <a href="<?= htmlspecialchars($baseUrl . '&action=index', ENT_QUOTES, "UTF-8") ?>"
           class="post-secondary-link">
            <span aria-hidden="true">←</span> Quay lại danh sách
        </a>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?>
        </div>
    <?php endif; ?>

    <section class="post-form-panel">
        <div class="post-form-intro">
            <span class="post-form-icon" aria-hidden="true"><?= $isEditing ? "✎" : "＋" ?></span>
            <div>
                <h2>Nội dung bài viết</h2>
                <p>Điền tiêu đề, nội dung và thông tin xuất bản.</p>
            </div>
        </div>
        <form class="post-form"
                method="POST"
                action="<?= htmlspecialchars($formUrl, ENT_QUOTES, "UTF-8") ?>"
                enctype="multipart/form-data"
            >
                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars($_SESSION["csrf_token"] ?? "", ENT_QUOTES, "UTF-8") ?>"
                >

                <div class="mb-3">
                    <label for="title" class="form-label">Tiêu đề *</label>
                    <input
                        type="text"
                        class="form-control"
                        id="title"
                        name="title"
                        maxlength="255"
                        required
                        value="<?= htmlspecialchars($title, ENT_QUOTES, "UTF-8") ?>"
                        placeholder="Ví dụ: Top laptop gaming đáng mua 2026"
                    >
                </div>

                <div class="mb-3">
                    <label for="slug" class="form-label">Slug</label>
                    <input
                        type="text"
                        class="form-control"
                        id="slug"
                        name="slug"
                        maxlength="255"
                        value="<?= htmlspecialchars($slug, ENT_QUOTES, "UTF-8") ?>"
                        placeholder="Để trống để tự tạo từ tiêu đề"
                    >
                    <div class="post-form-help">
                        Dùng trong đường dẫn bài viết. Có thể để trống khi thêm mới.
                    </div>
                </div>

                <div class="mb-3">
                    <label for="image" class="form-label">
                        Ảnh đại diện <?= $isEditing ? "" : "(tùy chọn)" ?>
                    </label>
                    <input
                        type="file"
                        class="form-control"
                        id="image"
                        name="image"
                        accept="image/jpeg,image/png,image/webp"
                    >
                    <div class="post-form-help">
                        JPG, PNG hoặc WEBP, tối đa 5 MB.
                        <?php if ($isEditing): ?>
                            Để trống nếu giữ ảnh hiện tại.
                        <?php endif; ?>
                    </div>

                    <?php if ($isEditing && $image !== ""): ?>
                        <div class="mt-2">
                            <img
                                src="<?= BASE_URL . htmlspecialchars($image, ENT_QUOTES, "UTF-8") ?>"
                                alt="Ảnh bài viết"
                                class="post-image-preview"
                            >
                        </div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label for="content" class="form-label">Nội dung *</label>
                    <textarea
                        class="form-control"
                        id="content"
                        name="content"
                        rows="12"
                        required
                        placeholder="Nhập nội dung bài viết..."
                    ><?= htmlspecialchars($content, ENT_QUOTES, "UTF-8") ?></textarea>
                </div>

                <div class="mb-4">
                    <label for="status" class="form-label">Trạng thái</label>
                    <select class="form-select" id="status" name="status">
                        <option value="1" <?= $status === 1 ? "selected" : "" ?>>
                            Đã xuất bản
                        </option>
                        <option value="0" <?= $status === 0 ? "selected" : "" ?>>
                            Nháp / Ẩn
                        </option>
                    </select>
                </div>

                <div class="post-form-actions">
                    <button type="submit" class="btn btn-primary">
                        <?= $isEditing ? "Lưu thay đổi" : "Thêm bài viết" ?>
                    </button>

                    <a href="<?= htmlspecialchars($baseUrl . '&action=index') ?>" class="btn btn-secondary post-cancel-button">
                        Hủy
                    </a>
                </div>
        </form>
    </section>
</main>

<?php require __DIR__ . "/../../layouts/admin_footer.php"; ?>
