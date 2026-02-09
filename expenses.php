<?php
// expenses.php
require_once 'config/database.php';
require_once 'includes/auth.php';
requireAuth();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add_expense') {
            $expense_name = trim($_POST['expense_name']);
            $amount = floatval($_POST['amount']);
            
            if (!empty($expense_name) && $amount > 0) {
                try {
                    $stmt = $pdo->prepare("INSERT INTO expenses (expenses_name, amount, created_at) VALUES (?, ?, NOW())");
                    $stmt->execute([$expense_name, $amount]);
                    $success = "Expense added successfully!";
                    
                    // Redirect to avoid form resubmission
                    header("Location: expenses.php?success=" . urlencode($success));
                    exit();
                } catch (Exception $e) {
                    $error = "Error adding expense: " . $e->getMessage();
                }
            } else {
                $error = "Please enter a valid expense name and amount.";
            }
        } elseif ($_POST['action'] === 'delete_expense') {
            $expense_id = intval($_POST['expense_id']);
            
            try {
                $stmt = $pdo->prepare("DELETE FROM expenses WHERE id = ?");
                $stmt->execute([$expense_id]);
                $success = "Expense deleted successfully!";
                
                header("Location: expenses.php?success=" . urlencode($success));
                exit();
            } catch (Exception $e) {
                $error = "Error deleting expense: " . $e->getMessage();
            }
        }
    }
}

