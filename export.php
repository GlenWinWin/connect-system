<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
requireAuth();

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=church_visitors_' . date('Y-m-d') . '.csv');

// Create output stream
$output = fopen('php://output', 'w');

// CSV headers
fputcsv($output, [
    'ID',
    'I Am',
    'Full Name', 
    'Contact',
    'Age Group',
    'Messenger',
    'Service Attended',
    'Invited By',
    'Lifegroup',
    'Connected With',
    'Approached By',
    'Texted Already',
    'Update/Report',
    'Followed Up By',
    'Started One-to-One',
    'Created At',
    'Updated At'
]);

// Fetch all visitors
$stmt = $pdo->query("SELECT * FROM first_timers ORDER BY created_at DESC");

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    fputcsv($output, [
        $row['id'],
        $row['iam'],
        $row['fullname'],
        $row['contact'],
        $row['age_group'],
        $row['messenger'],
        $row['service_attended'],
        $row['invited_by'],
        $row['lifegroup'],
        $row['connected_with'],
        $row['approached_by'],
        $row['texted_already'] ? 'Yes' : 'No',
        $row['update_report'],
        $row['followed_up_by'],
        $row['started_one2one'] ? 'Yes' : 'No',
        $row['created_at'],
        $row['updated_at']
    ]);
}

fclose($output);
exit();