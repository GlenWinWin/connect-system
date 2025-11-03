<?php require_once __DIR__ . '/../layouts/header.php'; ?>

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
                <h2 class="welcome-title">Welcome Back!</h2>
                <p class="welcome-text">We're glad to see you again. Sign in to access your account and stay connected with our church community.</p>

                <ul class="features">
                    <li><i class="fas fa-check-circle"></i> Stay updated with church events</li>
                    <li><i class="fas fa-check-circle"></i> Join life groups and ministries</li>
                    <li><i class="fas fa-check-circle"></i> Access exclusive resources</li>
                    <li><i class="fas fa-check-circle"></i> Connect with church members</li>
                </ul>

                <p>Don't have an account? <a href="/signup.php">Sign up here</a></p>
            </div>
        </div>

        <div class="form-section">
            <div class="form-container">
                <h2 class="form-title">Sign In</h2>
                <p class="form-subtitle">Enter your credentials to access your account</p>

                <?php if (isset($error)): ?>
                    <div class="alert alert-error"><?php echo $error; ?></div>
                <?php endif; ?>

                <?php if (isset($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" placeholder="Enter your email" required>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="password-container">
                            <input type="password" id="password" name="password" placeholder="Enter your password" required>
                            <button type="button" class="toggle-password">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-options">
                        <div class="remember-me">
                            <input type="checkbox" id="remember-me" name="remember">
                            <label for="remember-me">Remember me</label>
                        </div>
                        <a href="#" class="forgot-password">Forgot password?</a>
                    </div>

                    <button type="submit" class="btn">Sign In</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const togglePassword = document.querySelector('.toggle-password');
        const password = document.getElementById('password');
        
        togglePassword.addEventListener('click', function() {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            this.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
        });
    });
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>