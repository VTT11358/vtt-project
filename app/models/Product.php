<?php

class Product
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    /**
     * Lấy tất cả sản phẩm
     */
    public function getAll(
        $search = '',
        $category_id = '',
        $brand_id = '',
        ?int $limit = null,
        int $offset = 0
    )
    {
        [$whereSql, $types, $params] = $this->buildListFilters(
            $search,
            $category_id,
            $brand_id
        );

        $sql = "
            SELECT
                products.*,
                categories.name AS category_name,
                brands.name AS brand_name
            FROM products
            INNER JOIN categories
                ON products.category_id = categories.id
            INNER JOIN brands
                ON products.brand_id = brands.id
            {$whereSql}
            ORDER BY products.created_at DESC, products.id DESC
        ";

        if ($limit !== null) {
            $sql .= " LIMIT ? OFFSET ?";
            $types .= "ii";
            $params[] = max(1, $limit);
            $params[] = max(0, $offset);
        }

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            throw new RuntimeException("Không thể chuẩn bị danh sách sản phẩm.");
        }

        if ($types !== "" && !$stmt->bind_param($types, ...$params)) {
            $stmt->close();
            throw new RuntimeException("Không thể áp dụng bộ lọc sản phẩm.");
        }

        if (!$stmt->execute()) {
            $stmt->close();
            throw new RuntimeException("Không thể tải danh sách sản phẩm.");
        }

        $result = $stmt->get_result();
        $stmt->close();
        return $result;
    }

    public function getFilteredCount($search = '', $category_id = '', $brand_id = ''): int
    {
        [$whereSql, $types, $params] = $this->buildListFilters(
            $search,
            $category_id,
            $brand_id
        );
        $stmt = $this->conn->prepare(
            "SELECT COUNT(*) AS total
             FROM products
             INNER JOIN categories ON products.category_id = categories.id
             INNER JOIN brands ON products.brand_id = brands.id
             {$whereSql}"
        );
        if (!$stmt) {
            throw new RuntimeException("Không thể chuẩn bị số lượng sản phẩm.");
        }
        if ($types !== "" && !$stmt->bind_param($types, ...$params)) {
            $stmt->close();
            throw new RuntimeException("Không thể áp dụng bộ lọc sản phẩm.");
        }
        if (!$stmt->execute()) {
            $stmt->close();
            throw new RuntimeException("Không thể đếm sản phẩm.");
        }
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return (int) ($row["total"] ?? 0);
    }

    private function buildListFilters($search, $category_id, $brand_id): array
    {
        $whereSql = "WHERE products.is_active = 1";
        $types = "";
        $params = [];

        if (is_string($search) && $search !== "") {
            $whereSql .= " AND (
                products.name LIKE ?
                OR products.cpu LIKE ?
                OR products.ram LIKE ?
                OR products.storage LIKE ?
            )";
            $searchValue = "%" . $search . "%";
            array_push($params, $searchValue, $searchValue, $searchValue, $searchValue);
            $types .= "ssss";
        }

        if ($category_id !== "" && is_numeric($category_id)) {
            $whereSql .= " AND products.category_id = ?";
            $params[] = (int) $category_id;
            $types .= "i";
        }

        if ($brand_id !== "" && is_numeric($brand_id)) {
            $whereSql .= " AND products.brand_id = ?";
            $params[] = (int) $brand_id;
            $types .= "i";
        }

        return [$whereSql, $types, $params];
    }

    /**
     * Lấy sản phẩm theo ID
     */
    public function getById($id)
    {
        $sql = "
            SELECT
                products.*,
                categories.name AS category_name,
                brands.name AS brand_name
            FROM products
            INNER JOIN categories
                ON products.category_id = categories.id
            INNER JOIN brands
                ON products.brand_id = brands.id
            WHERE products.id = ?
              AND products.is_active = 1
            LIMIT 1
        ";

        $stmt = $this->conn->prepare($sql);

        $stmt->bind_param("i", $id);

        $stmt->execute();

        $result = $stmt->get_result();

        return $result->fetch_assoc();
    }


    /**
     * Lấy sản phẩm nổi bật
     */
    public function getFeatured($limit = 8)
    {
        $limit = (int)$limit;

        $sql = "
            SELECT
                products.*,
                categories.name AS category_name,
                brands.name AS brand_name
            FROM products
            INNER JOIN categories
                ON products.category_id = categories.id
            INNER JOIN brands
                ON products.brand_id = brands.id
            WHERE products.is_active = 1
              AND products.is_featured = 1
            ORDER BY products.created_at DESC
            LIMIT $limit
        ";

        return $this->conn->query($sql);
    }


    /**
     * Lấy sản phẩm bán chạy
     */
    public function getBestSellers($limit = 8)
    {
        $limit = (int)$limit;

        $sql = "
            SELECT
                products.*,
                categories.name AS category_name,
                brands.name AS brand_name
            FROM products
            INNER JOIN categories
                ON products.category_id = categories.id
            INNER JOIN brands
                ON products.brand_id = brands.id
            WHERE products.is_active = 1
              AND products.is_bestseller = 1
            ORDER BY products.sold_quantity DESC
            LIMIT $limit
        ";

        return $this->conn->query($sql);
    }


    /**
     * Lấy hình ảnh phụ của sản phẩm
     */
    public function getImages($product_id)
    {
        $sql = "
            SELECT *
            FROM product_images
            WHERE product_id = ?
            ORDER BY sort_order ASC, id ASC
        ";

        $stmt = $this->conn->prepare($sql);

        $stmt->bind_param("i", $product_id);

        $stmt->execute();

        return $stmt->get_result();
    }
}