<?php
require_once '../config/includes/db_connect.php';
require_once '../config/includes/functions.php';

// Hide SQL errors from users
error_reporting(0);
mysqli_report(MYSQLI_REPORT_OFF);

if (!isLoggedIn()) {
    redirect('../auth/login.php');
}

$task_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get task details
$stmt = $conn->prepare("SELECT t.*, tm.name as team_name, tm.leader_id 
                        FROM tasks t 
                        INNER JOIN teams tm ON t.team_id = tm.id 
                        WHERE t.id = ?");
$stmt->bind_param("i", $task_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    redirect('../user/dashboard.php');
}

$task = $result->fetch_assoc();
$stmt->close();

// Check if user is the team leader or admin
if ($task['leader_id'] != $_SESSION['user_id'] && !isAdmin()) {
    redirect('../user/dashboard.php');
}

$error = '';
$success = '';

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Invalid request. Please try again.";
    } else {
    $task_name = sanitize($conn, $_POST['task_name']);
    $description = sanitize($conn, $_POST['description']);
    
    if (empty($task_name)) {
        $error = "Task name is required";
    } else {
        $stmt = $conn->prepare("UPDATE tasks SET title = ?, description = ? WHERE id = ?");
        $stmt->bind_param("ssi", $task_name, $description, $task_id);
        
        if ($stmt->execute()) {
            $success = "Task updated successfully!";
            log_activity($conn, $_SESSION['user_id'], 'update_task', "Updated task: $task_name");
            $task['title'] = $task_name;
            $task['description'] = $description;
        } else {
            $error = "Error updating task. Please try again.";
        }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Task - TaskHive</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/components.css">
    <link rel="stylesheet" href="../assets/css/pages/task.css">
</head>
<body>
<div class="auth-page">
    <div class="edit-task-container animate-fadeInUp">
        <div class="back-container">
            <a href="<?php echo isAdmin() ? '../admin/admin.php?view=tasks' : '../team/team_details.php?id=' . $task['team_id']; ?>" class="btn btn-secondary btn-rounded">
                <span class="glyphicon glyphicon-arrow-left"></span> Back
            </a>
            <a href="#" class="btn btn-danger btn-rounded" onclick="confirmDelete(); return false;">
                <span class="glyphicon glyphicon-trash"></span> Delete Task
            </a>
        </div>
        
        <?php if($error): ?>
            <div class="alert alert-danger">
                <span class="glyphicon glyphicon-exclamation-sign"></span> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if($success): ?>
            <div class="alert alert-success">
                <span class="glyphicon glyphicon-ok-circle"></span> <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        
        <div class="task-card" id="width-450">
            <div class="task-header">
                <div class="logo-icon">📝</div>
                <h2>Edit Task</h2>
                <p>Update task information</p>
                <span class="task-team-badge"><span class="glyphicon glyphicon-flag"></span> <?php echo htmlspecialchars($task['team_name']); ?></span>
            </div>
            
            <form method="POST" action="" id="taskForm">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <div class="form-group">
                    <label><span class="glyphicon glyphicon-tag"></span> Task Name</label>
                    <input type="text" name="task_name" class="form-control" 
                           value="<?php echo htmlspecialchars($task['title']); ?>" 
                           maxlength="200" required id="taskNameInput">
                    <div class="char-counter">
                        <span id="nameCount"><?php echo strlen($task['title']); ?></span>/200
                    </div>
                </div>
                
                <div class="form-group">
                    <label><span class="glyphicon glyphicon-align-left"></span> Description</label>
                    <textarea name="description" class="form-control" 
                              maxlength="1000" id="descriptionInput" rows="5"><?php echo htmlspecialchars($task['description']); ?></textarea>
                    <div class="char-counter">
                        <span id="descCount"><?php echo strlen($task['description']); ?></span>/1000
                    </div>
                </div>
                
                <button type="submit" class="btn btn-success btn-block btn-lg btn-rounded">
                    <span class="glyphicon glyphicon-floppy-disk"></span> Save Changes
                </button>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
<script>
$(document).ready(function() {
    $('#taskNameInput').on('input', function() {
        $('#nameCount').text($(this).val().length);
    });
    
    $('#descriptionInput').on('input', function() {
        $('#descCount').text($(this).val().length);
    });
    
    $('#taskForm').on('submit', function(e) {
        var taskName = $('#taskNameInput').val().trim();
        if (taskName.length < 3) {
            e.preventDefault();
            alert('Task name must be at least 3 characters long');
            return false;
        }
    });
});

function confirmDelete() {
    if (confirm('Are you sure you want to delete this task?\n\nThis will also delete all submissions for this task.\n\nThis action cannot be undone!')) {
        window.location.href = 'delete_task.php?id=<?php echo $task_id; ?>&token=<?php echo get_csrf_token(); ?>';
    }
}
</script>
</body>
</html>
