<?php
session_start();
require_once "../includes/db.php";

// ---------------------
// CHECK LOGIN & VALIDATE
// ---------------------
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== "customer") {
    header("Location: ../login.php");
    exit;
}

if (!isset($_GET['order_id']) || !is_numeric($_GET['order_id'])) {
    $_SESSION['error'] = "Invalid order ID.";
    header("Location: orders.php");
    exit;
}

$order_id = intval($_GET['order_id']);
$user_id = $_SESSION['user_id'];
$success = "";
$error = "";

// ---------------------
// FETCH ORDER DETAILS
// ---------------------
$order_query = "SELECT o.*, 
                GROUP_CONCAT(CONCAT(oi.quantity, 'x ', oi.item_name) SEPARATOR ', ') as items_list,
                SUM(oi.price * oi.quantity) as calculated_total
                FROM orders o
                LEFT JOIN order_items oi ON o.id = oi.order_id
                WHERE o.id = ? AND o.user_id = ?
                GROUP BY o.id";

$stmt = $conn->prepare($order_query);
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$order_result = $stmt->get_result();

if ($order_result->num_rows === 0) {
    $_SESSION['error'] = "Order not found or you don't have permission.";
    header("Location: orders.php");
    exit;
}

$order = $order_result->fetch_assoc();

// Check if already paid
$check_payment = $conn->prepare("SELECT id FROM payments WHERE order_id = ? AND payment_status = 'paid'");
$check_payment->bind_param("i", $order_id);
$check_payment->execute();
$payment_result = $check_payment->get_result();

if ($payment_result->num_rows > 0) {
    $error = "This order has already been paid.";
}

