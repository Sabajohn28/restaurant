<?php
session_start();
include "../includes/db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != "customer") {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Get order statistics
$stats_query = "SELECT 
                COUNT(*) as total_orders,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'preparing' THEN 1 ELSE 0 END) as preparing,
                SUM(CASE WHEN status = 'ready' THEN 1 ELSE 0 END) as ready,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(total_amount) as total_spent
                FROM orders WHERE customer_id = '$user_id'";
$stats_result = mysqli_query($conn, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);

// Get orders with items (if you have order_items table)
$orders_query = "SELECT * FROM orders 
                 WHERE customer_id = '$user_id' 
                 ORDER BY 
                    CASE 
                        WHEN status = 'pending' THEN 1
                        WHEN status = 'preparing' THEN 2
                        WHEN status = 'ready' THEN 3
                        WHEN status = 'completed' THEN 4
                    END,
                    created_at DESC";
$orders_result = mysqli_query($conn, $orders_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders</title>
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

        .back-btn {
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

        .back-btn:hover {
            background: rgba(255,255,255,0.3);
            transform: translateY(-2px);
            border-color: white;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(102, 126, 234, 0.2);
        }

        .stat-icon {
            font-size: 30px;
            margin-bottom: 10px;
        }

        .stat-value {
            font-size: 28px;
            font-weight: 700;
            color: #333;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #666;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .stat-total {
            color: #28a745;
            font-weight: 600;
        }

        /* Filter Bar */
        .filter-bar {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .filter-form {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }

        .filter-select {
            padding: 12px 20px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 14px;
            background: white;
            cursor: pointer;
            transition: all 0.3s;
            min-width: 150px;
        }

        .filter-select:focus {
            outline: none;
            border-color: #667eea;
        }

        .date-input {
            padding: 12px 20px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s;
        }

        .date-input:focus {
            outline: none;
            border-color: #667eea;
        }

        .filter-btn {
            padding: 12px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .filter-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }

        /* Orders Container */
        .orders-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
            overflow: hidden;
        }

        /* Order Tabs */
        .order-tabs {
            display: flex;
            background: #f8f9fa;
            border-bottom: 2px solid #eee;
        }

        .tab-btn {
            flex: 1;
            padding: 15px;
            background: none;
            border: none;
            font-size: 14px;
            font-weight: 600;
            color: #666;
            cursor: pointer;
            transition: all 0.3s;
            position: relative;
        }

        .tab-btn:hover {
            color: #667eea;
            background: #f0f0f0;
        }

        .tab-btn.active {
            color: #667eea;
        }

        .tab-btn.active::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 100%;
            height: 2px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .tab-badge {
            background: #667eea;
            color: white;
            padding: 2px 8px;
            border-radius: 50px;
            font-size: 11px;
            margin-left: 8px;
        }

        /* Order Cards */
        .order-list {
            padding: 25px;
        }

        .order-card {
            background: white;
            border: 1px solid #eee;
            border-radius: 15px;
            margin-bottom: 20px;
            overflow: hidden;
            transition: all 0.3s ease;
            animation: fadeIn 0.5s ease-out;
        }

        .order-card:hover {
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }

        .order-header {
            padding: 20px;
            background: #f8f9fa;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #eee;
        }

        .order-info {
            display: flex;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
        }

        .order-id {
            font-size: 18px;
            font-weight: 700;
            color: #333;
        }

        .order-id span {
            color: #667eea;
            background: #e8f0fe;
            padding: 5px 10px;
            border-radius: 8px;
            font-size: 14px;
            margin-left: 10px;
        }

        .order-date {
            color: #666;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .status-badge {
            padding: 8px 16px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 600;
            text-transform: capitalize;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .status-pending { background: #fff3cd; color: #856404; }
        .status-preparing { background: #cce5ff; color: #004085; }
        .status-ready { background: #d4edda; color: #155724; }
        .status-completed { background: #e2e3e5; color: #383d41; }

        .order-body {
            padding: 20px;
        }

        /* Order Items */
        .order-items {
            margin-bottom: 20px;
        }

        .order-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px dashed #eee;
        }

        .order-item:last-child {
            border-bottom: none;
        }

        .item-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .item-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
        }

        .item-details {
            font-size: 14px;
        }

        .item-name {
            font-weight: 600;
            color: #333;
        }

        .item-quantity {
            color: #666;
            font-size: 12px;
        }

        .item-price {
            font-weight: 600;
            color: #28a745;
        }

        .order-footer {
            padding: 20px;
            background: #f8f9fa;
            border-top: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .order-total {
            font-size: 18px;
            font-weight: 700;
        }

        .order-total span {
            color: #28a745;
            margin-left: 10px;
        }

        .order-actions {
            display: flex;
            gap: 10px;
        }

        .action-btn {
            padding: 8px 20px;
            border-radius: 50px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .track-btn {
            background: #667eea;
            color: white;
        }

        .track-btn:hover {
            background: #5a67d8;
            transform: translateY(-2px);
        }

        .reorder-btn {
            background: #28a745;
            color: white;
        }

        .reorder-btn:hover {
            background: #218838;
            transform: translateY(-2px);
        }

        .review-btn {
            background: #ffc107;
            color: #333;
        }

        .review-btn:hover {
            background: #e0a800;
            transform: translateY(-2px);
        }

        /* Timeline Progress */
        .order-timeline {
            display: flex;
            justify-content: space-between;
            margin: 20px 0;
            position: relative;
        }

        .order-timeline::before {
            content: '';
            position: absolute;
            top: 15px;
            left: 0;
            width: 100%;
            height: 2px;
            background: #e0e0e0;
            z-index: 1;
        }

        .timeline-step {
            position: relative;
            z-index: 2;
            text-align: center;
            flex: 1;
        }

        .step-icon {
            width: 30px;
            height: 30px;
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            font-size: 14px;
            transition: all 0.3s;
        }

        .step-icon.completed {
            background: #28a745;
            border-color: #28a745;
            color: white;
        }

        .step-icon.active {
            background: #667eea;
            border-color: #667eea;
            color: white;
            animation: pulse 2s infinite;
        }

        .step-label {
            font-size: 12px;
            color: #666;
        }

        .step-label.completed {
            color: #28a745;
            font-weight: 600;
        }

        .step-label.active {
            color: #667eea;
            font-weight: 600;
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.1);
            }
            100% {
                transform: scale(1);
            }
        }

        /* Empty State */
        .empty-orders {
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
            font-size: 24px;
            color: #333;
            margin-bottom: 15px;
        }

        .empty-message {
            color: #666;
            margin-bottom: 30px;
        }

        .browse-menu-btn {
            display: inline-block;
            padding: 15px 40px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .browse-menu-btn:hover {
            transform: scale(1.05);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .filter-form {
                flex-direction: column;
                align-items: stretch;
            }
            
            .order-header {
                flex-direction: column;
                gap: 10px;
                align-items: flex-start;
            }
            
            .order-info {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .order-footer {
                flex-direction: column;
                align-items: flex-start;
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
                <span>Orders</span>
            </div>
            
            <div class="nav-links">
                <a href="dashboard.php" class="nav-link">
                    <span>🏠</span> Dashboard
                </a>
                <a href="menu.php" class="nav-link">
                    <span>📋</span> Menu
                </a>
                <a href="cart.php" class="nav-link">
                    <span>🛒</span> Cart
                    <?php
                    $cart_count = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
                    if ($cart_count > 0) {
                        echo "<span class='cart-badge'>$cart_count</span>";
                    }
                    ?>
                </a>
                <a href="orders.php" class="nav-link active">
                    <span>📦</span> Orders
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
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">
                📦 My Orders
                <span><?php echo $stats['total_orders'] ?? 0; ?> Total Orders</span>
            </h1>
            <a href="dashboard.php" class="back-btn">
                <span>←</span> Dashboard
            </a>
        </div>

        <!-- Statistics Cards -->
        <?php if (($stats['total_orders'] ?? 0) > 0): ?>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">📋</div>
                <div class="stat-value"><?php echo $stats['total_orders'] ?? 0; ?></div>
                <div class="stat-label">Total Orders</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">⏳</div>
                <div class="stat-value"><?php echo $stats['pending'] ?? 0; ?></div>
                <div class="stat-label">Pending</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">👨‍🍳</div>
                <div class="stat-value"><?php echo $stats['preparing'] ?? 0; ?></div>
                <div class="stat-label">Preparing</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">✅</div>
                <div class="stat-value"><?php echo $stats['ready'] ?? 0; ?></div>
                <div class="stat-label">Ready</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">💰</div>
                <div class="stat-value stat-total">T<?php echo number_format($stats['total_spent'] ?? 0, 2); ?></div>
                <div class="stat-label">Total Spent</div>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="filter-bar">
            <form method="GET" action="" class="filter-form">
                <select name="status" class="filter-select">
                    <option value="">All Orders</option>
                    <option value="pending">Pending</option>
                    <option value="preparing">Preparing</option>
                    <option value="ready">Ready</option>
                    <option value="completed">Completed</option>
                </select>
                
                <select name="sort" class="filter-select">
                    <option value="newest">Newest First</option>
                    <option value="oldest">Oldest First</option>
                    <option value="highest">Highest Amount</option>
                    <option value="lowest">Lowest Amount</option>
                </select>
                
                <input type="date" name="from_date" class="date-input" placeholder="From Date">
                <input type="date" name="to_date" class="date-input" placeholder="To Date">
                
                <button type="submit" class="filter-btn">Apply Filters</button>
            </form>
        </div>
        <?php endif; ?>

        <!-- Orders Container -->
        <div class="orders-container">
            <?php if (mysqli_num_rows($orders_result) > 0): ?>
                <!-- Order Tabs -->
                <div class="order-tabs">
                    <button class="tab-btn active" onclick="filterOrders('all')">All Orders</button>
                    <button class="tab-btn" onclick="filterOrders('pending')">Pending <?php if($stats['pending'] > 0): ?><span class="tab-badge"><?php echo $stats['pending']; ?></span><?php endif; ?></button>
                    <button class="tab-btn" onclick="filterOrders('preparing')">Preparing <?php if($stats['preparing'] > 0): ?><span class="tab-badge"><?php echo $stats['preparing']; ?></span><?php endif; ?></button>
                    <button class="tab-btn" onclick="filterOrders('ready')">Ready <?php if($stats['ready'] > 0): ?><span class="tab-badge"><?php echo $stats['ready']; ?></span><?php endif; ?></button>
                    <button class="tab-btn" onclick="filterOrders('completed')">Completed <?php if($stats['completed'] > 0): ?><span class="tab-badge"><?php echo $stats['completed']; ?></span><?php endif; ?></button>
                </div>

                <!-- Order List -->
                <div class="order-list">
                    <?php while ($order = mysqli_fetch_assoc($orders_result)): 
                        $status_class = '';
                        $status_icon = '';
                        
                        switch($order['status']) {
                            case 'pending':
                                $status_class = 'status-pending';
                                $status_icon = '⏳';
                                break;
                            case 'preparing':
                                $status_class = 'status-preparing';
                                $status_icon = '👨‍🍳';
                                break;
                            case 'ready':
                                $status_class = 'status-ready';
                                $status_icon = '✅';
                                break;
                            case 'completed':
                                $status_class = 'status-completed';
                                $status_icon = '✔️';
                                break;
                        }
                        
                        $order_date = date('M d, Y', strtotime($order['created_at']));
                        $order_time = date('h:i A', strtotime($order['created_at']));
                    ?>
                        <div class="order-card" data-status="<?php echo $order['status']; ?>">
                            <div class="order-header">
                                <div class="order-info">
                                    <div class="order-id">
                                        Order #<?php echo $order['id']; ?>
                                        <span>Table 12</span>
                                    </div>
                                    <div class="order-date">
                                        <span>📅</span> <?php echo $order_date; ?> at <?php echo $order_time; ?>
                                    </div>
                                    <span class="status-badge <?php echo $status_class; ?>">
                                        <?php echo $status_icon; ?> <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="order-body">
                                <!-- Order Timeline -->
                                <div class="order-timeline">
                                    <div class="timeline-step">
                                        <div class="step-icon <?php echo in_array($order['status'], ['pending', 'preparing', 'ready', 'completed']) ? 'completed' : ''; ?>">📋</div>
                                        <div class="step-label <?php echo in_array($order['status'], ['pending', 'preparing', 'ready', 'completed']) ? 'completed' : ''; ?>">Placed</div>
                                    </div>
                                    <div class="timeline-step">
                                        <div class="step-icon <?php echo in_array($order['status'], ['preparing', 'ready', 'completed']) ? 'completed' : ($order['status'] == 'pending' ? 'active' : ''); ?>">👨‍🍳</div>
                                        <div class="step-label <?php echo in_array($order['status'], ['preparing', 'ready', 'completed']) ? 'completed' : ($order['status'] == 'pending' ? 'active' : ''); ?>">Preparing</div>
                                    </div>
                                    <div class="timeline-step">
                                        <div class="step-icon <?php echo in_array($order['status'], ['ready', 'completed']) ? 'completed' : ($order['status'] == 'preparing' ? 'active' : ''); ?>">✅</div>
                                        <div class="step-label <?php echo in_array($order['status'], ['ready', 'completed']) ? 'completed' : ($order['status'] == 'preparing' ? 'active' : ''); ?>">Ready</div>
                                    </div>
                                    <div class="timeline-step">
                                        <div class="step-icon <?php echo $order['status'] == 'completed' ? 'completed' : ($order['status'] == 'ready' ? 'active' : ''); ?>">✔️</div>
                                        <div class="step-label <?php echo $order['status'] == 'completed' ? 'completed' : ($order['status'] == 'ready' ? 'active' : ''); ?>">Completed</div>
                                    </div>
                                </div>

                              
                            </div>
                            
                            <div class="order-footer">
                                <div class="order-total">
                                    Total Amount: <span>Tsh<?php echo number_format($order['total_amount'], 2); ?></span>
                                </div>
                                
                                <div class="order-actions">
                                    <?php if ($order['status'] != 'completed'): ?>
                                        <a href="track-order.php?id=<?php echo $order['id']; ?>" class="action-btn track-btn">
                                            <span>📍</span> Track Order
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php if ($order['status'] == 'completed'): ?>
                                        <a href="rate-order.php?id=<?php echo $order['id']; ?>" class="action-btn review-btn">
                                            <span>⭐</span> Write Review
                                        </a>
                                    <?php endif; ?>
                                    
                                    <a href="reorder.php?id=<?php echo $order['id']; ?>" class="action-btn reorder-btn">
                                        <span>🔄</span> Reorder
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <!-- Empty State -->
                <div class="empty-orders">
                    <div class="empty-icon">📦</div>
                    <h2 class="empty-title">No Orders Yet</h2>
                    <p class="empty-message">Looks like you haven't placed any orders yet. Start exploring our delicious menu!</p>
                    <a href="menu.php" class="browse-menu-btn">Browse Menu</a>
                </div>
            <?php endif; ?>
        </div>
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

        // Filter orders by status
        function filterOrders(status) {
            const orders = document.querySelectorAll('.order-card');
            const tabs = document.querySelectorAll('.tab-btn');
            
            // Update active tab
            tabs.forEach(tab => {
                tab.classList.remove('active');
            });
            event.target.classList.add('active');
            
            // Filter orders
            orders.forEach(order => {
                if (status === 'all' || order.dataset.status === status) {
                    order.style.display = 'block';
                } else {
                    order.style.display = 'none';
                }
            });
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