<?php
session_start();
require_once("../includes/db.php");

// Check if user is logged in
if (!isset($_SESSION['customer_id'])) {
    header("Location: customer-login.html");
    exit();
}

$user_id = $_SESSION['customer_id'];
$success = "";
$error = "";

// Fetch user data
$stmt = $pdo->prepare("SELECT id, name, email, phone, avatar, points, member_since, notification_preferences, language_preference FROM customers WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    header("Location: customer-login.html");
    exit();
}

// Handle profile update
if (isset($_POST['update_profile'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);

    if (!empty($name) && !empty($email)) {
        // Check if email exists for another user
        $check = $pdo->prepare("SELECT id FROM customers WHERE email = ? AND id != ?");
        $check->execute([$email, $user_id]);
        
        if ($check->rowCount() > 0) {
            $error = "Email already exists for another account.";
        } else {
            $update = $pdo->prepare("UPDATE customers SET name = ?, email = ?, phone = ? WHERE id = ?");
            if ($update->execute([$name, $email, $phone, $user_id])) {
                $success = "Profile updated successfully!";
                $_SESSION['customer_name'] = $name;
                // Refresh user data
                $stmt->execute([$user_id]);
                $user = $stmt->fetch();
            } else {
                $error = "Failed to update profile.";
            }
        }
    } else {
        $error = "Name and email are required.";
    }
}

// Handle password change
if (isset($_POST['change_password'])) {
    $current = $_POST['current_password'];
    $new = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];

    // Get current password hash
    $pwd_stmt = $pdo->prepare("SELECT password FROM customers WHERE id = ?");
    $pwd_stmt->execute([$user_id]);
    $user_data = $pwd_stmt->fetch();

    if (password_verify($current, $user_data['password'])) {
        if ($new === $confirm) {
            if (strlen($new) >= 6) {
                $hashed = password_hash($new, PASSWORD_DEFAULT);
                $update = $pdo->prepare("UPDATE customers SET password = ? WHERE id = ?");
                if ($update->execute([$hashed, $user_id])) {
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

// Handle notification preferences
if (isset($_POST['update_notifications'])) {
    $email_notifications = isset($_POST['email_notifications']) ? 1 : 0;
    $sms_notifications = isset($_POST['sms_notifications']) ? 1 : 0;
    $promotional_emails = isset($_POST['promotional_emails']) ? 1 : 0;
    $order_updates = isset($_POST['order_updates']) ? 1 : 0;

    $preferences = json_encode([
        'email' => $email_notifications,
        'sms' => $sms_notifications,
        'promotional' => $promotional_emails,
        'orders' => $order_updates
    ]);

    $update = $pdo->prepare("UPDATE customers SET notification_preferences = ? WHERE id = ?");
    if ($update->execute([$preferences, $user_id])) {
        $success = "Notification preferences updated!";
        // Refresh user data
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
    } else {
        $error = "Failed to update preferences.";
    }
}

// Handle language preference
if (isset($_POST['update_language'])) {
    $language = $_POST['language'];
    $update = $pdo->prepare("UPDATE customers SET language_preference = ? WHERE id = ?");
    if ($update->execute([$language, $user_id])) {
        $success = "Language preference updated!";
        // Refresh user data
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
    } else {
        $error = "Failed to update language.";
    }
}

// Handle account deletion
if (isset($_POST['delete_account'])) {
    $confirm = $_POST['confirm_delete'];
    if ($confirm === 'DELETE') {
        // Delete user account
        $delete = $pdo->prepare("DELETE FROM customers WHERE id = ?");
        if ($delete->execute([$user_id])) {
            session_destroy();
            header("Location: customer-login.html?deleted=1");
            exit();
        } else {
            $error = "Failed to delete account.";
        }
    } else {
        $error = "Please type DELETE to confirm.";
    }
}

// Parse notification preferences
$prefs = json_decode($user['notification_preferences'] ?? '{}', true);
$email_notifications = $prefs['email'] ?? 1;
$sms_notifications = $prefs['sms'] ?? 0;
$promotional_emails = $prefs['promotional'] ?? 1;
$order_updates = $prefs['orders'] ?? 1;

// Format date
$member_since = date('F Y', strtotime($user['member_since'] ?? 'now'));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Chef D</title>
    <link rel="stylesheet" href="customer-dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700;800&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Settings Page Styles */
        .settings-container {
            max-width: 1000px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }

        .settings-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 2px solid #f0f0f0;
        }

        .settings-header h1 {
            font-size: 2rem;
            color: #333;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .settings-header h1 i {
            color: var(--primary-red);
        }

        .back-link {
            color: var(--primary-red);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .back-link:hover {
            background: #fff0f0;
        }

        /* Tabs */
        .settings-tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }

        .tab-btn {
            padding: 1rem 2rem;
            border: none;
            border-radius: 50px;
            background: #f8f8f8;
            color: #666;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .tab-btn:hover {
            background: #fff0f0;
            color: var(--primary-red);
        }

        .tab-btn.active {
            background: var(--primary-red);
            color: white;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Cards */
        .settings-card {
            background: #f8f9fa;
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .card-title {
            font-size: 1.2rem;
            color: #333;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .card-title i {
            color: var(--primary-red);
        }

        /* Forms */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.2rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #555;
            font-weight: 500;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 1rem;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary-red);
            box-shadow: 0 0 0 3px rgba(255,71,87,0.1);
        }

        .form-group input[readonly] {
            background: #f0f0f0;
            cursor: not-allowed;
        }

        /* Toggle Switches */
        .toggle-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            background: white;
            border-radius: 12px;
            margin-bottom: 0.8rem;
        }

        .toggle-info h4 {
            font-size: 1rem;
            color: #333;
            margin-bottom: 0.2rem;
        }

        .toggle-info p {
            font-size: 0.85rem;
            color: #666;
        }

        .switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
        }

        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 24px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 20px;
            width: 20px;
            left: 2px;
            bottom: 2px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked + .slider {
            background-color: var(--primary-red);
        }

        input:checked + .slider:before {
            transform: translateX(26px);
        }

        /* Buttons */
        .btn {
            padding: 1rem 2rem;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-red), #ff6b4a);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(255,71,87,0.3);
        }

        .btn-secondary {
            background: var(--primary-blue);
            color: white;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        /* Messages */
        .message {
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
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

        /* Danger Zone */
        .danger-zone {
            background: #fff0f0;
            border: 2px solid #ff4757;
            border-radius: 20px;
            padding: 2rem;
        }

        .danger-zone h3 {
            color: #dc3545;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .delete-input {
            width: 100%;
            padding: 1rem;
            border: 2px solid #ff4757;
            border-radius: 12px;
            margin: 1rem 0;
            font-size: 1rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .settings-container {
                padding: 1rem;
                margin: 1rem;
            }
            
            .settings-tabs {
                flex-direction: column;
            }
            
            .tab-btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar (copy from customer_dashboard.php) -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <i class="fas fa-hat-chef"></i>
                    <span>CHEF D</span>
                </div>
                <button class="sidebar-toggle" onclick="toggleSidebar()">
                    <i class="fas fa-bars"></i>
                </button>
            </div>

            <div class="user-profile-mini">
                <div class="user-avatar" id="userAvatar">
                    <i class="fas fa-user"></i>
                </div>
                <div class="user-info-mini">
                    <h4 id="sidebarUserName"><?php echo htmlspecialchars($user['name']); ?></h4>
                    <p><i class="fas fa-star"></i> <span id="sidebarPoints"><?php echo $user['points'] ?? 0; ?></span> points</p>
                </div>
            </div>

            <nav class="sidebar-nav">
                <a href="customer_dashboard.php" class="nav-item">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
                <a href="customer_reservations.php" class="nav-item">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Reservations</span>
                </a>
                <a href="customer_orders.php" class="nav-item">
                    <i class="fas fa-clock"></i>
                    <span>Order History</span>
                </a>
                <a href="customer_favorites.php" class="nav-item">
                    <i class="fas fa-heart"></i>
                    <span>Favorites</span>
                </a>
                <a href="customer_rewards.php" class="nav-item">
                    <i class="fas fa-gift"></i>
                    <span>Rewards</span>
                </a>
                <a href="customer_profile.php" class="nav-item">
                    <i class="fas fa-user"></i>
                    <span>Profile</span>
                </a>
                <a href="customer_settings.php" class="nav-item active">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
            </nav>

            <div class="sidebar-footer">
                <a href="index.html" class="nav-item">
                    <i class="fas fa-home"></i>
                    <span>Restaurant Home</span>
                </a>
                <a href="backend/logout.php" class="nav-item logout">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="settings-container">
                <div class="settings-header">
                    <h1>
                        <i class="fas fa-cog"></i>
                        Settings
                    </h1>
                    <a href="customer_dashboard.php" class="back-link">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
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

                <!-- Settings Tabs -->
                <div class="settings-tabs">
                    <button class="tab-btn active" onclick="openTab(event, 'profile')">
                        <i class="fas fa-user"></i> Profile Settings
                    </button>
                    <button class="tab-btn" onclick="openTab(event, 'security')">
                        <i class="fas fa-lock"></i> Security
                    </button>
                    <button class="tab-btn" onclick="openTab(event, 'notifications')">
                        <i class="fas fa-bell"></i> Notifications
                    </button>
                    <button class="tab-btn" onclick="openTab(event, 'preferences')">
                        <i class="fas fa-globe"></i> Preferences
                    </button>
                    <button class="tab-btn" onclick="openTab(event, 'danger')">
                        <i class="fas fa-exclamation-triangle"></i> Danger Zone
                    </button>
                </div>

                <!-- Profile Settings Tab -->
                <div id="profile" class="tab-content active">
                    <div class="settings-card">
                        <h3 class="card-title">
                            <i class="fas fa-edit"></i>
                            Edit Profile Information
                        </h3>
                        <form method="POST">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label>Full Name</label>
                                    <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Email Address</label>
                                    <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Phone Number</label>
                                    <input type="tel" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="+255 XXX XXX XXX">
                                </div>
                                <div class="form-group">
                                    <label>Member Since</label>
                                    <input type="text" value="<?php echo $member_since; ?>" readonly>
                                </div>
                            </div>
                            <button type="submit" name="update_profile" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Changes
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Security Tab -->
                <div id="security" class="tab-content">
                    <div class="settings-card">
                        <h3 class="card-title">
                            <i class="fas fa-key"></i>
                            Change Password
                        </h3>
                        <form method="POST">
                            <div class="form-group">
                                <label>Current Password</label>
                                <input type="password" name="current_password" required>
                            </div>
                            <div class="form-group">
                                <label>New Password</label>
                                <input type="password" name="new_password" id="new_password" required>
                                <small style="color: #666;">Minimum 6 characters</small>
                            </div>
                            <div class="form-group">
                                <label>Confirm New Password</label>
                                <input type="password" name="confirm_password" id="confirm_password" required>
                            </div>
                            <button type="submit" name="change_password" class="btn btn-primary">
                                <i class="fas fa-key"></i> Update Password
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Notifications Tab -->
                <div id="notifications" class="tab-content">
                    <div class="settings-card">
                        <h3 class="card-title">
                            <i class="fas fa-bell"></i>
                            Notification Preferences
                        </h3>
                        <form method="POST">
                            <div class="toggle-item">
                                <div class="toggle-info">
                                    <h4>Email Notifications</h4>
                                    <p>Receive updates via email</p>
                                </div>
                                <label class="switch">
                                    <input type="checkbox" name="email_notifications" <?php echo $email_notifications ? 'checked' : ''; ?>>
                                    <span class="slider"></span>
                                </label>
                            </div>

                            <div class="toggle-item">
                                <div class="toggle-info">
                                    <h4>SMS Notifications</h4>
                                    <p>Get text messages for order updates</p>
                                </div>
                                <label class="switch">
                                    <input type="checkbox" name="sms_notifications" <?php echo $sms_notifications ? 'checked' : ''; ?>>
                                    <span class="slider"></span>
                                </label>
                            </div>

                            <div class="toggle-item">
                                <div class="toggle-info">
                                    <h4>Promotional Emails</h4>
                                    <p>Receive offers and discounts</p>
                                </div>
                                <label class="switch">
                                    <input type="checkbox" name="promotional_emails" <?php echo $promotional_emails ? 'checked' : ''; ?>>
                                    <span class="slider"></span>
                                </label>
                            </div>

                            <div class="toggle-item">
                                <div class="toggle-info">
                                    <h4>Order Updates</h4>
                                    <p>Get notified about order status</p>
                                </div>
                                <label class="switch">
                                    <input type="checkbox" name="order_updates" <?php echo $order_updates ? 'checked' : ''; ?>>
                                    <span class="slider"></span>
                                </label>
                            </div>

                            <button type="submit" name="update_notifications" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Preferences
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Preferences Tab -->
                <div id="preferences" class="tab-content">
                    <div class="settings-card">
                        <h3 class="card-title">
                            <i class="fas fa-globe"></i>
                            Language & Region
                        </h3>
                        <form method="POST">
                            <div class="form-group">
                                <label>Language Preference</label>
                                <select name="language">
                                    <option value="en" <?php echo ($user['language_preference'] ?? 'en') == 'en' ? 'selected' : ''; ?>>English</option>
                                    <option value="sw" <?php echo ($user['language_preference'] ?? '') == 'sw' ? 'selected' : ''; ?>>Swahili / Kiswahili</option>
                                    <option value="fr" <?php echo ($user['language_preference'] ?? '') == 'fr' ? 'selected' : ''; ?>>French</option>
                                </select>
                            </div>
                            <button type="submit" name="update_language" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Preference
                            </button>
                        </form>
                    </div>

                    <div class="settings-card">
                        <h3 class="card-title">
                            <i class="fas fa-palette"></i>
                            Display Settings
                        </h3>
                        <p style="color: #666; margin-bottom: 1rem;">Coming soon: Theme preferences, dark mode, and more!</p>
                    </div>
                </div>

                <!-- Danger Zone Tab -->
                <div id="danger" class="tab-content">
                    <div class="danger-zone">
                        <h3>
                            <i class="fas fa-exclamation-triangle"></i>
                            Danger Zone
                        </h3>
                        <p style="margin-bottom: 1rem;">Once you delete your account, there is no going back. Please be certain.</p>
                        
                        <form method="POST" onsubmit="return confirm('Are you absolutely sure? This action cannot be undone!')">
                            <label>Type DELETE to confirm</label>
                            <input type="text" name="confirm_delete" class="delete-input" placeholder="DELETE" required>
                            <button type="submit" name="delete_account" class="btn btn-danger">
                                <i class="fas fa-trash"></i> Permanently Delete My Account
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Toggle sidebar
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('collapsed');
        }

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

        // Password match validation
        document.getElementById('confirm_password')?.addEventListener('keyup', function() {
            var password = document.getElementById('new_password').value;
            var confirm = this.value;
            
            if (password !== confirm) {
                this.style.borderColor = '#dc3545';
            } else {
                this.style.borderColor = '#28a745';
            }
        });
    </script>
</body>
</html>