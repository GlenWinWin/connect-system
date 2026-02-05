<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
allowPublicAccess(); // No login required

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Map form fields to database columns
        $iam = $_POST['iam'] ?? '';
        $fullname = $_POST['fullname'] ?? '';
        $contact = $_POST['contact'] ?? '';
        $age = $_POST['age'] ?? ''; // Changed from age_group to age
        $gender = $_POST['gender'] ?? ''; // Added gender field
        $messenger = $_POST['messenger'] ?? '';
        $service_attended = $_POST['service'] ?? '';
        $invited_by = $_POST['invited-by'] ?? null;
        $lifegroup = $_POST['lifegroup'] ?? '';
        $connected_with = $_POST['connected-with'] ?? '';
        $approached_by = $_POST['approached-by'] ?? '';
        
        // Follow-up fields (set to default values for public form)
        $texted_already = 0;
        $update_report = '';
        $followed_up_by = '';
        $started_one2one = 0;

        // Validate required fields
        $required_fields = [
            'iam', 'fullname', 'contact', 'age', 'gender', 'messenger', 
            'service', 'lifegroup', 'connected-with', 'approached-by'
        ];
        
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("Please fill in all required fields.");
            }
        }

        // Validate age is numeric
        if (!is_numeric($age) || $age <= 0) {
            throw new Exception("Please enter a valid age.");
        }

        // Determine age group based on age and gender
        $age = (int)$age; // Convert to integer for comparison
        $age_group = ''; // This will hold the computed age group
        
        if ($age <= 19) {
            $age_group = 'River Youth';
        } elseif ($age >= 20 && $age <= 35) {
            $age_group = 'Young Adults';
        } elseif ($age >= 36 && $age <= 50) {
            // Determine based on gender
            $gender_value = ($gender === 'male') ? 1 : 0;
            if ($gender === 'male') {
                $age_group = 'River Men';
            } else {
                $age_group = 'River Women';
            }
        } elseif ($age >= 51) {
            $age_group = 'Seasoned';
        } else {
            // Fallback for any edge cases
            $age_group = 'Other';
        }

        // Convert gender to database value (1 for male, 0 for female)
        $gender_value = ($gender === 'male') ? 1 : 0;

        // Insert into database
        $sql = "INSERT INTO first_timers (
            iam, fullname, contact, gender, age_group, messenger, service_attended, 
            invited_by, lifegroup, connected_with, approached_by,
            texted_already, update_report, followed_up_by, started_one2one
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $iam, $fullname, $contact, $gender_value, $age_group, $messenger, $service_attended,
            $invited_by, $lifegroup, $connected_with, $approached_by,
            $texted_already, $update_report, $followed_up_by, $started_one2one
        ]);
        
        $success_message = "Thank you for submitting your information! We will contact you soon.";
        
        // Clear form
        $_POST = array();
        
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>River of God Church - Visitor Form</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/connect-form.css">
</head>

