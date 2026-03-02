<?php
session_start();
require_once "../includes/db.php";

// ============================
// CHECK MANAGER ACCESS
// ============================
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'manager') {
    header("Location: ../login.php");
    exit;
}

// ============================
// UPDATE ORDER STATUS
// ============================
if (isset($_POST['update_status'])) {
    $order_id = intval($_POST['order_id']);
    $status = $_POST['status'];

    $update = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $update->bind_param("si", $status, $order_id);
    $update->execute();
    
    // Redirect to prevent form resubmission
    header("Location: manage_orders.php?updated=1");
    exit;
}

// ============================
// DELETE ORDER (OPTIONAL)
// ============================
if (isset($_GET['delete'])) {
    $order_id = intval($_GET['delete']);
    $conn->query("DELETE FROM orders WHERE id = $order_id");
    header("Location: manage_orders.php?deleted=1");
    exit;
}

// ============================
// FETCH ALL ORDERS
// ============================
$query = "
SELECT o.id, o.status, o.created_at,
       u.name AS customer_name,
       SUM(oi.quantity * oi.price) AS total_amount,
       GROUP_CONCAT(CONCAT(oi.quantity, 'x ', m.name) SEPARATOR ', ') AS items_list,
       p.payment_status
FROM orders o
LEFT JOIN users u ON o.customer_id = u.id
LEFT JOIN order_items oi ON o.id = oi.order_id
LEFT JOIN menu_items m ON oi.menu_item_id = m.id
LEFT JOIN payments p ON o.id = p.order_id
GROUP BY o.id
ORDER BY o.created_at DESC
";

$result = $conn->query($query);

// Get status counts for summary cards
$count_query = "
SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'preparing' THEN 1 ELSE 0 END) as preparing,
    SUM(CASE WHEN status = 'ready' THEN 1 ELSE 0 END) as ready,
    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
