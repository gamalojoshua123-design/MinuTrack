<?php
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireManager();

$page_title = 'Product Management';
$active_page = 'products';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();
    // Build redirect params to preserve filters
    $redirect_params = [];
    if (!empty($_POST['_search'])) $redirect_params['search'] = $_POST['_search'];
    if (!empty($_POST['_category'])) $redirect_params['category'] = $_POST['_category'];
    if (!empty($_POST['_page'])) $redirect_params['page'] = $_POST['_page'];
    if (!empty($_POST['_per_page'])) $redirect_params['per_page'] = $_POST['_per_page'];

    // Add Product
    if (isset($_POST['add_product'])) {
        try {
            $image_filename = 'default-product.png';
            if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = 'assets/images/products/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }

                // Validate file size (max 5MB)
                if ($_FILES['product_image']['size'] > 5 * 1024 * 1024) {
                    throw new Exception('Image file size must be less than 5MB.');
                }

                // Validate it's actually an image
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->file($_FILES['product_image']['tmp_name']);
                $allowed_mimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                if (!in_array($mime, $allowed_mimes)) {
                    throw new Exception('Invalid image file. Please upload a valid JPG, PNG, GIF, or WebP image.');
                }

                $file_extension = pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION);
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

                if (in_array(strtolower($file_extension), $allowed_extensions)) {
                    $image_filename = uniqid() . '.' . strtolower($file_extension);
                    $upload_path = $upload_dir . $image_filename;

                    if (!move_uploaded_file($_FILES['product_image']['tmp_name'], $upload_path)) {
                        throw new Exception('Failed to upload image');
                    }
                } else {
                    throw new Exception('Invalid file type. Please upload JPG, PNG, or GIF images only.');
                }
            }

            // Validate price and stock
            $price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
            if ($price === false || $price < 0) {
                throw new Exception('Price must be a valid positive number');
            }
            
            $stock = filter_input(INPUT_POST, 'stock', FILTER_VALIDATE_INT);
            if ($stock === false || $stock < 0) {
                throw new Exception('Stock must be a valid non-negative number');
            }

            $stmt = $pdo->prepare("INSERT INTO products (name, price, stock, category, status, image) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $_POST['name'],
                $price,
                $stock,
                $_POST['category'],
                $_POST['status'],
                $image_filename
            ]);

            $redirect_params['message'] = 'Product added successfully!';
            $redirect_params['type'] = 'success';
            header("Location: products.php?" . http_build_query($redirect_params));
            exit;
        } catch (Exception $e) {
            $redirect_params['message'] = 'Error: ' . $e->getMessage();
            $redirect_params['type'] = 'error';
            header("Location: products.php?" . http_build_query($redirect_params));
            exit;
        }
    }

    // Update Product
    if (isset($_POST['update_product'])) {
        try {
            $image_update = "";
            $params = [
                $_POST['name'],
                $_POST['price'],
                $_POST['stock'],
                $_POST['category'],
                $_POST['status'],
                $_POST['product_id']
            ];

            if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = 'assets/images/products/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }

                $file_extension = pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION);
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

                if (in_array(strtolower($file_extension), $allowed_extensions)) {
                    $image_filename = uniqid() . '.' . $file_extension;
                    $upload_path = $upload_dir . $image_filename;

                    if (move_uploaded_file($_FILES['product_image']['tmp_name'], $upload_path)) {
                        $image_update = ", image = ?";
                        array_splice($params, 5, 0, [$image_filename]);
                    }
                } else {
                    throw new Exception('Invalid file type. Please upload JPG, PNG, GIF, or WebP images only.');
                }
            }

            $sql = "UPDATE products SET name = ?, price = ?, stock = ?, category = ?, status = ?$image_update WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            $redirect_params['message'] = 'Product updated successfully!';
            $redirect_params['type'] = 'success';
            header("Location: products.php?" . http_build_query($redirect_params));
            exit;
        } catch (Exception $e) {
            $redirect_params['message'] = 'Error: ' . $e->getMessage();
            $redirect_params['type'] = 'error';
            header("Location: products.php?" . http_build_query($redirect_params));
            exit;
        }
    }

    // Delete/Archive Product
    if (isset($_POST['delete_product'])) {
        try {
            $stmt = $pdo->prepare("UPDATE products SET status = 'inactive', deleted_at = NOW() WHERE id = ?");
            $stmt->execute([$_POST['product_id']]);
            $redirect_params['message'] = 'Product archived successfully!';
            $redirect_params['type'] = 'success';
            header("Location: products.php?" . http_build_query($redirect_params));
            exit;
        } catch (PDOException $e) {
            $redirect_params['message'] = 'Error: ' . $e->getMessage();
            $redirect_params['type'] = 'error';
            header("Location: products.php?" . http_build_query($redirect_params));
            exit;
        }
    }

    // Save Ingredients (template-based BOM)
    if (isset($_POST['save_ingredients'])) {
        try {
            $product_id = (int)($_POST['product_id'] ?? 0);
            $ingredients = json_decode($_POST['ingredients'] ?? '[]', true);

            if ($product_id <= 0) {
                throw new Exception('Invalid product selected');
            }
            if (!is_array($ingredients)) {
                throw new Exception('Invalid ingredients data');
            }

            $pdo->beginTransaction();

            $exists = $pdo->prepare("SELECT COUNT(*) FROM products WHERE id = ?");
            $exists->execute([$product_id]);
            if ($exists->fetchColumn() == 0) {
                throw new Exception('Product not found');
            }

            $stmt = $pdo->prepare("DELETE FROM product_ingredients WHERE product_id = ?");
            $stmt->execute([$product_id]);

            $stmt = $pdo->prepare("INSERT INTO product_ingredients (product_id, template_id, inventory_id, qty_required) VALUES (?, ?, ?, ?)");

            foreach ($ingredients as $item) {
                if (empty($item['template_id']) || empty($item['qty_required'])) continue;

                $invStmt = $pdo->prepare("SELECT id FROM inventory WHERE template_id = ? AND deleted_at IS NULL LIMIT 1");
                $invStmt->execute([$item['template_id']]);
                $inventory_id = $invStmt->fetchColumn() ?: 0;

                $stmt->execute([
                    $product_id,
                    $item['template_id'],
                    $inventory_id,
                    $item['qty_required']
                ]);
            }

            $pdo->commit();

            $redirect_params['message'] = 'Ingredients saved successfully!';
            $redirect_params['type'] = 'success';
            header("Location: products.php?" . http_build_query($redirect_params));
            exit;
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $redirect_params['message'] = 'Error: ' . $e->getMessage();
            $redirect_params['type'] = 'error';
            header("Location: products.php?" . http_build_query($redirect_params));
            exit;
        }
    }
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_product') {
    requireAuth();
    try {
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([(int)$_GET['id']]);
        $product = $stmt->fetch();

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'data' => $product]);
        exit;
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

