<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
requireAuth();

// Query for age group statistics
$age_groups = [
    'Youth' => 0,
    'Young Adult' => 0,
    'River Men' => 0,
    'River Women' => 0,
    'Seasoned' => 0
];

try {
    // Get counts for each age group
    foreach ($age_groups as $age_group => $count) {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM first_timers WHERE age_group = ?");
        $stmt->execute([$age_group]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $age_groups[$age_group] = $result['count'];
    }

    // Get total visitors
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM first_timers");
    $total_result = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_visitors = $total_result['total'];

    // Get recent visitors (last 30 days)
    $stmt = $pdo->prepare("SELECT COUNT(*) as recent FROM first_timers WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $stmt->execute();
    $recent_result = $stmt->fetch(PDO::FETCH_ASSOC);
    $recent_visitors = $recent_result['recent'];

    // Get most common age group
    $stmt = $pdo->query("SELECT age_group, COUNT(*) as count FROM first_timers GROUP BY age_group ORDER BY count DESC LIMIT 1");
    $common_result = $stmt->fetch(PDO::FETCH_ASSOC);
    $most_common_age = $common_result['age_group'] ?? 'No data';
    $most_common_count = $common_result['count'] ?? 0;

    // Get age group data for charts
    $age_labels = array_keys($age_groups);
    $age_counts = array_values($age_groups);

} catch (Exception $e) {
    $error_message = "Error fetching statistics: " . $e->getMessage();
    $total_visitors = 0;
    $recent_visitors = 0;
    $most_common_age = 'Error';
    $most_common_count = 0;
    $age_labels = [];
    $age_counts = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Life Stages Statistics - River of God Church</title>
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            background: linear-gradient(90deg, #3498db, #2ecc71);
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
                <h1 class="church-name">Life Stages Statistics</h1>
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
                <div class="stat-number"><?php echo $recent_visitors; ?></div>
                <div class="stat-label">Recent Visitors</div>
                <div class="stat-subtext">Last 30 days</div>
            </div>
            
            <div class="stat-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                <div class="stat-number"><?php echo $most_common_count; ?></div>
                <div class="stat-label">Most Common Group</div>
                <div class="stat-subtext"><?php echo htmlspecialchars($most_common_age); ?></div>
            </div>
        </div>

        <!-- Charts Grid -->
        <div class="charts-grid">
            <!-- Pie Chart -->
            <div class="chart-container">
                <h3 class="chart-title"><i class="fas fa-chart-pie"></i> Age Group Distribution</h3>
                <div class="chart-wrapper">
                    <canvas id="ageGroupPieChart"></canvas>
                </div>
            </div>

            <!-- Bar Chart -->
            <div class="chart-container">
                <h3 class="chart-title"><i class="fas fa-chart-bar"></i> Age Group Comparison</h3>
                <div class="chart-wrapper">
                    <canvas id="ageGroupBarChart"></canvas>
                </div>
            </div>

            <!-- Doughnut Chart -->
            <div class="chart-container">
                <h3 class="chart-title"><i class="fas fa-chart-donut"></i> Age Group Breakdown</h3>
                <div class="chart-wrapper">
                    <canvas id="ageGroupDoughnutChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Detailed Breakdown -->
        <div class="table-container">
            <h3><i class="fas fa-table"></i> Age Group Breakdown</h3>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Age Group</th>
                        <th>Count</th>
                        <th>Percentage</th>
                        <th>Distribution</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $colors = ['#FF6B35', '#004E89', '#28a745', '#ffc107', '#17a2b8'];
                    $i = 0;
                    foreach ($age_groups as $group => $count): 
                        $percentage = $total_visitors > 0 ? round(($count / $total_visitors) * 100, 1) : 0;
                        $color = $colors[$i % count($colors)];
                        $i++;
                    ?>
                        <tr>
                            <td>
                                <span style="display: inline-block; width: 12px; height: 12px; background-color: <?php echo $color; ?>; border-radius: 50%; margin-right: 8px;"></span>
                                <?php echo htmlspecialchars($group); ?>
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
    </div>

    <script>
        // Age Group Data from PHP
        const ageGroupLabels = <?php echo json_encode($age_labels); ?>;
        const ageGroupCounts = <?php echo json_encode($age_counts); ?>;
        const ageGroupColors = ['#FF6B35', '#004E89', '#28a745', '#ffc107', '#17a2b8'];
        const ageGroupHoverColors = ['#FF8555', '#006EAA', '#34c759', '#ffd350', '#2ebad8'];

        // Pie Chart
        const pieCtx = document.getElementById('ageGroupPieChart').getContext('2d');
        new Chart(pieCtx, {
            type: 'pie',
            data: {
                labels: ageGroupLabels,
                datasets: [{
                    data: ageGroupCounts,
                    backgroundColor: ageGroupColors,
                    borderColor: '#fff',
                    borderWidth: 2,
                    hoverBackgroundColor: ageGroupHoverColors
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

        // Bar Chart
        const barCtx = document.getElementById('ageGroupBarChart').getContext('2d');
        new Chart(barCtx, {
            type: 'bar',
            data: {
                labels: ageGroupLabels,
                datasets: [{
                    label: 'Visitors',
                    data: ageGroupCounts,
                    backgroundColor: ageGroupColors,
                    borderColor: ageGroupColors,
                    borderWidth: 1,
                    borderRadius: 5,
                    hoverBackgroundColor: ageGroupHoverColors
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
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `Visitors: ${context.raw}`;
                            }
                        }
                    }
                }
            }
        });

        // Doughnut Chart
        const doughnutCtx = document.getElementById('ageGroupDoughnutChart').getContext('2d');
        new Chart(doughnutCtx, {
            type: 'doughnut',
            data: {
                labels: ageGroupLabels,
                datasets: [{
                    data: ageGroupCounts,
                    backgroundColor: ageGroupColors,
                    borderColor: '#fff',
                    borderWidth: 3,
                    hoverBackgroundColor: ageGroupHoverColors,
                    hoverBorderWidth: 3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '60%',
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