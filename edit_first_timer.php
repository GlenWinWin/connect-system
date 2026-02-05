<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
requireAuth();

$id = $_GET['id'] ?? 0;
$success_message = '';
$error_message = '';

// Fetch visitor data
$stmt = $pdo->prepare("SELECT * FROM first_timers WHERE id = ?");
$stmt->execute([$id]);
$visitor = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$visitor) {
    header("Location: dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get form data
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
        
        // Follow-up fields
        $texted_already = isset($_POST['texted_already']) ? 1 : 0;
        $update_report = $_POST['update_report'] ?? '';
        $followed_up_by = $_POST['followed_up_by'] ?? '';
        $started_one2one = isset($_POST['started_one2one']) ? 1 : 0;

        // Update database
        $sql = "UPDATE first_timers SET 
                iam = ?, fullname = ?, contact = ?, age_group = ?, messenger = ?, 
                service_attended = ?, invited_by = ?, lifegroup = ?, connected_with = ?, 
                approached_by = ?, texted_already = ?, update_report = ?, 
                followed_up_by = ?, started_one2one = ?, updated_at = CURRENT_TIMESTAMP 
                WHERE id = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $iam, $fullname, $contact, $age_group, $messenger, $service_attended,
            $invited_by, $lifegroup, $connected_with, $approached_by,
            $texted_already, $update_report, $followed_up_by, $started_one2one, $id
        ]);
        
        $success_message = "Visitor information updated successfully!";
        
        // Refresh visitor data
        $stmt = $pdo->prepare("SELECT * FROM first_timers WHERE id = ?");
        $stmt->execute([$id]);
        $visitor = $stmt->fetch(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        $error_message = "Error updating visitor: " . $e->getMessage();
    }
}

// Function to check if radio button should be selected
function isSelected($currentValue, $expectedValue) {
    return $currentValue === $expectedValue ? 'checked' : '';
}

