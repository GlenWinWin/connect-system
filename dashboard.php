<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
requireAuth();

// Handle date range and search
$start_date = $_GET['start_date'] ?? date('Y-m-01'); // First day of current month
$end_date = $_GET['end_date'] ?? date('Y-m-t'); // Last day of current month
$search = $_GET['search'] ?? '';
$age_group_filter = $_GET['age_group'] ?? '';

// Check for upload messages
$upload_success = $_SESSION['upload_success'] ?? '';
$upload_error = $_SESSION['upload_error'] ?? '';
$upload_errors = $_SESSION['upload_errors'] ?? [];

// Clear session messages
unset($_SESSION['upload_success']);
unset($_SESSION['upload_error']);
unset($_SESSION['upload_errors']);

$where_conditions = ["DATE(created_at) BETWEEN ? AND ?"];
$params = [$start_date, $end_date];

if (!empty($search)) {
    $where_conditions[] = "(fullname LIKE ? OR contact LIKE ? OR service_attended LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($age_group_filter)) {
    $where_conditions[] = "age_group = ?";
    $params[] = $age_group_filter;
}

$where = "WHERE " . implode(" AND ", $where_conditions);

// Fetch first timers
$sql = "SELECT * FROM first_timers $where ORDER BY created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$first_timers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch age group statistics for grouping
$age_group_sql = "SELECT age_group, COUNT(*) as count FROM first_timers $where GROUP BY age_group ORDER BY count DESC";
$age_group_stmt = $pdo->prepare($age_group_sql);
$age_group_stmt->execute($params);
$age_groups = $age_group_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - River of God Church</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/main.css">
    <style>
        /* Custom styles for dashboard specific elements */
        .age-group-section {
            background: white;
            border-radius: var(--border-radius);
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: var(--shadow);
            border: 1px solid rgba(255, 107, 53, 0.1);
            position: relative;
        }
        
        .age-group-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: var(--orange-gradient);
        }
        
        .age-group-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }
        
        .age-group-title {
            font-family: var(--font-heading);
            font-size: 22px;
            font-weight: 700;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .age-group-title i {
            color: var(--primary);
        }
        
        .age-group-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        
        .age-group-card {
            background: white;
            border-radius: var(--border-radius-sm);
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: var(--transition);
            border: 2px solid transparent;
            box-shadow: 0 4px 15px rgba(255, 107, 53, 0.1);
        }
        
        .age-group-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(255, 107, 53, 0.15);
            border-color: var(--primary-light);
        }
        
        .age-group-card.active {
            border-color: var(--primary);
            background: linear-gradient(135deg, rgba(255, 107, 53, 0.05) 0%, rgba(255, 159, 28, 0.05) 100%);
        }
        
        .age-group-name {
            font-family: var(--font-heading);
            font-size: 16px;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 10px;
        }
        
        .age-group-count {
            font-family: var(--font-heading);
            font-size: 32px;
            font-weight: 800;
            color: var(--primary);
            line-height: 1;
            margin-bottom: 8px;
        }
        
        .stats-bar {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .age-group-divider {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 15px 25px;
            border-radius: var(--border-radius-sm);
            margin: 30px 0 20px 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: var(--shadow);
        }
        
        .age-group-divider i {
            margin-right: 10px;
        }
        
        .badge-age-group {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        
        .desktop-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            display: none;
        }
        
        .mobile-cards {
            display: block;
        }
        
        @media (min-width: 1024px) {
            .desktop-table {
                display: table;
            }
            .mobile-cards {
                display: none;
            }
        }
        
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(42, 45, 52, 0.8);
            backdrop-filter: blur(5px);
            overflow: auto;
        }
        
        .modal-content {
            background: white;
            margin: 5% auto;
            padding: 30px;
            border-radius: var(--border-radius);
            width: 90%;
            max-width: 500px;
            box-shadow: var(--shadow-lg);
            position: relative;
            animation: fadeInUp 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid rgba(255, 107, 53, 0.2);
        }
        
        .modal-content::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: var(--orange-gradient);
        }
        
        .close {
            position: absolute;
            right: 20px;
            top: 15px;
            font-size: 28px;
            cursor: pointer;
            color: var(--gray);
            transition: var(--transition);
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }
        
        .close:hover {
            color: var(--primary);
            background: rgba(255, 107, 53, 0.1);
        }
        
        .modal h2 {
            margin-top: 0;
            margin-bottom: 20px;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 10px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--gray-light);
        }
        
        .modal h2 i {
            color: var(--primary);
        }
        
        .file-upload {
            margin: 20px 0;
        }
        
        .file-input {
            width: 100%;
            padding: 15px;
            border: 2px dashed var(--primary-light);
            border-radius: var(--border-radius-sm);
            background: linear-gradient(135deg, rgba(255, 107, 53, 0.05) 0%, rgba(255, 159, 28, 0.05) 100%);
            cursor: pointer;
            transition: var(--transition);
            text-align: center;
            color: var(--gray);
        }
        
        .file-input:hover {
            border-color: var(--primary);
            background: linear-gradient(135deg, rgba(255, 107, 53, 0.08) 0%, rgba(255, 159, 28, 0.08) 100%);
        }
        
        .upload-info {
            background: linear-gradient(135deg, rgba(255, 107, 53, 0.03) 0%, rgba(255, 159, 28, 0.03) 100%);
            padding: 20px;
            border-radius: var(--border-radius-sm);
            margin: 20px 0;
            border-left: 4px solid var(--primary);
        }
        
        .upload-info h4 {
            margin-top: 0;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }
        
        .upload-info ul {
            margin: 10px 0 0 0;
            padding-left: 20px;
            color: var(--gray);
        }
        
        .upload-info li {
            margin-bottom: 5px;
            font-size: 14px;
        }
        
        .modal-actions {
            display: flex;
            gap: 15px;
            margin-top: 25px;
        }
        
        .modal-actions .btn {
            flex: 1;
        }
        
        /* Animation delays for fade-in */
        .age-group-section { animation-delay: 0.1s; }
        .stats-bar { animation-delay: 0.2s; }
        .search-bar { animation-delay: 0.3s; }
        .actions-grid { animation-delay: 0.4s; }
        .age-group-divider { animation-delay: 0.5s; }
        .table-container { animation-delay: 0.6s; }
        
        /* Chart-like colors for age groups */
        .age-group-card:nth-child(1) .age-group-count { color: #FF6B35; }
        .age-group-card:nth-child(2) .age-group-count { color: #FF9F1C; }
        .age-group-card:nth-child(3) .age-group-count { color: #2EC4B6; }
        .age-group-card:nth-child(4) .age-group-count { color: #FF5A5F; }
        .age-group-card:nth-child(5) .age-group-count { color: #3A86FF; }
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
                <h1 class="church-name">Visitor Management Dashboard</h1>
            </div>
            <div class="user-info">
                <span style="font-weight: 600;">Welcome, <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'User'); ?></span>
                <a href="logout.php" class="btn btn-secondary">
                    <i class="fas fa-sign-out-alt"></i> <span class="desktop-only">Logout</span>
                </a>
            </div>
        </div>

        <!-- Date Filter -->
        <div class="filter-section fade-in">
            <form method="GET" class="date-filter-form">
                <div class="form-group">
                    <label for="start_date"><i class="far fa-calendar-alt"></i> Start Date</label>
                    <input type="date" id="start_date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>" required>
                </div>
                <div class="form-group">
                    <label for="end_date"><i class="far fa-calendar-alt"></i> End Date</label>
                    <input type="date" id="end_date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>" required>
                </div>
                <div class="form-group">
                    <label for="age_group"><i class="fas fa-users"></i> Age Group</label>
                    <select id="age_group" name="age_group">
                        <option value="">All Age Groups</option>
                        <option value="Youth" <?php echo $age_group_filter === 'Youth' ? 'selected' : ''; ?>>Youth (19 years old and below)</option>
                        <option value="Young Adult" <?php echo $age_group_filter === 'Young Adult' ? 'selected' : ''; ?>>Young Adult (20 to 35 years old)</option>
                        <option value="River Men" <?php echo $age_group_filter === 'River Men' ? 'selected' : ''; ?>>River Men (36 to 50 years old)</option>
                        <option value="River Women" <?php echo $age_group_filter === 'River Women' ? 'selected' : ''; ?>>River Women (36 to 50 years old)</option>
                        <option value="Seasoned" <?php echo $age_group_filter === 'Seasoned' ? 'selected' : ''; ?>>Seasoned (51 years old and above)</option>
                    </select>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-filter"></i> Apply Filter
                    </button>
                </div>
                <div class="form-group">
                    <a href="dashboard.php" class="btn btn-secondary">
                        <i class="fas fa-sync-alt"></i> Reset
                    </a>
                </div>
            </form>
        </div>

        <!-- Age Group Statistics -->
        <div class="age-group-section fade-in">
            <div class="age-group-header">
                <h2 class="age-group-title"><i class="fas fa-chart-pie"></i> Age Group Distribution</h2>
                <span class="detail-label">Total Visitors: <?php echo count($first_timers); ?></span>
            </div>
            
            <div class="age-group-grid">
                <?php foreach ($age_groups as $age_group): ?>
                    <div class="age-group-card <?php echo $age_group_filter === $age_group['age_group'] ? 'active' : ''; ?>" 
                         onclick="filterAgeGroup('<?php echo $age_group['age_group']; ?>')">
                        <div class="age-group-name"><?php echo htmlspecialchars($age_group['age_group']); ?></div>
                        <div class="age-group-count"><?php echo $age_group['count']; ?></div>
                        <div class="detail-label">
                            <?php echo count($first_timers) > 0 ? round(($age_group['count'] / count($first_timers)) * 100, 1) : 0; ?>%
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <?php if (empty($age_groups)): ?>
                    <div class="age-group-card">
                        <div class="age-group-name">No Age Data</div>
                        <div class="age-group-count">0</div>
                        <div class="detail-label">0%</div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Statistics -->
        <div class="stats-bar fade-in">
            <div class="stat-card">
                <div class="stat-number"><?php echo count($first_timers); ?></div>
                <div class="stat-label">
                    <i class="fas fa-users"></i> Total Visitors
                </div>
                <div class="stat-percentage">All visitors</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count(array_filter($first_timers, fn($v) => $v['lifegroup'] === 'YES')); ?></div>
                <div class="stat-label">
                    <i class="fas fa-heart"></i> Lifegroup Interest
                </div>
                <div class="stat-percentage">
                    <?php echo count($first_timers) > 0 ? round((count(array_filter($first_timers, fn($v) => $v['lifegroup'] === 'YES')) / count($first_timers)) * 100, 1) : 0; ?>% interested
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count(array_filter($first_timers, fn($v) => $v['texted_already'])); ?></div>
                <div class="stat-label">
                    <i class="fas fa-comment-alt"></i> Texted
                </div>
                <div class="stat-percentage">
                    <?php echo count($first_timers) > 0 ? round((count(array_filter($first_timers, fn($v) => $v['texted_already'])) / count($first_timers)) * 100, 1) : 0; ?>% texted
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count(array_filter($first_timers, fn($v) => $v['started_one2one'])); ?></div>
                <div class="stat-label">
                    <i class="fas fa-handshake"></i> One-to-One Started
                </div>
                <div class="stat-percentage">
                    <?php echo count($first_timers) > 0 ? round((count(array_filter($first_timers, fn($v) => $v['started_one2one'])) / count($first_timers)) * 100, 1) : 0; ?>% started
                </div>
            </div>
        </div>

        <!-- Search Bar -->
        <div class="search-bar fade-in">
            <form method="GET" style="display: flex; width: 100%; gap: 15px;">
                <input type="hidden" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
                <input type="hidden" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
                <input type="hidden" name="age_group" value="<?php echo htmlspecialchars($age_group_filter); ?>">
                <input type="text" name="search" class="search-input" placeholder="Search by name, contact, or service..." 
                       value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn">
                    <i class="fas fa-search"></i> <span class="desktop-only">Search</span>
                </button>
            </form>
        </div>

        <!-- Actions Grid -->
        <div class="actions-grid fade-in">
            <a href="analytics.php" class="btn btn-info">
                <i class="fas fa-chart-bar"></i> Analytics
            </a>
            <a href="export.php" class="btn btn-success">
                <i class="fas fa-file-export"></i> Export CSV
            </a>
            <button type="button" data-open-modal class="btn btn-secondary">
                <i class="fas fa-file-upload"></i> Upload CSV
            </button>
        </div>

        <?php if (empty($first_timers)): ?>
            <div class="empty-state fade-in">
                <i class="fas fa-users"></i>
                <h3>No Visitors Found</h3>
                <p>No visitors have been added yet. <a href="connect-form.php" target="_blank">Add the first visitor</a></p>
            </div>
        <?php else: ?>
            <!-- Group visitors by age group -->
            <?php 
            $grouped_visitors = [];
            foreach ($first_timers as $visitor) {
                $age_group = $visitor['age_group'] ?: 'Not Specified';
                $grouped_visitors[$age_group][] = $visitor;
            }
            ?>

            <?php foreach ($grouped_visitors as $age_group_name => $visitors_in_group): ?>
                <?php if (count($grouped_visitors) > 1): ?>
                    <div class="age-group-divider fade-in">
                        <span>
                            <i class="fas fa-users"></i> 
                            <?php echo htmlspecialchars($age_group_name); ?> Age Group 
                            (<?php echo count($visitors_in_group); ?> visitors)
                        </span>
                        <span class="badge badge-age-group"><?php echo count($visitors_in_group); ?></span>
                    </div>
                <?php endif; ?>

                <!-- Desktop Table (shows on large screens) -->
                <div class="table-container fade-in">
                    <table class="desktop-table">
                        <thead>
                            <tr>
                                <th>Date & Time</th>
                                <th>Name</th>
                                <th>Contact</th>
                                <th>Age Group</th>
                                <th>Visitor Type</th>
                                <th>Invited By</th>
                                <th>Service</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($visitors_in_group as $visitor): ?>
                                <tr>
                                    <td>
                                        <div class="detail-label"><?php echo date('M j, Y', strtotime($visitor['created_at'])); ?></div>
                                        <div class="detail-value"><?php echo date('g:i A', strtotime($visitor['created_at'])); ?></div>
                                    </td>
                                    <td>
                                        <div class="visitor-name"><?php echo htmlspecialchars($visitor['fullname']); ?></div>
                                        <div class="detail-label">Approached by: <?php echo htmlspecialchars($visitor['approached_by']); ?></div>
                                    </td>
                                    <td><?php echo htmlspecialchars($visitor['contact']); ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($visitor['age_group'] ?: 'Not Specified'); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($visitor['iam']); ?></td>
                                    <td><?php echo htmlspecialchars($visitor['invited_by'] ?: 'Walk In'); ?></td>
                                    <td><?php echo htmlspecialchars($visitor['service_attended']); ?></td>
                                    <td>
                                        <a href="edit_first_timer.php?id=<?php echo $visitor['id']; ?>" class="btn btn-sm">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <!-- Mobile Cards (shows on small/medium screens) -->
                    <div class="mobile-cards">
                        <?php foreach ($visitors_in_group as $visitor): ?>
                            <div class="visitor-card">
                                <div class="visitor-header">
                                    <div>
                                        <div class="visitor-name"><?php echo htmlspecialchars($visitor['fullname']); ?></div>
                                        <div class="visitor-contact"><?php echo htmlspecialchars($visitor['contact']); ?></div>
                                    </div>
                                    <div style="text-align: right;">
                                        <div class="detail-label"><?php echo date('M j, Y', strtotime($visitor['created_at'])); ?></div>
                                        <div class="detail-value"><?php echo date('g:i A', strtotime($visitor['created_at'])); ?></div>
                                    </div>
                                </div>

                                <div class="action-buttons">
                                    <a href="edit_first_timer.php?id=<?php echo $visitor['id']; ?>" class="btn btn-sm">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <small class="detail-label">
                                        Updated: <?php echo date('M j, g:i A', strtotime($visitor['updated_at'])); ?>
                                    </small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- CSV Upload Modal -->
    <div id="csvUploadModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2><i class="fas fa-file-upload"></i> Upload CSV File</h2>
            
            <?php if (!empty($upload_success)): ?>
                <div class="alert alert-success" style="margin-bottom: 20px;">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($upload_success); ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($upload_error)): ?>
                <div class="alert alert-error" style="margin-bottom: 20px;">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($upload_error); ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($upload_errors)): ?>
                <div class="alert alert-warning" style="margin-bottom: 20px;">
                    <i class="fas fa-exclamation-triangle"></i> 
                    <?php echo count($upload_errors); ?> errors found:
                    <ul style="margin: 10px 0 0 0; padding-left: 20px; font-size: 0.9em; color: var(--gray);">
                        <?php foreach ($upload_errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <form id="csvUploadForm" method="POST" action="upload_csv.php" enctype="multipart/form-data">
                <div class="file-upload">
                    <input type="file" id="csv_file" name="csv_file" accept=".csv" class="file-input" required>
                    <p style="margin-top: 10px; color: var(--gray); font-size: 0.9em;">
                        <i class="fas fa-info-circle"></i> Select a CSV file with visitor data
                    </p>
                </div>
                
                <div class="upload-info">
                    <h4><i class="fas fa-table"></i> CSV Format Requirements:</h4>
                    <ul>
                        <li>File must have headers: Timestamp, FULL NAME:, AGE GROUP:, etc.</li>
                        <li>Supported age groups: Youth, Young Adult, River Men, River Women, Seasoned</li>
                        <li>Maximum file size: 10MB</li>
                    </ul>
                </div>
                
                <div class="modal-actions">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-upload"></i> Upload & Import
                    </button>
                    <button type="button" class="btn btn-secondary close-modal">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function filterAgeGroup(ageGroup) {
            const url = new URL(window.location.href);
            url.searchParams.set('age_group', ageGroup);
            window.location.href = url.toString();
        }
        
        // Modal functionality
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('csvUploadModal');
            const openModalBtn = document.querySelector('[data-open-modal]');
            const closeModalBtns = document.querySelectorAll('.close, .close-modal');
            const fileInput = document.getElementById('csv_file');
            
            // Open modal when "Upload CSV" button is clicked
            if (openModalBtn) {
                openModalBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    modal.style.display = 'block';
                });
            }
            
            // Close modal
            closeModalBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    modal.style.display = 'none';
                });
            });
            
            // Close modal when clicking outside
            window.addEventListener('click', function(e) {
                if (e.target === modal) {
                    modal.style.display = 'none';
                }
            });
            
            // Show file name when selected
            if (fileInput) {
                fileInput.addEventListener('change', function() {
                    const fileName = this.files[0]?.name || 'No file selected';
                    this.nextElementSibling.innerHTML = 
                        `<i class="fas fa-file-csv"></i> Selected: <strong>${fileName}</strong>`;
                });
            }
            
            // Show modal if there are upload messages
            <?php if (!empty($upload_success) || !empty($upload_error) || !empty($upload_errors)): ?>
                modal.style.display = 'block';
            <?php endif; ?>
            
            // Add animation to stat cards on hover
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    const number = this.querySelector('.stat-number');
                    if (number) {
                        number.style.transform = 'scale(1.05)';
                    }
                });
                
                card.addEventListener('mouseleave', function() {
                    const number = this.querySelector('.stat-number');
                    if (number) {
                        number.style.transform = 'scale(1)';
                    }
                });
            });
        });
    </script>
</body>
</html>