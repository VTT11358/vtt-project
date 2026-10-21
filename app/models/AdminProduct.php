
<?php

class AdminProduct
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function getAll()
    {
        $sql = "SELECT p.*, c.name AS category_name, b.name AS brand_name
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN brands b ON p.brand_id = b.id
                ORDER BY p.id DESC";

        $result = $this->conn->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function getCategories()
    {
        $result = $this->conn->query(
            "SELECT id, name FROM categories WHERE status = 1 ORDER BY name"
        );

        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function getBrands()
    {
        $result = $this->conn->query(
            "SELECT id, name FROM brands WHERE status = 1 ORDER BY name"
        );

        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function getInventorySummary(): array
    {
        $result = $this->conn->query(
            "SELECT COUNT(*) AS product_count, COALESCE(SUM(quantity), 0) AS unit_count
             FROM products
             WHERE quantity > 0"
        );

        if (!$result) {
            throw new RuntimeException("Không thể tải tổng số sản phẩm còn tồn.");
        }

        $summary = $result->fetch_assoc();

        return [
            "product_count" => (int) ($summary["product_count"] ?? 0),
            "unit_count" => (int) ($summary["unit_count"] ?? 0),
        ];
    }

    public function getInventoryTotalCount(string $search = ""): int
    {
        [$whereSql, $types, $params] = $this->buildInventorySearch($search);
        $stmt = $this->conn->prepare(
            "SELECT COUNT(*) AS total
             FROM products p
             LEFT JOIN categories c ON p.category_id = c.id
             LEFT JOIN brands b ON p.brand_id = b.id
             {$whereSql}"
        );

        if (!$stmt) {
            throw new RuntimeException("Không thể chuẩn bị truy vấn danh sách tồn kho.");
        }

        if ($types !== "") {
            $stmt->bind_param($types, ...$params);
        }

        if (!$stmt->execute()) {
            $stmt->close();
            throw new RuntimeException("Không thể đếm sản phẩm còn tồn.");
        }

        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return (int) ($row["total"] ?? 0);
    }

    public function getInventoryPaginated(string $search, int $limit, int $offset): array
    {
        $limit = max(1, $limit);
        $offset = max(0, $offset);
        [$whereSql, $types, $params] = $this->buildInventorySearch($search);
        $sql = "SELECT p.id, p.name, p.image, p.price, p.sale_price,
                       p.quantity, p.sold_quantity, p.is_active,
                       c.name AS category_name, b.name AS brand_name
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN brands b ON p.brand_id = b.id
                {$whereSql}
                ORDER BY p.quantity DESC, p.name ASC
                LIMIT ? OFFSET ?";
        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            throw new RuntimeException("Không thể chuẩn bị danh sách sản phẩm còn tồn.");
        }

        $types .= "ii";
        $params[] = $limit;
        $params[] = $offset;
        $stmt->bind_param($types, ...$params);

        if (!$stmt->execute()) {
            $stmt->close();
            throw new RuntimeException("Không thể tải danh sách sản phẩm còn tồn.");
        }

        $products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $products;
    }

    private function buildInventorySearch(string $search): array
    {
        $where = ["p.quantity > 0"];
        $types = "";
        $params = [];

        if ($search !== "") {
            $where[] = "(p.name LIKE ? OR p.slug LIKE ? OR c.name LIKE ? OR b.name LIKE ?)";
            $keyword = "%" . $search . "%";
            $types = "ssss";
            $params = [$keyword, $keyword, $keyword, $keyword];
        }

        return ["WHERE " . implode(" AND ", $where), $types, $params];
    }

    public function create($data, $files)
    {
        $uploadDir = dirname(__DIR__, 2) . "/uploads/products/";

        if (!is_dir($uploadDir) &&
            !mkdir($uploadDir, 0755, true) &&
            !is_dir($uploadDir)) {
            return "Không thể tạo thư mục lưu ảnh.";
        }

        if (!is_writable($uploadDir)) {
            return "Thư mục uploads/products không có quyền ghi.";
        }

        if (
            empty($files["images"]) ||
            !isset($files["images"]["name"]) ||
            !is_array($files["images"]["name"])
        ) {
            return "Vui lòng chọn ít nhất một ảnh sản phẩm.";
        }

        $images = $files["images"];
        $validFiles = [];
        $total = count($images["name"]);

        if ($total > 10) {
            return "Mỗi sản phẩm chỉ được tải tối đa 10 ảnh một lần.";
        }

        $allowedTypes = [
            "image/jpeg" => "jpg",
            "image/png"  => "png",
            "image/webp" => "webp"
        ];

        for ($i = 0; $i < $total; $i++) {
            if ($images["error"][$i] === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            if ($images["error"][$i] !== UPLOAD_ERR_OK) {
                return "Có ảnh tải lên bị lỗi.";
            }

            if ($images["size"][$i] > 5 * 1024 * 1024) {
                return "Mỗi ảnh không được vượt quá 5 MB.";
            }

            $tmp = $images["tmp_name"][$i];

            if (!is_uploaded_file($tmp)) {
                return "File tải lên không hợp lệ.";
            }

            $imageInfo = getimagesize($tmp);
            $mime = $imageInfo["mime"] ?? "";

            if (!$imageInfo || !isset($allowedTypes[$mime])) {
                return "Chỉ chấp nhận ảnh JPG, PNG hoặc WEBP.";
            }

            $validFiles[] = [
                "tmp" => $tmp,
                "extension" => $allowedTypes[$mime]
            ];
        }

        if (count($validFiles) === 0) {
            return "Vui lòng chọn ít nhất một ảnh hợp lệ.";
        }

        $categoryId = (int) $data["category_id"];
        $brandId = (int) $data["brand_id"];
        $name = trim($data["name"]);
        $price = (float) $data["price"];
        $salePrice = trim($data["sale_price"] ?? "");
        $quantity = (int) $data["quantity"];

        if ($name === "" || mb_strlen($name) > 255) {
            return "Tên sản phẩm không hợp lệ.";
        }

        if ($categoryId <= 0 || $brandId <= 0) {
            return "Vui lòng chọn danh mục và thương hiệu.";
        }

        if ($price < 0 || $quantity < 0) {
            return "Giá và số lượng không được âm.";
        }

        if ($salePrice !== "" &&
            ((float) $salePrice < 0 || (float) $salePrice > $price)) {
            return "Giá khuyến mãi phải từ 0 đến giá gốc.";
        }

        $salePriceValue = $salePrice === "" ? null : (float) $salePrice;

        // Tạo slug đơn giản, thêm hậu tố để tránh trùng.
        $slugBase = mb_strtolower($name);
        $slugBase = preg_replace(
            '/[^a-z0-9]+/u',
            '-',
            iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $slugBase)
        );
        $slugBase = trim($slugBase, '-');

        if ($slugBase === "") {
            $slugBase = "laptop";
        }

        $slug = $slugBase . "-" . bin2hex(random_bytes(4));

        $uploadedPaths = [];

        try {
            $this->conn->begin_transaction();

            $check = $this->conn->prepare(
                "SELECT id FROM categories WHERE id = ? AND status = 1"
            );
            $check->bind_param("i", $categoryId);
            $check->execute();
            $categoryExists = $check->get_result()->num_rows > 0;
            $check->close();

            $check = $this->conn->prepare(
                "SELECT id FROM brands WHERE id = ? AND status = 1"
            );
            $check->bind_param("i", $brandId);
            $check->execute();
            $brandExists = $check->get_result()->num_rows > 0;
            $check->close();

            if (!$categoryExists || !$brandExists) {
                throw new RuntimeException(
                    "Danh mục hoặc thương hiệu không hợp lệ."
                );
            }

            $relativePaths = [];

            foreach ($validFiles as $file) {
                $fileName = bin2hex(random_bytes(16))
                    . "." . $file["extension"];

                if (!move_uploaded_file(
                    $file["tmp"],
                    $uploadDir . $fileName
                )) {
                    throw new RuntimeException("Không thể lưu ảnh.");
                }

                $relativePath = "uploads/products/" . $fileName;
                $uploadedPaths[] = $uploadDir . $fileName;
                $relativePaths[] = $relativePath;
            }

            $mainImage = $relativePaths[0];

            $sql = "INSERT INTO products
                    (category_id, brand_id, name, slug, price, sale_price,
                     quantity, image, cpu, ram, storage, screen, gpu,
                     operating_system, weight, color, description,
                     is_featured, is_bestseller, is_active)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $this->conn->prepare($sql);

            $cpu = trim($data["cpu"] ?? "");
            $ram = trim($data["ram"] ?? "");
            $storage = trim($data["storage"] ?? "");
            $screen = trim($data["screen"] ?? "");
            $gpu = trim($data["gpu"] ?? "");
            $os = trim($data["operating_system"] ?? "");
            $weight = trim($data["weight"] ?? "");
            $color = trim($data["color"] ?? "");
            $description = trim($data["description"] ?? "");
            $featured = isset($data["is_featured"]) ? 1 : 0;
            $bestseller = isset($data["is_bestseller"]) ? 1 : 0;
            $active = isset($data["is_active"]) ? 1 : 0;

            $stmt->bind_param(
                "iissddisssssssssssii",
                $categoryId,
                $brandId,
                $name,
                $slug,
                $price,
                $salePriceValue,
                $quantity,
                $mainImage,
                $cpu,
                $ram,
                $storage,
                $screen,
                $gpu,
                $os,
                $weight,
                $color,
                $description,
                $featured,
                $bestseller,
                $active
            );

            if (!$stmt->execute()) {
                throw new RuntimeException("Không thể thêm sản phẩm.");
            }

            $productId = $this->conn->insert_id;
            $stmt->close();

            $imageStmt = $this->conn->prepare(
                "INSERT INTO product_images (product_id, image, sort_order)
                 VALUES (?, ?, ?)"
            );

            foreach ($relativePaths as $order => $path) {
                $sortOrder = $order + 1;
                $imageStmt->bind_param(
                    "isi",
                    $productId,
                    $path,
                    $sortOrder
                );

                if (!$imageStmt->execute()) {
                    throw new RuntimeException("Không thể lưu danh sách ảnh.");
                }
            }

            $imageStmt->close();
            $this->conn->commit();

            return true;
        } catch (Throwable $e) {
            $this->conn->rollback();

            foreach ($uploadedPaths as $path) {
                if (is_file($path)) {
                    unlink($path);
                }
            }

            return $e instanceof RuntimeException
                ? $e->getMessage()
                : "Đã xảy ra lỗi khi lưu sản phẩm.";
        }
    }


public function getById($id)
{
    $stmt = $this->conn->prepare(
        "SELECT * FROM products WHERE id = ? LIMIT 1"
    );
    $stmt->bind_param("i", $id);
    $stmt->execute();

    $product = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $product ?: null;
}

public function getImages($productId)
{
    $stmt = $this->conn->prepare(
        "SELECT id, product_id, image, sort_order
         FROM product_images
         WHERE product_id = ?
         ORDER BY sort_order, id"
    );

    $stmt->bind_param("i", $productId);
    $stmt->execute();

    $images = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $images;
}

public function updateDetails($id, $data)
{
    $categoryId = (int) ($data["category_id"] ?? 0);
    $brandId = (int) ($data["brand_id"] ?? 0);
    $name = trim($data["name"] ?? "");
    $price = (float) ($data["price"] ?? -1);
    $saleInput = trim($data["sale_price"] ?? "");
    $quantity = filter_var(
        $data["quantity"] ?? null,
        FILTER_VALIDATE_INT
    );

    if ($name === "" || mb_strlen($name) > 255) {
        return "Tên sản phẩm không hợp lệ.";
    }

    if ($categoryId <= 0 || $brandId <= 0) {
        return "Vui lòng chọn danh mục và thương hiệu.";
    }

    if ($price < 0 || $quantity === false || $quantity < 0) {
        return "Giá hoặc số lượng không hợp lệ.";
    }

    if (
        $saleInput !== "" &&
        (!is_numeric($saleInput) ||
         (float) $saleInput < 0 ||
         (float) $saleInput > $price)
    ) {
        return "Giá khuyến mãi phải từ 0 đến giá gốc.";
    }

    $salePrice = $saleInput === "" ? null : (float) $saleInput;

    $cpu = trim($data["cpu"] ?? "");
    $ram = trim($data["ram"] ?? "");
    $storage = trim($data["storage"] ?? "");
    $screen = trim($data["screen"] ?? "");
    $gpu = trim($data["gpu"] ?? "");
    $os = trim($data["operating_system"] ?? "");
    $weight = trim($data["weight"] ?? "");
    $color = trim($data["color"] ?? "");
    $description = trim($data["description"] ?? "");

    $featured = isset($data["is_featured"]) ? 1 : 0;
    $bestseller = isset($data["is_bestseller"]) ? 1 : 0;
    $active = isset($data["is_active"]) ? 1 : 0;

    $stmt = $this->conn->prepare(
        "UPDATE products SET
            category_id = ?,
            brand_id = ?,
            name = ?,
            price = ?,
            sale_price = ?,
            quantity = ?,
            cpu = ?,
            ram = ?,
            storage = ?,
            screen = ?,
            gpu = ?,
            operating_system = ?,
            weight = ?,
            color = ?,
            description = ?,
            is_featured = ?,
            is_bestseller = ?,
            is_active = ?
         WHERE id = ?"
    );

    
$types = "iisddi" . str_repeat("s", 9) . "iiii";

$stmt->bind_param(
    $types,
    $categoryId,
    $brandId,
    $name,
    $price,
    $salePrice,
    $quantity,
    $cpu,
    $ram,
    $storage,
    $screen,
    $gpu,
    $os,
    $weight,
    $color,
    $description,
    $featured,
    $bestseller,
    $active,
    $id
);


    $success = $stmt->execute();
    $stmt->close();

    return $success ? true : "Không thể cập nhật sản phẩm.";
}


public function addImages($productId, $files)
{
    if (
        empty($files["images"]["name"]) ||
        !is_array($files["images"]["name"])
    ) {
        return true;
    }

    $images = $files["images"];
    $uploadDir = dirname(__DIR__, 2) . "/uploads/products/";

    if (!is_dir($uploadDir) &&
        !mkdir($uploadDir, 0755, true) &&
        !is_dir($uploadDir)) {
        return "Không thể tạo thư mục lưu ảnh.";
    }

    if (!is_writable($uploadDir)) {
        return "Thư mục lưu ảnh không có quyền ghi.";
    }

    $allowedTypes = [
        "image/jpeg" => "jpg",
        "image/png" => "png",
        "image/webp" => "webp"
    ];

    $valid = [];
    $count = count($images["name"]);

    if ($count > 10) {
        return "Chỉ được tải tối đa 10 ảnh mỗi lần.";
    }

    for ($i = 0; $i < $count; $i++) {
        $error = $images["error"][$i];

        if ($error === UPLOAD_ERR_NO_FILE) {
            continue;
        }

        if ($error !== UPLOAD_ERR_OK) {
            return "Có ảnh tải lên bị lỗi.";
        }

        $tmp = $images["tmp_name"][$i];

        if ($images["size"][$i] > 5 * 1024 * 1024) {
            return "Mỗi ảnh không được vượt quá 5 MB.";
        }

        if (!is_uploaded_file($tmp)) {
            return "File tải lên không hợp lệ.";
        }

        $info = getimagesize($tmp);
        $mime = $info["mime"] ?? "";

        if (!$info || !isset($allowedTypes[$mime])) {
            return "Chỉ chấp nhận ảnh JPG, PNG hoặc WEBP.";
        }

        $valid[] = [
            "tmp" => $tmp,
            "extension" => $allowedTypes[$mime]
        ];
    }

    $currentImages = $this->getImages($productId);

    if (count($currentImages) + count($valid) > 20) {
        return "Mỗi sản phẩm được lưu tối đa 20 ảnh.";
    }

    $uploadedPaths = [];
    $stmt = $this->conn->prepare(
        "INSERT INTO product_images (product_id, image, sort_order)
         VALUES (?, ?, ?)"
    );

    if (!$stmt) {
        return "Không thể chuẩn bị lưu ảnh.";
    }

    try {
        $this->conn->begin_transaction();

        $sortOrder = count($currentImages);

        foreach ($valid as $file) {
            $fileName = bin2hex(random_bytes(16))
                . "." . $file["extension"];

            $fullPath = $uploadDir . $fileName;

            if (!move_uploaded_file($file["tmp"], $fullPath)) {
                throw new RuntimeException("Không thể lưu file ảnh.");
            }

            $uploadedPaths[] = $fullPath;
            $relativePath = "uploads/products/" . $fileName;
            $sortOrder++;

            $stmt->bind_param(
                "isi",
                $productId,
                $relativePath,
                $sortOrder
            );

            if (!$stmt->execute()) {
                throw new RuntimeException("Không thể lưu thông tin ảnh.");
            }
        }

        $this->conn->commit();
        $stmt->close();

        return true;
    } catch (Throwable $e) {
        $this->conn->rollback();
        $stmt->close();

        foreach ($uploadedPaths as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }

        return $e->getMessage();
    }
}


public function deleteImage($productId, $imageId)
{
    $stmt = $this->conn->prepare(
        "SELECT image FROM product_images
         WHERE id = ? AND product_id = ?"
    );

    $stmt->bind_param("ii", $imageId, $productId);
    $stmt->execute();

    $image = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$image) {
        return "Không tìm thấy ảnh.";
    }

    $images = $this->getImages($productId);

    if (count($images) <= 1) {
        return "Sản phẩm phải có ít nhất một ảnh.";
    }

    $product = $this->getById($productId);

    if (!$product) {
        return "Không tìm thấy sản phẩm.";
    }

    $remaining = array_values(array_filter(
        $images,
        fn($item) => (int) $item["id"] !== $imageId
    ));

    $isMain = $product["image"] === $image["image"];

    $this->conn->begin_transaction();

    try {
        $stmt = $this->conn->prepare(
            "DELETE FROM product_images
             WHERE id = ? AND product_id = ?"
        );
        $stmt->bind_param("ii", $imageId, $productId);

        if (!$stmt->execute()) {
            throw new RuntimeException("Không thể xóa ảnh.");
        }

        $stmt->close();

        if ($isMain) {
            $newMain = $remaining[0]["image"];

            $stmt = $this->conn->prepare(
                "UPDATE products SET image = ? WHERE id = ?"
            );
            $stmt->bind_param("si", $newMain, $productId);

            if (!$stmt->execute()) {
                throw new RuntimeException("Không thể đổi ảnh đại diện.");
            }

            $stmt->close();
        }

        $this->conn->commit();

        $fullPath = dirname(__DIR__, 2) . "/"
            . str_replace("\\", "/", $image["image"]);

        if (is_file($fullPath)) {
            unlink($fullPath);
        }

        return true;
    } catch (Throwable $e) {
        $this->conn->rollback();

        return $e->getMessage();
    }
}


public function deleteProduct($id)
{
    $id = (int) $id;

    if ($id <= 0) {
        return [
            "status" => "error",
            "message" => "ID sản phẩm không hợp lệ."
        ];
    }

    // Kiểm tra sản phẩm có tồn tại không
    $stmt = $this->conn->prepare(
        "SELECT id, image FROM products WHERE id = ?"
    );
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$product) {
        return [
            "status" => "error",
            "message" => "Không tìm thấy sản phẩm."
        ];
    }

    // Kiểm tra sản phẩm đã được sử dụng trong đơn hàng chưa
    $stmt = $this->conn->prepare(
        "SELECT COUNT(*) AS total FROM order_items WHERE product_id = ?"
    );
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $orderCount = (int) $stmt->get_result()->fetch_assoc()["total"];
    $stmt->close();

    // Kiểm tra sản phẩm còn trong giỏ hàng không
    $stmt = $this->conn->prepare(
        "SELECT COUNT(*) AS total FROM cart_items WHERE product_id = ?"
    );
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $cartCount = (int) $stmt->get_result()->fetch_assoc()["total"];
    $stmt->close();

    // Có dữ liệu liên quan: chỉ ngừng kinh doanh
    if ($orderCount > 0 || $cartCount > 0) {
        $stmt = $this->conn->prepare(
            "UPDATE products SET is_active = 0 WHERE id = ?"
        );
        $stmt->bind_param("i", $id);
        $success = $stmt->execute();
        $stmt->close();

        return [
            "status" => $success ? "deactivated" : "error",
            "message" => $success
                ? "Sản phẩm đã được ngừng kinh doanh vì đang có dữ liệu liên quan."
                : "Không thể cập nhật trạng thái sản phẩm."
        ];
    }

    // Lấy danh sách ảnh để xóa file sau khi xóa sản phẩm thành công
    $images = [];

    if (!empty($product["image"])) {
        $images[] = $product["image"];
    }

    $stmt = $this->conn->prepare(
        "SELECT image FROM product_images WHERE product_id = ?"
    );
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        if (!empty($row["image"])) {
            $images[] = $row["image"];
        }
    }
    $stmt->close();

    $this->conn->begin_transaction();

    try {
        // Xóa sản phẩm; product_images sẽ được xóa theo khóa ngoại CASCADE
        $stmt = $this->conn->prepare(
            "DELETE FROM products WHERE id = ?"
        );
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

        $this->conn->commit();

        // Chỉ xóa file ảnh sau khi database xóa thành công
        $root = dirname(__DIR__, 2);
        $images = array_unique($images);

        foreach ($images as $image) {
            $filename = basename(str_replace("\\", "/", $image));
            $filePath = $root . "/uploads/products/" . $filename;

            if (is_file($filePath)) {
                @unlink($filePath);
            }
        }

        return [
            "status" => "deleted",
            "message" => "Đã xóa sản phẩm thành công."
        ];
    } catch (Throwable $e) {
        $this->conn->rollback();

        return [
            "status" => "error",
            "message" => "Không thể xóa sản phẩm. Vui lòng kiểm tra ràng buộc dữ liệu."
        ];
    }
}


