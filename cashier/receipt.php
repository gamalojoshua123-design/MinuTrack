<?php
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';
requirePermission('transactions_view');

// Get transaction ID from URL
$transaction_id = $_GET['id'] ?? null;

if (!$transaction_id) {
    die('Transaction ID not provided');
}

// Check if this was opened from POS (has referrer from pos.php)
$from_pos = isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'pos.php') !== false;

// Fetch transaction details
try {
    $branch_id = getCurrentBranchId() ?? $_SESSION['branch_id'] ?? null;
    $sql = "
        SELECT 
            o.*, 
            COALESCE(NULLIF(u.full_name, ''), u.user_id, 'Unknown User') AS cashier_name
        FROM orders o 
        LEFT JOIN users u ON o.cashier_id = u.id 
        WHERE o.id = ?
    ";
    if (!isOwner() && $branch_id !== null) {
        $sql .= " AND (o.branch_id = ? OR o.branch_id IS NULL)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$transaction_id, $branch_id]);
    } else {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$transaction_id]);
    }
    $transaction = $stmt->fetch();

    if (!$transaction) {
        die('Transaction not found');
    }

    // Fetch order items
    $stmt = $pdo->prepare("
        SELECT oi.*, p.name, p.price
        FROM order_items oi 
        LEFT JOIN products p ON oi.product_id = p.id 
        WHERE oi.order_id = ?
    ");
    $stmt->execute([$transaction_id]);
    $order_items = $stmt->fetchAll();

    // Calculate VAT
    $vat_rate = 0.12; // 12% VAT
    $total_amount = $transaction['total_amount'];

    // VAT exclusive amount (total / 1.12)
    $vat_exclusive = $total_amount / (1 + $vat_rate);

    // VAT amount (total - vat_exclusive)
    $vat_amount = $total_amount - $vat_exclusive;

    // VAT inclusive amount is the total
    $vat_inclusive = $total_amount;

} catch (PDOException $e) {
    error_log('Receipt fetch error: ' . $e->getMessage());
    die('An error occurred. Please try again later.');
}

// Use the stored order number from the database
$order_number = $transaction['order_number'] ?? ('ORD-' . date('Ymd', strtotime($transaction['date_time'])) . '-' . date('Hi', strtotime($transaction['date_time'])));

// Generate receipt number from stored order number
$receipt_number = str_replace('ORD-', 'RCPT-', $order_number);

