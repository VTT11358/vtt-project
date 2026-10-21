<?php

require_once __DIR__ . "/../../helpers/ProductImage.php";
require_once __DIR__ . "/../layouts/header.php";

$renderProducts = static function ($products, string $badge = ""): void {
    if ($products && $products->num_rows > 0):
        while ($product = $products->fetch_assoc()):
            $price = (float) (
                !empty($product["sale_price"])
                    ? $product["sale_price"]
                    : $product["price"]
            );
            $originalPrice = (float) $product["price"];
            ?>
            <article class="home-product-card" data-home-reveal>
                <a
                    class="home-product-image"
                    href="<?= BASE_URL ?>index.php?controller=product&action=detail&id=<?= (int) $product["id"] ?>"
                    aria-label="Xem <?= htmlspecialchars($product["name"], ENT_QUOTES, "UTF-8") ?>"
                >
                    <?php if ($badge !== ""): ?>
                        <span class="home-product-badge"><?= htmlspecialchars($badge, ENT_QUOTES, "UTF-8") ?></span>
                    <?php elseif ($price < $originalPrice): ?>
                        <span class="home-product-badge">Ưu đãi</span>
                    <?php endif; ?>
                    <?php if (!empty($product["image"])): ?>
                        <img
                            src="<?= htmlspecialchars(productImageUrl($product["image"]), ENT_QUOTES, "UTF-8") ?>"
                            alt="<?= htmlspecialchars($product["name"], ENT_QUOTES, "UTF-8") ?>"
                            loading="lazy"
                        >
                    <?php else: ?>
                        <span class="home-product-placeholder" aria-hidden="true">LAPTOP</span>
                    <?php endif; ?>
                </a>
                <div class="home-product-info">
                    <span class="home-product-brand">
                        <?= htmlspecialchars($product["brand_name"] ?? "", ENT_QUOTES, "UTF-8") ?>
                    </span>
                    <h3>
                        <a href="<?= BASE_URL ?>index.php?controller=product&action=detail&id=<?= (int) $product["id"] ?>">
                            <?= htmlspecialchars($product["name"], ENT_QUOTES, "UTF-8") ?>
                        </a>
                    </h3>
                    <div class="home-product-specs">
                        <?php if (!empty($product["cpu"])): ?>
                            <span><?= htmlspecialchars($product["cpu"], ENT_QUOTES, "UTF-8") ?></span>
                        <?php endif; ?>
                        <?php if (!empty($product["ram"])): ?>
                            <span><?= htmlspecialchars($product["ram"], ENT_QUOTES, "UTF-8") ?> RAM</span>
                        <?php endif; ?>
                        <?php if (!empty($product["storage"])): ?>
                            <span><?= htmlspecialchars($product["storage"], ENT_QUOTES, "UTF-8") ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="home-product-bottom">
                        <div>
                            <span class="home-product-price"><?= number_format($price, 0, ",", ".") ?>₫</span>
                            <?php if ($price < $originalPrice): ?>
                                <del><?= number_format($originalPrice, 0, ",", ".") ?>₫</del>
                            <?php endif; ?>
                        </div>
                        <a
                            class="home-product-arrow"
                            href="<?= BASE_URL ?>index.php?controller=product&action=detail&id=<?= (int) $product["id"] ?>"
                            aria-label="Xem chi tiết sản phẩm"
                        >↗</a>
                    </div>
                </div>
            </article>
            <?php
        endwhile;
    else:
        ?>
        <div class="home-empty-state">
            Chưa có sản phẩm trong danh mục này. Hãy quay lại sau nhé.
        </div>
        <?php
    endif;
};
?>

