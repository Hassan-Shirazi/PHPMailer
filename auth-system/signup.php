<?php
require_once 'config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = sanitize_input($_POST['name']);
    $email = sanitize_input($_POST['email']);
    $phone = sanitize_input($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate inputs
    if (empty($name) || empty($email) || empty($phone) || empty($password)) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } else {
        // Check if user already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR phone = ?");
        $stmt->execute([$email, $phone]);
        
        if ($stmt->rowCount() > 0) {
            $error = 'User already registered.';
        } else {
            // Hash password and insert user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, phone, password) VALUES (?, ?, ?, ?)");
            
            if ($stmt->execute([$name, $email, $phone, $hashed_password])) {
                $success = 'Registration successful. Please log in.';
                // Clear form
                $name = $email = $phone = '';
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Auth System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4361ee;
            --primary-dark: #3a56d4;
            --secondary: #7209b7;
            --success: #4cc9f0;
            --danger: #e63946;
            --warning: #f4a261;
            --light: #f8f9fa;
            --dark: #212529;
            --gray: #6c757d;
            --light-gray: #e9ecef;
            --border-radius: 12px;
            --box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            color: var(--dark);
        }

        .container {
            max-width: 500px;
            width: 100%;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
            animation: fadeIn 0.5s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 2.5rem 2rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 70%);
            transform: rotate(30deg);
        }

        .header h2 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            position: relative;
            z-index: 1;
        }

        .header p {
            opacity: 0.9;
            font-size: 1rem;
            position: relative;
            z-index: 1;
        }

        .content {
            padding: 2.5rem 2rem;
        }

        .alert {
            padding: 1rem;
            border-radius: var(--border-radius);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideIn 0.3s ease-out;
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateX(-10px); }
            to { opacity: 1; transform: translateX(0); }
        }

        .alert-error {
            background: rgba(230, 57, 70, 0.1);
            color: var(--danger);
            border-left: 4px solid var(--danger);
        }

        .alert-success {
            background: rgba(76, 201, 240, 0.1);
            color: var(--success);
            border-left: 4px solid var(--success);
        }

        .alert i {
            font-size: 1.2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--dark);
        }

        .input-group {
            position: relative;
        }

        .input-group input {
            width: 100%;
            padding: 1rem 1rem 1rem 3rem;
            border: 2px solid var(--light-gray);
            border-radius: var(--border-radius);
            font-size: 1rem;
            transition: var(--transition);
            background: white;
        }

        .input-group input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
        }

        .input-group i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray);
            font-size: 1.1rem;
            transition: var(--transition);
        }

        .input-group input:focus + i {
            color: var(--primary);
        }

        .password-toggle {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray);
            cursor: pointer;
            transition: var(--transition);
        }

        .password-toggle:hover {
            color: var(--primary);
        }

        .password-strength {
            margin-top: 0.5rem;
            height: 4px;
            border-radius: 2px;
            background: var(--light-gray);
            overflow: hidden;
        }

        .password-strength-bar {
            height: 100%;
            width: 0%;
            transition: var(--transition);
            border-radius: 2px;
        }

        .strength-weak { background: var(--danger); width: 33%; }
        .strength-medium { background: var(--warning); width: 66%; }
        .strength-strong { background: var(--success); width: 100%; }

        .password-requirements {
            margin-top: 0.5rem;
            font-size: 0.85rem;
            color: var(--gray);
        }

        .requirement {
            display: flex;
            align-items: center;
            gap: 5px;
            margin-bottom: 0.3rem;
        }

        .requirement i {
            font-size: 0.8rem;
        }

        .requirement.met {
            color: var(--success);
        }

        .requirement.unmet {
            color: var(--gray);
        }

        .btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            background: linear-gradient(to right, var(--primary), var(--primary-dark));
            color: white;
            border: none;
            padding: 1rem 1.5rem;
            border-radius: 50px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            box-shadow: 0 4px 15px rgba(67, 97, 238, 0.3);
            width: 100%;
            margin-top: 0.5rem;
        }

        .btn:hover {
            background: linear-gradient(to right, var(--primary-dark), var(--secondary));
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(67, 97, 238, 0.4);
        }

        .btn:active {
            transform: translateY(0);
        }

        .btn:disabled {
            background: var(--light-gray);
            color: var(--gray);
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .links {
            text-align: center;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--light-gray);
        }

        .links a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .links a:hover {
            color: var(--secondary);
            text-decoration: underline;
        }

        .benefits {
            background: rgba(67, 97, 238, 0.05);
            border-radius: var(--border-radius);
            padding: 1.2rem;
            margin-bottom: 1.5rem;
            border-left: 4px solid var(--primary);
        }

        .benefits h4 {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 0.8rem;
            color: var(--primary);
        }

        .benefits ul {
            list-style: none;
            padding-left: 0;
        }

        .benefits li {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 0.5rem;
            color: var(--gray);
            font-size: 0.9rem;
        }

        .benefits li i {
            color: var(--success);
            font-size: 0.8rem;
        }

        @media (max-width: 480px) {
            .container {
                max-width: 100%;
            }
            
            .header {
                padding: 2rem 1.5rem;
            }
            
            .header h2 {
                font-size: 1.7rem;
            }
            
            .content {
                padding: 2rem 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Join Our Community</h2>
            <p>Create your account to get started</p>
        </div>
        
        <div class="content">
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo $error; ?></span>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <span><?php echo $success; ?></span>
                </div>
            <?php endif; ?>

            <div class="benefits">
                <h4><i class="fas fa-star"></i> Benefits of joining</h4>
                <ul>
                    <li><i class="fas fa-check"></i> Secure account protection</li>
                    <li><i class="fas fa-check"></i> Access to all features</li>
                    <li><i class="fas fa-check"></i> Personalized experience</li>
                </ul>
            </div>

            <form method="POST" action="" id="signupForm">
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <div class="input-group">
                        <i class="fas fa-user"></i>
                        <input type="text" id="name" name="name" value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>" placeholder="Enter your full name" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <div class="input-group">
                        <i class="fas fa-envelope"></i>
                        <input type="email" id="email" name="email" value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" placeholder="Enter your email address" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <div class="input-group">
                        <i class="fas fa-phone"></i>
                        <input type="tel" id="phone" name="phone" value="<?php echo isset($phone) ? htmlspecialchars($phone) : ''; ?>" placeholder="Enter your phone number" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-group">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="password" name="password" placeholder="Create a strong password" required>
                        <span class="password-toggle" id="togglePassword">
                            <i class="fas fa-eye"></i>
                        </span>
                    </div>
                    <div class="password-strength">
                        <div class="password-strength-bar" id="passwordStrengthBar"></div>
                    </div>
                    <div class="password-requirements">
                        <div class="requirement unmet" id="lengthReq">
                            <i class="fas fa-circle"></i>
                            <span>At least 6 characters</span>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <div class="input-group">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm your password" required>
                        <span class="password-toggle" id="toggleConfirmPassword">
                            <i class="fas fa-eye"></i>
                        </span>
                    </div>
                    <div id="passwordMatch" class="password-requirements">
                        <div class="requirement unmet" id="matchReq">
                            <i class="fas fa-circle"></i>
                            <span>Passwords must match</span>
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="btn" id="submitBtn">
                    <i class="fas fa-user-plus"></i> Create Account
                </button>
            </form>
            
            <div class="links">
                <a href="login.php">
                    <i class="fas fa-sign-in-alt"></i> Already have an account? Login
                </a>
            </div>
        </div>
    </div>

    <script>
        // Add interactive elements
        document.addEventListener('DOMContentLoaded', function() {
            const passwordInput = document.getElementById('password');
            const confirmPasswordInput = document.getElementById('confirm_password');
            const togglePassword = document.getElementById('togglePassword');
            const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
            const passwordStrengthBar = document.getElementById('passwordStrengthBar');
            const lengthReq = document.getElementById('lengthReq');
            const matchReq = document.getElementById('matchReq');
            const submitBtn = document.getElementById('submitBtn');
            
            // Password visibility toggle
            function setupPasswordToggle(input, toggle) {
                const eyeIcon = toggle.querySelector('i');
                
                toggle.addEventListener('click', function() {
                    const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                    input.setAttribute('type', type);
                    
                    // Toggle eye icon
                    if (type === 'text') {
                        eyeIcon.classList.remove('fa-eye');
                        eyeIcon.classList.add('fa-eye-slash');
                    } else {
                        eyeIcon.classList.remove('fa-eye-slash');
                        eyeIcon.classList.add('fa-eye');
                    }
                });
            }
            
            if (passwordInput) setupPasswordToggle(passwordInput, togglePassword);
            if (confirmPasswordInput) setupPasswordToggle(confirmPasswordInput, toggleConfirmPassword);
            
            // Password strength indicator
            if (passwordInput) {
                passwordInput.addEventListener('input', function() {
                    const password = this.value;
                    
                    // Check length requirement
                    if (password.length >= 6) {
                        lengthReq.classList.remove('unmet');
                        lengthReq.classList.add('met');
                        lengthReq.innerHTML = '<i class="fas fa-check-circle"></i><span>At least 6 characters</span>';
                    } else {
                        lengthReq.classList.remove('met');
                        lengthReq.classList.add('unmet');
                        lengthReq.innerHTML = '<i class="fas fa-circle"></i><span>At least 6 characters</span>';
                    }
                    
                    // Update strength bar
                    if (password.length === 0) {
                        passwordStrengthBar.className = 'password-strength-bar';
                        passwordStrengthBar.style.width = '0%';
                    } else if (password.length < 6) {
                        passwordStrengthBar.className = 'password-strength-bar strength-weak';
                    } else if (password.length < 10) {
                        passwordStrengthBar.className = 'password-strength-bar strength-medium';
                    } else {
                        passwordStrengthBar.className = 'password-strength-bar strength-strong';
                    }
                    
                    validateForm();
                });
            }
            
            // Password match validation
            if (confirmPasswordInput) {
                confirmPasswordInput.addEventListener('input', function() {
                    validateForm();
                });
            }
            
            // Form validation
            function validateForm() {
                if (!passwordInput || !confirmPasswordInput) return;
                
                const password = passwordInput.value;
                const confirmPassword = confirmPasswordInput.value;
                
                // Check if passwords match
                if (confirmPassword.length > 0) {
                    if (password === confirmPassword) {
                        matchReq.classList.remove('unmet');
                        matchReq.classList.add('met');
                        matchReq.innerHTML = '<i class="fas fa-check-circle"></i><span>Passwords match</span>';
                    } else {
                        matchReq.classList.remove('met');
                        matchReq.classList.add('unmet');
                        matchReq.innerHTML = '<i class="fas fa-circle"></i><span>Passwords must match</span>';
                    }
                }
                
                // Enable/disable submit button
                const isLengthValid = password.length >= 6;
                const isMatchValid = password === confirmPassword;
                
                if (isLengthValid && isMatchValid) {
                    submitBtn.disabled = false;
                } else {
                    submitBtn.disabled = true;
                }
            }
            
            // Initialize form validation
            validateForm();
            
            // Form submission with enhanced validation
            const form = document.getElementById('signupForm');
            if (form) {
                form.addEventListener('submit', function(e) {
                    const password = passwordInput.value;
                    const confirmPassword = confirmPasswordInput.value;
                    
                    if (password !== confirmPassword) {
                        e.preventDefault();
                        alert('Passwords do not match!');
                        return false;
                    }
                    
                    if (password.length < 6) {
                        e.preventDefault();
                        alert('Password must be at least 6 characters long!');
                        return false;
                    }
                });
            }
            
            // Add input validation for phone number
            const phoneInput = document.getElementById('phone');
            if (phoneInput) {
                phoneInput.addEventListener('input', function() {
                    // Basic phone number formatting
                    this.value = this.value.replace(/[^0-9+\-\s()]/g, '');
                });
            }
        });
    </script>
</body>
</html>