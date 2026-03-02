<?php
session_start();
require_once "../includes/db.php";

// 🔐 Security check with proper exit
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== "manager") {
    $_SESSION['error'] = "Access denied. Manager privileges required.";
    header("Location: ../login.php");
    exit;
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: ../login.php");
    exit;
}

// ✨ Handle Chef Actions
$message = '';
$messageType = '';

// Add new chef
if (isset($_POST['add_chef'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $specialty = mysqli_real_escape_string($conn, $_POST['specialty']);
    $experience = intval($_POST['experience']);
    $salary = floatval($_POST['salary']);
    $shift = mysqli_real_escape_string($conn, $_POST['shift']);
    $password = password_hash('chef123', PASSWORD_DEFAULT); // Default password
    
    // Check if email exists
    $check = mysqli_query($conn, "SELECT id FROM users WHERE email = '$email'");
    if (mysqli_num_rows($check) > 0) {
        $message = "❌ Email already exists!";
        $messageType = "error";
    } else {
        // Note: 'status' column might not exist, so we'll omit it
        $query = "INSERT INTO users (name, email, phone, password, role, specialty, experience, salary, shift, created_at) 
                  VALUES ('$name', '$email', '$phone', '$password', 'chef', '$specialty', $experience, $salary, '$shift', NOW())";
        
        if (mysqli_query($conn, $query)) {
            $message = "✅ Chef added successfully! Default password: chef123";
            $messageType = "success";
        } else {
            $message = "❌ Error adding chef: " . mysqli_error($conn);
            $messageType = "error";
        }
    }
}

// Update chef
if (isset($_POST['update_chef'])) {
    $id = intval($_POST['chef_id']);
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $specialty = mysqli_real_escape_string($conn, $_POST['specialty']);
    $experience = intval($_POST['experience']);
    $salary = floatval($_POST['salary']);
    $shift = mysqli_real_escape_string($conn, $_POST['shift']);
    
    // Note: 'status' column might not exist, so we'll omit it
    $query = "UPDATE users SET 
              name = '$name',
              phone = '$phone',
              specialty = '$specialty',
              experience = $experience,
              salary = $salary,
              shift = '$shift'
              WHERE id = $id AND role = 'chef'";
    
    if (mysqli_query($conn, $query)) {
        $message = "✅ Chef updated successfully!";
        $messageType = "success";
    } else {
        $message = "❌ Error updating chef: " . mysqli_error($conn);
        $messageType = "error";
    }
}

// Delete chef
if (isset($_GET['delete_chef'])) {
    $id = intval($_GET['delete_chef']);
    $query = "DELETE FROM users WHERE id = $id AND role = 'chef'";
    
    if (mysqli_query($conn, $query)) {
        $message = "✅ Chef removed successfully!";
        $messageType = "success";
    } else {
        $message = "❌ Error removing chef: " . mysqli_error($conn);
        $messageType = "error";
    }
}

// Reset chef password
if (isset($_GET['reset_password'])) {
    $id = intval($_GET['reset_password']);
    $newPassword = password_hash('chef123', PASSWORD_DEFAULT);
    
    $query = "UPDATE users SET password = '$newPassword' WHERE id = $id AND role = 'chef'";
    
    if (mysqli_query($conn, $query)) {
        $message = "✅ Password reset to: chef123";
        $messageType = "success";
    } else {
        $message = "❌ Error resetting password: " . mysqli_error($conn);
        $messageType = "error";
    }
}

// Fetch chef data for editing
$editChef = null;
if (isset($_GET['edit_chef'])) {
    $id = intval($_GET['edit_chef']);
    $result = mysqli_query($conn, "SELECT * FROM users WHERE id = $id AND role = 'chef'");
    $editChef = mysqli_fetch_assoc($result);
}

// Fetch all chefs
$chefs = mysqli_query($conn, "SELECT * FROM users WHERE role = 'chef' ORDER BY created_at DESC");

// Statistics Queries - FIXED: Removed 'status' column references
$total_orders = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) AS total FROM orders")
)['total'] ?? 0;

$total_customers = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) AS total FROM users WHERE role='customer'")
)['total'] ?? 0;

$total_sales = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COALESCE(SUM(total_amount), 0) AS total FROM orders WHERE status='completed'")
)['total'] ?? 0;

$total_chefs = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) AS total FROM users WHERE role='chef'")
)['total'] ?? 0;

