<?php require __DIR__ . "/../../layouts/admin_header.php"; ?>

<?php
$search = $search ?? "";
$currentPage = (int) ($currentPage ?? 1);
$limit = (int) ($limit ?? 10);
$totalUsers = (int) ($totalUsers ?? 0);
$totalPages = (int) ($totalPages ?? 1);
$users = $users ?? [];
$currentUserId = (int) ($currentUserId ?? 0);

$currentPage = max(1, $currentPage);
$totalPages = max(1, $totalPages);

$startUser = $totalUsers > 0
    ? ($currentPage - 1) * $limit + 1
    : 0;

$endUser = min($currentPage * $limit, $totalUsers);

$paginationUrl = function ($page) use ($search) {
    return "index.php?" . http_build_query([
        "controller" => "adminUser",
        "action" => "index",
        "q" => $search,
        "page" => (int) $page
    ]);
};

$startPage = max(1, $currentPage - 2);
$endPage = min($totalPages, $currentPage + 2);
$baseUrl = BASE_URL . "index.php?controller=adminUser";
?>

<main class="container py-4 user-management">
    <div class="user-page-header">
        <div>
            <span class="user-eyebrow">TÀI KHOẢN CỬA HÀNG</span>
            <h1>Quản lý người dùng</h1>
            <p>Quản lý thông tin, vai trò và trạng thái tài khoản trong cửa hàng.</p>
        </div>

        <a href="<?= htmlspecialchars($baseUrl . '&action=create') ?>" class="user-primary-link">
            <span aria-hidden="true">＋</span> Thêm người dùng
        </a>
    </div>

    <?php if (!empty($_SESSION["admin_success"])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= htmlspecialchars($_SESSION["admin_success"], ENT_QUOTES, "UTF-8") ?>
            <button type="button" class="btn-close" data-dismiss-alert aria-label="Đóng"></button>
        </div>
        <?php unset($_SESSION["admin_success"]); ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION["admin_error"])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?= htmlspecialchars($_SESSION["admin_error"], ENT_QUOTES, "UTF-8") ?>
            <button type="button" class="btn-close" data-dismiss-alert aria-label="Đóng"></button>
        </div>
        <?php unset($_SESSION["admin_error"]); ?>
    <?php endif; ?>

    <form method="GET" action="<?= BASE_URL ?>index.php" class="user-search-panel">
        <input type="hidden" name="controller" value="adminUser">
        <input type="hidden" name="action" value="index">

        <input
            type="text"
            name="q"
            class="form-control"
            aria-label="Tìm kiếm người dùng"
            placeholder="Tìm theo tên, email hoặc số điện thoại..."
            value="<?= htmlspecialchars($search, ENT_QUOTES, "UTF-8") ?>"
        >

        <button type="submit" class="btn btn-primary user-search-button">Tìm kiếm</button>

        <?php if ($search !== ""): ?>
            <a href="<?= htmlspecialchars($baseUrl . '&action=index') ?>" class="btn btn-outline-secondary user-clear-search">
                Xóa tìm kiếm
            </a>
        <?php endif; ?>
    </form>

    <section class="user-table-panel" aria-label="Danh sách người dùng">
        <div class="table-responsive">
        <table class="table align-middle user-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Họ tên</th>
                    <th>Email</th>
                    <th>Điện thoại</th>
                    <th>Vai trò</th>
                    <th>Trạng thái</th>
                    <th>Ngày tạo</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-4">
                            Không tìm thấy người dùng nào.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($users as $item): ?>
                        <?php $itemId = (int) ($item["id"] ?? 0); ?>
                        <tr>
                            <td><?= $itemId ?></td>
                            <td>
                                <strong><?= htmlspecialchars($item["name"] ?? "", ENT_QUOTES, "UTF-8") ?></strong>
                                <?php if ($itemId === $currentUserId): ?>
                                    <span class="badge bg-info user-self-badge">Bạn</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($item["email"] ?? "", ENT_QUOTES, "UTF-8") ?></td>
                            <td><?= htmlspecialchars($item["phone"] ?? "", ENT_QUOTES, "UTF-8") ?></td>
                            <td>
                                <?php if (($item["role"] ?? "") === "admin"): ?>
                                    <span class="badge bg-warning user-role-admin">Quản trị</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary user-role-customer">Khách hàng</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ((int) ($item["status"] ?? 0) === 1): ?>
                                    <span class="badge bg-success">Hoạt động</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Đã khóa</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= htmlspecialchars(
                                    !empty($item["created_at"])
                                        ? date("d/m/Y H:i", strtotime($item["created_at"]))
                                        : "",
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>
                            </td>
                            <td>
                                <div class="d-flex gap-2">
                                    <a
                                        class="btn btn-sm user-edit-button"
                                        href="<?= htmlspecialchars($baseUrl . '&action=edit&id=' . $itemId, ENT_QUOTES, 'UTF-8') ?>"
                                    >
                                        Sửa
                                    </a>

                                    <?php if ($itemId !== $currentUserId): ?>
                                        <form
                                            method="POST"
                                            action="<?= htmlspecialchars($baseUrl . '&action=delete', ENT_QUOTES, 'UTF-8') ?>"
                                            onsubmit="return confirm('Bạn có chắc chắn muốn xóa người dùng này?');"
                                        >
                                            <input type="hidden" name="id" value="<?= $itemId ?>">
                                            <input
                                                type="hidden"
                                                name="csrf_token"
                                                value="<?= htmlspecialchars($_SESSION["csrf_token"] ?? "", ENT_QUOTES, "UTF-8") ?>"
                                            >
                                            <button type="submit" class="btn btn-sm btn-danger user-delete-button">Xóa</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        </div>

    <div class="user-pagination">
        <div class="text-muted">
            Hiển thị <?= $startUser ?>–<?= $endUser ?>
            trong tổng số <?= $totalUsers ?> người dùng
        </div>

        <nav aria-label="Phân trang người dùng">
            <ul class="pagination mb-0 flex-wrap">
                <li class="page-item <?= $currentPage <= 1 ? "disabled" : "" ?>">
                    <?php if ($currentPage > 1): ?>
                        <a class="page-link" href="<?= htmlspecialchars($paginationUrl($currentPage - 1), ENT_QUOTES, "UTF-8") ?>">
                            &laquo; Trước
                        </a>
                    <?php else: ?>
                        <span class="page-link">&laquo; Trước</span>
                    <?php endif; ?>
                </li>

                <?php if ($startPage > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="<?= htmlspecialchars($paginationUrl(1), ENT_QUOTES, "UTF-8") ?>">1</a>
                    </li>
                    <?php if ($startPage > 2): ?>
                        <li class="page-item disabled"><span class="page-link">...</span></li>
                    <?php endif; ?>
                <?php endif; ?>

                <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                    <li class="page-item <?= $i === $currentPage ? "active" : "" ?>">
                        <?php if ($i === $currentPage): ?>
                            <span class="page-link"><?= $i ?></span>
                        <?php else: ?>
                            <a class="page-link" href="<?= htmlspecialchars($paginationUrl($i), ENT_QUOTES, "UTF-8") ?>">
                                <?= $i ?>
                            </a>
                        <?php endif; ?>
                    </li>
                <?php endfor; ?>

                <?php if ($endPage < $totalPages): ?>
                    <?php if ($endPage < $totalPages - 1): ?>
                        <li class="page-item disabled"><span class="page-link">...</span></li>
                    <?php endif; ?>
                    <li class="page-item">
                        <a class="page-link" href="<?= htmlspecialchars($paginationUrl($totalPages), ENT_QUOTES, "UTF-8") ?>">
                            <?= $totalPages ?>
                        </a>
                    </li>
                <?php endif; ?>

                <li class="page-item <?= $currentPage >= $totalPages ? "disabled" : "" ?>">
                    <?php if ($currentPage < $totalPages): ?>
                        <a class="page-link" href="<?= htmlspecialchars($paginationUrl($currentPage + 1), ENT_QUOTES, "UTF-8") ?>">
                            Tiếp &raquo;
                        </a>
                    <?php else: ?>
                        <span class="page-link">Tiếp &raquo;</span>
                    <?php endif; ?>
                </li>
            </ul>
        </nav>
    </div>

    </section>
    <a href="<?= BASE_URL ?>index.php?controller=admin&action=index" class="user-back-link">
        <span aria-hidden="true">←</span> Quay lại trang quản trị
    </a>
</main>

<?php require __DIR__ . "/../../layouts/admin_footer.php"; ?>
