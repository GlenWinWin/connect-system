<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>River of God Church - Login & Signup</title>
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
        input[type="email"],
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

        .password-container {
            position: relative;
        }

        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--gray);
            cursor: pointer;
            font-size: 1.1rem;
        }

        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .remember-me {
            display: flex;
            align-items: center;
        }

        .remember-me input {
            margin-right: 8px;
            accent-color: var(--primary);
        }

        .forgot-password {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
        }

        .forgot-password:hover {
            color: var(--primary-dark);
            text-decoration: underline;
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

        .divider {
            text-align: center;
            margin: 25px 0;
            position: relative;
            color: var(--gray);
        }

        .divider::before {
            content: "";
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: var(--gray-light);
        }

        .divider span {
            background: white;
            padding: 0 15px;
            position: relative;
        }

        .social-login {
            display: flex;
            gap: 15px;
            margin-bottom: 25px;
        }

        .social-btn {
            flex: 1;
            padding: 14px;
            border: 2px solid var(--gray-light);
            border-radius: var(--border-radius);
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
        }

        .social-btn:hover {
            border-color: var(--primary);
            transform: translateY(-2px);
        }

        .switch-form {
            text-align: center;
            margin-top: 20px;
            color: var(--gray);
        }

        .switch-form a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            margin-left: 5px;
            transition: var(--transition);
        }

        .switch-form a:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }

        footer {
            text-align: center;
            padding: 30px 20px;
            color: var(--gray);
            font-size: 0.9rem;
            width: 100%;
            margin-top: 40px;
        }

        /* Form states */
        .login-form,
        .signup-form {
            display: none;
        }

        .active-form {
            display: block;
        }

        /* Mobile responsiveness */
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

        @media (max-width: 480px) {
            .form-options {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }

            .social-login {
                flex-direction: column;
            }

            .church-name {
                font-size: 1.8rem;
            }

            .logo {
                font-size: 2.5rem;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <div class="logo">
                <i class="fas fa-church"></i>
            </div>
            <h1 class="church-name">River of God Church</h1>
            <p class="tagline">Connecting hearts, transforming lives, serving our community</p>
        </div>

        <div class="auth-container">
            <div class="welcome-section">
                <div class="welcome-content">
                    <h2 class="welcome-title" id="welcome-title">Welcome Back!</h2>
                    <p class="welcome-text" id="welcome-text">We're glad to see you again. Sign in to access your
                        account and stay connected with our church community.</p>

                    <ul class="features">
                        <li><i class="fas fa-check-circle"></i> Stay updated with church events</li>
                        <li><i class="fas fa-check-circle"></i> Join life groups and ministries</li>
                        <li><i class="fas fa-check-circle"></i> Access exclusive resources</li>
                        <li><i class="fas fa-check-circle"></i> Connect with church members</li>
                    </ul>

                    <p id="switch-prompt">Don't have an account? <a href="#" id="switch-link">Sign up here</a></p>
                </div>
            </div>

            <div class="form-section">
                <!-- Login Form -->
                <div class="form-container login-form active-form" id="login-form">
                    <h2 class="form-title">Sign In</h2>
                    <p class="form-subtitle">Enter your credentials to access your account</p>

                    <form id="loginForm">
                        <div class="form-group">
                            <label for="login-email">Email Address</label>
                            <input type="email" id="login-email" placeholder="Enter your email" required>
                        </div>

                        <div class="form-group">
                            <label for="login-password">Password</label>
                            <div class="password-container">
                                <input type="password" id="login-password" placeholder="Enter your password" required>
                                <button type="button" class="toggle-password" id="toggle-login-password">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="form-options">
                            <div class="remember-me">
                                <input type="checkbox" id="remember-me">
                                <label for="remember-me">Remember me</label>
                            </div>
                            <a href="#" class="forgot-password">Forgot password?</a>
                        </div>

                        <button type="submit" class="btn">Sign In</button>
                    </form>
                </div>

                <!-- Signup Form -->
                <div class="form-container signup-form" id="signup-form">
                    <h2 class="form-title">Create Account</h2>
                    <p class="form-subtitle">Join our church community today</p>

                    <form id="signupForm">
                        <div class="form-group">
                            <label for="signup-fullname">Full Name</label>
                            <input type="text" id="signup-fullname" placeholder="Enter your full name" required>
                        </div>

                        <div class="form-group">
                            <label for="signup-email">Email Address</label>
                            <input type="email" id="signup-email" placeholder="Enter your email" required>
                        </div>

                        <div class="form-group">
                            <label for="signup-phone">Phone Number</label>
                            <input type="text" id="signup-phone" placeholder="Enter your phone number" required>
                        </div>

                        <div class="form-group">
                            <label for="signup-password">Password</label>
                            <div class="password-container">
                                <input type="password" id="signup-password" placeholder="Create a password" required>
                                <button type="button" class="toggle-password" id="toggle-signup-password">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="signup-confirm-password">Confirm Password</label>
                            <div class="password-container">
                                <input type="password" id="signup-confirm-password" placeholder="Confirm your password"
                                    required>
                                <button type="button" class="toggle-password" id="toggle-confirm-password">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="remember-me">
                                <input type="checkbox" id="terms" required>
                                <label for="terms">I agree to the <a href="#" style="color: var(--primary);">Terms of
                                        Service</a> and <a href="#" style="color: var(--primary);">Privacy
                                        Policy</a></label>
                            </div>
                        </div>

                        <button type="submit" class="btn">Create Account</button>
                    </form>
                </div>
            </div>
        </div>

        <footer>
            <p>River of God Church © 2023. All rights reserved.</p>
        </footer>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // DOM Elements
            const loginForm = document.getElementById('login-form');
            const signupForm = document.getElementById('signup-form');
            const switchLink = document.getElementById('switch-link');
            const switchPrompt = document.getElementById('switch-prompt');
            const welcomeTitle = document.getElementById('welcome-title');
            const welcomeText = document.getElementById('welcome-text');

            // Toggle password visibility
            const toggleLoginPassword = document.getElementById('toggle-login-password');
            const toggleSignupPassword = document.getElementById('toggle-signup-password');
            const toggleConfirmPassword = document.getElementById('toggle-confirm-password');

            const loginPassword = document.getElementById('login-password');
            const signupPassword = document.getElementById('signup-password');
            const confirmPassword = document.getElementById('signup-confirm-password');

            // Form toggle functionality
            let isLogin = true;

            switchLink.addEventListener('click', function (e) {
                e.preventDefault();

                if (isLogin) {
                    // Switch to Signup
                    loginForm.classList.remove('active-form');
                    signupForm.classList.add('active-form');
                    welcomeTitle.textContent = 'Join Our Community!';
                    welcomeText.textContent = 'Create an account to get connected with our church, join life groups, and stay updated with events and activities.';
                    switchPrompt.innerHTML = 'Already have an account? <a href="#" id="switch-link">Sign in here</a>';
                    isLogin = false;
                } else {
                    // Switch to Login
                    signupForm.classList.remove('active-form');
                    loginForm.classList.add('active-form');
                    welcomeTitle.textContent = 'Welcome Back!';
                    welcomeText.textContent = "We're glad to see you again. Sign in to access your account and stay connected with our church community.";
                    switchPrompt.innerHTML = "Don't have an account? <a href=\"#\" id=\"switch-link\">Sign up here</a>";
                    isLogin = true;
                }

                // Re-attach event listener to the new switch link
                document.getElementById('switch-link').addEventListener('click', arguments.callee);
            });

            // Toggle password visibility
            toggleLoginPassword.addEventListener('click', function () {
                const type = loginPassword.getAttribute('type') === 'password' ? 'text' : 'password';
                loginPassword.setAttribute('type', type);
                this.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
            });

            toggleSignupPassword.addEventListener('click', function () {
                const type = signupPassword.getAttribute('type') === 'password' ? 'text' : 'password';
                signupPassword.setAttribute('type', type);
                this.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
            });

            toggleConfirmPassword.addEventListener('click', function () {
                const type = confirmPassword.getAttribute('type') === 'password' ? 'text' : 'password';
                confirmPassword.setAttribute('type', type);
                this.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
            });

            // Form submission
            document.getElementById('loginForm').addEventListener('submit', function (e) {
                e.preventDefault();
                alert('Login functionality would be implemented here!');
                // In a real application, you would handle the login process here
            });

            document.getElementById('signupForm').addEventListener('submit', function (e) {
                e.preventDefault();

                // Check if passwords match
                if (signupPassword.value !== confirmPassword.value) {
                    alert('Passwords do not match!');
                    return;
                }

                alert('Account creation functionality would be implemented here!');
                // In a real application, you would handle the signup process here
            });
        });
    </script>
</body>

</html>