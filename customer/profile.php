<?php
session_start();
require_once __DIR__ . "/../includes/db.php";

// ---------------------
// CHECK LOGIN
// ---------------------
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success = "";
$error = "";

// ---------------------
// FETCH USER DATA
// ---------------------
$stmt = $conn->prepare("SELECT id, name, email, phone, role, avatar, created_at, last_login FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// ---------------------
// UPDATE PROFILE
// ---------------------
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Handle profile update
    if (isset($_POST['update_profile'])) {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);

        if (!empty($name) && !empty($email)) {
            
            // Check if email already exists for another user
            $check = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $check->bind_param("si", $email, $user_id);
            $check->execute();
            $check_result = $check->get_result();
            
            if ($check_result->num_rows > 0) {
                $error = "Email already exists for another user.";
            } else {
                $update = $conn->prepare("UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?");
                $update->bind_param("sssi", $name, $email, $phone, $user_id);

                if ($update->execute()) {
                    $success = "Profile updated successfully!";
                    $_SESSION['user_name'] = $name; // update session name
                    
                    // Refresh user data
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $user = $result->fetch_assoc();
                } else {
                    $error = "Something went wrong.";
                }
            }
        } else {
            $error = "Name and email are required.";
        }
    }
    
    // Handle password change
    elseif (isset($_POST['change_password'])) {
        $current = $_POST['current_password'];
        $new = $_POST['new_password'];
        $confirm = $_POST['confirm_password'];
        
        // Verify current password
        $pwd_check = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $pwd_check->bind_param("i", $user_id);
        $pwd_check->execute();
        $pwd_result = $pwd_check->get_result();
        $user_data = $pwd_result->fetch_assoc();
        
        if (password_verify($current, $user_data['password'])) {
            if ($new === $confirm) {
                if (strlen($new) >= 6) {
                    $hashed = password_hash($new, PASSWORD_DEFAULT);
                    $update = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $update->bind_param("si", $hashed, $user_id);
                    
                    if ($update->execute()) {
                        $success = "Password changed successfully!";
                    } else {
                        $error = "Failed to change password.";
                    }
                } else {
                    $error = "Password must be at least 6 characters.";
                }
            } else {
                $error = "New passwords do not match.";
            }
        } else {
            $error = "Current password is incorrect.";
        }
    }
    
    // Handle avatar upload
    elseif (isset($_POST['upload_avatar'])) {
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == 0) {
            $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $file_type = $_FILES['avatar']['type'];
            
            if (in_array($file_type, $allowed)) {
                $upload_dir = __DIR__ . "/../uploads/avatars/";
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $extension = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
                $filename = "avatar_" . $user_id . "_" . time() . "." . $extension;
                $target = $upload_dir . $filename;
                
                if (move_uploaded_file($_FILES['avatar']['tmp_name'], $target)) {
                    // Delete old avatar if exists
                    if (!empty($user['avatar']) && file_exists(__DIR__ . "/../" . $user['avatar'])) {
                        unlink(__DIR__ . "/../" . $user['avatar']);
                    }
                    
                    $avatar_path = "uploads/avatars/" . $filename;
                    $update = $conn->prepare("UPDATE users SET avatar = ? WHERE id = ?");
                    $update->bind_param("si", $avatar_path, $user_id);
                    
                    if ($update->execute()) {
                        $success = "Avatar uploaded successfully!";
                        // Refresh user data
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $user = $result->fetch_assoc();
                    } else {
                        $error = "Failed to update avatar in database.";
                    }
                } else {
                    $error = "Failed to upload avatar.";
                }
            } else {
                $error = "Only JPG, PNG, GIF, and WEBP images are allowed.";
            }
        } else {
            $error = "Please select an image to upload.";
        }
    }
}