<body>
    <div class="container">
        <header>
            <div class="logo">
                <i class="fas fa-church"></i>
            </div>
            <h1>Welcome to River of God Church!</h1>
            <p class="subtitle">We're glad you're here. Our team would love to serve you and help you get connected.</p>
        </header>

        <div class="form-container">
            <div class="form-section">
                <div class="card form-card">
                    <div class="card-header">
                        <h2 class="card-title">Visitor Information</h2>
                        <div class="required-note"><span class="required"></span> indicates required question</div>
                    </div>

                    <?php if ($success_message): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
                    <?php endif; ?>

                    <?php if ($error_message): ?>
                        <div class="alert alert-error"><?php echo htmlspecialchars($error_message); ?></div>
                    <?php endif; ?>

                    <form id="visitor-form" method="POST">
                        <div class="form-grid">
                            <div class="form-group full-width">
                                <label class="required">I AM...</label>
                                <div class="radio-group">
                                    <div class="radio-option">
                                        <input type="radio" id="looking" name="iam" value="LOOKING FOR A CHURCH" 
                                            <?php echo (isset($_POST['iam']) && $_POST['iam'] === 'LOOKING FOR A CHURCH') ? 'checked' : ''; ?> required>
                                        <label for="looking">LOOKING FOR A CHURCH</label>
                                    </div>
                                    <div class="radio-option">
                                        <input type="radio" id="visitor" name="iam" value="VISITOR"
                                            <?php echo (isset($_POST['iam']) && $_POST['iam'] === 'VISITOR') ? 'checked' : ''; ?> required>
                                        <label for="visitor">VISITOR</label>
                                    </div>
                                    <div class="radio-option">
                                        <input type="radio" id="other-church" name="iam" value="FROM OTHER CHURCH"
                                            <?php echo (isset($_POST['iam']) && $_POST['iam'] === 'FROM OTHER CHURCH') ? 'checked' : ''; ?> required>
                                        <label for="other-church">FROM OTHER CHURCH</label>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="fullname" class="required">FULL NAME</label>
                                <input type="text" id="fullname" name="fullname" placeholder="Enter your full name"
                                    value="<?php echo htmlspecialchars($_POST['fullname'] ?? ''); ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="contact" class="required">CONTACT NO</label>
                                <input type="tel" id="contact" name="contact" placeholder="Enter your phone number"
                                    value="<?php echo htmlspecialchars($_POST['contact'] ?? ''); ?>" required>
                            </div>

                            <!-- Age Field (changed from age group radio buttons) -->
                            <div class="form-group">
                                <label for="age" class="required">AGE</label>
                                <input type="number" id="age" name="age" placeholder="Enter your age" 
                                    min="1" max="120" step="1"
                                    value="<?php echo htmlspecialchars($_POST['age'] ?? ''); ?>" required>
                            </div>

                            <!-- Gender Field (added) -->
                            <div class="form-group">
                                <label class="required">GENDER</label>
                                <div class="radio-group">
                                    <div class="radio-option">
                                        <input type="radio" id="gender-male" name="gender" value="male"
                                            <?php echo (isset($_POST['gender']) && $_POST['gender'] === 'male') ? 'checked' : ''; ?> required>
                                        <label for="gender-male">Male</label>
                                    </div>
                                    <div class="radio-option">
                                        <input type="radio" id="gender-female" name="gender" value="female"
                                            <?php echo (isset($_POST['gender']) && $_POST['gender'] === 'female') ? 'checked' : ''; ?> required>
                                        <label for="gender-female">Female</label>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="messenger">MESSENGER</label>
                                <input type="text" id="messenger" name="messenger" placeholder="Enter your messenger ID"
                                    value="<?php echo htmlspecialchars($_POST['messenger'] ?? ''); ?>">
                            </div>

                            <div class="form-group">
                                <label class="required">WHICH SERVICE DID YOU ATTEND?</label>
                                <div class="radio-group">
                                    <div class="radio-option">
                                        <input type="radio" id="service-10am" name="service" value="10AM"
                                            <?php echo (isset($_POST['service']) && $_POST['service'] === '10AM') ? 'checked' : ''; ?> required>
                                        <label for="service-10am">10AM</label>
                                    </div>
                                    <div class="radio-option">
                                        <input type="radio" id="service-1pm" name="service" value="1PM"
                                            <?php echo (isset($_POST['service']) && $_POST['service'] === '1PM') ? 'checked' : ''; ?> required>
                                        <label for="service-1pm">1PM</label>
                                    </div>
                                    <div class="radio-option">
                                        <input type="radio" id="service-4pm" name="service" value="4PM"
                                            <?php echo (isset($_POST['service']) && $_POST['service'] === '4PM') ? 'checked' : ''; ?> required>
                                        <label for="service-4pm">4PM</label>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group full-width">
                                <label for="invited-by">IF YOU ARE INVITED BY SOMEONE, PLEASE TYPE THEIR NAME</label>
                                <input type="text" id="invited-by" name="invited-by" placeholder="Optional"
                                    value="<?php echo htmlspecialchars($_POST['invited-by'] ?? ''); ?>">
                            </div>

                            <div class="form-group">
                                <label for="approached-by" class="required">APPROACHED BY (Connect Member)</label>
                                <input type="text" id="approached-by" name="approached-by" placeholder="Name of the member who approached you"
                                    value="<?php echo htmlspecialchars($_POST['approached-by'] ?? ''); ?>" required>
                            </div>

                            <div class="form-group">
                                <label class="required">DO YOU WANT TO JOIN A LIFEGROUP?</label>
                                <div class="radio-group">
                                    <div class="radio-option">
                                        <input type="radio" id="lifegroup-yes" name="lifegroup" value="YES"
                                            <?php echo (isset($_POST['lifegroup']) && $_POST['lifegroup'] === 'YES') ? 'checked' : ''; ?> required>
                                        <label for="lifegroup-yes">YES</label>
                                    </div>
                                    <div class="radio-option">
                                        <input type="radio" id="lifegroup-no" name="lifegroup" value="NO"
                                            <?php echo (isset($_POST['lifegroup']) && $_POST['lifegroup'] === 'NO') ? 'checked' : ''; ?> required>
                                        <label for="lifegroup-no">NO</label>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="connected-with">CONNECTED WITH</label>
                                <input type="text" id="connected-with" name="connected-with" placeholder="How did you connect with us?"
                                    value="<?php echo htmlspecialchars($_POST['connected-with'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="actions">
                            <button type="submit" class="btn">Submit Information</button>
                            <button type="reset" class="btn btn-secondary">Clear Form</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="privacy-card">
                <div class="card privacy-notice">
                    <h3>Data Privacy Notice</h3>
                    <p>We are aware of our responsibility to protect your personal data under strict confidentiality. As provided by the Data Privacy Act, you may decide for the processing of your personal data, except in access your personal information, and/or have a corrected, erased, or blocked on reasonable grounds. For the details of your rights as a data subject, you can get in touch with our Discipleship Team.</p>
                </div>
            </div>
        </div>

        <footer>
            <p>River of God Church © 2026. All rights reserved.</p>
        </footer>
    </div>
</body>
</html>