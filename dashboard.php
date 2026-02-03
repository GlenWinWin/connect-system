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
    <link rel="stylesheet" href="css/dashboard.css">
    <style>
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            overflow: auto;
        }
        
        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 30px;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            position: relative;
            animation: modalFadeIn 0.3s;
        }
        
        @keyframes modalFadeIn {
            from { opacity: 0; transform: translateY(-50px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .close {
            position: absolute;
            right: 20px;
            top: 15px;
            font-size: 28px;
            cursor: pointer;
            color: #666;
            transition: color 0.3s;
        }
        
        .close:hover {
            color: #000;
        }
        
        .modal h2 {
            margin-top: 0;
            margin-bottom: 20px;
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .file-upload {
            margin: 20px 0;
        }
        
        .file-input {
            width: 100%;
            padding: 15px;
            border: 2px dashed #3498db;
            border-radius: 5px;
            background-color: #f8f9fa;
            cursor: pointer;
            transition: border-color 0.3s;
        }
        
        .file-input:hover {
            border-color: #2980b9;
        }
        
        .upload-info {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #3498db;
        }
        
        .upload-info h4 {
            margin-top: 0;
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .upload-info ul {
            margin: 10px 0 0 0;
            padding-left: 20px;
        }
        
        .upload-info li {
            margin-bottom: 5px;
        }
        
        .modal-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        /* Alert styles for modal */
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
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
        
        .alert-warning {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
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
                <h1 class="church-name">Visitor Management</h1>
            </div>
            <div class="user-info">
                <span style="font-weight: 500;">Welcome, <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'User'); ?></span>
                <a href="logout.php" class="btn btn-secondary">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>

        <div class="date-filter">
            <form method="GET" class="date-filter-form">
                <div class="form-group">
                    <label for="start_date">Start Date</label>
                    <input type="date" id="start_date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>" required>
                </div>
                <div class="form-group">
                    <label for="end_date">End Date</label>
                    <input type="date" id="end_date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>" required>
                </div>
                <div class="form-group">
                    <label for="age_group">Age Group</label>
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
                        <i class="fas fa-sync"></i> Reset
                    </a>
                </div>
            </form>
        </div>

        <!-- Age Group Statistics -->
        <div class="age-group-section">
            <div class="age-group-header">
                <h2 class="age-group-title">Age Group Distribution</h2>
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
        <div class="stats-bar">
            <div class="stat-card">
                <div class="stat-number"><?php echo count($first_timers); ?></div>
                <div class="stat-label">Total Visitors</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count(array_filter($first_timers, fn($v) => $v['lifegroup'] === 'YES')); ?></div>
                <div class="stat-label">Lifegroup Interest</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count(array_filter($first_timers, fn($v) => $v['texted_already'])); ?></div>
                <div class="stat-label">Texted</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count(array_filter($first_timers, fn($v) => $v['started_one2one'])); ?></div>
                <div class="stat-label">One-to-One Started</div>
            </div>
        </div>

        <div class="search-bar">
            <form method="GET" style="display: flex; width: 100%; gap: 15px;">
                <input type="hidden" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
                <input type="hidden" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
                <input type="hidden" name="age_group" value="<?php echo htmlspecialchars($age_group_filter); ?>">
                <input type="text" name="search" class="search-input" placeholder="Search by name, contact, or service..." 
                       value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn">
                    <i class="fas fa-search"></i> Search
                </button>
            </form>
        </div>

        <div class="actions-grid">
            <a href="analytics.php" class="btn btn-info">
                <i class="fas fa-chart-bar"></i> Analytics
            </a>
            <a href="export.php" class="btn btn-success">
                <i class="fas fa-file-export"></i> Export CSV
            </a>
            <button type="button" data-open-modal class="btn btn-secondary">
                <i class="fas fa-file-upload"></i> Upload CSV
            </button>
            <a href="stats_visitor_type.php" class="btn">
                <i class="fas fa-users"></i> Visitor Type Stats
            </a>
            <a href="stats_life_stages.php" class="btn btn-success">
                <i class="fas fa-chart-pie"></i> Life Stages Stats
            </a>
        </div>

        <?php if (empty($first_timers)): ?>
            <div class="empty-state">
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
                    <div class="age-group-divider">
                        <span>
                            <i class="fas fa-users"></i> 
                            <?php echo htmlspecialchars($age_group_name); ?> Age Group 
                            (<?php echo count($visitors_in_group); ?> visitors)
                        </span>
                        <span class="badge badge-age-group"><?php echo count($visitors_in_group); ?></span>
                    </div>
                <?php endif; ?>

                <!-- Desktop Table (shows on large screens) -->
                <div class="table-container">
                    <table class="desktop-table">
                        <thead>
                            <tr>
                                <th>Date & Time</th>
                                <th>Name</th>
                                <th>Contact</th>
                                <th>Age Group</th>
                                <th>Messenger</th>
                                <th>Visitor Type</th>
                                <th>Invited By</th>
                                <th>Service</th>
                                <th>Lifegroup</th>
                                <th>Texted</th>
                                <th>One-to-One</th>
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
                                        <div class="visitor-name" style="font-size: 1rem; margin-bottom: 2px;"><?php echo htmlspecialchars($visitor['fullname']); ?></div>
                                        <div class="detail-label">Approached by: <?php echo htmlspecialchars($visitor['approached_by']); ?></div>
                                    </td>
                                    <td><?php echo htmlspecialchars($visitor['contact']); ?></td>
                                    <td>
                                        <span class="badge badge-age-group"><?php echo htmlspecialchars($visitor['age_group'] ?: 'Not Specified'); ?></span>
                                    </td>
                                    <td><?php echo htmlspecialchars($visitor['messenger']); ?></td>
                                    <td><?php echo htmlspecialchars($visitor['iam']); ?></td>
                                    <td><?php echo htmlspecialchars($visitor['invited_by'] ?: 'Walk In'); ?></td>
                                    <td><?php echo htmlspecialchars($visitor['service_attended']); ?></td>
                                    <td>
                                        <span class="badge <?php echo $visitor['lifegroup'] === 'YES' ? 'badge-yes' : 'badge-no'; ?>">
                                            <?php echo $visitor['lifegroup']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $visitor['texted_already'] ? 'badge-yes' : 'badge-no'; ?>">
                                            <?php echo $visitor['texted_already'] ? 'Yes' : 'No'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $visitor['started_one2one'] ? 'badge-yes' : 'badge-no'; ?>">
                                            <?php echo $visitor['started_one2one'] ? 'Yes' : 'No'; ?>
                                        </span>
                                    </td>
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

                                <div class="visitor-details">
                                    <div class="detail-group">
                                        <span class="detail-label">Age Group</span>
                                        <span class="detail-value">
                                            <span class="badge badge-age-group"><?php echo htmlspecialchars($visitor['age_group'] ?: 'Not Specified'); ?></span>
                                        </span>
                                    </div>

                                    <div class="detail-group">
                                        <span class="detail-label">Messenger</span>
                                        <span class="detail-value"><?php echo htmlspecialchars($visitor['messenger']); ?></span>
                                    </div>

                                    <div class="detail-group">
                                        <span class="detail-label">Visitor Type</span>
                                        <span class="detail-value"><?php echo htmlspecialchars($visitor['iam']); ?></span>
                                    </div>

                                    <div class="detail-group">
                                        <span class="detail-label">Invited By</span>
                                        <span class="detail-value"><?php echo htmlspecialchars($visitor['invited_by'] ?: 'Walk In'); ?></span>
                                    </div>

                                    <div class="detail-group">
                                        <span class="detail-label">Service</span>
                                        <span class="detail-value"><?php echo htmlspecialchars($visitor['service_attended']); ?></span>
                                    </div>

                                    <div class="detail-group">
                                        <span class="detail-label">Approached By</span>
                                        <span class="detail-value"><?php echo htmlspecialchars($visitor['approached_by']); ?></span>
                                    </div>

                                    <div class="detail-group">
                                        <span class="detail-label">Connected With</span>
                                        <span class="detail-value"><?php echo htmlspecialchars($visitor['connected_with']); ?></span>
                                    </div>

                                    <div class="detail-group">
                                        <span class="detail-label">Lifegroup</span>
                                        <span class="detail-value">
                                            <span class="badge <?php echo $visitor['lifegroup'] === 'YES' ? 'badge-yes' : 'badge-no'; ?>">
                                                <?php echo $visitor['lifegroup']; ?>
                                            </span>
                                        </span>
                                    </div>
                                </div>

                                <!-- Follow-up Section -->
                                <div class="follow-up-section">
                                    <div class="follow-up-title">Follow-up Status</div>
                                    <div class="follow-up-grid">
                                        <div class="detail-group">
                                            <span class="detail-label">Texted</span>
                                            <span class="detail-value">
                                                <span class="badge <?php echo $visitor['texted_already'] ? 'badge-yes' : 'badge-no'; ?>">
                                                    <?php echo $visitor['texted_already'] ? 'Yes' : 'No'; ?>
                                                </span>
                                            </span>
                                        </div>

                                        <div class="detail-group">
                                            <span class="detail-label">One-to-One</span>
                                            <span class="detail-value">
                                                <span class="badge <?php echo $visitor['started_one2one'] ? 'badge-yes' : 'badge-no'; ?>">
                                                    <?php echo $visitor['started_one2one'] ? 'Yes' : 'No'; ?>
                                                </span>
                                            </span>
                                        </div>

                                        <?php if ($visitor['followed_up_by']): ?>
                                        <div class="detail-group">
                                            <span class="detail-label">Followed Up By</span>
                                            <span class="detail-value"><?php echo htmlspecialchars($visitor['followed_up_by']); ?></span>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($visitor['update_report']): ?>
                                    <div class="detail-group" style="margin-top: 10px;">
                                        <span class="detail-label">Update / Report</span>
                                        <span class="detail-value"><?php echo htmlspecialchars($visitor['update_report']); ?></span>
                                    </div>
                                    <?php endif; ?>
                                </div>

                                <div class="action-buttons">
                                    <a href="edit_first_timer.php?id=<?php echo $visitor['id']; ?>" class="btn btn-sm">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <small class="detail-label" style="margin-left: auto;">
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
                    <ul style="margin: 10px 0 0 20px; font-size: 0.9em;">
                        <?php foreach ($upload_errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <form id="csvUploadForm" method="POST" action="upload-csv.php" enctype="multipart/form-data">
                <div class="file-upload">
                    <input type="file" id="csv_file" name="csv_file" accept=".csv" class="file-input" required>
                    <p style="margin-top: 10px; color: #666; font-size: 0.9em;">
                        <i class="fas fa-info-circle"></i> Select a CSV file with visitor data
                    </p>
                </div>
                
                <div class="upload-info">
                    <h4><i class="fas fa-table"></i> CSV Format Requirements:</h4>
                    <ul style="font-size: 0.9em; color: #555;">
                        <li>File must have headers: Timestamp, FULL NAME:, AGE GROUP:, etc.</li>
                        <li>Supported age groups: Youth, Young Adult, River Men, River Women, Seasoned</li>
                        <li>Maximum file size: 10MB</li>
                    </ul>
                </div>
                
                <div class="modal-actions" style="margin-top: 20px; display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-success" style="flex: 1;">
                        <i class="fas fa-upload"></i> Upload & Import
                    </button>
                    <button type="button" class="btn btn-secondary close-modal" style="flex: 1;">
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
                        `<i class="fas fa-file-csv"></i> Selected: ${fileName}`;
                });
            }
            
            // Show modal if there are upload messages
            <?php if (!empty($upload_success) || !empty($upload_error) || !empty($upload_errors)): ?>
                modal.style.display = 'block';
            <?php endif; ?>
        });
    </script>
</body>
</html>