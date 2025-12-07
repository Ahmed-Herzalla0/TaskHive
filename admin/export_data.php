<?php
require_once '../config/includes/db_connect.php';
require_once '../config/includes/functions.php';
require_once '../config/includes/navbar.php';

// Hide SQL errors from users
error_reporting(0);
mysqli_report(MYSQLI_REPORT_OFF);

if (!isLoggedIn() || !isAdmin()) {
    redirect('../auth/login.php');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['export'])) {
    $type = $_POST['export_type'] ?? 'users';
    
    // Set headers for CSV download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=TaskHive_' . $type . '_' . date('Y-m-d') . '.csv');
    
    $output = fopen('php://output', 'w');
    
    switch ($type) {
        case 'teams':
            fputcsv($output, ['ID', 'Team Name', 'Leader ID', 'Leader Name', 'Created At']);
            $result = $conn->query("SELECT t.id, t.name, t.leader_id, u.username as leader_name, t.created_at FROM teams t JOIN users u ON t.leader_id = u.id");
            break;
        case 'tasks':
            fputcsv($output, ['ID', 'Title', 'Description', 'Team ID', 'Status', 'Created At']);
            $result = $conn->query("SELECT id, title, description, team_id, status, created_at FROM tasks");
            break;
        case 'submissions':
            fputcsv($output, ['ID', 'Task ID', 'User ID', 'User Name', 'File Path', 'Comments', 'Submitted At']);
            $result = $conn->query("SELECT s.id, s.task_id, s.user_id, u.username, s.file_path, s.comments, s.submitted_at FROM submissions s JOIN users u ON s.user_id = u.id");
            break;
        case 'users':
        default:
            fputcsv($output, ['ID', 'First Name', 'Last Name', 'Username', 'Email', 'Role', 'Created At']);
            $result = $conn->query("SELECT id, first_name, last_name, username, email, role, created_at FROM users");
            break;
    }

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            fputcsv($output, $row);
        }
    }
    
    fclose($output);
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Export Data - TaskHive Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/components.css">
    <link rel="stylesheet" href="../assets/css/pages/admin.css">
</head>
<body class="admin-body">
<?php render_unified_navbar(th_nav_template('admin', [
        'links' => [
                ['label' => 'Dashboard', 'href' => 'admin_dashboard.php', 'icon' => 'glyphicon glyphicon-home', 'slug' => 'dashboard'],
                ['label' => 'Users', 'href' => 'admin.php', 'icon' => 'glyphicon glyphicon-user', 'slug' => 'users'],
                ['label' => 'Export', 'href' => 'export_data.php', 'icon' => 'glyphicon glyphicon-save', 'slug' => 'export'],
        ],
        'active' => 'export',
])); ?>

<div class="container">
    <div class="export-container">
        <h2 style="margin-bottom: 30px; color: #333;">
            <span class="glyphicon glyphicon-download"></span> Export Data
        </h2>
        <p class="text-muted" style="margin-bottom: 40px;">Select the data you want to export as CSV file</p>
        
        <form method="POST" action="">
            <label class="export-option">
                <input type="radio" name="export_type" value="users" checked>
                <div style="display: inline-block; vertical-align: top;">
                    <div class="export-icon"><span class="glyphicon glyphicon-user"></span></div>
                    <h4 style="margin: 0 0 10px 0;">Users Data</h4>
                    <p class="text-muted" style="margin: 0;">Export all users with their information</p>
                </div>
            </label>
            
            <label class="export-option">
                <input type="radio" name="export_type" value="teams">
                <div style="display: inline-block; vertical-align: top;">
                    <div class="export-icon"><span class="glyphicon glyphicon-flag"></span></div>
                    <h4 style="margin: 0 0 10px 0;">Teams Data</h4>
                    <p class="text-muted" style="margin: 0;">Export all teams with leaders</p>
                </div>
            </label>
            
            <label class="export-option">
                <input type="radio" name="export_type" value="tasks">
                <div style="display: inline-block; vertical-align: top;">
                    <div class="export-icon"><span class="glyphicon glyphicon-tasks"></span></div>
                    <h4 style="margin: 0 0 10px 0;">Tasks Data</h4>
                    <p class="text-muted" style="margin: 0;">Export all tasks and their status</p>
                </div>
            </label>
            
            <label class="export-option">
                <input type="radio" name="export_type" value="submissions">
                <div style="display: inline-block; vertical-align: top;">
                    <div class="export-icon"><span class="glyphicon glyphicon-paperclip"></span></div>
                    <h4 style="margin: 0 0 10px 0;">Submissions Data</h4>
                    <p class="text-muted" style="margin: 0;">Export all task submissions</p>
                </div>
            </label>
            
            <button type="submit" name="export" class="btn btn-primary btn-lg btn-block" style="margin-top: 30px; padding: 15px; font-size: 18px;">
                <span class="glyphicon glyphicon-save"></span> Download CSV File
            </button>
            
            <a href="admin_dashboard.php" class="btn btn-link btn-block" style="margin-top: 15px;">
                <span class="glyphicon glyphicon-arrow-left"></span> Back to Dashboard
            </a>
        </form>
    </div>
</div>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
</body>
</html>