// Format time for display (12-hour format with AM/PM)
$display_time = date('h:i A', strtotime($transaction['date_time']));
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt - Minute Burger</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Courier New', monospace;
            margin: 0;
            padding: 10px;
            background-color: #f5f5f5;
            font-size: 12px;
            line-height: 1.2;
        }

        .receipt-container {
            max-width: 280px;
            margin: 0 auto;
            background-color: white;
            padding: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            border: 1px solid #ddd;
        }

        /* Receipt Header */
        .receipt-header {
            text-align: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px dashed #333;
        }

        .company-name {
            font-size: 18px;
            font-weight: bold;
            color: #F37902;
            margin-bottom: 3px;
            text-transform: uppercase;
        }

        .company-tagline {
            font-size: 10px;
            color: #666;
            margin-bottom: 5px;
        }

        .branch-info {
            font-size: 10px;
            color: #333;
            margin-bottom: 3px;
        }

        .receipt-title {
            font-size: 12px;
            font-weight: bold;
            margin: 8px 0;
            text-transform: uppercase;
        }

        /* Transaction Info */
        .transaction-info {
            font-size: 10px;
            margin-bottom: 12px;
            text-align: center;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2px;
        }

        /* Items Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
            font-size: 11px;
        }

        .items-table th {
            text-align: left;
            padding: 3px 0;
            border-bottom: 1px dashed #ccc;
            font-weight: bold;
        }

        .items-table td {
            padding: 2px 0;
            vertical-align: top;
        }

        .item-name {
            width: 50%;
        }

        .item-qty {
            width: 15%;
            text-align: center;
        }

        .item-price {
            width: 35%;
            text-align: right;
        }

        .item-subtotal {
            text-align: right;
            font-weight: bold;
        }

        /* Totals Section */
        .totals-section {
            border-top: 1px dashed #333;
            padding-top: 8px;
            margin-bottom: 12px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 3px;
        }

        .total-label {
            font-weight: bold;
        }

        .total-amount {
            font-weight: bold;
        }

        .grand-total {
            border-top: 2px solid #333;
            padding-top: 5px;
            margin-top: 5px;
            font-size: 13px;
            font-weight: bold;
        }

        /* VAT Section */
        .vat-section {
            margin-bottom: 8px;
            padding-bottom: 8px;
            border-bottom: 1px dashed #ccc;
        }

        .vat-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2px;
            font-size: 10px;
        }

        .vat-note {
            font-size: 9px;
            color: #666;
            text-align: center;
            margin-top: 3px;
        }

        /* Payment Section */
        .payment-section {
            margin-bottom: 12px;
        }

        .payment-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2px;
        }

        /* Footer */
        .receipt-footer {
            text-align: center;
            margin-top: 15px;
            padding-top: 10px;
            border-top: 1px dashed #333;
            font-size: 10px;
        }

        .thank-you {
            font-weight: bold;
            margin-bottom: 5px;
            color: #F37902;
        }

        .return-policy {
            font-size: 9px;
            color: #666;
            margin-bottom: 5px;
            font-style: italic;
        }

        .contact-info {
            font-size: 9px;
            color: #666;
            margin-bottom: 3px;
        }

        .barcode {
            margin: 8px 0;
            text-align: center;
            font-family: 'Libre Barcode 128', cursive;
            font-size: 24px;
        }

        .vat-reg-tin {
            font-size: 9px;
            text-align: center;
            margin: 5px 0;
            font-weight: bold;
        }

        /* Button Container — pinned to the bottom of the screen so the
           cashier can print or get back to the POS without scrolling past
           a long receipt. Hidden entirely when printing (.no-print). */
        .button-container {
            position: sticky;
            bottom: 0;
            z-index: 10;
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 0.6rem;
            margin-top: 20px;
            padding: 0.75rem 1rem calc(0.75rem + env(safe-area-inset-bottom, 0px));
            background: rgba(245, 245, 245, 0.96);
            backdrop-filter: blur(6px);
            -webkit-backdrop-filter: blur(6px);
            border-top: 1px solid #ddd;
        }

        .btn {
            background: #F37902;
            color: white;
            border: none;
            padding: 0.85rem 1.5rem;
            min-height: 48px;
            min-width: 150px;
            border-radius: 10px;
            cursor: pointer;
            font-size: 15px;
            font-weight: 700;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            transition: background 0.2s ease, box-shadow 0.2s ease, transform 0.1s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.45rem;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.12);
            /* removes the 300ms double-tap delay on touch devices */
            touch-action: manipulation;
            -webkit-tap-highlight-color: transparent;
        }

        .btn i {
            font-size: 1.2rem;
        }

        .btn:hover {
            background: #DC6902;
            box-shadow: 0 4px 12px rgba(243, 121, 2, 0.3);
        }

        /* Touchscreens have no hover — a pressed state is the only feedback
           that the tap actually registered. */
        .btn:active {
            transform: scale(0.97);
        }

        .btn-secondary {
            background: #666;
        }

        .btn-secondary:hover {
            background: #555;
        }

        .btn-success {
            background: #27ae60;
        }

        .btn-success:hover {
            background: #229954;
        }

        @media (max-width: 480px) {
            .btn {
                flex: 1 1 100%;
                min-width: 0;
            }
        }

        /* Print Styles */
        @media print {
            body {
                background-color: white;
                padding: 0;
                margin: 0;
            }

            .receipt-container {
                box-shadow: none;
                border: none;
                max-width: 100%;
                padding: 10px;
                margin: 0;
            }

            .no-print {
                display: none;
            }
        }

        /* Utility Classes */
        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .bold {
            font-weight: bold;
        }

        .dashed-border {
            border-bottom: 1px dashed #333;
            margin: 8px 0;
        }
        
        .time-note {
            font-size: 8px;
            color: #666;
            text-align: center;
            margin-top: 3px;
        }
    </style>

    <link href="https://fonts.googleapis.com/css2?family=Libre+Barcode+128&display=swap" rel="stylesheet">
</head>