/**
 * Đếm số sản phẩm theo từ khóa tìm kiếm
 */
public function searchTotalCount($search = "")
{
    $search = trim($search);

    $sql = "
        SELECT COUNT(*) AS total
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN brands b ON p.brand_id = b.id
        WHERE p.name LIKE ?
           OR p.slug LIKE ?
           OR c.name LIKE ?
           OR b.name LIKE ?
    ";

    $keyword1 = "%" . $search . "%";
    $keyword2 = "%" . $search . "%";
    $keyword3 = "%" . $search . "%";
    $keyword4 = "%" . $search . "%";

    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param(
        "ssss",
        $keyword1,
        $keyword2,
        $keyword3,
        $keyword4
    );
    $stmt->execute();

    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    $stmt->close();

    return (int) $row["total"];
}

/**
 * Tìm kiếm sản phẩm kết hợp phân trang
 */
public function searchPaginated($search, $limit, $offset)
{
    $search = trim($search);
    $limit = max(1, (int) $limit);
    $offset = max(0, (int) $offset);

    $sql = "
        SELECT
            p.*,
            c.name AS category_name,
            b.name AS brand_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN brands b ON p.brand_id = b.id
        WHERE p.name LIKE ?
           OR p.slug LIKE ?
           OR c.name LIKE ?
           OR b.name LIKE ?
        ORDER BY p.id DESC
        LIMIT ? OFFSET ?
    ";

    $keyword1 = "%" . $search . "%";
    $keyword2 = "%" . $search . "%";
    $keyword3 = "%" . $search . "%";
    $keyword4 = "%" . $search . "%";

    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param(
        "ssssii",
        $keyword1,
        $keyword2,
        $keyword3,
        $keyword4,
        $limit,
        $offset
    );

    $stmt->execute();
    $result = $stmt->get_result();
    $products = $result->fetch_all(MYSQLI_ASSOC);

    $stmt->close();

    return $products;
}


public function getTotalCount()
{
    $sql = "SELECT COUNT(*) AS total FROM products";
    $result = $this->conn->query($sql);

    if (!$result) {
        return 0;
    }

    $row = $result->fetch_assoc();

    return (int) $row["total"];
}

/**
 * Lấy sản phẩm theo từng trang
 */
public function getPaginated($limit, $offset)
{
    $limit = max(1, (int) $limit);
    $offset = max(0, (int) $offset);

    $sql = "
        SELECT
            p.*,
            c.name AS category_name,
            b.name AS brand_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN brands b ON p.brand_id = b.id
        ORDER BY p.id DESC
        LIMIT ? OFFSET ?
    ";

    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("ii", $limit, $offset);
    $stmt->execute();

    $result = $stmt->get_result();
    $products = $result->fetch_all(MYSQLI_ASSOC);

    $stmt->close();

    return $products;
}


}
