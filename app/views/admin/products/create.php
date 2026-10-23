
<?php require __DIR__ . "/../../layouts/admin_header.php"; ?>

<main class="container py-4 product-management">
    <div class="product-page-header">
        <div>
            <span class="product-eyebrow">DANH MỤC KINH DOANH</span>
            <h1>Thêm sản phẩm laptop</h1>
            <p>Nhập thông tin sản phẩm và thông số kỹ thuật để đăng bán.</p>
        </div>
        <a href="<?= BASE_URL ?>index.php?controller=adminProduct&action=products" class="product-secondary-link">
            <span aria-hidden="true">←</span> Quay lại danh sách
        </a>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <section class="product-form-panel">
        <div class="product-form-intro">
            <span class="product-form-icon" aria-hidden="true">＋</span>
            <div>
                <h2>Thông tin sản phẩm</h2>
                <p>Các trường có dấu * là bắt buộc.</p>
            </div>
        </div>
        <form class="product-form" method="POST"
              action="<?= BASE_URL ?>index.php?controller=adminProduct&action=storeProduct"
              enctype="multipart/form-data">

        <input type="hidden" name="csrf_token"
               value="<?= htmlspecialchars($_SESSION["csrf_token"]) ?>">

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Tên sản phẩm *</label>
                <input type="text" name="name" class="form-control"
                       maxlength="255" required>
            </div>

            <div class="col-md-3 mb-3">
                <label class="form-label">Danh mục *</label>
                <select name="category_id" class="form-select" required>
                    <option value="">-- Chọn danh mục --</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= (int) $category["id"] ?>">
                            <?= htmlspecialchars($category["name"]) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-3 mb-3">
                <label class="form-label">Thương hiệu *</label>
                <select name="brand_id" class="form-select" required>
                    <option value="">-- Chọn thương hiệu --</option>
                    <?php foreach ($brands as $brand): ?>
                        <option value="<?= (int) $brand["id"] ?>">
                            <?= htmlspecialchars($brand["name"]) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-4 mb-3">
                <label class="form-label">Giá gốc *</label>
                <input type="number" name="price" class="form-control"
                       min="0" step="1000" required>
            </div>

            <div class="col-md-4 mb-3">
                <label class="form-label">Giá khuyến mãi</label>
                <input type="number" name="sale_price" class="form-control"
                       min="0" step="1000">
            </div>

            <div class="col-md-4 mb-3">
                <label class="form-label">Số lượng tồn kho *</label>
                <input type="number" name="quantity" class="form-control"
                       min="0" value="0" required>
            </div>

            <div class="col-12 mb-3">
                <label class="form-label">Ảnh sản phẩm *</label>
                <input type="file" name="images[]" class="form-control"
                       accept="image/jpeg,image/png,image/webp"
                       multiple required>
                <small class="text-muted">
                    Tối đa 10 ảnh, mỗi ảnh không quá 5 MB.
                    Ảnh đầu tiên là ảnh đại diện.
                </small>
            </div>

            <div class="col-md-4 mb-3">
                <label class="form-label">CPU</label>
                <input type="text" name="cpu" class="form-control">
            </div>

            <div class="col-md-4 mb-3">
                <label class="form-label">RAM</label>
                <input type="text" name="ram" class="form-control">
            </div>

            <div class="col-md-4 mb-3">
                <label class="form-label">Ổ cứng</label>
                <input type="text" name="storage" class="form-control">
            </div>

            <div class="col-md-4 mb-3">
                <label class="form-label">Màn hình</label>
                <input type="text" name="screen" class="form-control">
            </div>

            <div class="col-md-4 mb-3">
                <label class="form-label">Card đồ họa</label>
                <input type="text" name="gpu" class="form-control">
            </div>

            <div class="col-md-4 mb-3">
                <label class="form-label">Hệ điều hành</label>
                <input type="text" name="operating_system" class="form-control">
            </div>

            <div class="col-md-6 mb-3">
                <label class="form-label">Trọng lượng</label>
                <input type="text" name="weight" class="form-control">
            </div>

            <div class="col-md-6 mb-3">
                <label class="form-label">Màu sắc</label>
                <input type="text" name="color" class="form-control">
            </div>

            <div class="col-12 mb-3">
                <label class="form-label">Mô tả</label>
                <textarea name="description" class="form-control"
                          rows="5"></textarea>
            </div>

            <div class="col-12 mb-3">
                <div class="form-check">
                    <input type="checkbox" name="is_featured"
                           id="featured" class="form-check-input" value="1">
                    <label for="featured" class="form-check-label">
                        Sản phẩm nổi bật
                    </label>
                </div>

                <div class="form-check">
                    <input type="checkbox" name="is_bestseller"
                           id="bestseller" class="form-check-input" value="1">
                    <label for="bestseller" class="form-check-label">
                        Sản phẩm bán chạy
                    </label>
                </div>

                <div class="form-check">
                    <input type="checkbox" name="is_active"
                           id="active" class="form-check-input"
                           value="1" checked>
                    <label for="active" class="form-check-label">
                        Hiển thị sản phẩm
                    </label>
                </div>
            </div>
        </div>

            <div class="product-form-actions">
                <button type="submit" class="btn btn-primary">
                    Lưu sản phẩm
                </button>

                <a href="<?= BASE_URL ?>index.php?controller=adminProduct&action=products"
                   class="btn btn-secondary product-cancel-button">
                    Quay lại
                </a>
            </div>
        </form>
    </section>
</main>

<?php require __DIR__ . "/../../layouts/admin_footer.php"; ?>
