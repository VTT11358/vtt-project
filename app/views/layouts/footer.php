<footer class="footer">

    <div class="container">

        <div class="row">


            <div class="col-md-4">

                <h4>LAPTOP STORE</h4>

                <p>

                    Website bán laptop chính hãng,
                    cung cấp các sản phẩm laptop
                    phục vụ học tập, văn phòng,
                    gaming và đồ họa.

                </p>

            </div>


            <div class="col-md-4">

                <h4>LIÊN KẾT</h4>

                <ul class="footer-links">

                    <li>

                        <a href="<?= BASE_URL ?>">

                            Trang chủ

                        </a>

                    </li>


                    <li>

                        <a
                            href="<?= BASE_URL ?>index.php?controller=product&action=index"
                        >

                            Sản phẩm

                        </a>

                    </li>


                    <li>

                        <a href="#">

                            Tin tức

                        </a>

                    </li>


                    <li>

                        <a href="#">

                            Giới thiệu

                        </a>

                    </li>


                    <li>

                        <a href="#">

                            Liên hệ

                        </a>

                    </li>

                </ul>

            </div>


            <div class="col-md-4">

                <h4>LIÊN HỆ</h4>

                <p>

                    <span class="icon" aria-hidden="true">⌖</span>

                    TP. Hồ Chí Minh

                </p>


                <p>

                    <span class="icon" aria-hidden="true">☎</span>

                    0123 456 789

                </p>


                <p>

                    <span class="icon" aria-hidden="true">✉</span>

                    contact@laptopstore.com

                </p>

            </div>


        </div>


        <hr>


        <div class="text-center">

            <p class="mb-0">

                © <?= date('Y') ?>

                Laptop Store.

                All Rights Reserved.

            </p>

        </div>

    </div>

</footer>


<script src="<?= BASE_URL ?>assets/js/main.js" defer></script>
<?php if (($_GET["controller"] ?? "home") === "home" && ($_GET["action"] ?? "index") === "index"): ?>
    <script src="<?= BASE_URL ?>assets/js/pages/home.js?v=<?= filemtime(dirname(__DIR__, 3) . '/assets/js/pages/home.js') ?>" defer></script>
<?php endif; ?>
<?php if (($_GET["controller"] ?? "") === "product" && ($_GET["action"] ?? "index") === "index"): ?>
    <script src="<?= BASE_URL ?>assets/js/pages/products.js?v=<?= filemtime(dirname(__DIR__, 3) . '/assets/js/pages/products.js') ?>" defer></script>
<?php endif; ?>
<?php if (($_GET["controller"] ?? "") === "product" && ($_GET["action"] ?? "") === "detail"): ?>
    <script src="<?= BASE_URL ?>assets/js/pages/product-detail.js?v=<?= filemtime(dirname(__DIR__, 3) . '/assets/js/pages/product-detail.js') ?>" defer></script>
<?php endif; ?>
<?php if (($_GET["controller"] ?? "") === "post" && ($_GET["action"] ?? "index") === "index"): ?>
    <script src="<?= BASE_URL ?>assets/js/pages/posts.js?v=<?= filemtime(dirname(__DIR__, 3) . '/assets/js/pages/posts.js') ?>" defer></script>
<?php endif; ?>
<?php if (($_GET["controller"] ?? "") === "post" && ($_GET["action"] ?? "") === "detail"): ?>
    <script src="<?= BASE_URL ?>assets/js/pages/post-detail.js?v=<?= filemtime(dirname(__DIR__, 3) . '/assets/js/pages/post-detail.js') ?>" defer></script>
<?php endif; ?>
<?php if (($_GET["controller"] ?? "") === "cart" && ($_GET["action"] ?? "index") === "index"): ?>
    <script src="<?= BASE_URL ?>assets/js/pages/cart.js?v=<?= filemtime(dirname(__DIR__, 3) . '/assets/js/pages/cart.js') ?>" defer></script>
<?php endif; ?>


</body>

</html>