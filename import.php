<?php
// Database configuration - use the same as your connect-form.php
require_once 'config/database.php';

$successCount = 0;
$errorCount = 0;

try {
    // Check if form was submitted and file was uploaded
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
        
        $tmpName = $_FILES['csv_file']['tmp_name'];
        
        // Process CSV file
        if (($handle = fopen($tmpName, 'r')) !== FALSE) {
            // Skip header row
            fgetcsv($handle);
            
            // Prepare insert statement - matching your form's structure
            $stmt = $pdo->prepare("
                INSERT INTO first_timers 
                (iam, fullname, contact, age_group, messenger, service_attended, 
                 invited_by, lifegroup, connected_with, approached_by,
                 texted_already, update_report, followed_up_by, started_one2one,
                 created_at, updated_at) 
                VALUES 
                (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            while (($data = fgetcsv($handle)) !== FALSE) {
                // Skip empty rows
                if (empty($data[0]) || empty($data[1])) {
                    continue;
                }
                
                try {
                    // Map CSV columns to database fields
                    $timestamp = $data[0]; // Column A: Timestamp
                    $fullname = $data[1]; // Column B: FULL NAME
                    $age_group = $data[2]; // Column C: AGE GROUP
                    $city = $data[3]; // Column D: City
                    $contact = $data[4]; // Column E: Contact Number
                    $iam = $data[5]; // Column F: I AM..
                    $service_attended = $data[6]; // Column G: WHICH SERVICE DID YOU ATTEND?
                    $invited_by = $data[7]; // Column H: IF YOU ARE INVITED BY SOMEONE...
                    $approached_by = $data[8]; // Column I: APPROACHED BY
                    
                    // Transformations to match your form's values
                    // 1. Convert service time: handle both "3PM" and "3PM SERVICE" formats
                    $service_attended = transformServiceTime($service_attended);
                    
                    // 2. Transform I AM values to match your form options
                    // "FROM OTHER CHURCH" should also be "VISITOR"
                    $iam = transformIAmValue($iam);
                    
                    // 3. Transform age groups to match your form's format
                    $age_group = transformAgeGroup($age_group);
                    
                    // 4. Set default values for other fields (like in your form)
                    $messenger = ''; // Not in CSV, set empty like your form
                    $lifegroup = 'NO'; // Default value as in your form
                    $connected_with = ''; // Not in CSV, set empty
                    
                    // 5. Set follow-up fields to default values (like your form)
                    $texted_already = 0;
                    $update_report = '';
                    $followed_up_by = '';
                    $started_one2one = 0;
                    
                    // 6. Use timestamp for created_at and updated_at
                    $created_at = date('Y-m-d H:i:s', strtotime($timestamp));
                    $updated_at = $created_at;
                    
                    // Execute insert
                    $stmt->execute([
                        $iam,
                        $fullname,
                        $contact,
                        $age_group,
                        $messenger,
                        $service_attended,
                        $invited_by,
                        $lifegroup,
                        $connected_with,
                        $approached_by,
                        $texted_already,
                        $update_report,
                        $followed_up_by,
                        $started_one2one,
                        $created_at,
                        $updated_at
                    ]);
                    
                    $successCount++;
                    
                } catch (Exception $e) {
                    $errorCount++;
                    error_log("Error importing row for $fullname: " . $e->getMessage());
                    continue;
                }
            }
            
            fclose($handle);
            
        } else {
            throw new Exception("Could not open uploaded file.");
        }
    }
    
} catch (Exception $e) {
    $error_message = $e->getMessage();
}

/**
 * Transform service time from CSV format to form format
 */
function transformServiceTime($serviceTime) {
    $serviceTime = trim($serviceTime);
    
    // Handle different formats found in CSV
    $transformations = [
        '3PM SERVICE' => '1PM',
        '3PM' => '1PM',
        '10AM SERVICE' => '10AM',
        '10AM' => '10AM',
        '1PM SERVICE' => '1PM',
        '1PM' => '1PM',
        '4PM SERVICE' => '4PM',
        '4PM' => '4PM'
    ];
    
    return $transformations[$serviceTime] ?? $serviceTime;
}

/**
 * Transform I AM values from CSV format to form format
 * "FROM OTHER CHURCH" should also be "VISITOR"
 */
function transformIAmValue($iam) {
    $iam = trim($iam);
    
    $transformations = [
        'First Timer' => 'LOOKING FOR A CHURCH',
        'Visitor' => 'VISITOR',
        'VISITOR' => 'VISITOR',
        'FROM OTHER CHURCH' => 'VISITOR', // Changed to VISITOR
        'LOOKING FOR A CHURCH' => 'LOOKING FOR A CHURCH',
    ];
    
    return $transformations[$iam] ?? $iam;
}

/**
 * Transform age group from CSV format to form format
 */
function transformAgeGroup($csvAgeGroup) {
    $mapping = [
        'XO (13 to 19 years old)' => 'River Youth (13 to 19 years old)',
        'Young Adults (20 to 35 years old)' => 'Young Adult (20 to 35 years old)',
        'Men (36 to 50 years old)' => 'River Men (36 to 50 years old)',
        'Women (36 to 50 years old)' => 'River Women (36 to 50 years old)',
        'Seasoned (51 years old and above)' => 'Seasoned (51 years old and above)',
        'River Youth (13 to 19 years old)' => 'River Youth (13 to 19 years old)',
        'Young Adult (20 to 35 years old)' => 'Young Adult (20 to 35 years old)',
        'River Men (36 to 50 years old)' => 'River Men (36 to 50 years old)',
        'River Women (36 to 50 years old)' => 'River Women (36 to 50 years old)'
    ];
    
    return $mapping[$csvAgeGroup] ?? $csvAgeGroup;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Data - River of God Church</title>
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
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding: 20px;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }

        .church-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .logo {
            font-size: 2.5rem;
            color: var(--primary);
        }

        .church-name {
            font-size: 1.8rem;
            color: var(--dark);
            font-weight: 700;
        }

        .btn {
            padding: 12px 24px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: var(--border-radius);
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: var(--secondary);
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

        input[type="file"] {
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

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 20px;
                text-align: center;
            }
            
            .church-info {
                flex-direction: column;
                gap: 15px;
            }
            
            .church-name {
                font-size: 1.5rem;
            }
            
            .btn {
                padding: 10px 20px;
                font-size: 0.9rem;
            }
            
            .card {
                padding: 20px 15px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="church-info">
                <div class="logo">
                    <i class="fas fa-church"></i>
                </div>
                <h1 class="church-name">Import Data</h1>
            </div>
            <div class="user-info">
                <a href="dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>

        <div class="card">
            <!-- Your existing import form content here -->
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="csv_file">Select CSV File</label>
                    <input type="file" id="csv_file" name="csv_file" accept=".csv" required>
                </div>
                <button type="submit" class="btn">
                    <i class="fas fa-upload"></i> Import Data
                </button>
            </form>
            
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-error"><?php echo $error_message; ?></div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>