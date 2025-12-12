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

// Create activity_log table if not exists
$conn->query("CREATE TABLE IF NOT EXISTS activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(255) NOT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
)");

// Get activities (with pagination)
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

$total_result = $conn->query("SELECT COUNT(*) as total FROM activity_log");
$total_activities = $total_result->fetch_assoc()['total'];
$total_pages = max(1, (int)ceil($total_activities / $per_page));
$offset = min($offset, max(0, ($total_pages - 1) * $per_page));
$activities = $conn->query(
    "SELECT al.*, u.username, u.email
     FROM activity_log al
     LEFT JOIN users u ON al.user_id = u.id
     ORDER BY al.created_at DESC
     LIMIT $offset, $per_page"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Log - TaskHive Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/components.css">
    <link rel="stylesheet" href="../assets/css/pages/admin.css">
    <script src="../assets/js/main.js"></script>
</head>
<body class="admin-body">
<?php render_unified_navbar(th_nav_template('admin', [
        'links' => [
            ['label' => 'Dashboard', 'href' => 'admin_dashboard.php', 'icon' => 'glyphicon glyphicon-home', 'slug' => 'dashboard'],
            ['label' => 'Users', 'href' => 'admin.php', 'icon' => 'glyphicon glyphicon-user', 'slug' => 'users'],
            ['label' => 'Teams', 'href' => 'admin.php?view=teams', 'icon' => 'glyphicon glyphicon-flag', 'slug' => 'teams'],
            ['label' => 'Tasks', 'href' => 'admin.php?view=tasks', 'icon' => 'glyphicon glyphicon-tasks', 'slug' => 'tasks'],
            ['label' => 'System', 'href' => 'status.php', 'icon' => 'glyphicon glyphicon-cog', 'slug' => 'system'],
            ['label' => 'Activity', 'href' => 'activity_log.php', 'icon' => 'glyphicon glyphicon-time', 'slug' => 'activity'],
        ],
        'active' => 'activity',
    ])); ?>

<div class="container admin-main-container">
    <div class="activity-container animate-fadeIn">
        <div class="activity-header">
            <h3><span class="glyphicon glyphicon-list-alt"></span> Activity Log</h3>
            <small style="opacity: 0.9;">Track all system activities and user actions</small>
        </div>
        
        <?php if ($activities->num_rows > 0): ?>
            <?php while($activity = $activities->fetch_assoc()): ?>
            <div class="activity-item animate-fadeInUp">
                <div class="row" style="display: flex; align-items: center;">
                    <div class="col-md-8" style="display: flex; align-items: flex-start;">
                        <div class="timeline-icon">
                            <span class="glyphicon glyphicon-flash"></span>
                        </div>
                        <div class="activity-content">
                            <strong><?php echo htmlspecialchars($activity['action']); ?></strong>
                            <br>
                            <small style="color: var(--gray-500);">
                                <span class="glyphicon glyphicon-user"></span>
                                <?php echo $activity['username'] ? htmlspecialchars($activity['username']) . ' (' . htmlspecialchars($activity['email']) . ')' : 'Unknown User'; ?>
                            </small>
                            <?php if ($activity['details']): ?>
                                <br>
                                <small style="color: var(--gray-500);"><?php echo htmlspecialchars($activity['details']); ?></small>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-4 text-right">
                        <small style="color: var(--gray-500);">
                            <span class="glyphicon glyphicon-calendar"></span>
                            <?php echo date('M d, Y H:i:s', strtotime($activity['created_at'])); ?>
                        </small>
                        <br>
                        <small style="color: var(--gray-500);">
                            <span class="glyphicon glyphicon-globe"></span>
                            <?php echo $activity['ip_address'] ? $activity['ip_address'] : 'N/A'; ?>
                        </small>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <nav style="margin-top: 32px; text-align: center;">
                <ul class="pagination" style="margin: 0;">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="<?php echo $i == $page ? 'active' : ''; ?>">
                            <a href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
            <?php endif; ?>
        <?php else: ?>
            <div class="alert alert-info text-center" style="margin-top: 32px; border-radius: var(--radius-lg); border: none; padding: 48px;">
                <span class="glyphicon glyphicon-info-sign" style="font-size: 56px; display: block; margin-bottom: 20px; color: var(--primary);"></span>
                <h4 style="color: var(--gray-800); font-weight: 700;">No Activity Logs Yet</h4>
                <p style="color: var(--gray-500);">Activity logs will appear here as users interact with the system.</p>
            </div>
        <?php endif; ?>
        
        <div class="text-center" style="margin-top: 32px;">
            <a href="admin_dashboard.php" class="btn btn-secondary btn-lg btn-rounded">
                <span class="glyphicon glyphicon-arrow-left"></span> Back to Dashboard
            </a>
        </div>
    </div>
</div>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
</body>
</html>
