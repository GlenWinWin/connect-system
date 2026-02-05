<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
requireAuth();

// Default date range (last 30 days)
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$age_group_filter = $_GET['age_group'] ?? '';

// Build WHERE conditions
$where_conditions = ["DATE(created_at) BETWEEN ? AND ?"];
$params = [$start_date, $end_date];

if (!empty($age_group_filter)) {
    $where_conditions[] = "age_group = ?";
    $params[] = $age_group_filter;
}

$where_clause = "WHERE " . implode(" AND ", $where_conditions);

// Fetch analytics data
$sql = "SELECT 
            DATE(created_at) as date,
            COUNT(*) as total,
            SUM(CASE WHEN iam = 'LOOKING FOR A CHURCH' THEN 1 ELSE 0 END) as first_timers,
            SUM(CASE WHEN iam != 'LOOKING FOR A CHURCH' THEN 1 ELSE 0 END) as visitors,
            SUM(CASE WHEN lifegroup = 'YES' THEN 1 ELSE 0 END) as lifegroup_interest,
            SUM(CASE WHEN texted_already = 1 THEN 1 ELSE 0 END) as texted,
            SUM(CASE WHEN started_one2one = 1 THEN 1 ELSE 0 END) as started_one2one
        FROM first_timers 
        $where_clause
        GROUP BY DATE(created_at)
        ORDER BY date";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$daily_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate totals
$total_first_timers = 0;
$total_visitors = 0;
$total_lifegroup_interest = 0;
$total_texted = 0;
$total_one2one = 0;

foreach ($daily_data as $day) {
    $total_first_timers += $day['first_timers'];
    $total_visitors += $day['visitors'];
    $total_lifegroup_interest += $day['lifegroup_interest'];
    $total_texted += $day['texted'];
    $total_one2one += $day['started_one2one'];
}

$total_visitors_all = $total_first_timers + $total_visitors;

// Get service data
$service_sql = "SELECT service_attended, COUNT(*) as count FROM first_timers $where_clause GROUP BY service_attended";
$service_stmt = $pdo->prepare($service_sql);
$service_stmt->execute($params);
$service_data = $service_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get age group data
$age_group_sql = "SELECT age_group, COUNT(*) as count FROM first_timers $where_clause GROUP BY age_group";
$age_group_stmt = $pdo->prepare($age_group_sql);
$age_group_stmt->execute($params);
$age_group_data = $age_group_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get connected data breakdown
$connected_sql = "SELECT 
                    iam,
                    COUNT(*) as connected_count
                  FROM first_timers 
                  WHERE started_one2one = 1 
                  AND DATE(created_at) BETWEEN ? AND ?
                  " . (!empty($age_group_filter) ? " AND age_group = ?" : "") . "
                  GROUP BY iam";
$connected_params = [$start_date, $end_date];
if (!empty($age_group_filter)) {
    $connected_params[] = $age_group_filter;
}
$connected_stmt = $pdo->prepare($connected_sql);
$connected_stmt->execute($connected_params);
$connected_data = $connected_stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate connected breakdown
$connected_from_first_timers = 0;
$connected_from_visitors = 0;

foreach ($connected_data as $conn) {
    if ($conn['iam'] === 'LOOKING FOR A CHURCH') {
        $connected_from_first_timers = $conn['connected_count'];
    } else {
        $connected_from_visitors = $conn['connected_count'];
    }
}

// Calculate adjusted totals for chart
$first_timers_not_connected = $total_first_timers - $connected_from_first_timers;
$visitors_not_connected = $total_visitors - $connected_from_visitors;
$total_connected = $connected_from_first_timers + $connected_from_visitors;