// ---------------------
// PROCESS PAYMENT
// ---------------------
if (isset($_POST['pay']) && !$error) {
    $method = mysqli_real_escape_string($conn, $_POST['method']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone'] ?? '');
    $transaction_id = isset($_POST['transaction_id']) ? mysqli_real_escape_string($conn, $_POST['transaction_id']) : '';
    
    // Validate based on method
    if ($method == 'M-Pesa' && empty($phone)) {
        $error = "Phone number is required for M-Pesa payment.";
    } elseif ($method == 'Card' && empty($transaction_id)) {
        $error = "Transaction ID is required for card payment.";
    } else {
        // Generate receipt number
        $receipt_no = 'RCT-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        $insert = $conn->prepare("INSERT INTO payments (order_id, method, payment_status, phone, transaction_id, receipt_no, paid_at) 
                                   VALUES (?, ?, 'paid', ?, ?, ?, NOW())");
        $insert->bind_param("issss", $order_id, $method, $phone, $transaction_id, $receipt_no);
        
        if ($insert->execute()) {
            // Update order status
            $update_order = $conn->prepare("UPDATE orders SET status = 'paid' WHERE id = ?");
            $update_order->bind_param("i", $order_id);
            $update_order->execute();
            
            $success = "Payment successful! Your receipt number is: $receipt_no";
        } else {
            $error = "Payment failed. Please try again.";
        }
    }
}

// Format amount
$total_amount = $order['calculated_total'] ?? $order['total_amount'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - Chef D</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #ff4757;
            --secondary: #2c3e50;
            --success: #2ecc71;
            --danger: #e74c3c;
            --warning: #f39c12;
            --dark: #2c3e50;
            --light: #f5f7fa;
            --mpesa: #4CAF50;
            --cash: #f39c12;
            --card: #3498db;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .payment-container {
            max-width: 600px;
            width: 100%;
            margin: 0 auto;
        }

        /* Back Link */
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: white;
            text-decoration: none;
            padding: 0.8rem 1.5rem;
            background: rgba(255,255,255,0.2);
            border-radius: 50px;
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .back-link:hover {
            background: rgba(255,255,255,0.3);
            transform: translateX(-5px);
        }

        /* Main Card */
        .payment-card {
            background: white;
            border-radius: 30px;
            overflow: hidden;
            box-shadow: 0 20px 50px rgba(0,0,0,0.2);
            animation: slideUp 0.5s ease;
        }

        .payment-header {
            background: linear-gradient(135deg, var(--primary), #ff6b4a);
            color: white;
            padding: 2rem;
            text-align: center;
        }

        .payment-header h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .payment-header h1 i {
            animation: spin 10s linear infinite;
        }

        .payment-header p {
            opacity: 0.9;
            font-size: 1rem;
        }

        .payment-body {
            padding: 2rem;
        }

        /* Order Summary */
        .order-summary {
            background: linear-gradient(135deg, #f8f9fa, #ffffff);
            border-radius: 20px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            border: 2px dashed var(--primary);
            position: relative;
            animation: pulse 2s infinite;
        }

        .order-summary::before {
            content: '✨';
            position: absolute;
            top: -10px;
            left: 20px;
            font-size: 1.5rem;
            background: white;
            padding: 0 0.5rem;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 0.8rem 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .summary-row:last-child {
            border-bottom: none;
            font-weight: bold;
            font-size: 1.2rem;
            color: var(--primary);
        }

        .summary-label {
            color: #666;
        }

        .summary-value {
            font-weight: 600;
            color: var(--secondary);
        }

        .order-items {
            margin-top: 1rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 15px;
            font-size: 0.9rem;
            color: #666;
            max-height: 100px;
            overflow-y: auto;
        }

        .order-items i {
            color: var(--primary);
            margin-right: 0.3rem;
        }

        /* Messages */
        .message {
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            animation: slideIn 0.3s ease;
        }

        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .message.warning {
            background: #fff3e0;
            color: #f57c00;
            border: 1px solid #ffe0b2;
        }

        /* Payment Methods */
        .payment-methods {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin: 2rem 0;
        }

        .method-card {
            background: #f8f9fa;
            border: 2px solid #e0e0e0;
            border-radius: 15px;
            padding: 1.5rem 1rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }

        .method-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }

        .method-card.selected {
            border-color: var(--primary);
            background: #fff0f0;
            transform: scale(1.02);
        }

        .method-card input[type="radio"] {
            position: absolute;
            opacity: 0;
        }

        .method-icon {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }

        .method-card.cash .method-icon { color: var(--cash); }
        .method-card.mpesa .method-icon { color: var(--mpesa); }
        .method-card.card .method-icon { color: var(--card); }

        .method-name {
            font-weight: 600;
            color: var(--secondary);
            margin-bottom: 0.3rem;
        }

        .method-desc {
            font-size: 0.75rem;
            color: #666;
        }

        /* Method Details */
        .method-details {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 1.5rem;
            margin: 1.5rem 0;
            display: none;
            animation: fadeIn 0.3s ease;
        }

        .method-details.active {
            display: block;
        }

        .form-group {
            margin-bottom: 1.2rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--secondary);
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 1rem;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            font-family: inherit;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(255,71,87,0.1);
        }

        .form-group input[readonly] {
            background: #f0f0f0;
            cursor: not-allowed;
        }

        .input-hint {
            font-size: 0.8rem;
            color: #666;
            margin-top: 0.3rem;
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }

        .input-hint i {
            color: var(--primary);
        }

        /* Cash Payment Info */
        .cash-info {
            background: #fff3e0;
            border-radius: 12px;
            padding: 1rem;
            margin-top: 1rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .cash-info i {
            font-size: 2rem;
            color: var(--cash);
        }

        .cash-info p {
            color: #666;
            font-size: 0.9rem;
        }

        /* Pay Button */
        .pay-btn {
            width: 100%;
            padding: 1.2rem;
            border: none;
            border-radius: 15px;
            background: linear-gradient(135deg, var(--primary), #ff6b4a);
            color: white;
            font-weight: 700;
            font-size: 1.2rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 2rem;
        }

        .pay-btn:hover:not(:disabled) {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(255,71,87,0.4);
        }

        .pay-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
        }

        .pay-btn i {
            animation: bounce 2s infinite;
        }

        /* Security Badge */
        .security-badge {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
            margin-top: 1.5rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 50px;
        }

        .security-badge i {
            color: var(--success);
            font-size: 1.2rem;
        }

        .security-badge span {
            color: #666;
            font-size: 0.8rem;
        }

        /* Success Actions */
        .success-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 2rem;
        }

        .action-btn {
            padding: 1rem 2rem;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .action-btn-primary {
            background: var(--primary);
            color: white;
        }

        .action-btn-secondary {
            background: var(--secondary);
            color: white;
        }

        .action-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }

        /* Animations */
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.02); }
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-3px); }
        }

        /* Responsive */
        @media (max-width: 768px) {
            body { padding: 1rem; }
            .payment-methods { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="payment-container">
        <!-- Back Link -->
        <a href="orders.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Orders
        </a>

        <!-- Payment Card -->
        <div class="payment-card">
            <div class="payment-header">
                <h1>
                    <i class="fas fa-magic"></i>
                    Secure Payment
                </h1>
                <p>Complete your payment to enjoy delicious food!</p>
            </div>

            <div class="payment-body">
                <?php if ($success): ?>
                    <!-- Success Message -->
                    <div class="message success">
                        <i class="fas fa-check-circle"></i>
                        <?php echo $success; ?>
                    </div>
                    
                    <div class="order-summary" style="border-color: var(--success);">
                        <div class="summary-row">
                            <span class="summary-label">Receipt Number</span>
                            <span class="summary-value" style="color: var(--success);"><?php echo $receipt_no ?? 'N/A'; ?></span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-label">Order ID</span>
                            <span class="summary-value">#<?php echo str_pad($order_id, 5, '0', STR_PAD_LEFT); ?></span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-label">Date</span>
                            <span class="summary-value"><?php echo date('F j, Y, g:i a'); ?></span>
                        </div>
                    </div>

                    <div class="success-actions">
                        <a href="view_receipt.php?order_id=<?php echo $order_id; ?>" class="action-btn action-btn-primary">
                            <i class="fas fa-file-pdf"></i> Download Receipt
                        </a>
                        <a href="orders.php" class="action-btn action-btn-secondary">
                            <i class="fas fa-list"></i> View Orders
                        </a>
                    </div>

                <?php elseif ($error): ?>
                    <!-- Error Message -->
                    <div class="message error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo $error; ?>
                    </div>
                    
                    <a href="orders.php" class="pay-btn" style="background: var(--secondary);">
                        <i class="fas fa-arrow-left"></i> Return to Orders
                    </a>

                <?php else: ?>
                    <!-- Order Summary -->
                    <div class="order-summary">
                        <div class="summary-row">
                            <span class="summary-label">Order #</span>
                            <span class="summary-value"><?php echo str_pad($order_id, 5, '0', STR_PAD_LEFT); ?></span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-label">Items</span>
                            <span class="summary-value"><?php echo substr_count($order['items_list'], ',') + 1; ?> items</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-label">Total Amount</span>
                            <span class="summary-value">TSh <?php echo number_format($total_amount, 0); ?></span>
                        </div>
                        
                        <div class="order-items">
                            <i class="fas fa-utensils"></i>
                            <?php echo $order['items_list']; ?>
                        </div>
                    </div>

                    <!-- Payment Form -->
                    <form method="POST" id="paymentForm">
                        <h3 style="margin-bottom: 1rem; color: var(--secondary);">
                            <i class="fas fa-credit-card"></i>
                            Select Payment Method
                        </h3>

                        <div class="payment-methods">
                            <!-- Cash -->
                            <label class="method-card cash" onclick="selectMethod('cash')">
                                <input type="radio" name="method" value="Cash" required>
                                <div class="method-icon">
                                    <i class="fas fa-money-bill-wave"></i>
                                </div>
                                <div class="method-name">Cash</div>
                                <div class="method-desc">Pay at restaurant</div>
                            </label>

                            <!-- M-Pesa -->
                            <label class="method-card mpesa" onclick="selectMethod('mpesa')">
                                <input type="radio" name="method" value="M-Pesa">
                                <div class="method-icon">
                                    <i class="fas fa-mobile-alt"></i>
                                </div>
                                <div class="method-name">M-Pesa</div>
                                <div class="method-desc">Mobile money</div>
                            </label>

                            <!-- Card -->
                            <label class="method-card card" onclick="selectMethod('card')">
                                <input type="radio" name="method" value="Card">
                                <div class="method-icon">
                                    <i class="fas fa-credit-card"></i>
                                </div>
                                <div class="method-name">Card</div>
                                <div class="method-desc">Visa/Mastercard</div>
                            </label>
                        </div>

                        <!-- Cash Details -->
                        <div id="cash-details" class="method-details">
                            <div class="cash-info">
                                <i class="fas fa-info-circle"></i>
                                <div>
                                    <p><strong>Pay with Cash</strong></p>
                                    <p>Please have exact change ready when picking up your order.</p>
                                </div>
                            </div>
                        </div>

                        <!-- M-Pesa Details -->
                        <div id="mpesa-details" class="method-details">
                            <div class="form-group">
                                <label><i class="fas fa-phone"></i> M-Pesa Phone Number</label>
                                <input type="tel" name="phone" id="mpesa-phone" placeholder="e.g., 0712 345 678">
                                <div class="input-hint">
                                    <i class="fas fa-info-circle"></i>
                                    You'll receive an STK push on your phone
                                </div>
                            </div>
                        </div>

                        <!-- Card Details -->
                        <div id="card-details" class="method-details">
                            <div class="form-group">
                                <label><i class="fas fa-hashtag"></i> Transaction ID</label>
                                <input type="text" name="transaction_id" id="card-txn" placeholder="Enter transaction ID">
                                <div class="input-hint">
                                    <i class="fas fa-lock"></i>
                                    Your payment info is secure
                                </div>
                            </div>
                        </div>

                        <!-- Security Badge -->
                        <div class="security-badge">
                            <i class="fas fa-shield-alt"></i>
                            <span>256-bit SSL Encrypted Payment</span>
                            <i class="fas fa-lock"></i>
                        </div>

                        <button type="submit" name="pay" class="pay-btn" id="payBtn">
                            <i class="fas fa-bolt"></i>
                            Pay TSh <?php echo number_format($total_amount, 0); ?>
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Method selection
        function selectMethod(method) {
            // Update radio button
            const radio = document.querySelector(`input[value="${method === 'cash' ? 'Cash' : method === 'mpesa' ? 'M-Pesa' : 'Card'}"]`);
            if (radio) radio.checked = true;

            // Update card selection
            document.querySelectorAll('.method-card').forEach(card => {
                card.classList.remove('selected');
            });
            event.currentTarget.classList.add('selected');

            // Show/hide details
            document.querySelectorAll('.method-details').forEach(detail => {
                detail.classList.remove('active');
            });

            if (method === 'mpesa') {
                document.getElementById('mpesa-details').classList.add('active');
                document.getElementById('mpesa-phone').setAttribute('required', 'required');
                document.getElementById('card-txn')?.removeAttribute('required');
            } else if (method === 'card') {
                document.getElementById('card-details').classList.add('active');
                document.getElementById('card-txn').setAttribute('required', 'required');
                document.getElementById('mpesa-phone')?.removeAttribute('required');
            } else {
                document.getElementById('cash-details').classList.add('active');
                document.getElementById('mpesa-phone')?.removeAttribute('required');
                document.getElementById('card-txn')?.removeAttribute('required');
            }
        }

        // Auto-select first method
        document.addEventListener('DOMContentLoaded', function() {
            const firstMethod = document.querySelector('.method-card');
            if (firstMethod) {
                firstMethod.classList.add('selected');
                firstMethod.querySelector('input[type="radio"]').checked = true;
                document.getElementById('cash-details').classList.add('active');
            }
        });

        // Form validation
        document.getElementById('paymentForm')?.addEventListener('submit', function(e) {
            const selectedMethod = document.querySelector('input[name="method"]:checked')?.value;
            
            if (selectedMethod === 'M-Pesa') {
                const phone = document.getElementById('mpesa-phone').value;
                if (!phone || phone.length < 10) {
                    e.preventDefault();
                    alert('Please enter a valid M-Pesa phone number');
                }
            } else if (selectedMethod === 'Card') {
                const txn = document.getElementById('card-txn').value;
                if (!txn) {
                    e.preventDefault();
                    alert('Please enter the transaction ID');
                }
            }
        });

        // Phone number formatting
        document.getElementById('mpesa-phone')?.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 0) {
                if (value.length <= 3) {
                    value = value;
                } else if (value.length <= 6) {
                    value = value.slice(0, 3) + ' ' + value.slice(3);
                } else {
                    value = value.slice(0, 3) + ' ' + value.slice(3, 6) + ' ' + value.slice(6, 9);
                }
                e.target.value = value;
            }
        });

        // Prevent double submission
        document.querySelector('.pay-btn')?.addEventListener('click', function() {
            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
        });
    </script>
</body>
</html>