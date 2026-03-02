<?php
session_start();
include "includes/db.php";

/* ====================================
   AUTO REDIRECT IF ALREADY LOGGED IN
==================================== */
if(isset($_SESSION['user_id']) && isset($_SESSION['role'])){
    header("Location: ".$_SESSION['role']."/dashboard.php");
    exit();
}

$error_message = "";

/* ====================================
   LOGIN PROCESS
==================================== */
if($_SERVER["REQUEST_METHOD"] == "POST"){

    $email = mysqli_real_escape_string($conn,$_POST['email']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']);

    $sql = "SELECT * FROM users WHERE email='$email'";
    $result = mysqli_query($conn,$sql);

    if(mysqli_num_rows($result) > 0){

        $user = mysqli_fetch_assoc($result);

        if(password_verify($password,$user['password'])){

            /* SESSION VARIABLES */
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];

            /* REMEMBER ME COOKIE */
            if($remember){

                $token = bin2hex(random_bytes(32));
                $expiry = time() + (30*24*60*60);

                setcookie("remember_token",$token,$expiry,"/");
                setcookie("user_email",$email,$expiry,"/");

                // You can also store token in DB (recommended for production)
            }

            /* REDIRECT TO DASHBOARD */
            $redirect_url = $user['role']."/dashboard.php";

            echo "<script>
                    window.location='$redirect_url';
                  </script>";
            exit();

        }else{
            $error_message = "Invalid password!";
        }

    }else{
        $error_message = "Email not found!";
    }
}

