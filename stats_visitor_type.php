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

} catch (Exception $e) {
    $error_message = "Error fetching statistics: " . $e->getMessage();
    $total_visitors = 0;
    $texted_count = 0;
    $one2one_count = 0;
    $most_common_type = 'Error';
    $most_common_count = 0;
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
            <div class="stat-card">
                <div class="stat-number"><?php echo $total_visitors; ?></div>
                <div class="stat-label">Total Visitors</div>
                <div class="stat-subtext">All time</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?php echo $most_common_count; ?></div>
                <div class="stat-label">Most Common Type</div>
                <div class="stat-subtext"><?php echo htmlspecialchars($most_common_type); ?></div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?php echo $texted_count; ?></div>
                <div class="stat-label">Texted Already</div>
                <div class="stat-subtext">Follow-up initiated</div>
            </div>
        </div>

        <!-- Follow-up Statistics -->
        <div class="card">
            <h3 class="chart-title">Follow-up Progress</h3>
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

        <!-- Visitor Type Distribution -->
        <div class="card">
            <h3 class="chart-title">Visitor Type Distribution</h3>
            <div style="height: 400px; display: flex; align-items: center; justify-content: center; background: var(--primary-ultralight); border-radius: var(--border-radius);">
                <!-- Chart would be implemented with Chart.js or similar library -->
                <div style="text-align: center; color: var(--gray);">
                    <i class="fas fa-chart-bar" style="font-size: 3rem; margin-bottom: 10px;"></i>
                    <p>Bar Chart: Visitor Type Distribution</p>
                    <p style="font-size: 0.9rem; margin-top: 10px;">Total: <?php echo $total_visitors; ?> visitors</p>
                </div>
            </div>
        </div>

        <!-- Detailed Breakdown -->
        <div class="table-container">
            <h3>Visitor Type Breakdown</h3>
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
                    <?php foreach ($visitor_types as $type => $count): 
                        $percentage = $total_visitors > 0 ? round(($count / $total_visitors) * 100, 1) : 0;
                    ?>
                        <tr>
                            <td><?php echo htmlspecialchars($type); ?></td>
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

        <!-- Recent Activity (Last 30 Days) -->
        <div class="table-container">
            <h3>Recent Activity (Last 30 Days)</h3>
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
                    foreach ($recent_types as $type => $count): 
                        $percentage = $recent_total > 0 ? round(($count / $recent_total) * 100, 1) : 0;
                    ?>
                        <tr>
                            <td><?php echo htmlspecialchars($type); ?></td>
                            <td><?php echo $count; ?></td>
                            <td class="percentage"><?php echo $percentage; ?>%</td>
                        </tr>
                    <?php endforeach; ?>
                    <tr style="background-color: var(--primary-ultralight); font-weight: 600;">
                        <td>Total (Last 30 Days)</td>
                        <td><?php echo $recent_total; ?></td>
                        <td>100%</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>