// FIXED: Removed the query that was causing the error (line 135)
// $active_chefs is no longer used
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manager Dashboard - Chef Management | Chef D</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #ff4757;
            --secondary: #2c3e50;
            --success: #2ecc71;
            --warning: #f39c12;
            --danger: #e74c3c;
            --dark: #2c3e50;
            --light: #f5f7fa;
            --chef-gold: #ffb347;
            --chef-brown: #8B4513;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem;
        }
        
        .dashboard {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        /* Header Styles */
        .header {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            animation: slideDown 0.5s ease;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .header h2 {
            font-size: 2.5rem;
            color: var(--secondary);
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .header h2 i {
            color: var(--primary);
            animation: spin 20s linear infinite;
        }
        
        .header p {
            color: #666;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .badge {
            background: var(--primary);
            color: white;
            padding: 0.3rem 1rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        /* Logout Button */
        .logout-btn {
            background: var(--danger);
            color: white;
            padding: 0.8rem 1.5rem;
            border-radius: 10px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        
        .logout-btn:hover {
            background: white;
            color: var(--danger);
            border-color: var(--danger);
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(231, 76, 60, 0.3);
        }
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 1.8rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card.chef-stat {
            background: linear-gradient(135deg, var(--chef-brown), var(--chef-gold));
            color: white;
        }
        
        .stat-card.chef-stat .stat-icon {
            background: rgba(255,255,255,0.2);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.15);
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            font-size: 1.8rem;
            color: white;
            background: linear-gradient(135deg, var(--primary), #ff6b4a);
        }
        
        .stat-content h3 {
            font-size: 0.9rem;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 0.5rem;
        }
        
        .stat-card.chef-stat .stat-content h3 {
            color: rgba(255,255,255,0.9);
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--secondary);
            line-height: 1.2;
            margin-bottom: 0.5rem;
        }
        
        .stat-card.chef-stat .stat-number {
            color: white;
        }
        
        /* Message Alert */
        .message {
            padding: 1rem;
            border-radius: 10px;
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
        
        /* Chef Management Section */
        .chef-section {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .section-header h3 {
            font-size: 1.5rem;
            color: var(--secondary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .section-header h3 i {
            color: var(--primary);
        }
        
        .btn {
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary), #ff6b4a);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(255,71,87,0.3);
        }
        
        .btn-secondary {
            background: var(--secondary);
            color: white;
        }
        
        .btn-secondary:hover {
            background: #34495e;
        }
        
        .btn-success {
            background: var(--success);
            color: white;
        }
        
        .btn-warning {
            background: var(--warning);
            color: white;
        }
        
        .btn-danger {
            background: var(--danger);
            color: white;
        }
        
        /* Chef Form */
        .chef-form {
            background: #f8f9fa;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            display: <?php echo $editChef ? 'block' : 'none'; ?>;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--secondary);
            font-weight: 500;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(255,71,87,0.1);
        }
        
        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 1.5rem;
        }
        
        /* Chef Grid */
        .chef-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
        }
        
        .chef-card {
            background: linear-gradient(135deg, #fff, #f8f9fa);
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            border: 1px solid #e0e0e0;
            position: relative;
            overflow: hidden;
        }
        
        .chef-card::before {
            content: '👨‍🍳';
            position: absolute;
            top: -10px;
            right: -10px;
            font-size: 5rem;
            opacity: 0.1;
            transform: rotate(15deg);
        }
        
        .chef-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.15);
            border-color: var(--primary);
        }
        
        .chef-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .chef-avatar {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--chef-brown), var(--chef-gold));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: white;
        }
        
        .chef-info h4 {
            font-size: 1.2rem;
            color: var(--secondary);
            margin-bottom: 0.2rem;
        }
        
        .chef-info p {
            color: #666;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .chef-details {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin: 1rem 0;
            padding: 1rem 0;
            border-top: 1px solid #e0e0e0;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .detail-item {
            text-align: center;
        }
        
        .detail-item i {
            color: var(--primary);
            font-size: 1.2rem;
            margin-bottom: 0.3rem;
        }
        
        .detail-item span {
            display: block;
            font-size: 0.8rem;
            color: #666;
        }
        
        .detail-item strong {
            display: block;
            font-size: 1rem;
            color: var(--secondary);
        }
        
        .chef-actions {
            display: flex;
            gap: 0.5rem;
            justify-content: flex-end;
            margin-top: 1rem;
        }
        
        .chef-actions button,
        .chef-actions a {
            width: 35px;
            height: 35px;
            border-radius: 8px;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            color: white;
        }
        
        .btn-edit {
            background: var(--warning);
        }
        
        .btn-reset {
            background: var(--success);
        }
        
        .btn-delete {
            background: var(--danger);
        }
        
        .btn-edit:hover,
        .btn-reset:hover,
        .btn-delete:hover {
            transform: scale(1.1);
        }
        
        /* Action Grid */
        .action-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin: 2rem 0;
        }
        
        .action-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            text-align: center;
            text-decoration: none;
            color: var(--secondary);
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        
        .action-card:hover {
            transform: translateY(-5px);
            border-color: var(--primary);
            box-shadow: 0 10px 30px rgba(255,71,87,0.2);
        }
        
        .action-card i {
            font-size: 2.5rem;
            color: var(--primary);
            margin-bottom: 1rem;
        }
        
        /* Recent Activity */
        .recent-activity {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            margin-top: 2rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .activity-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            border-bottom: 1px solid #f0f0f0;
            transition: all 0.3s ease;
        }
        
        .activity-item:hover {
            background: #f8f9fa;
            transform: translateX(10px);
        }
        
        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: #fff0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
        }
        
        /* Animations */
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
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
        
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            body { padding: 1rem; }
            .header h2 { font-size: 1.5rem; }
            .chef-grid { grid-template-columns: 1fr; }
            .form-grid { grid-template-columns: 1fr; }
            .header {
                flex-direction: column;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <!-- Header with Logout Button -->
        <div class="header">
            <div>
                <h2>
                    <i class="fas fa-hat-wizard"></i> 
                    Manager's Dashboard
                    <span class="badge">⚡ Live</span>
                </h2>
                <p>
                    <i class="fas fa-calendar-alt"></i> 
                    <?php echo date('l, F j, Y'); ?>
                    <i class="fas fa-clock"></i>
                    <span id="liveClock"></span>
                </p>
            </div>
            <a href="?logout=1" class="logout-btn" onclick="return confirm('Are you sure you want to logout?')">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
        
        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-shopping-bag"></i>
                </div>
                <div class="stat-content">
                    <h3>Total Orders</h3>
                    <div class="stat-number"><?php echo number_format($total_orders); ?></div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-content">
                    <h3>Customers</h3>
                    <div class="stat-number"><?php echo number_format($total_customers); ?></div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-coins"></i>
                </div>
                <div class="stat-content">
                    <h3>Revenue</h3>
                    <div class="stat-number">TSh <?php echo number_format($total_sales, 0); ?></div>
                </div>
            </div>
            
            <div class="stat-card chef-stat">
                <div class="stat-icon">
                    <i class="fas fa-user-chef"></i>
                </div>
                <div class="stat-content">
                    <h3>Kitchen Chefs</h3>
                    <div class="stat-number"><?php echo $total_chefs; ?></div>
                </div>
            </div>
        </div>
        
        <!-- Chef Management Section -->
        <div class="chef-section">
            <div class="section-header">
                <h3>
                    <i class="fas fa-user-chef"></i>
                    👨‍🍳 Kitchen Chefs Management
                </h3>
                <button class="btn btn-primary" onclick="toggleChefForm()">
                    <i class="fas fa-plus"></i> Add New Chef
                </button>
            </div>
            
            <!-- Add/Edit Chef Form -->
            <div class="chef-form" id="chefForm">
                <h4 style="margin-bottom: 1rem; color: var(--secondary);">
                    <i class="fas fa-<?php echo $editChef ? 'edit' : 'plus-circle'; ?>"></i>
                    <?php echo $editChef ? 'Edit Chef' : 'Add New Chef'; ?>
                </h4>
                
                <form method="POST" action="">
                    <?php if ($editChef): ?>
                        <input type="hidden" name="chef_id" value="<?php echo $editChef['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Full Name *</label>
                            <input type="text" name="name" required 
                                   value="<?php echo $editChef['name'] ?? ''; ?>"
                                   placeholder="e.g., Chef John Doe">
                        </div>
                        
                        <div class="form-group">
                            <label>Email *</label>
                            <input type="email" name="email" required 
                                   value="<?php echo $editChef['email'] ?? ''; ?>"
                                   placeholder="chef@example.com"
                                   <?php echo $editChef ? 'readonly' : ''; ?>>
                            <?php if (!$editChef): ?>
                                <small style="color: #666;">Default password: chef123</small>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label>Phone Number</label>
                            <input type="tel" name="phone" 
                                   value="<?php echo $editChef['phone'] ?? ''; ?>"
                                   placeholder="+255 XXX XXX XXX">
                        </div>
                        
                        <div class="form-group">
                            <label>Specialty *</label>
                            <select name="specialty" required>
                                <option value="">Select Specialty</option>
                                <option value="Head Chef" <?php echo ($editChef['specialty'] ?? '') == 'Head Chef' ? 'selected' : ''; ?>>Head Chef</option>
                                <option value="Sous Chef" <?php echo ($editChef['specialty'] ?? '') == 'Sous Chef' ? 'selected' : ''; ?>>Sous Chef</option>
                                <option value="Pastry Chef" <?php echo ($editChef['specialty'] ?? '') == 'Pastry Chef' ? 'selected' : ''; ?>>Pastry Chef</option>
                                <option value="Grill Master" <?php echo ($editChef['specialty'] ?? '') == 'Grill Master' ? 'selected' : ''; ?>>Grill Master</option>
                                <option value="Sauce Chef" <?php echo ($editChef['specialty'] ?? '') == 'Sauce Chef' ? 'selected' : ''; ?>>Sauce Chef</option>
                                <option value="Prep Chef" <?php echo ($editChef['specialty'] ?? '') == 'Prep Chef' ? 'selected' : ''; ?>>Prep Chef</option>
                                <option value="Line Cook" <?php echo ($editChef['specialty'] ?? '') == 'Line Cook' ? 'selected' : ''; ?>>Line Cook</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Experience (years)</label>
                            <input type="number" name="experience" min="0" max="50"
                                   value="<?php echo $editChef['experience'] ?? '5'; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Monthly Salary (TSh)</label>
                            <input type="number" name="salary" min="0" step="10000"
                                   value="<?php echo $editChef['salary'] ?? '500000'; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Shift</label>
                            <select name="shift">
                                <option value="morning" <?php echo ($editChef['shift'] ?? '') == 'morning' ? 'selected' : ''; ?>>Morning (6AM - 2PM)</option>
                                <option value="evening" <?php echo ($editChef['shift'] ?? '') == 'evening' ? 'selected' : ''; ?>>Evening (2PM - 10PM)</option>
                                <option value="night" <?php echo ($editChef['shift'] ?? '') == 'night' ? 'selected' : ''; ?>>Night (10PM - 6AM)</option>
                                <option value="rotating" <?php echo ($editChef['shift'] ?? '') == 'rotating' ? 'selected' : ''; ?>>Rotating</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" onclick="toggleChefForm()">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                        <button type="submit" name="<?php echo $editChef ? 'update_chef' : 'add_chef'; ?>" class="btn btn-primary">
                            <i class="fas fa-<?php echo $editChef ? 'save' : 'plus'; ?>"></i>
                            <?php echo $editChef ? 'Update Chef' : 'Add Chef'; ?>
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Chefs Grid -->
            <div class="chef-grid">
                <?php if (mysqli_num_rows($chefs) > 0): ?>
                    <?php while ($chef = mysqli_fetch_assoc($chefs)): ?>
                        <div class="chef-card">
                            <div class="chef-header">
                                <div class="chef-avatar">
                                    <i class="fas fa-user-chef"></i>
                                </div>
                                <div class="chef-info">
                                    <h4><?php echo htmlspecialchars($chef['name']); ?></h4>
                                    <p>
                                        <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($chef['email']); ?>
                                    </p>
                                    <?php if ($chef['phone']): ?>
                                        <p>
                                            <i class="fas fa-phone"></i> <?php echo htmlspecialchars($chef['phone']); ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="chef-details">
                                <div class="detail-item">
                                    <i class="fas fa-utensils"></i>
                                    <span>Specialty</span>
                                    <strong><?php echo $chef['specialty'] ?? 'Line Cook'; ?></strong>
                                </div>
                                <div class="detail-item">
                                    <i class="fas fa-clock"></i>
                                    <span>Shift</span>
                                    <strong><?php echo ucfirst($chef['shift'] ?? 'Morning'); ?></strong>
                                </div>
                                <div class="detail-item">
                                    <i class="fas fa-briefcase"></i>
                                    <span>Experience</span>
                                    <strong><?php echo $chef['experience'] ?? '5'; ?> years</strong>
                                </div>
                                <div class="detail-item">
                                    <i class="fas fa-money-bill"></i>
                                    <span>Salary</span>
                                    <strong>TSh <?php echo number_format($chef['salary'] ?? 500000, 0); ?></strong>
                                </div>
                            </div>
                            
                            <div style="color: #666; font-size: 0.8rem; margin: 0.5rem 0; text-align: right;">
                                <i class="fas fa-calendar-alt"></i>
                                Joined: <?php echo date('M Y', strtotime($chef['created_at'] ?? 'now')); ?>
                            </div>
                            
                            <div class="chef-actions">
                                <a href="?edit_chef=<?php echo $chef['id']; ?>" class="btn-edit" title="Edit Chef">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="?reset_password=<?php echo $chef['id']; ?>" class="btn-reset" title="Reset Password (to chef123)" 
                                   onclick="return confirm('Reset password to default (chef123)?')">
                                    <i class="fas fa-key"></i>
                                </a>
                                <a href="?delete_chef=<?php echo $chef['id']; ?>" class="btn-delete" title="Remove Chef"
                                   onclick="return confirm('Are you sure you want to remove this chef?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div style="grid-column: 1/-1; text-align: center; padding: 3rem; color: #666;">
                        <i class="fas fa-user-chef" style="font-size: 4rem; opacity: 0.3; margin-bottom: 1rem;"></i>
                        <h3>No Chefs Found</h3>
                        <p>Start by adding your first kitchen chef!</p>
                        <button class="btn btn-primary" onclick="toggleChefForm()" style="margin-top: 1rem;">
                            <i class="fas fa-plus"></i> Add First Chef
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Action Grid -->
        <div class="action-grid">
            <a href="manage_menu.php" class="action-card">
                <i class="fas fa-utensils"></i>
                <h4>Menu Manager</h4>
                <p>Create & edit dishes</p>
            </a>
            
            <a href="orders.php" class="action-card">
                <i class="fas fa-clipboard-list"></i>
                <h4>Order Manager</h4>
                <p>Track all orders</p>
            </a>
            
            <a href="payments.php" class="action-card">
                <i class="fas fa-hand-holding-usd"></i>
                <h4>Payment Manager</h4>
                <p>View transactions</p>
            </a>
        </div>
        
        <!-- Recent Activity -->
        <div class="recent-activity">
            <h3 style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1.5rem;">
                <i class="fas fa-history"></i>
                Recent Kitchen Activity
            </h3>
            
            <?php
            $recent = mysqli_query($conn, 
                "SELECT o.*, u.name AS customer_name
                 FROM orders o
                 JOIN users u ON o.customer_id = u.id
                 ORDER BY o.created_at DESC 
                 LIMIT 5"
            );
            
            if ($recent && mysqli_num_rows($recent) > 0):
                while ($row = mysqli_fetch_assoc($recent)):
            ?>
            <div class="activity-item">
                <div class="activity-icon">
                    <i class="fas fa-<?php 
                        echo $row['status'] == 'completed' ? 'check-circle' : 
                            ($row['status'] == 'pending' ? 'clock' : 'truck'); 
                    ?>"></i>
                </div>
                <div class="activity-content" style="flex: 1;">
                    <h4 style="font-size: 0.95rem;">Order #<?php echo $row['id']; ?> from <?php echo htmlspecialchars($row['customer_name']); ?></h4>
                    <p style="font-size: 0.8rem; color: #666;">Status: <?php echo $row['status']; ?></p>
                </div>
                <div class="activity-time" style="font-size: 0.75rem; color: #999;">
                    <?php echo date('H:i', strtotime($row['created_at'])); ?>
                </div>
            </div>
            <?php 
                endwhile;
            else:
            ?>
            <p style="text-align: center; color: #666; padding: 2rem;">
                <i class="fas fa-magic"></i> No recent activity
            </p>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // Live Clock
        function updateClock() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('en-US', { 
                hour: '2-digit', 
                minute: '2-digit', 
                second: '2-digit',
                hour12: true 
            });
            document.getElementById('liveClock').textContent = timeString;
        }
        updateClock();
        setInterval(updateClock, 1000);
        
        // Toggle Chef Form
        function toggleChefForm() {
            const form = document.getElementById('chefForm');
            if (form.style.display === 'none' || form.style.display === '') {
                form.style.display = 'block';
                form.scrollIntoView({ behavior: 'smooth' });
            } else {
                form.style.display = 'none';
                // If we were editing, redirect to remove edit mode
                <?php if ($editChef): ?>
                    window.location.href = window.location.pathname;
                <?php endif; ?>
            }
        }
        
        // Show form if editing
        <?php if ($editChef): ?>
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('chefForm').style.display = 'block';
        });
        <?php endif; ?>
    </script>
</body>
</html>