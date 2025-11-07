<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
requireAuth();

// Query for age group statistics
$age_groups = [
    'River Youth (13 to 19 years old)' => 0,
    'Young Adult (20 to 35 years old)' => 0,
    'River Men (36 to 50 years old)' => 0,
    'River Women (36 to 50 years old)' => 0,
    'Seasoned (51 years old and above)' => 0
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

} catch (Exception $e) {
    $error_message = "Error fetching statistics: " . $e->getMessage();
    $total_visitors = 0;
    $recent_visitors = 0;
    $most_common_age = 'Error';
    $most_common_count = 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Life Stages Statistics - River of God Church</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding: 20px;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }

        .church-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .logo {
            font-size: 2.5rem;
            color: var(--primary);
        }

        .church-name {
            font-size: 1.8rem;
            color: var(--dark);
            font-weight: 700;
        }

        .btn {
            padding: 12px 24px;
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
            gap: 8px;
        }

        .btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: var(--secondary);
        }

        .card {
            background: white;
            margin-bottom: 25px;
            padding: 30px 25px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            transition: var(--transition);
            border-top: 4px solid var(--primary);
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            text-align: center;
            border-top: 4px solid var(--primary);
            transition: var(--transition);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 35px rgba(255, 107, 53, 0.2);
        }

        .stat-number {
            font-size: 3rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 10px;
        }

        .stat-label {
            font-size: 1.1rem;
            color: var(--dark);
            font-weight: 600;
        }

        .stat-subtext {
            font-size: 0.9rem;
            color: var(--gray);
            margin-top: 5px;
        }

        .chart-container {
            background: white;
            padding: 30px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-bottom: 25px;
        }

        .chart-title {
            font-size: 1.4rem;
            color: var(--primary);
            margin-bottom: 20px;
            text-align: center;
            font-weight: 700;
        }

        .table-container {
            background: white;
            padding: 25px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow-x: auto;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .data-table th,
        .data-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid var(--gray-light);
        }

        .data-table th {
            background-color: var(--primary-ultralight);
            color: var(--primary);
            font-weight: 600;
        }

        .data-table tr:hover {
            background-color: var(--primary-ultralight);
        }

        .percentage {
            color: var(--success);
            font-weight: 600;
        }

        .progress-bar {
            width: 100%;
            height: 8px;
            background-color: var(--gray-light);
            border-radius: 4px;
            overflow: hidden;
            margin-top: 5px;
        }

        .progress-fill {
            height: 100%;
            background-color: var(--primary);
            border-radius: 4px;
            transition: width 0.3s ease;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: var(--border-radius);
            font-weight: 500;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 20px;
                text-align: center;
            }
            
            .church-info {
                flex-direction: column;
                gap: 15px;
            }
            
            .church-name {
                font-size: 1.5rem;
            }
            
            .btn {
                padding: 10px 20px;
                font-size: 0.9rem;
            }
            
            .stats-container {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .stat-card {
                padding: 20px;
            }
            
            .stat-number {
                font-size: 2.5rem;
            }
            
            .chart-container {
                padding: 20px;
            }
            
            .table-container {
                padding: 15px;
            }
            
            .data-table {
                font-size: 0.9rem;
            }
            
            .data-table th,
            .data-table td {
                padding: 10px 8px;
            }
        }

        @media (max-width: 480px) {
            body {
                padding: 10px;
            }
            
            .container {
                padding: 0 10px;
            }
            
            .data-table {
                font-size: 0.8rem;
            }
            
            .data-table th,
            .data-table td {
                padding: 8px 5px;
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
            <div class="stat-card">
                <div class="stat-number"><?php echo $total_visitors; ?></div>
                <div class="stat-label">Total Visitors</div>
                <div class="stat-subtext">All time</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?php echo $recent_visitors; ?></div>
                <div class="stat-label">Recent Visitors</div>
                <div class="stat-subtext">Last 30 days</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?php echo $most_common_count; ?></div>
                <div class="stat-label">Most Common Group</div>
                <div class="stat-subtext"><?php echo htmlspecialchars($most_common_age); ?></div>
            </div>
        </div>

        <!-- Age Group Distribution -->
        <div class="card">
            <h3 class="chart-title">Age Group Distribution</h3>
            <div style="height: 400px; display: flex; align-items: center; justify-content: center; background: var(--primary-ultralight); border-radius: var(--border-radius);">
                <!-- Chart would be implemented with Chart.js or similar library -->
                <div style="text-align: center; color: var(--gray);">
                    <i class="fas fa-chart-pie" style="font-size: 3rem; margin-bottom: 10px;"></i>
                    <p>Pie Chart: Age Group Distribution</p>
                    <p style="font-size: 0.9rem; margin-top: 10px;">Total: <?php echo $total_visitors; ?> visitors</p>
                </div>
            </div>
        </div>

        <!-- Detailed Breakdown -->
        <div class="table-container">
            <h3>Age Group Breakdown</h3>
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
                    <?php foreach ($age_groups as $group => $count): 
                        $percentage = $total_visitors > 0 ? round(($count / $total_visitors) * 100, 1) : 0;
                    ?>
                        <tr>
                            <td><?php echo htmlspecialchars($group); ?></td>
                            <td><?php echo $count; ?></td>
                            <td class="percentage"><?php echo $percentage; ?>%</td>
                            <td style="width: 150px;">
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo $percentage; ?>%"></div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>