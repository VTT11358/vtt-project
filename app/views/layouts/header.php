<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$isHomePage = ($_GET["controller"] ?? "home") === "home"
    && ($_GET["action"] ?? "index") === "index";
$isProductListPage = ($_GET["controller"] ?? "") === "product"
    && ($_GET["action"] ?? "index") === "index";
$isProductDetailPage = ($_GET["controller"] ?? "") === "product"
    && ($_GET["action"] ?? "") === "detail";
$isPostListPage = ($_GET["controller"] ?? "") === "post"
    && ($_GET["action"] ?? "index") === "index";
$isPostDetailPage = ($_GET["controller"] ?? "") === "post"
    && ($_GET["action"] ?? "") === "detail";
$isCartPage = ($_GET["controller"] ?? "") === "cart"
    && ($_GET["action"] ?? "index") === "index";
$isCheckoutPage = ($_GET["controller"] ?? "") === "checkout";
$isOrderPage = ($_GET["controller"] ?? "") === "order";
$isProfilePage = ($_GET["controller"] ?? "") === "profile";
$isAuthPage = ($_GET["controller"] ?? "") === "auth"
    && in_array(
        $_GET["action"] ?? "",
        ["login", "register", "forgotPassword", "resetPassword"],
        true
    );

?>

<!DOCTYPE html>

<html lang="vi">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title><?= htmlspecialchars($pageTitle ?? "Laptop Store", ENT_QUOTES, "UTF-8") ?></title>


    <!-- CSS -->

    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>assets/css/style.css?v=<?= filemtime(dirname(__DIR__, 3) . '/assets/css/style.css') ?>"
    >
    <?php if ($isHomePage || $isPostListPage || $isPostDetailPage || $isCartPage || $isCheckoutPage || $isOrderPage || $isAuthPage || $isProfilePage): ?>
        <link
            rel="stylesheet"
            href="<?= BASE_URL ?>assets/css/pages/home.css?v=<?= filemtime(dirname(__DIR__, 3) . '/assets/css/pages/home.css') ?>"
        >
    <?php endif; ?>
    <?php if ($isProductListPage || $isProductDetailPage): ?>
        <link
            rel="stylesheet"
            href="<?= BASE_URL ?>assets/css/pages/products.css?v=<?= filemtime(dirname(__DIR__, 3) . '/assets/css/pages/products.css') ?>"
        >
    <?php endif; ?>
    <?php if ($isProductDetailPage): ?>
        <link
            rel="stylesheet"
            href="<?= BASE_URL ?>assets/css/pages/product-detail.css?v=<?= filemtime(dirname(__DIR__, 3) . '/assets/css/pages/product-detail.css') ?>"
        >
    <?php endif; ?>
    <?php if ($isPostListPage): ?>
        <link
            rel="stylesheet"
            href="<?= BASE_URL ?>assets/css/pages/posts.css?v=<?= filemtime(dirname(__DIR__, 3) . '/assets/css/pages/posts.css') ?>"
        >
    <?php endif; ?>
    <?php if ($isPostDetailPage): ?>
        <link
            rel="stylesheet"
            href="<?= BASE_URL ?>assets/css/pages/post-detail.css?v=<?= filemtime(dirname(__DIR__, 3) . '/assets/css/pages/post-detail.css') ?>"
        >
    <?php endif; ?>
    <?php if ($isCartPage): ?>
        <link
            rel="stylesheet"
            href="<?= BASE_URL ?>assets/css/pages/cart.css?v=<?= filemtime(dirname(__DIR__, 3) . '/assets/css/pages/cart.css') ?>"
        >
    <?php endif; ?>
    <?php if ($isCheckoutPage): ?>
        <link
            rel="stylesheet"
            href="<?= BASE_URL ?>assets/css/pages/checkout.css?v=<?= filemtime(dirname(__DIR__, 3) . '/assets/css/pages/checkout.css') ?>"
        >
    <?php endif; ?>
    <?php if ($isOrderPage): ?>
        <link
            rel="stylesheet"
            href="<?= BASE_URL ?>assets/css/pages/orders.css?v=<?= filemtime(dirname(__DIR__, 3) . '/assets/css/pages/orders.css') ?>"
        >
    <?php endif; ?>
    <?php if ($isAuthPage): ?>
        <link
            rel="stylesheet"
            href="<?= BASE_URL ?>assets/css/pages/auth.css?v=<?= filemtime(dirname(__DIR__, 3) . '/assets/css/pages/auth.css') ?>"
        >
    <?php endif; ?>
    <?php if ($isProfilePage): ?>
        <link
            rel="stylesheet"
            href="<?= BASE_URL ?>assets/css/pages/profile.css?v=<?= filemtime(dirname(__DIR__, 3) . '/assets/css/pages/profile.css') ?>"
        >
    <?php endif; ?>