<body>
    <div class="receipt-container">

        <!-- VAT Registered Info -->
        <div class="vat-reg-tin">
            VAT REG TIN: 123-456-789-000
        </div>

        <div class="receipt-header">
            <div class="company-name">MINUTE BURGER</div>
            <div class="company-tagline">"We Create Everyday Happiness by Serving More Delicious, More Affordable and
                More Nutritious Food."</div>
            <?php
            $branch_name = 'Jasaan Branch';
            $branch_address = 'Jasaan, Misamis Oriental';
            if (isset($transaction['branch_id'])) {
                $branchStmt = $pdo->prepare("SELECT branch_name, location FROM branches WHERE id = ?");
                $branchStmt->execute([$transaction['branch_id']]);
                $branch = $branchStmt->fetch(PDO::FETCH_ASSOC);
                if ($branch) {
                    $branch_name = $branch['branch_name'];
                    $branch_address = $branch['location'];
                }
            }
            ?>
            <div class="branch-info"><?= htmlspecialchars($branch_name) ?></div>
            <div class="branch-info"><?= htmlspecialchars($branch_address) ?></div>
            <div class="branch-info">Tel: 09********</div>

            <div class="dashed-border"></div>

            <div class="receipt-title">OFFICIAL RECEIPT</div>

            <div class="transaction-info">
                <div class="info-row">
                    <span class="bold">Order No:</span>
                    <span class="bold"><?php echo htmlspecialchars($order_number); ?></span>
                </div>
                <div class="info-row">
                    <span>Date:</span>
                    <span><?php echo date('m/d/Y', strtotime($transaction['date_time'])); ?></span>
                </div>
                <div class="info-row">
                    <span>Time:</span>
                    <span><?php echo $display_time; ?></span>
                </div>
                <div class="info-row">
                    <span>Cashier:</span>
                    <span><?php echo htmlspecialchars($transaction['cashier_name']); ?></span>
                </div>
            </div>
        </div>

        <!-- Items Section -->
        <table class="items-table">
            <thead>
                    <tr>
                        <th class="item-name">ITEM</th>
                        <th class="item-qty">QTY</th>
                        <th class="item-price">AMOUNT</th>
                    </tr>
                </thead>
            <tbody>
                <?php if (empty($order_items)): ?>
                    <tr><td colspan="3" class="text-center">No items in this order.</td></tr>
                <?php else: ?>
                    <?php foreach ($order_items as $item): ?>
                        <tr>
                            <td class="item-name"><?php echo htmlspecialchars($item['name']); ?></td>
                            <td class="item-qty"><?php echo htmlspecialchars($item['quantity']); ?></td>
                            <td class="item-price">₱<?php echo number_format($item['subtotal'], 2); ?></td>
                        </tr>
                        <?php if ($item['quantity'] > 1): ?>
                            <tr>
                                <td class="item-name" style="padding-left: 10px; font-size: 9px;">
                                    @ ₱<?php echo number_format($item['price'], 2); ?> each
                                </td>
                                <td class="item-qty"></td>
                                <td class="item-price"></td>
                            </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="dashed-border"></div>

        <!-- Totals Section -->
        <div class="totals-section">
            <div class="total-row">
                <span>Subtotal:</span>
                <span class="total-amount">₱<?php echo number_format($total_amount, 2); ?></span>
            </div>

            <!-- VAT Breakdown -->
            <div class="vat-section">
                <div class="vat-row">
                    <span>VAT Exclusive:</span>
                    <span>₱<?php echo number_format($vat_exclusive, 2); ?></span>
                </div>
                <div class="vat-row">
                    <span>VAT (12%):</span>
                    <span>₱<?php echo number_format($vat_amount, 2); ?></span>
                </div>
                <div class="vat-note">
                    * Inclusive of 12% VAT (VAT Reg)
                </div>
            </div>

            <div class="total-row grand-total">
                <span>TOTAL AMOUNT:</span>
                <span class="total-amount">₱<?php echo number_format($vat_inclusive, 2); ?></span>
            </div>
        </div>

        <!-- Payment Section -->
        <div class="payment-section">
            <div class="payment-row">
                <span>Cash:</span>
                <span class="bold">₱<?php echo number_format($transaction['payment'], 2); ?></span>
            </div>
            <div class="payment-row">
                <span>Change:</span>
                <span class="bold">₱<?php echo number_format($transaction['change'], 2); ?></span>
            </div>
        </div>

        <div class="dashed-border"></div>

        <!-- Barcode -->
        <div class="barcode">
            *<?php echo $receipt_number; ?>*
        </div>

        <!-- Receipt Footer -->
        <div class="receipt-footer">
            <div class="thank-you">THANK YOU FOR CHOOSING MINUTE BURGER!</div>
            <div class="return-policy">Goods sold in good condition are not returnable</div>
            <div class="contact-info">For feedback: feedback@minuteburger.com</div>
            <div class="contact-info">MINUTE BURGER CORP.</div>
            <div class="contact-info">VAT REG TIN: 123-456-789-000</div>
            <div class="contact-info">THIS SERVES AS YOUR OFFICIAL RECEIPT</div>
        </div>
    </div>

    <!-- Button Container (hidden when printing) -->
    <div class="button-container no-print">
        <button onclick="window.print()" class="btn">
            <i class='bx bx-printer'></i> Print Receipt
        </button>

        <?php if ($from_pos): ?>
            <!-- If opened from POS, show button to go back to POS -->
            <button onclick="goBackToPOS()" class="btn btn-success">
                <i class='bx bx-cart'></i> Back to POS
            </button>
        <?php else: ?>
            <!-- If opened from transactions page, show close button -->
            <button onclick="closeWindow()" class="btn btn-secondary">
                <i class='bx bx-x'></i> Close Window
            </button>
        <?php endif; ?>
    </div>

    <!-- Add Boxicons for icons -->
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">

    <script>
        // Function to close window or go back
        function closeWindow() {
            // Try to close the window
            window.close();

            // If window.close() doesn't work (due to browser restrictions),
            // show a message and provide alternative
            setTimeout(function () {
                if (!window.closed) {
                    showBackConfirm('Unable to close this window automatically. Would you like to go back to the transactions page instead?', function () {
                        window.location.href = 'transactions.php';
                    });
                }
            }, 500);
        }

        // Function to go back to POS
        function goBackToPOS() {
            window.location.href = 'pos.php';
        }

        // Auto-print receipt only when accessed from POS (not from transactions)
        window.onload = function () {
            var fromPos = document.referrer.indexOf('pos.php') !== -1 || window.location.search.indexOf('from_pos=1') !== -1;
            if (fromPos) {
                setTimeout(function () {
                    window.print();
                }, 500);
            }
        };

        // Optional: Show message after print
        window.onafterprint = function () {
            // You can add any after-print logic here
            console.log('Print completed');
        };
    </script>

    <!-- Self-contained confirmation modal (receipt.php does not load admin.css) -->
    <div id="confirmBackOverlay" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(15,23,42,0.5);z-index:9999;align-items:center;justify-content:center;padding:1rem;">
        <div style="background:#ffffff;border-radius:12px;max-width:380px;width:90%;padding:1.5rem;text-align:center;box-shadow:0 20px 50px rgba(0,0,0,0.25);">
            <div style="font-size:2rem;margin-bottom:0.5rem;">&#9888;&#65039;</div>
            <p style="margin:0 0 1.25rem;font-size:0.95rem;color:#334155;line-height:1.5;" id="confirmBackMsg"></p>
            <div style="display:flex;gap:8px;justify-content:flex-end;">
                <button type="button" onclick="closeBackConfirm()" style="padding:0.6rem 1rem;border:1px solid #94a3b8;background:#ffffff;color:#334155;border-radius:8px;cursor:pointer;font-weight:600;">Cancel</button>
                <button type="button" onclick="proceedBackConfirm()" style="padding:0.6rem 1rem;border:none;background:#dc2626;color:#ffffff;border-radius:8px;cursor:pointer;font-weight:600;">Go to Transactions</button>
            </div>
        </div>
    </div>
    <script>
        var __backConfirmCb = null;
        function showBackConfirm(msg, cb) {
            __backConfirmCb = cb;
            var el = document.getElementById('confirmBackOverlay');
            var msgEl = document.getElementById('confirmBackMsg');
            if (msgEl) msgEl.textContent = msg;
            if (el) el.style.display = 'flex';
        }
        function closeBackConfirm() {
            var el = document.getElementById('confirmBackOverlay');
            if (el) el.style.display = 'none';
        }
        function proceedBackConfirm() {
            closeBackConfirm();
            if (__backConfirmCb) {
                var cb = __backConfirmCb;
                __backConfirmCb = null;
                cb();
            }
        }
    </script>
</body>

</html>