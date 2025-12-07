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

// جمع الإحصائيات
$stats = [];

// إحصائيات المستخدمين
$result = $conn->query("SELECT COUNT(*) as total FROM users");
$stats['total_users'] = $result->fetch_assoc()['total'];

$result = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'admin'");
$stats['total_admins'] = $result->fetch_assoc()['total'];

$result = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'user'");
$stats['total_regular_users'] = $result->fetch_assoc()['total'];

$result = $conn->query("SELECT COUNT(*) as total FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
$stats['new_users_week'] = $result->fetch_assoc()['total'];

// إحصائيات الفرق
$result = $conn->query("SELECT COUNT(*) as total FROM teams");
$stats['total_teams'] = $result->fetch_assoc()['total'];

// إحصائيات المهام
$result = $conn->query("SELECT COUNT(*) as total FROM tasks");
$stats['total_tasks'] = $result->fetch_assoc()['total'];

$result = $conn->query("SELECT COUNT(*) as total FROM tasks WHERE status = 'pending'");
$stats['pending_tasks'] = $result->fetch_assoc()['total'];

$result = $conn->query("SELECT COUNT(*) as total FROM tasks WHERE status = 'submitted'");
$stats['submitted_tasks'] = $result->fetch_assoc()['total'];

$result = $conn->query("SELECT COUNT(*) as total FROM tasks WHERE status = 'approved'");
$stats['approved_tasks'] = $result->fetch_assoc()['total'];

$result = $conn->query("SELECT COUNT(*) as total FROM tasks WHERE status = 'rejected'");
$stats['rejected_tasks'] = $result->fetch_assoc()['total'];

// إحصائيات التسليمات
$result = $conn->query("SELECT COUNT(*) as total FROM submissions");
$stats['total_submissions'] = $result->fetch_assoc()['total'];

$result = $conn->query("SELECT COUNT(*) as total FROM submissions WHERE submitted_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
$stats['submissions_week'] = $result->fetch_assoc()['total'];

// معلومات السيرفر
$stats['server_software'] = $_SERVER['SERVER_SOFTWARE'];
$stats['php_version'] = phpversion();
$stats['mysql_version'] = $conn->server_info;
$stats['server_time'] = date('Y-m-d H:i:s');

// أحدث المستخدمين
$recent_users = $conn->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 5");

// أحدث الفرق
$recent_teams = $conn->query("SELECT t.*, u.username as leader_name FROM teams t JOIN users u ON t.leader_id = u.id ORDER BY t.created_at DESC LIMIT 5");

// أحدث المهام
$recent_tasks = $conn->query("SELECT t.*, tm.name as team_name FROM tasks t JOIN teams tm ON t.team_id = tm.id ORDER BY t.created_at DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - TaskHive</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/components.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/pages/admin.css">
</head>
<body class="admin-body">
<?php render_unified_navbar(th_nav_template('admin', [
    'active' => 'dashboard',
])); ?>

<div class="container admin-main-container">
    <!-- إحصائيات رئيسية -->
    <div class="row stagger-animation">
        <div class="col-md-3 col-sm-6">
            <div class="admin-stat-card">
                <span class="glyphicon glyphicon-user stat-icon-bg"></span>
                <div class="stat-label">Total Users</div>
                <div class="stat-number"><?php echo $stats['total_users']; ?></div>
                <small class="admin-meta"><span class="glyphicon glyphicon-arrow-up icon-success"></span> <?php echo $stats['new_users_week']; ?> new this week</small>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="admin-stat-card success">
                <span class="glyphicon glyphicon-flag stat-icon-bg"></span>
                <div class="stat-label">Total Teams</div>
                <div class="stat-number"><?php echo $stats['total_teams']; ?></div>
                <small class="admin-meta">Active teams</small>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="admin-stat-card warning">
                <span class="glyphicon glyphicon-tasks stat-icon-bg"></span>
                <div class="stat-label">Total Tasks</div>
                <div class="stat-number"><?php echo $stats['total_tasks']; ?></div>
                <small class="admin-meta"><?php echo $stats['pending_tasks']; ?> pending</small>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="admin-stat-card info">
                <span class="glyphicon glyphicon-paperclip stat-icon-bg"></span>
                <div class="stat-label">Submissions</div>
                <div class="stat-number"><?php echo $stats['total_submissions']; ?></div>
                <small class="admin-meta"><?php echo $stats['submissions_week']; ?> this week</small>
            </div>
        </div>
    </div>

    <!-- Task Status Chart -->
    <div class="row">
        <div class="col-md-6">
            <div class="chart-card animate-fadeInUp">
                <h4 class="section-title"><span class="glyphicon glyphicon-stats icon-primary"></span> Task Status Distribution</h4>
                <div class="chart-content">
                    <div class="progress-bar-modern">
                        <?php if ($stats['pending_tasks'] > 0): ?>
                            <div class="progress-bar progress-bar-warning" style="width: <?php echo ($stats['pending_tasks'] / $stats['total_tasks']) * 100; ?>%">
                                Pending (<?php echo $stats['pending_tasks']; ?>)
                            </div>
                        <?php endif; ?>
                        <?php if ($stats['submitted_tasks'] > 0): ?>
                            <div class="progress-bar progress-bar-info" style="width: <?php echo ($stats['submitted_tasks'] / $stats['total_tasks']) * 100; ?>%">
                                Submitted (<?php echo $stats['submitted_tasks']; ?>)
                            </div>
                        <?php endif; ?>
                        <?php if ($stats['approved_tasks'] > 0): ?>
                            <div class="progress-bar progress-bar-success" style="width: <?php echo ($stats['approved_tasks'] / $stats['total_tasks']) * 100; ?>%">
                                Approved (<?php echo $stats['approved_tasks']; ?>)
                            </div>
                        <?php endif; ?>
                        <?php if ($stats['rejected_tasks'] > 0): ?>
                            <div class="progress-bar progress-bar-danger" style="width: <?php echo ($stats['rejected_tasks'] / $stats['total_tasks']) * 100; ?>%">
                                Rejected (<?php echo $stats['rejected_tasks']; ?>)
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="row text-center status-stats">
                        <div class="col-xs-3">
                            <span class="status-number pending"><?php echo $stats['pending_tasks']; ?></span><br>
                            <small class="admin-meta">Pending</small>
                        </div>
                        <div class="col-xs-3">
                            <span class="status-number submitted"><?php echo $stats['submitted_tasks']; ?></span><br>
                            <small class="admin-meta">Submitted</small>
                        </div>
                        <div class="col-xs-3">
                            <span class="status-number approved"><?php echo $stats['approved_tasks']; ?></span><br>
                            <small class="admin-meta">Approved</small>
                        </div>
                        <div class="col-xs-3">
                            <span class="status-number rejected"><?php echo $stats['rejected_tasks']; ?></span><br>
                            <small class="admin-meta">Rejected</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="chart-card animate-fadeInUp">
                <h4 class="section-title"><span class="glyphicon glyphicon-info-sign icon-info"></span> Server Information</h4>
                <table class="table server-info-table">
                    <tr>
                        <td><strong>Server Software</strong></td>
                        <td class="admin-meta"><?php echo htmlspecialchars($stats['server_software']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>PHP Version</strong></td>
                        <td class="admin-meta"><?php echo $stats['php_version']; ?></td>
                    </tr>
                    <tr>
                        <td><strong>MySQL Version</strong></td>
                        <td class="admin-meta"><?php echo $stats['mysql_version']; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Server Time</strong></td>
                        <td class="admin-meta"><?php echo $stats['server_time']; ?></td>
                    </tr>
                    <tr>
                        <td style="padding: 12px 0;"><strong style="color: var(--gray-700);">Total Admins</strong></td>
                        <td style="padding: 12px 0;"><span class="badge badge-danger"><?php echo $stats['total_admins']; ?></span></td>
                    </tr>
                    <tr>
                        <td style="padding: 12px 0; border-bottom: none;"><strong style="color: var(--gray-700);">Total Users</strong></td>
                        <td style="padding: 12px 0; border-bottom: none;"><span class="badge badge-info"><?php echo $stats['total_regular_users']; ?></span></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>



    <!-- Recent Activities -->
    <div class="row">
        <div class="col-md-4">
            <div class="chart-card animate-fadeInUp" style="animation-delay: 0.2s;">
                <h4 class="section-title"><span class="glyphicon glyphicon-time" style="color: var(--primary);"></span> Recent Users</h4>
                <?php while($user = $recent_users->fetch_assoc()): ?>
                <div class="recent-item">
                    <span class="badge badge-<?php echo $user['role'] == 'admin' ? 'danger' : 'primary'; ?>" style="float: right;"><?php echo ucfirst(htmlspecialchars($user['role'])); ?></span>
                    <strong style="color: var(--gray-800);"><?php echo htmlspecialchars($user['username']); ?></strong><br>
                    <small style="color: var(--gray-500);"><?php echo htmlspecialchars($user['email']); ?></small><br>
                    <small style="color: var(--gray-400);"><span class="glyphicon glyphicon-calendar"></span> <?php echo date('M d, Y', strtotime($user['created_at'])); ?></small>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
        <div class="col-md-4">
            <div class="chart-card animate-fadeInUp" style="animation-delay: 0.3s;">
                <h4 class="section-title"><span class="glyphicon glyphicon-flag" style="color: var(--success);"></span> Recent Teams</h4>
                <?php while($team = $recent_teams->fetch_assoc()): ?>
                <div class="recent-item">
                    <strong style="color: var(--gray-800);"><?php echo htmlspecialchars($team['name']); ?></strong><br>
                    <small style="color: var(--gray-500);">Leader: <?php echo htmlspecialchars($team['leader_name']); ?></small><br>
                    <small style="color: var(--gray-400);"><span class="glyphicon glyphicon-calendar"></span> <?php echo date('M d, Y', strtotime($team['created_at'])); ?></small>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
        <div class="col-md-4">
            <div class="chart-card animate-fadeInUp" style="animation-delay: 0.4s;">
                <h4 class="section-title"><span class="glyphicon glyphicon-tasks" style="color: var(--warning);"></span> Recent Tasks</h4>
                <?php while($task = $recent_tasks->fetch_assoc()): 
                    $status_colors = ['pending' => 'warning', 'submitted' => 'info', 'approved' => 'success', 'rejected' => 'danger'];
                ?>
                <div class="recent-item">
                    <span class="badge badge-<?php echo $status_colors[$task['status']]; ?>" style="float: right;"><?php echo ucfirst(htmlspecialchars($task['status'])); ?></span>
                    <strong style="color: var(--gray-800);"><?php echo htmlspecialchars($task['title']); ?></strong><br>
                    <small style="color: var(--gray-500);">Team: <?php echo htmlspecialchars($task['team_name']); ?></small><br>
                    <small style="color: var(--gray-400);"><span class="glyphicon glyphicon-calendar"></span> <?php echo date('M d, Y', strtotime($task['created_at'])); ?></small>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
<script src="../assets/js/main.js"></script>
<script>
    // Auto refresh every 60 seconds
    setTimeout(function() {
        location.reload();
    }, 60000);
</script>
</body>
</html>