</head>


<body class="<?= $isHomePage || $isPostListPage || $isPostDetailPage || $isCartPage || $isCheckoutPage || $isOrderPage || $isAuthPage || $isProfilePage
    ? "home-site-body"
    : (($isProductListPage || $isProductDetailPage) ? "catalog-site-body" : "") ?>">


<!-- TOP BAR -->

<div class="storefront-header">

<div class="top-bar">

    <div class="container">

        <div class="d-flex justify-content-between">

            <div>

                <span class="icon" aria-hidden="true">☎</span>

                Hotline: 0123 456 789

            </div>


            <div>

                Laptop chính hãng - Giá tốt mỗi ngày

            </div>

        </div>

    </div>

</div>


<!-- HEADER -->

<header class="main-header">

    <div class="container">

        <div class="row align-items-center g-3">


            <!-- LOGO -->

            <div class="col-md-3">

                <a
                    href="<?= BASE_URL ?>"
                    class="logo"
                >

                    LAPTOP STORE

                </a>

            </div>


            <!-- SEARCH -->

            <div class="col-md-6">

                <form
                    action="<?= BASE_URL ?>index.php"
                    method="GET"
                >

                    <input
                        type="hidden"
                        name="controller"
                        value="product"
                    >

                    <input
                        type="hidden"
                        name="action"
                        value="index"
                    >


                    <div class="search-box">

                        <input
                            type="text"
                            name="search"
                            placeholder="Tìm kiếm laptop..."
                            aria-label="Tìm kiếm laptop"
                            value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"
                        >


                        <button type="submit" aria-label="Tìm kiếm">
                            <svg class="search-box-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                <circle cx="10.8" cy="10.8" r="6.6"></circle>
                                <path d="m16 16 4.2 4.2"></path>
                            </svg>
                        </button>

                    </div>

                </form>

            </div>


            <!-- ACTION -->

            <div class="col-md-3">

                <div class="header-actions">


                    <?php if (isset($_SESSION['user'])): ?>
    <?php
    $headerUserName = (string) ($_SESSION["user"]["name"] ?? "Tài khoản");
    $headerUserAvatar = (string) ($_SESSION["user"]["avatar"] ?? "");
    $headerUserRole = $_SESSION["user"]["role"] ?? "";
    ?>
    <details class="header-account-menu" data-account-menu>
        <summary class="header-account-trigger" aria-label="Mở menu tài khoản">
            <span class="header-account-avatar" aria-hidden="true">
                <?php if ($headerUserAvatar !== ""): ?>
                    <img
                        src="<?= htmlspecialchars($headerUserAvatar, ENT_QUOTES, "UTF-8") ?>"
                        alt=""
                        referrerpolicy="no-referrer"
                        data-account-avatar-image
                        data-default-avatar="<?= BASE_URL ?>assets/images/default-avatar.svg"
                    >
                <?php else: ?>
                    <img
                        src="<?= BASE_URL ?>assets/images/default-avatar.svg"
                        alt=""
                        data-account-avatar-image
                        data-default-avatar="<?= BASE_URL ?>assets/images/default-avatar.svg"
                    >
                <?php endif; ?>
            </span>
            <span class="header-account-name"><?= htmlspecialchars($headerUserName, ENT_QUOTES, "UTF-8") ?></span>
            <span class="header-account-chevron" aria-hidden="true"></span>
        </summary>

        <div class="header-account-dropdown" id="header-account-dropdown">
            <div class="header-account-dropdown-heading">
                <a
                    class="header-account-dropdown-name"
                    href="<?= BASE_URL ?>index.php?controller=profile&action=index"
                ><?= htmlspecialchars($headerUserName, ENT_QUOTES, "UTF-8") ?></a>
                <a
                    class="header-account-dropdown-email"
                    href="<?= BASE_URL ?>index.php?controller=profile&action=index"
                ><?= htmlspecialchars($_SESSION["user"]["email"] ?? "", ENT_QUOTES, "UTF-8") ?></a>
            </div>
            <?php if ($headerUserRole === "admin"): ?>
                <a
                    href="<?= BASE_URL ?>index.php?controller=admin&action=index"
                    class="header-account-dropdown-link header-account-dropdown-admin"
                >
                    <span aria-hidden="true">▦</span> Trang quản trị
                </a>
            <?php else: ?>
                <a
                    href="<?= BASE_URL ?>index.php?controller=order&action=index"
                    class="header-account-dropdown-link"
                >
                    <span aria-hidden="true">▤</span> Đơn hàng của tôi
                </a>
            <?php endif; ?>
            <form
                method="POST"
                action="<?= BASE_URL ?>index.php?controller=auth&action=logout"
                class="header-account-dropdown-logout-form"
            >
                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars($_SESSION["csrf_token"] ?? "", ENT_QUOTES, "UTF-8") ?>"
                >
                <button class="header-account-dropdown-link header-account-dropdown-logout" type="submit">
                    <span aria-hidden="true">↪</span> Đăng xuất
                </button>
            </form>
        </div>
    </details>

