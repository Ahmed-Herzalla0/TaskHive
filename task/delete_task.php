<?php
require_once '../config/includes/db_connect.php';
require_once '../config/includes/functions.php';

// Hide SQL errors from users
error_reporting(0);
mysqli_report(MYSQLI_REPORT_OFF);

if (!isLoggedIn()) {
    redirect('../auth/login.php');
}

// Verify CSRF token
if (!isset($_GET['token']) || !isset($_SESSION['csrf_token']) || $_GET['token'] !== $_SESSION['csrf_token']) {
    $_SESSION['message'] = "Invalid request. Please try again.";
    $_SESSION['message_type'] = "danger";
    redirect('../user/dashboard.php');
}

$task_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get task details
$stmt = $conn->prepare("SELECT t.*, tm.name as team_name, tm.leader_id, tm.id as team_id 
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

// Delete all submissions for this task
$stmt = $conn->prepare("DELETE FROM submissions WHERE task_id = ?");
$stmt->bind_param("i", $task_id);
$stmt->execute();
$stmt->close();

// Delete the task
$task_name = $task['title'];
$team_id = $task['team_id'];
$stmt = $conn->prepare("DELETE FROM tasks WHERE id = ?");
$stmt->bind_param("i", $task_id);

if ($stmt->execute()) {
    log_activity($conn, $_SESSION['user_id'], 'delete_task', "Deleted task: $task_name");
    $_SESSION['message'] = "Task deleted successfully!";
    $_SESSION['message_type'] = "success";
} else {
    $_SESSION['message'] = "Error deleting task. Please try again.";
    $_SESSION['message_type'] = "danger";
}

$stmt->close();
if (isAdmin()) {
    redirect('../admin/admin.php?view=tasks');
} else {
    redirect('../team/team_details.php?id=' . $team_id);
}
?>