/* ====================================
   AUTO LOGIN FROM COOKIE
==================================== */
if(!isset($_SESSION['user_id']) &&
   isset($_COOKIE['user_email']) &&
   isset($_COOKIE['remember_token'])){

    $email = mysqli_real_escape_string($conn,$_COOKIE['user_email']);

    $sql = "SELECT * FROM users WHERE email='$email'";
    $result = mysqli_query($conn,$sql);

    if(mysqli_num_rows($result)>0){

        $user = mysqli_fetch_assoc($result);

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];

        echo "<script>
                window.location='".$user['role']."/dashboard.php';
              </script>";
        exit();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login - Restaurant Management System</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
            overflow-x: hidden;
        }

        /* Animated Background Elements */
        .bg-bubble {
            position: absolute;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 50%;
            animation: float 20s infinite ease-in-out;
        }

        .bg-bubble-1 {
            width: 300px;
            height: 300px;
            top: -150px;
            left: -150px;
            animation-delay: 0s;
        }

        .bg-bubble-2 {
            width: 400px;
            height: 400px;
            bottom: -200px;
            right: -200px;
            animation-delay: 5s;
        }

        .bg-bubble-3 {
            width: 200px;
            height: 200px;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            animation-delay: 10s;
        }

        @keyframes float {
            0%, 100% {
                transform: translate(0, 0) scale(1);
            }
            33% {
                transform: translate(30px, -30px) scale(1.1);
            }
            66% {
                transform: translate(-20px, 20px) scale(0.9);
            }
        }

        /* Main Container */
        .login-container {
            width: 100%;
            max-width: 1000px;
            background: white;
            border-radius: 30px;
            box-shadow: 0 50px 100px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            display: grid;
            grid-template-columns: 1fr 1fr;
            position: relative;
            z-index: 10;
            animation: slideUp 0.8s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Left Side - Branding */
        .brand-side {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 60px 40px;
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .brand-side::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" fill="rgba(255,255,255,0.05)"><path d="M20 50 Q 40 30, 60 50 T 100 50" stroke="white" stroke-width="2" fill="none"/><circle cx="50" cy="50" r="5" fill="white"/></svg>') repeat;
            opacity: 0.3;
            animation: slide 20s linear infinite;
        }

        @keyframes slide {
            from { transform: translateX(0); }
            to { transform: translateX(100px); }
        }

        .brand-icon {
            font-size: 80px;
            margin-bottom: 30px;
            animation: bounce 2s infinite ease-in-out;
            display: inline-block;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        .brand-title {
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 15px;
            line-height: 1.2;
        }

        .brand-subtitle {
            font-size: 16px;
            opacity: 0.9;
            margin-bottom: 40px;
            line-height: 1.6;
        }

        .feature-list {
            list-style: none;
            margin-top: 20px;
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
            animation: fadeInLeft 0.5s ease-out forwards;
            opacity: 0;
        }

        .feature-item:nth-child(1) { animation-delay: 0.2s; }
        .feature-item:nth-child(2) { animation-delay: 0.4s; }
        .feature-item:nth-child(3) { animation-delay: 0.6s; }

        @keyframes fadeInLeft {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .feature-icon {
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        .feature-text {
            font-size: 15px;
            font-weight: 500;
        }

        /* Right Side - Login Form */
        .form-side {
            padding: 60px 50px;
            background: white;
        }

        .form-header {
            margin-bottom: 40px;
        }

        .form-header h2 {
            font-size: 32px;
            color: #333;
            margin-bottom: 10px;
            font-weight: 600;
        }

        .form-header p {
            color: #666;
            font-size: 15px;
        }

        .form-header p a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
        }

        .form-header p a:hover {
            color: #764ba2;
        }

        /* Error Message */
        .error-message {
            background: #fee;
            color: #c33;
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            border-left: 4px solid #c33;
            animation: shake 0.5s ease-in-out;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            20% { transform: translateX(-10px); }
            40% { transform: translateX(10px); }
            60% { transform: translateX(-5px); }
            80% { transform: translateX(5px); }
        }

        .form-group {
            margin-bottom: 25px;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
            font-size: 18px;
            transition: color 0.3s;
        }

        .form-control {
            width: 100%;
            padding: 15px 15px 15px 50px;
            border: 2px solid #e0e0e0;
            border-radius: 15px;
            font-size: 15px;
            transition: all 0.3s ease;
            background: #f8f9fa;
            font-family: 'Poppins', sans-serif;
        }

        .form-control:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.1);
        }

        .form-control:focus + .input-icon {
            color: #667eea;
        }

        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #999;
            font-size: 18px;
            transition: color 0.3s;
        }

        .password-toggle:hover {
            color: #667eea;
        }

        .remember-forgot {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #666;
            font-size: 14px;
            cursor: pointer;
        }

        .remember-me input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: #667eea;
        }

        .forgot-link {
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: color 0.3s;
        }

        .forgot-link:hover {
            color: #764ba2;
            text-decoration: underline;
        }

        .login-btn {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 15px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            font-family: 'Poppins', sans-serif;
        }

        .login-btn::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }

        .login-btn:hover::before {
            width: 300px;
            height: 300px;
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }

        .login-btn:active {
            transform: translateY(0);
        }

        .demo-credentials {
            margin-top: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 15px;
            text-align: center;
        }

        .demo-credentials p {
            color: #666;
            font-size: 13px;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .credential-badges {
            display: flex;
            gap: 10px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .credential-badge {
            padding: 8px 16px;
            background: white;
            border-radius: 50px;
            font-size: 12px;
            color: #667eea;
            border: 1px solid #667eea;
            cursor: pointer;
            transition: all 0.3s;
        }

        .credential-badge:hover {
            background: #667eea;
            color: white;
            transform: scale(1.05);
        }

        .credential-badge.chef {
            border-color: #28a745;
            color: #28a745;
        }

        .credential-badge.chef:hover {
            background: #28a745;
            color: white;
        }

        .credential-badge.manager {
            border-color: #dc3545;
            color: #dc3545;
        }

        .credential-badge.manager:hover {
            background: #dc3545;
            color: white;
        }

        /* Loading Overlay */
        #overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.95);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            backdrop-filter: blur(5px);
        }

        .loading-spinner {
            text-align: center;
        }

        .spinner {
            width: 60px;
            height: 60px;
            border: 5px solid #f3f3f3;
            border-top: 5px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }

        .loading-text {
            color: #333;
            font-size: 18px;
            font-weight: 600;
        }

        .loading-subtext {
            color: #666;
            font-size: 14px;
            margin-top: 10px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .login-container {
                grid-template-columns: 1fr;
                max-width: 450px;
            }
            
            .brand-side {
                display: none;
            }
            
            .form-side {
                padding: 40px 30px;
            }
        }

        /* Registration Link */
        .register-link {
            text-align: center;
            margin-top: 20px;
            color: #666;
            font-size: 14px;
        }

        .register-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
        }

        .register-link a:hover {
            color: #764ba2;
            text-decoration: underline;
        }
        .brand-icon img {
    width: 50px;      /* adjust size */
    height: 50px;     /* MUST be equal to width */
    border-radius: 50%;
    object-fit: cover;   /* prevents stretching */
}
    </style>
</head>
<body>

<!-- Animated Background Bubbles -->
<div class="bg-bubble bg-bubble-1"></div>
<div class="bg-bubble bg-bubble-2"></div>
<div class="bg-bubble bg-bubble-3"></div>

<!-- Loading Overlay -->
<div id="overlay">
    <div class="loading-spinner">
        <div class="spinner"></div>
        <div class="loading-text">Signing in...</div>
        <div class="loading-subtext">Please wait while we redirect you</div>
    </div>
</div>

