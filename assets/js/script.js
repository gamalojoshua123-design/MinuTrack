// POS System JavaScript
document.addEventListener('DOMContentLoaded', function () {
    // Initialize the POS system
    initPOS();

    // Check for low stock items on page load
    checkLowStock();
});

// Global variables
let cart = [];
let products = [];

// Initialize the POS system
function initPOS() {
    loadProducts();
    setupEventListeners();
    updateCartDisplay();
}

// Load products from the PHP data
function loadProducts() {
    // Use the products data passed from PHP
    products = productsData.map(product => ({
        id: product.id,
        name: product.name,
        price: parseFloat(product.price),
        stock: parseInt(product.stock),
        category: product.category,
        image: product.image || 'default-product.png' // Use actual image from database
    }));

    renderProducts(products);
}

// Set up event listeners
function setupEventListeners() {
    // Search functionality
    const searchInput = document.getElementById('search');
    if (searchInput) {
        searchInput.addEventListener('input', handleSearch);
    }

    // Category filter
    const categoryFilter = document.getElementById('category');
    if (categoryFilter) {
        categoryFilter.addEventListener('change', handleCategoryFilter);
    }

    // Payment input
    const cashInput = document.getElementById('cash');
    if (cashInput) {
        cashInput.addEventListener('input', calculateChange);
    }

    // Checkout button
    const checkoutBtn = document.getElementById('checkout');
    if (checkoutBtn) {
        checkoutBtn.addEventListener('click', handleCheckout);
    }

    // Clear cart button
    const clearCartBtn = document.getElementById('clear-cart');
    if (clearCartBtn) {
        clearCartBtn.addEventListener('click', clearCart);
    }

    // Modal close buttons
    const modalCloseBtns = document.querySelectorAll('.modal-close');
    modalCloseBtns.forEach(btn => {
        btn.addEventListener('click', function () {
            this.closest('.modal').style.display = 'none';
        });
    });

    // Close modal when clicking outside
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        modal.addEventListener('click', function (e) {
            if (e.target === this) {
                this.style.display = 'none';
            }
        });
    });
    

    // Payment input with enhanced validation
    if (cashInput) {
        cashInput.addEventListener('input', function () {
            calculateChange();
            validateCashInput();
        });

        cashInput.addEventListener('blur', validateCashInput);
    }

    // Also prevent form submission on Enter key in cash input
    if (cashInput) {
        cashInput.addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                handleCheckout(e);
            }
        });
    }
}

// Render products to the grid with actual images
function renderProducts(productsToRender) {
    const productsGrid = document.getElementById('products-grid');
    if (!productsGrid) return;

    productsGrid.innerHTML = '';

    if (productsToRender.length === 0) {
        productsGrid.innerHTML = `
            <div class="no-products">
                <p>No products available. Please add products in the admin panel.</p>
                <a href="admin.php" class="btn-primary">Go to Admin Panel</a>
            </div>
        `;
        return;
    }

    productsToRender.forEach(product => {
        const productCard = document.createElement('div');
        productCard.className = 'product-card';
        productCard.setAttribute('data-id', product.id);
        productCard.setAttribute('data-category', product.category);

        // Get appropriate emoji fallback
        const getEmoji = (category) => {
            switch (category) {
                case 'Burgers': return '🍔';
                case 'Drinks': return '🥤';
                case 'Add-ons': return '🍟';
                default: return '📦';
            }
        };

        const emoji = getEmoji(product.category);
        const imagePath = `assets/images/products/${product.image}`;

        productCard.innerHTML = `
            <div class="product-image">
                <img src="${imagePath}" alt="${product.name}" 
                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                <div class="product-emoji" style="display: none;">
                    ${emoji}
                </div>
            </div>
            <div class="product-name">${product.name}</div>
            <div class="product-price">₱${product.price.toFixed(2)}</div>
            <div class="product-stock ${product.stock < 10 ? 'low-stock' : ''}">
                Stock: ${product.stock}
            </div>
            <button class="btn-add" data-id="${product.id}" ${product.stock === 0 ? 'disabled' : ''}>
                ${product.stock === 0 ? 'Out of Stock' : 'Add to Cart'}
            </button>
        `;

        productsGrid.appendChild(productCard);
    });

    // Add event listeners to add buttons
    const addButtons = document.querySelectorAll('.btn-add:not(:disabled)');
    addButtons.forEach(button => {
        button.addEventListener('click', function () {
            const productId = parseInt(this.getAttribute('data-id'));
            addToCart(productId);
        });
    });
}

