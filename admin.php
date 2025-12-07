<?php
require_once 'config/includes/db_connect.php';
require_once 'config/includes/functions.php';
require_once 'config/includes/navbar.php';

// Hide SQL errors from users
error_reporting(0);
mysqli_report(MYSQLI_REPORT_OFF);

if (!isLoggedIn() || !isAdmin()) {
    redirect('login.php');
}

$view = isset($_GET['view']) ? $_GET['view'] : 'users';

// Verify CSRF token for delete operations
$csrf_valid = isset($_GET['token']) && verify_csrf_token($_GET['token']);

// Handle Delete User
if (isset($_GET['delete']) && $view == 'users' && $csrf_valid) {
    $id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    redirect('admin.php');
}

// Handle Delete Team
if (isset($_GET['delete_team']) && $csrf_valid) {
    $id = (int)$_GET['delete_team'];
    // Delete cascade handled by DB
    $stmt = $conn->prepare("DELETE FROM teams WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    redirect('admin.php?view=teams');
}

// Handle Delete Task
if (isset($_GET['delete_task']) && $csrf_valid) {
    $id = (int)$_GET['delete_task'];
    $stmt = $conn->prepare("DELETE FROM tasks WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    redirect('admin.php?view=tasks');
}

if ($view == 'users') {
    $users = $conn->query("SELECT * FROM users ORDER BY created_at DESC");
} elseif ($view == 'teams') {
    $teams = $conn->query("SELECT t.*, u.username as leader_name FROM teams t JOIN users u ON t.leader_id = u.id ORDER BY t.created_at DESC");
} elseif ($view == 'tasks') {
    $tasks = $conn->query("SELECT t.*, tm.name as team_name, u.username as assigned_name FROM tasks t LEFT JOIN teams tm ON t.team_id = tm.id LEFT JOIN users u ON t.assigned_to = u.id ORDER BY t.created_at DESC");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - TaskHive</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/components.css">
    <link rel="stylesheet" href="assets/css/pages/admin.css">
</head>
<body>
<?php
$adminNav = th_nav_template('admin');
if ($view === 'teams') {
        $adminNav['active'] = 'teams';
} elseif ($view === 'tasks') {
        $adminNav['active'] = 'tasks';
} else {
        $adminNav['active'] = 'users';
}
render_unified_navbar($adminNav);
?>

<div class="container" style="max-width: 1400px; margin-left: auto; margin-right: auto;">
    <div class="user-table-container">
        <div class="user-table-header">
            <div class="row">
                <div class="col-md-6">
                    <h3 style="margin: 0;">
                        <?php if($view == 'users'): ?>
                            <span class="glyphicon glyphicon-users"></span> User Management
                        <?php elseif($view == 'teams'): ?>
                            <span class="glyphicon glyphicon-flag"></span> Team Management
                        <?php else: ?>
                            <span class="glyphicon glyphicon-tasks"></span> Task Management
                        <?php endif; ?>
                    </h3>
                    <small>
                        <?php if($view == 'users'): ?>
                            Manage all system users
                        <?php elseif($view == 'teams'): ?>
                            Manage all teams
                        <?php else: ?>
                            Manage all tasks
                        <?php endif; ?>
                    </small>
                </div>
                <div class="col-md-6 text-right">
                    <?php if($view == 'users'): ?>
                    <a href="admin_user_form.php" class="btn btn-warning btn-lg" style="background: #FFD700; color: #333; border: none; font-weight: 700;">
                        <span class="glyphicon glyphicon-plus"></span> Add New User
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="table-responsive">
            <?php if($view == 'users'): ?>
            <!-- Users Table -->
            <table class="table table-hover" style="margin: 0;">
                <thead style="background: #f8f9fa;">
                    <tr>
                        <th style="padding: 15px;">ID</th>
                        <th>User</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                            <?php while($row = $users->fetch_assoc()): ?>
                            <tr style="border-left: 3px solid <?php echo $row['role'] == 'admin' ? '#d9534f' : '#5bc0de'; ?>;">
                                <td style="padding: 15px; vertical-align: middle;"><strong>#<?php echo $row['id']; ?></strong></td>
                                <td style="vertical-align: middle;">
                                    <?php if(isset($row['profile_pic']) && $row['profile_pic']): ?>
                                        <img src="<?php echo $row['profile_pic']; ?>" alt="pic" class="user-avatar">
                                    <?php else: ?>
                                        <div class="user-placeholder"><?php echo strtoupper(substr($row['username'], 0, 1)); ?></div>
                                    <?php endif; ?>
                                    <strong><?php echo $row['username']; ?></strong>
                                    <?php if(isset($row['first_name'])): ?>
                                        <br><small class="text-muted"><?php echo $row['first_name'] . ' ' . $row['last_name']; ?></small>
                                    <?php endif; ?>
                                </td>
                                <td style="vertical-align: middle;"><?php echo $row['email']; ?></td>
                                <td style="vertical-align: middle;">
                                    <span class="label label-<?php echo $row['role'] == 'admin' ? 'danger' : 'info'; ?>" style="padding: 6px 12px; font-size: 12px;">
                                        <?php echo strtoupper($row['role']); ?>
                                    </span>
                                </td>
                                <td style="vertical-align: middle;">
                                    <small class="text-muted">
                                        <span class="glyphicon glyphicon-calendar"></span>
                                        <?php echo date('M d, Y', strtotime($row['created_at'])); ?>
                                    </small>
                                </td>
                                <td class="table-actions" style="vertical-align: middle;">
                                    <a href="admin_user_form.php?id=<?php echo $row['id']; ?>" class="btn btn-warning btn-sm">
                                        <span class="glyphicon glyphicon-edit"></span> Edit
                                    </a>
                                    <a href="admin.php?delete=<?php echo $row['id']; ?>&token=<?php echo get_csrf_token(); ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this user?')">
                                        <span class="glyphicon glyphicon-trash"></span> Delete
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
            
            <?php elseif($view == 'teams'): ?>
            <!-- Teams Table -->
            <table class="table table-hover" style="margin: 0;">
                <thead style="background: #f8f9fa;">
                    <tr>
                        <th style="padding: 15px;">ID</th>
                        <th>Team Name</th>
                        <th>Leader</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $teams->fetch_assoc()): ?>
                    <tr style="border-left: 3px solid #4A90E2;">
                        <td style="padding: 15px; vertical-align: middle;"><strong>#<?php echo $row['id']; ?></strong></td>
                        <td style="vertical-align: middle;">
                            <strong><?php echo htmlspecialchars($row['name']); ?></strong>
                        </td>
                        <td style="vertical-align: middle;">
                            <span class="glyphicon glyphicon-user"></span> <?php echo $row['leader_name']; ?>
                        </td>
                        <td style="vertical-align: middle;">
                            <small class="text-muted">
                                <span class="glyphicon glyphicon-calendar"></span>
                                <?php echo date('M d, Y', strtotime($row['created_at'])); ?>
                            </small>
                        </td>
                        <td class="table-actions" style="vertical-align: middle;">
                            <a href="edit_team.php?id=<?php echo $row['id']; ?>" class="btn btn-warning btn-sm">
                                <span class="glyphicon glyphicon-edit"></span> Edit
                            </a>
                            <a href="admin.php?delete_team=<?php echo $row['id']; ?>&view=teams&token=<?php echo get_csrf_token(); ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this team? This will also delete all tasks and submissions!')">
                                <span class="glyphicon glyphicon-trash"></span> Delete
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            
            <?php else: ?>
            <!-- Tasks Table -->
            <table class="table table-hover" style="margin: 0;">
                <thead style="background: #f8f9fa;">
                    <tr>
                        <th style="padding: 15px;">ID</th>
                        <th>Task Title</th>
                        <th>Team</th>
                        <th>Assigned To</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $tasks->fetch_assoc()): ?>
                    <tr style="border-left: 3px solid <?php 
                        echo $row['status'] == 'approved' ? '#5cb85c' : 
                            ($row['status'] == 'rejected' ? '#d9534f' : 
                            ($row['status'] == 'submitted' ? '#5bc0de' : '#f0ad4e')); 
                    ?>;">
                        <td style="padding: 15px; vertical-align: middle;"><strong>#<?php echo $row['id']; ?></strong></td>
                        <td style="vertical-align: middle;">
                            <strong><?php echo htmlspecialchars($row['title']); ?></strong>
                            <?php if ($row['start_date'] || $row['end_date']): ?>
                            <br>
                            <small style="background: #e3f2fd; padding: 3px 8px; border-radius: 3px; display: inline-block; margin-top: 5px;">
                                <?php if ($row['start_date']): ?>
                                    <span class="glyphicon glyphicon-play-circle" style="color: #4caf50; font-size: 10px;"></span>
                                    <?php echo date('M d, Y', strtotime($row['start_date'])); ?>
                                <?php endif; ?>
                                <?php if ($row['start_date'] && $row['end_date']): ?>
                                    →
                                <?php endif; ?>
                                <?php if ($row['end_date']): ?>
                                    <span class="glyphicon glyphicon-flag" style="color: #f44336; font-size: 10px;"></span>
                                    <?php echo date('M d, Y', strtotime($row['end_date'])); ?>
                                <?php endif; ?>
                            </small>
                            <?php endif; ?>
                        </td>
                        <td style="vertical-align: middle;">
                            <span class="glyphicon glyphicon-flag"></span> <?php echo $row['team_name']; ?>
                        </td>
                        <td style="vertical-align: middle;">
                            <?php echo $row['assigned_name'] ? $row['assigned_name'] : '<em class="text-muted">Anyone</em>'; ?>
                        </td>
                        <td style="vertical-align: middle;">
                            <span class="label label-<?php 
                                echo $row['status'] == 'approved' ? 'success' : 
                                    ($row['status'] == 'rejected' ? 'danger' : 
                                    ($row['status'] == 'submitted' ? 'info' : 'warning')); 
                            ?>" style="padding: 6px 12px; font-size: 12px;">
                                <?php echo strtoupper($row['status']); ?>
                            </span>
                        </td>
                        <td style="vertical-align: middle;">
                            <small class="text-muted">
                                <span class="glyphicon glyphicon-calendar"></span>
                                <?php echo date('M d, Y', strtotime($row['created_at'])); ?>
                            </small>
                        </td>
                        <td class="table-actions" style="vertical-align: middle;">
                            <a href="edit_task.php?id=<?php echo $row['id']; ?>" class="btn btn-warning btn-sm">
                                <span class="glyphicon glyphicon-edit"></span> Edit
                            </a>
                            <a href="admin.php?delete_task=<?php echo $row['id']; ?>&view=tasks&token=<?php echo get_csrf_token(); ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this task? This will also delete all submissions!')">
                                <span class="glyphicon glyphicon-trash"></span> Delete
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
</div>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
<script src="js/main.js"></script>
</body>
</html>
