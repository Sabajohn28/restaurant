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

/* ADD TO CART */
if (isset($_GET['add'])) {

    $id = mysqli_real_escape_string($conn, $_GET['add']);

    $result = mysqli_query($conn, "SELECT * FROM menu_items WHERE id=$id AND status='available'");
    $item = mysqli_fetch_assoc($result);

    if ($item) {

        // Check if item already in cart
        if (isset($_SESSION['cart'][$id])) {
            $_SESSION['cart'][$id]['quantity']++;
            $message = "Quantity updated in cart!";
        } else {
            $_SESSION['cart'][$id] = [
                "name" => $item['name'],
                "price" => $item['price'],
                "quantity" => 1
            ];
            $message = "Item added to cart!";
        }
        $message_type = "success";
    }
}

/* SEARCH AND FILTER */
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'name';

// Build query
$query = "SELECT * FROM menu_items WHERE status='available'";

if (!empty($search)) {
    $query .= " AND (name LIKE '%$search%' OR description LIKE '%$search%')";
}

// Add sorting
switch($sort) {
    case 'price_low':
        $query .= " ORDER BY price ASC";
        break;
    case 'price_high':
        $query .= " ORDER BY price DESC";
        break;
    case 'name':
    default:
        $query .= " ORDER BY name ASC";
}