// Handle search functionality
function handleSearch() {
    const searchTerm = this.value.toLowerCase();
    const categoryFilter = document.getElementById('category');
    const selectedCategory = categoryFilter ? categoryFilter.value : 'all';

    let filteredProducts = products;

    // Apply search filter
    if (searchTerm) {
        filteredProducts = filteredProducts.filter(product =>
            product.name.toLowerCase().includes(searchTerm)
        );
    }

    // Apply category filter
    if (selectedCategory !== 'all') {
        filteredProducts = filteredProducts.filter(product =>
            product.category === selectedCategory
        );
    }

    renderProducts(filteredProducts);
}

// Handle category filter
function handleCategoryFilter() {
    const searchInput = document.getElementById('search');
    const searchTerm = searchInput ? searchInput.value.toLowerCase() : '';
    const selectedCategory = this.value;

    let filteredProducts = products;

    // Apply search filter
    if (searchTerm) {
        filteredProducts = filteredProducts.filter(product =>
            product.name.toLowerCase().includes(searchTerm)
        );
    }

    // Apply category filter
    if (selectedCategory !== 'all') {
        filteredProducts = filteredProducts.filter(product =>
            product.category === selectedCategory
        );
    }

    renderProducts(filteredProducts);
}

// Add product to cart
function addToCart(productId) {
    const product = products.find(p => p.id === productId);
    if (!product) return;

    // Check if product is already in cart
    const existingItem = cart.find(item => item.id === productId);

    if (existingItem) {
        // Check if we have enough stock
        if (existingItem.quantity < product.stock) {
            existingItem.quantity++;
            existingItem.subtotal = existingItem.quantity * existingItem.price;
        } else {
            alert(`Only ${product.stock} units of ${product.name} available in stock.`);
            return;
        }
    } else {
        // Add new item to cart
        if (product.stock > 0) {
            cart.push({
                id: product.id,
                name: product.name,
                price: product.price,
                quantity: 1,
                subtotal: product.price
            });
        } else {
            alert(`${product.name} is out of stock.`);
            return;
        }
    }

    updateCartDisplay();
}

// Update cart display
function updateCartDisplay() {
    const cartItems = document.getElementById('cart-items');
    const cartTotal = document.getElementById('cart-total');
    const checkoutBtn = document.getElementById('checkout');

    if (!cartItems || !cartTotal || !checkoutBtn) return;

    // Clear current cart items
    cartItems.innerHTML = '';

    if (cart.length === 0) {
        cartItems.innerHTML = '<div class="empty-cart-message">Your cart is empty</div>';
        cartTotal.textContent = '₱0.00';
        checkoutBtn.disabled = true;
        calculateChange();
        return;
    }

    // Add each item to cart display
    cart.forEach(item => {
        const cartItem = document.createElement('div');
        cartItem.className = 'cart-item';
        cartItem.innerHTML = `
            <div class="item-info">
                <div class="item-name">${item.name}</div>
                <div class="item-price">₱${item.price.toFixed(2)}</div>
            </div>
            <div class="item-quantity">
                <button class="quantity-btn decrease" data-id="${item.id}">-</button>
                <input type="number" class="quantity-input" value="${item.quantity}" min="1" data-id="${item.id}">
                <button class="quantity-btn increase" data-id="${item.id}">+</button>
            </div>
            <div class="item-total">₱${item.subtotal.toFixed(2)}</div>
            <button class="btn-remove" data-id="${item.id}">×</button>
        `;

        cartItems.appendChild(cartItem);
    });

    // Calculate total
    const total = cart.reduce((sum, item) => sum + item.subtotal, 0);
    cartTotal.textContent = `₱${total.toFixed(2)}`;

    // Enable/disable checkout button
    checkoutBtn.disabled = cart.length === 0;

    // Add event listeners to quantity controls
    const decreaseBtns = document.querySelectorAll('.quantity-btn.decrease');
    const increaseBtns = document.querySelectorAll('.quantity-btn.increase');
    const quantityInputs = document.querySelectorAll('.quantity-input');
    const removeBtns = document.querySelectorAll('.btn-remove');

    decreaseBtns.forEach(btn => {
        btn.addEventListener('click', function () {
            const itemId = parseInt(this.getAttribute('data-id'));
            updateQuantity(itemId, -1);
        });
    });

    increaseBtns.forEach(btn => {
        btn.addEventListener('click', function () {
            const itemId = parseInt(this.getAttribute('data-id'));
            updateQuantity(itemId, 1);
        });
    });

    quantityInputs.forEach(input => {
        input.addEventListener('change', function () {
            const itemId = parseInt(this.getAttribute('data-id'));
            const newQuantity = parseInt(this.value);

            if (newQuantity < 1) {
                this.value = 1;
                updateQuantity(itemId, 0, 1);
            } else {
                updateQuantity(itemId, 0, newQuantity);
            }
        });
    });

    removeBtns.forEach(btn => {
        btn.addEventListener('click', function () {
            const itemId = parseInt(this.getAttribute('data-id'));
            removeFromCart(itemId);
        });
    });

    // Update change calculation
    calculateChange();
}

