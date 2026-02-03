<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
requireAuth();

// Default date range (last 30 days)
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');

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
        WHERE DATE(created_at) BETWEEN ? AND ?
        GROUP BY DATE(created_at)
        ORDER BY date";

$stmt = $pdo->prepare($sql);
$stmt->execute([$start_date, $end_date]);
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
$service_sql = "SELECT service_attended, COUNT(*) as count FROM first_timers WHERE DATE(created_at) BETWEEN ? AND ? GROUP BY service_attended";
$service_stmt = $pdo->prepare($service_sql);
$service_stmt->execute([$start_date, $end_date]);
$service_data = $service_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics - River of God Church</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="css/analytics.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="church-info">
                <div class="logo">
                    <i class="fas fa-chart-bar"></i>
                </div>
                <h1 class="church-name">Visitor Analytics</h1>
            </div>
            <div class="user-info">
                <div class="navigation">
                    <a href="dashboard.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> <span class="desktop-only">Dashboard</span>
                    </a>
                    <a href="logout.php" class="btn">
                        <i class="fas fa-sign-out-alt"></i> <span class="desktop-only">Logout</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Date Filter -->
        <div class="filter-section">
            <form method="GET" class="filter-form">
                <div class="form-group">
                    <label for="start_date">Start Date</label>
                    <input type="date" id="start_date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>" required>
                </div>
                <div class="form-group">
                    <label for="end_date">End Date</label>
                    <input type="date" id="end_date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>" required>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-filter"></i> <span class="desktop-only">Apply Filter</span>
                        <span class="mobile-only">Apply</span>
                    </button>
                </div>
                <div class="form-group">
                    <a href="analytics.php" class="btn btn-secondary">
                        <i class="fas fa-sync"></i> <span class="desktop-only">Reset</span>
                    </a>
                </div>
            </form>
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
                    <div class="stat-label">First Timers</div>
                    <div class="stat-percentage">
                        <?php echo $total_visitors_all > 0 ? round(($total_first_timers / $total_visitors_all) * 100, 1) : 0; ?>% of total
                    </div>
                </div>

                <div class="stat-card visitors">
                    <div class="stat-number"><?php echo $total_visitors; ?></div>
                    <div class="stat-label">Visitors</div>
                    <div class="stat-percentage">
                        <?php echo $total_visitors_all > 0 ? round(($total_visitors / $total_visitors_all) * 100, 1) : 0; ?>% of total
                    </div>
                </div>

                <div class="stat-card lifegroup">
                    <div class="stat-number"><?php echo $total_lifegroup_interest; ?></div>
                    <div class="stat-label">Lifegroup Interest</div>
                    <div class="stat-percentage">
                        <?php echo $total_visitors_all > 0 ? round(($total_lifegroup_interest / $total_visitors_all) * 100, 1) : 0; ?>% interested
                    </div>
                </div>

                <div class="stat-card followup">
                    <div class="stat-number"><?php echo $total_one2one; ?></div>
                    <div class="stat-label">One-to-One Started</div>
                    <div class="stat-percentage">
                        <?php echo $total_first_timers > 0 ? round(($total_one2one / $total_first_timers) * 100, 1) : 0; ?>% of first timers
                    </div>
                </div>
            </div>

            <!-- Charts -->
            <div class="charts-grid">
                <div class="chart-container">
                    <h3 class="chart-title">First Timers vs Visitors</h3>
                    <div class="chart-wrapper">
                        <canvas id="visitorTypeChart"></canvas>
                    </div>
                </div>

                <div class="chart-container">
                    <h3 class="chart-title">Daily Visitor Trend</h3>
                    <div class="chart-wrapper">
                        <canvas id="dailyTrendChart"></canvas>
                    </div>
                </div>

                <div class="chart-container">
                    <h3 class="chart-title">Follow-up Progress</h3>
                    <div class="chart-wrapper">
                        <canvas id="followupChart"></canvas>
                    </div>
                </div>

                <div class="chart-container">
                    <h3 class="chart-title">Service Attendance</h3>
                    <div class="chart-wrapper">
                        <canvas id="serviceChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Daily Breakdown -->
            <div class="daily-breakdown">
                <h3 class="breakdown-title">Daily Breakdown</h3>
                <div class="breakdown-container">
                    <table class="breakdown-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>First Timers</th>
                                <th>Visitors</th>
                                <th>Total</th>
                                <th>Lifegroup</th>
                                <th>Texted</th>
                                <th>1-to-1</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($daily_data as $day): ?>
                                <tr>
                                    <td><?php echo date('M j', strtotime($day['date'])); ?></td>
                                    <td><?php echo $day['first_timers']; ?></td>
                                    <td><?php echo $day['visitors']; ?></td>
                                    <td><?php echo $day['total']; ?></td>
                                    <td>
                                        <?php echo $day['lifegroup_interest']; ?>
                                        <?php if ($day['total'] > 0): ?>
                                            <div class="percentage-bar">
                                                <div class="percentage-fill" style="width: <?php echo ($day['lifegroup_interest'] / $day['total']) * 100; ?>%"></div>
                                            </div>
                                            <?php echo round(($day['lifegroup_interest'] / $day['total']) * 100, 1); ?>%
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $day['texted']; ?></td>
                                    <td><?php echo $day['started_one2one']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Prepare data for charts
        const dates = <?php echo json_encode(array_column($daily_data, 'date')); ?>;
        const firstTimers = <?php echo json_encode(array_column($daily_data, 'first_timers')); ?>;
        const visitors = <?php echo json_encode(array_column($daily_data, 'visitors')); ?>;
        const totals = <?php echo json_encode(array_column($daily_data, 'total')); ?>;
        const lifegroupInterest = <?php echo json_encode(array_column($daily_data, 'lifegroup_interest')); ?>;
        const texted = <?php echo json_encode(array_column($daily_data, 'texted')); ?>;
        const startedOne2one = <?php echo json_encode(array_column($daily_data, 'started_one2one')); ?>;

        // Format dates for mobile
        const formattedDates = dates.map(date => {
            const d = new Date(date);
            return window.innerWidth < 768 ? 
                `${d.getMonth()+1}/${d.getDate()}` : 
                d.toLocaleDateString();
        });

        // Visitor Type Chart (Pie)
        new Chart(document.getElementById('visitorTypeChart'), {
            type: 'doughnut',
            data: {
                labels: ['First Timers', 'Visitors'],
                datasets: [{
                    data: [<?php echo $total_first_timers; ?>, <?php echo $total_visitors; ?>],
                    backgroundColor: ['#FF6B35', '#004E89'],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: window.innerWidth < 768 ? 'bottom' : 'right'
                    }
                }
            }
        });

        // Daily Trend Chart (Line)
        new Chart(document.getElementById('dailyTrendChart'), {
            type: 'line',
            data: {
                labels: formattedDates,
                datasets: [
                    {
                        label: 'First Timers',
                        data: firstTimers,
                        borderColor: '#FF6B35',
                        backgroundColor: 'rgba(255, 107, 53, 0.1)',
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: 'Visitors',
                        data: visitors,
                        borderColor: '#004E89',
                        backgroundColor: 'rgba(0, 78, 137, 0.1)',
                        tension: 0.4,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Follow-up Progress Chart (Bar)
        new Chart(document.getElementById('followupChart'), {
            type: 'bar',
            data: {
                labels: ['Texted', 'One-to-One'],
                datasets: [{
                    data: [<?php echo $total_texted; ?>, <?php echo $total_one2one; ?>],
                    backgroundColor: ['#17a2b8', '#ffc107'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });

        // Service Attendance Chart
        new Chart(document.getElementById('serviceChart'), {
            type: 'pie',
            data: {
                labels: <?php echo json_encode(array_column($service_data, 'service_attended')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($service_data, 'count')); ?>,
                    backgroundColor: ['#FF6B35', '#004E89', '#28a745', '#ffc107', '#17a2b8'],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: window.innerWidth < 768 ? 'bottom' : 'right'
                    }
                }
            }
        });

        // Handle window resize for chart responsiveness
        window.addEventListener('resize', function() {
            // Charts automatically resize due to Chart.js responsive options
        });
    </script>
</body>
</html>