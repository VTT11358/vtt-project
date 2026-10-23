<?php
require __DIR__ . "/../../layouts/admin_header.php";

$item = $item ?? [];
$isEditing = !empty($item["id"]);

$baseUrl = BASE_URL . "index.php?controller=adminBrands";

$formUrl = $baseUrl . "&action="
    . ($isEditing
        ? "update&id=" . (int) $item["id"]
        : "store");

$name = $item["name"] ?? "";
$slug = $item["slug"] ?? "";
$description = $item["description"] ?? "";
$status = (int) ($item["status"] ?? 1);
?>

<main class="container py-4 category-management brand-management">
    <div class="category-page-header">
        <div>
            <span class="category-eyebrow">DANH MỤC KINH DOANH</span>
            <h1><?= $isEditing ? "Cập nhật thương hiệu" : "Thêm thương hiệu" ?></h1>
            <p>Nhập thông tin để quản lý các thương hiệu laptop trong cửa hàng.</p>
        </div>
        <a href="<?= htmlspecialchars($baseUrl . '&action=index', ENT_QUOTES, "UTF-8") ?>"
           class="category-secondary-link">
            <span aria-hidden="true">←</span> Quay lại danh sách
        </a>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?>
        </div>
    <?php endif; ?>

    <section class="category-form-panel">
        <div class="category-form-intro">
            <span class="category-form-icon" aria-hidden="true"><?= $isEditing ? "✎" : "＋" ?></span>
            <div>
                <h2>Thông tin thương hiệu</h2>
                <p>Điền tên, đường dẫn và trạng thái hiển thị.</p>
            </div>
        </div>
        <form class="category-form" method="POST"
              action="<?= htmlspecialchars($formUrl, ENT_QUOTES, "UTF-8") ?>">

                <input type="hidden" name="csrf_token"
                       value="<?= htmlspecialchars($_SESSION["csrf_token"] ?? "", ENT_QUOTES, "UTF-8") ?>">

                <div class="mb-3">
                    <label for="name" class="form-label">Tên thương hiệu *</label>
                    <input
                        type="text"
                        class="form-control"
                        id="name"
                        name="name"
                        maxlength="100"
                        required
                        value="<?= htmlspecialchars($name, ENT_QUOTES, "UTF-8") ?>"
                        placeholder="Ví dụ: ASUS">
                </div>

                <div class="mb-3">
                    <label for="slug" class="form-label">Slug *</label>
                    <input
                        type="text"
                        class="form-control"
                        id="slug"
                        name="slug"
                        maxlength="150"
                        required
                        value="<?= htmlspecialchars($slug, ENT_QUOTES, "UTF-8") ?>"
                        placeholder="asus">
                    <div class="category-form-help">
                        Slug dùng trong đường dẫn website, ví dụ: asus.
                    </div>
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">Mô tả</label>
                    <textarea
                        class="form-control"
                        id="description"
                        name="description"
                        rows="4"><?= htmlspecialchars($description, ENT_QUOTES, "UTF-8") ?></textarea>
                </div>

                <div class="mb-4">
                    <label for="status" class="form-label">Trạng thái</label>
                    <select class="form-select" id="status" name="status">
                        <option value="1" <?= $status === 1 ? "selected" : "" ?>>
                            Hoạt động
                        </option>
                        <option value="0" <?= $status === 0 ? "selected" : "" ?>>
                            Đã ẩn
                        </option>
                    </select>
                </div>

                <div class="category-form-actions">
                    <button type="submit" class="btn btn-primary">
                        <?= $isEditing ? "Lưu thay đổi" : "Thêm thương hiệu" ?>
                    </button>

                    <a href="<?= htmlspecialchars($baseUrl . '&action=index') ?>"
                       class="btn btn-secondary category-cancel-button">
                        Hủy
                    </a>
                </div>
        </form>
    </section>
</main>

<?php require __DIR__ . "/../../layouts/admin_footer.php"; ?>