// Update item quantity in cart
function updateQuantity(itemId, change, setQuantity = null) {
    const item = cart.find(item => item.id === itemId);
    if (!item) return;

    const product = products.find(p => p.id === itemId);
    if (!product) return;

    let newQuantity;

    if (setQuantity !== null) {
        newQuantity = setQuantity;
    } else {
        newQuantity = item.quantity + change;
    }

    // Check if we have enough stock
    if (newQuantity > product.stock) {
        alert(`Only ${product.stock} units of ${product.name} available in stock.`);
        return;
    }

    if (newQuantity < 1) {
        removeFromCart(itemId);
    } else {
        item.quantity = newQuantity;
        item.subtotal = item.quantity * item.price;
        updateCartDisplay();
    }
}

// Remove item from cart
function removeFromCart(itemId) {
    cart = cart.filter(item => item.id !== itemId);
    updateCartDisplay();
}

// Clear entire cart
function clearCart() {
    if (cart.length === 0) return;

    if (confirm('Are you sure you want to clear the cart?')) {
        cart = [];
        updateCartDisplay();
    }
}

// Calculate change
function calculateChange() {
    const cashInput = document.getElementById('cash');
    const changeDisplay = document.getElementById('change');

    if (!cashInput || !changeDisplay) return;

    const total = cart.reduce((sum, item) => sum + item.subtotal, 0);
    const cash = parseFloat(cashInput.value) || 0;
    const change = cash - total;

    if (change >= 0) {
        changeDisplay.textContent = `₱${change.toFixed(2)}`;
        changeDisplay.style.color = 'green';
    } else {
        changeDisplay.textContent = `-₱${Math.abs(change).toFixed(2)}`;
        changeDisplay.style.color = 'red';
    }

    // Run enhanced validation
    validateCashInput();
}

// Handle checkout with better validation
function handleCheckout(e) {
    e.preventDefault();

    const cashInput = document.getElementById('cash');
    const cash = parseFloat(cashInput.value) || 0;
    const total = cart.reduce((sum, item) => sum + item.subtotal, 0);

    // Client-side validation
    if (cart.length === 0) {
        alert('Your cart is empty. Please add items before checkout.');
        return;
    }

    if (cash <= 0) {
        alert('Please enter cash amount.');
        cashInput.focus();
        return;
    }

    if (cash < total) {
        alert('Insufficient payment. Cash received must be greater than or equal to total amount.');
        cashInput.focus();
        return;
    }

    // Check stock availability before submitting
    if (!checkStockAvailability()) {
        alert('Some items in your cart are out of stock. Please update your cart.');
        return;
    }

    // Set form data
    document.getElementById('total-amount').value = total;
    document.getElementById('cart-items-data').value = JSON.stringify(cart);
    document.getElementById('change-value').value = cash - total;

    // Show confirmation dialog
    if (confirm('Are you sure you want to complete this order?')) {
        // Generate client-side receipt first
        generateReceipt(total, cash);
        // Then submit the form after a delay to process on server
        setTimeout(() => {
            document.getElementById('checkout-form').submit();
        }, 1000);
    }
}

// Check stock availability
function checkStockAvailability() {
    for (const item of cart) {
        const product = products.find(p => p.id === item.id);
        if (!product || product.stock < item.quantity) {
            return false;
        }
    }
    return true;
}

