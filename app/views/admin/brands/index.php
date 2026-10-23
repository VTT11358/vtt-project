<?php
require __DIR__ . "/../../layouts/admin_header.php";

$items = $items ?? [];
$baseUrl = BASE_URL . "index.php?controller=adminBrands";
?>

<main class="container py-4 category-management brand-management">
    <div class="category-page-header">
        <div>
            <span class="category-eyebrow">DANH MỤC KINH DOANH</span>
            <h1>Quản lý thương hiệu</h1>
            <p>Thêm, cập nhật và quản lý các thương hiệu sản phẩm trong cửa hàng.</p>
        </div>

        <a href="<?= htmlspecialchars($baseUrl . '&action=create') ?>"
           class="category-primary-link">
            <span aria-hidden="true">＋</span> Thêm thương hiệu
        </a>
    </div>

    <?php if (!empty($_SESSION["admin_success"])): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($_SESSION["admin_success"], ENT_QUOTES, "UTF-8") ?>
        </div>
        <?php unset($_SESSION["admin_success"]); ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION["admin_error"])): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($_SESSION["admin_error"], ENT_QUOTES, "UTF-8") ?>
        </div>
        <?php unset($_SESSION["admin_error"]); ?>
    <?php endif; ?>

    <section class="category-table-panel" aria-label="Danh sách thương hiệu">
        <div class="table-responsive">
            <table class="table align-middle category-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tên thương hiệu</th>
                        <th>Slug</th>
                        <th>Mô tả</th>
                        <th>Trạng thái</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if (empty($items)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-4">
                                Chưa có thương hiệu nào.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td>#<?= (int) ($item["id"] ?? 0) ?></td>

                                <td>
                                    <strong>
                                        <?= htmlspecialchars($item["name"] ?? "", ENT_QUOTES, "UTF-8") ?>
                                    </strong>
                                </td>

                                <td>
                                    <span class="category-slug">
                                        <?= htmlspecialchars($item["slug"] ?? "", ENT_QUOTES, "UTF-8") ?>
                                    </span>
                                </td>

                                <td class="category-description">
                                    <?= htmlspecialchars($item["description"] ?? "", ENT_QUOTES, "UTF-8") ?>
                                </td>

                                <td>
                                    <?php if ((int) ($item["status"] ?? 0) === 1): ?>
                                        <span class="badge bg-success">Hoạt động</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Đã ẩn</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <div class="d-flex gap-2">
                                        <a
                                            class="btn btn-sm category-edit-button"
                                            href="<?= htmlspecialchars(
                                                $baseUrl . '&action=edit&id=' . (int) $item["id"],
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>">
                                            Sửa
                                        </a>

                                        <form
                                            method="POST"
                                            action="<?= htmlspecialchars($baseUrl . '&action=delete', ENT_QUOTES, "UTF-8") ?>"
                                            onsubmit="return confirm('Bạn có chắc chắn muốn xóa thương hiệu này?');">

                                            <input type="hidden" name="id"
                                                   value="<?= (int) $item["id"] ?>">

                                            <input type="hidden" name="csrf_token"
                                                   value="<?= htmlspecialchars($_SESSION["csrf_token"] ?? "", ENT_QUOTES, "UTF-8") ?>">

                                            <button type="submit" class="btn btn-sm btn-danger category-delete-button">
                                                Xóa
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="category-table-footer">
            <span><?= number_format(count($items)) ?> thương hiệu</span>
            <a href="<?= BASE_URL ?>index.php?controller=admin&action=index"
               class="category-back-link">
                <span aria-hidden="true">←</span> Quay lại trang quản trị
            </a>
        </div>
    </section>
</main>

<?php require __DIR__ . "/../../layouts/admin_footer.php"; ?>