// Verify totals match
$chart_total = $first_timers_not_connected + $visitors_not_connected + $total_connected;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics - River of God Church</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="css/analytics.css">
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="church-info">
                <div class="logo">
                    <i class="fas fa-handshake"></i>
                </div>
                <h1 class="church-name">Analytics</h1>
            </div>
            <div class="user-info">
                <a href="dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> <span class="desktop-only">Dashboard</span>
                </a>
            </div>
        </div>

        <!-- Date Filter -->
        <div class="filter-section">
            <form method="GET" class="filter-form" id="filterForm">
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
                    <a href="analytics.php" class="btn btn-secondary">
                        <i class="fas fa-sync-alt"></i> Reset
                    </a>
                </div>
            </form>
            
            <!-- Storage Controls -->
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

        <?php if (empty($daily_data)): ?>
            <div class="empty-state">
                <i class="fas fa-chart-line"></i>
                <h3>No Data Found</h3>
                <p>No visitor data available for the selected date range.</p>
            </div>
        <?php else: ?>
            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card first-timers">
                    <div class="stat-number"><?php echo $total_first_timers; ?></div>
                    <div class="stat-label">
                        <i class="fas fa-user-plus"></i> First Timers
                    </div>
                    <div class="stat-percentage">
                        <?php echo $total_visitors_all > 0 ? round(($total_first_timers / $total_visitors_all) * 100, 1) : 0; ?>% of total
                    </div>
                </div>

                <div class="stat-card visitors">
                    <div class="stat-number"><?php echo $total_visitors; ?></div>
                    <div class="stat-label">
                        <i class="fas fa-users"></i> Visitors
                    </div>
                    <div class="stat-percentage">
                        <?php echo $total_visitors_all > 0 ? round(($total_visitors / $total_visitors_all) * 100, 1) : 0; ?>% of total
                    </div>
                </div>

                <div class="stat-card lifegroup">
                    <div class="stat-number"><?php echo $total_lifegroup_interest; ?></div>
                    <div class="stat-label">
                        <i class="fas fa-heart"></i> Lifegroup Interest
                    </div>
                    <div class="stat-percentage">
                        <?php echo $total_visitors_all > 0 ? round(($total_lifegroup_interest / $total_visitors_all) * 100, 1) : 0; ?>% interested
                    </div>
                </div>

                <div class="stat-card followup">
                    <div class="stat-number"><?php echo $total_one2one; ?></div>
                    <div class="stat-label">
                        <i class="fas fa-handshake"></i> Connected (One-to-One)
                    </div>
                    <div class="stat-percentage">
                        <?php echo $total_visitors_all > 0 ? round(($total_one2one / $total_visitors_all) * 100, 1) : 0; ?>% of total
                    </div>
                    <!-- View Connected Details Button -->
                    <button id="view-connected-btn">
                        <i class="fas fa-eye"></i> View Details
                    </button>
                </div>
            </div>

            <!-- Charts Grid -->
            <div class="charts-grid">
                <!-- Visitor Type Chart (Now with Connected as a subset) -->
                <div class="chart-container">
                    <h3 class="chart-title">
                        <i class="fas fa-chart-pie"></i> Visitor Distribution
                    </h3>
                    <div class="chart-wrapper">
                        <div class="chart-with-numbers">
                            <canvas id="visitorTypeChart"></canvas>
                            <div class="doughnut-center-text">
                                <div class="center-total"><?php echo $total_visitors_all; ?></div>
                                <div class="center-label">Total</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Daily Trend Chart (NO NUMBERS) -->
                <div class="chart-container">
                    <h3 class="chart-title">
                        <i class="fas fa-chart-line"></i> Daily Visitor Trend
                    </h3>
                    <div class="chart-wrapper">
                        <canvas id="dailyTrendChart"></canvas>
                    </div>
                </div>

                <!-- Service Attendance Chart -->
                <div class="chart-container">
                    <h3 class="chart-title">
                        <i class="fas fa-chart-donut"></i> Service Attendance
                    </h3>
                    <div class="chart-wrapper">
                        <div class="chart-with-numbers">
                            <canvas id="serviceChart"></canvas>
                            <?php 
                            $service_total = 0;
                            foreach ($service_data as $service) {
                                $service_total += $service['count'];
                            }
                            ?>
                            <div class="doughnut-center-text">
                                <div class="center-total"><?php echo $service_total; ?></div>
                                <div class="center-label">Total</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Age Group Distribution Chart (ONLY PERCENTAGES) -->
                <div class="chart-container">
                    <h3 class="chart-title">
                        <i class="fas fa-chart-bar"></i> Age Group Distribution
                    </h3>
                    <div class="chart-wrapper">
                        <canvas id="ageGroupChart"></canvas>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Register the datalabels plugin
        Chart.register(ChartDataLabels);
        
        // Local Storage Keys
        const STORAGE_KEYS = {
            START_DATE: 'analytics_start',
            END_DATE: 'analytics_end',
            AGE_GROUP: 'analytics_group'
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
            
            console.log('URL parameters present:', hasUrlParams, 'Params:', Object.fromEntries(urlParams));
            
            // Only apply saved settings if NO URL parameters exist
            if (!hasUrlParams) {
                const savedStart = localStorage.getItem(STORAGE_KEYS.START_DATE);
                const savedEnd = localStorage.getItem(STORAGE_KEYS.END_DATE);
                const savedGroup = localStorage.getItem(STORAGE_KEYS.AGE_GROUP);
                
                console.log('Saved settings:', { savedStart, savedEnd, savedGroup });
                
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
                    console.log('Auto-submitting with saved settings...');
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

        // Prepare data for charts
        const dates = <?php echo json_encode(array_column($daily_data, 'date')); ?>;
        const firstTimers = <?php echo json_encode(array_column($daily_data, 'first_timers')); ?>;
        const visitors = <?php echo json_encode(array_column($daily_data, 'visitors')); ?>;
        const serviceData = <?php echo json_encode(array_column($service_data, 'count')); ?>;
        const serviceLabels = <?php echo json_encode(array_column($service_data, 'service_attended')); ?>;
        const ageGroupData = <?php echo json_encode(array_column($age_group_data, 'count')); ?>;
        const ageGroupLabels = <?php echo json_encode(array_column($age_group_data, 'age_group')); ?>;

        // Visitor Type data - dynamic from PHP calculations
        const firstTimersNotConnected = <?php echo $first_timers_not_connected; ?>;
        const visitorsNotConnected = <?php echo $visitors_not_connected; ?>;
        const totalConnected = <?php echo $total_connected; ?>;
        const totalVisitorsAll = <?php echo $total_visitors_all; ?>;

        // Format dates for display
        const formattedDates = dates.map(date => {
            const d = new Date(date);
            return d.toLocaleDateString('en-US', { 
                month: 'short', 
                day: 'numeric',
                year: dates.length > 30 ? undefined : 'numeric'
            });
        });

        // Orange theme color palette
        const colors = {
            primary: '#FF6B35',
            primaryLight: '#FF8E53',
            primaryDark: '#E55A2B',
            secondary: '#FF9F1C',
            accent: '#FF5A5F',
            success: '#2EC4B6',
            warning: '#FFBF69',
            info: '#3A86FF',
            gradient1: ['#FF6B35', '#FF9F1C'],
            gradient2: ['#FF8E53', '#FFBF69'],
            gradient3: ['#2EC4B6', '#25a898'],
            gradient4: ['#FF5A5F', '#E63946']
        };

        // Age group color mapping
        const ageGroupColors = {
            'Youth': '#FF6B35',
            'Young Adult': '#FF9F1C',
            'River Men': '#2EC4B6',
            'River Women': '#FF5A5F',
            'Seasoned': '#3A86FF'
        };

        // FIXED: Declare ageGroupChart variable at the top level
        let ageGroupChart = null;

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM fully loaded and parsed');
            
            // Check for saved settings (but respect URL parameters first)
            checkSavedSettings();
            
            // Event Listeners
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
            
            // Add click handler to View Details button
            const viewConnectedBtn = document.querySelector('#view-connected-btn');
            
            if (viewConnectedBtn) {
                console.log('View Connected button found:', viewConnectedBtn);
                
                viewConnectedBtn.addEventListener('click', function(e) {
                    console.log('View Connected button clicked!');
                    e.preventDefault(); // Prevent any default behavior
                    e.stopPropagation(); // Prevent event bubbling
                    
                    // Get current month from filter or use default
                    const startDateInput = document.getElementById('start_date');
                    let startMonth;
                    
                    if (startDateInput && startDateInput.value) {
                        const startDate = new Date(startDateInput.value);
                        startMonth = startDate.getMonth() + 1; // January is 1
                        console.log('Using month from filter:', startMonth);
                    } else {
                        // Default to current month
                        startMonth = new Date().getMonth() + 1;
                        console.log('Using current month:', startMonth);
                    }
                    
                    // Redirect to connected page
                    console.log('Redirecting to connected.php?start=' + startMonth);
                    window.location.href = `connected.php?start=${startMonth}`;
                });
                
                // Add button animation
                viewConnectedBtn.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-3px) scale(1.05)';
                    this.style.boxShadow = '0 8px 25px rgba(46, 196, 182, 0.4)';
                });
                
                viewConnectedBtn.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0) scale(1)';
                    this.style.boxShadow = '0 4px 15px rgba(46, 196, 182, 0.2)';
                });
            } else {
                console.error('View Connected button not found!');
            }
            
            // Keep the existing hover effects for other stat cards
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach(card => {
                // Skip if it's the connected card
                if (card.querySelector('#view-connected-btn')) {
                    return;
                }
                
                card.addEventListener('mouseenter', function() {
                    const number = this.querySelector('.stat-number');
                    if (number) {
                        number.style.transform = 'scale(1.05)';
                        number.style.transition = 'transform 0.3s ease';
                    }
                });
                
                card.addEventListener('mouseleave', function() {
                    const number = this.querySelector('.stat-number');
                    if (number) {
                        number.style.transform = 'scale(1)';
                    }
                });
            });
            
            // Initialize charts
            initializeCharts();
        });

        // Initialize all charts
        function initializeCharts() {
            // Visitor Type Chart (Doughnut with numbers)
            const visitorTypeCtx = document.getElementById('visitorTypeChart').getContext('2d');
            new Chart(visitorTypeCtx, {
                type: 'doughnut',
                data: {
                    labels: ['First Timers', 'Visitors', 'Connected'],
                    datasets: [{
                        data: [firstTimersNotConnected, visitorsNotConnected, totalConnected],
                        backgroundColor: [colors.primary, colors.secondary, colors.success],
                        borderWidth: 0,
                        hoverBackgroundColor: [colors.primaryLight, '#FFB347', '#4DD0C6'],
                        hoverBorderWidth: 0,
                        hoverBorderColor: 'white',
                        hoverBorderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '70%',
                    hover: {
                        mode: 'nearest',
                        intersect: true
                    },
                    plugins: {
                        legend: {
                            position: window.innerWidth < 768 ? 'bottom' : 'right',
                            labels: {
                                padding: 25,
                                usePointStyle: true,
                                pointStyle: 'circle',
                                font: {
                                    family: 'Inter',
                                    size: window.innerWidth < 768 ? 11 : 13,
                                    weight: '600'
                                },
                                color: '#2A2D34'
                            }
                        },
                        tooltip: {
                            enabled: true,
                            backgroundColor: 'rgba(42, 45, 52, 0.95)',
                            titleFont: { family: 'Inter', size: 14, weight: '600' },
                            bodyFont: { family: 'Inter', size: 13, weight: '500' },
                            padding: 14,
                            cornerRadius: 10,
                            boxPadding: 8,
                            callbacks: {
                                label: function(context) {
                                    const total = totalVisitorsAll;
                                    const percentage = total > 0 ? Math.round((context.raw / total) * 100) : 0;
                                    let description = context.label;
                                    
                                    if (context.label === 'Connected' && context.raw === 0) {
                                        description = 'Connected (No one-to-one started yet)';
                                    }
                                    
                                    return `${description}: ${context.raw} (${percentage}%)`;
                                }
                            }
                        },
                        datalabels: {
                            color: '#fff',
                            font: {
                                family: 'Inter',
                                weight: '700',
                                size: window.innerWidth < 768 ? 10 : 14
                            },
                            formatter: function(value, context) {
                                const total = totalVisitorsAll;
                                const percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                                
                                if (context.dataIndex === 2 && value === 0) {
                                    return window.innerWidth < 768 ? '0%' : '0%\n(Not started)';
                                }
                                
                                return value > 0 ? (window.innerWidth < 768 ? `${percentage}%` : `${value}\n(${percentage}%)`) : '';
                            },
                            display: function(context) {
                                const value = context.dataset.data[context.dataIndex];
                                if (context.dataIndex === 2) {
                                    return true;
                                }
                                return value > 0;
                            }
                        }
                    }
                },
                plugins: [ChartDataLabels]
            });

            // Daily Trend Chart (Line chart - NO NUMBERS)
            const dailyTrendCtx = document.getElementById('dailyTrendChart').getContext('2d');
            new Chart(dailyTrendCtx, {
                type: 'line',
                data: {
                    labels: formattedDates,
                    datasets: [
                        {
                            label: 'First Timers',
                            data: firstTimers,
                            borderColor: colors.primary,
                            backgroundColor: 'rgba(255, 107, 53, 0.08)',
                            tension: 0.4,
                            fill: true,
                            pointBackgroundColor: colors.primary,
                            pointBorderColor: '#fff',
                            pointBorderWidth: 3,
                            pointRadius: window.innerWidth < 768 ? 4 : 5,
                            pointHoverRadius: window.innerWidth < 768 ? 6 : 8,
                            borderWidth: window.innerWidth < 768 ? 2 : 3,
                            pointHoverBackgroundColor: colors.primaryLight
                        },
                        {
                            label: 'Visitors',
                            data: visitors,
                            borderColor: colors.secondary,
                            backgroundColor: 'rgba(255, 159, 28, 0.08)',
                            tension: 0.4,
                            fill: true,
                            pointBackgroundColor: colors.secondary,
                            pointBorderColor: '#fff',
                            pointBorderWidth: 3,
                            pointRadius: window.innerWidth < 768 ? 4 : 5,
                            pointHoverRadius: window.innerWidth < 768 ? 6 : 8,
                            borderWidth: window.innerWidth < 768 ? 2 : 3,
                            pointHoverBackgroundColor: '#FFB347'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    hover: {
                        mode: 'index',
                        intersect: false
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.06)',
                                drawBorder: false
                            },
                            ticks: {
                                font: { family: 'Inter', size: window.innerWidth < 768 ? 10 : 12, weight: '500' },
                                color: '#8C8C8C',
                                padding: 10
                            }
                        },
                        x: {
                            grid: {
                                color: 'rgba(0, 0, 0, 0.06)',
                                drawBorder: false
                            },
                            ticks: {
                                font: { family: 'Inter', size: window.innerWidth < 768 ? 9 : 11, weight: '500' },
                                color: '#8C8C8C',
                                maxRotation: window.innerWidth < 768 ? 90 : 45,
                                minRotation: window.innerWidth < 768 ? 90 : 45,
                                padding: 10
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                padding: 20,
                                usePointStyle: true,
                                pointStyle: 'circle',
                                font: {
                                    family: 'Inter',
                                    size: window.innerWidth < 768 ? 11 : 13,
                                    weight: '600'
                                },
                                color: '#2A2D34'
                            }
                        },
                        tooltip: {
                            enabled: true,
                            backgroundColor: 'rgba(42, 45, 52, 0.95)',
                            titleFont: { family: 'Inter', size: 14, weight: '600' },
                            bodyFont: { family: 'Inter', size: 13, weight: '500' },
                            padding: 14,
                            cornerRadius: 10,
                            boxPadding: 8,
                            mode: 'index',
                            intersect: false,
                            callbacks: {
                                label: function(context) {
                                    return `${context.dataset.label}: ${context.raw}`;
                                }
                            }
                        },
                        datalabels: {
                            display: false // Make sure this is false
                        }

                    },
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    }
                }
            });

            // Service Attendance Chart (Doughnut with numbers)
            const serviceCtx = document.getElementById('serviceChart').getContext('2d');
            new Chart(serviceCtx, {
                type: 'doughnut',
                data: {
                    labels: serviceLabels,
                    datasets: [{
                        data: serviceData,
                        backgroundColor: [colors.primary, colors.secondary, colors.success, colors.warning, colors.accent],
                        borderWidth: 0,
                        hoverBackgroundColor: [colors.primaryLight, '#FFB347', '#4DD0C6', '#FFD166', '#FF7A80'],
                        hoverBorderWidth: 0,
                        hoverBorderColor: 'white',
                        hoverBorderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '70%',
                    hover: {
                        mode: 'nearest',
                        intersect: true
                    },
                    plugins: {
                        legend: {
                            position: window.innerWidth < 768 ? 'bottom' : 'right',
                            labels: {
                                padding: 20,
                                usePointStyle: true,
                                pointStyle: 'circle',
                                font: {
                                    family: 'Inter',
                                    size: window.innerWidth < 768 ? 11 : 13,
                                    weight: '600'
                                },
                                color: '#2A2D34'
                            }
                        },
                        tooltip: {
                            enabled: true,
                            backgroundColor: 'rgba(42, 45, 52, 0.95)',
                            titleFont: { family: 'Inter', size: 14, weight: '600' },
                            bodyFont: { family: 'Inter', size: 13, weight: '500' },
                            padding: 14,
                            cornerRadius: 10,
                            boxPadding: 8,
                            callbacks: {
                                label: function(context) {
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = total > 0 ? Math.round((context.raw / total) * 100) : 0;
                                    return `${context.label}: ${context.raw} (${percentage}%)`;
                                }
                            }
                        },
                        datalabels: {
                            color: '#fff',
                            font: {
                                family: 'Inter',
                                weight: '700',
                                size: window.innerWidth < 768 ? 10 : 14
                            },
                            formatter: function(value, context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                                return value > 0 ? (window.innerWidth < 768 ? `${percentage}%` : `${value}\n(${percentage}%)`) : '';
                            },
                            display: function(context) {
                                const value = context.dataset.data[context.dataIndex];
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = (value / total) * 100;
                                return percentage > 12;
                            }
                        }
                    }
                },
                plugins: [ChartDataLabels]
            });

            // Age Group Distribution Chart (Bar with ONLY PERCENTAGES)
            const ageGroupCtx = document.getElementById('ageGroupChart').getContext('2d');
            
            ageGroupChart = new Chart(ageGroupCtx, {
                type: 'bar',
                data: {
                    labels: ageGroupLabels,
                    datasets: [{
                        data: ageGroupData,
                        backgroundColor: ageGroupLabels.map(label => {
                            return ageGroupColors[label] || colors.primary;
                        }),
                        borderWidth: 0,
                        borderRadius: 10,
                        hoverBackgroundColor: ageGroupLabels.map(label => {
                            const baseColor = ageGroupColors[label] || colors.primary;
                            return baseColor.replace(')', ', 0.8)').replace('rgb', 'rgba');
                        }),
                        hoverBorderColor: 'white',
                        hoverBorderWidth: 2,
                        barPercentage: window.innerWidth < 768 ? 0.6 : 0.7,
                        categoryPercentage: window.innerWidth < 768 ? 0.7 : 0.8
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    hover: {
                        mode: 'nearest',
                        intersect: true
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.06)',
                                drawBorder: false
                            },
                            ticks: {
                                font: { family: 'Inter', size: window.innerWidth < 768 ? 10 : 12, weight: '500' },
                                color: '#8C8C8C',
                                padding: 10,
                                callback: function(value) {
                                    return value;
                                }
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                font: { 
                                    family: 'Inter', 
                                    size: window.innerWidth < 768 ? 11 : 13, 
                                    weight: '600' 
                                },
                                color: '#2A2D34',
                                padding: 10,
                                maxRotation: window.innerWidth < 768 ? 90 : 45,
                                minRotation: window.innerWidth < 768 ? 90 : 45
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            enabled: true,
                            backgroundColor: 'rgba(42, 45, 52, 0.95)',
                            titleFont: { family: 'Inter', size: 14, weight: '600' },
                            bodyFont: { family: 'Inter', size: 13, weight: '500' },
                            padding: 14,
                            cornerRadius: 10,
                            boxPadding: 8,
                            callbacks: {
                                label: function(context) {
                                    const total = ageGroupData.reduce((a, b) => a + b, 0);
                                    const percentage = total > 0 ? Math.round((context.raw / total) * 100) : 0;
                                    return `${context.label}: ${context.raw} (${percentage}%)`;
                                }
                            }
                        },
                        datalabels: {
                            display: false // Make sure this is false
                        }
                    }
                }
            });
            
            // Add bar value labels
            setTimeout(() => addBarValueLabels(), 500);
        }

        // Function to add percentage labels on bars
        function addBarValueLabels() {
            if (!ageGroupChart) {
                console.warn('ageGroupChart is not available');
                return;
            }
            
            try {
                const canvas = ageGroupChart.canvas;
                if (!canvas) {
                    console.warn('Canvas not found');
                    return;
                }
                
                const meta = ageGroupChart.getDatasetMeta(0);
                if (!meta) {
                    console.warn('Chart metadata not available');
                    return;
                }
                
                // Clear previous labels
                const existingLabels = canvas.parentNode.querySelectorAll('.bar-value-container');
                existingLabels.forEach(label => label.remove());
                
                // Calculate total for percentages
                const total = ageGroupData.reduce((a, b) => a + b, 0);
                
                // Add percentage labels for each bar
                meta.data.forEach((bar, index) => {
                    if (!bar) return;
                    
                    const value = ageGroupData[index];
                    const percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                    
                    // Position at top of bar
                    const x = bar.x;
                    const y = bar.y - 20; // Position above the bar
                    
                    const labelContainer = document.createElement('div');
                    labelContainer.className = 'bar-value-container';
                    labelContainer.style.left = `${x}px`;
                    labelContainer.style.top = `${y}px`;
                    labelContainer.style.transform = 'translate(-50%, -100%)';
                    labelContainer.style.position = 'absolute';
                    labelContainer.style.pointerEvents = 'none';
                    labelContainer.style.zIndex = '10';
                    
                    // ONLY SHOW PERCENTAGE, NOT THE VALUE
                    labelContainer.innerHTML = `
                        <div class="bar-value-percentage">${value}</div>
                    `;
                    
                    canvas.parentNode.appendChild(labelContainer);
                });
            } catch (error) {
                console.error('Error adding bar value labels:', error);
            }
        }

        // Handle window resize - Update bar percentage labels on resize
        window.addEventListener('resize', function() {
            // Update bar value labels on resize
            if (ageGroupChart) {
                setTimeout(() => addBarValueLabels(), 300);
            }
        });

        // Simple hover effect for charts
        document.querySelectorAll('.chart-wrapper').forEach(wrapper => {
            wrapper.addEventListener('mouseenter', function() {
                this.style.cursor = 'pointer';
            });
            
            wrapper.addEventListener('mouseleave', function() {
                this.style.cursor = 'default';
            });
        });
    </script>
</body>
</html>