// Generate receipt (for demo purposes)
function generateReceipt(total, cash) {
    const change = cash - total;
    const receiptWindow = window.open('', 'Receipt', 'width=400,height=600,left=100,top=100');

    if (!receiptWindow) {
        alert('Popup blocked! Please allow popups for this site to print receipts.');
        return;
    }

    receiptWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Angel's Burger - Receipt</title>
            <style>
                body { 
                    font-family: 'Courier New', monospace; 
                    margin: 0;
                    padding: 20px;
                    background-color: white;
                }
                .receipt {
                    max-width: 300px;
                    margin: 0 auto;
                }
                .header { 
                    text-align: center; 
                    margin-bottom: 20px;
                    border-bottom: 2px dashed #000;
                    padding-bottom: 10px;
                }
                .header h2 {
                    color: #E01A22;
                    margin: 0;
                    font-size: 24px;
                }
                .receipt-item { 
                    display: flex; 
                    justify-content: space-between; 
                    margin-bottom: 5px;
                    font-size: 14px;
                }
                .total { 
                    border-top: 1px dashed #000; 
                    padding-top: 10px; 
                    margin-top: 10px; 
                }
                .thank-you { 
                    text-align: center; 
                    margin-top: 20px;
                    font-style: italic;
                }
                @media print {
                    body {
                        padding: 0;
                    }
                }
            </style>
        </head>
        <body>
            <div class="receipt">
                <div class="header">
                    <h2>Angel's Burger</h2>
                    <p>Point of Sale Receipt</p>
                    <p>${new Date().toLocaleString()}</p>
                </div>
                
                <div class="items">
                    ${cart.map(item => `
                        <div class="receipt-item">
                            <span>${item.name} x${item.quantity}</span>
                            <span>₱${item.subtotal.toFixed(2)}</span>
                        </div>
                    `).join('')}
                </div>
                
                <div class="total">
                    <div class="receipt-item">
                        <strong>Total:</strong>
                        <strong>₱${total.toFixed(2)}</strong>
                    </div>
                    <div class="receipt-item">
                        <span>Cash:</span>
                        <span>₱${cash.toFixed(2)}</span>
                    </div>
                    <div class="receipt-item">
                        <span>Change:</span>
                        <span>₱${change.toFixed(2)}</span>
                    </div>
                </div>
                
                <div class="thank-you">
                    <p>Thank you for your order!</p>
                    <p>Please come again!</p>
                </div>
            </div>
            
            <script>
                // Auto-print when receipt window loads
                window.onload = function() {
                    window.print();
                    // Optional: close window after printing
                    setTimeout(() => {
                        window.close();
                    }, 1000);
                };
            </script>
        </body>
        </html>
    `);

    receiptWindow.document.close();
}

// Check for low stock items
function checkLowStock() {
    const lowStockItems = products.filter(product => product.stock < 10);

    if (lowStockItems.length > 0) {
        showLowStockAlert(lowStockItems);
    }
}

// Show low stock alert
function showLowStockAlert(lowStockItems) {
    const modal = document.getElementById('low-stock-modal');
    const list = document.getElementById('low-stock-list');

    if (!modal || !list) return;

    list.innerHTML = '';

    lowStockItems.forEach(item => {
        const listItem = document.createElement('div');
        listItem.className = 'low-stock-item';
        listItem.innerHTML = `
            <span>${item.name}</span>
            <span class="low-stock">${item.stock} left</span>
        `;
        list.appendChild(listItem);
    });

    modal.style.display = 'flex';
}

// Enhanced cash validation with visual feedback
function validateCashInput() {
    const cashInput = document.getElementById('cash');
    const validationMsg = document.getElementById('cash-validation');
    const total = cart.reduce((sum, item) => sum + item.subtotal, 0);
    const cash = parseFloat(cashInput.value) || 0;

    if (!validationMsg) {
        // Create validation message element if it doesn't exist
        const validationElement = document.createElement('div');
        validationElement.id = 'cash-validation';
        validationElement.className = 'payment-validation';
        cashInput.parentNode.appendChild(validationElement);
    }

    const validationElement = document.getElementById('cash-validation');

    if (cash === 0) {
        cashInput.classList.remove('insufficient', 'sufficient');
        validationElement.style.display = 'none';
        return false;
    }

    if (cash < total) {
        cashInput.classList.add('insufficient');
        cashInput.classList.remove('sufficient');
        validationElement.className = 'payment-validation error';
        validationElement.innerHTML = `Insufficient cash. Need ₱${(total - cash).toFixed(2)} more.`;
        validationElement.style.display = 'block';
        return false;
    } else {
        cashInput.classList.add('sufficient');
        cashInput.classList.remove('insufficient');
        validationElement.className = 'payment-validation success';
        validationElement.innerHTML = `Change: ₱${(cash - total).toFixed(2)}`;
        validationElement.style.display = 'block';
        return true;
    }
}

// Barcode scanning simulation
function simulateBarcodeScan(productCode) {
    // In a real application, this would be triggered by a barcode scanner
    const product = products.find(p => p.id === parseInt(productCode));

    if (product) {
        addToCart(product.id);
    } else {
        alert('Product not found!');
    }
}

// Inventory functions
function showAddInventoryForm() {
    document.getElementById('inventory-modal-title').textContent = 'Add New Inventory Item';
    document.getElementById('inventory-form').reset();
    document.getElementById('inventory_id').value = '';
    document.getElementById('inventory_item_name').value = '';
    document.getElementById('inventory_quantity').value = '';
    document.getElementById('inventory_min_stock').value = '';
    document.getElementById('inventory-submit-btn').name = 'add_inventory';
    document.getElementById('inventory-submit-btn').textContent = 'Add Item';
    document.getElementById('inventory-submit-btn').disabled = false;
    showModal('inventory-modal');
    // Auto-focus first input
    setTimeout(() => document.getElementById('inventory_item_name').focus(), 100);
}

function editInventory(id) {
    document.getElementById('inventory-modal-title').textContent = 'Edit Inventory Item';
    document.getElementById('inventory_id').value = id;
    document.getElementById('inventory-submit-btn').name = 'update_inventory';
    document.getElementById('inventory-submit-btn').textContent = 'Update Item';
    document.getElementById('inventory-submit-btn').disabled = true;

    // Fetch inventory data via AJAX
    fetch(`?action=get_inventory&id=${id}`)
        .then(response => {
            if (!response.ok) throw new Error('Failed to fetch inventory');
            return response.json();
        })
        .then(result => {
            if (result.success && result.data) {
                const inventory = result.data;
                document.getElementById('inventory_item_name').value = inventory.item_name || '';
                document.getElementById('inventory_quantity').value = inventory.quantity || '';
                document.getElementById('inventory_min_stock').value = inventory.min_stock || '';
                document.getElementById('inventory-submit-btn').disabled = false;
                document.getElementById('inventory_item_name').focus();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('inventory-submit-btn').disabled = false;
            alert('Error loading inventory data. Please try again.');
        });

    showModal('inventory-modal');
}
// Inventory Alert System
let alertPanelVisible = false;

// Function to check inventory alerts
function checkInventoryAlerts() {
    fetch('?action=get_inventory_alerts')
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                const data = result.data;
                updateAlertBadge(data.counts.total_alerts);

                // Show notifications for critical items
                if (data.counts.total_alerts > 0) {
                    showInventoryNotifications(data);
                }

                // Store data for later use
                window.inventoryAlertData = data;
            }
        })
        .catch(error => console.error('Error checking inventory:', error));
}

// Update notification badge
function updateAlertBadge(count) {
    const badge = document.getElementById('alert-count-badge');
    if (badge) {
        badge.textContent = count;
        badge.style.display = count > 0 ? 'flex' : 'none';
    }
}

// Toggle alert panel
function toggleAlertPanel() {
    const panel = document.getElementById('alert-panel');
    if (alertPanelVisible) {
        panel.style.display = 'none';
        alertPanelVisible = false;
    } else {
        if (window.inventoryAlertData) {
            renderAlertPanel(window.inventoryAlertData);
        } else {
            checkInventoryAlerts();
        }
        panel.style.display = 'block';
        alertPanelVisible = true;
    }
}

// Render alert panel with inventory data
function renderAlertPanel(data) {
    const panel = document.getElementById('alert-panel');

    let html = '';

    // Out of Stock Items (Critical)
    if (data.out_of_stock.length > 0) {
        html += `
            <div class="inventory-alert critical">
                <div class="alert-header" onclick="toggleAlert(this)">
                    <h4>
                        <i class='bx bx-error-circle' style="color: var(--red);"></i>
                        Out of Stock (${data.out_of_stock.length})
                    </h4>
                    <i class='bx bx-chevron-down'></i>
                </div>
                <div class="alert-content" style="display: block;">
                    ${data.out_of_stock.map(item => `
                        <div class="alert-item">
                            <span class="item-name">
                                ${escapeHtml(item.item_name)}
                                ${getCategoryBadge(item.item_name)}
                            </span>
                            <span class="item-quantity quantity-critical">0</span>
                        </div>
                    `).join('')}
                    <div class="alert-actions">
                        <button class="btn btn-primary btn-sm" onclick="quickOrder('out_of_stock')">
                            <i class='bx bx-cart'></i> Order Now
                        </button>
                        <button class="btn btn-outline btn-sm" onclick="showAddInventoryForm()">
                            <i class='bx bx-plus'></i> Add New
                        </button>
                    </div>
                </div>
            </div>
        `;
    }

    // Below Minimum Stock (Warning)
    if (data.below_minimum.length > 0) {
        html += `
            <div class="inventory-alert warning">
                <div class="alert-header" onclick="toggleAlert(this)">
                    <h4>
                        <i class='bx bx-error' style="color: var(--warning);"></i>
                        Below Minimum Stock (${data.below_minimum.length})
                    </h4>
                    <i class='bx bx-chevron-down'></i>
                </div>
                <div class="alert-content" style="display: block;">
                    ${data.below_minimum.map(item => `
                        <div class="alert-item">
                            <span class="item-name">
                                ${escapeHtml(item.item_name)}
                                ${getCategoryBadge(item.item_name)}
                                <span class="item-min">(Min: ${item.min_stock})</span>
                            </span>
                            <span class="item-quantity quantity-warning">${item.quantity}</span>
                        </div>
                    `).join('')}
                    <div class="alert-actions">
                        <button class="btn btn-primary btn-sm" onclick="quickOrder('below_minimum')">
                            <i class='bx bx-cart'></i> Order More
                        </button>
                    </div>
                </div>
            </div>
        `;
    }

    // Low Stock (Info)
    if (data.low_stock.length > 0) {
        html += `
            <div class="inventory-alert info">
                <div class="alert-header" onclick="toggleAlert(this)">
                    <h4>
                        <i class='bx bx-info-circle' style="color: var(--blue);"></i>
                        Low Stock (${data.low_stock.length})
                    </h4>
                    <i class='bx bx-chevron-down'></i>
                </div>
                <div class="alert-content" style="display: block;">
                    ${data.low_stock.map(item => `
                        <div class="alert-item">
                            <span class="item-name">
                                ${escapeHtml(item.item_name)}
                                ${getCategoryBadge(item.item_name)}
                            </span>
                            <span class="item-quantity quantity-warning">${item.quantity}</span>
                        </div>
                    `).join('')}
                </div>
            </div>
        `;
    }

    // Category-based alerts
    if (data.categories) {
        Object.entries(data.categories).forEach(([category, items]) => {
            if (items.length > 0) {
                const categoryLabels = {
                    'buns': '🥖 Buns',
                    'patties': '🍔 Patties',
                    'fries': '🍟 Fries',
                    'drinks': '🥤 Drinks',
                    'toppings': '🧀 Toppings',
                    'other': '📦 Other'
                };

                html += `
                    <div class="inventory-alert info">
                        <div class="alert-header" onclick="toggleAlert(this)">
                            <h4>
                                <i class='bx bx-category'></i>
                                ${categoryLabels[category] || category} (${items.length})
                            </h4>
                            <i class='bx bx-chevron-down'></i>
                        </div>
                        <div class="alert-content" style="display: none;">
                            ${items.map(item => `
                                <div class="alert-item">
                                    <span class="item-name">${escapeHtml(item.item_name)}</span>
                                    <span class="item-quantity ${item.quantity <= 0 ? 'quantity-critical' : 'quantity-warning'}">
                                        ${item.quantity}
                                    </span>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                `;
            }
        });
    }

    if (!html) {
        html = `
            <div class="inventory-alert info">
                <div class="alert-header">
                    <h4>
                        <i class='bx bx-check-circle' style="color: var(--success);"></i>
                        All Inventory Items are Stocked
                    </h4>
                </div>
                <div class="alert-content">
                    <p>No low stock alerts at this time.</p>
                </div>
            </div>
        `;
    }

    panel.innerHTML = html;
}

