<?php require __DIR__ . "/../../layouts/admin_header.php"; ?>

<?php
$search = $search ?? "";
$currentPage = (int) ($currentPage ?? 1);
$limit = (int) ($limit ?? 10);
$totalPosts = (int) ($totalPosts ?? 0);
$totalPages = (int) ($totalPages ?? 1);
$posts = $posts ?? [];

$currentPage = max(1, $currentPage);
$totalPages = max(1, $totalPages);

$startPost = $totalPosts > 0
    ? ($currentPage - 1) * $limit + 1
    : 0;

$endPost = min($currentPage * $limit, $totalPosts);

$paginationUrl = function ($page) use ($search) {
    return "index.php?" . http_build_query([
        "controller" => "adminPost",
        "action" => "index",
        "q" => $search,
        "page" => (int) $page
    ]);
};

$startPage = max(1, $currentPage - 2);
$endPage = min($totalPages, $currentPage + 2);
$baseUrl = BASE_URL . "index.php?controller=adminPost";
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div>
            <h2 class="mb-1">Quản lý bài viết</h2>
            <p class="text-muted mb-0">Thêm, sửa và xuất bản tin tức / bài viết.</p>
        </div>

        <a href="<?= htmlspecialchars($baseUrl . '&action=create') ?>" class="btn btn-primary">
            + Thêm bài viết
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

    <form method="GET" action="<?= BASE_URL ?>index.php" class="d-flex flex-wrap gap-2 mb-3">
        <input type="hidden" name="controller" value="adminPost">
        <input type="hidden" name="action" value="index">

        <input
            type="text"
            name="q"
            class="form-control"
            style="max-width: 420px;"
            placeholder="Tìm theo tiêu đề, slug hoặc tác giả..."
            value="<?= htmlspecialchars($search, ENT_QUOTES, "UTF-8") ?>"
        >

        <button type="submit" class="btn btn-primary">Tìm kiếm</button>

        <?php if ($search !== ""): ?>
            <a href="<?= htmlspecialchars($baseUrl . '&action=index') ?>" class="btn btn-outline-secondary">
                Xóa tìm kiếm
            </a>
        <?php endif; ?>
    </form>

    <div class="table-responsive">
        <table class="table table-bordered table-striped align-middle">
            <thead class="table-dark">
                <tr>
                    <th style="width: 70px;">ID</th>
                    <th style="width: 90px;">Ảnh</th>
                    <th>Tiêu đề</th>
                    <th>Tác giả</th>
                    <th style="width: 120px;">Trạng thái</th>
                    <th style="width: 160px;">Ngày tạo</th>
                    <th style="width: 180px;">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($posts)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-4">
                            Chưa có bài viết nào.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($posts as $item): ?>
                        <?php $itemId = (int) ($item["id"] ?? 0); ?>
                        <tr>
                            <td><?= $itemId ?></td>
                            <td>
                                <?php if (!empty($item["image"])): ?>
                                    <img
                                        src="<?= BASE_URL . htmlspecialchars($item["image"], ENT_QUOTES, "UTF-8") ?>"
                                        alt=""
                                        style="width: 64px; height: 48px; object-fit: cover;"
                                        class="rounded border"
                                    >
                                <?php else: ?>
                                    <span class="text-muted">Không có</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($item["title"] ?? "", ENT_QUOTES, "UTF-8") ?></strong>
                                <div class="small text-muted">
                                    <?= htmlspecialchars($item["slug"] ?? "", ENT_QUOTES, "UTF-8") ?>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($item["author_name"] ?? "—", ENT_QUOTES, "UTF-8") ?></td>
                            <td>
                                <?php if ((int) ($item["status"] ?? 0) === 1): ?>
                                    <span class="badge bg-success">Đã xuất bản</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Nháp / Ẩn</span>
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
                                        class="btn btn-sm btn-warning"
                                        href="<?= htmlspecialchars($baseUrl . '&action=edit&id=' . $itemId, ENT_QUOTES, 'UTF-8') ?>"
                                    >
                                        Sửa
                                    </a>

                                    <form
                                        method="POST"
                                        action="<?= htmlspecialchars($baseUrl . '&action=delete', ENT_QUOTES, 'UTF-8') ?>"
                                        onsubmit="return confirm('Bạn có chắc chắn muốn xóa bài viết này?');"
                                    >
                                        <input type="hidden" name="id" value="<?= $itemId ?>">
                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?= htmlspecialchars($_SESSION["csrf_token"] ?? "", ENT_QUOTES, "UTF-8") ?>"
                                        >
                                        <button type="submit" class="btn btn-sm btn-danger">Xóa</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-3">
        <div class="text-muted">
            Hiển thị <?= $startPost ?>–<?= $endPost ?>
            trong tổng số <?= $totalPosts ?> bài viết
        </div>

        <nav aria-label="Phân trang bài viết">
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

    <a href="<?= BASE_URL ?>index.php?controller=admin&action=index" class="btn btn-outline-secondary mt-3">
        ← Quay lại trang quản trị
    </a>
</div>

<?php require __DIR__ . "/../../layouts/admin_footer.php"; ?>