// Fetch all expenses
$stmt = $pdo->query("SELECT * FROM expenses ORDER BY created_at DESC");
$expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate total expenses
$total_expenses = 0;
foreach ($expenses as $expense) {
    $total_expenses += floatval($expense['amount']);
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
    <title>Expenses Dashboard - River of God Church</title>
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
        
        .btn-danger {
            background: #ef4444;
            color: white;
        }
        
        .btn-danger:hover {
            background: #dc2626;
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
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        }
        
        .summary-card:nth-child(2) .summary-icon {
            background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
        }
        
        .summary-card:nth-child(3) .summary-icon {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
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
        
        /* Add Expense Form */
        .add-expense-card {
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
        
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr auto;
            gap: 16px;
            align-items: end;
        }
        
        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .form-group {
            margin-bottom: 0;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 600;
            color: var(--dark);
        }
        
        .form-input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid var(--border);
            border-radius: var(--border-radius-sm);
            font-family: 'Inter', sans-serif;
            font-size: 16px;
            transition: all 0.3s ease;
            background: white;
        }
        
        .form-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }
        
        .btn-block {
            width: 100%;
            justify-content: center;
            padding: 14px;
            font-size: 16px;
        }
        
        /* Expenses Table */
        .expenses-table-container {
            background: white;
            border-radius: var(--border-radius-lg);
            padding: 32px;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border);
        }
        
        .table-responsive {
            overflow-x: auto;
        }
        
        .expenses-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .expenses-table thead {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border-bottom: 2px solid var(--border);
        }
        
        .expenses-table th {
            padding: 16px 20px;
            text-align: left;
            font-weight: 600;
            color: var(--dark);
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .expenses-table tbody tr {
            border-bottom: 1px solid var(--border);
            transition: all 0.3s ease;
        }
        
        .expenses-table tbody tr:hover {
            background: #f8fafc;
        }
        
        .expenses-table td {
            padding: 16px 20px;
            font-size: 14px;
            color: var(--dark);
        }
        
        .expense-name {
            font-weight: 500;
        }
        
        .expense-amount {
            font-weight: 700;
            color: #ef4444;
            font-feature-settings: "tnum";
        }
        
        .expense-date {
            color: var(--gray);
            font-size: 13px;
        }
        
        .action-buttons {
            display: flex;
            gap: 8px;
        }
        
        .btn-sm {
            padding: 8px 12px;
            font-size: 13px;
            border-radius: var(--border-radius-sm);
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--gray);
        }
        
        .empty-state i {
            font-size: 48px;
            margin-bottom: 16px;
            opacity: 0.5;
        }
        
        .empty-state h3 {
            font-size: 18px;
            margin-bottom: 8px;
            color: var(--dark);
        }
        
        .empty-state p {
            font-size: 14px;
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
    </style>
</head>
<body>
    <div class="app-container">
        <!-- Header -->
        <header class="app-header">
            <div class="header-content">
                <div class="logo-section">
                    <div class="logo-icon">
                        <i class="fas fa-receipt"></i>
                    </div>
                    <div class="header-title">
                        <h1>Expenses Dashboard</h1>
                        <p>Connect Ministry - <?php echo date('Y'); ?></p>
                    </div>
                </div>
                <div class="header-actions">
                    <a href="funds.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Funds
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
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="summary-number">₱<?php echo number_format($total_expenses, 2); ?></div>
                    <div class="summary-label">Total Expenses</div>
                </div>
                <div class="summary-card">
                    <div class="summary-icon">
                        <i class="fas fa-list"></i>
                    </div>
                    <div class="summary-number"><?php echo count($expenses); ?></div>
                    <div class="summary-label">Total Expense Items</div>
                </div>
                <div class="summary-card">
                    <div class="summary-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div class="summary-number"><?php echo date('M Y'); ?></div>
                    <div class="summary-label">Current Month</div>
                </div>
            </div>

            <!-- Add Expense Form -->
            <div class="add-expense-card">
                <h3 class="section-title">
                    <i class="fas fa-plus-circle"></i>
                    Add New Expense
                </h3>
                
                <form method="POST" id="addExpenseForm">
                    <input type="hidden" name="action" value="add_expense">
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Expense Name</label>
                            <input type="text" name="expense_name" class="form-input" 
                                   placeholder="e.g., Food, Transportation, Materials" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Amount (₱)</label>
                            <input type="number" name="amount" class="form-input" 
                                   placeholder="0.00" min="0" step="0.01" required>
                        </div>
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary btn-block" id="addExpenseBtn">
                                <i class="fas fa-plus"></i> Add Expense
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Expenses List -->
            <div class="expenses-table-container">
                <h3 class="section-title">
                    <i class="fas fa-receipt"></i>
                    Expenses History
                </h3>
                
                <div class="table-responsive">
                    <?php if (count($expenses) > 0): ?>
                        <table class="expenses-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Expense Name</th>
                                    <th>Amount</th>
                                    <th>Date Added</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($expenses as $expense): ?>
                                    <tr>
                                        <td><?php echo $expense['id']; ?></td>
                                        <td class="expense-name"><?php echo htmlspecialchars($expense['expenses_name']); ?></td>
                                        <td class="expense-amount">₱<?php echo number_format($expense['amount'], 2); ?></td>
                                        <td class="expense-date"><?php echo date('M d, Y h:i A', strtotime($expense['created_at'])); ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <form method="POST" onsubmit="return confirm('Are you sure you want to delete this expense?');">
                                                    <input type="hidden" name="action" value="delete_expense">
                                                    <input type="hidden" name="expense_id" value="<?php echo $expense['id']; ?>">
                                                    <button type="submit" class="btn btn-danger btn-sm">
                                                        <i class="fas fa-trash"></i> Delete
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-receipt"></i>
                            <h3>No expenses recorded yet</h3>
                            <p>Add your first expense using the form above</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Handle form submission with loading state
        document.getElementById('addExpenseForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Show loading state
            const addBtn = document.getElementById('addExpenseBtn');
            const originalText = addBtn.innerHTML;
            addBtn.innerHTML = '<span class="loading"></span> Adding...';
            addBtn.disabled = true;
            
            // Submit the form
            fetch('expenses.php', {
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
            .catch(error => {
                showNotification('Error adding expense: ' + error.message, 'error');
                addBtn.innerHTML = originalText;
                addBtn.disabled = false;
            });
        });
        
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
            
            // Focus on first input
            const firstInput = document.querySelector('input[name="expense_name"]');
            if (firstInput) firstInput.focus();
        });
    </script>
</body>
</html>