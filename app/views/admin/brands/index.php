<?php
require __DIR__ . "/../../layouts/admin_header.php";

$items = $items ?? [];
$baseUrl = BASE_URL . "index.php?controller=adminBrands";
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <h2 class="mb-1">Quản lý thương hiệu</h2>
            <p class="text-muted mb-0">Quản lý các hãng laptop trong cửa hàng.</p>
        </div>

        <a href="<?= htmlspecialchars($baseUrl . '&action=create') ?>"
           class="btn btn-primary">
            + Thêm thương hiệu
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

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th style="width: 80px;">ID</th>
                            <th>Tên thương hiệu</th>
                            <th>Slug</th>
                            <th>Mô tả</th>
                            <th style="width: 120px;">Trạng thái</th>
                            <th style="width: 180px;">Thao tác</th>
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
                                    <td><?= (int) ($item["id"] ?? 0) ?></td>

                                    <td>
                                        <strong>
                                            <?= htmlspecialchars($item["name"] ?? "", ENT_QUOTES, "UTF-8") ?>
                                        </strong>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($item["slug"] ?? "", ENT_QUOTES, "UTF-8") ?>
                                    </td>

                                    <td>
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
                                                class="btn btn-sm btn-warning"
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

                                                <button type="submit" class="btn btn-sm btn-danger">
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
        </div>
    </div>

    <a href="<?= BASE_URL ?>index.php?controller=admin&action=index"
       class="btn btn-outline-secondary mt-3">
        ← Quay lại trang quản trị
    </a>
</div>

<?php require __DIR__ . "/../../layouts/admin_footer.php"; ?>