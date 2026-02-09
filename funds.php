<?php
// funds.php
require_once 'config/database.php';
require_once 'includes/auth.php';
requireAuth();

// Password protection for this page
$page_password = 'gxqyPJtjnZHgUBw';

if (!isset($_SESSION['funds_authenticated']) || $_SESSION['funds_authenticated'] !== true) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
        if ($_POST['password'] === $page_password) {
            $_SESSION['funds_authenticated'] = true;
            // Redirect to clear POST data and show funds page
            echo "<script>window.location.href = 'funds.php';</script>";
            exit();
        } else {
            $error = "Incorrect password.";
        }
    }
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Access Restricted - River of God Church</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="css/main.css">
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            
            body {
                font-family: 'Inter', sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                display: flex;
                justify-content: center;
                align-items: center;
                padding: 20px;
            }
            
            .auth-card {
                background: rgba(255, 255, 255, 0.95);
                backdrop-filter: blur(10px);
                border-radius: 20px;
                padding: 40px 30px;
                width: 100%;
                max-width: 400px;
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
                text-align: center;
                animation: fadeIn 0.6s ease-out;
            }
            
            @keyframes fadeIn {
                from { opacity: 0; transform: translateY(20px); }
                to { opacity: 1; transform: translateY(0); }
            }
            
            .lock-icon {
                font-size: 60px;
                color: #667eea;
                margin-bottom: 20px;
                display: block;
            }
            
            .auth-title {
                font-size: 24px;
                font-weight: 700;
                color: #1a1a2e;
                margin-bottom: 8px;
            }
            
            .auth-subtitle {
                color: #666;
                font-size: 14px;
                margin-bottom: 30px;
                line-height: 1.5;
            }
            
            .input-group {
                position: relative;
                margin-bottom: 20px;
            }
            
            .password-input {
                width: 100%;
                padding: 16px 20px 16px 50px;
                border: 2px solid #e1e5e9;
                border-radius: 12px;
                font-size: 16px;
                font-family: 'Inter', sans-serif;
                transition: all 0.3s ease;
                background: white;
            }
            
            .password-input:focus {
                outline: none;
                border-color: #667eea;
                box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
            }
            
            .input-icon {
                position: absolute;
                left: 18px;
                top: 50%;
                transform: translateY(-50%);
                color: #94a3b8;
                font-size: 18px;
            }
            
            .btn-auth {
                width: 100%;
                padding: 16px;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                border: none;
                border-radius: 12px;
                font-size: 16px;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.3s ease;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 10px;
            }
            
            .btn-auth:hover {
                transform: translateY(-2px);
                box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
            }
            
            .alert-error {
                background: linear-gradient(135deg, #fee 0%, #fdd 100%);
                border: 2px solid #fcc;
                color: #c00;
                padding: 15px;
                border-radius: 12px;
                margin-bottom: 25px;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
                animation: shake 0.5s ease-in-out;
            }
            
            @keyframes shake {
                0%, 100% { transform: translateX(0); }
                25% { transform: translateX(-5px); }
                75% { transform: translateX(5px); }
            }
        </style>
    </head>
    <body>
        <div class="auth-card">
            <i class="fas fa-lock lock-icon"></i>
            <h1 class="auth-title">Funds Dashboard</h1>
            <p class="auth-subtitle">Enter password to access contribution records</p>
            
            <?php if (isset($error)): ?>
                <div class="alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="input-group">
                    <i class="fas fa-key input-icon"></i>
                    <input type="password" name="password" class="password-input" placeholder="Enter access password" required autofocus>
                </div>
                <button type="submit" class="btn-auth">
                    <i class="fas fa-unlock"></i>
                    Access Funds Dashboard
                </button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit();
}

