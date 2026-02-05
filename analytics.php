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
    <style>
        :root {
            --primary: #FF6B35;
            --primary-light: #FF8E53;
            --primary-dark: #E55A2B;
            --secondary: #FF9F1C;
            --accent: #FF5A5F;
            --success: #2EC4B6;
            --warning: #FFBF69;
            --info: #3A86FF;
            --light: #FFF8F0;
            --dark: #2A2D34;
            --gray: #8C8C8C;
            --gray-light: #F5F3F4;
            --orange-gradient: linear-gradient(135deg, #FF6B35 0%, #FF9F1C 100%);
            --warm-gradient: linear-gradient(135deg, #FF8E53 0%, #FFBF69 100%);
            --border-radius: 16px;
            --border-radius-sm: 10px;
            --shadow: 0 8px 30px rgba(255, 107, 53, 0.12);
            --shadow-lg: 0 15px 50px rgba(255, 107, 53, 0.18);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #FFF8F0 0%, #FFFAF5 100%);
            color: var(--dark);
            line-height: 1.6;
            min-height: 100vh;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at 20% 80%, rgba(255, 107, 53, 0.08) 0%, transparent 50%),
                        radial-gradient(circle at 80% 20%, rgba(255, 159, 28, 0.06) 0%, transparent 50%);
            z-index: -1;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        /* Header Styles */
        .header {
            background: var(--orange-gradient);
            border-radius: var(--border-radius);
            padding: 25px 30px;
            margin-bottom: 30px;
            box-shadow: var(--shadow);
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: white;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url("data:image/svg+xml,%3Csvg width='100' height='100' viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M11 18c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm48 25c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm-43-7c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm63 31c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM34 90c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm56-76c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM12 86c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm28-65c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm23-11c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-6 60c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm29 22c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zM32 63c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm57-13c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-9-21c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM60 91c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM35 41c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM12 60c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2z' fill='%23ffffff' fill-opacity='0.1' fill-rule='evenodd'/%3E%3C/svg%3E");
            opacity: 0.3;
        }

        .church-info {
            display: flex;
            align-items: center;
            gap: 20px;
            z-index: 1;
        }

        .logo {
            width: 60px;
            height: 60px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .church-name {
            font-family: 'Poppins', sans-serif;
            font-size: 28px;
            font-weight: 700;
            letter-spacing: -0.5px;
            text-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
            z-index: 1;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            border-radius: var(--border-radius-sm);
            border: none;
            font-family: 'Inter', sans-serif;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
        }

        .btn-secondary {
            background: linear-gradient(135deg, var(--primary-light) 0%, var(--secondary) 100%);
            box-shadow: 0 4px 15px rgba(255, 142, 83, 0.2);
        }

        .btn-secondary:hover {
            box-shadow: 0 8px 25px rgba(255, 142, 83, 0.3);
        }

        /* Filter Section */
        .filter-section {
            background: white;
            border-radius: var(--border-radius);
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: var(--shadow);
            border: 1px solid rgba(255, 107, 53, 0.1);
            position: relative;
            overflow: hidden;
        }

        .filter-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: var(--orange-gradient);
        }

        .filter-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            align-items: end;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .form-group label {
            font-size: 14px;
            font-weight: 600;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .form-group label i {
            color: var(--primary);
        }

        .form-group input,
        .form-group select {
            padding: 12px 16px;
            border: 2px solid var(--gray-light);
            border-radius: var(--border-radius-sm);
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            transition: var(--transition);
            background: white;
            color: var(--dark);
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(255, 107, 53, 0.15);
        }

        .btn-success {
            background: linear-gradient(135deg, var(--success) 0%, #25a898 100%);
            color: white;
            border: none;
        }

        .btn-success:hover {
            background: linear-gradient(135deg, var(--success) 0%, #25a898 100%);
            color: white;
            border: none;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 30px;
            box-shadow: var(--shadow);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255, 107, 53, 0.1);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: var(--orange-gradient);
        }

        .stat-card:hover {
            transform: translateY(-10px);
            box-shadow: var(--shadow-lg);
        }

        .stat-card.first-timers .stat-number {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .stat-card.visitors .stat-number {
            background: linear-gradient(135deg, var(--secondary) 0%, #FF8C00 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .stat-card.lifegroup .stat-number {
            background: linear-gradient(135deg, var(--success) 0%, #1E9B8A 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .stat-card.followup .stat-number {
            background: linear-gradient(135deg, var(--accent) 0%, #E63946 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .stat-number {
            font-family: 'Poppins', sans-serif;
            font-size: 44px;
            font-weight: 800;
            line-height: 1;
            margin-bottom: 15px;
            position: relative;
            display: inline-block;
        }

        .stat-number::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 40px;
            height: 3px;
            background: var(--orange-gradient);
            border-radius: 2px;
        }

        .stat-label {
            font-size: 16px;
            font-weight: 600;
            color: var(--gray);
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .stat-label i {
            font-size: 18px;
            color: var(--primary);
        }

        .stat-percentage {
            font-size: 14px;
            font-weight: 600;
            color: white;
            padding: 8px 16px;
            background: var(--orange-gradient);
            border-radius: 20px;
            display: inline-block;
            box-shadow: 0 4px 15px rgba(255, 107, 53, 0.2);
        }

        /* Charts Grid */
        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
        }

        @media (max-width: 1200px) {
            .charts-grid {
                grid-template-columns: 1fr;
            }
        }

        .chart-container {
            background: white;
            border-radius: var(--border-radius);
            padding: 30px;
            box-shadow: var(--shadow);
            transition: var(--transition);
            border: 1px solid rgba(255, 107, 53, 0.1);
            position: relative;
            overflow: hidden;
        }

        .chart-container::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, rgba(255, 107, 53, 0.05) 0%, rgba(255, 159, 28, 0.05) 100%);
            border-radius: 0 0 0 100%;
        }

        .chart-container:hover {
            box-shadow: var(--shadow-lg);
            transform: translateY(-5px);
        }

        .chart-title {
            font-family: 'Poppins', sans-serif;
            font-size: 20px;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--gray-light);
            position: relative;
        }

        .chart-title::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 60px;
            height: 2px;
            background: var(--orange-gradient);
        }

        .chart-title i {
            color: var(--primary);
            font-size: 22px;
        }

        .chart-wrapper {
            height: 320px;
            position: relative;
        }

        /* Chart Number Overlays */
        .doughnut-center-text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
            background: white;
            width: 100px;
            height: 100px;
            border-radius: 50%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            box-shadow: 0 8px 25px rgba(255, 107, 53, 0.15);
            border: 3px solid var(--primary-light);
        }

        .center-total {
            font-family: 'Poppins', sans-serif;
            font-size: 26px;
            font-weight: 800;
            color: var(--primary);
            line-height: 1;
        }

        .center-label {
            font-size: 12px;
            color: var(--gray);
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-top: 6px;
            font-weight: 600;
        }

        /* Age Group Bar Value Styles */
        .bar-value-container {
            position: absolute;
            display: flex;
            flex-direction: column;
            align-items: center;
            pointer-events: none;
            z-index: 10;
        }

        .bar-value-number {
            font-family: 'Poppins', sans-serif;
            font-size: 16px;
            font-weight: 800;
            padding: 6px 10px;
            border-radius: 8px;
            margin-bottom: 4px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.25);
            white-space: nowrap;
            color: white;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.5);
            background: rgba(42, 45, 52, 0.9);
            backdrop-filter: blur(10px);
            border: 2px solid rgba(255, 255, 255, 0.2);
            min-width: 50px;
            text-align: center;
        }

        .bar-value-percentage {
            font-size: 12px;
            font-weight: 700;
            color: white;
            background: rgba(255, 107, 53, 0.9);
            padding: 4px 8px;
            border-radius: 6px;
            box-shadow: 0 3px 10px rgba(255, 107, 53, 0.3);
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 80px 20px;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            border: 1px solid rgba(255, 107, 53, 0.1);
        }

        .empty-state i {
            font-size: 64px;
            color: var(--gray-light);
            margin-bottom: 20px;
        }

        .empty-state h3 {
            font-family: 'Poppins', sans-serif;
            font-size: 24px;
            color: var(--dark);
            margin-bottom: 10px;
        }

        .empty-state p {
            color: var(--gray);
            max-width: 400px;
            margin: 0 auto;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }

            .header {
                flex-direction: column;
                gap: 20px;
                text-align: center;
                padding: 20px;
            }

            .church-info {
                flex-direction: column;
                text-align: center;
            }

            .church-name {
                font-size: 24px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .chart-wrapper {
                height: 280px;
            }

            .charts-grid {
                grid-template-columns: 1fr;
            }

            .stat-number {
                font-size: 38px;
            }

            .filter-form {
                grid-template-columns: 1fr;
            }

            .bar-value-number {
                font-size: 14px;
                padding: 4px 8px;
                min-width: 45px;
            }

            .bar-value-percentage {
                font-size: 10px;
                padding: 3px 6px;
            }
        }

        /* Animation */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes float {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-10px);
            }
        }

        .stat-card, .chart-container {
            animation: fadeInUp 0.6s cubic-bezier(0.4, 0, 0.2, 1) forwards;
            opacity: 0;
        }

        .stat-card:nth-child(1) { animation-delay: 0.1s; }
        .stat-card:nth-child(2) { animation-delay: 0.2s; }
        .stat-card:nth-child(3) { animation-delay: 0.3s; }
        .stat-card:nth-child(4) { animation-delay: 0.4s; }
        .chart-container:nth-child(1) { animation-delay: 0.5s; }
        .chart-container:nth-child(2) { animation-delay: 0.6s; }
        .chart-container:nth-child(3) { animation-delay: 0.7s; }
        .chart-container:nth-child(4) { animation-delay: 0.8s; }

        /* Scrollbar Styling */
        ::-webkit-scrollbar {
            width: 10px;
            height: 10px;
        }

        ::-webkit-scrollbar-track {
            background: var(--gray-light);
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            border-radius: 10px;
            border: 2px solid var(--gray-light);
        }

        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, var(--primary-dark) 0%, #FF8C00 100%);
        }

        /* Hover Effects */
        .stat-card:hover .stat-number::after {
            width: 100%;
            transition: width 0.3s ease;
        }

        .chart-container:hover .chart-title::after {
            width: 100%;
            transition: width 0.3s ease;
        }

        /* Loading Animation */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 107, 53, 0.3);
            border-radius: 50%;
            border-top-color: var(--primary);
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="church-info">
                <div class="logo">
                    <i class="fas fa-chart-network"></i>
                </div>
                <h1 class="church-name">Visitor Analytics Dashboard</h1>
            </div>
            <div class="user-info">
                <a href="dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> <span class="desktop-only">Dashboard</span>
                </a>
                <a href="logout.php" class="btn">
                    <i class="fas fa-sign-out-alt"></i> <span class="desktop-only">Logout</span>
                </a>
            </div>
        </div>

        <!-- Date Filter -->
        <div class="filter-section">
            <form method="GET" class="filter-form">
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
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-filter"></i> Apply Filter
                    </button>
                </div>
                <div class="form-group">
                    <a href="analytics.php" class="btn btn-secondary">
                        <i class="fas fa-sync-alt"></i> Reset
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

                <!-- Daily Trend Chart -->
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

                <!-- Age Group Distribution Chart -->
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

        // Visitor Type Chart (Doughnut with numbers) - UPDATED with dynamic connected data
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
                    hoverBorderWidth: 0
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
                            padding: 25,
                            usePointStyle: true,
                            pointStyle: 'circle',
                            font: {
                                family: 'Inter',
                                size: 13,
                                weight: '600'
                            },
                            color: '#2A2D34'
                        }
                    },
                    tooltip: {
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
                                
                                // Add description for connected if it's 0
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
                            size: window.innerWidth < 768 ? 12 : 14
                        },
                        formatter: function(value, context) {
                            const total = totalVisitorsAll;
                            const percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                            
                            // Don't show 0 values for Connected
                            if (context.dataIndex === 2 && value === 0) {
                                return '0%\n(Not started)';
                            }
                            
                            return value > 0 ? `${value}\n(${percentage}%)` : '';
                        },
                        display: function(context) {
                            const value = context.dataset.data[context.dataIndex];
                            // Always show Connected even if 0
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

        // Daily Trend Chart (Line chart WITHOUT numbers on points)
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
                        pointRadius: 5,
                        pointHoverRadius: 8,
                        borderWidth: 3,
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
                        pointRadius: 5,
                        pointHoverRadius: 8,
                        borderWidth: 3,
                        pointHoverBackgroundColor: '#FFB347'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.06)',
                            drawBorder: false
                        },
                        ticks: {
                            font: { family: 'Inter', size: 12, weight: '500' },
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
                            font: { family: 'Inter', size: 11, weight: '500' },
                            color: '#8C8C8C',
                            maxRotation: 45,
                            minRotation: 45,
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
                                size: 13,
                                weight: '600'
                            },
                            color: '#2A2D34'
                        }
                    },
                    tooltip: {
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
                    hoverBorderWidth: 0
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
                            padding: 20,
                            usePointStyle: true,
                            pointStyle: 'circle',
                            font: {
                                family: 'Inter',
                                size: 13,
                                weight: '600'
                            },
                            color: '#2A2D34'
                        }
                    },
                    tooltip: {
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
                            size: window.innerWidth < 768 ? 12 : 14
                        },
                        formatter: function(value, context) {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                            return value > 0 ? `${value}\n(${percentage}%)` : '';
                        },
                        display: function(context) {
                            const value = context.dataset.data[context.dataIndex];
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = (value / total) * 100;
                            return percentage > 12; // Only show for segments > 12%
                        }
                    }
                }
            },
            plugins: [ChartDataLabels]
        });

        // Age Group Distribution Chart (Bar with WHITE numbers)
        const ageGroupCtx = document.getElementById('ageGroupChart').getContext('2d');
        const ageGroupChart = new Chart(ageGroupCtx, {
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
                        // Lighten color for hover effect
                        return baseColor.replace(')', ', 0.8)').replace('rgb', 'rgba');
                    }),
                    barPercentage: 0.7,
                    categoryPercentage: 0.8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.06)',
                            drawBorder: false
                        },
                        ticks: {
                            font: { family: 'Inter', size: 12, weight: '500' },
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
                                size: 13, 
                                weight: '600' 
                            },
                            color: '#2A2D34',
                            padding: 10,
                            maxRotation: 45,
                            minRotation: 45
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
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
                    }
                },
                animation: {
                    onComplete: function() {
                        addBarValueLabels(ageGroupChart);
                    }
                },
                resize: {
                    onResize: function() {
                        setTimeout(() => addBarValueLabels(ageGroupChart), 100);
                    }
                }
            }
        });

        // Function to add WHITE value labels to bars
        function addBarValueLabels(chart) {
            const canvas = chart.canvas;
            const meta = chart.getDatasetMeta(0);
            
            // Clear previous labels
            const existingLabels = canvas.parentNode.querySelectorAll('.bar-value-container');
            existingLabels.forEach(label => label.remove());
            
            // Calculate total for percentages
            const total = ageGroupData.reduce((a, b) => a + b, 0);
            
            // Add labels for each bar
            meta.data.forEach((bar, index) => {
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
                
                labelContainer.innerHTML = `
                    <div class="bar-value-number">${value}</div>
                    <div class="bar-value-percentage">${percentage}%</div>
                `;
                
                canvas.parentNode.appendChild(labelContainer);
            });
        }

        // Add animation to stat cards on hover
        document.addEventListener('DOMContentLoaded', function() {
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    const number = this.querySelector('.stat-number');
                    number.style.transform = 'scale(1.05)';
                    number.style.transition = 'transform 0.3s ease';
                });
                
                card.addEventListener('mouseleave', function() {
                    const number = this.querySelector('.stat-number');
                    number.style.transform = 'scale(1)';
                });
            });
        });

        // Handle window resize
        window.addEventListener('resize', function() {
            // Update bar value labels on resize
            if (ageGroupChart) {
                setTimeout(() => addBarValueLabels(ageGroupChart), 100);
            }
        });
    </script>
</body>
</html>