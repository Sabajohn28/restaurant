<?php
session_start();
require_once "../includes/db.php";

// 🔐 Security check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== "manager") {
    $_SESSION['error'] = "Access denied. Manager privileges required.";
    header("Location: ../login.php");
    exit;
}

// Initialize message
$message = '';
$messageType = '';

// ============================================
// ✨ MAGICAL CRUD OPERATIONS
// ============================================

// 🍽️ ADD MENU ITEM
if (isset($_POST['add_item'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $price = floatval($_POST['price']);
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $spicy_level = intval($_POST['spicy_level'] ?? 0);
    $is_vegetarian = isset($_POST['is_vegetarian']) ? 1 : 0;
    $is_available = isset($_POST['is_available']) ? 1 : 0;
    $prep_time = intval($_POST['prep_time'] ?? 15);
    
    // 🖼️ Handle image upload
    $image_path = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $target_dir = "../uploads/menu/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $image_name = time() . '_' . basename($_FILES['image']['name']);
        $target_file = $target_dir . $image_name;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        
        // Check if image file is valid
        $check = getimagesize($_FILES['image']['tmp_name']);
        if ($check !== false) {
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                $image_path = 'uploads/menu/' . $image_name;
            }
        }
    }
    
    $sql = "INSERT INTO menu_items (name, description, price, category, image, spicy_level, is_vegetarian, is_available, prep_time) 
            VALUES ('$name', '$description', $price, '$category', '$image_path', $spicy_level, $is_vegetarian, $is_available, $prep_time)";
    
    if (mysqli_query($conn, $sql)) {
        $message = "✅ Menu item added successfully!";
        $messageType = "success";
    } else {
        $message = "❌ Error: " . mysqli_error($conn);
        $messageType = "error";
    }
}

// ✏️ UPDATE MENU ITEM
if (isset($_POST['update_item'])) {
    $id = intval($_POST['item_id']);
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $price = floatval($_POST['price']);
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $spicy_level = intval($_POST['spicy_level'] ?? 0);
    $is_vegetarian = isset($_POST['is_vegetarian']) ? 1 : 0;
    $is_available = isset($_POST['is_available']) ? 1 : 0;
    $prep_time = intval($_POST['prep_time'] ?? 15);
    
    // Check if new image uploaded
    $image_sql = "";
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $target_dir = "../uploads/menu/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $image_name = time() . '_' . basename($_FILES['image']['name']);
        $target_file = $target_dir . $image_name;
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
            $image_path = 'uploads/menu/' . $image_name;
            $image_sql = ", image = '$image_path'";
            
            // Delete old image
            $old = mysqli_query($conn, "SELECT image FROM menu_items WHERE id = $id");
            $old_image = mysqli_fetch_assoc($old)['image'];
            if ($old_image && file_exists("../" . $old_image)) {
                unlink("../" . $old_image);
            }
        }
    }
    
    $sql = "UPDATE menu_items SET 
            name = '$name',
            description = '$description',
            price = $price,
            category = '$category',
            spicy_level = $spicy_level,
            is_vegetarian = $is_vegetarian,
            is_available = $is_available,
            prep_time = $prep_time
            $image_sql
            WHERE id = $id";
    
    if (mysqli_query($conn, $sql)) {
        $message = "✅ Menu item updated successfully!";
        $messageType = "success";
    } else {
        $message = "❌ Error: " . mysqli_error($conn);
        $messageType = "error";
    }
}

// 🗑️ DELETE MENU ITEM
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    
    // Delete image file first
    $result = mysqli_query($conn, "SELECT image FROM menu_items WHERE id = $id");
    $item = mysqli_fetch_assoc($result);
    if ($item['image'] && file_exists("../" . $item['image'])) {
        unlink("../" . $item['image']);
    }
    
    if (mysqli_query($conn, "DELETE FROM menu_items WHERE id = $id")) {
        $message = "✅ Menu item deleted successfully!";
        $messageType = "success";
    } else {
        $message = "❌ Error: " . mysqli_error($conn);
        $messageType = "error";
    }
}

