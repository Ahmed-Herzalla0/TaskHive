<?php
require_once '../config/includes/db_connect.php';
require_once '../config/includes/functions.php';

// Hide SQL errors from users
error_reporting(0);
mysqli_report(MYSQLI_REPORT_OFF);

if (!isLoggedIn()) {
    redirect('../auth/login.php');
}

$task_id = isset($_GET['task_id']) ? (int)$_GET['task_id'] : 0;
$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

// Get task info and verify access
$task_stmt = $conn->prepare("SELECT t.*, tm.leader_id, tm.name as team_name
                              FROM tasks t
                              INNER JOIN teams tm ON t.team_id = tm.id
                              WHERE t.id = ?");
$task_stmt->bind_param("i", $task_id);
$task_stmt->execute();
$task_result = $task_stmt->get_result();

if ($task_result->num_rows == 0) {
    die("Task not found");
}

$task = $task_result->fetch_assoc();
$task_stmt->close();

// Check authorization (team leader, user, or admin)
if ($task['leader_id'] != $_SESSION['user_id'] && 
    $user_id != $_SESSION['user_id'] && 
    !isAdmin()) {
    die("Access denied");
}

// Get all versions
$versions_stmt = $conn->prepare("SELECT s.*, u.username 
                                 FROM submissions s
                                 INNER JOIN users u ON s.user_id = u.id
                                 WHERE s.task_id = ? AND s.user_id = ?
                                 ORDER BY s.version DESC");
$versions_stmt->bind_param("ii", $task_id, $user_id);
$versions_stmt->execute();
$versions_result = $versions_stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submission History - <?php echo htmlspecialchars($task['title']); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/components.css">
    <link rel="stylesheet" href="../assets/css/pages/task.css">
</head>
<body>
<div class="history-header">
    <div class="container">
        <h2><span class="glyphicon glyphicon-time"></span> Submission History</h2>
        <p><?php echo htmlspecialchars($task['title']); ?> • <?php echo htmlspecialchars($task['team_name']); ?></p>
    </div>
</div>

<div class="container">
    <div class="row">
        <div class="col-md-12">
            <a href="../team/team_details.php?id=<?php echo $task['team_id']; ?>" class="btn btn-secondary btn-rounded">
                <span class="glyphicon glyphicon-arrow-left"></span> Back to Team
            </a>
            <hr style="margin: 28px 0; border-color: var(--gray-200);">
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <h3 style="font-weight: 700; color: var(--gray-800); margin-bottom: 24px;">
                <span class="glyphicon glyphicon-folder-open" style="color: var(--primary);"></span> 
                All Versions <span class="badge" style="background: var(--primary); font-size: 14px;"><?php echo $versions_result->num_rows; ?></span>
            </h3>
            
            <?php if ($versions_result->num_rows > 0): ?>
                <?php while($version = $versions_result->fetch_assoc()): ?>
                    <?php 
                        $file_name = basename($version['file_path']);
                        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                        $file_size = file_exists($version['file_path']) ? filesize($version['file_path']) : 0;
                        $is_latest = $version['is_latest'];
                    ?>
                    <div class="version-card <?php echo $is_latest ? 'latest' : ''; ?> animate-fadeInUp">
                        <div class="row" style="display: flex; align-items: center;">
                            <div class="col-md-8">
                                <div style="margin-bottom: 16px;">
                                    <span class="version-badge <?php echo $is_latest ? 'latest' : 'old'; ?>">
                                        Version <?php echo $version['version']; ?>
                                    </span>
                                    <?php if ($is_latest): ?>
                                        <span class="label label-success" style="border-radius: 20px; padding: 5px 12px; margin-left: 8px;">Current Version</span>
                                    <?php endif; ?>
                                </div>
                                <div class="version-info">
                                    <p>
                                        <span class="glyphicon glyphicon-user" style="color: var(--primary);"></span>
                                        <strong>Submitted by:</strong> 
                                        <?php echo htmlspecialchars($version['username']); ?>
                                    </p>
                                    <p>
                                        <span class="glyphicon glyphicon-time" style="color: var(--primary);"></span>
                                        <strong>Date:</strong> 
                                        <?php echo date('M d, Y - H:i:s', strtotime($version['submitted_at'])); ?>
                                    </p>
                                    <p>
                                        <span class="glyphicon glyphicon-file" style="color: var(--primary);"></span>
                                        <strong>File:</strong> 
                                        <?php echo htmlspecialchars($file_name); ?>
                                        <span class="label label-default" style="border-radius: 8px;">.<?php echo strtoupper($file_ext); ?></span>
                                        <span style="color: var(--gray-500);">(<?php echo round($file_size / 1024, 2); ?> KB)</span>
                                    </p>
                                    <?php if ($version['comments']): ?>
                                        <p>
                                            <span class="glyphicon glyphicon-comment" style="color: var(--primary);"></span>
                                            <strong>Comments:</strong>
                                            <em style="color: var(--gray-600);"><?php echo htmlspecialchars($version['comments']); ?></em>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="version-actions">
                                    <a href="view_submission.php?id=<?php echo $version['id']; ?>" class="btn btn-primary btn-rounded">
                                        <span class="glyphicon glyphicon-eye-open"></span> View
                                    </a>
                                    <a href="download_file.php?id=<?php echo $version['id']; ?>" class="btn btn-success btn-rounded">
                                        <span class="glyphicon glyphicon-download"></span> Download
                                    </a>
                                    <?php if ($version['user_id'] == $_SESSION['user_id'] || $task['leader_id'] == $_SESSION['user_id'] || isAdmin()): ?>
                                        <a href="#" class="btn btn-danger btn-rounded" onclick="if(confirm('Are you sure you want to delete version <?php echo $version['version']; ?>?')) { window.location.href='delete_submission.php?id=<?php echo $version['id']; ?>&redirect=history&task_id=<?php echo $task_id; ?>&user_id=<?php echo $user_id; ?>&token=<?php echo get_csrf_token(); ?>'; } return false;">
                                            <span class="glyphicon glyphicon-trash"></span>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="alert alert-info" style="border-radius: var(--radius-lg); border: none; padding: 24px;">
                    <span class="glyphicon glyphicon-info-sign"></span>
                    No submissions found for this task.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
</body>
</html>
<?php
$versions_stmt->close();
$conn->close();
?>
