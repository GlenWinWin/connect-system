<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
requireAuth();

// Query for age group statistics
$age_groups = [
    'River Youth' => 0,
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="css/stats.css">
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