// Handle form submission for individual person updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'update_person_contributions') {
            try {
                $pdo->beginTransaction();
                
                $person_id = $_POST['person_id'];
                
                // First, delete all contributions for this person
                $stmt = $pdo->prepare("DELETE FROM contributions WHERE person_id = ? AND year = ?");
                $stmt->execute([$person_id, date('Y')]);
                
                // Insert new contributions
                $month_data = $_POST['contributions'];
                foreach ($month_data as $month_index => $amount) {
                    $amount = floatval($amount);
                    if ($amount > 0) {
                        $stmt = $pdo->prepare("INSERT INTO contributions (person_id, month, year, amount, created_at) 
                                              VALUES (?, ?, ?, ?, NOW())");
                        $stmt->execute([$person_id, $month_index + 1, date('Y'), $amount]);
                    }
                }
                
                $pdo->commit();
                $success = "Contributions updated successfully!";
                
                // Redirect to avoid form resubmission
                header("Location: funds.php?success=" . urlencode($success));
                exit();
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = "Error saving contributions: " . $e->getMessage();
            }
        }
    }
}

// Fetch persons in exact order as specified
$person_groups = [
    'Team Leaders' => ['Glenwin', 'Neri', 'Irene', 'Judy', 'Karmela'],
    'Members' => ['Bojie', 'Roland', 'Reyban', 'Niño', 'Catherine', 'Mayla', 'Rashil', 
                      'Noemie', 'Melanie', 'Frank', 'Nelvin', 'Ecleo', 'Kim', 'Maricris', 
                      'Riza', 'Mary Anne', 'Franie', 'Belen', 'Cholly', 'Leni', 'Gerza', 'Llosis Nicole']
];

$persons = [];
foreach ($person_groups as $group => $names) {
    foreach ($names as $name) {
        $stmt = $pdo->prepare("SELECT id FROM persons WHERE name = ?");
        $stmt->execute([$name]);
        $person = $stmt->fetch();
        
        if (!$person) {
            $stmt = $pdo->prepare("INSERT INTO persons (name, group_name, created_at) VALUES (?, ?, NOW())");
            $stmt->execute([$name, $group]);
            $person_id = $pdo->lastInsertId();
        } else {
            $person_id = $person['id'];
        }
        
        $persons[] = ['id' => $person_id, 'name' => $name, 'group' => $group];
    }
}

// Get current year and months
$current_year = date('Y');
$months = ['January', 'February', 'March', 'April', 'May', 'June', 
          'July', 'August', 'September', 'October', 'November', 'December'];
$months_short = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

// Fetch all contributions
$contributions = [];
$person_totals = [];
$month_totals = array_fill(0, 12, 0);
$overall_total = 0;

foreach ($persons as $person) {
    $stmt = $pdo->prepare("SELECT month, amount FROM contributions WHERE person_id = ? AND year = ?");
    $stmt->execute([$person['id'], $current_year]);
    $person_contributions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $person_total = 0;
    $month_data = array_fill(0, 12, 0);
    
    foreach ($person_contributions as $contrib) {
        $month_index = $contrib['month'] - 1;
        $amount = floatval($contrib['amount']);
        $month_data[$month_index] = $amount;
        $person_total += $amount;
        $month_totals[$month_index] += $amount;
    }
    
    $contributions[$person['id']] = $month_data;
    $person_totals[$person['id']] = $person_total;
    $overall_total += $person_total;
}

