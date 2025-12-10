<?php
// إخفاء أخطاء SQL عن المستخدم
error_reporting(0);
ini_set('display_errors', 0);
mysqli_report(MYSQLI_REPORT_OFF);

require_once '../config/includes/db_connect.php';
require_once '../config/includes/functions.php';
require_once '../config/includes/navbar.php';

if (!isLoggedIn()) {
    redirect('../auth/login.php');
}

$user_id = $_SESSION['user_id'];
$team_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Verify user is the leader of this team (using prepared statement)
$stmt = $conn->prepare("SELECT * FROM teams WHERE id = ? AND leader_id = ?");
$stmt->bind_param("ii", $team_id, $user_id);
$stmt->execute();
$team_result = $stmt->get_result();

if ($team_result->num_rows == 0) {
    redirect('../user/dashboard.php');
}

$team = $team_result->fetch_assoc();
$stmt->close();

// Get team members (using prepared statement)
$stmt = $conn->prepare("SELECT u.* FROM users u JOIN team_members tm ON u.id = tm.user_id WHERE tm.team_id = ?");
$stmt->bind_param("i", $team_id);
$stmt->execute();
$members = $stmt->get_result();
$stmt->close();

// Get team tasks (using prepared statement)
$stmt = $conn->prepare("SELECT * FROM tasks WHERE team_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $team_id);
$stmt->execute();
$tasks = $stmt->get_result();
$stmt->close();

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($team['name']); ?> - Team Details</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/components.css">
    <link rel="stylesheet" href="../assets/css/pages/team.css">
    <link rel="stylesheet" href="../assets/css/pages/task.css">
</head>
<body class="page-body">
<?php render_unified_navbar(th_nav_template('user', [
    'links' => [
        ['label' => 'Dashboard', 'href' => '../user/dashboard.php', 'icon' => 'glyphicon glyphicon-home', 'slug' => 'dashboard'],
        ['label' => 'Team Overview', 'href' => 'team_details.php?id=' . $team_id, 'icon' => 'glyphicon glyphicon-eye-open', 'slug' => 'team-overview'],
        ['label' => 'Team Tasks', 'href' => '../task/team_tasks.php?id=' . $team_id, 'icon' => 'glyphicon glyphicon-tasks', 'slug' => 'team-tasks'],
        ['label' => 'Invite Link', 'href' => '../team/manage_invite.php?id=' . $team_id, 'icon' => 'glyphicon glyphicon-link', 'slug' => 'invite'],
    ],
    'active' => 'team-overview',
])); ?>

<div class="container team-details-container">
    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-<?php echo htmlspecialchars($_SESSION['message_type']); ?> alert-dismissible fade in animate-fadeInDown">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <strong><?php echo $_SESSION['message_type'] == 'success' ? '✓' : '⚠'; ?></strong>
            <?php echo htmlspecialchars($_SESSION['message']); ?>
        </div>
        <?php 
            unset($_SESSION['message']);
            unset($_SESSION['message_type']);
        ?>
    <?php endif; ?>
    
    <!-- Team Header -->
    <div class="team-header animate-fadeInDown">
        <h1>
            <span class="glyphicon glyphicon-flag"></span> <?php echo htmlspecialchars($team['name']); ?>
        </h1>
        <p class="team-description">Team Leader Dashboard</p>
        <img src="../assets/images/programmers-team.png" alt="programmers team">
    </div>

    <div class="row">
        <!-- Left Column -->
        <div class="col-md-4">
            <!-- Invite Link -->
            <div class="detail-card animate-fadeInUp invite-link-box">
                <h4><span class="glyphicon glyphicon-link icon-primary"></span> Invite Link</h4>
                <?php if ($team['invite_token']): ?>
                <div class="input-group">
                    <input type="text" class="form-control" id="inviteLink" value="http://<?php echo $_SERVER['HTTP_HOST']; ?>/TaskHive/join_team.php?token=<?php echo $team['invite_token']; ?>" readonly>
                    <span class="input-group-btn">
                        <button class="btn btn-warning copy-btn" type="button">
                            <span class="glyphicon glyphicon-duplicate"></span>
                        </button>
                    </span>
                </div>
                <?php else: ?>
                <p class="text-muted">No active invite link</p>
                <?php endif; ?>
                <a href="manage_invite.php?id=<?php echo $team_id; ?>" class="btn btn-secondary btn-block manage-invite-btn">
                    <span class="glyphicon glyphicon-cog"></span> Manage Invite Link
                </a>
            </div>

            <!-- Team Members -->
            <div class="detail-card animate-fadeInUp">
                <h4><span class="glyphicon glyphicon-users icon-success"></span> Team Members (<?php echo $members->num_rows; ?>)</h4>
                <?php if ($members->num_rows > 0): ?>
                    <?php while($member = $members->fetch_assoc()): ?>
                    <div class="member-card member-card-extended">
                        <strong><?php echo htmlspecialchars($member['username']); ?></strong>
                        <br><small class="text-muted"><?php echo htmlspecialchars($member['email']); ?></small>
                        <?php if (isset($member['first_name'])): ?>
                            <br><small class="text-muted"><?php echo htmlspecialchars($member['first_name'] . ' ' . $member['last_name']); ?></small>
                        <?php endif; ?>
                        <a href="#" onclick="confirmRemove(<?php echo $member['id']; ?>, '<?php echo htmlspecialchars($member['username'], ENT_QUOTES); ?>'); return false;" 
                           class="btn btn-danger btn-xs member-remove-btn">
                            <span class="glyphicon glyphicon-remove"></span>
                        </a>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-members">
                        <span class="glyphicon glyphicon-user"></span>
                        No members yet. Share the invite link!
                    </div>
                <?php endif; ?>
            </div>

            <!-- Quick Actions -->
            <div class="detail-card animate-fadeInUp quick-actions">
                <h4><span class="glyphicon glyphicon-flash icon-warning"></span> Quick Actions</h4>
                <a href="../task/create_task.php?team_id=<?php echo $team_id; ?>" class="btn btn-success btn-block btn-lg btn-rounded">
                    <span class="glyphicon glyphicon-plus"></span> Create New Task
                </a>
                <a href="edit_team.php?id=<?php echo $team_id; ?>" class="btn btn-warning btn-block">
                    <span class="glyphicon glyphicon-edit"></span> Edit Team
                </a>
                <a href="../user/dashboard.php" class="btn btn-secondary btn-block">
                    <span class="glyphicon glyphicon-arrow-left"></span> Back to Dashboard
                </a>
            </div>
        </div>

        <!-- Right Column - Tasks -->
        <div class="col-md-8">
            <div class="detail-card animate-fadeInUp">
                <h4 class="task-section-title">
                    <span class="glyphicon glyphicon-tasks icon-primary"></span> Team Tasks (<?php echo $tasks->num_rows; ?>)
                </h4>
                
                <?php if ($tasks->num_rows > 0): ?>
                    <?php while($task = $tasks->fetch_assoc()): 
                        $status_colors = ['pending' => 'warning', 'submitted' => 'info', 'approved' => 'success', 'rejected' => 'danger'];
                    ?>
                    <div class="task-item <?php echo $task['status']; ?>">
                        <div class="row">
                            <div class="col-md-8">
                                <h4 class="task-title">
                                    <?php echo htmlspecialchars($task['title']); ?>
                                    <span class="label label-<?php echo $status_colors[$task['status']]; ?> label-status">
                                        <?php echo strtoupper($task['status']); ?>
                                    </span>
                                </h4>
                                <p class="task-description"><?php echo htmlspecialchars($task['description']); ?></p>
                                <?php if ($task['start_date'] || $task['end_date']): ?>
                                <div class="date-box">
                                    <?php if ($task['start_date']): ?>
                                        <span class="glyphicon glyphicon-play-circle icon-success"></span>
                                        <strong>Start:</strong> <?php echo date('M d, Y h:i A', strtotime($task['start_date'])); ?>
                                    <?php endif; ?>
                                    <?php if ($task['start_date'] && $task['end_date']): ?>
                                        <span class="date-separator">→</span>
                                    <?php endif; ?>
                                    <?php if ($task['end_date']): ?>
                                        <span class="glyphicon glyphicon-flag icon-danger"></span>
                                        <strong>End:</strong> <?php echo date('M d, Y h:i A', strtotime($task['end_date'])); ?>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-4 text-right">
                                <small class="task-date">
                                    <span class="glyphicon glyphicon-calendar"></span>
                                    <?php echo date('M d, Y', strtotime($task['created_at'])); ?>
                                </small>
                            </div>
                        </div>
                        
                        <!-- Submissions -->
                        <?php
                        $stmt_subs = $conn->prepare("SELECT s.*, u.username FROM submissions s JOIN users u ON s.user_id = u.id WHERE s.task_id = ? AND s.is_latest = TRUE");
                        $stmt_subs->bind_param("i", $task['id']);
                        $stmt_subs->execute();
                        $subs = $stmt_subs->get_result();
                        if ($subs->num_rows > 0):
                        ?>
                        <hr class="separator">
                        <h5 class="submissions-title"><span class="glyphicon glyphicon-paperclip"></span> Submissions (<?php echo $subs->num_rows; ?>):</h5>
                        <?php while($sub = $subs->fetch_assoc()): 
                            $file_name = basename($sub['file_path']);
                            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                            $code_extensions = array('php', 'html', 'htm', 'css', 'scss', 'sass', 'less', 'js', 'jsx', 'ts', 'tsx', 'json', 'xml', 'yaml', 'yml', 'py', 'java', 'jar', 'cpp', 'c', 'h', 'hpp', 'cs', 'sql', 'txt', 'md', 'markdown', 'sh', 'bash', 'bat', 'ps1', 'cmd', 'rb', 'go', 'rs', 'swift', 'kt', 'kts', 'scala', 'r', 'lua', 'pl', 'pm', 'vue', 'svelte', 'asp', 'aspx', 'jsp', 'ini', 'conf', 'cfg', 'env', 'htaccess', 'gitignore', 'dockerfile', 'makefile', 'gradle', 'properties', 'log', 'csv');
                            $can_view = in_array($file_ext, $code_extensions);
                        ?>
                        <div class="submission-box">
                            <div class="row">
                                <div class="col-xs-12">
                                    <span class="glyphicon glyphicon-file icon-primary"></span>
                                    <strong class="submission-user"><?php echo htmlspecialchars($sub['username']); ?></strong> submitted
                                    <span class="badge badge-file">.<?php echo strtoupper($file_ext); ?></span>
                                    <span class="badge badge-version">v<?php echo $sub['version']; ?></span>
                                    <?php if ($sub['is_latest']): ?>
                                        <span class="badge badge-latest">Latest</span>
                                    <?php endif; ?>
                                    <br>
                                    <small class="submission-meta">
                                        <span class="glyphicon glyphicon-time"></span> <?php echo date('M d, Y - H:i', strtotime($sub['submitted_at'])); ?>
                                    </small>
                                    <?php if ($sub['comments']): ?>
                                        <br><small class="submission-comment"><em>"<?php echo htmlspecialchars($sub['comments']); ?>"</em></small>
                                    <?php endif; ?>
                                    <div class="submission-actions">
                                        <?php if ($can_view): ?>
                                            <a href="../task/view_submission.php?id=<?php echo $sub['id']; ?>" class="btn btn-xs btn-success" title="View Code">
                                                <span class="glyphicon glyphicon-eye-open"></span> View
                                            </a>
                                        <?php endif; ?>
                                        <a href="../task/download_file.php?id=<?php echo $sub['id']; ?>" class="btn btn-xs btn-info" title="Download File">
                                            <span class="glyphicon glyphicon-download"></span> Download
                                        </a>
                                        <a href="../task/submission_history.php?task_id=<?php echo $task['id']; ?>&user_id=<?php echo $sub['user_id']; ?>" class="btn btn-xs btn-primary" title="View History">
                                            <span class="glyphicon glyphicon-time"></span> History
                                        </a>
                                        <?php if ($sub['user_id'] == $_SESSION['user_id'] || $team['leader_id'] == $_SESSION['user_id'] || isAdmin()): ?>
                                            <a href="#" class="btn btn-xs btn-danger" onclick="confirmDeleteSubmission(<?php echo $sub['id']; ?>); return false;" title="Delete Submission">
                                                <span class="glyphicon glyphicon-trash"></span> Delete
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                        
                        <!-- Review Buttons -->
                        <?php if ($task['status'] == 'submitted'): ?>
                        <div class="review-buttons">
                            <form method="POST" action="../task/review_task.php">
                                <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                <input type="hidden" name="action" value="approve">
                                <button type="submit" class="btn btn-success btn-rounded">
                                    <span class="glyphicon glyphicon-ok"></span> Approve
                                </button>
                            </form>
                            <form method="POST" action="../task/review_task.php">
                                <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                <input type="hidden" name="action" value="reject">
                                <button type="submit" class="btn btn-danger btn-rounded">
                                    <span class="glyphicon glyphicon-remove"></span> Reject
                                </button>
                            </form>
                        </div>
                        <?php endif; ?>
                        <?php else: ?>
                        <hr class="separator">
                        <p class="no-submissions">
                            <span class="glyphicon glyphicon-info-sign"></span> No submissions yet
                        </p>
                        <?php endif; ?>
                        
                        <!-- Task Action Buttons -->
                        <div class="task-actions-footer">
                            <a href="../task/edit_task.php?id=<?php echo $task['id']; ?>" class="btn btn-sm btn-warning">
                                <span class="glyphicon glyphicon-edit"></span> Edit Task
                            </a>
                            <a href="#" onclick="confirmDeleteTask(<?php echo $task['id']; ?>); return false;" class="btn btn-sm btn-danger">
                                <span class="glyphicon glyphicon-trash"></span> Delete
                            </a>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon">
                            <span class="glyphicon glyphicon-tasks"></span>
                        </div>
                        <h3>No tasks yet</h3>
                        <p>Create your first task to get started!</p>
                        <a href="../task/create_task.php?team_id=<?php echo $team_id; ?>" class="btn btn-success btn-lg btn-rounded">
                            <span class="glyphicon glyphicon-plus"></span> Create Task
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
<script src="../assets/js/main.js"></script>
<script>
$('.copy-btn').on('click', function() {
    var $input = $('#inviteLink');
    $input.select();
    document.execCommand('copy');
    
    var $btn = $(this);
    var originalHtml = $btn.html();
    $btn.html('<span class="glyphicon glyphicon-ok"></span>');
    $btn.removeClass('btn-warning').addClass('btn-success');
    
    setTimeout(function() {
        $btn.html(originalHtml);
        $btn.removeClass('btn-success').addClass('btn-warning');
    }, 2000);
});

function confirmDeleteTask(taskId) {
    if (confirm('Are you sure you want to delete this task?\n\nThis will also delete all submissions for this task.\n\nThis action cannot be undone!')) {
        window.location.href = '../task/delete_task.php?id=' + taskId + '&token=<?php echo get_csrf_token(); ?>';
    }
}

function confirmRemove(userId, username) { // kick member
    if (confirm('Are you sure you want to remove ' + username + ' from this team?')) {
        window.location.href = 'remove_member.php?team_id=<?php echo $team_id; ?>&user_id=' + userId + '&token=<?php echo get_csrf_token(); ?>';
    }
}

function confirmDeleteSubmission(submissionId) {
    if (confirm('Are you sure you want to delete this submission?\n\nThis action cannot be undone!\n\nThe file will be permanently deleted.')) {
        window.location.href = '../task/delete_submission.php?id=' + submissionId + '&token=<?php echo get_csrf_token(); ?>';
    }
}
</script>
</body>
</html>
