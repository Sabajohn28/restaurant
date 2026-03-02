<?php
session_start();
include "../includes/db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != "manager") {
    header("Location: ../login.php");
    exit;
}

$result = mysqli_query($conn,
    "SELECT payments.*, users.name 
     FROM payments
     JOIN orders ON payments.order_id = orders.id
     JOIN users ON orders.customer_id = users.id
     ORDER BY payments.paid_at DESC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Payment History - Manager</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 30px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 30px;
        }

        h2 {
            color: #333;
            margin-bottom: 25px;
            font-size: 28px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        h2:before {
            content: '💰';
            font-size: 32px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        th {
            background: #2c3e50;
            color: white;
            font-weight: 600;
            padding: 15px;
            text-align: left;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
            color: #333;
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:hover td {
            background: #f5f5f5;
        }

        /* Status colors */
        td:nth-child(5) {
            font-weight: 600;
        }

        td:nth-child(5):contains('completed') {
            color: #28a745;
        }

        td:nth-child(5):contains('pending') {
            color: #ffc107;
        }

        td:nth-child(5):contains('failed') {
            color: #dc3545;
        }

        td:nth-child(5):contains('refunded') {
            color: #6c757d;
        }

        /* Back button */
        .back-btn {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background: #2c3e50;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .back-btn:hover {
            background: #34495e;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .back-btn:before {
            content: '← ';
            font-size: 16px;
        }

        /* Amount styling */
        td:nth-child(4) {
            font-weight: 600;
            color: #28a745;
        }

        /* Date styling */
        td:nth-child(6) {
            color: #666;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Payment History</h2>

        <table>
            <tr>
                <th>Payment ID</th>
                <th>Order ID</th>
                <th>Customer</th>
                <th>Method</th>
                <th>Status</th>
                <th>Paid At</th>
            </tr>

            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td>#<?php echo str_pad($row['id'], 5, '0', STR_PAD_LEFT); ?></td>
                    <td>#<?php echo str_pad($row['order_id'], 5, '0', STR_PAD_LEFT); ?></td>
                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                    <td>
                        <?php 
                        $method = $row['method'];
                        $method_icon = '';
                        switch($method) {
                            case 'cash': $method_icon = '💵'; break;
                            case 'card': $method_icon = '💳'; break;
                            case 'online': $method_icon = '🌐'; break;
                            default: $method_icon = '💰';
                        }
                        echo $method_icon . ' ' . ucfirst($method);
                        ?>
                    </td>
                    <td style="
                        <?php 
                        $status = $row['payment_status'];
                        if($status == 'completed') echo 'color: #28a745; font-weight: 600;';
                        elseif($status == 'pending') echo 'color: #ffc107; font-weight: 600;';
                        elseif($status == 'failed') echo 'color: #dc3545; font-weight: 600;';
                        elseif($status == 'refunded') echo 'color: #6c757d; font-weight: 600;';
                        ?>
                    ">
                        <?php 
                        $status_icon = '';
                        switch($status) {
                            case 'completed': $status_icon = '✅'; break;
                            case 'pending': $status_icon = '⏳'; break;
                            case 'failed': $status_icon = '❌'; break;
                            case 'refunded': $status_icon = '↩️'; break;
                            default: $status_icon = '•';
                        }
                        echo $status_icon . ' ' . ucfirst($status);
                        ?>
                    </td>
                    <td>
                        <?php 
                        $date = new DateTime($row['paid_at']);
                        echo $date->format('M d, Y - h:i A');
                        ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        </table>

        <br>
        <a href="dashboard.php" class="back-btn">Back to Dashboard</a>
    </div>
</body>
</html>