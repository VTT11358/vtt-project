<?php

require_once "config/config.php";
require_once "config/vnpay.php";
require_once "config/database.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/*
|--------------------------------------------------------------------------
| Lấy controller
|--------------------------------------------------------------------------
*/

$controllerName = $_GET['controller'] ?? 'home';


/*
|--------------------------------------------------------------------------
| Lấy action
|--------------------------------------------------------------------------
*/

$action = $_GET['action'] ?? 'index';


/*
|--------------------------------------------------------------------------
| Xử lý Controller
|--------------------------------------------------------------------------
*/

switch ($controllerName) {

    case 'product':

        require_once "app/controllers/ProductController.php";

        $controller = new ProductController($conn);

        break;

    case 'cart':

        require_once "app/controllers/CartController.php";

        $controller = new CartController($conn);

        break;


    case 'home':

        require_once "app/controllers/HomeController.php";

        $controller = new HomeController($conn);

        break;

    case 'post':

        require_once "app/controllers/PostController.php";

        $controller = new PostController($conn);

        break;

    case 'auth':

        require_once "app/controllers/AuthController.php";

        $controller = new AuthController($conn);

        break;
        
    case 'admin':

        require_once "app/controllers/AdminController.php";

        $controller = new AdminController($conn);

        break;

    case 'adminProduct':

        require_once "app/controllers/AdminProductController.php";

        $controller = new AdminProductController($conn);

        break;

    case 'adminCategory':

        require_once "app/controllers/AdminCategoryController.php";

        $controller = new AdminCategoryController($conn);

        break;

    case 'adminBrands':

        require_once "app/controllers/AdminBrandsController.php";

        $controller = new AdminBrandsController($conn);

        break;

    case 'adminUser':

        require_once "app/controllers/AdminUserController.php";

        $controller = new AdminUserController($conn);

        break;

    case 'adminPost':

        require_once "app/controllers/AdminPostController.php";

        $controller = new AdminPostController($conn);

        break;

    case 'adminOrder':

        require_once "app/controllers/AdminOrderController.php";

        $controller = new AdminOrderController($conn);

        break;

    case 'checkout':

        require_once "app/controllers/CheckoutController.php";

        $controller = new CheckoutController($conn);

        break;

    case 'order':

        require_once "app/controllers/OrderController.php";

        $controller = new OrderController($conn);

        break;

    case 'profile':

        require_once "app/controllers/ProfileController.php";

        $controller = new ProfileController($conn);

        break;


    default:

        die("Controller không tồn tại.");

}


/*
|--------------------------------------------------------------------------
| Kiểm tra action
|--------------------------------------------------------------------------
*/

if (!method_exists($controller, $action)) {

    die("Action không tồn tại.");

}

if (!empty($_SESSION["user"]["id"]) && !in_array($controllerName, ["cart", "checkout"], true)) {
    require_once "app/models/Cart.php";
    (new Cart($conn))->getCount();
}


/*
|--------------------------------------------------------------------------
| Chạy action
|--------------------------------------------------------------------------
*/

$controller->$action();