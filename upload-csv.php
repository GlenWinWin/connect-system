<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
requireAuth();

$success_count = 0;
$error_count = 0;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    try {
        $csv_file = $_FILES['csv_file'];
        
        // Check for upload errors
        if ($csv_file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('File upload failed with error code: ' . $csv_file['error']);
        }
        
        // Check file type
        $file_type = mime_content_type($csv_file['tmp_name']);
        $allowed_types = ['text/csv', 'text/plain', 'application/csv', 'application/vnd.ms-excel', 'application/octet-stream'];
        
        if (!in_array($file_type, $allowed_types)) {
            throw new Exception('Invalid file type. Please upload a CSV file. Detected type: ' . $file_type);
        }
        
        // Read CSV file
        if (($handle = fopen($csv_file['tmp_name'], 'r')) !== FALSE) {
            $header = fgetcsv($handle); // Get header row
            
            // Process each row
            $row_number = 1;
            while (($data = fgetcsv($handle)) !== FALSE) {
                $row_number++;
                
                try {
                    // Map CSV columns to database fields
                    // CSV format: Timestamp, FULL NAME:, AGE GROUP:, I AM.., WHICH SERVICE..., INVITED BY..., APPROACHED BY..., CONTACT NO:, LIFEGROUP?, MESSENGER:, CONNECTED WITH:
                    
                    if (count($data) < 11) {
                        throw new Exception("Row $row_number: Insufficient columns (" . count($data) . " columns found)");
                    }
                    
                    $timestamp = trim($data[0] ?? '');
                    $fullname = trim($data[1] ?? '');
                    $age_group = trim($data[2] ?? '');
                    $iam = trim($data[3] ?? '');
                    $service_attended = trim($data[4] ?? '');
                    $invited_by = trim($data[5] ?? '') ?: null;
                    $approached_by = trim($data[6] ?? '');
                    $contact = trim($data[7] ?? '');
                    $lifegroup = strtoupper(trim($data[8] ?? ''));
                    $messenger = trim($data[9] ?? '');
                    $connected_with = trim($data[10] ?? '');
                    
                    // Skip empty rows
                    if (empty($fullname) && empty($contact)) {
                        continue;
                    }
                    
                    // Determine gender based on age group
                    $gender = 0; // Default to female (0)
                    
                    // Check if male based on age group and other indicators
                    if (strpos($age_group, 'River Men') !== false || 
                        strpos($age_group, 'Seasoned') !== false) {
                        // For Seasoned, check name patterns or make educated guess
                        $male_indicators = ['Mr.', 'Mr ', 'Brother', 'Father', 'Uncle', 'Grandpa', 'Grandfather', 'Son'];
                        $name_lower = strtolower($fullname);
                        $is_male = false;
                        
                        foreach ($male_indicators as $indicator) {
                            if (stripos($fullname, $indicator) !== false) {
                                $is_male = true;
                                break;
                            }
                        }
                        
                        if ($is_male) {
                            $gender = 1;
                        } else if (strpos($age_group, 'River Men') !== false) {
                            $gender = 1; // River Men is definitely male
                        }
                    } else if (strpos($age_group, 'River Women') !== false) {
                        $gender = 0; // Female
                    } else if (strpos($age_group, 'River Youth') !== false || 
                               strpos($age_group, 'Young Adult') !== false) {
                        // For Youth and Young Adult, we need to guess or set default
                        // Could add more sophisticated logic here
                        $gender = 0; // Default to female for now
                    }
                    
                    // Standardize age group values
                    $standard_age_groups = [
                        'Youth' => ['youth', 'river youth'],
                        'Young Adult' => ['young adult'],
                        'River Men' => ['river men'],
                        'River Women' => ['river women'],
                        'Seasoned' => ['seasoned']
                    ];
                    
                    $found_age_group = 'Youth'; // Default
                    foreach ($standard_age_groups as $standard => $variations) {
                        foreach ($variations as $variation) {
                            if (stripos($age_group, $variation) !== false) {
                                $found_age_group = $standard;
                                break 2;
                            }
                        }
                    }
                    
                    // Standardize lifegroup value
                    if ($lifegroup === 'Y' || $lifegroup === 'YES' || $lifegroup === '1') {
                        $lifegroup = 'YES';
                    } else {
                        $lifegroup = 'NO';
                    }
                    
                    // Follow-up fields (default values)
                    $texted_already = 0;
                    $update_report = '';
                    $followed_up_by = '';
                    $started_one2one = 0;
                    
                    // Set created_at from timestamp if available
                    $created_at = date('Y-m-d H:i:s');
                    if (!empty($timestamp)) {
                        // Try different date formats
                        $formats = ['n/j/Y H:i', 'm/d/Y H:i', 'Y-m-d H:i:s', 'd-m-Y H:i'];
                        $parsed_time = false;
                        
                        foreach ($formats as $format) {
                            $date = DateTime::createFromFormat($format, $timestamp);
                            if ($date !== false) {
                                $parsed_time = $date->getTimestamp();
                                break;
                            }
                        }
                        
                        if ($parsed_time === false) {
                            // Try strtotime as fallback
                            $parsed_time = strtotime($timestamp);
                        }
                        
                        if ($parsed_time !== false) {
                            $created_at = date('Y-m-d H:i:s', $parsed_time);
                        }
                    }
                    
                    // Check if record already exists (by name and contact)
                    $check_sql = "SELECT id FROM first_timers WHERE fullname = ? AND contact = ? LIMIT 1";
                    $check_stmt = $pdo->prepare($check_sql);
                    $check_stmt->execute([$fullname, $contact]);
                    
                    if ($check_stmt->fetch()) {
                        throw new Exception("Record already exists for '$fullname' with contact '$contact'");
                    }
                    
                    // Insert into database
                    $sql = "INSERT INTO first_timers (
                        iam, fullname, gender, contact, age_group, messenger, 
                        service_attended, invited_by, lifegroup, connected_with, 
                        approached_by, texted_already, update_report, followed_up_by, 
                        started_one2one, created_at, updated_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        $iam, $fullname, $gender, $contact, $found_age_group, $messenger,
                        $service_attended, $invited_by, $lifegroup, $connected_with,
                        $approached_by, $texted_already, $update_report, $followed_up_by,
                        $started_one2one, $created_at, $created_at
                    ]);
                    
                    $success_count++;
                    
                } catch (Exception $row_error) {
                    $error_count++;
                    $errors[] = "Row $row_number: " . $row_error->getMessage();
                }
            }
            fclose($handle);
            
            if ($success_count > 0) {
                $_SESSION['upload_success'] = "Successfully imported $success_count records.";
            }
            if ($error_count > 0) {
                $_SESSION['upload_errors'] = $errors;
                $_SESSION['upload_error_count'] = $error_count;
            }
            
        } else {
            throw new Exception('Could not open CSV file.');
        }
        
    } catch (Exception $e) {
        $_SESSION['upload_error'] = $e->getMessage();
    }
    
    header('Location: dashboard.php');
    exit;
}

// If not POST request, redirect to dashboard
header('Location: dashboard.php');
exit;
?>