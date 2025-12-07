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

$team_id = isset($_GET['team_id']) ? (int)$_GET['team_id'] : 0;
$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

// Verify user is the team leader
$stmt = $conn->prepare("SELECT * FROM teams WHERE id = ? AND leader_id = ?");
$stmt->bind_param("ii", $team_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    $_SESSION['message'] = "Access denied!";
    $_SESSION['message_type'] = "danger";
    redirect('../user/dashboard.php');
}
$stmt->close();

// Get username before removing
$username = null;
$stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result && ($user = $result->fetch_assoc())) {
    $username = $user['username'];
}
$stmt->close();

// Remove member from team
$stmt = $conn->prepare("DELETE FROM team_members WHERE team_id = ? AND user_id = ?");
$stmt->bind_param("ii", $team_id, $user_id);

if ($stmt->execute()) {
    if ($username) {
        log_activity($conn, $_SESSION['user_id'], 'remove_member', "Removed $username from team");
    }
    $_SESSION['message'] = "Member removed successfully!";
    $_SESSION['message_type'] = "success";
} else {
    $_SESSION['message'] = "Error removing member. Please try again.";
    $_SESSION['message_type'] = "danger";
}

$stmt->close();
redirect('team_details.php?id=' . $team_id);
?>
