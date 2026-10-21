<?php

$pageTitle = "Tin tức & chia sẻ | Laptop Store";
$detailUrl = BASE_URL . "index.php?controller=post&action=detail&id=";
$pageSize = 6;
$firstPageNumber = $pageNumbers[0] ?? 1;
$lastPageNumber = $pageNumbers ? end($pageNumbers) : 1;
$excerptFor = static function (array $post, int $length): string {
    $excerpt = trim(strip_tags($post["content"] ?? ""));
    return mb_substr($excerpt, 0, $length) . (mb_strlen($excerpt) > $length ? "…" : "");
};

require_once __DIR__ . "/../layouts/header.php";
?>

<main class="journal-page">
    <section class="journal-content">
        <div class="journal-container">
            <?php if ($totalPosts > 0): ?>
                <div class="journal-results">
                    <span>
                        Hiển thị <strong><?= number_format(($page - 1) * $pageSize + 1) ?>–<?= number_format(min($page * $pageSize, $totalPosts)) ?></strong>
                        trong <strong><?= number_format($totalPosts) ?></strong> bài viết
                    </span>
                    <span class="journal-page-label">TRANG <?= number_format($page) ?> / <?= number_format($totalPages) ?></span>
                </div>
            <?php endif; ?>

            <?php if ($latestPost): ?>
                <article class="journal-featured" data-journal-reveal>
                    <a
                        class="journal-featured-image"
                        href="<?= $detailUrl . (int) $latestPost["id"] ?>"
                        aria-label="Đọc bài viết <?= htmlspecialchars($latestPost["title"], ENT_QUOTES, "UTF-8") ?>"
                    >
                        <?php if (!empty($latestPost["image"])): ?>
                            <img
                                src="<?= BASE_URL . htmlspecialchars($latestPost["image"], ENT_QUOTES, "UTF-8") ?>"
                                alt="<?= htmlspecialchars($latestPost["title"], ENT_QUOTES, "UTF-8") ?>"
                            >
                        <?php else: ?>
                            <span class="journal-image-placeholder" aria-hidden="true">
                                <strong>LS</strong><small>LAPTOP STORE JOURNAL</small>
                            </span>
                        <?php endif; ?>
                        <span class="journal-featured-badge">BÀI VIẾT MỚI</span>
                    </a>
                    <div class="journal-featured-content">
                        <span class="journal-category">ĐIỂM TIN CÔNG NGHỆ</span>
                        <div class="journal-meta">
                            <span><?= htmlspecialchars($latestPost["author_name"] ?? "Laptop Store", ENT_QUOTES, "UTF-8") ?></span>
                            <span><?= htmlspecialchars(date("d/m/Y", strtotime($latestPost["created_at"])), ENT_QUOTES, "UTF-8") ?></span>
                        </div>
                        <h2>
                            <a href="<?= $detailUrl . (int) $latestPost["id"] ?>">
                                <?= htmlspecialchars($latestPost["title"], ENT_QUOTES, "UTF-8") ?>
                            </a>
                        </h2>
                        <p><?= htmlspecialchars($excerptFor($latestPost, 240), ENT_QUOTES, "UTF-8") ?></p>
                        <a class="journal-read-link" href="<?= $detailUrl . (int) $latestPost["id"] ?>">
                            Đọc bài viết <span aria-hidden="true">↗</span>
                        </a>
                    </div>
                </article>
            <?php else: ?>
                <div class="journal-empty">
                    <span aria-hidden="true">LS</span>
                    <h2>Chưa có bài viết</h2>
                    <p>Tin tức và chia sẻ mới sẽ được cập nhật tại đây. Hãy quay lại sau nhé.</p>
                    <a href="<?= BASE_URL ?>">Về trang chủ <span aria-hidden="true">↗</span></a>
                </div>
            <?php endif; ?>

            <?php if (!empty($posts)): ?>
                <section class="journal-latest" id="journal-latest" aria-labelledby="journal-latest-heading">
                    <div class="journal-section-heading" data-journal-reveal>
                        <div>
                            <span class="journal-section-kicker">KHÁM PHÁ THÊM</span>
                            <h2 id="journal-latest-heading">Danh sách bài viết</h2>
                            <p>Kiến thức và câu chuyện công nghệ dành cho bạn.</p>
                        </div>
                        <span class="journal-section-index">
                            <?= sprintf("%02d — %02d", ($page - 1) * $pageSize + 1, min($page * $pageSize, $totalPosts)) ?>
                        </span>
                    </div>

                    <div class="journal-grid">
                        <?php foreach ($posts as $post): ?>
                            <article class="journal-card" data-journal-reveal>
                                <a
                                    class="journal-card-image"
                                    href="<?= $detailUrl . (int) $post["id"] ?>"
                                    aria-label="Đọc bài viết <?= htmlspecialchars($post["title"], ENT_QUOTES, "UTF-8") ?>"
                                >
                                    <?php if (!empty($post["image"])): ?>
                                        <img
                                            src="<?= BASE_URL . htmlspecialchars($post["image"], ENT_QUOTES, "UTF-8") ?>"
                                            alt="<?= htmlspecialchars($post["title"], ENT_QUOTES, "UTF-8") ?>"
                                            loading="lazy"
                                        >
                                    <?php else: ?>
                                        <span class="journal-image-placeholder" aria-hidden="true">
                                            <strong>LS</strong><small>TECH JOURNAL</small>
                                        </span>
                                    <?php endif; ?>
                                    <span class="journal-card-arrow" aria-hidden="true">↗</span>
                                </a>
                                <div class="journal-card-content">
                                    <div class="journal-meta">
                                        <span><?= htmlspecialchars($post["author_name"] ?? "Laptop Store", ENT_QUOTES, "UTF-8") ?></span>
                                        <span><?= htmlspecialchars(date("d/m/Y", strtotime($post["created_at"])), ENT_QUOTES, "UTF-8") ?></span>
                                    </div>
                                    <h3>
                                        <a href="<?= $detailUrl . (int) $post["id"] ?>">
                                            <?= htmlspecialchars($post["title"], ENT_QUOTES, "UTF-8") ?>
                                        </a>
                                    </h3>
                                    <p><?= htmlspecialchars($excerptFor($post, 140), ENT_QUOTES, "UTF-8") ?></p>
                                    <a class="journal-read-link" href="<?= $detailUrl . (int) $post["id"] ?>">
                                        Đọc tiếp <span aria-hidden="true">→</span>
                                    </a>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

            <?php if ($totalPages > 1): ?>
                <nav class="journal-pagination" aria-label="Điều hướng trang bài viết">
                    <?php if ($page > 1): ?>
                        <a class="journal-page-control" href="<?= BASE_URL ?>index.php?controller=post&action=index&page=<?= $page - 1 ?>">
                            <span aria-hidden="true">←</span> Trước
                        </a>
                    <?php else: ?>
                        <span class="journal-page-control is-disabled" aria-disabled="true"><span aria-hidden="true">←</span> Trước</span>
                    <?php endif; ?>

                    <div class="journal-page-numbers">
                        <?php if ($firstPageNumber > 1): ?>
                            <a href="<?= BASE_URL ?>index.php?controller=post&action=index&page=1">1</a>
                            <?php if ($firstPageNumber > 2): ?><span class="journal-page-ellipsis">…</span><?php endif; ?>
                        <?php endif; ?>
                        <?php foreach ($pageNumbers as $pageNumber): ?>
                            <?php if ($pageNumber === $page): ?>
                                <span class="is-current" aria-current="page"><?= $pageNumber ?></span>
                            <?php else: ?>
                                <a href="<?= BASE_URL ?>index.php?controller=post&action=index&page=<?= $pageNumber ?>"><?= $pageNumber ?></a>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        <?php if ($lastPageNumber < $totalPages): ?>
                            <?php if ($lastPageNumber < $totalPages - 1): ?><span class="journal-page-ellipsis">…</span><?php endif; ?>
                            <a href="<?= BASE_URL ?>index.php?controller=post&action=index&page=<?= $totalPages ?>"><?= $totalPages ?></a>
                        <?php endif; ?>
                    </div>

                    <?php if ($page < $totalPages): ?>
                        <a class="journal-page-control" href="<?= BASE_URL ?>index.php?controller=post&action=index&page=<?= $page + 1 ?>">
                            Tiếp <span aria-hidden="true">→</span>
                        </a>
                    <?php else: ?>
                        <span class="journal-page-control is-disabled" aria-disabled="true">Tiếp <span aria-hidden="true">→</span></span>
                    <?php endif; ?>
                </nav>
            <?php endif; ?>
        </div>
    </section>

    <section class="journal-cta">
        <div class="journal-container journal-cta-inner">
            <div>
                <span class="journal-section-kicker">CÔNG NGHỆ CHO HÀNH TRÌNH TIẾP THEO</span>
                <h2>Đang tìm chiếc laptop phù hợp?</h2>
            </div>
            <a href="<?= BASE_URL ?>index.php?controller=product&action=index">
                Khám phá sản phẩm <span aria-hidden="true">↗</span>
            </a>
        </div>
    </section>
</main>

<?php require_once __DIR__ . "/../layouts/footer.php"; ?>
