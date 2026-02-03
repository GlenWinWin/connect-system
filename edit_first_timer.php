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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Visitor - River of God Church</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/connect-form.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="church-info">
                <div class="logo">
                    <i class="fas fa-church"></i>
                </div>
                <h1 class="church-name">Edit Visitor Information</h1>
            </div>
            <div class="user-info">
                <a href="dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>

        <div class="card">
            <?php if ($success_message): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-grid">
                    <!-- Original Form Fields -->
                    <div class="form-group full-width">
                        <label class="required">I AM...</label>
                        <div class="radio-group">
                            <div class="radio-option">
                                <input type="radio" id="looking" name="iam" value="LOOKING FOR A CHURCH" 
                                    <?php echo ($visitor['iam'] === 'LOOKING FOR A CHURCH') ? 'checked' : ''; ?> required>
                                <label for="looking">LOOKING FOR A CHURCH</label>
                            </div>
                            <div class="radio-option">
                                <input type="radio" id="visitor" name="iam" value="VISITOR"
                                    <?php echo ($visitor['iam'] === 'VISITOR') ? 'checked' : ''; ?> required>
                                <label for="visitor">VISITOR</label>
                            </div>
                            <div class="radio-option">
                                <input type="radio" id="other-church" name="iam" value="FROM OTHER CHURCH"
                                    <?php echo ($visitor['iam'] === 'FROM OTHER CHURCH') ? 'checked' : ''; ?> required>
                                <label for="other-church">FROM OTHER CHURCH</label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="fullname" class="required">FULL NAME</label>
                        <input type="text" id="fullname" name="fullname" placeholder="Enter your full name"
                            value="<?php echo htmlspecialchars($visitor['fullname']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="contact" class="required">CONTACT NO</label>
                        <input type="tel" id="contact" name="contact" placeholder="Enter your phone number"
                            value="<?php echo htmlspecialchars($visitor['contact']); ?>" required>
                    </div>

                    <div class="form-group full-width">
                        <label class="required">AGE GROUP</label>
                        <div class="radio-group">
                            <?php
                            $age_groups = [
                                'River Youth (13 to 19 years old)',
                                'Young Adult (20 to 35 years old)',
                                'River Men (36 to 50 years old)',
                                'River Women (36 to 50 years old)',
                                'Seasoned (51 years old and above)'
                            ];
                            foreach ($age_groups as $age_group): ?>
                                <div class="radio-option">
                                    <input type="radio" id="age-<?php echo str_replace(' ', '-', strtolower($age_group)); ?>" 
                                           name="age" value="<?php echo htmlspecialchars($age_group); ?>"
                                        <?php echo ($visitor['age_group'] === $age_group) ? 'checked' : ''; ?> required>
                                    <label for="age-<?php echo str_replace(' ', '-', strtolower($age_group)); ?>">
                                        <?php echo htmlspecialchars($age_group); ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="messenger" class="required">MESSENGER</label>
                        <input type="text" id="messenger" name="messenger" placeholder="Enter your messenger ID"
                            value="<?php echo htmlspecialchars($visitor['messenger']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="required">WHICH SERVICE DID YOU ATTEND?</label>
                        <div class="radio-group">
                            <?php
                            $services = ['10AM', '1PM', '4PM'];
                            foreach ($services as $service): ?>
                                <div class="radio-option">
                                    <input type="radio" id="service-<?php echo strtolower($service); ?>" 
                                           name="service" value="<?php echo htmlspecialchars($service); ?>"
                                        <?php echo ($visitor['service_attended'] === $service) ? 'checked' : ''; ?> required>
                                    <label for="service-<?php echo strtolower($service); ?>"><?php echo htmlspecialchars($service); ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="form-group full-width">
                        <label for="invited-by">IF YOU ARE INVITED BY SOMEONE, PLEASE TYPE THEIR NAME</label>
                        <input type="text" id="invited-by" name="invited-by" placeholder="Optional"
                            value="<?php echo htmlspecialchars($visitor['invited_by']); ?>">
                    </div>

                    <div class="form-group">
                        <label class="required">DO YOU WANT TO JOIN A LIFEGROUP?</label>
                        <div class="radio-group">
                            <div class="radio-option">
                                <input type="radio" id="lifegroup-yes" name="lifegroup" value="YES"
                                    <?php echo ($visitor['lifegroup'] === 'YES') ? 'checked' : ''; ?> required>
                                <label for="lifegroup-yes">YES</label>
                            </div>
                            <div class="radio-option">
                                <input type="radio" id="lifegroup-no" name="lifegroup" value="NO"
                                    <?php echo ($visitor['lifegroup'] === 'NO') ? 'checked' : ''; ?> required>
                                <label for="lifegroup-no">NO</label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="connected-with" class="required">CONNECTED WITH</label>
                        <input type="text" id="connected-with" name="connected-with" placeholder="How did you connect with us?"
                            value="<?php echo htmlspecialchars($visitor['connected_with']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="approached-by" class="required">APPROACHED BY (Connect Member)</label>
                        <input type="text" id="approached-by" name="approached-by" placeholder="Name of the member who approached you"
                            value="<?php echo htmlspecialchars($visitor['approached_by']); ?>" required>
                    </div>

                                        <!-- Follow-up Section -->
                    <div class="form-group full-width">
                        <div class="follow-up-section">
                            <h3 class="follow-up-title">Follow-up Information</h3>
                            
                            <div class="form-grid">
                                <div class="form-group">
                                    <div class="checkbox-group">
                                        <input type="checkbox" id="texted_already" name="texted_already" 
                                            <?php echo $visitor['texted_already'] ? 'checked' : ''; ?>>
                                        <label for="texted_already">Texted Already</label>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <div class="checkbox-group">
                                        <input type="checkbox" id="started_one2one" name="started_one2one"
                                            <?php echo $visitor['started_one2one'] ? 'checked' : ''; ?>>
                                        <label for="started_one2one">Started One-to-One</label>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="followed_up_by">Followed Up By</label>
                                    <input type="text" id="followed_up_by" name="followed_up_by" 
                                        placeholder="Staff member name"
                                        value="<?php echo htmlspecialchars($visitor['followed_up_by'] ?? ''); ?>">
                                </div>

                                <div class="form-group full-width">
                                    <label for="update_report">Update / Report</label>
                                    <textarea id="update_report" name="update_report" 
                                        placeholder="Notes about contact and follow-up"><?php echo htmlspecialchars($visitor['update_report'] ?? ''); ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <div style="display: flex; gap: 15px; margin-top: 30px;">
                    <button type="submit" class="btn" style="flex: 1;">
                        <i class="fas fa-save"></i> Update Information
                    </button>
                    <a href="dashboard.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>