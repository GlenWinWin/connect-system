<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
requireAuth();

// Handle date range and search
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$search = $_GET['search'] ?? '';
$age_group_filter = $_GET['age_group'] ?? '';

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
            max-width: 1400px;
            margin: 0 auto;
        }

        .header {
            display: flex;
            flex-direction: column;
            gap: 20px;
            margin-bottom: 30px;
            padding: 25px;
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
            gap: 20px;
            flex-wrap: wrap;
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

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
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
            font-size: 0.95rem;
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
            background: #17a2b8;
        }

        .search-bar {
            margin-bottom: 25px;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        @media (min-width: 768px) {
            .search-bar {
                flex-direction: row;
                gap: 15px;
            }
        }

        .search-input {
            flex: 1;
            padding: 15px 20px;
            border: 2px solid var(--primary-ultralight);
            border-radius: var(--border-radius);
            font-size: 1rem;
        }

        .actions-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 12px;
            margin-bottom: 25px;
        }

        @media (min-width: 768px) {
            .actions-grid {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            }
        }

        /* Table Styles */
        .table-container {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
            margin-bottom: 30px;
        }

        /* Desktop Table */
        .desktop-table {
            width: 100%;
            border-collapse: collapse;
            display: none;
        }

        @media (min-width: 1024px) {
            .desktop-table {
                display: table;
            }
        }

        .desktop-table th {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 18px 15px;
            text-align: left;
            font-weight: 600;
            font-size: 0.9rem;
            border-bottom: 2px solid var(--primary-dark);
        }

        .desktop-table td {
            padding: 16px 15px;
            border-bottom: 1px solid var(--primary-ultralight);
            font-size: 0.9rem;
        }

        .desktop-table tr:hover {
            background: var(--primary-ultralight);
        }

        /* Mobile Cards */
        .mobile-cards {
            display: block;
            padding: 20px;
        }

        @media (min-width: 1024px) {
            .mobile-cards {
                display: none;
            }
        }

        .visitor-card {
            background: white;
            border: 1px solid var(--primary-ultralight);
            border-radius: var(--border-radius);
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .visitor-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--primary-ultralight);
        }

        .visitor-name {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 5px;
        }

        .visitor-contact {
            color: var(--gray);
            font-size: 0.9rem;
        }

        .visitor-details {
            display: grid;
            grid-template-columns: 1fr;
            gap: 12px;
        }

        @media (min-width: 768px) and (max-width: 1023px) {
            .visitor-details {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        .detail-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .detail-label {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--gray);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .detail-value {
            font-size: 0.9rem;
            color: var(--dark);
            font-weight: 500;
        }

        .badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-block;
        }

        .badge-yes {
            background: #d4edda;
            color: #155724;
        }

        .badge-no {
            background: #f8d7da;
            color: #721c24;
        }

        .badge-age-group {
            background: #e3f2fd;
            color: #1565c0;
            font-weight: 700;
        }

        .follow-up-section {
            background: var(--primary-ultralight);
            padding: 15px;
            border-radius: var(--border-radius);
            margin-top: 15px;
        }

        .follow-up-title {
            color: var(--primary);
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .follow-up-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 10px;
        }

        @media (min-width: 480px) {
            .follow-up-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        .action-buttons {
            display: flex;
            gap: 8px;
            margin-top: 15px;
            flex-wrap: wrap;
        }

        .btn-sm {
            padding: 8px 16px;
            font-size: 0.85rem;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }

        .empty-state i {
            font-size: 4rem;
            color: var(--primary-light);
            margin-bottom: 20px;
        }

        .empty-state h3 {
            color: var(--dark);
            margin-bottom: 10px;
            font-size: 1.5rem;
        }

        .empty-state p {
            color: var(--gray);
            font-size: 1.1rem;
        }

        .stats-bar {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 25px;
        }

        @media (min-width: 768px) {
            .stats-bar {
                grid-template-columns: repeat(4, 1fr);
            }
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            text-align: center;
            border-top: 4px solid var(--primary);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 0.9rem;
            color: var(--gray);
            font-weight: 500;
        }

        .date-filter {
            background: white;
            padding: 20px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-bottom: 25px;
        }

        .date-filter-form {
            display: grid;
            grid-template-columns: 1fr;
            gap: 12px;
        }

        @media (min-width: 640px) {
            .date-filter-form {
                grid-template-columns: 1fr 1fr;
                gap: 15px;
            }
        }

        @media (min-width: 768px) {
            .date-filter-form {
                grid-template-columns: 1fr 1fr auto auto auto;
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

        input[type="date"], select {
            padding: 10px 12px;
            border: 2px solid var(--primary-ultralight);
            border-radius: var(--border-radius);
            font-size: 0.9rem;
            transition: var(--transition);
        }

        input[type="date"]:focus, select:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 4px rgba(255, 107, 53, 0.2);
        }

        .age-group-section {
            background: white;
            padding: 20px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-bottom: 25px;
        }

        .age-group-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--primary-ultralight);
        }

        .age-group-title {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--primary);
        }

        .age-group-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .age-group-card {
            background: var(--primary-ultralight);
            padding: 20px;
            border-radius: var(--border-radius);
            text-align: center;
            border-left: 4px solid var(--primary);
            transition: var(--transition);
            cursor: pointer;
        }

        .age-group-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--box-shadow);
        }

        .age-group-card.active {
            background: var(--primary);
            color: white;
        }

        .age-group-card.active .age-group-count {
            color: white;
        }

        .age-group-name {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .age-group-count {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--primary);
        }

        .age-group-card.active .age-group-count {
            color: white;
        }

        .visitors-by-age-group {
            margin-top: 10px;
        }

        .age-group-divider {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 15px 20px;
            margin: 20px 0;
            border-radius: var(--border-radius);
            font-weight: 600;
            font-size: 1.1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .age-group-divider i {
            font-size: 1.2rem;
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
                <h1 class="church-name">Visitor Management</h1>
            </div>
            <div class="user-info">
                <span style="font-weight: 500;">Welcome, <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'User'); ?></span>
                <a href="logout.php" class="btn btn-secondary">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>

        <div class="date-filter">
            <form method="GET" class="date-filter-form">
                <div class="form-group">
                    <label for="start_date">Start Date</label>
                    <input type="date" id="start_date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>" required>
                </div>
                <div class="form-group">
                    <label for="end_date">End Date</label>
                    <input type="date" id="end_date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>" required>
                </div>
                <div class="form-group">
                    <label for="age_group">Age Group</label>
                    <select id="age_group" name="age_group">
                        <option value="">All Age Groups</option>
                        <option value="River Youth (13 to 19 years old)" <?php echo $age_group_filter === 'River Youth (13 to 19 years old)' ? 'selected' : ''; ?>>River Youth (13 to 19 years old)</option>
                        <option value="Young Adult (20 to 35 years old)" <?php echo $age_group_filter === 'Young Adult (20 to 35 years old)' ? 'selected' : ''; ?>>Young Adult (20 to 35 years old)</option>
                        <option value="River Men (36 to 50 years old)" <?php echo $age_group_filter === 'River Men (36 to 50 years old)' ? 'selected' : ''; ?>>River Men (36 to 50 years old)</option>
                        <option value="River Women (36 to 50 years old)" <?php echo $age_group_filter === 'River Women (36 to 50 years old)' ? 'selected' : ''; ?>>River Women (36 to 50 years old)</option>
                        <option value="Seasoned (51 years old and above)" <?php echo $age_group_filter === 'Seasoned (51 years old and above)' ? 'selected' : ''; ?>>Seasoned (51 years old and above)</option>
                    </select>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-filter"></i> Apply Filter
                    </button>
                </div>
                <div class="form-group">
                    <a href="dashboard.php" class="btn btn-secondary">
                        <i class="fas fa-sync"></i> Reset
                    </a>
                </div>
            </form>
        </div>

        <!-- Age Group Statistics -->
        <div class="age-group-section">
            <div class="age-group-header">
                <h2 class="age-group-title">Age Group Distribution</h2>
                <span class="detail-label">Total Visitors: <?php echo count($first_timers); ?></span>
            </div>
            
            <div class="age-group-grid">
                <?php foreach ($age_groups as $age_group): ?>
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
        <div class="stats-bar">
            <div class="stat-card">
                <div class="stat-number"><?php echo count($first_timers); ?></div>
                <div class="stat-label">Total Visitors</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count(array_filter($first_timers, fn($v) => $v['lifegroup'] === 'YES')); ?></div>
                <div class="stat-label">Lifegroup Interest</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count(array_filter($first_timers, fn($v) => $v['texted_already'])); ?></div>
                <div class="stat-label">Texted</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count(array_filter($first_timers, fn($v) => $v['started_one2one'])); ?></div>
                <div class="stat-label">One-to-One Started</div>
            </div>
        </div>

        <div class="search-bar">
            <form method="GET" style="display: flex; width: 100%; gap: 15px;">
                <input type="hidden" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
                <input type="hidden" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
                <input type="hidden" name="age_group" value="<?php echo htmlspecialchars($age_group_filter); ?>">
                <input type="text" name="search" class="search-input" placeholder="Search by name, contact, or service..." 
                       value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn">
                    <i class="fas fa-search"></i> Search
                </button>
            </form>
        </div>

        <div class="actions-grid">
            <a href="analytics.php" class="btn btn-info">
                <i class="fas fa-chart-bar"></i> Analytics
            </a>
            <a href="export.php" class="btn btn-success">
                <i class="fas fa-file-export"></i> Export CSV
            </a>
            <a href="connect-form.php" class="btn btn-secondary" target="_blank">
                <i class="fas fa-plus"></i> Public Form
            </a>
            <a href="stats_visitor_type.php" class="btn">
                <i class="fas fa-users"></i> Visitor Type Stats
            </a>
            <a href="stats_life_stages.php" class="btn btn-success">
                <i class="fas fa-chart-pie"></i> Life Stages Stats
            </a>
        </div>

        <?php if (empty($first_timers)): ?>
            <div class="empty-state">
                <i class="fas fa-users"></i>
                <h3>No Visitors Found</h3>
                <p>No visitors have been added yet. <a href="connect-form.php" target="_blank">Add the first visitor</a></p>
            </div>
        <?php else: ?>
            <!-- Group visitors by age group -->
            <?php 
            $grouped_visitors = [];
            foreach ($first_timers as $visitor) {
                $age_group = $visitor['age_group'] ?: 'Not Specified';
                $grouped_visitors[$age_group][] = $visitor;
            }
            ?>

            <?php foreach ($grouped_visitors as $age_group_name => $visitors_in_group): ?>
                <?php if (count($grouped_visitors) > 1): ?>
                    <div class="age-group-divider">
                        <span>
                            <i class="fas fa-users"></i> 
                            <?php echo htmlspecialchars($age_group_name); ?> Age Group 
                            (<?php echo count($visitors_in_group); ?> visitors)
                        </span>
                        <span class="badge badge-age-group"><?php echo count($visitors_in_group); ?></span>
                    </div>
                <?php endif; ?>

                <!-- Desktop Table (shows on large screens) -->
                <div class="table-container">
                    <table class="desktop-table">
                        <thead>
                            <tr>
                                <th>Date & Time</th>
                                <th>Name</th>
                                <th>Contact</th>
                                <th>Age Group</th>
                                <th>Messenger</th>
                                <th>Visitor Type</th>
                                <th>Invited By</th>
                                <th>Service</th>
                                <th>Lifegroup</th>
                                <th>Texted</th>
                                <th>One-to-One</th>
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
                                        <div class="visitor-name" style="font-size: 1rem; margin-bottom: 2px;"><?php echo htmlspecialchars($visitor['fullname']); ?></div>
                                        <div class="detail-label">Approached by: <?php echo htmlspecialchars($visitor['approached_by']); ?></div>
                                    </td>
                                    <td><?php echo htmlspecialchars($visitor['contact']); ?></td>
                                    <td>
                                        <span class="badge badge-age-group"><?php echo htmlspecialchars($visitor['age_group'] ?: 'Not Specified'); ?></span>
                                    </td>
                                    <td><?php echo htmlspecialchars($visitor['messenger']); ?></td>
                                    <td><?php echo htmlspecialchars($visitor['iam']); ?></td>
                                    <td><?php echo htmlspecialchars($visitor['invited_by'] ?: 'Walk In'); ?></td>
                                    <td><?php echo htmlspecialchars($visitor['service_attended']); ?></td>
                                    <td>
                                        <span class="badge <?php echo $visitor['lifegroup'] === 'YES' ? 'badge-yes' : 'badge-no'; ?>">
                                            <?php echo $visitor['lifegroup']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $visitor['texted_already'] ? 'badge-yes' : 'badge-no'; ?>">
                                            <?php echo $visitor['texted_already'] ? 'Yes' : 'No'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $visitor['started_one2one'] ? 'badge-yes' : 'badge-no'; ?>">
                                            <?php echo $visitor['started_one2one'] ? 'Yes' : 'No'; ?>
                                        </span>
                                    </td>
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

                                <div class="visitor-details">
                                    <div class="detail-group">
                                        <span class="detail-label">Age Group</span>
                                        <span class="detail-value">
                                            <span class="badge badge-age-group"><?php echo htmlspecialchars($visitor['age_group'] ?: 'Not Specified'); ?></span>
                                        </span>
                                    </div>

                                    <div class="detail-group">
                                        <span class="detail-label">Messenger</span>
                                        <span class="detail-value"><?php echo htmlspecialchars($visitor['messenger']); ?></span>
                                    </div>

                                    <div class="detail-group">
                                        <span class="detail-label">Visitor Type</span>
                                        <span class="detail-value"><?php echo htmlspecialchars($visitor['iam']); ?></span>
                                    </div>

                                    <div class="detail-group">
                                        <span class="detail-label">Invited By</span>
                                        <span class="detail-value"><?php echo htmlspecialchars($visitor['invited_by'] ?: 'Walk In'); ?></span>
                                    </div>

                                    <div class="detail-group">
                                        <span class="detail-label">Service</span>
                                        <span class="detail-value"><?php echo htmlspecialchars($visitor['service_attended']); ?></span>
                                    </div>

                                    <div class="detail-group">
                                        <span class="detail-label">Approached By</span>
                                        <span class="detail-value"><?php echo htmlspecialchars($visitor['approached_by']); ?></span>
                                    </div>

                                    <div class="detail-group">
                                        <span class="detail-label">Connected With</span>
                                        <span class="detail-value"><?php echo htmlspecialchars($visitor['connected_with']); ?></span>
                                    </div>

                                    <div class="detail-group">
                                        <span class="detail-label">Lifegroup</span>
                                        <span class="detail-value">
                                            <span class="badge <?php echo $visitor['lifegroup'] === 'YES' ? 'badge-yes' : 'badge-no'; ?>">
                                                <?php echo $visitor['lifegroup']; ?>
                                            </span>
                                        </span>
                                    </div>
                                </div>

                                <!-- Follow-up Section -->
                                <div class="follow-up-section">
                                    <div class="follow-up-title">Follow-up Status</div>
                                    <div class="follow-up-grid">
                                        <div class="detail-group">
                                            <span class="detail-label">Texted</span>
                                            <span class="detail-value">
                                                <span class="badge <?php echo $visitor['texted_already'] ? 'badge-yes' : 'badge-no'; ?>">
                                                    <?php echo $visitor['texted_already'] ? 'Yes' : 'No'; ?>
                                                </span>
                                            </span>
                                        </div>

                                        <div class="detail-group">
                                            <span class="detail-label">One-to-One</span>
                                            <span class="detail-value">
                                                <span class="badge <?php echo $visitor['started_one2one'] ? 'badge-yes' : 'badge-no'; ?>">
                                                    <?php echo $visitor['started_one2one'] ? 'Yes' : 'No'; ?>
                                                </span>
                                            </span>
                                        </div>

                                        <?php if ($visitor['followed_up_by']): ?>
                                        <div class="detail-group">
                                            <span class="detail-label">Followed Up By</span>
                                            <span class="detail-value"><?php echo htmlspecialchars($visitor['followed_up_by']); ?></span>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($visitor['update_report']): ?>
                                    <div class="detail-group" style="margin-top: 10px;">
                                        <span class="detail-label">Update / Report</span>
                                        <span class="detail-value"><?php echo htmlspecialchars($visitor['update_report']); ?></span>
                                    </div>
                                    <?php endif; ?>
                                </div>

                                <div class="action-buttons">
                                    <a href="edit_first_timer.php?id=<?php echo $visitor['id']; ?>" class="btn btn-sm">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <small class="detail-label" style="margin-left: auto;">
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

    <script>
        function filterAgeGroup(ageGroup) {
            const url = new URL(window.location.href);
            url.searchParams.set('age_group', ageGroup);
            window.location.href = url.toString();
        }
    </script>
</body>
</html>