// Check for success message from redirect
if (isset($_GET['success'])) {
    $success = $_GET['success'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Funds Dashboard - River of God Church</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/main.css">
    <style>
        :root {
            --primary: #6366f1;
            --primary-light: #818cf8;
            --primary-dark: #4f46e5;
            --secondary: #7c3aed;
            --success: #10b981;
            --info: #0ea5e9;
            --warning: #f59e0b;
            --danger: #ef4444;
            --light: #f8fafc;
            --dark: #1e293b;
            --gray: #64748b;
            --gray-light: #e2e8f0;
            --border: #e2e8f0;
            --border-radius-sm: 8px;
            --border-radius-md: 12px;
            --border-radius-lg: 16px;
            --border-radius-xl: 20px;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.08);
            --shadow-md: 0 4px 12px rgba(0,0,0,0.05);
            --shadow-lg: 0 10px 25px rgba(0,0,0,0.1);
            --shadow-xl: 0 20px 40px rgba(0,0,0,0.15);
            --gradient-primary: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: #f8fafc;
            color: var(--dark);
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
        }
        
        .app-container {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        /* Header */
        .app-header {
            background: var(--gradient-primary);
            color: white;
            padding: 20px 0;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: var(--shadow-lg);
        }
        
        .header-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
        }
        
        .logo-section {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        
        .logo-icon {
            font-size: 28px;
            background: rgba(255, 255, 255, 0.2);
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(10px);
        }
        
        .header-title h1 {
            color: white;
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 4px;
        }
        
        .header-title p {
            font-size: 14px;
            opacity: 0.9;
            font-weight: 400;
        }
        
        .header-actions {
            display: flex;
            gap: 12px;
            align-items: center;
        }
        
        .btn {
            padding: 10px 20px;
            border-radius: var(--border-radius-sm);
            font-family: 'Inter', sans-serif;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            text-decoration: none;
            outline: none;
        }
        
        .btn-primary {
            background: white;
            color: var(--primary);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        
        .btn-secondary {
            background: rgba(255, 255, 255, 0.15);
            color: white;
            backdrop-filter: blur(10px);
        }
        
        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: translateY(-1px);
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            max-width: 1400px;
            width: 100%;
            margin: 0 auto;
            padding: 24px;
        }
        
        /* Summary Cards */
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }
        
        .summary-card {
            background: white;
            border-radius: var(--border-radius-lg);
            padding: 24px;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }
        
        .summary-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-primary);
        }
        
        .summary-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-xl);
        }
        
        .summary-icon {
            font-size: 28px;
            margin-bottom: 16px;
            width: 56px;
            height: 56px;
            border-radius: var(--border-radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }
        
        .summary-card:nth-child(1) .summary-icon {
            background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
        }
        
        .summary-card:nth-child(2) .summary-icon {
            background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);
        }
        
        .summary-card:nth-child(3) .summary-icon {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }
        
        .summary-card:nth-child(4) .summary-icon {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        }
        
        .summary-number {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 8px;
            color: var(--dark);
            font-feature-settings: "tnum";
        }
        
        .summary-label {
            font-size: 14px;
            color: var(--gray);
            font-weight: 500;
        }
        
        /* Members Container */
        .members-container {
            background: white;
            border-radius: var(--border-radius-lg);
            padding: 32px;
            box-shadow: var(--shadow-md);
            margin-bottom: 32px;
            border: 1px solid var(--border);
        }
        
        .section-title {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 28px;
            font-size: 20px;
            font-weight: 700;
            color: var(--dark);
        }
        
        .section-title i {
            color: var(--primary);
            font-size: 22px;
        }
        
        .group-section {
            margin-bottom: 36px;
        }
        
        .group-section:last-child {
            margin-bottom: 0;
        }
        
        .group-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 2px solid var(--gray-light);
        }
        
        .group-header h3 {
            font-size: 18px;
            font-weight: 600;
            color: var(--primary-dark);
        }
        
        .group-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #eef2ff 0%, #e0e7ff 100%);
            border-radius: var(--border-radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
            font-size: 18px;
        }
        
        .members-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 20px;
        }
        
        @media (max-width: 768px) {
            .members-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .member-card {
            background: var(--light);
            border-radius: var(--border-radius-md);
            padding: 20px;
            border: 1px solid var(--border);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }
        
        .member-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            bottom: 0;
            width: 4px;
            background: var(--primary-light);
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .member-card:hover::before {
            opacity: 1;
        }
        
        .member-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
            background: white;
            border-color: var(--primary-light);
        }
        
        .member-card.active {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            border-color: var(--info);
        }
        
        .member-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 16px;
        }
        
        .member-name {
            font-size: 17px;
            font-weight: 600;
            color: var(--dark);
        }
        
        .member-status {
            font-size: 11px;
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .member-status.active {
            background: #dcfce7;
            color: #166534;
        }
        
        .member-status.inactive {
            background: #fef3c7;
            color: #92400e;
        }
        
        .member-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-bottom: 16px;
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-value {
            font-size: 18px;
            font-weight: 700;
            color: var(--primary-dark);
            margin-bottom: 4px;
            font-feature-settings: "tnum";
        }
        
        .stat-label {
            font-size: 12px;
            color: var(--gray);
            font-weight: 500;
        }
        
        .member-actions {
            display: flex;
            gap: 12px;
        }
        
        .btn-sm {
            padding: 10px 16px;
            font-size: 13px;
            flex: 1;
            justify-content: center;
            border-radius: var(--border-radius-sm);
        }
        
        .btn-outline {
            background: transparent;
            border: 2px solid var(--border);
            color: var(--gray);
        }
        
        .btn-outline:hover {
            border-color: var(--primary);
            color: var(--primary);
            background: rgba(99, 102, 241, 0.05);
        }
        
        /* Modal Styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(15, 23, 42, 0.7);
            backdrop-filter: blur(8px);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 2000;
            padding: 20px;
            animation: fadeIn 0.3s ease;
        }
        
        .modal-overlay.active {
            display: flex;
        }
        
        .modal-content {
            background: white;
            border-radius: var(--border-radius-xl);
            width: 100%;
            max-width: 800px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: var(--shadow-xl);
            animation: slideUp 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(40px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .modal-header {
            background: var(--gradient-primary);
            color: white;
            padding: 28px;
            border-radius: var(--border-radius-xl) var(--border-radius-xl) 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        
        .modal-header h2 {
            font-size: 22px;
            font-weight: 700;
        }
        
        .close-modal {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            flex-shrink: 0;
        }
        
        .close-modal:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: rotate(90deg);
        }
        
        .modal-body {
            padding: 32px;
        }
        
        .member-info {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 32px;
            padding-bottom: 24px;
            border-bottom: 2px solid var(--gray-light);
        }
        
        .member-avatar {
            width: 80px;
            height: 80px;
            background: var(--gradient-primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 28px;
            font-weight: 600;
            flex-shrink: 0;
        }
        
        .member-details h3 {
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 6px;
            color: var(--dark);
        }
        
        .member-group {
            font-size: 14px;
            color: var(--gray);
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        /* Monthly Contributions in Modal */
        .months-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            gap: 16px;
            margin-bottom: 32px;
        }
        
        .month-input-group {
            background: var(--light);
            border-radius: var(--border-radius-md);
            padding: 16px;
            border: 1px solid var(--border);
            transition: all 0.3s ease;
        }
        
        .month-input-group:focus-within {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
            background: white;
            transform: translateY(-2px);
        }
        
        .month-label {
            display: block;
            margin-bottom: 10px;
            font-size: 14px;
            font-weight: 600;
            color: var(--dark);
        }
        
        .modal-input {
            width: 100%;
            padding: 12px;
            border: 2px solid var(--border);
            border-radius: var(--border-radius-sm);
            font-family: 'Inter', sans-serif;
            font-size: 16px;
            font-weight: 500;
            text-align: center;
            transition: all 0.3s ease;
            background: white;
        }
        
        .modal-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }
        
        .modal-input.has-value {
            background: #f0fdf4;
            border-color: #22c55e;
            color: #166534;
            font-weight: 600;
        }
        
        .modal-summary {
            background: #f8fafc;
            border-radius: var(--border-radius-lg);
            padding: 24px;
            margin-bottom: 32px;
            border: 1px solid var(--border);
        }
        
        .modal-total {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .total-label {
            font-size: 18px;
            font-weight: 600;
            color: var(--dark);
        }
        
        .total-amount {
            font-size: 32px;
            font-weight: 700;
            color: var(--primary);
            font-feature-settings: "tnum";
        }
        
        .modal-actions {
            display: flex;
            gap: 16px;
        }
        
        .btn-block {
            flex: 1;
            justify-content: center;
            padding: 16px;
            font-size: 16px;
            font-weight: 600;
        }
        
        /* Notification */
        .notification {
            position: fixed;
            top: 24px;
            right: 24px;
            padding: 16px 24px;
            border-radius: var(--border-radius-md);
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: var(--shadow-xl);
            z-index: 9999;
            animation: slideIn 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            max-width: 400px;
            backdrop-filter: blur(10px);
        }
        
        .notification.success {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.95) 0%, rgba(5, 150, 105, 0.95) 100%);
            color: white;
            border-left: 4px solid #059669;
        }
        
        .notification.error {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.95) 0%, rgba(220, 38, 38, 0.95) 100%);
            color: white;
            border-left: 4px solid #dc2626;
        }
        
        .notification.info {
            background: linear-gradient(135deg, rgba(14, 165, 233, 0.95) 0%, rgba(2, 132, 199, 0.95) 100%);
            color: white;
            border-left: 4px solid #0284c7;
        }
        
        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        /* Loading Animation */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Removed form-actions styles */
    </style>