// Toggle alert expansion
function toggleAlert(header) {
    const content = header.nextElementSibling;
    const icon = header.querySelector('.bx-chevron-down, .bx-chevron-up');

    if (content.style.display === 'none') {
        content.style.display = 'block';
        if (icon) {
            icon.classList.remove('bx-chevron-up');
            icon.classList.add('bx-chevron-down');
        }
    } else {
        content.style.display = 'none';
        if (icon) {
            icon.classList.remove('bx-chevron-down');
            icon.classList.add('bx-chevron-up');
        }
    }
}

// Show inventory notifications
function showInventoryNotifications(data) {
    // Only show notifications for critical items (out of stock)
    if (data.out_of_stock.length > 0) {
        // Group out of stock items by category
        const categories = {};
        data.out_of_stock.forEach(item => {
            const category = getItemCategory(item.item_name);
            if (!categories[category]) categories[category] = [];
            categories[category].push(item);
        });

        // Show notifications for each category
        Object.entries(categories).forEach(([category, items]) => {
            const categoryLabels = {
                'buns': 'Buns',
                'patties': 'Patties',
                'fries': 'Fries',
                'drinks': 'Drinks',
                'toppings': 'Toppings',
                'other': 'Ingredients'
            };

            showToastNotification(
                'critical',
                `${categoryLabels[category] || 'Items'} Out of Stock`,
                `${items.length} item(s) need immediate attention: ${items.map(i => i.item_name).join(', ')}`
            );
        });
    } else if (data.below_minimum.length > 0) {
        // Show single notification for below minimum items
        showToastNotification(
            'warning',
            'Stock Below Minimum Level',
            `${data.below_minimum.length} item(s) are below their minimum stock level.`
        );
    }
}

