<?php
require_once '../config/includes/db_connect.php';
require_once '../config/includes/functions.php';
require_once '../config/includes/navbar.php';

// Hide SQL errors from users
error_reporting(0);
mysqli_report(MYSQLI_REPORT_OFF);

if (!isLoggedIn()) {
    redirect('../auth/login.php');
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

if ($user_role == 'admin') {
    redirect('../admin/admin_dashboard.php');
}

// Get all teams where user is a leader
$stmt = $conn->prepare("SELECT * FROM teams WHERE leader_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$my_teams_result = $stmt->get_result();

// Get all teams where user is a member
$stmt = $conn->prepare("SELECT t.*, u.username as leader_name FROM teams t 
                       JOIN team_members tm ON t.id = tm.team_id 
                       JOIN users u ON t.leader_id = u.id
                       WHERE tm.user_id = ? ORDER BY t.created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$member_teams_result = $stmt->get_result();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - TaskHive</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/components.css">
    <link rel="stylesheet" href="../assets/css/pages/user.css">
    <link rel="stylesheet" href="../assets/css/pages/team.css">
</head>
<body class="page-body">
<?php render_unified_navbar(th_nav_template('user', [
        'active' => 'dashboard',
])); ?>

<div class="container main-container">
    <!-- Session Messages -->
    <!-- عرض رسائل النجاح أو الخطأ من صفحات أخرى -->
    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-<?php echo htmlspecialchars($_SESSION['message_type']); ?> alert-dismissible animate-fadeInDown">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <span class="glyphicon glyphicon-<?php echo $_SESSION['message_type'] == 'success' ? 'ok-sign' : 'exclamation-sign'; ?>"></span>
            <?php echo htmlspecialchars($_SESSION['message']); ?>
        </div>
        <?php 
        unset($_SESSION['message']);
        unset($_SESSION['message_type']);
        ?>
    <?php endif; ?>
    
    <!-- Welcome Section -->
    <div class="dashboard-welcome animate-fadeInDown">
        <h2><span class="glyphicon glyphicon-user"></span> Welcome back, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>
        <p>Manage your teams and collaborate effectively with TaskHive</p>
    </div>

    <!-- Quick Actions -->
    <div class="quick-actions animate-fadeInUp">
        <a href="../team/create_team.php" class="btn btn-success btn-lg">
            <span class="glyphicon glyphicon-plus"></span> Create New Team
        </a>
        <a href="../team/join_team.php" class="btn btn-primary btn-lg">
            <span class="glyphicon glyphicon-log-in"></span> Join Existing Team
        </a>
    </div>

    <?php if ($my_teams_result->num_rows > 0): ?>
        <!-- My Teams (Leader) -->
        <div class="card animate-fadeInUp section-card">
            <div class="card-header">
                <h3>
                    <span class="glyphicon glyphicon-flag"></span> My Teams (Leader)
                    <span class="badge badge-primary"><?php echo $my_teams_result->num_rows; ?></span>
                </h3>
            </div>
            <div class="card-body">
                <div class="row stagger-animation">
                    <?php while($team = $my_teams_result->fetch_assoc()): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="team-card">
                            <h4>
                                <div class="team-icon"><span class="glyphicon glyphicon-flag"></span></div>
                                <?php echo htmlspecialchars($team['name']); ?>
                            </h4>
                            
                            <!-- Invite Link -->
                            <div class="invite-link-section">
                                <label class="team-info-label">Invite Link</label>
                                <div class="invite-link-box">
                                    <div class="input-group">
                                        <input type="text" class="form-control" value="http://<?php echo $_SERVER['HTTP_HOST']; ?>/TaskHive/join_team.php?token=<?php echo $team['invite_token']; ?>" readonly>
                                        <span class="input-group-btn">
                                            <button class="btn btn-warning copy-btn" type="button" data-clipboard-text="http://<?php echo $_SERVER['HTTP_HOST']; ?>/TaskHive/join_team.php?token=<?php echo $team['invite_token']; ?>">
                                                <span class="glyphicon glyphicon-duplicate"></span>
                                            </button>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Team Members -->
                            <div class="members-section">
                                <label class="team-info-label">
                                    <span class="glyphicon glyphicon-users"></span> Members
                                </label>
                                <div class="avatar-group">
                                    <?php
                                    $stmt_members = $conn->prepare("SELECT u.username FROM users u JOIN team_members tm ON u.id = tm.user_id WHERE tm.team_id = ? LIMIT 5");
                                    $stmt_members->bind_param("i", $team['id']);
                                    $stmt_members->execute();
                                    $members = $stmt_members->get_result();
                                    if ($members->num_rows > 0) {
                                        while($m = $members->fetch_assoc()) {
                                            echo '<div class="avatar avatar-sm" title="' . htmlspecialchars($m['username']) . '">' . strtoupper(substr($m['username'], 0, 1)) . '</div>';
                                        }
                                    } else {
                                        echo '<span class="no-members-text">No members yet</span>';
                                    }
                                    $stmt_members->close();
                                    ?>
                                </div>
                            </div>
                            
                            <!-- Actions -->
                            <div class="team-actions">
                                <a href="../team/team_details.php?id=<?php echo $team['id']; ?>" class="btn btn-primary btn-sm">
                                    <span class="glyphicon glyphicon-eye-open"></span> View Details
                                </a>
                                <a href="../task/create_task.php?team_id=<?php echo $team['id']; ?>" class="btn btn-success btn-sm">
                                    <span class="glyphicon glyphicon-plus"></span> Create Task
                                </a>
                                <a href="../team/edit_team.php?id=<?php echo $team['id']; ?>" class="btn btn-secondary btn-sm">
                                    <span class="glyphicon glyphicon-edit"></span> Edit Team
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($member_teams_result->num_rows > 0): ?>
        <!-- Teams I'm Member Of -->
        <div class="card animate-fadeInUp section-card">
            <div class="card-header">
                <h3>
                    <span class="glyphicon glyphicon-link"></span> Teams I'm Member Of
                    <span class="badge badge-success"><?php echo $member_teams_result->num_rows; ?></span>
                </h3>
            </div>
            <div class="card-body">
                <div class="row stagger-animation">
                    <?php while($team = $member_teams_result->fetch_assoc()): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="team-card team-card-member">
                            <h4>
                                <div class="team-icon team-icon-member"><span class="glyphicon glyphicon-flag"></span></div>
                                <?php echo htmlspecialchars($team['name']); ?>
                            </h4>
                            <p class="team-leader-info">
                                <span class="glyphicon glyphicon-user"></span> 
                                Leader: <strong><?php echo htmlspecialchars($team['leader_name']); ?></strong>
                            </p>
                            
                            <!-- My Tasks Count -->
                            <?php
                            $stmt_tasks = $conn->prepare("SELECT COUNT(*) as count FROM tasks WHERE team_id = ? AND (assigned_to = ? OR assigned_to IS NULL)");
                            $stmt_tasks->bind_param("ii", $team['id'], $user_id);
                            $stmt_tasks->execute();
                            $task_count = $stmt_tasks->get_result()->fetch_assoc()['count'];
                            $stmt_tasks->close();
                            ?>
                            <div class="team-stats-box">
                                <span class="glyphicon glyphicon-tasks task-icon"></span> 
                                <strong class="stat-number"><?php echo $task_count; ?></strong>
                                <span class="stat-label"> tasks available</span>
                            </div>
                            
                            <a href="../task/team_tasks.php?id=<?php echo $team['id']; ?>" class="btn btn-primary btn-sm btn-block">
                                <span class="glyphicon glyphicon-eye-open"></span> View My Tasks
                            </a>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($my_teams_result->num_rows == 0 && $member_teams_result->num_rows == 0): ?>
        <!-- No Teams View -->
        <div class="card animate-fadeInUp section-card">
            <div class="card-body">
                <div class="empty-state">
                    <div class="empty-icon">
                        <span class="glyphicon glyphicon-flag"></span>
                    </div>
                    <h3>You're Not Part of Any Team Yet</h3>
                    <p>Create your own team to lead projects or join an existing team to collaborate with others!</p>
                    <div class="empty-actions">
                        <a href="../team/create_team.php" class="btn btn-primary btn-lg">
                            <span class="glyphicon glyphicon-plus"></span> Create a Team
                        </a>
                        <a href="../team/join_team.php" class="btn btn-success btn-lg">
                            <span class="glyphicon glyphicon-log-in"></span> Join a Team
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
<script src="../assets/js/main.js"></script>
<script>
// Copy invite link functionality
$('.copy-btn').on('click', function() {
    var text = $(this).data('clipboard-text');
    
    // Create temporary input
    var $temp = $("<input>");
    $("body").append($temp);
    $temp.val(text).select();
    document.execCommand("copy");
    $temp.remove();
    
    // Visual feedback
    var $btn = $(this);
    var originalHtml = $btn.html();
    $btn.html('<span class="glyphicon glyphicon-ok"></span>');
    $btn.removeClass('btn-warning').addClass('btn-success');
    
    setTimeout(function() {
        $btn.html(originalHtml);
        $btn.removeClass('btn-success').addClass('btn-warning');
    }, 2000);
});
</script>
</body>
</html>