$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restaurant Menu - FoodieHub</title>
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

        /* Search and Filter Bar */
        .filter-bar {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .search-form {
            display: grid;
            grid-template-columns: 1fr auto auto;
            gap: 15px;
            align-items: center;
        }

        .search-input {
            position: relative;
        }

        .search-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
        }

        .search-field {
            width: 100%;
            padding: 12px 15px 12px 45px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s;
        }

        .search-field:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.1);
        }

        .filter-select {
            padding: 12px 25px;
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

        .search-btn {
            padding: 12px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .search-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }

        /* Menu Grid */
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .menu-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            animation: fadeIn 0.5s ease-out;
        }

        .menu-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(102, 126, 234, 0.2);
        }

        .menu-image {
            height: 160px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 60px;
        }

        .menu-content {
            padding: 20px;
        }

        .menu-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .menu-name {
            font-size: 18px;
            font-weight: 600;
            color: #333;
        }

        .menu-price {
            font-size: 20px;
            font-weight: 700;
            color: #28a745;
        }

        .menu-description {
            color: #666;
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 20px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .menu-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .add-to-cart-btn {
            flex: 1;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
        }

        .add-to-cart-btn:hover {
            transform: scale(1.02);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }

        /* Popular Tags */
        .popular-tag {
            background: #ffd700;
            color: #333;
            padding: 3px 10px;
            border-radius: 50px;
            font-size: 11px;
            font-weight: 600;
            margin-left: 10px;
        }

        /* No Results */
        .no-results {
            text-align: center;
            padding: 60px;
            background: white;
            border-radius: 20px;
            grid-column: 1 / -1;
        }

        .no-results-icon {
            font-size: 80px;
            color: #ddd;
            margin-bottom: 20px;
        }

        .no-results h3 {
            color: #333;
            margin-bottom: 10px;
        }

        .no-results p {
            color: #666;
            margin-bottom: 20px;
        }

        .reset-btn {
            display: inline-block;
            padding: 12px 30px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .reset-btn:hover {
            background: #5a67d8;
            transform: translateY(-2px);
        }

        /* Floating Cart Button for Mobile */
        .floating-cart {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: none;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            cursor: pointer;
            transition: all 0.3s;
            z-index: 999;
            text-decoration: none;
        }

        .floating-cart:hover {
            transform: scale(1.1);
        }

        .floating-cart .badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #ff4444;
            color: white;
            width: 25px;
            height: 25px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 600;
        }

        @media (max-width: 768px) {
            .search-form {
                grid-template-columns: 1fr;
            }
            
            .floating-cart {
                display: flex;
            }
            
            .nav-container {
                flex-direction: column;
                gap: 15px;
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
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="logo">
                🍽️ FoodieHub
                <span>Menu</span>
            </div>
            
            <div class="nav-links">
                <a href="dashboard.php" class="nav-link">
                    <span>🏠</span> Dashboard
                </a>
                <a href="menu.php" class="nav-link active">
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
                📋 Our Delicious Menu
                <span><?php echo mysqli_num_rows($result); ?> items</span>
            </h1>
        </div>

        <!-- Message Alert -->
        <?php if ($message): ?>
            <div class="message-alert <?php echo $message_type; ?>">
                <span><?php echo $message_type === 'success' ? '✅' : '⚠️'; ?></span>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- Search and Filter Bar -->
        <div class="filter-bar">
            <form method="GET" action="" class="search-form">
                <div class="search-input">
                    <span class="search-icon">🔍</span>
                    <input 
                        type="text" 
                        name="search" 
                        class="search-field" 
                        placeholder="Search for dishes..."
                        value="<?php echo htmlspecialchars($search); ?>"
                    >
                </div>
                
                <select name="sort" class="filter-select">
                    <option value="name" <?php echo $sort == 'name' ? 'selected' : ''; ?>>Sort by Name</option>
                    <option value="price_low" <?php echo $sort == 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                    <option value="price_high" <?php echo $sort == 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                </select>
                
                <button type="submit" class="search-btn">Apply Filters</button>
            </form>
        </div>

        <!-- Menu Grid -->
        <?php if (mysqli_num_rows($result) > 0): ?>
            <div class="menu-grid">
                <?php 
                $popular_items = [1, 3, 5]; // Example popular item IDs
                $icons = ['🍕', '🍔', '🥗', '🍝', '🍣', '🥘', '🍛', '🍜', '🍲', '🥪', '🍱', '🥟', '🍤', '🍨', '🍰'];
                
                while ($row = mysqli_fetch_assoc($result)): 
                    $is_popular = in_array($row['id'], $popular_items);
                    $icon = $icons[$row['id'] % count($icons)];
                ?>
                    <div class="menu-card">
                        <div class="menu-image">
                            <?php echo $icon; ?>
                        </div>
                        
                        <div class="menu-content">
                            <div class="menu-header">
                                <span class="menu-name">
                                    <?php echo htmlspecialchars($row['name']); ?>
                                    <?php if ($is_popular): ?>
                                        <span class="popular-tag">🔥 Popular</span>
                                    <?php endif; ?>
                                </span>
                                <span class="menu-price">$<?php echo number_format($row['price'], 2); ?></span>
                            </div>
                            
                            <p class="menu-description">
                                <?php echo htmlspecialchars($row['description'] ?? 'Delicious dish prepared with fresh ingredients.'); ?>
                            </p>
                            
                            <div class="menu-footer">
                                <a href="?add=<?php echo $row['id']; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?><?php echo !empty($sort) ? '&sort='.urlencode($sort) : ''; ?>" 
                                   class="add-to-cart-btn"
                                   onclick="showAddToCartAnimation(this)">
                                    <span>🛒</span>
                                    Add to Cart
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <!-- No Results -->
            <div class="no-results">
                <div class="no-results-icon">😕</div>
                <h3>No items found</h3>
                <p>We couldn't find any items matching your search.</p>
                <a href="menu.php" class="reset-btn">Reset Filters</a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Floating Cart Button for Mobile -->
    <a href="cart.php" class="floating-cart">
        🛒
        <?php if ($cart_count > 0): ?>
            <span class="badge"><?php echo $cart_count; ?></span>
        <?php endif; ?>
    </a>

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

        // Show add to cart animation
        function showAddToCartAnimation(button) {
            const originalText = button.innerHTML;
            button.innerHTML = '✅ Added!';
            button.style.background = '#28a745';
            
            setTimeout(() => {
                button.innerHTML = originalText;
                button.style.background = 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)';
            }, 1500);
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

        // Live search with debounce
        let searchTimeout;
        document.querySelector('.search-field').addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                document.querySelector('.search-form').submit();
            }, 500);
        });

        // Smooth scroll to top when filter changes
        document.querySelectorAll('.filter-select').forEach(element => {
            element.addEventListener('change', function() {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        });
    </script>
</body>
</html>