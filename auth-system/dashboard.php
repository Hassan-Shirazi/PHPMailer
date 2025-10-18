<?php
require_once 'config.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Auth System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4361ee;
            --primary-dark: #3a56d4;
            --secondary: #7209b7;
            --success: #4cc9f0;
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

        .dashboard-container {
            max-width: 900px;
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

        .dashboard-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 2rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .dashboard-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 70%);
            transform: rotate(30deg);
        }

        .dashboard-header h2 {
            font-size: 2.2rem;
            margin-bottom: 0.5rem;
            position: relative;
            z-index: 1;
        }

        .dashboard-header p {
            opacity: 0.9;
            font-size: 1.1rem;
            position: relative;
            z-index: 1;
        }

        .dashboard-content {
            padding: 2rem;
        }

        .user-info {
            background: var(--light);
            padding: 1.8rem;
            border-radius: var(--border-radius);
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            border-left: 4px solid var(--primary);
            transition: var(--transition);
        }

        .user-info:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .user-info h3 {
            color: var(--primary);
            margin-bottom: 1.2rem;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.4rem;
        }

        .user-info h3 i {
            font-size: 1.2rem;
        }

        .info-item {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 0.8rem;
            border-bottom: 1px solid var(--light-gray);
        }

        .info-item:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }

        .info-item i {
            width: 30px;
            color: var(--primary);
            font-size: 1.1rem;
        }

        .info-item strong {
            min-width: 120px;
            color: var(--gray);
        }

        .info-item span {
            color: var(--dark);
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: var(--border-radius);
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
            text-align: center;
            transition: var(--transition);
            border-top: 3px solid var(--primary);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .stat-card i {
            font-size: 2rem;
            color: var(--primary);
            margin-bottom: 0.8rem;
        }

        .stat-card h4 {
            font-size: 0.9rem;
            color: var(--gray);
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .stat-card p {
            font-size: 1.8rem;
            font-weight: bold;
            color: var(--dark);
        }

        .logout-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            background: linear-gradient(to right, #dc3545, #e63946);
            color: white;
            border: none;
            padding: 0.9rem 1.8rem;
            border-radius: 50px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
            margin: 0 auto;
            text-decoration: none;
            width: fit-content;
        }

        .logout-btn:hover {
            background: linear-gradient(to right, #c82333, #d90429);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(220, 53, 69, 0.4);
        }

        .welcome-message {
            text-align: center;
            margin-bottom: 2rem;
            font-size: 1.1rem;
            color: var(--gray);
        }

        @media (max-width: 768px) {
            .dashboard-container {
                max-width: 100%;
            }
            
            .dashboard-header {
                padding: 1.5rem;
            }
            
            .dashboard-header h2 {
                font-size: 1.8rem;
            }
            
            .dashboard-content {
                padding: 1.5rem;
            }
            
            .stats-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="dashboard-header">
            <h2>Welcome to Your Dashboard</h2>
            <p>Manage your account and view your information</p>
        </div>
        
        <div class="dashboard-content">
            <div class="welcome-message">
                <p>Hello, <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong>! You're successfully logged in.</p>
            </div>
            
            <div class="stats-container">
                <div class="stat-card">
                    <i class="fas fa-user-check"></i>
                    <h4>Account Status</h4>
                    <p>Active</p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-calendar-alt"></i>
                    <h4>Member Since</h4>
                    <p><?php echo date('M Y', strtotime('-3 months')); ?></p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-shield-alt"></i>
                    <h4>Security Level</h4>
                    <p>Standard</p>
                </div>
            </div>
            
            <div class="user-info">
                <h3><i class="fas fa-id-card"></i> User Information</h3>
                
                <div class="info-item">
                    <i class="fas fa-user"></i>
                    <strong>Name:</strong>
                    <span><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                </div>
                
                <div class="info-item">
                    <i class="fas fa-envelope"></i>
                    <strong>Email:</strong>
                    <span><?php echo htmlspecialchars($_SESSION['user_email']); ?></span>
                </div>
                
                <div class="info-item">
                    <i class="fas fa-clock"></i>
                    <strong>Login Time:</strong>
                    <span><?php echo date('F j, Y, g:i a'); ?></span>
                </div>
                
                <div class="info-item">
                    <i class="fas fa-globe"></i>
                    <strong>Timezone:</strong>
                    <span><?php echo date_default_timezone_get(); ?></span>
                </div>
            </div>

            <a href="logout.php" class="btn logout-btn">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>

    <script>
        // Add some interactive elements
        document.addEventListener('DOMContentLoaded', function() {
            // Add animation to stat cards on load
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach((card, index) => {
                card.style.animationDelay = `${index * 0.1}s`;
                card.classList.add('fade-in');
            });
            
            // Add current time updating
            function updateTime() {
                const now = new Date();
                const options = { 
                    weekday: 'long', 
                    year: 'numeric', 
                    month: 'long', 
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit'
                };
                document.querySelector('.welcome-message p').innerHTML = 
                    `Hello, <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong>! You're successfully logged in. <br><small>Current time: ${now.toLocaleDateString('en-US', options)}</small>`;
            }
            
            // Update time every second
            setInterval(updateTime, 1000);
            updateTime();
        });
    </script>
</body>
</html>