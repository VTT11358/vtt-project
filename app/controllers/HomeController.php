<?php

require_once "app/models/Product.php";
require_once "app/models/Post.php";

class HomeController
{
    private $productModel;
    private $postModel;

    public function __construct($conn)
    {
        $this->productModel = new Product($conn);
        $this->postModel = new Post($conn);
    }

    public function index()
    {
        $featuredProducts =
            $this->productModel->getFeatured(8);

        $bestSellerProducts =
            $this->productModel->getBestSellers(8);

        $latestPosts = $this->postModel->getPublishedPaginated(4, 0);

        require_once "app/views/home/index.php";
    }
}