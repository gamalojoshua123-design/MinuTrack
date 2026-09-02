<?php
require_once __DIR__ . '/bootstrap.php';
requirePermission('products_manage');

$active_tab = 'products';
$message = isset($_GET['message']) ? $_GET['message'] : '';
$message_type = isset($_GET['type']) ? $_GET['type'] : '';

// Handle AJAX request for product data
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_product') {
    try {
        $product_id = $_GET['id'] ?? null;
        if (!$product_id) throw new Exception('Product ID not provided');
        
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch();
        
        if (!$product) throw new Exception('Product not found');
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'data' => $product]);
        exit;
    } catch (Exception $e) {
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();
    if (isset($_POST['add_product'])) {
        try {
            $image_filename = 'default-product.png';
            if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = __DIR__ . '/../assets/images/products/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
                
                $extension = strtolower(pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                
                if (in_array($extension, $allowed)) {
                    $image_filename = uniqid() . '.' . $extension;
                    move_uploaded_file($_FILES['product_image']['tmp_name'], $upload_dir . $image_filename);
                }
            }

            $stmt = $pdo->prepare("INSERT INTO products (name, price, stock, category, status, image) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $_POST['name'],
                $_POST['price'],
                $_POST['stock'],
                $_POST['category'],
                $_POST['status'],
                $image_filename
            ]);
            
            header('Location: products.php?message=' . urlencode('Product added successfully!') . '&type=success');
            exit;
        } catch (Exception $e) {
            header('Location: products.php?message=' . urlencode('Error: ' . $e->getMessage()) . '&type=error');
            exit;
        }
    }
    
    if (isset($_POST['update_product'])) {
        try {
            $params = [
                $_POST['name'],
                $_POST['price'],
                $_POST['stock'],
                $_POST['category'],
                $_POST['status'],
                $_POST['product_id']
            ];
            
            $sql = "UPDATE products SET name = ?, price = ?, stock = ?, category = ?, status = ?";
            
            if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = __DIR__ . '/../assets/images/products/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
                
                $extension = strtolower(pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                
                if (in_array($extension, $allowed)) {
                    // Delete old image if it exists and isn't the default
                    $oldStmt = $pdo->prepare("SELECT image FROM products WHERE id = ?");
                    $oldStmt->execute([$_POST['product_id']]);
                    $old_image = $oldStmt->fetchColumn();
                    if ($old_image && $old_image !== 'default-product.png') {
                        $old_path = $upload_dir . $old_image;
                        if (file_exists($old_path)) unlink($old_path);
                    }
                    
                    $image_filename = uniqid() . '.' . $extension;
                    move_uploaded_file($_FILES['product_image']['tmp_name'], $upload_dir . $image_filename);
                    
                    $sql .= ", image = ?";
                    array_splice($params, 5, 0, [$image_filename]);
                }
            }
            
            $sql .= " WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            header('Location: products.php?message=' . urlencode('Product updated successfully!') . '&type=success');
            exit;
        } catch (Exception $e) {
            header('Location: products.php?message=' . urlencode('Error: ' . $e->getMessage()) . '&type=error');
            exit;
        }
    }
    
    if (isset($_POST['delete_product'])) {
        try {
            $stmt = $pdo->prepare("UPDATE products SET status = 'inactive', deleted_at = NOW() WHERE id = ?");
            $stmt->execute([$_POST['product_id']]);
            header('Location: products.php?message=' . urlencode('Product archived successfully!') . '&type=success');
            exit;
        } catch (PDOException $e) {
            header('Location: products.php?message=' . urlencode('Error: ' . $e->getMessage()) . '&type=error');
            exit;
        }
    }
}

// Fetch products (exclude soft-deleted duplicates)
$stmt = $pdo->prepare("SELECT * FROM products WHERE deleted_at IS NULL ORDER BY category, name");
$stmt->execute();
$products = $stmt->fetchAll();

