<?php
require __DIR__ . "/../../layouts/admin_header.php";

$item = $item ?? [];
$isEditing = !empty($item["id"]);

$baseUrl = BASE_URL . "index.php?controller=adminCategory";

$formUrl = $baseUrl . "&action="
    . ($isEditing
        ? "update&id=" . (int) $item["id"]
        : "store");

$name = $item["name"] ?? "";
$slug = $item["slug"] ?? "";
$description = $item["description"] ?? "";
$status = (int) ($item["status"] ?? 1);
?>

<div class="container py-4">
    <div class="mb-4">
        <h2><?= $isEditing ? "Cập nhật danh mục" : "Thêm danh mục" ?></h2>
        <p class="text-muted">Nhập thông tin danh mục sản phẩm.</p>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST"
                  action="<?= htmlspecialchars($formUrl, ENT_QUOTES, "UTF-8") ?>">

                <input type="hidden" name="csrf_token"
                       value="<?= htmlspecialchars($_SESSION["csrf_token"] ?? "", ENT_QUOTES, "UTF-8") ?>">

                <div class="mb-3">
                    <label for="name" class="form-label">Tên danh mục *</label>
                    <input
                        type="text"
                        class="form-control"
                        id="name"
                        name="name"
                        maxlength="100"
                        required
                        value="<?= htmlspecialchars($name, ENT_QUOTES, "UTF-8") ?>"
                        placeholder="Ví dụ: Laptop Gaming">
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
                        placeholder="laptop-gaming">
                    <div class="form-text">
                        Slug dùng trong đường dẫn website, ví dụ: laptop-gaming.
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

                <button type="submit" class="btn btn-primary">
                    <?= $isEditing ? "Lưu thay đổi" : "Thêm danh mục" ?>
                </button>

                <a href="<?= htmlspecialchars($baseUrl . '&action=index') ?>"
                   class="btn btn-secondary">
                    Hủy
                </a>
            </form>
        </div>
    </div>
</div>

<?php require __DIR__ . "/../../layouts/admin_footer.php"; ?>