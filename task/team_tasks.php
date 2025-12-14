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
$team_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Verify user is a member of this team
$stmt = $conn->prepare("SELECT * FROM team_members WHERE team_id = ? AND user_id = ?");
$stmt->bind_param("ii", $team_id, $user_id);
$stmt->execute();
$member_check = $stmt->get_result();

if ($member_check->num_rows == 0) {
    redirect('../user/dashboard.php');
}

// Get team info
$stmt = $conn->prepare("SELECT t.*, u.username as leader_name FROM teams t JOIN users u ON t.leader_id = u.id WHERE t.id = ?");
$stmt->bind_param("i", $team_id);
$stmt->execute();
$team = $stmt->get_result()->fetch_assoc();

// Get my tasks (unassigned or assigned to me)
$stmt = $conn->prepare("SELECT * FROM tasks WHERE team_id = ? AND (assigned_to = ? OR assigned_to IS NULL) ORDER BY created_at DESC");
$stmt->bind_param("ii", $team_id, $user_id);
$stmt->execute();
$tasks_result = $stmt->get_result();
$tasks = [];
while ($row = $tasks_result->fetch_assoc()) {
    $tasks[] = $row;
}

// Prefetch latest submission per task for this user (avoids N+1 queries)
$latest_submissions = [];
if ($tasks) {
    $task_ids = array_column($tasks, 'id');
    $placeholders = implode(',', array_fill(0, count($task_ids), '?'));
    $types = str_repeat('i', count($task_ids)) . 'i';
    $params = array_merge($task_ids, [$user_id]);

    $stmt_sub = $conn->prepare("SELECT * FROM submissions WHERE task_id IN ($placeholders) AND user_id = ? ORDER BY task_id, version DESC");
    $stmt_sub->bind_param($types, ...$params);
    $stmt_sub->execute();
    $sub_result = $stmt_sub->get_result();
    while ($row = $sub_result->fetch_assoc()) {
        if (!isset($latest_submissions[$row['task_id']])) {
            $latest_submissions[$row['task_id']] = $row;
        }
    }
    $stmt_sub->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Tasks - <?php echo htmlspecialchars($team['name']); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/components.css">
    <link rel="stylesheet" href="../assets/css/pages/task.css">
    <link rel="stylesheet" href="../assets/css/pages/team.css">
    <script src="../assets/js/main.js"></script>
</head>
<body class="page-body">
<?php render_unified_navbar(th_nav_template('user', [
    'links' => [
        ['label' => 'Dashboard', 'href' => '../user/dashboard.php', 'icon' => 'glyphicon glyphicon-home', 'slug' => 'dashboard'],
        ['label' => 'Team Overview', 'href' => '../team/team_details.php?id=' . $team_id, 'icon' => 'glyphicon glyphicon-eye-open', 'slug' => 'team-overview'],
        ['label' => 'Team Tasks', 'href' => '../task/team_tasks.php?id=' . $team_id, 'icon' => 'glyphicon glyphicon-tasks', 'slug' => 'team-tasks'],
        ['label' => 'Invite Link', 'href' => '../team/manage_invite.php?id=' . $team_id, 'icon' => 'glyphicon glyphicon-link', 'slug' => 'invite'],
    ],
    'active' => 'team-tasks',
])); ?>

<div class="container" style="margin-top: 30px; max-width: 1000px;">
    <!-- Team Header -->
    <div class="team-header animate-fadeInDown">
        <h1>
            <span class="glyphicon glyphicon-flag"></span> <?php echo htmlspecialchars($team['name']); ?>
        </h1>
        <p style="opacity: 0.9; margin: 10px 0 0 0; position: relative;">
            <span class="glyphicon glyphicon-user"></span> Team Leader: <strong><?php echo htmlspecialchars($team['leader_name']); ?></strong>
        </p>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card animate-fadeInUp">
                <div class="card-body" style="padding: 30px;">
                    <h3 style="margin-top: 0; color: var(--gray-900); display: flex; align-items: center; gap: 12px;">
                        <span class="glyphicon glyphicon-tasks" style="color: var(--primary);"></span> 
                        My Tasks <span class="badge badge-primary"><?php echo count($tasks); ?></span>
                    </h3>
                    
                    <?php if (isset($_SESSION['message'])): ?>
                    <div class="alert alert-<?php echo $_SESSION['message_type'] ?? 'info'; ?>" style="margin-top: 20px;">
                        <?php 
                        echo $_SESSION['message']; 
                        unset($_SESSION['message'], $_SESSION['message_type']);
                        ?>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($tasks)): ?>
                        <?php foreach ($tasks as $task): 
                            $status_colors = ['pending' => 'warning', 'submitted' => 'info', 'approved' => 'success', 'rejected' => 'danger'];
                        ?>
                        <div class="task-card <?php echo htmlspecialchars($task['status']); ?>">
                            <div class="row">
                                <div class="col-md-8">
                                    <h4 style="margin-top: 0; color: var(--gray-900);">
                                        <?php echo htmlspecialchars($task['title']); ?>
                                        <span class="label label-<?php echo $status_colors[$task['status']]; ?>" style="font-size: 11px; margin-left: 8px;">
                                            <?php echo strtoupper(htmlspecialchars($task['status'])); ?>
                                        </span>
                                    </h4>
                                    <p style="color: var(--gray-600);"><?php echo nl2br(str_replace(['\r\n', '\n', '\r'], '<br>', htmlspecialchars($task['description']))); ?></p>
                                </div>
                                <div class="col-md-4 text-right">
                                    <small style="color: var(--gray-500);">
                                        <span class="glyphicon glyphicon-calendar"></span>
                                        <?php echo date('M d, Y', strtotime($task['created_at'])); ?>
                                    </small>
                                </div>
                            </div>
                            
                            <!-- Check if already submitted -->
                            <?php if (isset($latest_submissions[$task['id']])):
                                $sub = $latest_submissions[$task['id']];
                            ?>
                            <div class="alert alert-success alert-permanent" style="margin-top: 20px;">
                                <strong><span class="glyphicon glyphicon-ok-circle"></span> Submitted!</strong>
                                <br>You submitted this task on <?php echo date('M d, Y - H:i', strtotime($sub['submitted_at'])); ?>
                                <div style="margin-top: 15px;">
                                    <a href="view_submission.php?id=<?php echo $sub['id']; ?>" class="btn btn-sm btn-primary btn-rounded">
                                        <span class="glyphicon glyphicon-eye-open"></span> VIEW SUBMISSION
                                    </a>
                                    <a href="#" onclick="if(confirm('Are you sure you want to delete this submission?')) { window.location.href='delete_submission.php?id=<?php echo $sub['id']; ?>&redirect=team_tasks&token=<?php echo get_csrf_token(); ?>'; } return false;" class="btn btn-sm btn-danger btn-rounded">
                                        <span class="glyphicon glyphicon-trash"></span> DELETE
                                    </a>
                                </div>
                            </div>
                            <?php else: ?>
                            <div class="upload-section">
                                <h5 style="margin-top: 0; color: var(--gray-700);"><span class="glyphicon glyphicon-upload"></span> Submit Your Work</h5>
                                <form action="upload_submission.php" method="post" enctype="multipart/form-data">
                                    <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="alert alert-info alert-permanent" style="font-size: 13px;">
                                                <strong><span class="glyphicon glyphicon-info-sign"></span> Allowed File Types:</strong>
                                                <ul style="margin: 8px 0 0 0; padding-left: 20px;">
                                                    <li><strong>Archives:</strong> .zip, .rar, .7z, .tar, .gz (up to 50MB)</li>
                                                    <li><strong>Documents:</strong> .pdf, .doc, .docx, .txt (up to 10MB)</li>
                                                    <li><strong>Code Files:</strong> .php, .html, .css, .js, .json, .py, .java (up to 10MB)</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label><span class="glyphicon glyphicon-file"></span> Select File</label>
                                                <input type="file" name="submission_file" class="form-control" required 
                                                       accept=".zip,.rar,.7z,.tar,.gz,.pdf,.doc,.docx,.txt,.php,.html,.css,.js,.json,.xml,.py,.java,.cpp,.c,.h,.cs,.sql,.sh,.bat,.jpg,.jpeg,.png,.gif">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label><span class="glyphicon glyphicon-comment"></span> Comments (Optional)</label>
                                                <textarea name="comments" class="form-control" rows="3" placeholder="Add notes about your submission..."></textarea>
                                            </div>
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-lg btn-block btn-rounded">
                                        <span class="glyphicon glyphicon-upload"></span> Submit Work
                                    </button>
                                </form>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-icon">
                                <span class="glyphicon glyphicon-inbox"></span>
                            </div>
                            <h3>No Tasks Assigned Yet</h3>
                            <p>You don't have any tasks assigned to you in this team.</p>
                            <a href="../user/dashboard.php" class="btn btn-primary btn-lg btn-rounded">
                                <span class="glyphicon glyphicon-arrow-left"></span> Back to Dashboard
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div style="margin: 20px 0; text-align: center;">
        <a href="../user/dashboard.php" class="btn btn-secondary btn-lg btn-rounded">
            <span class="glyphicon glyphicon-arrow-left"></span> Back to Dashboard
        </a>
    </div>
</div>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
</body>
</html>
