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
        $age_group = $_POST['age'] ?? '';
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
            'iam', 'fullname', 'contact', 'age', 'messenger', 
            'service', 'lifegroup', 'connected-with', 'approached-by'
        ];
        
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("Please fill in all required fields.");
            }
        }

        // Insert into database
        $sql = "INSERT INTO first_timers (
            iam, fullname, contact, age_group, messenger, service_attended, 
            invited_by, lifegroup, connected_with, approached_by,
            texted_already, update_report, followed_up_by, started_one2one
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $iam, $fullname, $contact, $age_group, $messenger, $service_attended,
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
            --border-radius: 12px;
            --box-shadow: 0 8px 25px rgba(255, 107, 53, 0.15);
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
            padding: 0;
        }

        .container {
            width: 100%;
            max-width: 1200px;
            padding: 0;
        }

        header {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 40px 30px;
            text-align: center;
            width: 100%;
            border-radius: 0 0 var(--border-radius) var(--border-radius);
            box-shadow: var(--box-shadow);
            position: relative;
            overflow: hidden;
        }

        header::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" preserveAspectRatio="none"><path d="M0,0 L100,0 L100,100 Z" fill="rgba(255,255,255,0.1)"/></svg>');
            background-size: cover;
        }

        .logo {
            font-size: 3rem;
            margin-bottom: 20px;
            position: relative;
            z-index: 1;
        }

        h1 {
            font-size: 2.2rem;
            margin-bottom: 15px;
            line-height: 1.3;
            position: relative;
            z-index: 1;
        }

        .subtitle {
            font-size: 1.1rem;
            opacity: 0.95;
            margin: 0 auto;
            line-height: 1.5;
            max-width: 600px;
            position: relative;
            z-index: 1;
        }

        .form-container {
            padding: 30px 20px;
        }

        .card {
            background: white;
            margin-bottom: 25px;
            padding: 30px 25px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            transition: var(--transition);
            border-top: 4px solid var(--primary);
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(255, 107, 53, 0.2);
        }

        .card-header {
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--primary-ultralight);
        }

        .card-title {
            font-size: 1.6rem;
            color: var(--primary);
            font-weight: 700;
            margin-bottom: 8px;
        }

        .required-note {
            font-size: 0.9rem;
            color: var(--gray);
        }

        .form-group {
            margin-bottom: 25px;
        }

        label {
            display: block;
            margin-bottom: 12px;
            font-weight: 600;
            color: var(--dark);
            font-size: 1rem;
        }

        .required::after {
            content: " *";
            color: var(--primary);
        }

        input[type="text"],
        input[type="tel"] {
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

        .radio-group {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .radio-option {
            display: flex;
            align-items: flex-start;
            margin-bottom: 8px;
            padding: 12px 15px;
            border-radius: var(--border-radius);
            transition: var(--transition);
            border: 2px solid transparent;
        }

        .radio-option:hover {
            background-color: var(--primary-ultralight);
            border-color: var(--primary-light);
        }

        .radio-option input {
            margin-right: 12px;
            margin-top: 3px;
            accent-color: var(--primary);
            transform: scale(1.2);
        }

        .radio-option label {
            margin-bottom: 0;
            font-weight: 500;
            line-height: 1.4;
        }

        .btn {
            display: block;
            width: 100%;
            padding: 18px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: var(--border-radius);
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            text-align: center;
            margin-bottom: 12px;
            box-shadow: 0 4px 12px rgba(255, 107, 53, 0.3);
        }

        .btn:hover {
            background: var(--primary-dark);
            transform: translateY(-3px);
            box-shadow: 0 8px 15px rgba(255, 107, 53, 0.4);
        }

        .btn-secondary {
            background: white;
            color: var(--dark);
            border: 2px solid var(--gray-light);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .btn-secondary:hover {
            background: #f8f9fa;
            border-color: var(--primary-light);
            transform: translateY(-3px);
        }

        .actions {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-top: 30px;
        }

        footer {
            text-align: center;
            padding: 30px 20px;
            color: var(--gray);
            font-size: 0.9rem;
            width: 100%;
            background: white;
            margin-top: 20px;
            border-top: 1px solid var(--primary-ultralight);
        }

        .privacy-notice {
            background: var(--primary-ultralight);
            padding: 25px;
            border-radius: var(--border-radius);
            border-left: 4px solid var(--primary);
        }

        .privacy-notice h3 {
            color: var(--primary);
            margin-bottom: 15px;
            font-size: 1.3rem;
        }

        .privacy-notice p {
            line-height: 1.6;
            font-size: 0.95rem;
            color: var(--dark);
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: var(--border-radius);
            font-weight: 500;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Desktop-specific styles */
        @media (min-width: 768px) {
            .container {
                padding: 0 20px;
            }

            header {
                margin-bottom: 40px;
                padding: 50px 30px;
            }

            .logo {
                font-size: 3.5rem;
            }

            h1 {
                font-size: 2.5rem;
            }

            .subtitle {
                font-size: 1.2rem;
            }

            .form-container {
                display: grid;
                grid-template-columns: 2fr 1fr;
                gap: 30px;
                padding: 0;
            }

            .form-section {
                display: contents;
            }

            .form-card {
                grid-column: 1;
            }

            .privacy-card {
                grid-column: 2;
                grid-row: 1;
                align-self: start;
            }

            .card {
                padding: 35px 30px;
                margin-bottom: 0;
            }

            .card-title {
                font-size: 1.8rem;
            }

            .form-grid {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 25px;
            }

            .full-width {
                grid-column: 1 / -1;
            }

            .radio-group {
                flex-direction: row;
                flex-wrap: wrap;
            }

            .radio-option {
                flex: 1 1 200px;
            }

            .actions {
                flex-direction: row;
                justify-content: flex-end;
            }

            .btn {
                width: auto;
                padding: 16px 35px;
                margin-bottom: 0;
            }

            .btn-secondary {
                order: -1;
            }
        }

        @media (min-width: 992px) {
            .form-grid {
                grid-template-columns: 1fr 1fr;
            }
        }

        /* Mobile-specific optimizations */
        @media (max-width: 767px) {
            .form-container {
                padding: 20px 15px;
            }

            .card {
                margin: 0 0 20px 0;
            }
        }

        @media (max-width: 480px) {
            header {
                padding: 30px 20px;
            }

            .logo {
                font-size: 2.8rem;
            }

            h1 {
                font-size: 1.9rem;
            }

            .card {
                padding: 25px 20px;
            }

            .card-title {
                font-size: 1.4rem;
            }
        }
    </style>
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

                            <div class="form-group full-width">
                                <label class="required">AGE GROUP</label>
                                <div class="radio-group">
                                    <div class="radio-option">
                                        <input type="radio" id="youth" name="age" value="River Youth (13 to 19 years old)"
                                            <?php echo (isset($_POST['age']) && $_POST['age'] === 'River Youth (13 to 19 years old)') ? 'checked' : ''; ?> required>
                                        <label for="youth">River Youth (13 to 19 years old)</label>
                                    </div>
                                    <div class="radio-option">
                                        <input type="radio" id="young-adult" name="age" value="Young Adult (20 to 35 years old)"
                                            <?php echo (isset($_POST['age']) && $_POST['age'] === 'Young Adult (20 to 35 years old)') ? 'checked' : ''; ?> required>
                                        <label for="young-adult">Young Adult (20 to 35 years old)</label>
                                    </div>
                                    <div class="radio-option">
                                        <input type="radio" id="men" name="age" value="River Men (36 to 50 years old)"
                                            <?php echo (isset($_POST['age']) && $_POST['age'] === 'River Men (36 to 50 years old)') ? 'checked' : ''; ?> required>
                                        <label for="men">River Men (36 to 50 years old)</label>
                                    </div>
                                    <div class="radio-option">
                                        <input type="radio" id="women" name="age" value="River Women (36 to 50 years old)"
                                            <?php echo (isset($_POST['age']) && $_POST['age'] === 'River Women (36 to 50 years old)') ? 'checked' : ''; ?> required>
                                        <label for="women">River Women (36 to 50 years old)</label>
                                    </div>
                                    <div class="radio-option">
                                        <input type="radio" id="seasoned" name="age" value="Seasoned (51 years old and above)"
                                            <?php echo (isset($_POST['age']) && $_POST['age'] === 'Seasoned (51 years old and above)') ? 'checked' : ''; ?> required>
                                        <label for="seasoned">Seasoned (51 years old and above)</label>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="messenger" class="required">MESSENGER</label>
                                <input type="text" id="messenger" name="messenger" placeholder="Enter your messenger ID"
                                    value="<?php echo htmlspecialchars($_POST['messenger'] ?? ''); ?>" required>
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
                                <label for="connected-with" class="required">CONNECTED WITH</label>
                                <input type="text" id="connected-with" name="connected-with" placeholder="How did you connect with us?"
                                    value="<?php echo htmlspecialchars($_POST['connected-with'] ?? ''); ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="approached-by" class="required">APPROACHED BY (Connect Member)</label>
                                <input type="text" id="approached-by" name="approached-by" placeholder="Name of the member who approached you"
                                    value="<?php echo htmlspecialchars($_POST['approached-by'] ?? ''); ?>" required>
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
            <p>River of God Church © 2025. All rights reserved.</p>
        </footer>
    </div>
</body>
</html>