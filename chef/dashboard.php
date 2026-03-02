<?php
session_start();
include "../includes/db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != "chef") {
    header("Location: ../login.php");
    exit;
}

/* UPDATE ORDER STATUS */
if (isset($_GET['update']) && isset($_GET['status'])) {
    
    $order_id = $_GET['update'];
    $status = $_GET['status'];

    mysqli_query($conn, "UPDATE orders SET status='$status' WHERE id='$order_id'");
    
    // Redirect to refresh the page and remove URL parameters
    header("Location: dashboard.php");
    exit;
}

/* FETCH ALL ORDERS */
$result = mysqli_query($conn,
    "SELECT orders.*, users.name 
     FROM orders 
     JOIN users ON orders.customer_id = users.id
     ORDER BY 
        CASE 
            WHEN orders.status = 'pending' THEN 1
            WHEN orders.status = 'preparing' THEN 2
            WHEN orders.status = 'ready' THEN 3
            WHEN orders.status = 'completed' THEN 4
        END, orders.created_at ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chef Dashboard - Kitchen Panel</title>
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
            padding: 20px;
        }

        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }

        .dashboard-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .dashboard-header h1 {
            font-size: 32px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .dashboard-header h1 i {
            font-size: 40px;
        }

        .logout-btn {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            padding: 12px 30px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .logout-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
            border-color: white;
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            padding: 30px;
            background: #f8f9fa;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            text-align: center;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }

        .stat-card h3 {
            color: #666;
            font-size: 14px;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .stat-card .stat-number {
            font-size: 36px;
            font-weight: 700;
            color: #667eea;
        }

        .orders-section {
            padding: 30px;
        }

        .orders-section h2 {
            color: #333;
            margin-bottom: 25px;
            font-size: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .orders-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
        }

        .order-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            overflow: hidden;
            transition: all 0.3s ease;
            border: 1px solid #eee;
        }

        .order-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(102, 126, 234, 0.2);
        }

        .order-header {
            padding: 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .order-id {
            font-size: 20px;
            font-weight: 700;
            color: #333;
        }

        .status-badge {
            padding: 8px 16px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .status-pending { background: #fff3cd; color: #856404; }
        .status-preparing { background: #cce5ff; color: #004085; }
        .status-ready { background: #d4edda; color: #155724; }
        .status-completed { background: #e2e3e5; color: #383d41; }

        .order-details {
            padding: 20px;
            background: #f8f9fa;
        }

        .customer-info {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
        }

        .customer-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 18px;
        }

        .customer-name {
            font-weight: 600;
            color: #333;
        }

        .order-amount {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
            padding: 15px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }

        .amount-label {
            color: #666;
            font-size: 14px;
        }

        .amount-value {
            font-size: 24px;
            font-weight: 700;
            color: #28a745;
            margin-left: auto;
        }

        .order-actions {
            padding: 20px;
            text-align: center;
        }

        .action-btn {
            display: inline-block;
            padding: 12px 30px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            width: 100%;
            text-align: center;
        }

        .btn-pending {
            background: #ffc107;
            color: #212529;
        }

        .btn-pending:hover {
            background: #e0a800;
            transform: scale(1.02);
        }

        .btn-preparing {
            background: #007bff;
            color: white;
        }

        .btn-preparing:hover {
            background: #0056b3;
            transform: scale(1.02);
        }

        .btn-ready {
            background: #28a745;
            color: white;
        }

        .btn-ready:hover {
            background: #218838;
            transform: scale(1.02);
        }

        .btn-completed {
            background: #6c757d;
            color: white;
            cursor: default;
            opacity: 0.7;
        }

        .no-orders {
            text-align: center;
            padding: 60px;
            color: #666;
            font-size: 18px;
            grid-column: 1 / -1;
        }

        .no-orders i {
            font-size: 60px;
            color: #ddd;
            margin-bottom: 20px;
            display: block;
        }

        @media (max-width: 768px) {
            .dashboard-header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .stats-container {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .orders-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="dashboard-header">
            <h1>
                <span>👨‍🍳</span>
                Chef's Kitchen Dashboard
            </h1>
            <a href="../logout.php" class="logout-btn">Logout</a>
        </div>

        <?php
        // Calculate statistics
        $total_orders = mysqli_num_rows($result);
        mysqli_data_seek($result, 0); // Reset pointer
        
        $pending_count = 0;
        $preparing_count = 0;
        $ready_count = 0;
        
        while ($row = mysqli_fetch_assoc($result)) {
            if ($row['status'] == 'pending') $pending_count++;
            elseif ($row['status'] == 'preparing') $preparing_count++;
            elseif ($row['status'] == 'ready') $ready_count++;
        }
        mysqli_data_seek($result, 0); // Reset pointer again
        ?>

        <div class="stats-container">
            <div class="stat-card">
                <h3>Total Orders</h3>
                <div class="stat-number"><?php echo $total_orders; ?></div>
            </div>
            <div class="stat-card">
                <h3>Pending</h3>
                <div class="stat-number" style="color: #ffc107;"><?php echo $pending_count; ?></div>
            </div>
            <div class="stat-card">
                <h3>Preparing</h3>
                <div class="stat-number" style="color: #007bff;"><?php echo $preparing_count; ?></div>
            </div>
            <div class="stat-card">
                <h3>Ready</h3>
                <div class="stat-number" style="color: #28a745;"><?php echo $ready_count; ?></div>
            </div>
        </div>

        <div class="orders-section">
            <h2>
                <span>📋</span>
                Current Orders
            </h2>
            
            <div class="orders-grid">
                <?php 
                if (mysqli_num_rows($result) > 0) {
                    while ($row = mysqli_fetch_assoc($result)) { 
                        $status_class = '';
                        $status_text = '';
                        
                        switch($row['status']) {
                            case 'pending':
                                $status_class = 'status-pending';
                                $status_text = '⏳ Pending';
                                break;
                            case 'preparing':
                                $status_class = 'status-preparing';
                                $status_text = '👨‍🍳 Preparing';
                                break;
                            case 'ready':
                                $status_class = 'status-ready';
                                $status_text = '✅ Ready';
                                break;
                            case 'completed':
                                $status_class = 'status-completed';
                                $status_text = '✔️ Completed';
                                break;
                        }
                ?>
                    <div class="order-card">
                        <div class="order-header">
                            <span class="order-id">#<?php echo $row['id']; ?></span>
                            <span class="status-badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                        </div>
                        
                        <div class="order-details">
                            <div class="customer-info">
                                <div class="customer-icon">
                                    <?php echo strtoupper(substr($row['name'], 0, 1)); ?>
                                </div>
                                <div class="customer-name"><?php echo htmlspecialchars($row['name']); ?></div>
                            </div>
                            
                            <div class="order-amount">
                                <span class="amount-label">Total Amount:</span>
                                <span class="amount-value">Tsh<?php echo number_format($row['total_amount'], 2); ?></span>
                            </div>
                        </div>
                        
                        <div class="order-actions">
                            <?php
                            if ($row['status'] == "pending") {
                                echo "<a href='dashboard.php?update=".$row['id']."&status=preparing' class='action-btn btn-pending'>Start Preparing</a>";
                            }
                            elseif ($row['status'] == "preparing") {
                                echo "<a href='dashboard.php?update=".$row['id']."&status=ready' class='action-btn btn-preparing'>Mark as Ready</a>";
                            }
                            elseif ($row['status'] == "ready") {
                                echo "<a href='dashboard.php?update=".$row['id']."&status=completed' class='action-btn btn-ready'>Mark as Completed</a>";
                            }
                            else {
                                echo "<button class='action-btn btn-completed' disabled>Order Completed</button>";
                            }
                            ?>
                        </div>
                    </div>
                <?php 
                    }
                } else { 
                ?>
                    <div class="no-orders">
                        <span>🍽️</span>
                        <h3>No Orders Yet</h3>
                        <p>Waiting for new orders to arrive...</p>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>
</body>
</html>