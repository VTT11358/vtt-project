<?php

$pageTitle = $post["title"] . " | Laptop Store";
$postContent = trim((string) ($post["content"] ?? ""));
$plainContent = trim(strip_tags($postContent));
preg_match_all('/[\p{L}\p{N}]+/u', $plainContent, $wordMatches);
$wordCount = count($wordMatches[0]);
$readingMinutes = max(1, (int) ceil($wordCount / 220));

require_once __DIR__ . "/../layouts/header.php";
?>

<main class="article-page">
    <div class="article-container">
        <article class="article-layout">
            <div class="article-main">
                <header class="article-header" data-journal-reveal>
                    <span class="article-kicker"><span></span> LAPTOP STORE JOURNAL</span>
                    <h1><?= htmlspecialchars($post["title"], ENT_QUOTES, "UTF-8") ?></h1>
                    <div class="article-meta">
                        <span class="article-author-mark" aria-hidden="true">
                            <?= htmlspecialchars(mb_substr($post["author_name"] ?? "L", 0, 1), ENT_QUOTES, "UTF-8") ?>
                        </span>
                        <span>
                            <strong><?= htmlspecialchars($post["author_name"] ?? "Laptop Store", ENT_QUOTES, "UTF-8") ?></strong>
                            <small><?= htmlspecialchars(date("d/m/Y", strtotime($post["created_at"])), ENT_QUOTES, "UTF-8") ?> · <?= $readingMinutes ?> phút đọc</small>
                        </span>
                    </div>
                </header>

                <?php if (!empty($post["image"])): ?>
                    <figure class="article-cover" data-journal-reveal>
                        <img
                            src="<?= BASE_URL . htmlspecialchars($post["image"], ENT_QUOTES, "UTF-8") ?>"
                            alt="<?= htmlspecialchars($post["title"], ENT_QUOTES, "UTF-8") ?>"
                        >
                        <figcaption>Chia sẻ mới nhất từ Laptop Store Journal</figcaption>
                    </figure>
                <?php endif; ?>

                <div class="article-content" data-article-content>
                    <?= nl2br(htmlspecialchars($postContent, ENT_QUOTES, "UTF-8")) ?>
                </div>

                <footer class="article-footer">
                    <span>Được đăng bởi <strong><?= htmlspecialchars($post["author_name"] ?? "Laptop Store", ENT_QUOTES, "UTF-8") ?></strong></span>
                    <a href="<?= BASE_URL ?>index.php?controller=post&action=index">
                        <span aria-hidden="true">←</span> Quay lại tin tức
                    </a>
                </footer>
            </div>

            <aside class="article-sidebar">
                <div class="article-sidebar-card">
                    <span class="article-sidebar-mark" aria-hidden="true">LS</span>
                    <span class="article-kicker">LAPTOP STORE JOURNAL</span>
                    <h2>Luôn cập nhật. Luôn khám phá.</h2>
                    <p>Tin công nghệ và chia sẻ hữu ích giúp bạn chọn, dùng laptop tốt hơn.</p>
                    <a href="<?= BASE_URL ?>index.php?controller=post&action=index">
                        Xem thêm bài viết <span aria-hidden="true">↗</span>
                    </a>
                </div>
                <div class="article-sidebar-note">
                    <span class="article-sidebar-note-icon" aria-hidden="true">✦</span>
                    <p>Đang tìm thiết bị cho bước tiếp theo?</p>
                    <a href="<?= BASE_URL ?>index.php?controller=product&action=index">Khám phá laptop <span aria-hidden="true">→</span></a>
                </div>
            </aside>
        </article>
    </div>

    <div class="article-progress" aria-hidden="true">
        <span data-article-progress></span>
    </div>
</main>

<?php require_once __DIR__ . "/../layouts/footer.php"; ?>
