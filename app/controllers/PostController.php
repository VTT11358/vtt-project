<?php

require_once "app/models/Post.php";

class PostController
{
    private $postModel;

    public function __construct($conn)
    {
        $this->postModel = new Post($conn);
    }

    public function index()
    {
        $limit = 6;
        $page = filter_input(INPUT_GET, "page", FILTER_VALIDATE_INT);
        $page = ($page && $page > 0) ? $page : 1;

        $totalPosts = $this->postModel->getPublishedCount();
        $totalPages = max(1, (int) ceil($totalPosts / $limit));
        $page = min($page, $totalPages);
        $posts = $this->postModel->getPublishedPaginated($limit, ($page - 1) * $limit);
        $latestPost = $this->postModel->getPublishedPaginated(1, 0)[0] ?? null;
        $pageNumbers = range(max(1, $page - 2), min($totalPages, $page + 2));

        require "app/views/posts/index.php";
    }

    public function detail()
    {
        $id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);
        $post = $id ? $this->postModel->getPublishedById($id) : null;

        if (!$post) {
            http_response_code(404);
            exit("Không tìm thấy bài viết.");
        }

        require "app/views/posts/detail.php";
    }
}
