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

$message = '';
$message_type = '';
$name = $email = '';

/* ====================================
   REGISTRATION PROCESS
==================================== */
if(isset($_POST['register'])) {

    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = "customer"; // Only customers can self-register

    // Validation
    $errors = [];

    // Check if email already exists
    $check_email = mysqli_query($conn, "SELECT id FROM users WHERE email='$email'");
    if (mysqli_num_rows($check_email) > 0) {
        $errors[] = "Email already registered!";
    }

    // Validate password strength
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long!";
    }
    if (!preg_match("/[A-Z]/", $password)) {
        $errors[] = "Password must contain at least one uppercase letter!";
    }
    if (!preg_match("/[a-z]/", $password)) {
        $errors[] = "Password must contain at least one lowercase letter!";
    }
    if (!preg_match("/[0-9]/", $password)) {
        $errors[] = "Password must contain at least one number!";
    }

    // Check if passwords match
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match!";
    }

    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO users (name, email, password, role, created_at)
                VALUES ('$name', '$email', '$hashed_password', '$role', NOW())";

        if (mysqli_query($conn, $sql)) {
            $message = "Registration successful! Redirecting to login page...";
            $message_type = "success";
            
            // Store success message in session for login page
            $_SESSION['registration_success'] = "Account created successfully! Please login with your credentials.";
            
            // Clear form fields
            $name = $email = '';
            
            // Redirect after 3 seconds using JavaScript
            echo "<script>
                    setTimeout(function() {
                        window.location.href = 'login.php';
                    }, 3000);
                  </script>";
        } else {
            $message = "Error: " . mysqli_error($conn);
            $message_type = "error";
        }
    } else {
        $message = implode("<br>", $errors);
        $message_type = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Restaurant Management System</title>
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
            padding: 20px;
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
            right: -150px;
            animation-delay: 0s;
        }

        .bg-bubble-2 {
            width: 400px;
            height: 400px;
            bottom: -200px;
            left: -200px;
            animation-delay: 5s;
        }

        .bg-bubble-3 {
            width: 200px;
            height: 200px;
            top: 50%;
            right: 10%;
            transform: translateY(-50%);
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
        .register-container {
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

        .benefits-list {
            list-style: none;
            margin-top: 20px;
        }

        .benefit-item {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
            animation: fadeInLeft 0.5s ease-out forwards;
            opacity: 0;
        }

        .benefit-item:nth-child(1) { animation-delay: 0.2s; }
        .benefit-item:nth-child(2) { animation-delay: 0.4s; }
        .benefit-item:nth-child(3) { animation-delay: 0.6s; }

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

        .benefit-icon {
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        .benefit-text {
            font-size: 15px;
            font-weight: 500;
        }

        /* Right Side - Registration Form */
        .form-side {
            padding: 50px 45px;
            background: white;
        }

        .form-header {
            margin-bottom: 30px;
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

        .form-group {
            margin-bottom: 20px;
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
            z-index: 1;
        }

        .form-control {
            width: 100%;
            padding: 14px 15px 14px 50px;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
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
            z-index: 1;
        }

        .password-toggle:hover {
            color: #667eea;
        }

        /* Password strength meter */
        .password-strength {
            margin-top: 8px;
            height: 4px;
            background: #e0e0e0;
            border-radius: 2px;
            overflow: hidden;
        }

        .strength-bar {
            height: 100%;
            width: 0;
            transition: width 0.3s, background-color 0.3s;
            border-radius: 2px;
        }

        .strength-text {
            font-size: 12px;
            margin-top: 5px;
            color: #666;
        }

        .strength-weak { background: #dc3545; }
        .strength-medium { background: #ffc107; }
        .strength-strong { background: #28a745; }

        /* Password requirements */
        .password-requirements {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 12px;
            margin: 10px 0 20px;
            font-size: 12px;
        }

        .requirement {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 6px;
            color: #666;
        }

        .requirement.met {
            color: #28a745;
        }

        .terms-checkbox {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 20px 0;
            color: #666;
            font-size: 14px;
        }

        .terms-checkbox input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: #667eea;
        }

        .terms-checkbox a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }

        .terms-checkbox a:hover {
            text-decoration: underline;
        }

        .register-btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            margin-bottom: 20px;
            font-family: 'Poppins', sans-serif;
        }

        .register-btn::before {
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

        .register-btn:hover::before {
            width: 300px;
            height: 300px;
        }

        .register-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }

        .register-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

        .login-link {
            text-align: center;
            color: #666;
            font-size: 14px;
        }

        .login-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
        }

        .login-link a:hover {
            color: #764ba2;
            text-decoration: underline;
        }

        /* Loading Overlay */
        .loading-overlay {
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

        .loading-overlay.active {
            display: flex;
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
            margin-bottom: 10px;
        }

        .loading-subtext {
            color: #666;
            font-size: 14px;
        }

        .redirect-timer {
            font-size: 36px;
            font-weight: 700;
            color: #667eea;
            margin: 20px 0 10px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Success Modal */
        .success-modal {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            z-index: 10000;
            text-align: center;
            display: none;
        }

        .success-modal.active {
            display: block;
        }

        .success-icon {
            font-size: 80px;
            margin-bottom: 20px;
            animation: bounce 1s infinite;
        }

        .success-title {
            font-size: 24px;
            color: #333;
            margin-bottom: 10px;
        }

        .success-message {
            color: #666;
            margin-bottom: 20px;
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .register-container {
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
    </style>
</head>
<body>

<!-- Animated Background Bubbles -->
<div class="bg-bubble bg-bubble-1"></div>
<div class="bg-bubble bg-bubble-2"></div>
<div class="bg-bubble bg-bubble-3"></div>

<!-- Loading Overlay -->
<div class="loading-overlay" id="loadingOverlay">
    <div class="loading-spinner">
        <div class="spinner"></div>
        <div class="loading-text" id="loadingText">Processing...</div>
        <div class="loading-subtext" id="loadingSubtext">Please wait</div>
    </div>
</div>

<!-- Success Modal -->
<div class="success-modal" id="successModal">
    <div class="success-icon">✅</div>
    <h2 class="success-title">Registration Successful!</h2>
    <p class="success-message" id="successMessage">Your account has been created successfully.</p>
    <div class="redirect-timer" id="redirectTimer">3</div>
    <p>Redirecting to login page...</p>
</div>

<div class="register-container">
    <!-- Left Side - Branding -->
    <div class="brand-side">
        <div class="brand-icon">🍽️</div>
        <h1 class="brand-title">Join Our Family!</h1>
        <p class="brand-subtitle">Create an account to start ordering delicious food and enjoy exclusive benefits.</p>
        
        <ul class="benefits-list">
            <li class="benefit-item">
                <span class="benefit-icon">🚀</span>
                <span class="benefit-text">Fast & easy ordering</span>
            </li>
            <li class="benefit-item">
                <span class="benefit-icon">🎉</span>
                <span class="benefit-text">Exclusive offers & discounts</span>
            </li>
            <li class="benefit-item">
                <span class="benefit-icon">❤️</span>
                <span class="benefit-text">Save your favorite orders</span>
            </li>
        </ul>
        
        <div style="margin-top: 40px; font-size: 14px; opacity: 0.8;">
            Already have an account? <a href="login.php" style="color: white; font-weight: 600;">Sign in</a>
        </div>
    </div>

    <!-- Right Side - Registration Form -->
    <div class="form-side">
        <div class="form-header">
            <h2>Create Account</h2>
            <p>Join us to start ordering delicious food</p>
        </div>

        <?php if($message && $message_type !== 'success'): ?>
            <div class="message-alert <?php echo $message_type; ?>">
                <span><?php echo $message_type === 'success' ? '✅' : '⚠️'; ?></span>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <?php if($message && $message_type === 'success'): ?>
            <div class="message-alert success">
                <span>✅</span>
                <?php echo $message; ?>
            </div>
            <script>
                // Show success modal
                document.addEventListener('DOMContentLoaded', function() {
                    document.getElementById('successModal').classList.add('active');
                    let timeLeft = 3;
                    const timerDisplay = document.getElementById('redirectTimer');
                    
                    const timer = setInterval(function() {
                        timeLeft--;
                        timerDisplay.textContent = timeLeft;
                        
                        if (timeLeft <= 0) {
                            clearInterval(timer);
                            window.location.href = 'login.php';
                        }
                    }, 1000);
                });
            </script>
        <?php endif; ?>

        <form method="POST" action="" id="registerForm" onsubmit="return validateForm()">
            <div class="form-group">
                <label for="name">Full Name</label>
                <div class="input-wrapper">
                    <span class="input-icon">👤</span>
                    <input 
                        type="text" 
                        class="form-control" 
                        id="name" 
                        name="name" 
                        placeholder="Enter your full name"
                        value="<?php echo htmlspecialchars($name); ?>"
                        required
                        minlength="2"
                    >
                </div>
            </div>

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
                        value="<?php echo htmlspecialchars($email); ?>"
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
                        placeholder="Create a password"
                        required
                        onkeyup="checkPasswordStrength()"
                    >
                    <span class="password-toggle" onclick="togglePassword('password')">👁️</span>
                </div>
                
                <div class="password-strength">
                    <div class="strength-bar" id="strengthBar"></div>
                </div>
                <div class="strength-text" id="strengthText"></div>
                
                <div class="password-requirements" id="passwordRequirements">
                    <div class="requirement" id="reqLength">
                        <span>🔴</span> At least 8 characters
                    </div>
                    <div class="requirement" id="reqUppercase">
                        <span>🔴</span> One uppercase letter
                    </div>
                    <div class="requirement" id="reqLowercase">
                        <span>🔴</span> One lowercase letter
                    </div>
                    <div class="requirement" id="reqNumber">
                        <span>🔴</span> One number
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <div class="input-wrapper">
                    <span class="input-icon">🔒</span>
                    <input 
                        type="password" 
                        class="form-control" 
                        id="confirm_password" 
                        name="confirm_password" 
                        placeholder="Confirm your password"
                        required
                        onkeyup="checkPasswordMatch()"
                    >
                    <span class="password-toggle" onclick="togglePassword('confirm_password')">👁️</span>
                </div>
                <div id="passwordMatchMessage" style="font-size: 12px; margin-top: 5px;"></div>
            </div>

            <div class="terms-checkbox">
                <input type="checkbox" id="terms" name="terms" required>
                <label for="terms">I agree to the <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a></label>
            </div>

            <button type="submit" name="register" class="register-btn" id="registerBtn">
                Create Account
            </button>

            <div class="login-link">
                Already have an account? <a href="login.php">Sign in here</a>
            </div>
        </form>
    </div>
</div>

<script>
// Toggle password visibility
function togglePassword(fieldId) {
    const passwordInput = document.getElementById(fieldId);
    const toggleIcon = passwordInput.nextElementSibling;
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.textContent = '🔓';
    } else {
        passwordInput.type = 'password';
        toggleIcon.textContent = '👁️';
    }
}

// Check password strength
function checkPasswordStrength() {
    const password = document.getElementById('password').value;
    const strengthBar = document.getElementById('strengthBar');
    const strengthText = document.getElementById('strengthText');
    
    const hasLength = password.length >= 8;
    const hasUppercase = /[A-Z]/.test(password);
    const hasLowercase = /[a-z]/.test(password);
    const hasNumber = /[0-9]/.test(password);
    
    updateRequirement('reqLength', hasLength, '✅', '🔴');
    updateRequirement('reqUppercase', hasUppercase, '✅', '🔴');
    updateRequirement('reqLowercase', hasLowercase, '✅', '🔴');
    updateRequirement('reqNumber', hasNumber, '✅', '🔴');
    
    const requirements = [hasLength, hasUppercase, hasLowercase, hasNumber];
    const metCount = requirements.filter(Boolean).length;
    
    let strength = 0;
    let text = '';
    let colorClass = '';
    
    if (metCount === 0) {
        strength = 0;
        text = 'Very weak';
        colorClass = '';
    } else if (metCount <= 2) {
        strength = 33.33;
        text = 'Weak';
        colorClass = 'strength-weak';
    } else if (metCount === 3) {
        strength = 66.66;
        text = 'Medium';
        colorClass = 'strength-medium';
    } else {
        strength = 100;
        text = 'Strong';
        colorClass = 'strength-strong';
    }
    
    strengthBar.style.width = strength + '%';
    strengthBar.className = 'strength-bar ' + colorClass;
    strengthText.textContent = 'Password strength: ' + text;
}

function updateRequirement(elementId, met, metIcon, notMetIcon) {
    const element = document.getElementById(elementId);
    const icon = element.querySelector('span');
    if (met) {
        element.classList.add('met');
        icon.innerHTML = metIcon;
    } else {
        element.classList.remove('met');
        icon.innerHTML = notMetIcon;
    }
}

// Check if passwords match
function checkPasswordMatch() {
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    const message = document.getElementById('passwordMatchMessage');
    
    if (confirmPassword === '') {
        message.innerHTML = '';
    } else if (password === confirmPassword) {
        message.innerHTML = '✅ Passwords match';
        message.style.color = '#28a745';
    } else {
        message.innerHTML = '❌ Passwords do not match';
        message.style.color = '#dc3545';
    }
}

// Validate form before submission
function validateForm() {
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    const terms = document.getElementById('terms').checked;
    const name = document.getElementById('name').value;
    const email = document.getElementById('email').value;
    
    // Basic validation
    if (!name || !email || !password || !confirmPassword) {
        alert('Please fill in all fields');
        return false;
    }
    
    // Check password strength
    const hasLength = password.length >= 8;
    const hasUppercase = /[A-Z]/.test(password);
    const hasLowercase = /[a-z]/.test(password);
    const hasNumber = /[0-9]/.test(password);
    
    if (!hasLength || !hasUppercase || !hasLowercase || !hasNumber) {
        alert('Please meet all password requirements!');
        return false;
    }
    
    // Check if passwords match
    if (password !== confirmPassword) {
        alert('Passwords do not match!');
        return false;
    }
    
    // Check terms agreement
    if (!terms) {
        alert('Please agree to the Terms of Service and Privacy Policy');
        return false;
    }
    
    // Show loading overlay
    const overlay = document.getElementById('loadingOverlay');
    const loadingText = document.getElementById('loadingText');
    const loadingSubtext = document.getElementById('loadingSubtext');
    
    loadingText.textContent = 'Creating Account...';
    loadingSubtext.textContent = 'Please wait while we process your registration';
    overlay.classList.add('active');
    
    // Disable button to prevent double submission
    document.getElementById('registerBtn').disabled = true;
    
    return true;
}

// Real-time validation for name field
document.getElementById('name').addEventListener('input', function(e) {
    const name = e.target.value;
    if (name.length < 2 && name.length > 0) {
        e.target.style.borderColor = '#dc3545';
    } else if (name.length >= 2) {
        e.target.style.borderColor = '#28a745';
    } else {
        e.target.style.borderColor = '#e0e0e0';
    }
});

// Real-time validation for email
document.getElementById('email').addEventListener('input', function(e) {
    const email = e.target.value;
    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailPattern.test(email) && email.length > 0) {
        e.target.style.borderColor = '#dc3545';
    } else if (emailPattern.test(email)) {
        e.target.style.borderColor = '#28a745';
    } else {
        e.target.style.borderColor = '#e0e0e0';
    }
});

// Auto-hide error message after 5 seconds
<?php if($message && $message_type === 'error'): ?>
setTimeout(() => {
    const alert = document.querySelector('.message-alert');
    if (alert) {
        alert.style.transition = 'opacity 0.5s';
        alert.style.opacity = '0';
        setTimeout(() => alert.remove(), 500);
    }
}, 5000);
<?php endif; ?>

// Hide loading overlay if there's an error (page reloaded with error)
<?php if($message && $message_type === 'error'): ?>
document.getElementById('loadingOverlay').classList.remove('active');
document.getElementById('registerBtn').disabled = false;
<?php endif; ?>
</script>

</body>
</html>