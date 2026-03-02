<?php
session_start();
include "../includes/db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != "customer") {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Get user statistics
$stats = [];

// Total orders count
$order_query = "SELECT COUNT(*) as total_orders, 
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
                SUM(CASE WHEN status = 'preparing' THEN 1 ELSE 0 END) as preparing_orders,
                SUM(CASE WHEN status = 'ready' THEN 1 ELSE 0 END) as ready_orders,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_orders
                FROM orders WHERE customer_id = $user_id";
$order_result = mysqli_query($conn, $order_query);
$stats = mysqli_fetch_assoc($order_result);

// Total spent
$spent_query = "SELECT SUM(total_amount) as total_spent FROM orders WHERE customer_id = $user_id AND status = 'completed'";
$spent_result = mysqli_query($conn, $spent_query);
$spent_data = mysqli_fetch_assoc($spent_result);
$total_spent = $spent_data['total_spent'] ?? 0;

// Favorite items (if you have order_items table)
// $fav_query = "SELECT menu_items.name, COUNT(*) as order_count 
//               FROM order_items 
//               JOIN menu_items ON order_items.menu_item_id = menu_items.id
//               JOIN orders ON order_items.order_id = orders.id
//               WHERE orders.customer_id = $user_id 
//               GROUP BY menu_items.id 
//               ORDER BY order_count DESC 
//               LIMIT 3";
// $fav_result = mysqli_query($conn, $fav_query);

// Recent orders
$recent_query = "SELECT * FROM orders 
                 WHERE customer_id = $user_id 
                 ORDER BY created_at DESC 
                 LIMIT 5";
