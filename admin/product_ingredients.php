<?php
require_once __DIR__ . '/bootstrap.php';
requirePermission('products_manage');

$active_tab = 'products';
$message = isset($_GET['message']) ? $_GET['message'] : '';
$message_type = isset($_GET['type']) ? $_GET['type'] : '';

// Fetch all products
$stmt = $pdo->prepare("SELECT * FROM products WHERE status = 'active' ORDER BY category, name");
$stmt->execute();
$products = $stmt->fetchAll();

// Fetch all ingredient templates (canonical definitions)
$stmt = $pdo->prepare("SELECT * FROM ingredient_templates ORDER BY item_name");
$stmt->execute();
$ingredient_templates = $stmt->fetchAll();

// Handle saving ingredients for a product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_ingredients'])) {
    requireCsrfToken();
    $product_id = (int)$_POST['product_id'];

    try {
        $pdo->beginTransaction();

        // Delete existing ingredients for this product
        $stmt = $pdo->prepare("DELETE FROM product_ingredients WHERE product_id = ?");
        $stmt->execute([$product_id]);

        // Insert new ingredients (template-based BOM)
        if (isset($_POST['template']) && is_array($_POST['template'])) {
            $stmt = $pdo->prepare("
                INSERT INTO product_ingredients (product_id, template_id, inventory_id, qty_required) 
                VALUES (?, ?, ?, ?)
            ");

            foreach ($_POST['template'] as $index => $template_id) {
                if (!empty($template_id) && isset($_POST['quantity'][$index]) && !empty($_POST['quantity'][$index])) {
                    // Resolve the first branch-specific inventory item for this template (for display/backward compat)
                    $invStmt = $pdo->prepare("SELECT id FROM inventory WHERE template_id = ? AND deleted_at IS NULL LIMIT 1");
                    $invStmt->execute([$template_id]);
                    $inventory_id = $invStmt->fetchColumn() ?: 0;

                    $stmt->execute([
                        $product_id,
                        $template_id,
                        $inventory_id,
                        $_POST['quantity'][$index]
                    ]);
                }
            }
        }

        $pdo->commit();
        $message = "Ingredients saved successfully!";
        $message_type = "success";
    } catch (Exception $e) {
        $pdo->rollBack();
        $message = "Error: " . $e->getMessage();
        $message_type = "error";
    }

    header("Location: product_ingredients.php?message=" . urlencode($message) . "&type=" . $message_type);
    exit();
}

