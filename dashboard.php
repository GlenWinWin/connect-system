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
    <link rel="stylesheet" href="css/dashboard.css">
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="church-info">
                <div class="logo">
                    <i class="fas fa-handshake"></i>
                </div>
                <h1 class="church-name">Dashboard</h1>
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
            <form method="GET" class="date-filter-form" id="filterForm">
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
                    <button type="submit" class="btn btn-success" id="applyFilterBtn">
                        <i class="fas fa-filter"></i> Apply Filter
                    </button>
                </div>
                <div class="form-group">
                    <a href="dashboard.php" class="btn btn-secondary">
                        <i class="fas fa-sync-alt"></i> Reset
                    </a>
                </div>
            </form>
            
            <!-- Storage Controls (NEW) -->
            <div class="storage-controls" id="storageControls">
                <div class="storage-info" id="storageStatus">
                    <i class="fas fa-database"></i>
                    <span>No saved settings</span>
                </div>
                <button type="button" class="btn btn-info" id="saveSettingsBtn">
                    <i class="fas fa-save"></i> Save Settings
                </button>
                <button type="button" class="btn btn-warning" id="removeSettingsBtn" style="display: none;">
                    <i class="fas fa-trash-alt"></i> Remove Settings
                </button>
            </div>
        </div>

        <!-- Age Group Statistics -->
        <div class="age-group-section fade-in">
            <div class="age-group-header">
                <h2 class="age-group-title"><i class="fas fa-chart-pie"></i> Age Group Distribution</h2>
                <span class="detail-label">Total Visitors: <?php echo count($first_timers); ?></span>
            </div>
            
            <div class="age-group-grid">
                <?php 
                // Define the correct order
                $age_group_order = ['Youth', 'Young Adult', 'River Men', 'River Women', 'Seasoned'];
                
                // Reorder age groups according to specified order
                $ordered_age_groups = [];
                foreach ($age_group_order as $group_name) {
                    $found = false;
                    foreach ($age_groups as $age_group) {
                        if ($age_group['age_group'] === $group_name) {
                            $ordered_age_groups[] = $age_group;
                            $found = true;
                            break;
                        }
                    }
                    // If age group not found in data, add empty placeholder
                    if (!$found) {
                        $ordered_age_groups[] = ['age_group' => $group_name, 'count' => 0];
                    }
                }
                
                // Display in correct order
                foreach ($ordered_age_groups as $age_group): 
                ?>
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
            <a href="funds.php" class="btn btn-info">
                <i class="fas fa-money-bill-wave"></i> Funds
            </a>
        </div>

        <?php if (empty($first_timers)): ?>
            <div class="empty-state fade-in">
                <i class="fas fa-users"></i>
                <h3>No Visitors Found</h3>
                <p>No visitors have been added yet. <a href="connect-form.php" target="_blank">Add the first visitor</a></p>
            </div>
        <?php else: ?>
            <!-- Group visitors by age group in specified order -->
            <?php 
            // Define the correct order for age groups
            $age_group_order = ['Youth', 'Young Adult', 'River Men', 'River Women', 'Seasoned'];
            
            // Initialize grouped visitors array in specified order
            $grouped_visitors = [];
            foreach ($age_group_order as $group_name) {
                $grouped_visitors[$group_name] = [];
            }
            
            // Group visitors by age group (skip visitors without a valid age group)
            foreach ($first_timers as $visitor) {
                $age_group = $visitor['age_group'] ?? '';
                
                // Only add to group if age group is in our predefined order
                if (in_array($age_group, $age_group_order)) {
                    $grouped_visitors[$age_group][] = $visitor;
                }
            }
            
            // Remove empty groups
            $grouped_visitors = array_filter($grouped_visitors, function($visitors_in_group) {
                return !empty($visitors_in_group);
            });
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
                                        <?php echo htmlspecialchars($visitor['age_group']); ?>
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
            
            <form id="csvUploadForm" method="POST" action="upload-csv.php" enctype="multipart/form-data">
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
        // Local Storage Keys for Dashboard
        const STORAGE_KEYS = {
            START_DATE: 'dashboard_start',
            END_DATE: 'dashboard_end',
            AGE_GROUP: 'dashboard_group'
        };

        // DOM Elements
        const filterForm = document.getElementById('filterForm');
        const startDateInput = document.getElementById('start_date');
        const endDateInput = document.getElementById('end_date');
        const ageGroupSelect = document.getElementById('age_group');
        const applyFilterBtn = document.getElementById('applyFilterBtn');
        const saveSettingsBtn = document.getElementById('saveSettingsBtn');
        const removeSettingsBtn = document.getElementById('removeSettingsBtn');
        const storageStatus = document.getElementById('storageStatus');

        // Check for saved settings on page load - but only if no URL parameters exist
        function checkSavedSettings() {
            const urlParams = new URLSearchParams(window.location.search);
            const hasUrlParams = urlParams.has('start_date') || urlParams.has('end_date') || urlParams.has('age_group');
            
            console.log('Dashboard - URL parameters present:', hasUrlParams, 'Params:', Object.fromEntries(urlParams));
            
            // Only apply saved settings if NO URL parameters exist
            if (!hasUrlParams) {
                const savedStart = localStorage.getItem(STORAGE_KEYS.START_DATE);
                const savedEnd = localStorage.getItem(STORAGE_KEYS.END_DATE);
                const savedGroup = localStorage.getItem(STORAGE_KEYS.AGE_GROUP);
                
                console.log('Dashboard - Saved settings:', { savedStart, savedEnd, savedGroup });
                
                if (savedStart && savedEnd) {
                    // Apply saved settings to form
                    startDateInput.value = savedStart;
                    endDateInput.value = savedEnd;
                    
                    if (savedGroup) {
                        ageGroupSelect.value = savedGroup;
                    }
                    
                    // Show saved status
                    updateStorageStatus(true);
                    
                    // Auto-submit form ONLY if we have saved settings and no URL params
                    console.log('Dashboard - Auto-submitting with saved settings...');
                    setTimeout(() => {
                        filterForm.submit();
                    }, 100);
                } else {
                    updateStorageStatus(false);
                }
            } else {
                // URL parameters exist - update storage status based on current values
                updateStorageStatusFromCurrent();
            }
        }

        // Update storage status from current form values
        function updateStorageStatusFromCurrent() {
            const currentStart = startDateInput.value;
            const currentEnd = endDateInput.value;
            const currentGroup = ageGroupSelect.value;
            
            // Check if current values match saved values
            const savedStart = localStorage.getItem(STORAGE_KEYS.START_DATE);
            const savedEnd = localStorage.getItem(STORAGE_KEYS.END_DATE);
            const savedGroup = localStorage.getItem(STORAGE_KEYS.AGE_GROUP);
            
            const hasSavedSettings = savedStart && savedEnd;
            const currentMatchesSaved = hasSavedSettings && 
                                       savedStart === currentStart && 
                                       savedEnd === currentEnd &&
                                       savedGroup === currentGroup;
            
            if (currentMatchesSaved) {
                updateStorageStatus(true);
            } else {
                // Check if we have any saved settings at all
                updateStorageStatus(hasSavedSettings);
            }
        }

        // Update storage status display
        function updateStorageStatus(hasSettings) {
            if (hasSettings) {
                const savedStart = localStorage.getItem(STORAGE_KEYS.START_DATE);
                const savedEnd = localStorage.getItem(STORAGE_KEYS.END_DATE);
                const savedGroup = localStorage.getItem(STORAGE_KEYS.AGE_GROUP);
                
                let statusText = `Saved: ${formatDate(savedStart)} to ${formatDate(savedEnd)}`;
                if (savedGroup) {
                    statusText += ` | Group: ${savedGroup}`;
                }
                
                storageStatus.innerHTML = `<i class="fas fa-database"></i><span>${statusText}</span>`;
                removeSettingsBtn.style.display = 'inline-flex';
            } else {
                storageStatus.innerHTML = `<i class="fas fa-database"></i><span>No saved settings</span>`;
                removeSettingsBtn.style.display = 'none';
            }
        }

        // Format date for display
        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', { 
                month: 'short', 
                day: 'numeric',
                year: 'numeric'
            });
        }

        // Save settings to localStorage
        function saveSettings() {
            const startDate = startDateInput.value;
            const endDate = endDateInput.value;
            const ageGroup = ageGroupSelect.value;
            
            if (!startDate || !endDate) {
                alert('Please select both start and end dates before saving.');
                return;
            }
            
            localStorage.setItem(STORAGE_KEYS.START_DATE, startDate);
            localStorage.setItem(STORAGE_KEYS.END_DATE, endDate);
            
            if (ageGroup) {
                localStorage.setItem(STORAGE_KEYS.AGE_GROUP, ageGroup);
            } else {
                localStorage.removeItem(STORAGE_KEYS.AGE_GROUP);
            }
            
            updateStorageStatus(true);
            showNotification('Settings saved successfully!', 'success');
        }

        // Remove settings from localStorage
        function removeSettings() {
            if (confirm('Are you sure you want to remove your saved settings?')) {
                localStorage.removeItem(STORAGE_KEYS.START_DATE);
                localStorage.removeItem(STORAGE_KEYS.END_DATE);
                localStorage.removeItem(STORAGE_KEYS.AGE_GROUP);
                
                updateStorageStatus(false);
                showNotification('Settings removed successfully!', 'info');
            }
        }

        // Show notification
        function showNotification(message, type = 'info') {
            // Remove existing notification
            const existingNotification = document.querySelector('.notification');
            if (existingNotification) {
                existingNotification.remove();
            }
            
            // Create notification element
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            notification.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'info-circle'}"></i>
                <span>${message}</span>
            `;
            
            // Add styles
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: ${type === 'success' ? 'var(--success)' : 'var(--info)'};
                color: white;
                padding: 15px 25px;
                border-radius: var(--border-radius-sm);
                display: flex;
                align-items: center;
                gap: 12px;
                box-shadow: var(--shadow-lg);
                z-index: 9999;
                animation: slideIn 0.3s ease-out;
                font-family: 'Inter', sans-serif;
                font-weight: 500;
                font-size: 14px;
            `;
            
            // Add animation
            const style = document.createElement('style');
            style.textContent = `
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
            `;
            document.head.appendChild(style);
            
            document.body.appendChild(notification);
            
            // Auto-remove after 3 seconds
            setTimeout(() => {
                notification.style.animation = 'slideOut 0.3s ease-out forwards';
                
                const slideOutStyle = document.createElement('style');
                slideOutStyle.textContent = `
                    @keyframes slideOut {
                        from {
                            transform: translateX(0);
                            opacity: 1;
                        }
                        to {
                            transform: translateX(100%);
                            opacity: 0;
                        }
                    }
                `;
                document.head.appendChild(slideOutStyle);
                
                setTimeout(() => {
                    notification.remove();
                    document.head.removeChild(slideOutStyle);
                }, 300);
            }, 3000);
        }

        function filterAgeGroup(ageGroup) {
            const url = new URL(window.location.href);
            url.searchParams.set('age_group', ageGroup);
            window.location.href = url.toString();
        }
        
        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Dashboard DOM fully loaded and parsed');
            
            // Check for saved settings (but respect URL parameters first)
            checkSavedSettings();
            
            // Event Listeners for storage controls
            saveSettingsBtn.addEventListener('click', saveSettings);
            removeSettingsBtn.addEventListener('click', removeSettings);
            
            // Also update storage status when form values change
            startDateInput.addEventListener('change', updateStorageStatusFromCurrent);
            endDateInput.addEventListener('change', updateStorageStatusFromCurrent);
            ageGroupSelect.addEventListener('change', updateStorageStatusFromCurrent);
            
            // Save settings when Apply Filter is clicked
            applyFilterBtn.addEventListener('click', function(e) {
                console.log('Apply Filter clicked');
                // Form will submit normally
            });
            
            // Modal functionality
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