// Format date nicely
function formatDate($date) {
    return date('F j, Y', strtotime($date));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Chef D</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #ff4757;
            --secondary: #2c3e50;
            --success: #2ecc71;
            --danger: #e74c3c;
            --dark: #2c3e50;
            --light: #f5f7fa;
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
        }
        
        .profile-wrapper {
            max-width: 1200px;
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
            margin-bottom: 2rem;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        
        .back-link:hover {
            background: rgba(255,255,255,0.3);
            transform: translateX(-5px);
        }
        
        /* Profile Container */
        .profile-container {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 2rem;
            background: white;
            border-radius: 30px;
            overflow: hidden;
            box-shadow: 0 20px 50px rgba(0,0,0,0.2);
            animation: slideUp 0.5s ease;
        }
        
        /* Sidebar */
        .profile-sidebar {
            background: linear-gradient(135deg, var(--secondary), #34495e);
            padding: 2rem;
            color: white;
            text-align: center;
        }
        
        .avatar-container {
            position: relative;
            width: 150px;
            height: 150px;
            margin: 0 auto 1.5rem;
        }
        
        .avatar {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid rgba(255,255,255,0.3);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }
        
        .avatar-placeholder {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), #ff6b4a);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 4rem;
            color: white;
            border: 4px solid rgba(255,255,255,0.3);
        }
        
        .avatar-upload-btn {
            position: absolute;
            bottom: 5px;
            right: 5px;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid white;
        }
        
        .avatar-upload-btn:hover {
            transform: scale(1.1);
            background: #ff6b4a;
        }
        
        .avatar-upload-btn i {
            font-size: 1.2rem;
        }
        
        #avatarInput {
            display: none;
        }
        
        .profile-name {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .profile-role {
            display: inline-block;
            padding: 0.3rem 1rem;
            border-radius: 50px;
            background: rgba(255,255,255,0.2);
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }
        
        .profile-meta {
            background: rgba(0,0,0,0.2);
            border-radius: 15px;
            padding: 1rem;
            margin-top: 2rem;
        }
        
        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .meta-item:last-child {
            border-bottom: none;
        }
        
        .meta-item i {
            width: 20px;
            color: var(--primary);
        }
        
        /* Main Content */
        .profile-main {
            padding: 2rem;
        }
        
        .profile-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .profile-header h2 {
            font-size: 2rem;
            color: var(--secondary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .profile-header h2 i {
            color: var(--primary);
        }
        
        .logout-btn {
            background: var(--danger);
            color: white;
            padding: 0.8rem 1.5rem;
            border-radius: 10px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        
        .logout-btn:hover {
            background: #c0392b;
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(231, 76, 60, 0.3);
        }
        
        /* Messages */
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
        
        /* Tabs */
        .profile-tabs {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 0.5rem;
        }
        
        .tab-btn {
            background: none;
            border: none;
            padding: 0.8rem 1.5rem;
            font-size: 1rem;
            font-weight: 600;
            color: #666;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .tab-btn:hover {
            color: var(--primary);
        }
        
        .tab-btn.active {
            color: var(--primary);
        }
        
        .tab-btn.active::after {
            content: '';
            position: absolute;
            bottom: -0.7rem;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--primary);
            border-radius: 3px 3px 0 0;
        }
        
        /* Tab Content */
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
            animation: fadeIn 0.5s ease;
        }
        
        /* Forms */
        .form-group {
            margin-bottom: 1.5rem;
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
            border-radius: 10px;
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
            background: #f5f5f5;
            cursor: not-allowed;
        }
        
        .btn {
            padding: 1rem 2rem;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary), #ff6b4a);
            color: white;
            width: 100%;
        }
        
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(255,71,87,0.3);
        }
        
        .btn-secondary {
            background: var(--secondary);
            color: white;
        }
        
        /* Password Requirements */
        .password-requirements {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 10px;
            margin: 1rem 0;
            font-size: 0.9rem;
        }
        
        .requirements-list {
            list-style: none;
            margin-top: 0.5rem;
        }
        
        .requirements-list li {
            padding: 0.3rem 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .requirements-list li i {
            width: 20px;
        }
        
        .req-valid {
            color: var(--success);
        }
        
        .req-invalid {
            color: var(--danger);
        }
        
        /* Info Cards */
        .info-card {
            background: linear-gradient(135deg, #f8f9fa, #ffffff);
            border-radius: 15px;
            padding: 1.5rem;
            margin-top: 2rem;
        }
        
        .info-card h3 {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--secondary);
            margin-bottom: 1rem;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }
        
        .info-item {
            padding: 1rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .info-label {
            font-size: 0.8rem;
            color: #666;
            text-transform: uppercase;
            margin-bottom: 0.3rem;
        }
        
        .info-value {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--secondary);
        }
        
        /* Recent Activity */
        .activity-list {
            margin-top: 1rem;
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
        
        /* Responsive */
        @media (max-width: 768px) {
            .profile-container {
                grid-template-columns: 1fr;
            }
            
            .profile-sidebar {
                padding: 1.5rem;
            }
            
            body {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="profile-wrapper">
        <a href="javascript:history.back()" class="back-link">
            <i class="fas fa-arrow-left"></i> Back
        </a>
        
        <div class="profile-container">
            <!-- Sidebar -->
            <div class="profile-sidebar">
                <div class="avatar-container">
                    <?php if (!empty($user['avatar']) && file_exists(__DIR__ . "/../" . $user['avatar'])): ?>
                        <img src="../<?php echo $user['avatar']; ?>" alt="Avatar" class="avatar">
                    <?php else: ?>
                        <div class="avatar-placeholder">
                            <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" enctype="multipart/form-data" id="avatarForm">
                        <label for="avatarInput" class="avatar-upload-btn" title="Upload Avatar">
                            <i class="fas fa-camera"></i>
                        </label>
                        <input type="file" id="avatarInput" name="avatar" accept="image/*" onchange="document.getElementById('avatarForm').submit()">
                        <input type="hidden" name="upload_avatar" value="1">
                    </form>
                </div>
                
                <div class="profile-name"><?php echo htmlspecialchars($user['name']); ?></div>
                <div class="profile-role">
                    <i class="fas fa-<?php 
                        echo $user['role'] == 'manager' ? 'crown' : 
                            ($user['role'] == 'chef' ? 'hat-chef' : 'user'); 
                    ?>"></i>
                    <?php echo ucfirst($user['role']); ?>
                </div>
                
                <div class="profile-meta">
                    <div class="meta-item">
                        <i class="fas fa-envelope"></i>
                        <span><?php echo htmlspecialchars($user['email']); ?></span>
                    </div>
                    <?php if (!empty($user['phone'])): ?>
                    <div class="meta-item">
                        <i class="fas fa-phone"></i>
                        <span><?php echo htmlspecialchars($user['phone']); ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="meta-item">
                        <i class="fas fa-calendar"></i>
                        <span>Joined <?php echo formatDate($user['created_at']); ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="profile-main">
                <div class="profile-header">
                    <h2>
                        <i class="fas fa-user-circle"></i>
                        My Profile
                    </h2>
                    <a href="../logout.php" class="logout-btn" onclick="return confirm('Are you sure you want to logout?')">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
                
                <?php if ($success): ?>
                    <div class="message success">
                        <i class="fas fa-check-circle"></i>
                        <?php echo $success; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="message error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <!-- Tabs -->
                <div class="profile-tabs">
                    <button class="tab-btn active" onclick="openTab(event, 'edit-profile')">
                        <i class="fas fa-edit"></i> Edit Profile
                    </button>
                    <button class="tab-btn" onclick="openTab(event, 'change-password')">
                        <i class="fas fa-key"></i> Change Password
                    </button>
                    <button class="tab-btn" onclick="openTab(event, 'account-info')">
                        <i class="fas fa-info-circle"></i> Account Info
                    </button>
                </div>
                
                <!-- Edit Profile Tab -->
                <div id="edit-profile" class="tab-content active">
                    <form method="POST">
                        <div class="form-group">
                            <label><i class="fas fa-user"></i> Full Name</label>
                            <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-envelope"></i> Email Address</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-phone"></i> Phone Number</label>
                            <input type="tel" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="+255 XXX XXX XXX">
                        </div>
                        
                        <button type="submit" name="update_profile" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                    </form>
                </div>
                
                <!-- Change Password Tab -->
                <div id="change-password" class="tab-content">
                    <form method="POST">
                        <div class="form-group">
                            <label><i class="fas fa-lock"></i> Current Password</label>
                            <input type="password" name="current_password" required>
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-key"></i> New Password</label>
                            <input type="password" name="new_password" id="new_password" required onkeyup="checkPasswordStrength()">
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-check-circle"></i> Confirm New Password</label>
                            <input type="password" name="confirm_password" id="confirm_password" required onkeyup="checkPasswordMatch()">
                        </div>
                        
                        <!-- Password Requirements -->
                        <div class="password-requirements">
                            <strong>Password Requirements:</strong>
                            <ul class="requirements-list" id="passwordRequirements">
                                <li id="req-length">
                                    <i class="fas fa-times req-invalid"></i> At least 6 characters
                                </li>
                                <li id="req-match">
                                    <i class="fas fa-times req-invalid"></i> Passwords match
                                </li>
                            </ul>
                        </div>
                        
                        <button type="submit" name="change_password" class="btn btn-primary" id="passwordSubmit" disabled>
                            <i class="fas fa-key"></i> Change Password
                        </button>
                    </form>
                </div>
                
                <!-- Account Info Tab -->
                <div id="account-info" class="tab-content">
                    <div class="info-card">
                        <h3><i class="fas fa-id-card"></i> Account Details</h3>
                        <div class="info-grid">
                            <div class="info-item">
                                <div class="info-label">User ID</div>
                                <div class="info-value">#<?php echo $user['id']; ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Role</div>
                                <div class="info-value"><?php echo ucfirst($user['role']); ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Member Since</div>
                                <div class="info-value"><?php echo formatDate($user['created_at']); ?></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Recent Activity -->
                    <div class="info-card">
                        <h3><i class="fas fa-history"></i> Recent Activity</h3>
                        <div class="activity-list">
                            <?php
                            // Fetch recent orders
                            $orders = $conn->prepare("SELECT id, total_amount, created_at FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 3");
                            $orders->bind_param("i", $user_id);
                            $orders->execute();
                            $orders_result = $orders->get_result();
                            
                            if ($orders_result->num_rows > 0):
                                while ($order = $orders_result->fetch_assoc()):
                            ?>
                            <div class="activity-item">
                                <div class="activity-icon">
                                    <i class="fas fa-shopping-bag"></i>
                                </div>
                                <div>
                                    <strong>Order #<?php echo $order['id']; ?></strong>
                                    <p style="font-size: 0.85rem; color: #666;">
                                        TSh <?php echo number_format($order['total_amount'], 0); ?> • 
                                        <?php echo date('M j, Y', strtotime($order['created_at'])); ?>
                                    </p>
                                </div>
                            </div>
                            <?php 
                                endwhile;
                            else:
                            ?>
                            <p style="color: #666; text-align: center; padding: 1rem;">
                                <i class="fas fa-info-circle"></i> No recent orders
                            </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Tab functionality
        function openTab(evt, tabName) {
            var i, tabcontent, tablinks;
            
            tabcontent = document.getElementsByClassName("tab-content");
            for (i = 0; i < tabcontent.length; i++) {
                tabcontent[i].classList.remove("active");
            }
            
            tablinks = document.getElementsByClassName("tab-btn");
            for (i = 0; i < tablinks.length; i++) {
                tablinks[i].classList.remove("active");
            }
            
            document.getElementById(tabName).classList.add("active");
            evt.currentTarget.classList.add("active");
        }
        
        // Password strength checker
        function checkPasswordStrength() {
            const password = document.getElementById('new_password').value;
            const lengthReq = document.getElementById('req-length');
            const lengthIcon = lengthReq.querySelector('i');
            
            if (password.length >= 6) {
                lengthIcon.className = 'fas fa-check req-valid';
            } else {
                lengthIcon.className = 'fas fa-times req-invalid';
            }
            
            checkPasswordMatch();
        }
        
        // Password match checker
        function checkPasswordMatch() {
            const password = document.getElementById('new_password').value;
            const confirm = document.getElementById('confirm_password').value;
            const matchReq = document.getElementById('req-match');
            const matchIcon = matchReq.querySelector('i');
            const submitBtn = document.getElementById('passwordSubmit');
            
            if (password === confirm && password.length > 0) {
                matchIcon.className = 'fas fa-check req-valid';
            } else {
                matchIcon.className = 'fas fa-times req-invalid';
            }
            
            // Enable submit only if all requirements met
            if (password.length >= 6 && password === confirm && password.length > 0) {
                submitBtn.disabled = false;
            } else {
                submitBtn.disabled = true;
            }
        }
        
        // Auto-submit avatar form when file selected
        document.getElementById('avatarInput').addEventListener('change', function() {
            if (this.files && this.files[0]) {
                document.getElementById('avatarForm').submit();
            }
        });
    </script>
</body>
</html>