// Handle AJAX request to get product ingredients
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_ingredients') {
    if (!hasPermission('products_manage')) {
        http_response_code(403);
        echo json_encode(['error' => 'Unauthorized']);
        exit();
    }
    $product_id = (int)$_GET['product_id'];

    $stmt = $pdo->prepare("
        SELECT pi.*, t.item_name, t.unit AS template_unit, t.category AS template_category
        FROM product_ingredients pi
        JOIN ingredient_templates t ON t.id = pi.template_id
        WHERE pi.product_id = ?
    ");
    $stmt->execute([$product_id]);
    $ingredients = $stmt->fetchAll();

    header('Content-Type: application/json');
    echo json_encode($ingredients);
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Ingredients - Minute Burger Admin</title>
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/minute1/assets/css/admin.css">
    <style>
        .ingredients-container {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 2rem;
        }

        .product-list {
            background: var(--white);
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: var(--shadow);
            height: fit-content;
        }

        .product-item {
            padding: 1rem;
            border-bottom: 1px solid var(--light-gray);
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .product-item:hover {
            background: var(--light-gray);
        }

        .product-item.selected {
            background: linear-gradient(135deg, var(--harvest-orange), var(--chocolate));
            color: white;
            border-radius: 8px;
        }

        .product-item.selected .product-category {
            color: rgba(255, 255, 255, 0.8);
        }

        .product-emoji {
            font-size: 1.5rem;
        }

        .product-info h4 {
            margin-bottom: 0.25rem;
        }

        .product-category {
            font-size: 0.8rem;
            color: #666;
        }

        .ingredients-panel {
            background: var(--white);
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: var(--shadow);
        }

        .ingredient-row {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1rem;
            align-items: center;
            background: var(--light-bg);
            padding: 1rem;
            border-radius: 8px;
        }

        .ingredient-row select,
        .ingredient-row input {
            padding: 0.5rem;
            border: 2px solid var(--apricot-cream);
            border-radius: 6px;
            font-size: 0.9rem;
        }

        .ingredient-row select {
            flex: 2 1 160px;
        }

        .ingredient-row input[type="number"] {
            flex: 1 1 80px;
            min-width: 80px;
        }

        .ingredient-row .unit-select {
            flex: 0.5 1 80px;
            min-width: 80px;
        }

        .remove-ingredient {
            background: var(--red);
            color: white;
            border: none;
            width: 32px;
            height: 32px;
            border-radius: 6px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .remove-ingredient:hover {
            background: #c0392b;
        }

        .add-ingredient {
            background: var(--success);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            margin: 1rem 0;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .add-ingredient:hover {
            background: #229954;
        }

        .current-stock {
            font-size: 0.8rem;
            color: #666;
            margin-top: 0.25rem;
        }

        .low-stock-warning {
            color: var(--red);
            font-weight: 600;
        }

        .ingredient-summary {
            background: var(--light-gray);
            padding: 1rem;
            border-radius: 8px;
            margin-top: 1rem;
        }

        .summary-title {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--dark-gray);
        }

        .stock-info {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 0.5rem;
            margin-top: 0.5rem;
        }

        .stock-item {
            background: var(--white);
            padding: 0.5rem;
            border-radius: 4px;
            font-size: 0.9rem;
        }

        .stock-item.warning {
            border-left: 3px solid var(--warning);
        }

        .stock-item.critical {
            border-left: 3px solid var(--red);
        }

        @media (max-width: 768px) {
            .ingredients-container {
                grid-template-columns: 1fr;
            }
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
                        <h3 class="card-title">
                            <i class='bx bx-link'></i>
                            Product Ingredients Management
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="ingredients-container">
                            <!-- Product List -->
                            <div class="product-list">
                                <h4 style="margin-bottom: 1rem; color: var(--brown);">
                                    <i class='bx bx-food-menu'></i> Select Product
                                </h4>
                                <div style="max-height: 500px; overflow-y: auto;">
                                    <?php foreach ($products as $product):
                                        $emoji = match ($product['category']) {
                                            'BIG TIME Burgers', 'MinuteBurgers' => '🍔',
                                            'Hotdogs' => '🌭',
                                            'Drinks' => '🥤',
                                            'Add-ons' => '🍟',
                                            default => '🍽️'
                                        };
                                        ?>
                                        <div class="product-item"
                                            onclick="selectProduct(<?php echo $product['id']; ?>, this)">
                                            <span class="product-emoji"><?php echo $emoji; ?></span>
                                            <div class="product-info">
                                                <h4><?php echo htmlspecialchars($product['name']); ?></h4>
                                                <div class="product-category"><?php echo $product['category']; ?></div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- Ingredients Panel -->
                            <div class="ingredients-panel" id="ingredients-panel">
                                <h4 style="margin-bottom: 1rem; color: var(--brown);">
                                    <i class='bx bx-package'></i>
                                    Ingredients for: <span id="selected-product-name">Select a product</span>
                                </h4>

                                <form method="POST" id="ingredients-form">
                                    <input type="hidden" name="product_id" id="product_id">
                                    <?php echo csrfField(); ?>

                                    <div id="ingredients-container">
                                        <!-- Ingredients will be added here dynamically -->
                                    </div>

                                    <button type="button" class="add-ingredient" onclick="addIngredientRow()">
                                        <i class='bx bx-plus'></i> Add Ingredient
                                    </button>

                                    <div id="stock-summary" class="ingredient-summary" style="display: none;">
                                        <div class="summary-title">Current Stock Levels:</div>
                                        <div class="stock-info" id="stock-info"></div>
                                    </div>

                                    <div class="form-actions">
                                        <button type="submit" class="btn btn-primary" name="save_ingredients"
                                            id="save-btn" disabled>
                                            <i class='bx bx-save'></i> Save Ingredients
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const ingredientTemplates = <?php echo json_encode($ingredient_templates); ?>;
        let currentProductId = null;
        let ingredientCount = 0;

        function selectProduct(productId, element) {
            document.querySelectorAll('.product-item').forEach(el => {
                el.classList.remove('selected');
            });

            element.classList.add('selected');

            currentProductId = productId;
            document.getElementById('product_id').value = productId;
            document.getElementById('selected-product-name').textContent =
                element.querySelector('h4').textContent;

            loadIngredients(productId);

            document.getElementById('save-btn').disabled = false;
        }

        function loadIngredients(productId) {
            fetch(`?action=get_ingredients&product_id=${productId}`)
                .then(response => response.json())
                .then(ingredients => {
                    const container = document.getElementById('ingredients-container');
                    container.innerHTML = '';
                    ingredientCount = 0;

                    if (ingredients.length > 0) {
                        ingredients.forEach(ing => {
                            addIngredientRow(ing);
                        });
                    } else {
                        addIngredientRow();
                    }

                    updateStockSummary();
                });
        }

        function addIngredientRow(data = null) {
            const container = document.getElementById('ingredients-container');
            const index = ingredientCount++;

            const row = document.createElement('div');
            row.className = 'ingredient-row';
            row.id = `ingredient-row-${index}`;

            let options = '<option value="">Select Ingredient</option>';
            ingredientTemplates.forEach(tpl => {
                const selected = (data && data.template_id == tpl.id) ? 'selected' : '';
                options += `<option value="${tpl.id}" ${selected}>${tpl.item_name} (${tpl.unit})</option>`;
            });

            row.innerHTML = `
                <select name="template[]" class="inventory-select" onchange="updateStockSummary()" required>
                    ${options}
                </select>
                <input type="number" name="quantity[]" value="${data ? data.qty_required : ''}" 
                       step="0.01" min="0.01" placeholder="Qty per product" required onchange="updateStockSummary()">
                <select name="unit[]" class="unit-select" disabled style="background:#f0f0f0;">
                    <option value="">from template</option>
                </select>
                <button type="button" class="remove-ingredient" aria-label="Remove ingredient" onclick="removeIngredient(${index})">
                    <i class='bx bx-trash'></i>
                </button>
            `;

            container.appendChild(row);
            updateStockSummary();
        }

        function removeIngredient(index) {
            document.getElementById(`ingredient-row-${index}`).remove();
            updateStockSummary();
        }

        function updateStockSummary() {
            const selects = document.querySelectorAll('.inventory-select');
            const quantities = document.querySelectorAll('input[name="quantity[]"]');
            const stockInfo = document.getElementById('stock-info');
            const summaryDiv = document.getElementById('stock-summary');

            let hasSelections = false;
            let html = '';

            for (let i = 0; i < selects.length; i++) {
                const select = selects[i];
                const quantity = quantities[i];

                if (select.value && quantity.value) {
                    hasSelections = true;
                    const selectedOption = select.options[select.selectedIndex];
                    const itemName = selectedOption.text.split(' (')[0];
                    const needed = parseFloat(quantity.value);

                    html += `
                        <div class="stock-item">
                            <strong>${itemName}:</strong>
                            ${needed ? `Need ${needed} per product` : ''}
                        </div>
                    `;
                }
            }

            if (hasSelections) {
                stockInfo.innerHTML = html;
                summaryDiv.style.display = 'block';
            } else {
                summaryDiv.style.display = 'none';
            }
        }
    </script>
    <?php include __DIR__ . '/includes/ai_chatbot_widget.php'; ?>
</body>

</html>