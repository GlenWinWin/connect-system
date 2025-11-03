<?php require_once 'layouts/header.php'; ?>

<div class="container">
    <header>
        <div class="logo">
            <i class="fas fa-church"></i>
        </div>
        <h1>Welcome to River of God Church</h1>
        <p class="subtitle">We're excited to connect with you! Please fill out this form to help us get to know you better.</p>
    </header>

    <div class="form-container">
        <div class="form-section">
            <div class="card form-card">
                <?php if (isset($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>

                <?php if (isset($error)): ?>
                    <div class="alert alert-error"><?php echo $error; ?></div>
                <?php endif; ?>

                <div class="card-header">
                    <h2 class="card-title">Visitor Information</h2>
                    <p class="required-note">Fields marked with * are required</p>
                </div>

                <form method="POST">
                    <div class="form-group">
                        <label for="iam" class="required">I am a:</label>
                        <div class="radio-group">
                            <div class="radio-option">
                                <input type="radio" id="first-timer" name="iam" value="First Timer" required>
                                <label for="first-timer">First Timer</label>
                            </div>
                            <div class="radio-option">
                                <input type="radio" id="second-timer" name="iam" value="Second Timer" required>
                                <label for="second-timer">Second Timer</label>
                            </div>
                            <div class="radio-option">
                                <input type="radio" id="regular" name="iam" value="Regular" required>
                                <label for="regular">Regular</label>
                            </div>
                            <div class="radio-option">
                                <input type="radio" id="member" name="iam" value="Member" required>
                                <label for="member">Member</label>
                            </div>
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label for="fullname" class="required">Full Name</label>
                            <input type="text" id="fullname" name="fullname" placeholder="Enter your full name" required>
                        </div>

                        <div class="form-group">
                            <label for="contact" class="required">Contact Number</label>
                            <input type="tel" id="contact" name="contact" placeholder="Enter your phone number" required>
                        </div>

                        <!-- Add other form fields similar to your original connect-form.php -->
                        
                    </div>

                    <div class="actions">
                        <button type="submit" class="btn">Submit Information</button>
                        <button type="reset" class="btn btn-secondary">Clear Form</button>
                    </div>
                </form>
            </div>

            <div class="card privacy-card">
                <div class="privacy-notice">
                    <h3><i class="fas fa-shield-alt"></i> Privacy Notice</h3>
                    <p>Your information is safe with us. We respect your privacy and will only use your contact details to connect with you about church activities and updates.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'layouts/footer.php'; ?>