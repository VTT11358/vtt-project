
<?php

require_once __DIR__ . "/AdminCatalogController.php";

class AdminBrandsController extends AdminCatalogController
{
    protected string $type = "brand";
    protected string $viewFolder = "brands";
    protected string $pageTitle = "Thương hiệu";
    protected string $controllerName = "adminBrands";
}