// Toast notification function
function showToastNotification(type, title, message) {
    // Create toast container if it doesn't exist
    let toastContainer = document.getElementById('toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'toast-container';
        toastContainer.style.cssText = `
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 2001;
        `;
        document.body.appendChild(toastContainer);
    }

    // Create toast
    const toast = document.createElement('div');
    toast.style.cssText = `
        background: white;
        border-left: 5px solid ${type === 'critical' ? 'var(--red)' : type === 'warning' ? 'var(--warning)' : 'var(--blue)'};
        border-radius: 8px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        padding: 15px 20px;
        margin-top: 10px;
        max-width: 350px;
        animation: slideIn 0.3s ease;
        cursor: pointer;
    `;

    toast.innerHTML = `
        <div style="display: flex; align-items: center; gap: 10px;">
            <i class='bx ${type === 'critical' ? 'bx-error-circle' : type === 'warning' ? 'bx-error' : 'bx-info-circle'}' 
               style="color: ${type === 'critical' ? 'var(--red)' : type === 'warning' ? 'var(--warning)' : 'var(--blue)'}; font-size: 1.5rem;"></i>
            <div>
                <strong style="display: block; margin-bottom: 5px;">${title}</strong>
                <span style="font-size: 0.9rem; color: #666;">${message}</span>
            </div>
        </div>
    `;

    toast.onclick = function () {
        toast.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => toast.remove(), 300);
        toggleAlertPanel();
    };

    toastContainer.appendChild(toast);

    // Auto remove after 8 seconds
    setTimeout(() => {
        if (toast.parentNode) {
            toast.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }
    }, 8000);
}