// 🔄 TOGGLE AVAILABILITY
if (isset($_GET['toggle'])) {
    $id = intval($_GET['toggle']);
    $current = mysqli_fetch_assoc(mysqli_query($conn, "SELECT is_available FROM menu_items WHERE id = $id"));
    $new_status = $current['is_available'] ? 0 : 1;
    mysqli_query($conn, "UPDATE menu_items SET is_available = $new_status WHERE id = $id");
    $message = $new_status ? "✅ Item is now available!" : "⚠️ Item marked as unavailable";
    $messageType = $new_status ? "success" : "warning";
}

// 📋 GET ALL MENU ITEMS
$items = mysqli_query($conn, "SELECT * FROM menu_items ORDER BY category, name");

// 🔍 GET ITEM FOR EDITING
$edit_item = null;
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $result = mysqli_query($conn, "SELECT * FROM menu_items WHERE id = $id");
    $edit_item = mysqli_fetch_assoc($result);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu Magic - Chef D Manager</title>
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
            --menu-gold: #ffb347;
            --menu-green: #27ae60;
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
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        /* Header */
        .header {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .header h1 {
            font-size: 2.5rem;
            color: var(--secondary);
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .header h1 i {
            color: var(--primary);
            animation: spin 10s linear infinite;
        }
        
        .header-actions {
            display: flex;
            gap: 1rem;
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
            font-size: 1rem;
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
        
        /* Message */
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
        
        .message.warning {
            background: #fff3e0;
            color: #f57c00;
            border: 1px solid #ffe0b2;
        }
        
        /* Form Section */
        .form-section {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .form-section h2 {
            font-size: 1.8rem;
            color: var(--secondary);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
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
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(255,71,87,0.1);
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .form-row {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
        }
        
        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
        }
        
        .checkbox-label input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }
        
        .image-preview {
            width: 100px;
            height: 100px;
            border: 2px dashed #e0e0e0;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-top: 0.5rem;
            overflow: hidden;
        }
        
        .image-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .image-preview i {
            font-size: 2rem;
            color: #ccc;
        }
        
        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 2rem;
        }
        
        /* Menu Grid */
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }
        
        .menu-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            position: relative;
        }
        
        .menu-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.15);
        }
        
        .menu-image {
            height: 200px;
            background: linear-gradient(135deg, var(--menu-gold), var(--primary));
            position: relative;
            overflow: hidden;
        }
        
        .menu-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .menu-image .placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 4rem;
            color: white;
        }
        
        .menu-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            padding: 0.3rem 0.8rem;
            border-radius: 50px;
            font-size: 0.7rem;
            font-weight: 600;
            z-index: 2;
        }
        
        .badge-veg {
            background: var(--menu-green);
            color: white;
        }
        
        .badge-spicy {
            background: var(--danger);
            color: white;
        }
        
        .badge-unavailable {
            background: #999;
            color: white;
        }
        
        .menu-content {
            padding: 1.5rem;
        }
        
        .menu-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 0.5rem;
        }
        
        .menu-header h3 {
            font-size: 1.2rem;
            color: var(--secondary);
        }
        
        .menu-category {
            background: #f0f0f0;
            padding: 0.2rem 1rem;
            border-radius: 50px;
            font-size: 0.7rem;
            color: #666;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .menu-description {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 1rem;
            line-height: 1.6;
        }
        
        .menu-details {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
            flex-wrap: wrap;
        }
        
        .detail-tag {
            background: #f8f9fa;
            padding: 0.3rem 0.8rem;
            border-radius: 50px;
            font-size: 0.8rem;
            color: #666;
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }
        
        .detail-tag i {
            color: var(--primary);
        }
        
        .menu-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #f0f0f0;
        }
        
        .menu-price {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
        }
        
        .menu-price small {
            font-size: 0.8rem;
            font-weight: 400;
            color: #666;
        }
        
        .menu-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .menu-actions a {
            width: 35px;
            height: 35px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            color: white;
            transition: all 0.3s ease;
        }
        
        .action-edit {
            background: var(--warning);
        }
        
        .action-toggle {
            background: var(--success);
        }
        
        .action-delete {
            background: var(--danger);
        }
        
        .menu-actions a:hover {
            transform: scale(1.1);
        }
        
        .availability-toggle {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
        }
        
        .availability-toggle input {
            width: 40px;
            height: 20px;
            cursor: pointer;
        }
        
        /* Stats Section */
        .stats-mini {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-mini-card {
            background: white;
            padding: 1rem;
            border-radius: 10px;
            text-align: center;
        }
        
        .stat-mini-number {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
        }
        
        .stat-mini-label {
            font-size: 0.8rem;
            color: #666;
        }
        
        /* Back Button */
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            background: rgba(255,255,255,0.2);
            border-radius: 50px;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }
        
        .back-link:hover {
            background: rgba(255,255,255,0.3);
            transform: translateX(-5px);
        }
        
        /* Animations */
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
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
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .menu-card {
            animation: fadeIn 0.5s ease;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            body { padding: 1rem; }
            .header h1 { font-size: 1.8rem; }
            .form-grid { grid-template-columns: 1fr; }
            .menu-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Back Link -->
        <a href="dashboard.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
        
        <!-- Header -->
        <div class="header">
            <h1>
                <i class="fas fa-utensils"></i>
                Menu Magic
                <span style="font-size: 1rem; background: var(--primary); color: white; padding: 0.3rem 1rem; border-radius: 50px;">
                    <i class="fas fa-hat-chef"></i> Chef's Special
                </span>
            </h1>
            <div class="header-actions">
                <button class="btn btn-primary" onclick="document.getElementById('addForm').scrollIntoView({behavior: 'smooth'})">
                    <i class="fas fa-plus"></i> Add New Dish
                </button>
            </div>
        </div>
        
        <!-- Quick Stats -->
        <div class="stats-mini">
            <?php
            $total_items = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM menu_items"))['total'];
            $available_items = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM menu_items WHERE is_available = 1"))['total'];
            $veg_items = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM menu_items WHERE is_vegetarian = 1"))['total'];
            ?>
            <div class="stat-mini-card">
                <div class="stat-mini-number"><?php echo $total_items; ?></div>
                <div class="stat-mini-label">Total Dishes</div>
            </div>
            <div class="stat-mini-card">
                <div class="stat-mini-number"><?php echo $available_items; ?></div>
                <div class="stat-mini-label">Available Now</div>
            </div>
            <div class="stat-mini-card">
                <div class="stat-mini-number"><?php echo $veg_items; ?></div>
                <div class="stat-mini-label">Vegetarian</div>
            </div>
        </div>
        
        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <i class="fas fa-<?php 
                    echo $messageType === 'success' ? 'check-circle' : 
                        ($messageType === 'warning' ? 'exclamation-triangle' : 'exclamation-circle'); 
                ?>"></i>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <!-- Add/Edit Form -->
        <div class="form-section" id="addForm">
            <h2>
                <i class="fas fa-<?php echo $edit_item ? 'edit' : 'plus-circle'; ?>"></i>
                <?php echo $edit_item ? 'Edit Dish' : 'Create New Dish'; ?>
            </h2>
            
            <form method="POST" enctype="multipart/form-data">
                <?php if ($edit_item): ?>
                    <input type="hidden" name="item_id" value="<?php echo $edit_item['id']; ?>">
                <?php endif; ?>
                
                <div class="form-grid">
                    <div>
                        <div class="form-group">
                            <label>🍽️ Dish Name *</label>
                            <input type="text" name="name" required 
                                   value="<?php echo $edit_item['name'] ?? ''; ?>"
                                   placeholder="e.g., Beef Wellington">
                        </div>
                        
                        <div class="form-group">
                            <label>📝 Description</label>
                            <textarea name="description" placeholder="Describe this delicious dish..."><?php echo $edit_item['description'] ?? ''; ?></textarea>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group" style="flex: 1;">
                                <label>💰 Price (TSh) *</label>
                                <input type="number" step="0.01" name="price" required 
                                       value="<?php echo $edit_item['price'] ?? ''; ?>"
                                       placeholder="25000">
                            </div>
                            
                            <div class="form-group" style="flex: 1;">
                                <label>⏱️ Prep Time (minutes)</label>
                                <input type="number" name="prep_time" value="<?php echo $edit_item['prep_time'] ?? '15'; ?>" min="0">
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <div class="form-group">
                            <label>📋 Category</label>
                            <select name="category">
                                <option value="appetizer" <?php echo ($edit_item['category'] ?? '') == 'appetizer' ? 'selected' : ''; ?>>🥗 Appetizer</option>
                                <option value="main" <?php echo ($edit_item['category'] ?? '') == 'main' ? 'selected' : ''; ?>>🍛 Main Course</option>
                                <option value="dessert" <?php echo ($edit_item['category'] ?? '') == 'dessert' ? 'selected' : ''; ?>>🍰 Dessert</option>
                                <option value="beverage" <?php echo ($edit_item['category'] ?? '') == 'beverage' ? 'selected' : ''; ?>>🥤 Beverage</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>🌶️ Spicy Level</label>
                            <select name="spicy_level">
                                <option value="0" <?php echo ($edit_item['spicy_level'] ?? 0) == 0 ? 'selected' : ''; ?>>😊 Not Spicy</option>
                                <option value="1" <?php echo ($edit_item['spicy_level'] ?? 0) == 1 ? 'selected' : ''; ?>>🌶️ Mild</option>
                                <option value="2" <?php echo ($edit_item['spicy_level'] ?? 0) == 2 ? 'selected' : ''; ?>>🌶️🌶️ Medium</option>
                                <option value="3" <?php echo ($edit_item['spicy_level'] ?? 0) == 3 ? 'selected' : ''; ?>>🌶️🌶️🌶️ Hot</option>
                            </select>
                        </div>
                        
                        <div class="checkbox-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="is_vegetarian" <?php echo ($edit_item['is_vegetarian'] ?? 0) ? 'checked' : ''; ?>>
                                🌱 Vegetarian
                            </label>
                            
                            <label class="checkbox-label">
                                <input type="checkbox" name="is_available" <?php echo ($edit_item['is_available'] ?? 1) ? 'checked' : ''; ?>>
                                ✅ Available Today
                            </label>
                        </div>
                        
                        <div class="form-group">
                            <label>🖼️ Dish Image</label>
                            <input type="file" name="image" accept="image/*" onchange="previewImage(this)">
                            <div class="image-preview" id="imagePreview">
                                <?php if ($edit_item && $edit_item['image']): ?>
                                    <img src="../<?php echo $edit_item['image']; ?>" alt="Preview">
                                <?php else: ?>
                                    <i class="fas fa-camera"></i>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="form-actions">
                    <?php if ($edit_item): ?>
                        <a href="manage_menu.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel Edit
                        </a>
                    <?php endif; ?>
                    <button type="submit" name="<?php echo $edit_item ? 'update_item' : 'add_item'; ?>" class="btn btn-primary">
                        <i class="fas fa-<?php echo $edit_item ? 'save' : 'plus'; ?>"></i>
                        <?php echo $edit_item ? 'Update Dish' : 'Add to Menu'; ?>
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Menu Grid -->
        <h2 style="color: white; margin: 2rem 0 1rem; display: flex; align-items: center; gap: 0.5rem;">
            <i class="fas fa-list"></i>
            Our Magical Menu
            <span style="font-size: 0.9rem; background: rgba(255,255,255,0.2); padding: 0.3rem 1rem; border-radius: 50px;">
                <?php echo mysqli_num_rows($items); ?> items
            </span>
        </h2>
        
        <div class="menu-grid">
            <?php if (mysqli_num_rows($items) > 0): ?>
                <?php while ($item = mysqli_fetch_assoc($items)): ?>
                    <div class="menu-card">
                        <div class="menu-image">
                            <?php if ($item['image'] && file_exists("../" . $item['image'])): ?>
                                <img src="../<?php echo $item['image']; ?>" alt="<?php echo $item['name']; ?>">
                            <?php else: ?>
                                <div class="placeholder">
                                    <?php 
                                    $icons = [
                                        'appetizer' => '🥗',
                                        'main' => '🍛',
                                        'dessert' => '🍰',
                                        'beverage' => '🥤'
                                    ];
                                    echo $icons[$item['category']] ?? '🍽️';
                                    ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!$item['is_available']): ?>
                                <span class="menu-badge badge-unavailable">
                                    <i class="fas fa-clock"></i> Unavailable
                                </span>
                            <?php elseif ($item['is_vegetarian']): ?>
                                <span class="menu-badge badge-veg">
                                    <i class="fas fa-leaf"></i> Veg
                                </span>
                            <?php endif; ?>
                            
                            <?php if ($item['spicy_level'] > 1): ?>
                                <span class="menu-badge badge-spicy" style="top: 50px;">
                                    <i class="fas fa-pepper-hot"></i> 
                                    <?php echo str_repeat('🌶️', $item['spicy_level']); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="menu-content">
                            <div class="menu-header">
                                <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                                <span class="menu-category"><?php echo $item['category']; ?></span>
                            </div>
                            
                            <p class="menu-description"><?php echo htmlspecialchars($item['description']); ?></p>
                            
                            <div class="menu-details">
                                <?php if ($item['prep_time']): ?>
                                    <span class="detail-tag">
                                        <i class="fas fa-clock"></i> <?php echo $item['prep_time']; ?> min
                                    </span>
                                <?php endif; ?>
                                
                                <?php if ($item['is_vegetarian']): ?>
                                    <span class="detail-tag">
                                        <i class="fas fa-leaf"></i> Vegetarian
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="menu-footer">
                                <div class="menu-price">
                                    TSh <?php echo number_format($item['price'], 0); ?>
                                    <small>/plate</small>
                                </div>
                                
                                <div class="menu-actions">
                                    <a href="?edit=<?php echo $item['id']; ?>" class="action-edit" title="Edit Item">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="?toggle=<?php echo $item['id']; ?>" class="action-toggle" title="<?php echo $item['is_available'] ? 'Mark Unavailable' : 'Mark Available'; ?>">
                                        <i class="fas fa-<?php echo $item['is_available'] ? 'ban' : 'check'; ?>"></i>
                                    </a>
                                    <a href="?delete=<?php echo $item['id']; ?>" class="action-delete" title="Delete Item"
                                       onclick="return confirm('Are you sure you want to delete <?php echo $item['name']; ?>?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="grid-column: 1/-1; text-align: center; padding: 4rem; background: white; border-radius: 20px;">
                    <i class="fas fa-utensils" style="font-size: 4rem; color: #ccc; margin-bottom: 1rem;"></i>
                    <h3 style="color: #666;">No menu items yet</h3>
                    <p style="color: #999; margin-bottom: 2rem;">Start adding delicious dishes to your menu!</p>
                    <button class="btn btn-primary" onclick="document.getElementById('addForm').scrollIntoView({behavior: 'smooth'})">
                        <i class="fas fa-plus"></i> Add Your First Dish
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // Image preview
        function previewImage(input) {
            const preview = document.getElementById('imagePreview');
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = `<img src="${e.target.result}" alt="Preview">`;
                };
                reader.readAsDataURL(input.files[0]);
            }
        }
        
        // Smooth scroll for edit mode
        <?php if ($edit_item): ?>
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('addForm').scrollIntoView({ behavior: 'smooth' });
        });
        <?php endif; ?>
    </script>
</body>
</html>