<?php else: ?>

    <a href="<?= BASE_URL ?>index.php?controller=auth&action=login"
       class="header-auth-link header-auth-login">
        Đăng nhập
    </a>

    <a href="<?= BASE_URL ?>index.php?controller=auth&action=register"
       class="header-auth-link header-auth-register">
        Đăng ký
    </a>

<?php endif; ?>


                    <a class="header-cart-link" href="<?= BASE_URL ?>index.php?controller=cart&action=index">

                        <span class="icon" aria-hidden="true">🛒</span>

                        Giỏ hàng
                        <?php
                        $cartCount = array_sum(
                            array_map("intval", $_SESSION["cart"] ?? [])
                        );
                        ?>
                        <span class="badge bg-danger"><?= $cartCount ?></span>

                    </a>


                </div>

            </div>

        </div>

    </div>

</header>


<!-- NAVBAR -->

<nav class="site-nav">

    <div class="container">


        <button
            class="menu-toggle"
            type="button"
            aria-controls="mainMenu"
            aria-expanded="false"
            aria-label="Mở menu"
        >
            <span></span>
            <span></span>
            <span></span>
        </button>


        <div
            class="main-menu"
            id="mainMenu"
        >

            <ul class="menu-list">


                <li class="menu-item">

                    <a
                        class="menu-link"
                        href="<?= BASE_URL ?>"
                    >
                        Trang chủ
                    </a>

                </li>


                <li class="menu-item">

                    <a
                        class="menu-link"
                        href="<?= BASE_URL ?>index.php?controller=product&action=index"
                    >
                        Sản phẩm
                    </a>

                </li>


                <li class="menu-item">

                    <a
                        class="menu-link"
                        href="<?= BASE_URL ?>index.php?controller=post&action=index"
                    >
                        Tin tức
                    </a>

                </li>


                <li class="menu-item">

                    <a
                        class="menu-link"
                        href="#"
                    >
                        Giới thiệu
                    </a>

                </li>


                <li class="menu-item">

                    <a
                        class="menu-link"
                        href="#"
                    >
                        Liên hệ
                    </a>

                </li>


            </ul>

        </div>

    </div>

</nav>

</div>