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

$submission_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get submission details
$stmt = $conn->prepare("SELECT s.*, t.title as task_title, t.team_id, t.id as task_id
                        FROM submissions s
                        INNER JOIN tasks t ON s.task_id = t.id
                        WHERE s.id = ?");
$stmt->bind_param("i", $submission_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("Submission not found");
}

$submission = $result->fetch_assoc();
$stmt->close();

// Get team leader
$leader_stmt = $conn->prepare("SELECT tm.leader_id FROM teams tm INNER JOIN tasks t ON tm.id = t.team_id WHERE t.id = ?");
$leader_stmt->bind_param("i", $submission['task_id']);
$leader_stmt->execute();
$leader_result = $leader_stmt->get_result();
$leader_row = $leader_result->fetch_assoc();
$leader_id = $leader_row['leader_id'];
$leader_stmt->close();

// Check authorization (submitter, team leader, or admin can delete)
if ($submission['user_id'] != $_SESSION['user_id'] && $leader_id != $_SESSION['user_id'] && !isAdmin()) {
    die("Access denied. Only the submitter, team leader, or admin can delete this submission.");
}

// Get all submissions for this user and task (all versions)
$all_subs_stmt = $conn->prepare("SELECT id, file_path FROM submissions WHERE task_id = ? AND user_id = ?");
$all_subs_stmt->bind_param("ii", $submission['task_id'], $submission['user_id']);
$all_subs_stmt->execute();
$all_subs_result = $all_subs_stmt->get_result();

$deleted_count = 0;
$deleted_files = [];

// Delete all files and submissions for this user's task
while ($sub = $all_subs_result->fetch_assoc()) {
    // Delete file from server
    if (file_exists($sub['file_path'])) {
        unlink($sub['file_path']);
    }
    $deleted_files[] = basename($sub['file_path']);
    
    // Delete from database
    $del_stmt = $conn->prepare("DELETE FROM submissions WHERE id = ?");
    $del_stmt->bind_param("i", $sub['id']);
    if ($del_stmt->execute()) {
        $deleted_count++;
    }
    $del_stmt->close();
}
$all_subs_stmt->close();

if ($deleted_count > 0) {
    log_activity($conn, $_SESSION['user_id'], 'delete_submission', 
                 "Deleted $deleted_count submission(s) for task #{$submission['task_id']}: " . implode(', ', $deleted_files));
    
    // Update task status back to pending if needed
    $check_stmt = $conn->prepare("SELECT COUNT(*) as count FROM submissions WHERE task_id = ?");
    $check_stmt->bind_param("i", $submission['task_id']);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    $row = $check_result->fetch_assoc();
    
    if ($row['count'] == 0) {
        // No more submissions, set task back to pending
        $update_stmt = $conn->prepare("UPDATE tasks SET status = 'pending' WHERE id = ?");
        $update_stmt->bind_param("i", $submission['task_id']);
        $update_stmt->execute();
        $update_stmt->close();
    }
    $check_stmt->close();
    
    $_SESSION['message'] = "All $deleted_count submission(s) deleted successfully!";
    $_SESSION['message_type'] = "success";
} else {
    $_SESSION['message'] = "Error deleting submissions. Please try again.";
    $_SESSION['message_type'] = "danger";
}

// Get redirect parameter
$redirect = isset($_GET['redirect']) ? $_GET['redirect'] : 'team_details';

// Redirect based on parameter
if ($redirect == 'team_tasks') {
    redirect('team_tasks.php?id=' . $submission['team_id']);
} else if ($redirect == 'history') {
    $task_id = isset($_GET['task_id']) ? (int)$_GET['task_id'] : 0;
    $user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
    redirect('submission_history.php?task_id=' . $task_id . '&user_id=' . $user_id);
} else {
    redirect('../team/team_details.php?id=' . $submission['team_id']);
}
?>