// Fetch inventory items for usage modal
$stmt = $pdo->prepare("SELECT * FROM inventory ORDER BY item_name");
$stmt->execute();
$inventory_items = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo htmlspecialchars(getCsrfToken()); ?>">
    <title>Products - Minute Burger Admin</title>
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/minute1/assets/css/admin.css">
    <style>
        .product-section { margin-bottom: 2.5rem; }
        .product-section:first-child { margin-top: 0.25rem; }
        .product-section h4 {
            color: var(--brown);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.65rem;
            font-size: 1.05rem;
            font-weight: 700;
            border-left: 4px solid var(--harvest-orange);
            padding-left: 0.85rem;
        }
        .product-section h4 i {
            width: 30px; height: 30px;
            display: inline-flex; align-items: center; justify-content: center;
            border-radius: 8px;
            background: rgba(243, 121, 2, 0.08);
            color: var(--primary);
            font-size: 1.1rem;
            flex-shrink: 0;
        }
        .product-section h4 .cat-count {
            margin-left: auto;
            font-size: 0.72rem;
            font-weight: 600;
            color: var(--text-muted);
            background: var(--bg);
            padding: 0.2rem 0.6rem;
            border-radius: 999px;
        }
        .product-image-small {
            width: 44px; height: 44px;
            border-radius: 8px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--bg);
            border: 1px solid var(--border);
        }
        .product-image-small img { width: 100%; height: 100%; object-fit: cover; }
        .product-emoji-small { font-size: 1.4rem; }
        
        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 2000;
            align-items: center;
            justify-content: center;
        }
        .modal-content {
            background: var(--bg-card);
            border-radius: var(--radius-lg);
            max-width: 500px;
            width: 95%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: var(--shadow-xl);
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--border);
        }
        .modal-header h3 {
            margin: 0;
            font-size: 1.1rem;
            font-weight: 700;
        }
        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--text-muted);
            padding: 0.25rem 0.5rem;
            border-radius: 6px;
            transition: background 0.15s, color 0.15s;
        }
        .modal-close:hover {
            color: var(--red);
            background: var(--red-light);
        }
        .modal-body {
            padding: 1.5rem;
        }
        .form-actions {
            display: flex;
            gap: 0.75rem;
            justify-content: flex-end;
            margin-top: 1.5rem;
        }
        .btn-sm {
            padding: 0.35rem 0.75rem;
            font-size: 0.75rem;
            border-radius: 6px;
        }
        .btn-edit {
            background: var(--blue-light);
            color: var(--blue);
            border: 1px solid transparent;
            transition: all 0.15s ease;
        }
        .btn-edit:hover {
            background: var(--blue);
            color: #fff;
        }
        .btn-delete {
            background: var(--red-light);
            color: var(--red);
            border: 1px solid transparent;
            transition: all 0.15s ease;
        }
        
        .btn-delete:hover {
            background: var(--red);
            color: #fff;
        }
        .action-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        .table-container {
            overflow-x: auto;
        }
        #image-preview {
            margin-top: 10px;
        }
        #image-preview img {
            max-width: 100px;
            max-height: 100px;
            border-radius: 4px;
            border: 1px solid var(--border);
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <?php include __DIR__ . '/includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include __DIR__ . '/includes/header.php'; ?>
            
            <div class="content-area">
                <?php if (!empty($message)): ?>
                    <div class="message <?php echo $message_type; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class='bx bx-food-menu'></i>Product Management</h3>
                        <button class="btn btn-primary" onclick="showAddProductForm()">
                            <i class='bx bx-plus'></i> Add New Product
                        </button>
                    </div>
                    <div class="card-body">
                        <?php foreach (CATEGORIES as $category => $catInfo): ?>
                            <?php
                            $category_products = array_filter($products, function($p) use ($category) {
                                return $p['category'] === $category;
                            });
                            ?>
                            
                            <div class="product-section">
                                <h4>
                                    <i class='bx bx-<?php echo strtolower(str_replace(' ', '-', $category)); ?>'></i>
                                    <?php echo $catInfo['name']; ?>
                                    <span class="cat-count"><?php echo count($category_products); ?></span>
                                </h4>
                                
                                <div class="table-container">
                                    <table class="data-table">
                                        <thead>
                                            <tr>
                                                <th>Image</th>
                                                <th>Product Name</th>
                                                <th>Price</th>
                                                <th>Stock</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($category_products)): ?>
                                                <tr>
                                                    <td colspan="6">
                                                        <div class="empty-state">
                                                            <i class="bx bx-food-tag"></i>
                                                            <span class="empty-title">No <?php echo strtolower($catInfo['name']); ?> found</span>
                                                            <span class="empty-sub">Add products to this category to get started</span>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($category_products as $product): ?>
                                                    <tr>
                                                        <td>
                                                            <div class="product-image-small">
                                                                <?php
                                                                $image_filename = $product['image'] ?? '';
                                                                $image_path = '/minute1/assets/images/products/' . $image_filename;
                                                                if ($image_filename && $image_filename !== 'default-product.png' && file_exists(__DIR__ . '/../assets/images/products/' . $image_filename)): ?>
                                                                    <img src="<?php echo $image_path; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                                                <?php else: ?>
                                                                    <div class="product-emoji-small"><?php echo $catInfo['emoji']; ?></div>
                                                                <?php endif; ?>
                                                            </div>
                                                        </td>
                                                        <td><strong><?php echo htmlspecialchars($product['name']); ?></strong></td>
                                                        <td><strong class="text-muted">₱<?php echo number_format($product['price'], 2); ?></strong></td>
                                                        <td><?php echo $product['stock']; ?></td>
                                                        <td>
                                                            <span class="status-badge <?php echo $product['status'] === 'active' ? 'status-active' : 'status-inactive'; ?>">
                                                                <?php echo ucfirst($product['status']); ?>
                                                            </span>
                                                        </td>
                                                        <td class="action-buttons">
                                                            <button class="btn btn-edit btn-sm" onclick="editProduct(<?php echo $product['id']; ?>)">
                                                                <i class='bx bx-edit'></i> Edit
                                                            </button>
                                                            <form method="POST" style="display: inline;" 
                                                                  onsubmit="return askConfirm(event, 'Are you sure you want to archive this product?')">
                                                                <?= csrfField() ?>
                                                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                                                <button type="submit" class="btn btn-delete btn-sm" name="delete_product">
                                                                    <i class='bx bx-archive'></i> Archive
                                                                </button>
                                                            </form>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Product Modal -->
    <div class="modal" id="product-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="product-modal-title">Add New Product</h3>
                <button class="modal-close" aria-label="Close modal" onclick="closeModal('product-modal')"><i class='bx bx-x'></i></button>
            </div>
            <div class="modal-body">
                <form method="POST" id="product-form" enctype="multipart/form-data">
                    <?= csrfField() ?>
                    <input type="hidden" name="product_id" id="product_id">
                    
                    <div class="form-group">
                        <label class="form-label">Product Image</label>
                        <input type="file" class="form-control" id="product_image" name="product_image" accept="image/*">
                        <small class="text-muted">Leave empty to use default image</small>
                        <div id="image-preview" style="display: none;">
                            <img id="preview-img" src="#" alt="Preview">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Product Name</label>
                        <input type="text" class="form-control" id="product_name" name="name" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Price</label>
                        <input type="number" class="form-control" id="product_price" name="price" step="0.01" min="0" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Stock</label>
                        <input type="number" class="form-control" id="product_stock" name="stock" min="0" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Category</label>
                        <select class="form-control" id="product_category" name="category" required>
                            <?php foreach (CATEGORIES as $category => $catInfo): ?>
                                <option value="<?php echo $category; ?>"><?php echo $catInfo['name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select class="form-control" id="product_status" name="status" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="btn btn-outline" onclick="closeModal('product-modal')">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="product-submit-btn" name="add_product">Add Product</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- ============================================================ -->
    <!-- ES5 COMPATIBLE JAVASCRIPT - WORKS ON OLD TABLETS             -->
    <!-- ============================================================ -->
    <script>
        // ================================================================
        // PRODUCT MANAGEMENT - ES5 Compatible (No fetch, no arrow functions)
        // ================================================================

        function $(id) {
            return document.getElementById(id);
        }

        function closeModal(modalId) {
            var modal = $(modalId);
            if (modal) modal.style.display = 'none';
        }

        // -------- Add Product --------
        function showAddProductForm() {
            var title = $('product-modal-title');
            var form = $('product-form');
            var idField = $('product_id');
            var submitBtn = $('product-submit-btn');
            var modal = $('product-modal');
            var preview = $('image-preview');

            if (!modal) return;

            title.textContent = 'Add New Product';
            if (form) form.reset();
            if (idField) idField.value = '';
            if (preview) preview.style.display = 'none';
            if (submitBtn) {
                submitBtn.name = 'add_product';
                submitBtn.textContent = 'Add Product';
            }

            modal.style.display = 'flex';
        }

        // -------- Edit Product (uses XMLHttpRequest - works on old tablets) --------
        function editProduct(id) {
            var modal = $('product-modal');
            var title = $('product-modal-title');
            var idField = $('product_id');
            var nameField = $('product_name');
            var priceField = $('product_price');
            var stockField = $('product_stock');
            var categoryField = $('product_category');
            var statusField = $('product_status');
            var submitBtn = $('product-submit-btn');
            var preview = $('image-preview');
            var previewImg = $('preview-img');

            if (!modal) return;

            title.textContent = 'Edit Product';
            if (idField) idField.value = id;
            if (submitBtn) {
                submitBtn.name = 'update_product';
                submitBtn.textContent = 'Update Product';
            }

            // Show loading state
            if (nameField) nameField.value = 'Loading...';
            if (priceField) priceField.value = '';
            if (stockField) stockField.value = '';
            if (preview) preview.style.display = 'none';

            modal.style.display = 'flex';

            // Use XMLHttpRequest instead of fetch (compatible with old browsers)
            var xhr = new XMLHttpRequest();
            xhr.open('GET', '?action=get_product&id=' + encodeURIComponent(id), true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    if (xhr.status >= 200 && xhr.status < 300) {
                        try {
                            var result = JSON.parse(xhr.responseText);
                            if (result.success) {
                                var data = result.data;
                                if (nameField) nameField.value = data.name || '';
                                if (priceField) priceField.value = data.price || '';
                                if (stockField) stockField.value = data.stock || '';
                                if (categoryField) categoryField.value = data.category || 'BIG TIME Burgers';
                                if (statusField) statusField.value = data.status || 'active';

                                if (data.image && data.image !== 'default-product.png' && preview && previewImg) {
                                    preview.style.display = 'block';
                                    previewImg.src = '/minute1/assets/images/products/' + data.image;
                                } else if (preview) {
                                    preview.style.display = 'none';
                                }
                            } else {
                                showToastMsg('Error loading product: ' + (result.error || 'Unknown error'), 'error');
                                closeModal('product-modal');
                            }
                        } catch (e) {
                            showToastMsg('Error parsing response. Please try again.', 'error');
                            closeModal('product-modal');
                        }
                    } else {
                        showToastMsg('Error loading product data (HTTP ' + xhr.status + '). Please refresh and try again.', 'error');
                        closeModal('product-modal');
                    }
                }
            };
            xhr.send();
        }

        // -------- Image Preview (ES5 compatible) --------
        document.addEventListener('DOMContentLoaded', function() {
            var imageInput = $('product_image');
            if (imageInput) {
                imageInput.addEventListener('change', function(e) {
                    var preview = $('image-preview');
                    var previewImg = $('preview-img');

                    if (this.files && this.files[0]) {
                        var reader = new FileReader();
                        reader.onload = function(e) {
                            if (previewImg) previewImg.src = e.target.result;
                            if (preview) preview.style.display = 'block';
                        };
                        reader.readAsDataURL(this.files[0]);
                    } else {
                        if (preview) preview.style.display = 'none';
                    }
                });
            }

            // -------- Form Validation --------
            var form = $('product-form');
            if (form) {
                form.addEventListener('submit', function(e) {
                    var nameField = $('product_name');
                    var priceField = $('product_price');
                    var stockField = $('product_stock');

                    if (!nameField || !nameField.value.trim()) {
                        e.preventDefault();
                        showToastMsg('Please enter a product name', 'warning');
                        if (nameField) nameField.focus();
                        return false;
                    }

                    if (!priceField || priceField.value === '' || parseFloat(priceField.value) < 0) {
                        e.preventDefault();
                        showToastMsg('Please enter a valid price', 'warning');
                        if (priceField) priceField.focus();
                        return false;
                    }

                    if (!stockField || stockField.value === '' || parseInt(stockField.value) < 0) {
                        e.preventDefault();
                        showToastMsg('Please enter a valid stock quantity', 'warning');
                        if (stockField) stockField.focus();
                        return false;
                    }

                    return true;
                });
            }

            // -------- Click outside modal to close --------
            var modals = document.querySelectorAll('.modal');
            for (var i = 0; i < modals.length; i++) {
                (function(modal) {
                    modal.addEventListener('click', function(e) {
                        if (e.target === this) {
                            closeModal(this.id);
                        }
                    });
                })(modals[i]);
            }
        });

        // Make functions globally accessible
        window.closeModal = closeModal;
        window.showAddProductForm = showAddProductForm;
        window.editProduct = editProduct;
    </script>
    
    <?php include __DIR__ . '/includes/ai_chatbot_widget.php'; ?>
    <?php include __DIR__ . '/../includes/confirm_modal.php'; ?>
</body>
</html>