// Fetch ingredient templates for usage modal
$stmt = $pdo->prepare("SELECT * FROM ingredient_templates ORDER BY item_name");
$stmt->execute();
$ingredient_templates = $stmt->fetchAll();

// ─── Filters & Pagination ───
$search_term = trim($_GET['search'] ?? '');
$category_filter = trim($_GET['category'] ?? '');
$per_page = max(1, intval($_GET['per_page'] ?? 15));
$prod_current_page = max(1, intval($_GET['page'] ?? 1));
$offset = ($prod_current_page - 1) * $per_page;

$categories = ['BIG TIME Burgers', 'MinuteBurgers', 'Hotdogs', 'Add-ons', 'Drinks'];

// Build WHERE clause
$where_clauses = ["status = 'active'"];
$params = [];

if ($search_term !== '') {
    $where_clauses[] = "name LIKE ?";
    $params[] = '%' . $search_term . '%';
}

if ($category_filter !== '' && in_array($category_filter, $categories)) {
    $where_clauses[] = "category = ?";
    $params[] = $category_filter;
}

$where_sql = 'WHERE ' . implode(' AND ', $where_clauses);

// Count total matching records
$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM products $where_sql");
$count_stmt->execute($params);
$total_records = $count_stmt->fetchColumn();
$total_pages = (int) max(1, ceil($total_records / $per_page));

// Clamp current page
if ($prod_current_page > $total_pages) {
    $prod_current_page = $total_pages;
    $offset = ($prod_current_page - 1) * $per_page;
}