</head>
<body>
    <div class="app-container">
        <!-- Header -->
        <header class="app-header">
            <div class="header-content">
                <div class="logo-section">
                    <div class="logo-icon">
                        <i class="fas fa-hand-holding-heart"></i>
                    </div>
                    <div class="header-title">
                        <h1>Contributions Dashboard</h1>
                        <p>Connect Ministry - <?php echo $current_year; ?></p>
                    </div>
                </div>
                <div class="header-actions">
                    <a href="dashboard.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                </div>
            </div>
        </header>

        <!-- Notifications -->
        <?php if (isset($success)): ?>
            <div class="notification success" id="successNotification">
                <i class="fas fa-check-circle"></i>
                <span><?php echo htmlspecialchars($success); ?></span>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="notification error" id="errorNotification">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <main class="main-content">
            <!-- Summary Section -->
            <div class="summary-grid">
                                <div class="summary-card">
                    <div class="summary-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="summary-number">₱<?php 

                        $total_as_a_whole = 12468;
                        echo number_format(($total_as_a_whole + $overall_total), 2);
                    ?></div>
                    <div class="summary-label">Total as of FEB 2026</div>
                </div>
                <div class="summary-card">
                    <div class="summary-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="summary-number"><?php echo count($persons); ?></div>
                    <div class="summary-label">Total Members</div>
                </div>
                <div class="summary-card">
                    <div class="summary-icon">
                        <i class="fas fa-hand-holding-usd"></i>
                    </div>
                    <div class="summary-number"><?php echo count(array_filter($person_totals, fn($total) => $total > 0)); ?></div>
                    <div class="summary-label">Active Contributors</div>
                </div>
                                <div class="summary-card">
                    <div class="summary-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="summary-number">₱<?php echo number_format($overall_total, 2); ?></div>
                    <div class="summary-label">Total Contributions for 2026</div>
                </div>
            </div>

            <div class="members-container">
                <h3 class="section-title">
                    <i class="fas fa-users"></i>
                    Members & Contributions
                </h3>
                
                <!-- Removed the main form since we're handling individual updates -->
                
                <?php 
                $current_group = '';
                $group_index = 0;
                foreach ($persons as $person): 
                    $person_total = $person_totals[$person['id']] ?? 0;
                    $is_active = $person_total > 0;
                    $current_contributions = $contributions[$person['id']] ?? array_fill(0, 12, 0);
                    $months_contributed = count(array_filter($current_contributions, fn($amount) => $amount > 0));
                    $average_per_month = $months_contributed > 0 ? $person_total / $months_contributed : 0;
                    
                    // Start new group section if needed
                    if ($current_group !== $person['group']):
                        // Close previous group if exists
                        if ($current_group !== ''):
                            echo '</div></div>';
                        endif;
                        
                        $current_group = $person['group'];
                        $group_index++;
                ?>
                        <div class="group-section">
                            <div class="group-header">
                                <div class="group-icon">
                                    <i class="fas fa-<?php echo $group_index === 1 ? 'crown' : 'user-friends'; ?>"></i>
                                </div>
                                <h3><?php echo htmlspecialchars($current_group); ?></h3>
                            </div>
                            <div class="members-grid">
                <?php endif; ?>
                        
                            <div class="member-card" data-person-id="<?php echo $person['id']; ?>" 
                                data-person-name="<?php echo htmlspecialchars($person['name']); ?>"
                                onclick="openMemberModal(<?php echo $person['id']; ?>, '<?php echo addslashes($person['name']); ?>', '<?php echo addslashes($person['group']); ?>')">
                                <div class="member-header">
                                    <div class="member-name"><?php echo htmlspecialchars($person['name']); ?></div>
                                    <div class="member-status <?php echo $is_active ? 'active' : 'inactive'; ?>">
                                        <?php echo $is_active ? 'Active' : 'Inactive'; ?>
                                    </div>
                                </div>
                                <div class="member-stats">
                                    <div class="stat-item">
                                        <div class="stat-value">₱<?php echo number_format($person_total, 2); ?></div>
                                        <div class="stat-label">Total</div>
                                    </div>
                                    <div class="stat-item">
                                        <div class="stat-value"><?php echo $months_contributed; ?></div>
                                        <div class="stat-label">Months</div>
                                    </div>
                                    <div class="stat-item">
                                        <div class="stat-value">₱<?php echo number_format($average_per_month, 2); ?></div>
                                        <div class="stat-label">Avg/Month</div>
                                    </div>
                                </div>
                                <div class="member-actions">
                                    <button type="button" class="btn btn-primary btn-sm" 
                                            onclick="event.stopPropagation(); openMemberModal(<?php echo $person['id']; ?>, '<?php echo addslashes($person['name']); ?>', '<?php echo addslashes($person['group']); ?>')">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <button type="button" class="btn btn-outline btn-sm" 
                                            onclick="event.stopPropagation(); viewMemberDetails(<?php echo $person['id']; ?>)">
                                        <i class="fas fa-eye"></i> View
                                    </button>
                                </div>
                            </div>
                
                <?php 
                    // Close last group section
                    if (next($persons) === false):
                        echo '</div></div>';
                    endif;
                    
                endforeach; 
                ?>
            </div>
        </main>
    </div>

    <!-- Member Modal -->
    <div class="modal-overlay" id="memberModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Contributions</h2>
                <button type="button" class="close-modal" onclick="closeMemberModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="member-info">
                    <div class="member-avatar" id="modalAvatar">JD</div>
                    <div class="member-details">
                        <h3 id="modalName">John Doe</h3>
                        <div class="member-group">
                            <i class="fas fa-users"></i>
                            <span id="modalGroup">Team Leader</span>
                        </div>
                    </div>
                </div>
                
                <form id="personContributionsForm" method="POST">
                    <input type="hidden" name="action" value="update_person_contributions">
                    <input type="hidden" name="person_id" id="modalPersonId">
                    
                    <div class="months-grid" id="monthsContainer">
                        <!-- Months will be dynamically added here -->
                    </div>
                    
                    <div class="modal-summary">
                        <div class="modal-total">
                            <div class="total-label">Total Contribution:</div>
                            <div class="total-amount" id="modalTotal">₱0.00</div>
                        </div>
                    </div>
                    
                    <div class="modal-actions">
                        <button type="submit" class="btn btn-primary btn-block" id="saveModalBtn">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Modal state
        let currentModalPersonId = null;
        let currentModalPersonName = null;
        let currentModalContributions = {};
        
        // Open member modal
        function openMemberModal(personId, personName, group) {
            currentModalPersonId = personId;
            currentModalPersonName = personName;
            
            // Update modal header
            document.getElementById('modalName').textContent = personName;
            document.getElementById('modalGroup').textContent = group;
            document.getElementById('modalPersonId').value = personId;
            
            // Set avatar initials
            const initials = personName.split(' ').map(n => n[0]).join('').toUpperCase();
            document.getElementById('modalAvatar').textContent = initials.substring(0, 2);
            
            // Load contributions
            const months = <?php echo json_encode($months); ?>;
            const contributions = <?php echo json_encode($contributions); ?>;
            
            let personContributions = contributions[personId] || Array(12).fill(0);
            currentModalContributions = [...personContributions];
            
            // Generate month inputs
            const monthsContainer = document.getElementById('monthsContainer');
            monthsContainer.innerHTML = '';
            
            let total = 0;
            
            months.forEach((month, index) => {
                const amount = personContributions[index] || 0;
                total += parseFloat(amount);
                
                const monthDiv = document.createElement('div');
                monthDiv.className = 'month-input-group';
                monthDiv.innerHTML = `
                    <label class="month-label">${month}</label>
                    <input type="number" 
                           name="contributions[${index}]"
                           class="modal-input ${amount > 0 ? 'has-value' : ''}"
                           value="${amount > 0 ? amount : ''}"
                           placeholder="0"
                           min="0"
                           step="10"
                           oninput="updateModalTotal(${index}, this)">
                `;
                monthsContainer.appendChild(monthDiv);
            });
            
            // Update total
            document.getElementById('modalTotal').textContent = `₱${total.toFixed(2)}`;
            
            // Show modal
            document.getElementById('memberModal').classList.add('active');
            
            // Add ESC key listener
            document.addEventListener('keydown', handleEscKey);
            
            // Focus on first input
            setTimeout(() => {
                const firstInput = monthsContainer.querySelector('input');
                if (firstInput) firstInput.focus();
            }, 100);
        }
        
        // Close member modal
        function closeMemberModal() {
            document.getElementById('memberModal').classList.remove('active');
            currentModalPersonId = null;
            currentModalPersonName = null;
            currentModalContributions = {};
            document.removeEventListener('keydown', handleEscKey);
        }
        
        // Handle ESC key
        function handleEscKey(event) {
            if (event.key === 'Escape') {
                closeMemberModal();
            }
        }
        
        // Update modal total
        function updateModalTotal(index, input) {
            const value = parseFloat(input.value) || 0;
            currentModalContributions[index] = value;
            
            // Update input style
            if (value > 0) {
                input.classList.add('has-value');
            } else {
                input.classList.remove('has-value');
            }
            
            // Calculate new total
            const total = currentModalContributions.reduce((sum, amount) => sum + (parseFloat(amount) || 0), 0);
            document.getElementById('modalTotal').textContent = `₱${total.toFixed(2)}`;
        }
        
        // Handle form submission
        document.getElementById('personContributionsForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Show loading state
            const saveBtn = document.getElementById('saveModalBtn');
            const originalText = saveBtn.innerHTML;
            saveBtn.innerHTML = '<span class="loading"></span> Saving...';
            saveBtn.disabled = true;
            
            // Submit the form
            fetch('funds.php', {
                method: 'POST',
                body: new FormData(this)
            })
            .then(response => {
                if (response.redirected) {
                    window.location.href = response.url;
                } else {
                    return response.text();
                }
            })
            .then(data => {
                // This will only execute if not redirected
                if (data) {
                    showNotification('Changes saved successfully!', 'success');
                    closeMemberModal();
                    // Refresh the page to show updated data
                    setTimeout(() => window.location.reload(), 1000);
                }
            })
            .catch(error => {
                showNotification('Error saving changes: ' + error.message, 'error');
            })
            .finally(() => {
                saveBtn.innerHTML = originalText;
                saveBtn.disabled = false;
            });
        });
        
        // View member details (read-only view)
        function viewMemberDetails(personId) {
            // Get member data
            const memberCard = document.querySelector(`.member-card[data-person-id="${personId}"]`);
            const personName = memberCard?.dataset.personName || 'Unknown';
            
            // Create a detailed view modal
            const contributions = <?php echo json_encode($contributions); ?>;
            const personContributions = contributions[personId] || Array(12).fill(0);
            const months = <?php echo json_encode($months); ?>;
            const monthsShort = <?php echo json_encode($months_short); ?>;
            
            let total = 0;
            let detailsHTML = `
                <div class="member-info">
                    <div class="member-avatar">${personName.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase()}</div>
                    <div class="member-details">
                        <h3>${personName}</h3>
                        <div class="member-group">
                            <i class="fas fa-chart-bar"></i>
                            <span>Contribution Details</span>
                        </div>
                    </div>
                </div>
                <div style="margin-top: 20px;">
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 12px;">
            `;
            
            personContributions.forEach((amount, index) => {
                if (amount > 0) {
                    total += parseFloat(amount);
                    detailsHTML += `
                        <div style="background: #f0fdf4; padding: 12px; border-radius: 8px; text-align: center; border: 2px solid #22c55e;">
                            <div style="font-size: 12px; color: #166534; font-weight: 600; margin-bottom: 4px;">${monthsShort[index]}</div>
                            <div style="font-size: 16px; font-weight: 700; color: #166534;">₱${parseFloat(amount).toFixed(2)}</div>
                        </div>
                    `;
                }
            });
            
            detailsHTML += `
                    </div>
                    <div style="margin-top: 24px; padding: 20px; background: #f8fafc; border-radius: 12px; border: 2px solid #e2e8f0;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div style="font-size: 18px; font-weight: 600; color: #1e293b;">Total Contribution:</div>
                            <div style="font-size: 32px; font-weight: 700; color: #6366f1;">₱${total.toFixed(2)}</div>
                        </div>
                    </div>
                </div>
            `;
            
            // Create modal for view
            const viewModal = document.createElement('div');
            viewModal.className = 'modal-overlay active';
            viewModal.innerHTML = `
                <div class="modal-content">
                    <div class="modal-header">
                        <h2>Contribution Details</h2>
                        <button type="button" class="close-modal" onclick="this.parentElement.parentElement.remove()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        ${detailsHTML}
                    </div>
                </div>
            `;
            
            document.body.appendChild(viewModal);
            
            // Close when clicking outside
            viewModal.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.remove();
                }
            });
            
            // Add ESC key listener
            const escHandler = function(e) {
                if (e.key === 'Escape') {
                    viewModal.remove();
                    document.removeEventListener('keydown', escHandler);
                }
            };
            document.addEventListener('keydown', escHandler);
        }
        
        function showNotification(message, type = 'info') {
            // Remove existing notifications
            document.querySelectorAll('.notification').forEach(n => n.remove());
            
            const notification = document.createElement('div');
            notification.className = `notification ${type}`;
            notification.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
                <span>${message}</span>
            `;
            
            document.body.appendChild(notification);
            
            // Auto remove after 3 seconds
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.style.animation = 'slideIn 0.3s ease-out reverse';
                    setTimeout(() => notification.remove(), 300);
                }
            }, 3000);
        }
        
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-hide notifications after 5 seconds
            setTimeout(() => {
                document.querySelectorAll('.notification').forEach(n => {
                    n.style.animation = 'slideIn 0.3s ease-out reverse';
                    setTimeout(() => n.remove(), 300);
                });
            }, 5000);
            
            // Close modal when clicking outside
            document.getElementById('memberModal').addEventListener('click', function(e) {
                if (e.target === this) {
                    closeMemberModal();
                }
            });
            
            // Enhance member cards with hover effects
            document.querySelectorAll('.member-card').forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.zIndex = '1';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.zIndex = '0';
                });
            });
        });
    </script>
</body>
</html>