<?php require_once 'layouts/header.php'; ?>

<div class="container">
    <header>
        <div class="header-left">
            <h1><i class="fas fa-church"></i> River of God Church Dashboard</h1>
            <p>Visitor Management System</p>
        </div>
        <div class="header-right">
            <div class="user-info">
                <div class="user-name"><?php echo htmlspecialchars($user['fullname']); ?></div>
                <div class="user-role"><?php echo htmlspecialchars($user['role']); ?></div>
            </div>
            <a href="/logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </header>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-number"><?php echo $statistics['total_visitors']; ?></div>
            <div class="stat-label">Total Visitors</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-user-plus"></i>
            </div>
            <div class="stat-number"><?php echo $statistics['total_first_timers']; ?></div>
            <div class="stat-label">First Timers</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-user-check"></i>
            </div>
            <div class="stat-number"><?php echo $statistics['total_regulars']; ?></div>
            <div class="stat-label">Regular Visitors</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-user-friends"></i>
            </div>
            <div class="stat-number"><?php echo $statistics['total_members']; ?></div>
            <div class="stat-label">Members</div>
        </div>
    </div>

    <!-- First Timers Table -->
    <div class="content-section">
        <div class="section-header">
            <h2 class="section-title">
                <i class="fas fa-user-plus"></i> First Timers List
            </h2>
            <div class="section-actions">
                <button class="btn btn-primary" onclick="window.location.href='/connect-form.php'">
                    <i class="fas fa-plus"></i> Add New Visitor
                </button>
            </div>
        </div>
        <div class="table-container">
            <?php if (count($first_timers) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Contact</th>
                            <th>Age Group</th>
                            <th>Service Time</th>
                            <th>Invited By</th>
                            <th>Life Group</th>
                            <th>Date Added</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($first_timers as $visitor): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($visitor['fullname']); ?></td>
                                <td><?php echo htmlspecialchars($visitor['contact']); ?></td>
                                <td><?php echo htmlspecialchars($visitor['age_group']); ?></td>
                                <td><?php echo htmlspecialchars($visitor['service_time']); ?></td>
                                <td><?php echo htmlspecialchars($visitor['invited_by'] ?: 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($visitor['lifegroup']); ?></td>
                                <td><?php echo date('M j, Y', strtotime($visitor['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-data">
                    <i class="fas fa-user-slash"></i>
                    <h3>No First Timers Found</h3>
                    <p>No first-time visitors have been registered yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- All Visitors Table -->
    <div class="content-section">
        <div class="section-header">
            <h2 class="section-title">
                <i class="fas fa-list"></i> All Visitors
            </h2>
        </div>
        <div class="table-container">
            <?php if (count($visitors) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Name</th>
                            <th>Contact</th>
                            <th>Age Group</th>
                            <th>Service</th>
                            <th>Date Added</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($visitors as $visitor): ?>
                            <tr>
                                <td>
                                    <span class="badge badge-<?php echo strtolower(str_replace(' ', '-', $visitor['iam'])); ?>">
                                        <?php echo htmlspecialchars($visitor['iam']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($visitor['fullname']); ?></td>
                                <td><?php echo htmlspecialchars($visitor['contact']); ?></td>
                                <td><?php echo htmlspecialchars($visitor['age_group']); ?></td>
                                <td><?php echo htmlspecialchars($visitor['service_time']); ?></td>
                                <td><?php echo date('M j, Y', strtotime($visitor['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-data">
                    <i class="fas fa-users-slash"></i>
                    <h3>No Visitors Found</h3>
                    <p>No visitors have been registered yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'layouts/footer.php'; ?>