$recent_result = mysqli_query($conn, $recent_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dashboard - Restaurant Management System</title>
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

        /* Welcome Banner */
        .welcome-banner {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 20px;
            padding: 40px;
            color: white;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }

        .welcome-banner::before {
            content: '🍽️';
            position: absolute;
            right: 20px;
            bottom: 20px;
            font-size: 120px;
            opacity: 0.1;
            transform: rotate(10deg);
        }

        .welcome-title {
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 10px;
            animation: slideInLeft 0.8s ease-out;
        }

        .welcome-subtitle {
            font-size: 18px;
            opacity: 0.9;
            margin-bottom: 20px;
            animation: slideInLeft 1s ease-out;
        }

        .quick-actions {
            display: flex;
            gap: 15px;
            animation: slideInLeft 1.2s ease-out;
        }

        .quick-action-btn {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            text-decoration: none;
            padding: 12px 25px;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: 2px solid transparent;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .quick-action-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
            border-color: white;
        }

        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 25px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(102, 126, 234, 0.2);
        }

        .stat-icon {
            font-size: 40px;
            margin-bottom: 15px;
        }

        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: #333;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #666;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .stat-trend {
            position: absolute;
            top: 20px;
            right: 20px;
            font-size: 14px;
            padding: 4px 10px;
            border-radius: 50px;
            background: #e8f5e9;
            color: #28a745;
        }

        /* Main Content Grid */
        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 25px;
            margin-bottom: 30px;
        }

        /* Card Styles */
        .card {
            background: white;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .card-title {
            font-size: 20px;
            font-weight: 600;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-link {
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: color 0.3s;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .card-link:hover {
            color: #764ba2;
        }

        /* Recent Orders Table */
        .orders-table {
            width: 100%;
        }

        .order-row {
            display: grid;
            grid-template-columns: auto 1fr auto auto auto;
            gap: 15px;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }

        .order-row:last-child {
            border-bottom: none;
        }

        .order-id {
            font-weight: 600;
            color: #667eea;
            background: #e8f0fe;
            padding: 5px 10px;
            border-radius: 8px;
            font-size: 12px;
        }

        .order-items {
            color: #333;
            font-weight: 500;
        }

        .order-total {
            font-weight: 700;
            color: #28a745;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 600;
            text-transform: capitalize;
        }

        .status-pending { background: #fff3cd; color: #856404; }
        .status-preparing { background: #cce5ff; color: #004085; }
        .status-ready { background: #d4edda; color: #155724; }
        .status-completed { background: #e2e3e5; color: #383d41; }

        .order-action {
            color: #667eea;
            text-decoration: none;
            font-size: 12px;
            font-weight: 600;
        }

        /* Favorite Items */
        .favorite-items {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .favorite-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 15px;
            transition: all 0.3s ease;
        }

        .favorite-item:hover {
            background: #e9ecef;
            transform: translateX(5px);
        }

        .item-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
        }

        .item-info {
            flex: 1;
        }

        .item-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }

        .item-count {
            font-size: 12px;
            color: #666;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .item-count span {
            background: #667eea;
            color: white;
            padding: 2px 8px;
            border-radius: 50px;
            font-size: 11px;
        }

        .order-again-btn {
            background: none;
            border: 1px solid #667eea;
            color: #667eea;
            padding: 8px 15px;
            border-radius: 50px;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .order-again-btn:hover {
            background: #667eea;
            color: white;
        }

        /* Quick Actions Grid */
        .quick-actions-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-top: 30px;
        }

        .action-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            border: 2px solid transparent;
        }

        .action-card:hover {
            transform: translateY(-5px);
            border-color: #667eea;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.2);
        }

        .action-icon {
            font-size: 40px;
            margin-bottom: 15px;
        }

        .action-title {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }

        .action-desc {
            font-size: 12px;
            color: #666;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #666;
        }

        .empty-icon {
            font-size: 60px;
            color: #ddd;
            margin-bottom: 20px;
        }

        .empty-text {
            font-size: 16px;
            margin-bottom: 20px;
        }

        .empty-btn {
            display: inline-block;
            padding: 12px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .empty-btn:hover {
            transform: scale(1.05);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .content-grid {
                grid-template-columns: 1fr;
            }
            
            .quick-actions-grid {
                grid-template-columns: 1fr;
            }
            
            .nav-container {
                flex-direction: column;
                gap: 15px;
            }
            
            .nav-links {
                flex-wrap: wrap;
                justify-content: center;
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
                <span>Customer</span>
            </div>
            
            <div class="nav-links">
                <a href="dashboard.php" class="nav-link active">
                    <span>🏠</span> Dashboard
                </a>
                <a href="menu.php" class="nav-link">
                    <span>📋</span> Menu
                </a>
                <a href="cart.php" class="nav-link">
                    <span>🛒</span> Cart
                    <?php
                    // Get cart count from session or database
                    $cart_count = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
                    if ($cart_count > 0) {
                        echo "<span style='background: #ff4444; color: white; padding: 2px 8px; border-radius: 50px; font-size: 12px; margin-left: 5px;'>$cart_count</span>";
                    }
                    ?>
                </a>
                <a href="orders.php" class="nav-link">
                    <span>📦</span> My Orders
                </a>
            </div>
            
            <div class="user-menu" onclick="toggleUserMenu()">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($user_name, 0, 1)); ?>
                </div>
                <div class="user-info">
                    <span class="user-name"><?php echo htmlspecialchars($user_name); ?></span>
                    <span class="user-role">Customer</span>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container">
        <!-- Welcome Banner -->
        <div class="welcome-banner">
            <h1 class="welcome-title">Welcome back, <?php echo explode(' ', $user_name)[0]; ?>! 👋</h1>
            <p class="welcome-subtitle">Ready to order something delicious today?</p>
            <div class="quick-actions">
                <a href="menu.php" class="quick-action-btn">
                    <span>🍕</span> Browse Menu
                </a>
                <a href="cart.php" class="quick-action-btn">
                    <span>🛒</span> View Cart
                </a>
                <a href="orders.php" class="quick-action-btn">
                    <span>📦</span> Track Orders
                </a>
            </div>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">📋</div>
                <div class="stat-value"><?php echo $stats['total_orders'] ?? 0; ?></div>
                <div class="stat-label">Total Orders</div>
                <?php if (($stats['pending_orders'] ?? 0) > 0): ?>
                <div class="stat-trend">+<?php echo $stats['pending_orders']; ?> pending</div>
                <?php endif; ?>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">⏳</div>
                <div class="stat-value"><?php echo ($stats['pending_orders'] ?? 0) + ($stats['preparing_orders'] ?? 0); ?></div>
                <div class="stat-label">Active Orders</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">✅</div>
                <div class="stat-value"><?php echo $stats['completed_orders'] ?? 0; ?></div>
                <div class="stat-label">Completed</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">💰</div>
                <div class="stat-value">Tsh<?php echo number_format($total_spent, 2); ?></div>
                <div class="stat-label">Total Spent</div>
            </div>
        </div>

        <!-- Main Content Grid -->
        <div class="content-grid">
            <!-- Recent Orders -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <span>📋</span> Recent Orders
                    </h3>
                    <a href="orders.php" class="card-link">
                        View All <span>→</span>
                    </a>
                </div>
                
                <?php if (mysqli_num_rows($recent_result) > 0): ?>
                    <div class="orders-table">
                        <?php while ($order = mysqli_fetch_assoc($recent_result)): 
                            $status_class = '';
                            switch($order['status']) {
                                case 'pending': $status_class = 'status-pending'; break;
                                case 'preparing': $status_class = 'status-preparing'; break;
                                case 'ready': $status_class = 'status-ready'; break;
                                case 'completed': $status_class = 'status-completed'; break;
                            }
                        ?>
                        <div class="order-row">
                            <span class="order-id">#<?php echo $order['id']; ?></span>
                            <span class="order-items">Order #<?php echo $order['id']; ?></span>
                            <span class="order-total">Tsh<?php echo number_format($order['total_amount'], 2); ?></span>
                            <span class="status-badge <?php echo $status_class; ?>">
                                <?php echo ucfirst($order['status']); ?>
                            </span>
                            <a href="order-details.php?id=<?php echo $order['id']; ?>" class="order-action">View →</a>
                        </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon">📦</div>
                        <div class="empty-text">No orders yet</div>
                        <a href="menu.php" class="empty-btn">Browse Menu</a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Favorite Items -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <span>❤️</span> Favorite Items
                    </h3>
                    <a href="menu.php" class="card-link">
                        Explore <span>→</span>
                    </a>
                </div>
                
                <div class="favorite-items">
                    <!-- Static favorite items for demo -->
                    
                    
                    
                    
                    
                    
                    
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions-grid">
            <a href="menu.php" class="action-card">
                <div class="action-icon">🍽️</div>
                <div class="action-title">Browse Menu</div>
                <div class="action-desc">Explore our delicious dishes</div>
            </a>
            
            <a href="cart.php" class="action-card">
                <div class="action-icon">🛒</div>
                <div class="action-title">View Cart</div>
                <div class="action-desc"><?php echo $cart_count > 0 ? "$cart_count items waiting" : "Your cart is empty"; ?></div>
            </a>
            
            <a href="orders.php" class="action-card">
                <div class="action-icon">📦</div>
                <div class="action-title">Track Orders</div>
                <div class="action-desc">Check your order status</div>
            </a>
            
            <a href="profile.php" class="action-card">
                <div class="action-icon">👤</div>
                <div class="action-title">My Profile</div>
                <div class="action-desc">Manage your account</div>
            </a>
            
            <a href="favorites.php" class="action-card">
                <div class="action-icon">❤️</div>
                <div class="action-title">Favorites</div>
                <div class="action-desc">Your saved items</div>
            </a>
            
            <a href="../logout.php" class="action-card" style="border-color: #ff4444;">
                <div class="action-icon">🚪</div>
                <div class="action-title" style="color: #ff4444;">Logout</div>
                <div class="action-desc">Sign out from your account</div>
            </a>
        </div>
    </div>

    <!-- User Menu Dropdown (hidden by default) -->
    <div id="userDropdown" style="display: none; position: fixed; top: 80px; right: 30px; background: white; border-radius: 10px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); padding: 15px; min-width: 200px; z-index: 1000;">
        <a href="profile.php" style="display: block; padding: 10px; text-decoration: none; color: #333; border-radius: 5px; transition: background 0.3s;">
            👤 My Profile
        </a>
        <a href="settings.php" style="display: block; padding: 10px; text-decoration: none; color: #333; border-radius: 5px; transition: background 0.3s;">
            ⚙️ Settings
        </a>
        <a href="../logout.php" style="display: block; padding: 10px; text-decoration: none; color: #ff4444; border-radius: 5px; transition: background 0.3s;">
            🚪 Logout
        </a>
    </div>

    <script>
        // Toggle user menu dropdown
        function toggleUserMenu() {
            const dropdown = document.getElementById('userDropdown');
            if (dropdown.style.display === 'none') {
                dropdown.style.display = 'block';
                // Close when clicking outside
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

        // Order again function
        function orderAgain(itemName) {
            // Show notification
            showNotification(`🍽️ Added ${itemName} to cart!`, 'success');
            
            // You can add actual cart functionality here
            setTimeout(() => {
                window.location.href = 'cart.php';
            }, 1500);
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