<?php
session_start();
include "../includes/db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != "customer") {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';
$message_type = '';

/* REMOVE ITEM */
if (isset($_GET['remove'])) {
    $id = $_GET['remove'];
    if (isset($_SESSION['cart'][$id])) {
        unset($_SESSION['cart'][$id]);
        $message = "Item removed from cart!";
        $message_type = "success";
    }
}

/* UPDATE QUANTITY */
if (isset($_POST['update_cart'])) {
    foreach ($_POST['quantity'] as $id => $quantity) {
        if ($quantity > 0) {
            $_SESSION['cart'][$id]['quantity'] = $quantity;
        } else {
            unset($_SESSION['cart'][$id]);
        }
    }
    $message = "Cart updated successfully!";
    $message_type = "success";
}

/* CLEAR CART */
if (isset($_GET['clear'])) {
    unset($_SESSION['cart']);
    $message = "Cart cleared successfully!";
    $message_type = "success";
}

$total = 0;
$item_count = 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - Restaurant Management System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }

        /* Navigation Bar */
        .navbar {
            background: white;
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 24px;
            font-weight: 700;
            color: #667eea;
        }

        .logo span {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 5px 10px;
            border-radius: 10px;
            font-size: 14px;
        }

        .nav-links {
            display: flex;
            gap: 20px;
            align-items: center;
        }

        .nav-link {
            text-decoration: none;
            color: #555;
            font-weight: 500;
            padding: 8px 16px;
            border-radius: 50px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .nav-link:hover {
            background: #f0f0f0;
            color: #667eea;
        }

        .nav-link.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .cart-badge {
            background: #ff4444;
            color: white;
            padding: 2px 8px;
            border-radius: 50px;
            font-size: 12px;
            margin-left: 5px;
        }

        .user-menu {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 5px 15px;
            background: #f8f9fa;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .user-menu:hover {
            background: #e9ecef;
        }

        .user-avatar {
            width: 35px;
            height: 35px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }

        .user-info {
            display: flex;
            flex-direction: column;
        }

        .user-name {
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }

        .user-role {
            font-size: 12px;
            color: #666;
        }

        /* Main Content */
        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 30px;
        }

        /* Page Header */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            color: white;
        }

        .page-title {
            font-size: 32px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .page-title span {
            background: rgba(255,255,255,0.2);
            padding: 10px 20px;
            border-radius: 50px;
            font-size: 16px;
        }

        .clear-cart-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            text-decoration: none;
            padding: 12px 25px;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            border: 2px solid transparent;
        }

        .clear-cart-btn:hover {
            background: rgba(255,255,255,0.3);
            transform: translateY(-2px);
            border-color: white;
        }

        /* Message Alert */
        .message-alert {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            animation: slideDown 0.5s ease-out;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .message-alert.success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .message-alert.error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        /* Cart Container */
        .cart-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
            overflow: hidden;
            margin-bottom: 30px;
        }

        /* Cart Header */
        .cart-header {
            display: grid;
            grid-template-columns: 3fr 1fr 1fr 1fr 0.5fr;
            padding: 20px 25px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Cart Items */
        .cart-items {
            padding: 20px 25px;
        }

        .cart-item {
            display: grid;
            grid-template-columns: 3fr 1fr 1fr 1fr 0.5fr;
            align-items: center;
            padding: 20px 0;
            border-bottom: 1px solid #eee;
            animation: fadeIn 0.5s ease-out;
        }

        .cart-item:last-child {
            border-bottom: none;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        /* Product Info */
        .product-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .product-image {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 32px;
        }

        .product-details h3 {
            font-size: 16px;
            color: #333;
            margin-bottom: 5px;
        }

        .product-details p {
            font-size: 13px;
            color: #666;
        }

        /* Price */
        .item-price {
            font-weight: 600;
            color: #333;
            font-size: 16px;
        }

        /* Quantity Controls */
        .quantity-control {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .quantity-btn {
            width: 35px;
            height: 35px;
            background: #f0f0f0;
            border: none;
            border-radius: 10px;
            font-size: 18px;
            font-weight: 600;
            color: #333;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .quantity-btn:hover {
            background: #667eea;
            color: white;
        }

        .quantity-input {
            width: 60px;
            height: 35px;
            text-align: center;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
        }

        .quantity-input:focus {
            outline: none;
            border-color: #667eea;
        }

        /* Subtotal */
        .item-subtotal {
            font-weight: 700;
            color: #28a745;
            font-size: 18px;
        }

        /* Remove Button */
        .remove-btn {
            background: none;
            border: none;
            font-size: 20px;
            color: #ff4444;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
        }

        .remove-btn:hover {
            background: #ff4444;
            color: white;
            transform: scale(1.1);
        }

        /* Cart Footer */
        .cart-footer {
            background: #f8f9fa;
            padding: 25px;
            border-top: 2px solid #eee;
        }

        .cart-summary {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 20px;
        }

        .summary-details {
            width: 300px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            color: #666;
        }

        .summary-row.total {
            border-top: 2px solid #ddd;
            margin-top: 10px;
            padding-top: 15px;
            font-size: 20px;
            font-weight: 700;
            color: #333;
        }

        .summary-row.total .summary-value {
            color: #28a745;
        }

        /* Cart Actions */
        .cart-actions {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
        }

        .action-btn {
            padding: 15px 30px;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
            border: none;
            cursor: pointer;
        }

        .continue-btn {
            background: #f0f0f0;
            color: #333;
        }

        .continue-btn:hover {
            background: #e0e0e0;
            transform: translateY(-2px);
        }

        .update-btn {
            background: #667eea;
            color: white;
        }

        .update-btn:hover {
            background: #5a67d8;
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }

        .checkout-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .checkout-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }

        /* Empty Cart */
        .empty-cart {
            text-align: center;
            padding: 60px 40px;
        }

        .empty-icon {
            font-size: 100px;
            color: #ddd;
            margin-bottom: 25px;
            animation: bounce 2s infinite;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        .empty-title {
            font-size: 28px;
            color: #333;
            margin-bottom: 15px;
        }

        .empty-message {
            color: #666;
            margin-bottom: 30px;
            font-size: 16px;
        }

        .browse-menu-btn {
            display: inline-block;
            padding: 15px 40px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .browse-menu-btn:hover {
            transform: scale(1.05);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }

        /* Related Items */
        .related-items {
            margin-top: 40px;
        }

        .related-title {
            color: white;
            font-size: 24px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .related-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
        }

        .related-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .related-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }

        .related-icon {
            font-size: 40px;
            margin-bottom: 15px;
        }

        .related-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
        }

        .related-price {
            color: #28a745;
            font-weight: 700;
            margin-bottom: 15px;
        }

        .add-related-btn {
            background: #f0f0f0;
            border: none;
            padding: 8px 20px;
            border-radius: 50px;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .add-related-btn:hover {
            background: #667eea;
            color: white;
        }

        @media (max-width: 768px) {
            .cart-header {
                display: none;
            }
            
            .cart-item {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .cart-actions {
                flex-direction: column;
            }
            
            .related-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .nav-container {
                flex-direction: column;
                gap: 15px;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="logo">
                🍽️ FoodieHub
                <span>Cart</span>
            </div>
            
            <div class="nav-links">
                <a href="dashboard.php" class="nav-link">
                    <span>🏠</span> Dashboard
                </a>
                <a href="menu.php" class="nav-link">
                    <span>📋</span> Menu
                </a>
                <a href="cart.php" class="nav-link active">
                    <span>🛒</span> Cart
                    <?php
                    $cart_count = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
                    if ($cart_count > 0) {
                        echo "<span class='cart-badge'>$cart_count</span>";
                    }
                    ?>
                </a>
                <a href="orders.php" class="nav-link">
                    <span>📦</span> Orders
                </a>
            </div>
            
            <div class="user-menu" onclick="toggleUserMenu()">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($_SESSION['user_name'], 0, 1)); ?>
                </div>
                <div class="user-info">
                    <span class="user-name"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                    <span class="user-role">Customer</span>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">
                🛒 Your Shopping Cart
                <span><?php echo $cart_count; ?> <?php echo $cart_count === 1 ? 'item' : 'items'; ?></span>
            </h1>
            
            <?php if (!empty($_SESSION['cart'])): ?>
            <a href="?clear=1" class="clear-cart-btn" onclick="return confirm('Are you sure you want to clear your cart?')">
                <span>🗑️</span> Clear Cart
            </a>
            <?php endif; ?>
        </div>

        <!-- Message Alert -->
        <?php if ($message): ?>
            <div class="message-alert <?php echo $message_type; ?>">
                <span><?php echo $message_type === 'success' ? '✅' : '⚠️'; ?></span>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- Cart Container -->
        <div class="cart-container">
            <?php if (!empty($_SESSION['cart'])): ?>
                <form method="POST" action="" id="cartForm">
                    <!-- Cart Header -->
                    <div class="cart-header">
                        <div>Product</div>
                        <div>Price</div>
                        <div>Quantity</div>
                        <div>Subtotal</div>
                        <div></div>
                    </div>

                    <!-- Cart Items -->
                    <div class="cart-items">
                        <?php foreach ($_SESSION['cart'] as $id => $item): 
                            $subtotal = $item['price'] * $item['quantity'];
                            $total += $subtotal;
                            $item_count += $item['quantity'];
                        ?>
                        <div class="cart-item">
                            <div class="product-info">
                                <div class="product-image">
                                    <?php 
                                    $icons = ['🍕', '🍔', '🥗', '🍝', '🍣', '🥘', '🍛', '🍜', '🍲', '🥪'];
                                    echo $icons[$id % count($icons)];
                                    ?>
                                </div>
                                <div class="product-details">
                                    <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                                    <p>Item #<?php echo $id; ?></p>
                                </div>
                            </div>
                            
                            <div class="item-price">$<?php echo number_format($item['price'], 2); ?></div>
                            
                            <div class="quantity-control">
                                <button type="button" class="quantity-btn" onclick="updateQuantity(<?php echo $id; ?>, -1)">−</button>
                                <input type="number" 
                                       name="quantity[<?php echo $id; ?>]" 
                                       value="<?php echo $item['quantity']; ?>" 
                                       min="1" 
                                       max="99"
                                       class="quantity-input"
                                       id="qty_<?php echo $id; ?>"
                                       onchange="updateTotal()">
                                <button type="button" class="quantity-btn" onclick="updateQuantity(<?php echo $id; ?>, 1)">+</button>
                            </div>
                            
                            <div class="item-subtotal">$<?php echo number_format($subtotal, 2); ?></div>
                            
                            <a href="?remove=<?php echo $id; ?>" class="remove-btn" onclick="return confirm('Remove this item from cart?')">✕</a>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Cart Footer -->
                    <div class="cart-footer">
                        <div class="cart-summary">
                            <div class="summary-details">
                                <div class="summary-row">
                                    <span class="summary-label">Subtotal (<?php echo $item_count; ?> items)</span>
                                    <span class="summary-value">$<?php echo number_format($total, 2); ?></span>
                                </div>
                                <div class="summary-row">
                                    <span class="summary-label">Tax (10%)</span>
                                    <span class="summary-value">$<?php echo number_format($total * 0.1, 2); ?></span>
                                </div>
                                <div class="summary-row total">
                                    <span class="summary-label">Total</span>
                                    <span class="summary-value">$<?php echo number_format($total * 1.1, 2); ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="cart-actions">
                            <a href="menu.php" class="action-btn continue-btn">
                                <span>←</span> Continue Shopping
                            </a>
                            <button type="submit" name="update_cart" class="action-btn update-btn">
                                <span>↻</span> Update Cart
                            </button>
                            <a href="checkout.php" class="action-btn checkout-btn">
                                Proceed to Checkout <span>→</span>
                            </a>
                        </div>
                    </div>
                </form>
            <?php else: ?>
                <!-- Empty Cart -->
                <div class="empty-cart">
                    <div class="empty-icon">🛒</div>
                    <h2 class="empty-title">Your cart is empty</h2>
                    <p class="empty-message">Looks like you haven't added any items to your cart yet.</p>
                    <a href="menu.php" class="browse-menu-btn">Browse Menu</a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Related Items (Popular Items) -->
        <?php if (empty($_SESSION['cart'])): ?>
        <div class="related-items">
            <h2 class="related-title">
                <span>🔥</span> Popular Items You Might Like
            </h2>
            <div class="related-grid">
                <div class="related-card" onclick="addToCart(1)">
                    <div class="related-icon">🍕</div>
                    <div class="related-name">Margherita Pizza</div>
                    <div class="related-price">$12.99</div>
                    <button class="add-related-btn">Add to Cart</button>
                </div>
                
                <div class="related-card" onclick="addToCart(2)">
                    <div class="related-icon">🍔</div>
                    <div class="related-name">Classic Burger</div>
                    <div class="related-price">$8.99</div>
                    <button class="add-related-btn">Add to Cart</button>
                </div>
                
                <div class="related-card" onclick="addToCart(3)">
                    <div class="related-icon">🥗</div>
                    <div class="related-name">Caesar Salad</div>
                    <div class="related-price">$7.99</div>
                    <button class="add-related-btn">Add to Cart</button>
                </div>
                
                <div class="related-card" onclick="addToCart(4)">
                    <div class="related-icon">🍝</div>
                    <div class="related-name">Pasta Alfredo</div>
                    <div class="related-price">$10.99</div>
                    <button class="add-related-btn">Add to Cart</button>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- User Menu Dropdown -->
    <div id="userDropdown" style="display: none; position: fixed; top: 80px; right: 30px; background: white; border-radius: 10px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); padding: 15px; min-width: 200px; z-index: 1000;">
        <a href="profile.php" style="display: block; padding: 10px; text-decoration: none; color: #333; border-radius: 5px; transition: background 0.3s;">
            👤 My Profile
        </a>
        <a href="orders.php" style="display: block; padding: 10px; text-decoration: none; color: #333; border-radius: 5px; transition: background 0.3s;">
            📦 My Orders
        </a>
        <a href="../logout.php" style="display: block; padding: 10px; text-decoration: none; color: #ff4444; border-radius: 5px; transition: background 0.3s;">
            🚪 Logout
        </a>
    </div>

    <script>
        // Toggle user menu
        function toggleUserMenu() {
            const dropdown = document.getElementById('userDropdown');
            if (dropdown.style.display === 'none') {
                dropdown.style.display = 'block';
                setTimeout(() => {
                    document.addEventListener('click', function closeMenu(e) {
                        if (!e.target.closest('.user-menu') && !e.target.closest('#userDropdown')) {
                            dropdown.style.display = 'none';
                            document.removeEventListener('click', closeMenu);
                        }
                    });
                }, 100);
            } else {
                dropdown.style.display = 'none';
            }
        }

        // Update quantity
        function updateQuantity(id, change) {
            const input = document.getElementById('qty_' + id);
            let newValue = parseInt(input.value) + change;
            if (newValue >= 1 && newValue <= 99) {
                input.value = newValue;
                updateTotal();
            }
        }

        // Update total (you can implement AJAX here for real-time updates)
        function updateTotal() {
            // This would typically make an AJAX call to update the cart
            // For now, we'll just submit the form to update
            document.getElementById('cartForm').submit();
        }

        // Add to cart function for related items
        function addToCart(itemId) {
            // Show notification
            showNotification('Item added to cart!', 'success');
            
            // In a real implementation, you would make an AJAX call here
            setTimeout(() => {
                window.location.href = 'cart.php?add=' + itemId;
            }, 1000);
        }

        // Show notification
        function showNotification(message, type) {
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 15px 25px;
                background: ${type === 'success' ? '#4caf50' : '#ff4444'};
                color: white;
                border-radius: 10px;
                box-shadow: 0 5px 15px rgba(0,0,0,0.2);
                z-index: 9999;
                animation: slideIn 0.3s ease-out;
            `;
            notification.textContent = message;
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.animation = 'slideOut 0.3s ease-out';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }

        // Auto-hide message after 5 seconds
        <?php if ($message): ?>
        setTimeout(() => {
            const alert = document.querySelector('.message-alert');
            if (alert) {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            }
        }, 5000);
        <?php endif; ?>

        // Add animation styles
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideIn {
                from {
                    transform: translateX(100%);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
            
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
    </script>
</body>
</html>