// Helper function to get category badge
function getCategoryBadge(itemName) {
    const name = itemName.toLowerCase();

    if (name.includes('bun') || name.includes('bread') || name.includes('roll')) {
        return '<span class="category-badge buns">Bun</span>';
    }
    if (name.includes('patty') || name.includes('beef') || name.includes('chicken')) {
        return '<span class="category-badge patties">Patty</span>';
    }
    if (name.includes('fries') || name.includes('french') || name.includes('potato')) {
        return '<span class="category-badge fries">Fries</span>';
    }
    if (name.includes('drink') || name.includes('soda') || name.includes('juice') ||
        name.includes('coke') || name.includes('water') || name.includes('beverage')) {
        return '<span class="category-badge drinks">Drink</span>';
    }
    if (name.includes('topping') || name.includes('cheese') || name.includes('lettuce') ||
        name.includes('tomato') || name.includes('onion') || name.includes('sauce') ||
        name.includes('ketchup') || name.includes('mayo') || name.includes('bacon')) {
        return '<span class="category-badge toppings">Topping</span>';
    }

    return '';
}

// Helper function to get item category
function getItemCategory(itemName) {
    const name = itemName.toLowerCase();

    if (name.includes('bun') || name.includes('bread') || name.includes('roll')) return 'buns';
    if (name.includes('patty') || name.includes('beef') || name.includes('chicken')) return 'patties';
    if (name.includes('fries') || name.includes('french') || name.includes('potato')) return 'fries';
    if (name.includes('drink') || name.includes('soda') || name.includes('juice') ||
        name.includes('coke') || name.includes('water')) return 'drinks';
    if (name.includes('topping') || name.includes('cheese') || name.includes('lettuce') ||
        name.includes('tomato') || name.includes('onion') || name.includes('sauce')) return 'toppings';

    return 'other';
}

