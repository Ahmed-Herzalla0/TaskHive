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

$team_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get team details
$stmt = $conn->prepare("SELECT * FROM teams WHERE id = ?");
$stmt->bind_param("i", $team_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    redirect('../user/dashboard.php');
}

$team = $result->fetch_assoc();
$stmt->close();

// Check if user is the team leader or admin
if ($team['leader_id'] != $_SESSION['user_id'] && !isAdmin()) {
    redirect('../user/dashboard.php');
}

// Get all submission files for this team's tasks and delete them from server
$files_stmt = $conn->prepare("SELECT s.file_path FROM submissions s 
                              INNER JOIN tasks t ON s.task_id = t.id 
                              WHERE t.team_id = ?");
$files_stmt->bind_param("i", $team_id);
$files_stmt->execute();
$files_result = $files_stmt->get_result();

while ($file_row = $files_result->fetch_assoc()) {
    if (!empty($file_row['file_path']) && file_exists($file_row['file_path'])) {
        unlink($file_row['file_path']);
    }
}
$files_stmt->close();

// Delete all submissions for this team's tasks from database
$stmt = $conn->prepare("DELETE s FROM submissions s 
                        INNER JOIN tasks t ON s.task_id = t.id 
                        WHERE t.team_id = ?");
$stmt->bind_param("i", $team_id);
$stmt->execute();
$stmt->close();

// Delete all tasks
$stmt = $conn->prepare("DELETE FROM tasks WHERE team_id = ?");
$stmt->bind_param("i", $team_id);
$stmt->execute();
$stmt->close();

// Delete all team members
$stmt = $conn->prepare("DELETE FROM team_members WHERE team_id = ?");
$stmt->bind_param("i", $team_id);
$stmt->execute();
$stmt->close();

// Delete the team
$team_name = $team['name'];
$stmt = $conn->prepare("DELETE FROM teams WHERE id = ?");
$stmt->bind_param("i", $team_id);

if ($stmt->execute()) {
    log_activity($conn, $_SESSION['user_id'], 'delete_team', "Deleted team: $team_name");
    $_SESSION['message'] = "Team deleted successfully!";
    $_SESSION['message_type'] = "success";
} else {
    $_SESSION['message'] = "Error deleting team. Please try again.";
    $_SESSION['message_type'] = "danger";
}

$stmt->close();
if (isAdmin()) {
    redirect('../admin/admin.php?view=teams');
} else {
    redirect('../user/dashboard.php');
}
?>