// Fetch only the current page
$stmt = $pdo->prepare("SELECT * FROM products $where_sql ORDER BY category, name LIMIT $per_page OFFSET $offset");
$stmt->execute($params);
$products = $stmt->fetchAll();

// Also fetch ALL active products for the usage modal dropdown
$all_products_stmt = $pdo->prepare("SELECT id, name FROM products WHERE status = 'active' ORDER BY category, name");
$all_products_stmt->execute();
$all_products = $all_products_stmt->fetchAll();

// Category badge CSS class map
$category_classes = [
    'BIG TIME Burgers' => 'cat-bigtime',
    'MinuteBurgers' => 'cat-minute',
    'Hotdogs' => 'cat-hotdog',
    'Add-ons' => 'cat-addon',
    'Drinks' => 'cat-drink',
];

// Category emoji map
$category_emojis = [
    'BIG TIME Burgers' => '🍔',
    'MinuteBurgers' => '🍔',
    'Hotdogs' => '🌭',
    'Add-ons' => '🍟',
    'Drinks' => '🥤',
];

$filters_active = ($search_term !== '' || $category_filter !== '');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - Minute Burger Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        /* Page-specific styles for Products */
        .usage-form {
            background: var(--bg);
            padding: 1.25rem;
            border-radius: var(--radius);
            border: 1px solid var(--border);
            margin-top: 1rem;
        }

        .usage-form h5 {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.75rem;
        }

        .usage-item {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
            align-items: center;
        }

        .usage-item select {
            flex: 2;
        }

        .usage-item input {
            flex: 1;
        }

        .remove-usage {
            background: var(--red);
            color: white;
            border: none;
            width: 32px;
            height: 32px;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: var(--transition);
            flex-shrink: 0;
        }

        .remove-usage:hover {
            background: #dc2626;
        }

        .add-usage {
            background: var(--green);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 600;
            font-family: inherit;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            margin-top: 0.5rem;
            transition: var(--transition);
        }

        .add-usage:hover {
            background: #059669;
        }

        /* Inventory alert container */
        .inventory-alert-container {
            position: fixed;
            top: 80px;
            right: 20px;
            z-index: 2000;
            max-width: 380px;
        }

        .inventory-alert {
            background: var(--bg-card);
            border-radius: var(--radius);
            box-shadow: var(--shadow-lg);
            margin-bottom: 0.75rem;
            overflow: hidden;
            animation: slideIn 0.3s ease;
            border-left: 4px solid;
        }

        .inventory-alert.critical {
            border-left-color: var(--red);
        }

        .inventory-alert.warning {
            border-left-color: var(--amber);
        }

        .inventory-alert.info {
            border-left-color: var(--blue);
        }

        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        @keyframes slideOut {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }

        .alert-header {
            padding: 0.85rem 1rem;
            background: var(--bg);
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
        }

        .alert-header h4 {
            margin: 0;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 600;
        }

        .alert-content {
            padding: 0.85rem 1rem;
            max-height: 300px;
            overflow-y: auto;
        }

        .alert-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0;
            border-bottom: 1px solid var(--border);
        }

        .alert-item:last-child {
            border-bottom: none;
        }

        .alert-item .item-name {
            font-weight: 600;
            font-size: 0.85rem;
            color: var(--text-primary);
        }

        .alert-item .item-quantity {
            font-weight: 700;
            font-size: 0.85rem;
        }

        .quantity-critical {
            color: var(--red);
        }

        .quantity-warning {
            color: var(--amber);
        }

        .alert-item .item-min {
            font-size: 0.75rem;
            color: var(--text-muted);
        }

        .alert-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }

        .alert-actions button {
            padding: 0.35rem 0.75rem;
            font-size: 0.75rem;
        }

        /* Category badge styles */
        .category-badge {
            display: inline-block;
            padding: 0.2rem 0.55rem;
            border-radius: 6px;
            font-size: 0.7rem;
            font-weight: 600;
            white-space: nowrap;
        }

        .category-badge.cat-bigtime { background: #fef3c7; color: #92400e; }
        .category-badge.cat-minute { background: #fee2e2; color: #dc2626; }
        .category-badge.cat-hotdog { background: #ffedd5; color: #c2410c; }
        .category-badge.cat-addon { background: #dcfce7; color: #16a34a; }
        .category-badge.cat-drink { background: #dbeafe; color: #2563eb; }

        /* Table toolbar */
        .table-toolbar {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 1.25rem;
            border-top: 1px solid var(--border);
            border-bottom: 1px solid var(--border);
            background: var(--bg-card);
            position: relative;
            z-index: 1;
        }

        .table-toolbar > * {
            margin: 0 0.75rem 0.75rem 0;
        }

        .table-toolbar-left {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
        }

        .table-toolbar-left > * {
            margin: 0 0.75rem 0.75rem 0;
        }

        .table-toolbar-right {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
        }

        .table-toolbar-right > * {
            margin: 0 0.75rem 0.75rem 0;
        }

        .table-search {
            position: relative;
            display: flex;
            align-items: center;
        }

        .table-search i {
            position: absolute;
            left: 0.75rem;
            color: var(--text-muted);
            font-size: 1.1rem;
        }

        .table-search input {
            padding-left: 2.25rem;
            min-width: 220px;
        }

        .per-page-select {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.85rem;
            color: var(--text-muted);
        }

        .per-page-select select {
            width: auto;
            min-width: 65px;
            padding: 0.4rem 0.5rem;
            font-size: 0.85rem;
        }

        /* Empty state styling */
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: var(--text-muted);
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 0.75rem;
            display: block;
        }

        .empty-state p {
            font-size: 0.9rem;
            margin: 0;
        }

        /* Action dropdown */
        .action-dropdown {
            position: relative;
            display: inline-block;
        }

        .action-dropdown-toggle {
            background: none;
            border: 1px solid var(--border);
            border-radius: 8px;
            width: 34px;
            height: 34px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: var(--text-muted);
            transition: var(--transition);
        }

        .action-dropdown-toggle:hover {
            background: var(--bg);
            color: var(--text-primary);
            border-color: var(--text-muted);
        }

        .action-dropdown-toggle i {
            font-size: 1.2rem;
        }

        .action-dropdown-menu {
            display: none;
            position: absolute;
            right: 0;
            top: 100%;
            margin-top: 4px;
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            box-shadow: var(--shadow-lg);
            min-width: 180px;
            z-index: 1000;
            padding: 0.35rem 0;
        }

        .action-dropdown-menu.show {
            display: block;
        }

        .action-dropdown-menu button,
        .action-dropdown-menu .action-link,
        .action-dropdown-menu .action-edit,
        .action-dropdown-menu .action-archive {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            width: 100%;
            padding: 0.5rem 1rem;
            border: none;
            background: none;
            font-size: 0.85rem;
            font-family: inherit;
            cursor: pointer;
            color: var(--text-primary);
            transition: var(--transition);
            text-align: left;
        }

        .action-dropdown-menu button:hover,
        .action-dropdown-menu .action-link:hover,
        .action-dropdown-menu .action-edit:hover {
            background: var(--bg);
        }

        .action-dropdown-menu .action-link i { color: var(--blue); }
        .action-dropdown-menu .action-edit i { color: var(--amber); }
        .action-dropdown-menu .action-archive { color: var(--red); }
        .action-dropdown-menu .action-archive:hover { background: var(--red-light); }
        .action-dropdown-menu .action-archive i { color: var(--red); }

        .action-dropdown-divider {
            height: 1px;
            background: var(--border);
            margin: 0.25rem 0;
        }

        .action-dropdown-menu form {
            margin: 0;
        }

        @media (max-width: 600px) {
            .table-toolbar { flex-direction: column; align-items: stretch; }
            .table-toolbar-left { flex-direction: column; }
            .table-search input { min-width: unset; width: 100%; }
            .table-toolbar-right { justify-content: flex-end; }
        }
    </style>

</head>

<body>
    <div class="admin-layout">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>

        <div class="main-content">
            <?php include __DIR__ . '/../includes/header.php'; ?>

            <div class="content-area">
                <?php if (!empty($_GET['message'])): ?>
                    <?php $msg_type = in_array(($_GET['type'] ?? ''), ['success', 'error', 'warning', 'info']) ? ($_GET['type'] ?? 'success') : 'success'; ?>
                    <div class="message <?php echo htmlspecialchars($msg_type); ?>">
                        <?php echo htmlspecialchars($_GET['message'], ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class='bx bx-food-menu'></i> Product Management</h3>
                        <button class="btn btn-primary" onclick="showAddProductForm()">
                            <i class='bx bx-plus'></i> Add New Product
                        </button>
                    </div>

                    <!-- Table Toolbar: Search, Filter, Per-page -->
                    <div class="table-toolbar">
                        <div class="table-toolbar-left">
                            <form method="GET" style="display:inline-flex; align-items:center; flex-wrap:wrap;" id="filter-form">
                                <div class="table-search">
                                    <i class='bx bx-search'></i>
                                    <input type="text" name="search" class="form-control" placeholder="Search products..." value="<?php echo htmlspecialchars($search_term); ?>">
                                </div>
                                <select name="category" class="form-control" style="width:auto;min-width:150px;" onchange="this.form.submit()">
                                    <option value="">All Categories</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $category_filter === $cat ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cat); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="hidden" name="per_page" value="<?php echo $per_page; ?>">
                                <button type="submit" class="btn btn-outline btn-sm">
                                    <i class='bx bx-search' style="margin-right:4px;"></i> Search
                                </button>
                                <?php if ($filters_active): ?>
                                    <a href="products.php<?php echo $per_page !== 15 ? '?per_page=' . $per_page : ''; ?>" class="btn btn-outline btn-sm" style="text-decoration:none;">
                                        <i class='bx bx-x' style="margin-right:4px;"></i> Clear
                                    </a>
                                <?php endif; ?>
                            </form>
                        </div>
                        <div class="table-toolbar-right">
                            <div class="per-page-select">
                                <span>Show</span>
                                <form method="GET" id="perpage-form">
                                    <input type="hidden" name="search" value="<?php echo htmlspecialchars($search_term); ?>">
                                    <input type="hidden" name="category" value="<?php echo htmlspecialchars($category_filter); ?>">
                                    <select name="per_page" class="form-control" onchange="this.form.submit()">
                                        <?php foreach ([10, 15, 25, 50, 100] as $pp): ?>
                                            <option value="<?php echo $pp; ?>" <?php echo $per_page === $pp ? 'selected' : ''; ?>><?php echo $pp; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </form>
                                <span>entries</span>
                            </div>
                        </div>
                    </div>

                    <!-- Unified Products Table -->
                    <div class="table-container">
                        <?php if (empty($products)): ?>
                            <div class="empty-state">
                                <i class='bx bx-package'></i>
                                <p>No products found<?php echo $filters_active ? ' matching your filters' : ''; ?>.</p>
                            </div>
                        <?php else: ?>
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Image</th>
                                        <th>Product Name</th>
                                        <th>Category</th>
                                        <th>Price</th>
                                        <th>Stock</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($products as $product): ?>
                                        <tr>
                                            <td>
                                                <div class="product-image-small">
                                                    <?php
                                                    $image_path = '../assets/images/products/' . ($product['image'] ?? 'default-product.png');
                                                    $emoji = $category_emojis[$product['category']] ?? '🍔';
                                                    if (file_exists($image_path) && !empty($product['image']) && $product['image'] !== 'default-product.png'): ?>
                                                        <img src="<?php echo $image_path; ?>"
                                                            alt="<?php echo htmlspecialchars($product['name']); ?>">
                                                    <?php else: ?>
                                                        <div class="product-emoji-small"><?php echo $emoji; ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td><strong><?php echo htmlspecialchars($product['name']); ?></strong></td>
                                            <td>
                                                <span class="category-badge <?php echo $category_classes[$product['category']] ?? ''; ?>">
                                                    <?php echo htmlspecialchars($product['category']); ?>
                                                </span>
                                            </td>
                                            <td><strong class="text-muted"><?php echo number_format($product['price'], 2); ?></strong></td>
                                            <td><?php echo $product['stock']; ?></td>
                                            <td>
                                                <span class="status-badge <?php echo $product['status'] === 'active' ? 'status-active' : 'status-inactive'; ?>">
                                                    <?php echo ucfirst($product['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="action-dropdown">
                                                    <button class="action-dropdown-toggle" type="button" aria-label="Actions menu" onclick="toggleDropdown(this)">
                                                        <i class='bx bx-dots-vertical-rounded'></i>
                                                    </button>
                                                    <div class="action-dropdown-menu">
                                                        <button class="action-link" onclick="showUsageModal(<?php echo $product['id']; ?>)">
                                                            <i class='bx bx-link'></i> Link Inventory
                                                        </button>
                                                        <button class="action-edit" onclick="editProduct(<?php echo $product['id']; ?>)">
                                                            <i class='bx bx-edit'></i> Edit Product
                                                        </button>
                                                        <div class="action-dropdown-divider"></div>
                                                        <form method="POST" onsubmit="return askConfirm(event, 'Archive this product?')">
                                                            <?= csrfField() ?>
                                                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                                            <input type="hidden" name="_search" value="<?php echo htmlspecialchars($search_term); ?>">
                                                            <input type="hidden" name="_category" value="<?php echo htmlspecialchars($category_filter); ?>">
                                                            <input type="hidden" name="_page" value="<?php echo $prod_current_page; ?>">
                                                            <input type="hidden" name="_per_page" value="<?php echo $per_page; ?>">
                                                            <button type="submit" name="delete_product" class="action-archive">
                                                                <i class='bx bx-archive-in'></i> Archive
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>

                    <!-- Pagination Bar -->
                    <?php
                    $pagination_params = array_filter([
                        'search' => $search_term !== '' ? $search_term : null,
                        'category' => $category_filter !== '' ? $category_filter : null,
                        'per_page' => $per_page !== 15 ? $per_page : null,
                    ]);
                    $showing_start = $total_records > 0 ? $offset + 1 : 0;
                    $showing_end = min($offset + $per_page, $total_records);
                    ?>
                    <?php if ($total_pages > 1): ?>
                        <div class="pagination-bar">
                            <div class="pagination-info">
                                Showing <?php echo $showing_start; ?>–<?php echo $showing_end; ?> of <?php echo $total_records; ?> products
                            </div>
                            <div class="pagination-controls">
                                <?php if ($prod_current_page > 1): ?>
                                    <a href="?<?php echo http_build_query(array_merge($pagination_params, ['page' => $prod_current_page - 1])); ?>" class="page-btn"><i class='bx bx-chevron-left'></i></a>
                                <?php else: ?>
                                    <span class="page-btn disabled"><i class='bx bx-chevron-left'></i></span>
                                <?php endif; ?>

                                <?php
                                $start_page = max(1, $prod_current_page - 2);
                                $end_page = min($total_pages, $prod_current_page + 2);

                                if ($start_page > 1): ?>
                                    <a href="?<?php echo http_build_query(array_merge($pagination_params, ['page' => 1])); ?>" class="page-btn">1</a>
                                    <?php if ($start_page > 2): ?><span class="page-dots">...</span><?php endif; ?>
                                <?php endif; ?>

                                <?php for ($p = $start_page; $p <= $end_page; $p++): ?>
                                    <a href="?<?php echo http_build_query(array_merge($pagination_params, ['page' => $p])); ?>"
                                       class="page-btn <?php echo $p === $prod_current_page ? 'active' : ''; ?>"><?php echo $p; ?></a>
                                <?php endfor; ?>

                                <?php if ($end_page < $total_pages): ?>
                                    <?php if ($end_page < $total_pages - 1): ?><span class="page-dots">...</span><?php endif; ?>
                                    <a href="?<?php echo http_build_query(array_merge($pagination_params, ['page' => $total_pages])); ?>" class="page-btn"><?php echo $total_pages; ?></a>
                                <?php endif; ?>

                                <?php if ($prod_current_page < $total_pages): ?>
                                    <a href="?<?php echo http_build_query(array_merge($pagination_params, ['page' => $prod_current_page + 1])); ?>" class="page-btn"><i class='bx bx-chevron-right'></i></a>
                                <?php else: ?>
                                    <span class="page-btn disabled"><i class='bx bx-chevron-right'></i></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="pagination-bar">
                            <div class="pagination-info">
                                Showing <?php echo $total_records; ?> product<?php echo $total_records !== 1 ? 's' : ''; ?>
                            </div>
                            <div class="pagination-controls"></div>
                        </div>
                    <?php endif; ?>
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
                    <!-- Hidden fields to preserve filters on form submit -->
                    <input type="hidden" name="_search" value="<?php echo htmlspecialchars($search_term); ?>">
                    <input type="hidden" name="_category" value="<?php echo htmlspecialchars($category_filter); ?>">
                    <input type="hidden" name="_page" value="<?php echo $prod_current_page; ?>">
                    <input type="hidden" name="_per_page" value="<?php echo $per_page; ?>">
                    <div class="form-group">
                        <label class="form-label">Product Image</label>
                        <input type="file" class="form-control" id="product_image" name="product_image"
                            accept="image/*">
                        <small class="text-muted">Recommended size: 300x300 pixels. Leave empty to use default
                            image.</small>
                        <div id="image-preview" style="margin-top: 10px; display: none;">
                            <img id="preview-img" src="#" alt="Preview"
                                style="max-width: 100px; max-height: 100px; border-radius: 4px;">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Product Name</label>
                        <input type="text" class="form-control" id="product_name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Price</label>
                        <input type="number" class="form-control" id="product_price" name="price" step="0.01" min="0"
                            required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Stock</label>
                        <input type="number" class="form-control" id="product_stock" name="stock" min="0" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Category</label>
                        <select class="form-control" id="product_category" name="category" required>
                            <option value="BIG TIME Burgers">BIGTIME Burgers</option>
                            <option value="MinuteBurgers">Minute Burgers</option>
                            <option value="Hotdogs">Hotdogs</option>
                            <option value="Add-ons">Add-ons</option>
                            <option value="Drinks">Drinks</option>
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
                        <button type="button" class="btn btn-outline"
                            onclick="closeModal('product-modal')">Cancel</button>
                        <button type="submit" class="btn btn-primary" name="add_product" id="product-submit-btn">Add
                            Product</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Product-Inventory Usage Modal -->
    <div class="modal" id="usage-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Link Inventory to Product</h3>
                <button class="modal-close" aria-label="Close modal" onclick="closeModal('usage-modal')"><i class='bx bx-x'></i></button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Select Product</label>
                    <select class="form-control" id="usage-product-id">
                        <?php foreach ($all_products as $prod): ?>
                            <option value="<?php echo $prod['id']; ?>">
                                <?php echo htmlspecialchars($prod['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="usage-form">
                    <h5>Inventory Items Used</h5>
                    <div id="usage-items"></div>
                    <button type="button" class="add-usage" onclick="addUsageItem()">
                        <i class='bx bx-plus'></i> Add Inventory Item
                    </button>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn btn-outline" onclick="closeModal('usage-modal')">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveInventoryUsage()">Save Usage</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Alert Panel -->
    <div class="inventory-alert-container" id="alert-panel" style="display: none;"></div>

    <script>
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
            if (modalId === 'product-modal') {
                document.getElementById('product-submit-btn').disabled = false;
            }
        }

        function showModal(modalId) {
            document.getElementById(modalId).style.display = 'flex';
        }

        // Product functions
        function showAddProductForm() {
            document.getElementById('product-modal-title').textContent = 'Add New Product';
            document.getElementById('product-form').reset();
            document.getElementById('product_id').value = '';
            document.getElementById('image-preview').style.display = 'none';
            document.getElementById('product-submit-btn').name = 'add_product';
            document.getElementById('product-submit-btn').textContent = 'Add Product';
            // Re-set hidden filter fields after reset
            document.querySelector('#product-form input[name="_search"]').value = '<?php echo htmlspecialchars($search_term, ENT_QUOTES); ?>';
            document.querySelector('#product-form input[name="_category"]').value = '<?php echo htmlspecialchars($category_filter, ENT_QUOTES); ?>';
            document.querySelector('#product-form input[name="_page"]').value = '<?php echo $prod_current_page; ?>';
            document.querySelector('#product-form input[name="_per_page"]').value = '<?php echo $per_page; ?>';
            showModal('product-modal');
        }

        function editProduct(id) {
            document.getElementById('product-modal-title').textContent = 'Edit Product';
            document.getElementById('product_id').value = id;
            document.getElementById('product-submit-btn').name = 'update_product';
            document.getElementById('product-submit-btn').textContent = 'Update Product';
            document.getElementById('image-preview').style.display = 'none';
            document.getElementById('product-submit-btn').disabled = true;

            fetch(`products.php?action=get_product&id=${id}`)
                .then(response => response.json())
                .then(result => {
                    if (result.success && result.data) {
                        const product = result.data;
                        document.getElementById('product_name').value = product.name || '';
                        document.getElementById('product_price').value = product.price || '';
                        document.getElementById('product_stock').value = product.stock || '';
                        document.getElementById('product_category').value = product.category || 'BIG TIME Burgers';
                        document.getElementById('product_status').value = product.status || 'active';

                        if (product.image && product.image !== 'default-product.png') {
                            const imagePreview = document.getElementById('image-preview');
                            const previewImg = document.getElementById('preview-img');
                            previewImg.src = '../assets/images/products/' + product.image;
                            imagePreview.style.display = 'block';
                        }
                        document.getElementById('product-submit-btn').disabled = false;
                        showModal('product-modal');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('product-submit-btn').disabled = false;
                    showToastMsg('Error loading product data', 'error');
                });
        }

        function showUsageModal(productId) {
            document.getElementById('usage-product-id').value = productId;
            document.getElementById('usage-items').innerHTML = '';
            addUsageItem();
            showModal('usage-modal');
        }

        function addUsageItem() {
            const container = document.getElementById('usage-items');
            const index = container.children.length;

            let options = '<option value="">Select Ingredient</option>';
            <?php foreach ($ingredient_templates as $tpl): ?>
                options += `<option value="<?php echo $tpl['id']; ?>"><?php echo htmlspecialchars($tpl['item_name']); ?> (<?php echo $tpl['unit']; ?>)</option>`;
            <?php endforeach; ?>

            const itemHtml = `
                <div class="usage-item" id="usage-item-${index}">
                    <select class="form-control" name="template_id[]" required>
                        ${options}
                    </select>
                    <input type="number" class="form-control" name="qty_required[]" placeholder="Qty per product" step="0.01" min="0.01" required>
                    <button type="button" class="remove-usage" aria-label="Remove inventory item" onclick="removeUsageItem(${index})">
                        <i class='bx bx-trash'></i>
                    </button>
                </div>
            `;

            container.insertAdjacentHTML('beforeend', itemHtml);
        }

        function removeUsageItem(index) {
            document.getElementById(`usage-item-${index}`).remove();
        }

        function saveInventoryUsage() {
            const productId = document.getElementById('usage-product-id').value;
            const items = [];

            document.querySelectorAll('.usage-item').forEach(item => {
                const templateId = item.querySelector('select[name="template_id[]"]').value;
                const qtyRequired = item.querySelector('input[name="qty_required[]"]').value;

                if (templateId && qtyRequired) {
                    items.push({ template_id: templateId, qty_required: qtyRequired });
                }
            });

            if (items.length === 0) {
                showToastMsg('Please add at least one ingredient', 'warning');
                return;
            }

            const formData = new FormData();
            formData.append('product_id', productId);
            formData.append('ingredients', JSON.stringify(items));
            formData.append('save_ingredients', 'true');
            formData.append('_search', '<?php echo htmlspecialchars($search_term, ENT_QUOTES); ?>');
            formData.append('_category', '<?php echo htmlspecialchars($category_filter, ENT_QUOTES); ?>');
            formData.append('_page', '<?php echo $prod_current_page; ?>');
            formData.append('_per_page', '<?php echo $per_page; ?>');

            fetch('products.php', {
                method: 'POST',
                body: formData
            })
                .then(response => {
                    if (response.redirected) {
                        window.location.href = response.url;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToastMsg('Error saving ingredients', 'error');
                });
        }

        // Image preview
        document.getElementById('product_image')?.addEventListener('change', function (e) {
            const preview = document.getElementById('image-preview');
            const previewImg = document.getElementById('preview-img');

            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    previewImg.src = e.target.result;
                    preview.style.display = 'block';
                }
                reader.readAsDataURL(this.files[0]);
            } else {
                preview.style.display = 'none';
            }
        });

        // Auto-dismiss success messages
        setTimeout(function() {
            const msg = document.querySelector('.message');
            if (msg) msg.style.display = 'none';
        }, 4000);

        // Alert functions (same as inventory.php)
        // ... (include all alert functions from inventory.php)
    </script>
    <?php include __DIR__ . '/../includes/confirm_modal.php'; ?>
</body>

</html>
