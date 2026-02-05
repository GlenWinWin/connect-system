<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

// Redirect if already logged in
if (isLoggedIn()) {
    echo "<script>window.location.href = 'Location: dashboard.php';</script>";
    exit();
}

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && verifyPassword($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            
            header("Location: dashboard.php");
            exit();
        } else {
            $error_message = "Invalid username or password.";
        }
    } catch (PDOException $e) {
        $error_message = "Login failed. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>River of God Church - Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #FF6B35;
            --primary-light: #FF8E53;
            --primary-dark: #E55A2B;
            --primary-ultralight: #FFF3EC;
            --secondary: #004E89;
            --light: #FFF8F0;
            --dark: #2D2D2D;
            --gray: #6c757d;
            --gray-light: #E8E8E8;
            --success: #28a745;
            --border-radius: 16px;
            --box-shadow: 0 10px 30px rgba(255, 107, 53, 0.15);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #FFF8F0 0%, #FFE8D6 100%);
            color: var(--dark);
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            width: 100%;
            max-width: 1200px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .header {
            text-align: center;
            margin-bottom: 40px;
        }

        .logo {
            font-size: 3.5rem;
            color: var(--primary);
            margin-bottom: 15px;
        }

        .church-name {
            font-size: 2.5rem;
            color: var(--dark);
            margin-bottom: 10px;
            font-weight: 700;
        }

        .tagline {
            font-size: 1.2rem;
            color: var(--gray);
            max-width: 500px;
            margin: 0 auto;
        }

        .auth-container {
            display: flex;
            width: 100%;
            max-width: 900px;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
            min-height: 550px;
        }

        .welcome-section {
            flex: 1;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .welcome-section::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" preserveAspectRatio="none"><path d="M0,0 L100,0 L100,100 Z" fill="rgba(255,255,255,0.1)"/></svg>');
            background-size: cover;
        }

        .welcome-content {
            position: relative;
            z-index: 1;
        }

        .welcome-title {
            font-size: 2.2rem;
            margin-bottom: 20px;
            font-weight: 700;
        }

        .welcome-text {
            font-size: 1.1rem;
            margin-bottom: 30px;
            opacity: 0.9;
            line-height: 1.6;
        }

        .features {
            list-style: none;
            margin-bottom: 30px;
        }

        .features li {
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }

        .features i {
            margin-right: 12px;
            font-size: 1.2rem;
        }

        .form-section {
            flex: 1;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .form-container {
            width: 100%;
        }

        .form-title {
            font-size: 1.8rem;
            color: var(--primary);
            margin-bottom: 10px;
            font-weight: 700;
        }

        .form-subtitle {
            color: var(--gray);
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark);
            font-size: 0.95rem;
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 16px 18px;
            border: 2px solid var(--primary-ultralight);
            border-radius: var(--border-radius);
            font-size: 1rem;
            transition: var(--transition);
            background-color: white;
        }

        input:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 4px rgba(255, 107, 53, 0.2);
            background-color: var(--primary-ultralight);
        }

        .btn {
            display: block;
            width: 100%;
            padding: 16px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: var(--border-radius);
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            text-align: center;
            margin-bottom: 20px;
            box-shadow: 0 4px 12px rgba(255, 107, 53, 0.3);
        }

        .btn:hover {
            background: var(--primary-dark);
            transform: translateY(-3px);
            box-shadow: 0 8px 15px rgba(255, 107, 53, 0.4);
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: var(--border-radius);
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
        }

        footer {
            text-align: center;
            padding: 30px 20px;
            color: var(--gray);
            font-size: 0.9rem;
            width: 100%;
            margin-top: 40px;
        }

        @media (max-width: 768px) {
            .auth-container {
                flex-direction: column;
            }

            .welcome-section {
                padding: 30px 25px;
            }

            .form-section {
                padding: 30px 25px;
            }

            .church-name {
                font-size: 2rem;
            }

            .logo {
                font-size: 3rem;
            }

            .welcome-title {
                font-size: 1.8rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">
                <i class="fas fa-handshake"></i>
            </div>
            <h1 class="church-name">River of God Church</h1>
            <p class="tagline">Staff Login - Newcomer Management System</p>
        </div>

        <div class="auth-container">
            <div class="welcome-section">
                <div class="welcome-content">
                    <h2 class="welcome-title">Staff Portal</h2>
                    <p class="welcome-text">Access the newcomer management system to track visitors, follow up with first-timers, and manage church connections.</p>

                    <ul class="features">
                        <li><i class="fas fa-check-circle"></i> Track visitor information</li>
                        <li><i class="fas fa-check-circle"></i> Update follow-up status</li>
                        <li><i class="fas fa-check-circle"></i> Export data to CSV</li>
                        <li><i class="fas fa-check-circle"></i> Manage lifegroup connections</li>
                    </ul>

                    <p>Need access? Contact the system administrator.</p>
                </div>
            </div>

            <div class="form-section">
                <div class="form-container">
                    <h2 class="form-title">Staff Login</h2>
                    <p class="form-subtitle">Enter your credentials to access the system</p>

                    <?php if ($error_message): ?>
                        <div class="alert-error"><?php echo htmlspecialchars($error_message); ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" id="username" name="username" placeholder="Enter your username" required>
                        </div>

                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" id="password" name="password" placeholder="Enter your password" required>
                        </div>

                        <button type="submit" class="btn">Sign In</button>
                    </form>

                    <div style="text-align: center; margin-top: 20px;">
                        <p><a href="connect-form.php" style="color: var(--primary);">Public Visitor Form →</a></p>
                        <p style="margin-top: 10px;"><a href="signup.php" style="color: var(--primary);">Create Staff Account</a></p>
                    </div>
                </div>
            </div>
        </div>

        <footer>
            <p>River of God Church © 2025. All rights reserved.</p>
        </footer>
    </div>
</body>
</html>