<?php require_once 'layouts/header.php'; ?>

<div class="container">
    <!-- Similar structure to login.php but for signup -->
    <div class="auth-container">
        <div class="welcome-section">
            <div class="welcome-content">
                <h2 class="welcome-title">Join Our Community!</h2>
                <p class="welcome-text">Create an account to get connected with our church, join life groups, and stay updated with events and activities.</p>
                <ul class="features">
                    <li><i class="fas fa-check-circle"></i> Personalized church experience</li>
                    <li><i class="fas fa-check-circle"></i> Join ministries and groups</li>
                    <li><i class="fas fa-check-circle"></i> Event notifications</li>
                    <li><i class="fas fa-check-circle"></i> Member directory access</li>
                </ul>
                <p>Already have an account? <a href="/login.php">Sign in here</a></p>
            </div>
        </div>

        <div class="form-section">
            <div class="form-container">
                <h2 class="form-title">Create Account</h2>
                <p class="form-subtitle">Join our church community today</p>

                <?php if (isset($error)): ?>
                    <div class="alert alert-error"><?php echo $error; ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="form-group">
                        <label for="fullname">Full Name</label>
                        <input type="text" id="fullname" name="fullname" placeholder="Enter your full name" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" placeholder="Enter your email" required>
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="text" id="phone" name="phone" placeholder="Enter your phone number" required>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="password-container">
                            <input type="password" id="password" name="password" placeholder="Create a password" required>
                            <button type="button" class="toggle-password">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <div class="password-container">
                            <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm your password" required>
                            <button type="button" class="toggle-password">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="remember-me">
                            <input type="checkbox" id="terms" required>
                            <label for="terms">I agree to the Terms of Service and Privacy Policy</label>
                        </div>
                    </div>

                    <button type="submit" class="btn">Create Account</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // Add similar JavaScript for password toggles
</script>

<?php require_once 'layouts/footer.php'; ?>