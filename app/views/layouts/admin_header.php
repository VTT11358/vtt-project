<?php
$adminController = (string) ($_GET["controller"] ?? "adminOrder");
$adminAction = (string) ($_GET["action"] ?? "stats");
$adminNavKey = $adminController . ":" . $adminAction;
$adminNavItems = [
    "adminOrder:stats" => [
        "label" => "Thống kê",
        "href" => BASE_URL . "index.php?controller=adminOrder&action=stats",
        "icon" => "▦",
    ],
    "adminOrder:index" => [
        "label" => "Đơn hàng",
        "href" => BASE_URL . "index.php?controller=adminOrder&action=index",
        "icon" => "▤",
    ],
    "adminProduct:products" => [
        "label" => "Sản phẩm",
        "href" => BASE_URL . "index.php?controller=adminProduct&action=products",
        "icon" => "▣",
    ],
    "adminProduct:inventory" => [
        "label" => "Tồn kho",
        "href" => BASE_URL . "index.php?controller=adminProduct&action=inventory",
        "icon" => "▧",
    ],
    "adminCategory:index" => [
        "label" => "Danh mục",
        "href" => BASE_URL . "index.php?controller=adminCategory&action=index",
        "icon" => "▰",
    ],
    "adminBrands:index" => [
        "label" => "Thương hiệu",
        "href" => BASE_URL . "index.php?controller=adminBrands&action=index",
        "icon" => "◇",
    ],
    "adminUser:index" => [
        "label" => "Người dùng",
        "href" => BASE_URL . "index.php?controller=adminUser&action=index",
        "icon" => "♙",
    ],
    "adminPost:index" => [
        "label" => "Bài viết",
        "href" => BASE_URL . "index.php?controller=adminPost&action=index",
        "icon" => "▧",
    ],
];
if ($adminController === "adminProduct") {
    $adminNavKey = $adminAction === "inventory"
        ? "adminProduct:inventory"
        : "adminProduct:products";
} elseif ($adminController === "adminOrder") {
    $adminNavKey = $adminAction === "stats"
        ? "adminOrder:stats"
        : "adminOrder:index";
} elseif (isset($adminNavItems[$adminController . ":index"])) {
    $adminNavKey = $adminController . ":index";
}
$adminPageTitle = $adminNavItems[$adminNavKey]["label"] ?? "Quản trị";
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($adminPageTitle, ENT_QUOTES, "UTF-8") ?> | Laptop Store Admin</title>
    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>assets/css/style.css?v=<?= filemtime(dirname(__DIR__, 3) . '/assets/css/style.css') ?>"
    >
    <?php if ($adminController === "adminOrder" && $adminAction === "stats"): ?>
        <link
            rel="stylesheet"
            href="<?= BASE_URL ?>assets/css/pages/order-stats.css?v=<?= filemtime(dirname(__DIR__, 3) . '/assets/css/pages/order-stats.css') ?>"
        >
    <?php endif; ?>
    <?php if ($adminController === "adminOrder" && in_array($adminAction, ["index", "show"], true)): ?>
        <link
            rel="stylesheet"
            href="<?= BASE_URL ?>assets/css/pages/order-management.css?v=<?= filemtime(dirname(__DIR__, 3) . '/assets/css/pages/order-management.css') ?>"
        >
    <?php endif; ?>
    <?php if ($adminController === "adminProduct"): ?>
        <link
            rel="stylesheet"
            href="<?= BASE_URL ?>assets/css/pages/product-management.css?v=<?= filemtime(dirname(__DIR__, 3) . '/assets/css/pages/product-management.css') ?>"
        >
    <?php endif; ?>
    <?php if (in_array($adminController, ["adminCategory", "adminBrands"], true)): ?>
        <link
            rel="stylesheet"
            href="<?= BASE_URL ?>assets/css/pages/category-management.css?v=<?= filemtime(dirname(__DIR__, 3) . '/assets/css/pages/category-management.css') ?>"
        >
    <?php endif; ?>
    <?php if ($adminController === "adminUser"): ?>
        <link
            rel="stylesheet"
            href="<?= BASE_URL ?>assets/css/pages/user-management.css?v=<?= filemtime(dirname(__DIR__, 3) . '/assets/css/pages/user-management.css') ?>"
        >
    <?php endif; ?>
    <?php if ($adminController === "adminPost"): ?>
        <link
            rel="stylesheet"
            href="<?= BASE_URL ?>assets/css/pages/post-management.css?v=<?= filemtime(dirname(__DIR__, 3) . '/assets/css/pages/post-management.css') ?>"
        >
    <?php endif; ?>
</head>
<body class="admin-body">
    <div class="admin-shell">
        <aside class="admin-sidebar" id="adminSidebar">
            <a class="admin-brand" href="<?= BASE_URL ?>index.php?controller=admin&action=index">
                <span class="admin-brand-mark">L</span>
                <span>Laptop Store <small>ADMIN</small></span>
            </a>

            <p class="admin-nav-heading">TỔNG QUAN</p>
            <nav class="admin-nav" aria-label="Điều hướng quản trị">
                <?php foreach ($adminNavItems as $navKey => $item): ?>
                    <?php
                    $active = $adminNavKey === $navKey;
                    ?>
                    <a class="admin-nav-link<?= $active ? " is-active" : "" ?>"
                        href="<?= htmlspecialchars($item["href"], ENT_QUOTES, "UTF-8") ?>"
                        <?= $active ? 'aria-current="page"' : "" ?>>
                        <span class="admin-nav-icon" aria-hidden="true"><?= $item["icon"] ?></span>
                        <span><?= htmlspecialchars($item["label"], ENT_QUOTES, "UTF-8") ?></span>
                    </a>
                <?php endforeach; ?>
            </nav>

            <div class="admin-sidebar-bottom">
                <a class="admin-nav-link" href="<?= BASE_URL ?>">
                    <span class="admin-nav-icon" aria-hidden="true">↗</span>
                    <span>Xem cửa hàng</span>
                </a>
                <form method="POST" action="<?= BASE_URL ?>index.php?controller=auth&action=logout">
                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= htmlspecialchars($_SESSION["csrf_token"] ?? "", ENT_QUOTES, "UTF-8") ?>"
                    >
                    <button class="admin-nav-link admin-logout" type="submit">
                        <span class="admin-nav-icon" aria-hidden="true">⇥</span>
                        <span>Đăng xuất</span>
                    </button>
                </form>
            </div>
        </aside>

        <div class="admin-workspace">
            <header class="admin-topbar">
                <button class="admin-menu-toggle" type="button" aria-controls="adminSidebar"
                    aria-expanded="false" aria-label="Mở menu quản trị" data-admin-menu-toggle>
                    <span></span><span></span><span></span>
                </button>
                <div class="admin-topbar-title"><?= htmlspecialchars($adminPageTitle, ENT_QUOTES, "UTF-8") ?></div>
                <div class="admin-user">
                    <span class="admin-user-avatar" aria-hidden="true">
                        <?= htmlspecialchars(mb_substr($_SESSION["user"]["name"] ?? "A", 0, 1), ENT_QUOTES, "UTF-8") ?>
                    </span>
                    <span><?= htmlspecialchars($_SESSION["user"]["name"] ?? "Quản trị viên", ENT_QUOTES, "UTF-8") ?></span>
                </div>
            </header>
            <main class="admin-main">
