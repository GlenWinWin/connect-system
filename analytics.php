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
            --info: #17a2b8;
            --warning: #ffc107;
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
            padding: 15px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .header {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin-bottom: 25px;
            padding: 20px;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }

        @media (min-width: 768px) {
            .header {
                flex-direction: row;
                justify-content: space-between;
                align-items: center;
            }
        }

        .church-info {
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }

        .logo {
            font-size: 2rem;
            color: var(--primary);
        }

        .church-name {
            font-size: 1.4rem;
            color: var(--dark);
            font-weight: 700;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 10px 16px;
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
            gap: 6px;
            font-size: 0.9rem;
            white-space: nowrap;
        }

        .btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: var(--secondary);
        }

        .btn-success {
            background: var(--success);
        }

        .btn-info {
            background: var(--info);
        }

        /* Date Filter */
        .filter-section {
            background: white;
            padding: 20px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-bottom: 25px;
        }

        .filter-form {
            display: grid;
            grid-template-columns: 1fr;
            gap: 12px;
        }

        @media (min-width: 640px) {
            .filter-form {
                grid-template-columns: 1fr 1fr;
                gap: 15px;
            }
        }

        @media (min-width: 768px) {
            .filter-form {
                grid-template-columns: 1fr 1fr auto auto;
                align-items: end;
            }
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        label {
            font-weight: 600;
            color: var(--dark);
            font-size: 0.85rem;
        }

        input[type="date"] {
            padding: 10px 12px;
            border: 2px solid var(--primary-ultralight);
            border-radius: var(--border-radius);
            font-size: 0.9rem;
            transition: var(--transition);
        }

        input[type="date"]:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 4px rgba(255, 107, 53, 0.2);
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }

        @media (min-width: 640px) {
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 20px;
            }
        }

        .stat-card {
            background: white;
            padding: 20px 15px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            text-align: center;
            border-top: 4px solid var(--primary);
            transition: var(--transition);
        }

        @media (min-width: 768px) {
            .stat-card {
                padding: 25px 20px;
            }
        }

        .stat-card:hover {
            transform: translateY(-3px);
        }

        .stat-card.first-timers {
            border-top-color: #FF6B35;
        }

        .stat-card.visitors {
            border-top-color: #004E89;
        }

        .stat-card.lifegroup {
            border-top-color: #28a745;
        }

        .stat-card.followup {
            border-top-color: #ffc107;
        }

        .stat-number {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 6px;
        }

        @media (min-width: 768px) {
            .stat-number {
                font-size: 2.2rem;
            }
        }

        .stat-card.first-timers .stat-number {
            color: #FF6B35;
        }

        .stat-card.visitors .stat-number {
            color: #004E89;
        }

        .stat-card.lifegroup .stat-number {
            color: #28a745;
        }

        .stat-card.followup .stat-number {
            color: #ffc107;
        }

        .stat-label {
            font-size: 0.85rem;
            color: var(--gray);
            font-weight: 500;
            margin-bottom: 6px;
        }

        @media (min-width: 768px) {
            .stat-label {
                font-size: 0.95rem;
            }
        }

        .stat-percentage {
            font-size: 0.8rem;
            color: var(--gray);
            font-weight: 600;
        }

        /* Charts Grid */
        .charts-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
            margin-bottom: 25px;
        }

        @media (min-width: 768px) {
            .charts-grid {
                grid-template-columns: 1fr 1fr;
                gap: 25px;
            }
        }

        @media (min-width: 1200px) {
            .charts-grid {
                grid-template-columns: 1fr 1fr;
            }
        }

        .chart-container {
            background: white;
            padding: 20px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }

        @media (min-width: 768px) {
            .chart-container {
                padding: 25px;
            }
        }

        .chart-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 15px;
            text-align: center;
        }

        @media (min-width: 768px) {
            .chart-title {
                font-size: 1.3rem;
                margin-bottom: 20px;
            }
        }

        .chart-wrapper {
            position: relative;
            height: 250px;
        }

        @media (min-width: 768px) {
            .chart-wrapper {
                height: 300px;
            }
        }

        /* Daily Breakdown */
        .daily-breakdown {
            background: white;
            padding: 20px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-bottom: 25px;
        }

        @media (min-width: 768px) {
            .daily-breakdown {
                padding: 25px;
            }
        }

        .breakdown-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 15px;
        }

        @media (min-width: 768px) {
            .breakdown-title {
                font-size: 1.3rem;
                margin-bottom: 20px;
            }
        }

        .breakdown-container {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .breakdown-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 600px;
        }

        .breakdown-table th {
            background: var(--primary-ultralight);
            padding: 12px 10px;
            text-align: left;
            font-weight: 600;
            color: var(--dark);
            border-bottom: 2px solid var(--primary);
            font-size: 0.8rem;
            white-space: nowrap;
        }

        @media (min-width: 768px) {
            .breakdown-table th {
                padding: 15px;
                font-size: 0.9rem;
            }
        }

        .breakdown-table td {
            padding: 10px 8px;
            border-bottom: 1px solid var(--primary-ultralight);
            font-size: 0.8rem;
        }

        @media (min-width: 768px) {
            .breakdown-table td {
                padding: 12px 15px;
                font-size: 0.9rem;
            }
        }

        .breakdown-table tr:hover {
            background: var(--primary-ultralight);
        }

        .percentage-bar {
            width: 60px;
            height: 6px;
            background: var(--gray-light);
            border-radius: 3px;
            overflow: hidden;
            display: inline-block;
            margin-right: 8px;
            vertical-align: middle;
        }

        @media (min-width: 768px) {
            .percentage-bar {
                width: 80px;
                height: 8px;
            }
        }

        .percentage-fill {
            height: 100%;
            background: var(--primary);
            border-radius: 3px;
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }

        .empty-state i {
            font-size: 3rem;
            color: var(--primary-light);
            margin-bottom: 15px;
        }

        .empty-state h3 {
            color: var(--dark);
            margin-bottom: 10px;
            font-size: 1.3rem;
        }

        .empty-state p {
            color: var(--gray);
            font-size: 1rem;
        }

        .navigation {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .mobile-only {
            display: block;
        }

        .desktop-only {
            display: none;
        }

        @media (min-width: 768px) {
            .mobile-only {
                display: none;
            }
            .desktop-only {
                display: block;
            }
        }

        /* Compact view for very small screens */
        @media (max-width: 380px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .chart-wrapper {
                height: 200px;
            }
            
            .btn {
                padding: 8px 12px;
                font-size: 0.8rem;
            }
        }

        /* Print styles */
        @media print {
            .btn, .filter-section {
                display: none;
            }
            
            .chart-container, .stat-card {
                break-inside: avoid;
                box-shadow: none;
                border: 1px solid #ddd;
            }
        }
    </style>
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