<div class="login-container">
    <!-- Left Side - Branding -->
    <div class="brand-side">
        <div class="brand-icon">
         <img src="images/CHEF D RESTAURANT LOGO.jpg" alt="Logo">
        </div>
        <h1 class="brand-title">Welcome Back!</h1>
        <p class="brand-subtitle">Sign in to access your restaurant management dashboard and continue managing your kitchen efficiently.</p>
        
        <ul class="feature-list">
            <li class="feature-item">
                <span class="feature-icon">📊</span>
                <span class="feature-text">Real-time order tracking</span>
            </li>
            <li class="feature-item">
                <span class="feature-icon">👨‍🍳</span>
                <span class="feature-text">Chef & kitchen management</span>
            </li>
            <li class="feature-item">
                <span class="feature-icon">📈</span>
                <span class="feature-text">Analytics & insights</span>
            </li>
        </ul>
        
        <div style="margin-top: 40px; font-size: 14px; opacity: 0.8;">
            © 2026 Chef D Restaurant Management System
        </div>
    </div>

    <!-- Right Side - Login Form -->
    <div class="form-side">
        <div class="form-header">
            <h2>Sign In</h2>
            <p>Don't have an account? <a href="register.php">Create one here</a></p>
        </div>

        <?php if($error_message): ?>
            <div class="error-message">
                <span>⚠️</span>
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <form method="POST" id="loginForm">
            <div class="form-group">
                <label for="email">Email Address</label>
                <div class="input-wrapper">
                    <span class="input-icon">📧</span>
                    <input 
                        type="email" 
                        class="form-control" 
                        id="email" 
                        name="email" 
                        placeholder="Enter your email"
                        value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                        required
                    >
                </div>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-wrapper">
                    <span class="input-icon">🔒</span>
                    <input 
                        type="password" 
                        class="form-control" 
                        id="password" 
                        name="password" 
                        placeholder="Enter your password"
                        required
                    >
                    <span class="password-toggle" onclick="togglePassword()">👁️</span>
                </div>
            </div>

            <div class="remember-forgot">
                <label class="remember-me">
                    <input type="checkbox" name="remember"> 
                    <span>Remember me</span>
                </label>
                <a href="forgot-password.php" class="forgot-link">Forgot Password?</a>
            </div>

            <button type="submit" class="login-btn" onclick="showOverlay()">
                Sign In
            </button>
        </form>

        <!-- Demo Credentials -->
        <div class="demo-credentials">
            <p>Demo Credentials (Click to fill)</p>
            <div class="credential-badges">
                <span class="credential-badge" onclick="fillCredentials('customer@example.com', 'customer123')">
                    🧑 Customer
                </span>
                <span class="credential-badge chef" onclick="fillCredentials('chef@example.com', 'chef123')">
                    👨‍🍳 Chef
                </span>
                <span class="credential-badge manager" onclick="fillCredentials('manager@example.com', 'manager123')">
                    👔 Manager
                </span>
            </div>
        </div>

        <div class="register-link">
            New to our restaurant? <a href="register.php">Create an account</a>
        </div>
    </div>
</div>

<script>
// Toggle password visibility
function togglePassword() {
    const passwordInput = document.getElementById('password');
    const toggleIcon = document.querySelector('.password-toggle');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.textContent = '🔓';
    } else {
        passwordInput.type = 'password';
        toggleIcon.textContent = '👁️';
    }
}

// Fill credentials for demo
function fillCredentials(email, password) {
    document.getElementById('email').value = email;
    document.getElementById('password').value = password;
    
    // Add animation effect
    const emailField = document.getElementById('email');
    const passwordField = document.getElementById('password');
    
    emailField.style.backgroundColor = '#e8f5e9';
    passwordField.style.backgroundColor = '#e8f5e9';
    
    setTimeout(() => {
        emailField.style.backgroundColor = '';
        passwordField.style.backgroundColor = '';
    }, 500);
}

// Show loading overlay
function showOverlay() {
    // Basic validation
    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;
    
    if (!email || !password) {
        alert('Please fill in all fields');
        return false;
    }
    
    document.getElementById('overlay').style.display = 'flex';
    return true;
}

// Auto-hide error message after 5 seconds
<?php if($error_message): ?>
setTimeout(() => {
    const errorMsg = document.querySelector('.error-message');
    if (errorMsg) {
        errorMsg.style.transition = 'opacity 0.5s';
        errorMsg.style.opacity = '0';
        setTimeout(() => errorMsg.remove(), 500);
    }
}, 5000);
<?php endif; ?>

// Remember me checkbox styling
document.querySelector('.remember-me input').addEventListener('change', function() {
    if (this.checked) {
        this.parentElement.style.color = '#667eea';
    } else {
        this.parentElement.style.color = '#666';
    }
});
</script>

</body>
</html>