<main class="home-page">
    <section class="home-hero">
        <div class="home-hero-glow home-hero-glow-one" aria-hidden="true"></div>
        <div class="home-hero-glow home-hero-glow-two" aria-hidden="true"></div>
        <div class="home-container home-hero-inner">
            <div class="home-hero-copy" data-home-reveal>
                <span class="home-eyebrow"><span></span> LAPTOP CHÍNH HÃNG · BẢO HÀNH TẬN TÂM</span>
                <h1>Chiếc laptop<br>tốt mở lối <em>tương lai.</em></h1>
                <p>
                    Chọn đúng thiết bị cho công việc, học tập và những ý tưởng lớn.
                    Công nghệ xịn, giá hợp lý, hỗ trợ bạn từ lúc chọn mua.
                </p>
                <div class="home-hero-actions">
                    <a class="home-button home-button-primary" href="<?= BASE_URL ?>index.php?controller=product&action=index">
                        Khám phá laptop <span aria-hidden="true">↗</span>
                    </a>
                    <a class="home-button home-button-quiet" href="#home-featured">
                        Xem sản phẩm nổi bật <span aria-hidden="true">↓</span>
                    </a>
                </div>
                <div class="home-hero-note">
                    <span class="home-hero-note-icon" aria-hidden="true">✓</span>
                    Tư vấn thật tâm · Giao hàng toàn quốc
                </div>
            </div>

            <div class="home-hero-art" aria-hidden="true" data-home-reveal>
                <div class="home-art-orbit home-art-orbit-one"></div>
                <div class="home-art-orbit home-art-orbit-two"></div>
                <div class="home-art-chip home-art-chip-top">HIỆU NĂNG<br><strong>VƯỢT TRỘI</strong></div>
                <div class="home-art-chip home-art-chip-bottom"><span>✦</span> Sẵn sàng bứt phá</div>
                <div class="home-laptop">
                    <div class="home-laptop-screen">
                        <div class="home-screen-bar"><span></span><span></span><span></span></div>
                        <div class="home-screen-content">
                            <span class="home-screen-kicker">YOUR NEXT MOVE</span>
                            <strong>MAKE<br>IT<br><i>HAPPEN.</i></strong>
                            <span class="home-screen-line"></span>
                        </div>
                        <span class="home-screen-camera"></span>
                    </div>
                    <div class="home-laptop-base"><span></span></div>
                    <div class="home-laptop-shadow"></div>
                </div>
            </div>
        </div>
        <div class="home-hero-bottom">
            <div class="home-container home-hero-bottom-inner">
                <span>THIẾT BỊ CHO MỌI HÀNH TRÌNH</span>
                <span class="home-scroll-cue"><i></i> CUỘN ĐỂ KHÁM PHÁ</span>
            </div>
        </div>
    </section>

    <section class="home-benefits" aria-label="Lý do chọn Laptop Store">
        <div class="home-container home-benefit-grid">
            <article class="home-benefit">
                <span class="home-benefit-icon" aria-hidden="true">◇</span>
                <div><strong>Chính hãng 100%</strong><small>Nguồn gốc rõ ràng</small></div>
            </article>
            <article class="home-benefit">
                <span class="home-benefit-icon" aria-hidden="true">↗</span>
                <div><strong>Giá tốt mỗi ngày</strong><small>Nhiều lựa chọn phù hợp</small></div>
            </article>
            <article class="home-benefit">
                <span class="home-benefit-icon" aria-hidden="true">♡</span>
                <div><strong>Bảo hành tận tâm</strong><small>Đồng hành sau khi mua</small></div>
            </article>
            <article class="home-benefit">
                <span class="home-benefit-icon" aria-hidden="true">⌖</span>
                <div><strong>Giao hàng toàn quốc</strong><small>An tâm nhận hàng tại nhà</small></div>
            </article>
        </div>
    </section>

    <section class="home-section home-featured" id="home-featured">
        <div class="home-container">
            <div class="home-section-heading" data-home-reveal>
                <div>
                    <span class="home-section-kicker">CHỌN LỌC DÀNH CHO BẠN</span>
                    <h2>Laptop nổi bật</h2>
                    <p>Những lựa chọn đáng cân nhắc cho hành trình tiếp theo của bạn.</p>
                </div>
                <div class="home-section-heading-actions">
                    <a class="home-text-link" href="<?= BASE_URL ?>index.php?controller=product&action=index">
                        Xem tất cả sản phẩm <span aria-hidden="true">↗</span>
                    </a>
                    <div class="home-carousel-controls" data-home-carousel-controls="featured" aria-label="Điều hướng sản phẩm nổi bật">
                        <button type="button" data-home-carousel-prev="featured" aria-controls="home-featured-carousel" aria-label="Xem sản phẩm nổi bật trước đó" disabled>←</button>
                        <button type="button" data-home-carousel-next="featured" aria-controls="home-featured-carousel" aria-label="Xem sản phẩm nổi bật tiếp theo">→</button>
                    </div>
                </div>
            </div>
            <div class="home-carousel">
                <div class="home-carousel-viewport" id="home-featured-carousel" data-home-carousel="featured" aria-label="Danh sách sản phẩm nổi bật">
                    <div class="home-product-grid home-carousel-track">
                        <?php $renderProducts($featuredProducts, "NỔI BẬT"); ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="home-promo">
        <div class="home-container">
            <div class="home-promo-card" data-home-reveal>
                <div class="home-promo-copy">
                    <span class="home-section-kicker">TÌM ĐÚNG LAPTOP, ĐÚNG NHU CẦU</span>
                    <h2>Một lựa chọn tốt<br>bắt đầu từ <em>đúng tư vấn.</em></h2>
                    <p>Dù bạn học tập, làm việc hay sáng tạo, hãy bắt đầu với những mẫu máy đang được khách hàng yêu thích.</p>
                    <a class="home-button home-button-light" href="<?= BASE_URL ?>index.php?controller=product&action=index">
                        Tìm laptop phù hợp <span aria-hidden="true">↗</span>
                    </a>
                </div>
                <div class="home-promo-mark" aria-hidden="true">
                    <span>LS</span>
                    <i></i><i></i><i></i>
                </div>
            </div>
        </div>
    </section>

    <section class="home-section home-bestsellers">
        <div class="home-container">
            <div class="home-section-heading" data-home-reveal>
                <div>
                    <span class="home-section-kicker">ĐƯỢC KHÁCH HÀNG YÊU THÍCH</span>
                    <h2>Sản phẩm bán chạy</h2>
                    <p>Những mẫu laptop được nhiều người lựa chọn tại Laptop Store.</p>
                </div>
                <div class="home-section-heading-actions">
                    <a class="home-text-link" href="<?= BASE_URL ?>index.php?controller=product&action=index">
                        Khám phá thêm <span aria-hidden="true">↗</span>
                    </a>
                    <div class="home-carousel-controls" data-home-carousel-controls="bestsellers" aria-label="Điều hướng sản phẩm bán chạy">
                        <button type="button" data-home-carousel-prev="bestsellers" aria-controls="home-bestsellers-carousel" aria-label="Xem sản phẩm bán chạy trước đó" disabled>←</button>
                        <button type="button" data-home-carousel-next="bestsellers" aria-controls="home-bestsellers-carousel" aria-label="Xem sản phẩm bán chạy tiếp theo">→</button>
                    </div>
                </div>
            </div>
            <div class="home-carousel">
                <div class="home-carousel-viewport" id="home-bestsellers-carousel" data-home-carousel="bestsellers" aria-label="Danh sách sản phẩm bán chạy">
                    <div class="home-product-grid home-carousel-track">
                        <?php $renderProducts($bestSellerProducts, "BÁN CHẠY"); ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="home-section home-news">
        <div class="home-container">
            <div class="home-section-heading" data-home-reveal>
                <div>
                    <span class="home-section-kicker">GÓC CÔNG NGHỆ</span>
                    <h2>Mới nhất từ Laptop Store</h2>
                    <p>Cập nhật kiến thức và tin tức hữu ích trước khi chọn mua.</p>
                </div>
                <div class="home-section-heading-actions">
                    <a class="home-text-link" href="<?= BASE_URL ?>index.php?controller=post&action=index">
                        Tất cả bài viết <span aria-hidden="true">↗</span>
                    </a>
                    <div class="home-carousel-controls" data-home-carousel-controls="news" aria-label="Điều hướng bài viết mới">
                        <button type="button" data-home-carousel-prev="news" aria-controls="home-news-carousel" aria-label="Xem bài viết trước đó" disabled>←</button>
                        <button type="button" data-home-carousel-next="news" aria-controls="home-news-carousel" aria-label="Xem bài viết tiếp theo">→</button>
                    </div>
                </div>
            </div>
            <div class="home-carousel">
                <div class="home-carousel-viewport" id="home-news-carousel" data-home-carousel="news" aria-label="Danh sách bài viết mới">
                    <div class="home-news-grid home-carousel-track">
                        <?php if (!empty($latestPosts)): ?>
                            <?php foreach ($latestPosts as $post): ?>
                                <?php
                                $excerpt = trim(strip_tags($post["content"] ?? ""));
                                $excerptLength = mb_strlen($excerpt);
                                ?>
                                <article class="home-news-card" data-home-reveal>
                                    <a
                                        class="home-news-image"
                                        href="<?= BASE_URL ?>index.php?controller=post&action=detail&id=<?= (int) $post["id"] ?>"
                                    >
                                        <?php if (!empty($post["image"])): ?>
                                            <img
                                                src="<?= BASE_URL . htmlspecialchars($post["image"], ENT_QUOTES, "UTF-8") ?>"
                                                alt="<?= htmlspecialchars($post["title"], ENT_QUOTES, "UTF-8") ?>"
                                                loading="lazy"
                                            >
                                        <?php else: ?>
                                            <span aria-hidden="true">LAPTOP STORE</span>
                                        <?php endif; ?>
                                    </a>
                                    <div class="home-news-content">
                                        <span class="home-news-meta">
                                            <?= htmlspecialchars(date("d/m/Y", strtotime($post["created_at"])), ENT_QUOTES, "UTF-8") ?>
                                            <span aria-hidden="true">·</span>
                                            <?= htmlspecialchars($post["author_name"] ?? "Laptop Store", ENT_QUOTES, "UTF-8") ?>
                                        </span>
                                        <h3>
                                            <a href="<?= BASE_URL ?>index.php?controller=post&action=detail&id=<?= (int) $post["id"] ?>">
                                                <?= htmlspecialchars($post["title"], ENT_QUOTES, "UTF-8") ?>
                                            </a>
                                        </h3>
                                        <p>
                                            <?= htmlspecialchars(mb_substr($excerpt, 0, 115), ENT_QUOTES, "UTF-8") ?><?= $excerptLength > 115 ? "…" : "" ?>
                                        </p>
                                        <a class="home-news-link" href="<?= BASE_URL ?>index.php?controller=post&action=detail&id=<?= (int) $post["id"] ?>">
                                            Đọc bài viết <span aria-hidden="true">→</span>
                                        </a>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="home-empty-state">Chưa có bài viết mới. Nội dung hữu ích sẽ được cập nhật sớm.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="home-final-cta">
        <div class="home-container home-final-cta-inner">
            <div>
                <span class="home-section-kicker">SẴN SÀNG CHO BƯỚC TIẾP THEO?</span>
                <h2>Tìm chiếc laptop dành riêng cho bạn.</h2>
            </div>
            <a class="home-button home-button-primary" href="<?= BASE_URL ?>index.php?controller=product&action=index">
                Xem toàn bộ sản phẩm <span aria-hidden="true">↗</span>
            </a>
        </div>
    </section>
</main>

<?php require_once __DIR__ . "/../layouts/footer.php"; ?>