// Escape HTML to prevent XSS
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Quick order function
function quickOrder(type) {
    if (type === 'out_of_stock') {
        window.location.href = '../inventory/inventory.php?stock=out';
    } else if (type === 'below_minimum') {
        window.location.href = '../inventory/inventory.php?stock=low';
    }
}

// Check inventory alerts every 5 minutes
setInterval(checkInventoryAlerts, 300000);

// Initial check
document.addEventListener('DOMContentLoaded', function () {
    checkInventoryAlerts();

    // Add quick action bar to inventory tab
    addQuickActionBar();
});

// Add quick action bar to inventory tab
function addQuickActionBar() {
    const inventoryTab = document.getElementById('inventory');
    if (inventoryTab) {
        const quickBar = document.createElement('div');
        quickBar.className = 'quick-action-bar';
        quickBar.innerHTML = `
            <div style="font-weight: 600; color: var(--brown);">Quick Actions:</div>
            <div class="quick-action-item" onclick="filterInventory('all')">
                <span class="indicator normal"></span>
                All Items
            </div>
            <div class="quick-action-item critical" onclick="filterInventory('out_of_stock')">
                <span class="indicator critical"></span>
                Out of Stock
            </div>
            <div class="quick-action-item warning" onclick="filterInventory('low_stock')">
                <span class="indicator warning"></span>
                Low Stock
            </div>
            <div class="quick-action-item" onclick="filterInventory('category_buns')">
                <span class="category-badge buns">Buns</span>
            </div>
            <div class="quick-action-item" onclick="filterInventory('category_patties')">
                <span class="category-badge patties">Patties</span>
            </div>
            <div class="quick-action-item" onclick="filterInventory('category_fries')">
                <span class="category-badge fries">Fries</span>
            </div>
            <div class="quick-action-item" onclick="filterInventory('category_drinks')">
                <span class="category-badge drinks">Drinks</span>
            </div>
            <div class="quick-action-item" onclick="filterInventory('category_toppings')">
                <span class="category-badge toppings">Toppings</span>
            </div>
        `;

        inventoryTab.insertBefore(quickBar, inventoryTab.firstChild);
    }
}

// Filter inventory items
function filterInventory(filter) {
    let params = new URLSearchParams();

    switch (filter) {
        case 'out_of_stock':
            params.set('stock', 'out');
            break;
        case 'low_stock':
            params.set('stock', 'low');
            break;
        case 'category_buns':
            params.set('category', 'Buns');
            break;
        case 'category_patties':
            params.set('category', 'Patties');
            break;
        case 'category_fries':
            params.set('category', 'Fries');
            break;
        case 'category_drinks':
            params.set('category', 'Drinks');
            break;
        case 'category_toppings':
            params.set('category', 'Toppings');
            break;
        case 'all':
        default:
            break;
    }

    window.location.href = '../inventory/inventory.php' + (params.toString() ? '?' + params.toString() : '');
}


// Add slideOut animation
const style = document.createElement('style');
style.textContent = `
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);

// For demo purposes, let's add a way to simulate barcode input
document.addEventListener('keydown', function (e) {
    // Simulate barcode scanning with Ctrl+B
    if (e.ctrlKey && e.key === 'b') {
        const productCode = prompt('Enter product ID:');
        if (productCode) {
            simulateBarcodeScan(productCode);
        }
    }
});