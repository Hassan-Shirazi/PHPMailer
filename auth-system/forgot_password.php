<?php
require_once 'config.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = sanitize_input($_POST['email']);

    if (empty($email)) {
        $error = 'Please enter your email address.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format.';
    } else {
        // Check if email exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            $error = 'Email not registered.';
        } else {
            // Generate 6-character random code
            $code = substr(str_shuffle('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 6);
            $expiry = date('Y-m-d H:i:s', strtotime('+30 minutes')); // Code expires in 30 minutes

            // Store code in database
            $stmt = $pdo->prepare("UPDATE users SET reset_code = ?, reset_code_expiry = ? WHERE email = ?");
            $stmt->execute([$code, $expiry, $email]);

            // Send email using PHPMailer
            require 'phpmailer/src/Exception.php';
            require 'phpmailer/src/PHPMailer.php';
            require 'phpmailer/src/SMTP.php';

            $mail = new PHPMailer\PHPMailer\PHPMailer(true);

            try {
                // Server settings
                $mail->isSMTP();
                $mail->Host = SMTP_HOST;
                $mail->SMTPAuth = true;
                $mail->Username = SMTP_USER;
                $mail->Password = SMTP_PASS;
                $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = SMTP_PORT;

                // Recipients
                $mail->setFrom(SMTP_USER, 'Auth System');
                $mail->addAddress($email);

                // Content
                $mail->isHTML(true);
                $mail->Subject = 'Password Reset Code';
                $mail->Body = "
                    <h2>Password Reset Request</h2>
                    <p>Your password reset code is: <strong>{$code}</strong></p>
                    <p>This code will expire in 30 minutes.</p>
                    <p>If you didn't request this, please ignore this email.</p>
                ";
                $mail->AltBody = "Your password reset code is: {$code}. This code will expire in 30 minutes.";

                $mail->send();
                
                // Store email in session for verification
                $_SESSION['reset_email'] = $email;
                header('Location: verify_code.php');
                exit;

            } catch (Exception $e) {
                $error = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
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
    <title>Forgot Password - Auth System</title>
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
            position: relative;
            overflow: hidden;
        }

        .btn:hover:not(:disabled) {
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
            margin-top: 1.5rem;
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

        .info-box {
            background: rgba(67, 97, 238, 0.05);
            border-radius: var(--border-radius);
            padding: 1.2rem;
            margin-bottom: 1.5rem;
            border-left: 4px solid var(--primary);
        }

        .info-box h4 {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 0.5rem;
            color: var(--primary);
        }

        .info-box p {
            color: var(--gray);
            font-size: 0.9rem;
            line-height: 1.5;
        }

        /* Three Dot Loader */
        .dot-loader {
            display: none;
            justify-content: center;
            align-items: center;
            gap: 6px;
            margin: 1rem 0;
        }

        .dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: var(--primary);
            animation: dotPulse 1.5s ease-in-out infinite;
        }

        .dot:nth-child(2) {
            animation-delay: 0.2s;
        }

        .dot:nth-child(3) {
            animation-delay: 0.4s;
        }

        @keyframes dotPulse {
            0%, 60%, 100% {
                transform: scale(1);
                opacity: 0.6;
            }
            30% {
                transform: scale(1.3);
                opacity: 1;
            }
        }

        .sending-status {
            text-align: center;
            margin: 1rem 0;
            color: var(--primary);
            font-weight: 500;
            display: none;
        }

        .success-animation {
            text-align: center;
            margin: 1rem 0;
            display: none;
        }

        .checkmark {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: block;
            stroke-width: 2;
            stroke: var(--success);
            stroke-miterlimit: 10;
            margin: 0 auto;
            box-shadow: inset 0px 0px 0px var(--success);
            animation: fill .4s ease-in-out .4s forwards, scale .3s ease-in-out .9s both;
        }

        .checkmark__circle {
            stroke-dasharray: 166;
            stroke-dashoffset: 166;
            stroke-width: 2;
            stroke-miterlimit: 10;
            stroke: var(--success);
            fill: none;
            animation: stroke 0.6s cubic-bezier(0.65, 0, 0.45, 1) forwards;
        }

        .checkmark__check {
            transform-origin: 50% 50%;
            stroke-dasharray: 48;
            stroke-dashoffset: 48;
            animation: stroke 0.3s cubic-bezier(0.65, 0, 0.45, 1) 0.8s forwards;
        }

        @keyframes stroke {
            100% {
                stroke-dashoffset: 0;
            }
        }

        @keyframes scale {
            0%, 100% {
                transform: none;
            }
            50% {
                transform: scale3d(1.1, 1.1, 1);
            }
        }

        @keyframes fill {
            100% {
                box-shadow: inset 0px 0px 0px 30px var(--success);
            }
        }

        .redirecting-text {
            text-align: center;
            margin-top: 1rem;
            color: var(--gray);
            display: none;
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
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Reset Your Password</h2>
            <p>Enter your email to receive a reset code</p>
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

            <div class="info-box">
                <h4><i class="fas fa-info-circle"></i> How it works</h4>
                <p>Enter your email address and we'll send you a 6-digit verification code to reset your password. The code will expire in 30 minutes.</p>
            </div>

            <form method="POST" action="" id="forgotPasswordForm">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <div class="input-group">
                        <i class="fas fa-envelope"></i>
                        <input type="email" id="email" name="email" value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" placeholder="Enter your registered email" required>
                    </div>
                </div>
                
                <button type="submit" class="btn" id="submitBtn">
                    <i class="fas fa-paper-plane"></i> Send Reset Code
                </button>

                <!-- Three Dot Loader -->
                <div class="dot-loader" id="dotLoader">
                    <div class="dot"></div>
                    <div class="dot"></div>
                    <div class="dot"></div>
                </div>

                <div class="sending-status" id="sendingStatus">
                    <i class="fas fa-envelope"></i> Sending reset code to your email...
                </div>

                <!-- Success Animation -->
                <div class="success-animation" id="successAnimation">
                    <svg class="checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
                        <circle class="checkmark__circle" cx="26" cy="26" r="25" fill="none"/>
                        <path class="checkmark__check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"/>
                    </svg>
                </div>

                <div class="redirecting-text" id="redirectingText">
                    <i class="fas fa-spinner fa-spin"></i> Redirecting to verification page...
                </div>
            </form>
            
            <div class="links">
                <a href="login.php"><i class="fas fa-arrow-left"></i> Back to Login</a>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('forgotPasswordForm');
            const submitBtn = document.getElementById('submitBtn');
            const dotLoader = document.getElementById('dotLoader');
            const sendingStatus = document.getElementById('sendingStatus');
            const successAnimation = document.getElementById('successAnimation');
            const redirectingText = document.getElementById('redirectingText');
            const emailInput = document.getElementById('email');
            
            // Add focus effect
            emailInput.addEventListener('focus', function() {
                this.parentElement.querySelector('i').style.color = 'var(--primary)';
            });
            
            emailInput.addEventListener('blur', function() {
                this.parentElement.querySelector('i').style.color = 'var(--gray)';
            });
            
            // Form submission handler
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Basic validation
                if (!emailInput.value) {
                    emailInput.style.borderColor = 'var(--danger)';
                    emailInput.parentElement.querySelector('i').style.color = 'var(--danger)';
                    
                    // Shake animation for empty field
                    emailInput.style.animation = 'shake 0.5s';
                    setTimeout(() => {
                        emailInput.style.animation = '';
                    }, 500);
                    return;
                }
                
                // Show loading state
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Sending...';
                dotLoader.style.display = 'flex';
                sendingStatus.style.display = 'block';
                
                // Simulate email sending process (in real scenario, this would be the actual form submission)
                // For demonstration, we'll simulate a delay and then show success
                setTimeout(() => {
                    // Hide loader and sending status
                    dotLoader.style.display = 'none';
                    sendingStatus.style.display = 'none';
                    
                    // Show success animation
                    successAnimation.style.display = 'block';
                    
                    // Change button to success state
                    submitBtn.innerHTML = '<i class="fas fa-check"></i> Code Sent!';
                    submitBtn.style.background = 'var(--success)';
                    
                    // Show redirecting text
                    redirectingText.style.display = 'block';
                    
                    // Actually submit the form after showing success animation
                    setTimeout(() => {
                        form.submit();
                    }, 2000);
                    
                }, 3000); // Simulate 3 second email sending process
            });
            
            // Clear error styling when user starts typing
            emailInput.addEventListener('input', function() {
                if (this.value) {
                    this.style.borderColor = 'var(--light-gray)';
                    this.parentElement.querySelector('i').style.color = 'var(--gray)';
                }
            });
        });
        
        // Add shake animation for empty field
        const style = document.createElement('style');
        style.textContent = `
            @keyframes shake {
                0%, 100% { transform: translateX(0); }
                25% { transform: translateX(-5px); }
                75% { transform: translateX(5px); }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>