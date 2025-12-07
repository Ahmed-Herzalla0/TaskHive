<?php
require_once '../config/includes/db_connect.php';
require_once '../config/includes/functions.php';

// Hide SQL errors from users
error_reporting(0);
mysqli_report(MYSQLI_REPORT_OFF);

if (!isLoggedIn()) {
    redirect('../auth/login.php');
}

$user_id = $_SESSION['user_id'];

// Handle task review action
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['task_id'], $_POST['action'])) {
    $task_id = (int)$_POST['task_id'];
    $action = $_POST['action'] === 'approve' ? 'approve' : 'reject';
    
    // Verify user is the leader of the team and fetch team_id in one query
    $verify_stmt = $conn->prepare("SELECT task.team_id 
                                    FROM tasks task 
                                    JOIN teams t ON task.team_id = t.id 
                                    WHERE task.id = ? AND t.leader_id = ?");
    $verify_stmt->bind_param("ii", $task_id, $user_id);
    $verify_stmt->execute();
    $verify_result = $verify_stmt->get_result();
    
    if ($verify_row = $verify_result->fetch_assoc()) {
        $new_status = ($action === 'approve') ? 'approved' : 'rejected';
        $update_stmt = $conn->prepare("UPDATE tasks SET status = ? WHERE id = ?");
        $update_stmt->bind_param("si", $new_status, $task_id);
        $update_stmt->execute();
        $update_stmt->close();
        
        $verify_stmt->close();
        redirect('../team/team_details.php?id=' . $verify_row['team_id']);
    }
    $verify_stmt->close();
}

redirect('../user/dashboard.php');
?>
