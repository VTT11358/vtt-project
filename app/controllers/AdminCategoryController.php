
<?php

require_once __DIR__ . "/AdminCatalogController.php";

class AdminCategoryController extends AdminCatalogController
{
    protected string $type = "category";
    protected string $viewFolder = "categories";
    protected string $pageTitle = "Danh mục";
    protected string $controllerName = "adminCategory";
}