// Function to check if checkbox should be checked
function isChecked($value) {
    return $value ? 'checked' : '';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Visitor - River of God Church</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <style>
        .edit-form-container {
            background: white;
            border-radius: var(--border-radius);
            padding: 30px;
            margin-top: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .form-title {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--light-gray);
        }
        
        .form-title h2 {
            margin: 0;
            color: var(--dark);
            font-weight: 600;
            font-size: 1.4em;
        }
        
        .form-title i {
            color: var(--primary);
        }
        
        .edit-form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group.full-width {
            grid-column: 1 / -1;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark);
            font-size: 0.95em;
        }
        
        .form-group label.required::after {
            content: " *";
            color: #ff4757;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid var(--gray);
            border-radius: 6px;
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            transition: all 0.3s ease;
            background: var(--light);
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.1);
        }
        
        .radio-group {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-top: 5px;
        }
        
        .radio-option {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .radio-option input[type="radio"] {
            margin: 0;
            width: 18px;
            height: 18px;
        }
        
        .radio-option label {
            margin: 0;
            font-weight: 500;
            color: var(--dark-gray);
            cursor: pointer;
        }
        
        .radio-group-horizontal {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 15px 0;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: 18px;
            height: 18px;
            margin: 0;
        }
        
        .checkbox-group label {
            margin: 0;
            font-weight: 500;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
        }
        
        .checkbox-group label i {
            color: var(--primary);
        }
        
        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }
        
        .form-actions {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            padding-top: 25px;
            margin-top: 30px;
            border-top: 1px solid var(--light-gray);
        }
        
        .section-divider {
            margin: 30px 0;
            padding: 20px 0;
            border-top: 2px solid var(--light-gray);
            border-bottom: 2px solid var(--light-gray);
        }
        
        .section-divider h3 {
            margin: 0 0 20px 0;
            color: var(--dark);
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .section-divider h3 i {
            color: var(--primary);
        }
        
        @media (max-width: 768px) {
            .edit-form-container {
                padding: 20px;
            }
            
            .edit-form-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .radio-group-horizontal {
                flex-direction: column;
                gap: 10px;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .form-actions .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="church-info">
                <div class="logo">
                    <i class="fas fa-church"></i>
                </div>
                <h1 class="church-name">Edit Visitor Information</h1>
            </div>
            <div class="user-info">
                <span style="font-weight: 600;">Welcome, <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'User'); ?></span>
                <a href="dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> <span class="desktop-only">Back to Dashboard</span>
                </a>
            </div>
        </div>

        <!-- Messages -->
        <?php if ($success_message): ?>
            <div class="alert alert-success fade-in" style="margin-top: 20px;">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-error fade-in" style="margin-top: 20px;">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <!-- Edit Form -->
        <div class="edit-form-container fade-in">
            <div class="form-title">
                <i class="fas fa-user-edit"></i>
                <h2>Edit Visitor Information</h2>
            </div>
            
            <form method="POST">
                <div class="edit-form-grid">
                    <!-- I AM... Section -->
                    <div class="form-group full-width">
                        <label class="required">I AM...</label>
                        <div class="radio-group-horizontal">
                            <div class="radio-option">
                                <input type="radio" id="looking" name="iam" value="LOOKING FOR A CHURCH" 
                                    <?php echo isSelected($visitor['iam'], 'LOOKING FOR A CHURCH'); ?> required>
                                <label for="looking">LOOKING FOR A CHURCH</label>
                            </div>
                            <div class="radio-option">
                                <input type="radio" id="visitor" name="iam" value="VISITOR"
                                    <?php echo isSelected($visitor['iam'], 'VISITOR'); ?> required>
                                <label for="visitor">VISITOR</label>
                            </div>
                            <div class="radio-option">
                                <input type="radio" id="other-church" name="iam" value="FROM OTHER CHURCH"
                                    <?php echo isSelected($visitor['iam'], 'FROM OTHER CHURCH'); ?> required>
                                <label for="other-church">FROM OTHER CHURCH</label>
                            </div>
                        </div>
                    </div>

                    <!-- Full Name -->
                    <div class="form-group">
                        <label for="fullname" class="required">FULL NAME</label>
                        <input type="text" id="fullname" name="fullname" placeholder="Enter full name"
                            value="<?php echo htmlspecialchars($visitor['fullname']); ?>" required
                            class="form-control">
                    </div>

                    <!-- Contact Number -->
                    <div class="form-group">
                        <label for="contact" class="required">CONTACT NO</label>
                        <input type="tel" id="contact" name="contact" placeholder="Enter phone number"
                            value="<?php echo htmlspecialchars($visitor['contact']); ?>" required
                            class="form-control">
                    </div>

                    <!-- Age Group -->
                    <div class="form-group full-width">
                        <label class="required">AGE GROUP</label>
                        <div class="radio-group">
                            <?php
                            $age_groups = [
                                'Youth' => 'Youth',
                                'Young Adult' => 'Young Adult',
                                'River Men' => 'River Men',
                                'River Women' => 'River Women',
                                'Seasoned' => 'Seasoned'
                            ];
                            foreach ($age_groups as $key => $label): 
                                $currentAgeGroup = $visitor['age_group'];
                                // Check both possible formats
                                $isSelected = ($currentAgeGroup === $label || $currentAgeGroup === $key) ? 'checked' : '';
                            ?>
                                <div class="radio-option">
                                    <input type="radio" id="age-<?php echo strtolower(str_replace(' ', '-', $key)); ?>" 
                                           name="age" value="<?php echo htmlspecialchars($label); ?>"
                                        <?php echo $isSelected; ?> required>
                                    <label for="age-<?php echo strtolower(str_replace(' ', '-', $key)); ?>">
                                        <?php echo htmlspecialchars($label); ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Messenger -->
                    <div class="form-group">
                        <label for="messenger">MESSENGER</label>
                        <input type="text" id="messenger" name="messenger" placeholder="Enter messenger ID"
                            value="<?php echo htmlspecialchars($visitor['messenger']); ?>"
                            class="form-control">
                    </div>

                    <!-- Service Attended -->
                    <div class="form-group">
                        <label class="required">WHICH SERVICE DID YOU ATTEND?</label>
                        <div class="radio-group-horizontal">
                            <?php
                            $services = ['10AM', '1PM', '4PM'];
                            foreach ($services as $service): ?>
                                <div class="radio-option">
                                    <input type="radio" id="service-<?php echo strtolower($service); ?>" 
                                           name="service" value="<?php echo htmlspecialchars($service); ?>"
                                        <?php echo isSelected($visitor['service_attended'], $service); ?> required>
                                    <label for="service-<?php echo strtolower($service); ?>"><?php echo htmlspecialchars($service); ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Invited By -->
                    <div class="form-group">
                        <label for="invited-by">IF YOU ARE INVITED BY SOMEONE, PLEASE TYPE THEIR NAME</label>
                        <input type="text" id="invited-by" name="invited-by" placeholder="Optional"
                            value="<?php echo htmlspecialchars($visitor['invited_by']); ?>"
                            class="form-control">
                    </div>

                    <!-- Lifegroup Interest -->
                    <div class="form-group">
                        <label class="required">DO YOU WANT TO JOIN A LIFEGROUP?</label>
                        <div class="radio-group-horizontal">
                            <div class="radio-option">
                                <input type="radio" id="lifegroup-yes" name="lifegroup" value="YES"
                                    <?php echo isSelected($visitor['lifegroup'], 'YES'); ?> required>
                                <label for="lifegroup-yes">YES</label>
                            </div>
                            <div class="radio-option">
                                <input type="radio" id="lifegroup-no" name="lifegroup" value="NO"
                                    <?php echo isSelected($visitor['lifegroup'], 'NO'); ?> required>
                                <label for="lifegroup-no">NO</label>
                            </div>
                        </div>
                    </div>

                    <!-- Connected With -->
                    <div class="form-group">
                        <label for="connected-with">CONNECTED WITH</label>
                        <input type="text" id="connected-with" name="connected-with" placeholder="How did you connect with us?"
                            value="<?php echo htmlspecialchars($visitor['connected_with']); ?>"
                            class="form-control">
                    </div>

                    <!-- Approached By -->
                    <div class="form-group">
                        <label for="approached-by" class="required">APPROACHED BY (Connect Member)</label>
                        <input type="text" id="approached-by" name="approached-by" placeholder="Name of the member who approached you"
                            value="<?php echo htmlspecialchars($visitor['approached_by']); ?>" required
                            class="form-control">
                    </div>
                </div>

                <!-- Follow-up Section -->
                <div class="section-divider">
                    <h3><i class="fas fa-clipboard-check"></i> Follow-up Information</h3>
                    
                    <div class="edit-form-grid">
                        <!-- Texted Already -->
                        <div class="form-group">
                            <div class="checkbox-group">
                                <input type="checkbox" id="texted_already" name="texted_already" 
                                    <?php echo isChecked($visitor['texted_already']); ?>
                                    class="checkbox-control">
                                <label for="texted_already">
                                    <i class="fas fa-comment-alt"></i> Texted Already
                                </label>
                            </div>
                        </div>

                        <!-- Started One-to-One -->
                        <div class="form-group">
                            <div class="checkbox-group">
                                <input type="checkbox" id="started_one2one" name="started_one2one"
                                    <?php echo isChecked($visitor['started_one2one']); ?>
                                    class="checkbox-control">
                                <label for="started_one2one">
                                    <i class="fas fa-handshake"></i> Started One-to-One
                                </label>
                            </div>
                        </div>

                        <!-- Followed Up By -->
                        <div class="form-group">
                            <label for="followed_up_by">Followed Up By</label>
                            <input type="text" id="followed_up_by" name="followed_up_by" 
                                placeholder="Staff member name"
                                value="<?php echo htmlspecialchars($visitor['followed_up_by'] ?? ''); ?>"
                                class="form-control">
                        </div>

                        <!-- Update Report -->
                        <div class="form-group full-width">
                            <label for="update_report">Update / Report</label>
                            <textarea id="update_report" name="update_report" 
                                placeholder="Notes about contact and follow-up"
                                class="form-control"><?php echo htmlspecialchars($visitor['update_report'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="form-actions fade-in">
                    <a href="dashboard.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Update Information
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-focus on first form field
            const firstInput = document.querySelector('.form-control');
            if (firstInput) {
                firstInput.focus();
            }
            
            // Auto-hide success message after 5 seconds
            const successMessage = document.querySelector('.alert-success');
            if (successMessage) {
                setTimeout(() => {
                    successMessage.style.opacity = '0';
                    successMessage.style.transform = 'translateY(-10px)';
                    setTimeout(() => {
                        successMessage.style.display = 'none';
                    }, 300);
                }, 5000);
            }
            
            // Add visual feedback for radio buttons
            const radioButtons = document.querySelectorAll('input[type="radio"]');
            radioButtons.forEach(radio => {
                radio.addEventListener('change', function() {
                    // Remove active class from all labels in the same group
                    const name = this.getAttribute('name');
                    document.querySelectorAll(`input[name="${name}"]`).forEach(r => {
                        r.parentElement.classList.remove('active');
                    });
                    
                    // Add active class to selected label
                    this.parentElement.classList.add('active');
                });
            });
            
            // Add active class to initially selected radios
            document.querySelectorAll('input[type="radio"]:checked').forEach(radio => {
                radio.parentElement.classList.add('active');
            });
            
            // Add visual feedback for checkboxes
            const checkboxes = document.querySelectorAll('input[type="checkbox"]');
            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    if (this.checked) {
                        this.parentElement.classList.add('active');
                    } else {
                        this.parentElement.classList.remove('active');
                    }
                });
                
                // Add active class to initially checked checkboxes
                if (checkbox.checked) {
                    checkbox.parentElement.classList.add('active');
                }
            });
        });
    </script>
</body>
</html>