<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
requireAuth();

// Query for visitor type statistics
$visitor_types = [
    'LOOKING FOR A CHURCH' => 0,
    'VISITOR' => 0,
    'FROM OTHER CHURCH' => 0
];

try {
    // Get counts for each visitor type
    foreach ($visitor_types as $type => $count) {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM first_timers WHERE iam = ?");
        $stmt->execute([$type]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $visitor_types[$type] = $result['count'];
    }

    // Get total visitors
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM first_timers");
    $total_result = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_visitors = $total_result['total'];

    // Get recent visitors by type (last 30 days)
    $recent_types = [];
    foreach ($visitor_types as $type => $count) {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM first_timers WHERE iam = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
        $stmt->execute([$type]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $recent_types[$type] = $result['count'];
    }

    // Get most common visitor type
    $stmt = $pdo->query("SELECT iam, COUNT(*) as count FROM first_timers GROUP BY iam ORDER BY count DESC LIMIT 1");
    $common_result = $stmt->fetch(PDO::FETCH_ASSOC);
    $most_common_type = $common_result['iam'] ?? 'No data';
    $most_common_count = $common_result['count'] ?? 0;

    // Get follow-up statistics
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM first_timers WHERE texted_already = 1");
    $texted_result = $stmt->fetch(PDO::FETCH_ASSOC);
    $texted_count = $texted_result['count'];

    $stmt = $pdo->query("SELECT COUNT(*) as count FROM first_timers WHERE started_one2one = 1");
    $one2one_result = $stmt->fetch(PDO::FETCH_ASSOC);
    $one2one_count = $one2one_result['count'];

    // Get data for charts
    $visitor_labels = array_map(function($type) {
        return str_replace('_', ' ', $type);
    }, array_keys($visitor_types));
    $visitor_counts = array_values($visitor_types);
    $recent_counts = array_values($recent_types);

} catch (Exception $e) {
    $error_message = "Error fetching statistics: " . $e->getMessage();
    $total_visitors = 0;
    $texted_count = 0;
    $one2one_count = 0;
    $most_common_type = 'Error';
    $most_common_count = 0;
    $visitor_labels = [];
    $visitor_counts = [];
    $recent_counts = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visitor Type Statistics - River of God Church</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="css/stats.css">
    <style>
        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .chart-container {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .chart-title {
            margin-top: 0;
            margin-bottom: 20px;
            color: #2c3e50;
            font-size: 1.2rem;
        }
        
        .chart-wrapper {
            height: 300px;
            position: relative;
        }
        
        .stat-card {
            color: white;
            border-radius: 10px;
            padding: 25px;
            text-align: center;
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .stat-label {
            font-size: 1rem;
            opacity: 0.9;
        }
        
        .stat-subtext {
            font-size: 0.9rem;
            opacity: 0.8;
            margin-top: 5px;
        }
        
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .follow-up-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .follow-up-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .table-container {
            background: white;
            border-radius: 10px;
            padding: 25px;
            margin-top: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        .data-table th {
            background-color: #f8f9fa;
            padding: 12px 15px;
            text-align: left;
            font-weight: 600;
            color: #2c3e50;
            border-bottom: 2px solid #dee2e6;
        }
        
        .data-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #dee2e6;
        }
        
        .data-table tr:hover {
            background-color: #f8f9fa;
        }
        
        .progress-bar {
            width: 100%;
            height: 10px;
            background-color: #e9ecef;
            border-radius: 5px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            border-radius: 5px;
        }
        
        .percentage {
            font-weight: bold;
            color: #2c3e50;
        }
        
        @media (max-width: 768px) {
            .charts-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-container {
                grid-template-columns: 1fr;
            }
            
            .follow-up-stats {
                grid-template-columns: 1fr;
            }
            
            .chart-wrapper {
                height: 250px;
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
                <h1 class="church-name">Visitor Type Statistics</h1>
            </div>
            <div class="user-info">
                <a href="dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="stats-container">
            <div class="stat-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <div class="stat-number"><?php echo $total_visitors; ?></div>
                <div class="stat-label">Total Visitors</div>
                <div class="stat-subtext">All time</div>
            </div>
            
            <div class="stat-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                <div class="stat-number"><?php echo $most_common_count; ?></div>
                <div class="stat-label">Most Common Type</div>
                <div class="stat-subtext"><?php echo htmlspecialchars($most_common_type); ?></div>
            </div>
            
            <div class="stat-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                <div class="stat-number"><?php echo $texted_count; ?></div>
                <div class="stat-label">Texted Already</div>
                <div class="stat-subtext">Follow-up initiated</div>
            </div>
        </div>

        <!-- Charts Grid -->
        <div class="charts-grid">
            <!-- Pie Chart -->
            <div class="chart-container">
                <h3 class="chart-title"><i class="fas fa-chart-pie"></i> Visitor Type Distribution</h3>
                <div class="chart-wrapper">
                    <canvas id="visitorTypePieChart"></canvas>
                </div>
            </div>

            <!-- Bar Chart -->
            <div class="chart-container">
                <h3 class="chart-title"><i class="fas fa-chart-bar"></i> Visitor Type Comparison</h3>
                <div class="chart-wrapper">
                    <canvas id="visitorTypeBarChart"></canvas>
                </div>
            </div>

            <!-- Doughnut Chart -->
            <div class="chart-container">
                <h3 class="chart-title"><i class="fas fa-chart-donut"></i> Follow-up Progress</h3>
                <div class="chart-wrapper">
                    <canvas id="followupDoughnutChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Follow-up Statistics -->
        <div class="chart-container">
            <h3 class="chart-title"><i class="fas fa-bullseye"></i> Follow-up Progress</h3>
            <div class="follow-up-stats">
                <div class="follow-up-card">
                    <div class="stat-number"><?php echo $texted_count; ?></div>
                    <div class="stat-label">Texted</div>
                    <div class="stat-subtext"><?php echo $total_visitors > 0 ? round(($texted_count / $total_visitors) * 100, 1) : 0; ?>% of total</div>
                </div>
                <div class="follow-up-card">
                    <div class="stat-number"><?php echo $one2one_count; ?></div>
                    <div class="stat-label">One-to-One Started</div>
                    <div class="stat-subtext"><?php echo $total_visitors > 0 ? round(($one2one_count / $total_visitors) * 100, 1) : 0; ?>% of total</div>
                </div>
            </div>
        </div>

        <!-- Detailed Breakdown -->
        <div class="table-container">
            <h3><i class="fas fa-table"></i> Visitor Type Breakdown</h3>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Visitor Type</th>
                        <th>Count</th>
                        <th>Percentage</th>
                        <th>Distribution</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $visitorColors = ['#FF6B35', '#004E89', '#28a745'];
                    $visitorHoverColors = ['#FF8555', '#006EAA', '#34c759'];
                    $i = 0;
                    foreach ($visitor_types as $type => $count): 
                        $percentage = $total_visitors > 0 ? round(($count / $total_visitors) * 100, 1) : 0;
                        $color = $visitorColors[$i % count($visitorColors)];
                        $i++;
                    ?>
                        <tr>
                            <td>
                                <span style="display: inline-block; width: 12px; height: 12px; background-color: <?php echo $color; ?>; border-radius: 50%; margin-right: 8px;"></span>
                                <?php echo htmlspecialchars(str_replace('_', ' ', $type)); ?>
                            </td>
                            <td><strong><?php echo $count; ?></strong></td>
                            <td class="percentage"><?php echo $percentage; ?>%</td>
                            <td style="width: 200px;">
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo $percentage; ?>%; background: <?php echo $color; ?>;"></div>
                                </div>
                                <small style="display: block; text-align: right; margin-top: 3px; color: #666;">
                                    <?php echo $percentage; ?>%
                                </small>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($total_visitors > 0): ?>
                    <tr style="background-color: #f8f9fa; font-weight: 600;">
                        <td>Total</td>
                        <td><?php echo $total_visitors; ?></td>
                        <td>100%</td>
                        <td>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: 100%; background: linear-gradient(90deg, #667eea, #764ba2);"></div>
                            </div>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Recent Activity (Last 30 Days) -->
        <div class="table-container">
            <h3><i class="fas fa-history"></i> Recent Activity (Last 30 Days)</h3>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Visitor Type</th>
                        <th>Recent Count</th>
                        <th>Percentage of Recent</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $recent_total = array_sum($recent_types);
                    $i = 0;
                    foreach ($recent_types as $type => $count): 
                        $percentage = $recent_total > 0 ? round(($count / $recent_total) * 100, 1) : 0;
                        $color = $visitorColors[$i % count($visitorColors)];
                        $i++;
                    ?>
                        <tr>
                            <td>
                                <span style="display: inline-block; width: 12px; height: 12px; background-color: <?php echo $color; ?>; border-radius: 50%; margin-right: 8px;"></span>
                                <?php echo htmlspecialchars(str_replace('_', ' ', $type)); ?>
                            </td>
                            <td><?php echo $count; ?></td>
                            <td class="percentage"><?php echo $percentage; ?>%</td>
                        </tr>
                    <?php endforeach; ?>
                    <tr style="background-color: #f8f9fa; font-weight: 600;">
                        <td>Total (Last 30 Days)</td>
                        <td><?php echo $recent_total; ?></td>
                        <td>100%</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // Visitor Type Data from PHP
        const visitorLabels = <?php echo json_encode($visitor_labels); ?>;
        const visitorCounts = <?php echo json_encode($visitor_counts); ?>;
        const recentCounts = <?php echo json_encode($recent_counts); ?>;
        const visitorColors = ['#FF6B35', '#004E89', '#28a745'];
        const visitorHoverColors = ['#FF8555', '#006EAA', '#34c759'];

        // Follow-up Data
        const followupLabels = ['Texted', 'Not Texted'];
        const followupCounts = [<?php echo $texted_count; ?>, <?php echo $total_visitors - $texted_count; ?>];
        const one2oneLabels = ['One-to-One', 'Not Started'];
        const one2oneCounts = [<?php echo $one2one_count; ?>, <?php echo $total_visitors - $one2one_count; ?>];

        // Visitor Type Pie Chart
        const visitorPieCtx = document.getElementById('visitorTypePieChart').getContext('2d');
        new Chart(visitorPieCtx, {
            type: 'pie',
            data: {
                labels: visitorLabels,
                datasets: [{
                    data: visitorCounts,
                    backgroundColor: visitorColors,
                    borderColor: '#fff',
                    borderWidth: 2,
                    hoverBackgroundColor: visitorHoverColors
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: window.innerWidth < 768 ? 'bottom' : 'right',
                        labels: {
                            padding: 20,
                            usePointStyle: true,
                            pointStyle: 'circle'
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.label || '';
                                let value = context.raw || 0;
                                let total = context.dataset.data.reduce((a, b) => a + b, 0);
                                let percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });

        // Visitor Type Bar Chart
        const visitorBarCtx = document.getElementById('visitorTypeBarChart').getContext('2d');
        new Chart(visitorBarCtx, {
            type: 'bar',
            data: {
                labels: visitorLabels,
                datasets: [{
                    label: 'Total Visitors',
                    data: visitorCounts,
                    backgroundColor: visitorColors,
                    borderColor: visitorColors,
                    borderWidth: 1,
                    borderRadius: 5,
                    hoverBackgroundColor: visitorHoverColors
                },
                {
                    label: 'Recent (30 days)',
                    data: recentCounts,
                    backgroundColor: visitorColors.map(color => color + '80'), // 50% opacity
                    borderColor: visitorColors,
                    borderWidth: 1,
                    borderRadius: 5,
                    hoverBackgroundColor: visitorHoverColors.map(color => color + '80')
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return value;
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            pointStyle: 'rect'
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `${context.dataset.label}: ${context.raw}`;
                            }
                        }
                    }
                }
            }
        });

        // Follow-up Doughnut Chart
        const followupCtx = document.getElementById('followupDoughnutChart').getContext('2d');
        new Chart(followupCtx, {
            type: 'doughnut',
            data: {
                labels: followupLabels,
                datasets: [{
                    data: followupCounts,
                    backgroundColor: ['#28a745', '#e9ecef'],
                    borderColor: '#fff',
                    borderWidth: 3,
                    hoverBackgroundColor: ['#34c759', '#f8f9fa'],
                    hoverBorderWidth: 3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: {
                        position: window.innerWidth < 768 ? 'bottom' : 'right',
                        labels: {
                            padding: 15,
                            usePointStyle: true
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.label || '';
                                let value = context.raw || 0;
                                let total = context.dataset.data.reduce((a, b) => a + b, 0);
                                let percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });

        // Handle window resize
        window.addEventListener('resize', function() {
            // Charts will automatically resize due to Chart.js responsive options
        });
    </script>
</body>
</html>