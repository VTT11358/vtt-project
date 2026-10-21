
<?php
require_once __DIR__ . "/../../../helpers/ProductImage.php";
require __DIR__ . "/../../layouts/admin_header.php";
?>

<div class="container py-4">
    <h2>Sửa sản phẩm</h2>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST"
          action="<?= BASE_URL ?>index.php?controller=adminProduct&action=updateProduct&id=<?= (int) $product["id"] ?>"
          enctype="multipart/form-data">

        <input type="hidden" name="csrf_token"
               value="<?= htmlspecialchars($_SESSION["csrf_token"]) ?>">

        <div class="mb-3">
            <label class="form-label">Tên sản phẩm</label>
            <input name="name" class="form-control" required
                   value="<?= htmlspecialchars($product["name"]) ?>">
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Danh mục</label>
                <select name="category_id" class="form-select" required>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= (int) $category["id"] ?>"
                            <?= (int) $product["category_id"] === (int) $category["id"] ? "selected" : "" ?>>
                            <?= htmlspecialchars($category["name"]) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-6 mb-3">
                <label class="form-label">Thương hiệu</label>
                <select name="brand_id" class="form-select" required>
                    <?php foreach ($brands as $brand): ?>
                        <option value="<?= (int) $brand["id"] ?>"
                            <?= (int) $product["brand_id"] === (int) $brand["id"] ? "selected" : "" ?>>
                            <?= htmlspecialchars($brand["name"]) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-4 mb-3">
                <label class="form-label">Giá gốc</label>
                <input type="number" name="price" class="form-control"
                       min="0" step="1000" required
                       value="<?= htmlspecialchars((string) $product["price"]) ?>">
            </div>

            <div class="col-md-4 mb-3">
                <label class="form-label">Giá khuyến mãi</label>
                <input type="number" name="sale_price" class="form-control"
                       min="0" step="1000"
                       value="<?= htmlspecialchars((string) ($product["sale_price"] ?? "")) ?>">
            </div>

            <div class="col-md-4 mb-3">
                <label class="form-label">Tồn kho</label>
                <input type="number" name="quantity" class="form-control"
                       min="0" required
                       value="<?= (int) $product["quantity"] ?>">
            </div>
        </div>

        <?php
        $fields = [
            "cpu" => "CPU",
            "ram" => "RAM",
            "storage" => "Ổ cứng",
            "screen" => "Màn hình",
            "gpu" => "Card đồ họa",
            "operating_system" => "Hệ điều hành",
            "weight" => "Trọng lượng",
            "color" => "Màu sắc"
        ];
        ?>

        <div class="row">
            <?php foreach ($fields as $field => $label): ?>
                <div class="col-md-6 mb-3">
                    <label class="form-label">
                        <?= htmlspecialchars($label) ?>
                    </label>
                    <input name="<?= $field ?>" class="form-control"
                           value="<?= htmlspecialchars($product[$field] ?? "") ?>">
                </div>
            <?php endforeach; ?>
        </div>

        <div class="mb-3">
            <label class="form-label">Mô tả</label>
            <textarea name="description" class="form-control" rows="5"><?= htmlspecialchars($product["description"] ?? "") ?></textarea>
        </div>

        <div class="form-check">
            <input type="checkbox" name="is_featured" value="1"
                   id="featured" class="form-check-input"
                   <?= $product["is_featured"] ? "checked" : "" ?>>
            <label for="featured" class="form-check-label">Nổi bật</label>
        </div>

        <div class="form-check">
            <input type="checkbox" name="is_bestseller" value="1"
                   id="bestseller" class="form-check-input"
                   <?= $product["is_bestseller"] ? "checked" : "" ?>>
            <label for="bestseller" class="form-check-label">Bán chạy</label>
        </div>

        <div class="form-check mb-4">
            <input type="checkbox" name="is_active" value="1"
                   id="active" class="form-check-input"
                   <?= $product["is_active"] ? "checked" : "" ?>>
            <label for="active" class="form-check-label">Đang hiển thị</label>
        </div>

        <hr>

        <h4>Thư viện ảnh hiện tại</h4>
        <p class="text-muted">
            Chọn ảnh đại diện mới nếu muốn thay đổi. Không thể xóa ảnh cuối cùng.
        </p>

        <div class="row">
            <?php foreach ($images as $image): ?>
                <div class="col-6 col-md-3 mb-4" id="image-<?= (int) $image["id"] ?>">
                    <img
                        src="<?= htmlspecialchars(productImageUrl($image["image"]), ENT_QUOTES, "UTF-8") ?>"
                        alt="Ảnh sản phẩm"
                        class="img-thumbnail mb-2"
                        style="width:100%;height:160px;object-fit:contain">

                    <div class="form-check">
                        <input type="radio" name="main_image_id"
                               value="<?= (int) $image["id"] ?>"
                               class="form-check-input"
                               id="main-<?= (int) $image["id"] ?>"
                               <?= productImageUrl($product["image"]) === productImageUrl($image["image"]) ? "checked" : "" ?>>

                        <label class="form-check-label"
                               for="main-<?= (int) $image["id"] ?>">
                            Ảnh đại diện
                        </label>
                    </div>

                    <button type="button"
                            class="btn btn-sm btn-outline-danger mt-2"
                            onclick="deleteProductImage(<?= (int) $product["id"] ?>, <?= (int) $image["id"] ?>)">
                        Xóa ảnh
                    </button>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="mb-4">
            <label class="form-label">Tải thêm ảnh</label>
            <input type="file" name="images[]" class="form-control"
                   accept="image/jpeg,image/png,image/webp" multiple>
            <small class="text-muted">Tối đa 10 ảnh mỗi lần, mỗi ảnh tối đa 5 MB.</small>
        </div>

        <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
        <a href="<?= BASE_URL ?>index.php?controller=adminProduct&action=products"
           class="btn btn-secondary">Quay lại</a>
    </form>
</div>

<script>
function deleteProductImage(productId, imageId) {
    if (!confirm("Bạn có chắc muốn xóa ảnh này?")) {
        return;
    }

    const form = document.createElement("form");
    form.method = "POST";
    form.action = "<?= BASE_URL ?>index.php?controller=adminProduct&action=deleteProductImage";

    const fields = {
        csrf_token: "<?= htmlspecialchars($_SESSION["csrf_token"]) ?>",
        product_id: productId,
        image_id: imageId
    };

    for (const key in fields) {
        const input = document.createElement("input");
        input.type = "hidden";
        input.name = key;
        input.value = fields[key];
        form.appendChild(input);
    }

    document.body.appendChild(form);
    form.submit();
}
</script>

<?php require __DIR__ . "/../../layouts/admin_footer.php"; ?>
