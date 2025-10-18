<?php
require_once 'config.php';

// Redirect if no reset email in session
if (!isset($_SESSION['reset_email'])) {
    header('Location: forgot_password.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $code = sanitize_input($_POST['code']);
    $email = $_SESSION['reset_email'];

    if (empty($code) || strlen($code) != 6) {
        $error = 'Please enter the 6-digit code.';
    } else {
        // Verify code
        $stmt = $pdo->prepare("SELECT reset_code, reset_code_expiry FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || $user['reset_code'] !== $code) {
            $error = 'Invalid verification code.';
        } elseif (strtotime($user['reset_code_expiry']) < time()) {
            $error = 'Verification code has expired. Please request a new one.';
            // Clear expired code
            $stmt = $pdo->prepare("UPDATE users SET reset_code = NULL, reset_code_expiry = NULL WHERE email = ?");
            $stmt->execute([$email]);
        } else {
            // Code is valid, redirect to reset password
            $_SESSION['code_verified'] = true;
            header('Location: reset_password.php');
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Code - Auth System</title>
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
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            color: var(--dark);
        }

        .container {
            max-width: 480px;
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
            padding: 2rem;
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
            font-size: 1.8rem;
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
            padding: 2rem;
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

        .alert i {
            font-size: 1.2rem;
        }

        .info-box {
            background: rgba(67, 97, 238, 0.05);
            border-radius: var(--border-radius);
            padding: 1.2rem;
            margin-bottom: 1.5rem;
            border-left: 4px solid var(--primary);
            text-align: center;
        }

        .info-box h4 {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-bottom: 0.8rem;
            color: var(--primary);
        }

        .info-box p {
            color: var(--gray);
            font-size: 0.95rem;
            line-height: 1.5;
        }

        .email-display {
            font-weight: 600;
            color: var(--primary);
            background: rgba(67, 97, 238, 0.1);
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            display: inline-block;
            margin: 0.5rem 0;
        }

        .code-input-container {
            margin-bottom: 2rem;
        }

        .code-inputs {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 1rem;
        }

        .code-input {
            width: 50px;
            height: 60px;
            text-align: center;
            font-size: 1.5rem;
            font-weight: bold;
            border: 2px solid var(--light-gray);
            border-radius: 8px;
            background: white;
            transition: var(--transition);
        }

        .code-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
            transform: translateY(-2px);
        }

        .code-input.filled {
            border-color: var(--success);
            background: rgba(76, 201, 240, 0.1);
        }

        .hidden-input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
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
            display: flex;
            justify-content: center;
            gap: 1.5rem;
            flex-wrap: wrap;
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

        .timer {
            text-align: center;
            margin-top: 1rem;
            color: var(--gray);
            font-size: 0.9rem;
        }

        .timer.expired {
            color: var(--danger);
        }

        @media (max-width: 480px) {
            .container {
                max-width: 100%;
            }
            
            .header {
                padding: 1.5rem;
            }
            
            .header h2 {
                font-size: 1.5rem;
            }
            
            .content {
                padding: 1.5rem;
            }
            
            .code-input {
                width: 45px;
                height: 55px;
                font-size: 1.3rem;
            }
            
            .links {
                flex-direction: column;
                gap: 0.8rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Verify Your Identity</h2>
            <p>Enter the verification code sent to your email</p>
        </div>
        
        <div class="content">
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo $error; ?></span>
                </div>
            <?php endif; ?>

            <div class="info-box">
                <h4><i class="fas fa-envelope"></i> Check Your Email</h4>
                <p>We've sent a 6-digit verification code to:</p>
                <div class="email-display">
                    <?php echo htmlspecialchars($_SESSION['reset_email']); ?>
                </div>
                <p>The code will expire in <strong>30 minutes</strong>.</p>
            </div>

            <form method="POST" action="" id="verifyForm">
                <div class="code-input-container">
                    <label for="code" style="display: block; margin-bottom: 0.8rem; font-weight: 600; color: var(--dark);">
                        Enter 6-digit Code
                    </label>
                    
                    <div class="code-inputs">
                        <input type="text" class="code-input" maxlength="1" data-index="0">
                        <input type="text" class="code-input" maxlength="1" data-index="1">
                        <input type="text" class="code-input" maxlength="1" data-index="2">
                        <input type="text" class="code-input" maxlength="1" data-index="3">
                        <input type="text" class="code-input" maxlength="1" data-index="4">
                        <input type="text" class="code-input" maxlength="1" data-index="5">
                    </div>
                    
                    <input type="text" id="code" name="code" maxlength="6" class="hidden-input" required>
                </div>
                
                <div class="timer" id="timer">
                    <i class="fas fa-clock"></i> Code expires in: <span id="countdown">30:00</span>
                </div>
                
                <button type="submit" class="btn" id="submitBtn">
                    <i class="fas fa-check-circle"></i> Verify Code
                </button>
            </form>
            
            <div class="links">
                <a href="forgot_password.php">
                    <i class="fas fa-sync-alt"></i> Request new code
                </a>
                <a href="login.php">
                    <i class="fas fa-arrow-left"></i> Back to Login
                </a>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const codeInputs = document.querySelectorAll('.code-input');
            const hiddenInput = document.getElementById('code');
            const form = document.getElementById('verifyForm');
            const submitBtn = document.getElementById('submitBtn');
            const timerElement = document.getElementById('countdown');
            const timerContainer = document.getElementById('timer');
            
            let timer = 30 * 60; // 30 minutes in seconds
            let countdownInterval;
            
            // Start countdown timer
            function startTimer() {
                countdownInterval = setInterval(function() {
                    timer--;
                    
                    const minutes = Math.floor(timer / 60);
                    const seconds = timer % 60;
                    
                    timerElement.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
                    
                    if (timer <= 0) {
                        clearInterval(countdownInterval);
                        timerElement.textContent = '00:00';
                        timerContainer.classList.add('expired');
                        submitBtn.disabled = true;
                        submitBtn.innerHTML = '<i class="fas fa-clock"></i> Code Expired';
                    }
                }, 1000);
            }
            
            // Initialize timer
            startTimer();
            
            // Code input handling
            codeInputs.forEach((input, index) => {
                // Focus on first input on load
                if (index === 0) {
                    input.focus();
                }
                
                input.addEventListener('input', function(e) {
                    const value = e.target.value.toUpperCase();
                    
                    // Only allow alphanumeric characters
                    if (/^[A-Z0-9]$/.test(value)) {
                        e.target.value = value;
                        e.target.classList.add('filled');
                        
                        // Move to next input if available
                        if (index < codeInputs.length - 1) {
                            codeInputs[index + 1].focus();
                        }
                    } else {
                        e.target.value = '';
                    }
                    
                    updateHiddenInput();
                });
                
                input.addEventListener('keydown', function(e) {
                    // Handle backspace
                    if (e.key === 'Backspace') {
                        if (e.target.value === '' && index > 0) {
                            // Move to previous input and clear it
                            codeInputs[index - 1].focus();
                            codeInputs[index - 1].value = '';
                            codeInputs[index - 1].classList.remove('filled');
                        } else {
                            e.target.value = '';
                            e.target.classList.remove('filled');
                        }
                        updateHiddenInput();
                    }
                    
                    // Handle arrow keys for navigation
                    if (e.key === 'ArrowLeft' && index > 0) {
                        codeInputs[index - 1].focus();
                    }
                    
                    if (e.key === 'ArrowRight' && index < codeInputs.length - 1) {
                        codeInputs[index + 1].focus();
                    }
                });
                
                input.addEventListener('paste', function(e) {
                    e.preventDefault();
                    const pasteData = e.clipboardData.getData('text').toUpperCase();
                    const pasteChars = pasteData.replace(/[^A-Z0-9]/g, '').split('');
                    
                    // Fill inputs with pasted data
                    pasteChars.forEach((char, charIndex) => {
                        if (charIndex < codeInputs.length) {
                            codeInputs[charIndex].value = char;
                            codeInputs[charIndex].classList.add('filled');
                        }
                    });
                    
                    // Focus on next empty input or last input
                    const nextEmptyIndex = pasteChars.length < codeInputs.length ? pasteChars.length : codeInputs.length - 1;
                    codeInputs[nextEmptyIndex].focus();
                    
                    updateHiddenInput();
                });
            });
            
            function updateHiddenInput() {
                const code = Array.from(codeInputs).map(input => input.value).join('');
                hiddenInput.value = code;
                
                // Enable/disable submit button based on code completeness
                submitBtn.disabled = code.length !== 6;
            }
            
            // Form submission
            form.addEventListener('submit', function(e) {
                const code = hiddenInput.value;
                
                if (code.length !== 6) {
                    e.preventDefault();
                    alert('Please enter the complete 6-digit code.');
                    return false;
                }
                
                if (!/^[A-Z0-9]{6}$/.test(code)) {
                    e.preventDefault();
                    alert('Please enter a valid 6-character code (letters and digits only).');
                    return false;
                }
                
                // Add loading state
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verifying...';
            });
            
            // Auto-uppercase for hidden input (fallback)
            hiddenInput.addEventListener('input', function(e) {
                this.value = this.value.toUpperCase().substr(0, 6);
            });
        });
    </script>
</body>
</html>