FROM orders
";
$count_result = $conn->query($count_query);
$counts = $count_result->fetch_assoc();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Orders - Manager Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 30px;
        }

        /* Main Container */
        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 30px;
            backdrop-filter: blur(10px);
        }

        /* Header Section */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        h2 {
            font-size: 32px;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        h2 i {
            color: #667eea;
        }

        /* Dashboard Button */
        .dashboard-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            padding: 12px 25px;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            border: 2px solid transparent;
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }

        .dashboard-btn:hover {
            transform: translateY(-2px);
            border-color: white;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }

        /* Success/Error Messages */
        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
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

        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border-left: 4px solid #17a2b8;
        }

        /* Stats Cards */
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
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            border: 1px solid #eee;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.2);
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

        /* Table Container */
        .table-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            overflow: hidden;
            border: 1px solid #eee;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 15px 20px;
            text-align: left;
        }

        td {
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
            color: #333;
            font-size: 14px;
            vertical-align: middle;
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:hover td {
            background: #f8f9fa;
        }

        /* Order ID */
        .order-id {
            font-weight: 700;
            color: #667eea;
            background: #e8f0fe;
            padding: 5px 10px;
            border-radius: 8px;
            display: inline-block;
            font-size: 13px;
        }

        /* Items List */
        .items-list {
            max-width: 300px;
            font-size: 13px;
            color: #666;
        }

        /* Amount */
        .amount {
            font-weight: 700;
            color: #28a745;
            font-size: 16px;
        }

        /* Payment Status */
        .payment-status {
            padding: 5px 12px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .payment-paid {
            background: #d4edda;
            color: #155724;
        }

        .payment-unpaid {
            background: #f8d7da;
            color: #721c24;
        }

        /* Form Styles */
        .status-form {
            display: flex;
            gap: 5px;
            align-items: center;
        }

        select {
            padding: 8px 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 13px;
            background: white;
            cursor: pointer;
            transition: all 0.3s;
            font-family: inherit;
        }

        select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        /* Buttons */
        .btn {
            padding: 8px 15px;
            border: none;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            text-decoration: none;
        }

        .btn-update {
            background: #3498db;
            color: white;
        }

        .btn-update:hover {
            background: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
        }

        .btn-delete {
            background: #e74c3c;
            color: white;
        }

        .btn-delete:hover {
            background: #c0392b;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(231, 76, 60, 0.3);
        }

        /* Status Badges */
        .status-badge {
            padding: 5px 10px;
            border-radius: 50px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-block;
        }

        .status-pending { background: #fff3cd; color: #856404; }
        .status-preparing { background: #cce5ff; color: #004085; }
        .status-ready { background: #d4edda; color: #155724; }
        .status-completed { background: #e2e3e5; color: #383d41; }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }

        .empty-icon {
            font-size: 60px;
            color: #ddd;
            margin-bottom: 20px;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .stats-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 768px) {
            body {
                padding: 15px;
            }
            
            .container {
                padding: 20px;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            table {
                display: block;
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <!-- Header with Back Button -->
    <div class="header">
        <div class="header-left">
            <h2>
                <i class="fas fa-clipboard-list"></i>
                Manage Orders
            </h2>
            <a href="dashboard.php" class="dashboard-btn">
                <i class="fas fa-arrow-left"></i>
                Back to Dashboard
            </a>
        </div>
        
        <!-- Optional: Add a refresh button or current time -->
        <div style="color: #666;">
            <i class="fas fa-clock"></i>
            <?php echo date('l, F j, Y'); ?>
        </div>
    </div>

    <!-- Success/Info Messages -->
    <?php if(isset($_GET['updated'])): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            Order status updated successfully!
        </div>
    <?php endif; ?>

    <?php if(isset($_GET['deleted'])): ?>
        <div class="alert alert-info">
            <i class="fas fa-trash-alt"></i>
            Order deleted successfully!
        </div>
    <?php endif; ?>

    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">📋</div>
            <div class="stat-value"><?php echo $counts['total'] ?? 0; ?></div>
            <div class="stat-label">Total Orders</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">⏳</div>
            <div class="stat-value"><?php echo $counts['pending'] ?? 0; ?></div>
            <div class="stat-label">Pending</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">👨‍🍳</div>
            <div class="stat-value"><?php echo $counts['preparing'] ?? 0; ?></div>
            <div class="stat-label">Preparing</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">✅</div>
            <div class="stat-value"><?php echo $counts['ready'] ?? 0; ?></div>
            <div class="stat-label">Ready</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">✔️</div>
            <div class="stat-value"><?php echo $counts['completed'] ?? 0; ?></div>
            <div class="stat-label">Completed</div>
        </div>
    </div>

    <!-- Orders Table -->
    <div class="table-container">
        <?php if($result->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Customer</th>
                        <th>Items</th>
                        <th>Total</th>
                        <th>Payment</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): 
                        $status_class = '';
                        switch($row['status']) {
                            case 'pending': $status_class = 'status-pending'; break;
                            case 'preparing': $status_class = 'status-preparing'; break;
                            case 'ready': $status_class = 'status-ready'; break;
                            case 'completed': $status_class = 'status-completed'; break;
                        }
                    ?>
                    <tr>
                        <td>
                            <span class="order-id">
                                #<?php echo str_pad($row['id'], 5, '0', STR_PAD_LEFT); ?>
                            </span>
                        </td>
                        
                        <td>
                            <i class="fas fa-user" style="color: #667eea; margin-right: 5px;"></i>
                            <?php echo htmlspecialchars($row['customer_name'] ?? 'Guest'); ?>
                        </td>
                        
                        <td class="items-list">
                            <i class="fas fa-utensils" style="color: #667eea; margin-right: 5px;"></i>
                            <?php echo $row['items_list'] ?? 'No items'; ?>
                        </td>
                        
                        <td>
                            <span class="amount">
                                <i class="fas fa-tag" style="color: #28a745; margin-right: 3px;"></i>
                                TSh <?php echo number_format($row['total_amount'] ?? 0); ?>
                            </span>
                        </td>

                        <td>
                            <?php if ($row['payment_status'] === 'paid'): ?>
                                <span class="payment-status payment-paid">
                                    <i class="fas fa-check-circle"></i> Paid
                                </span>
                            <?php else: ?>
                                <span class="payment-status payment-unpaid">
                                    <i class="fas fa-exclamation-circle"></i> Unpaid
                                </span>
                            <?php endif; ?>
                        </td>

                        <td>
                            <span class="status-badge <?php echo $status_class; ?>">
                                <?php echo ucfirst($row['status']); ?>
                            </span>
                        </td>

                        <td>
                            <div style="display: flex; gap: 5px;">
                                
                                
                                
                                
                                
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <!-- Empty State -->
            <div class="empty-state">
                <div class="empty-icon">
                    <i class="fas fa-clipboard-list"></i>
                </div>
                <h3 style="margin-bottom: 10px;">No Orders Found</h3>
                <p style="color: #666;">There are no orders to display at the moment.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Auto-hide alerts after 5 seconds -->
<script>
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        });
    }, 5000);
</script>

</body>
</html>