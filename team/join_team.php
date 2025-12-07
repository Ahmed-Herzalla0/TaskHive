<?php
require_once '../config/includes/db_connect.php';
require_once '../config/includes/functions.php';

// Hide SQL errors from users
error_reporting(0);
mysqli_report(MYSQLI_REPORT_OFF);

if (!isLoggedIn()) {
    redirect('../auth/login.php');
}

$message = '';
$message_type = 'info';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['invite_link'])) {
    $invite_link = trim($_POST['invite_link']);
    
    // Extract token from URL
    if (preg_match('/[?&]token=([^&]+)/', $invite_link, $matches)) {
        $token = $matches[1];
    } else {
            $token = $invite_link; // In case user pastes only the token
        }
        
        // Sanitize token
        $token = preg_replace('/[^a-f0-9]/i', '', $token);    $user_id = $_SESSION['user_id'];

    // Check if token is valid and not expired
    $stmt = $conn->prepare("SELECT * FROM teams WHERE invite_token = ? AND (token_expiry IS NULL OR token_expiry > NOW())");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $team = $result->fetch_assoc();
        $team_id = $team['id'];

        // Check if already a member
        $check_stmt = $conn->prepare("SELECT * FROM team_members WHERE team_id = ? AND user_id = ?");
        $check_stmt->bind_param("ii", $team_id, $user_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows == 0) {
            $insert_stmt = $conn->prepare("INSERT INTO team_members (team_id, user_id) VALUES (?, ?)");
            $insert_stmt->bind_param("ii", $team_id, $user_id);
            $insert_stmt->execute();
            $insert_stmt->close();
            
            log_activity($conn, $user_id, 'join_team', "Joined team: " . $team['name']);
            
            $message = "🎉 Success! You have joined the team <strong>" . htmlspecialchars($team['name']) . "</strong>";
            $message_type = 'success';
        } else {
            $message = "You are already a member of this team.";
            $message_type = 'info';
        }
        $check_stmt->close();
    } else {
        $message = "⚠️ Invalid or expired invite link.";
        $message_type = 'danger';
    }
    $stmt->close();
}

// Legacy support for old URL-based tokens
if (isset($_GET['token']) && empty($message)) {
    $token = preg_replace('/[^a-f0-9]/i', '', $_GET['token']);
    $user_id = $_SESSION['user_id'];

    $stmt = $conn->prepare("SELECT * FROM teams WHERE invite_token = ? AND (token_expiry IS NULL OR token_expiry > NOW())");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $team = $result->fetch_assoc();
        $team_id = $team['id'];

        $check_stmt = $conn->prepare("SELECT * FROM team_members WHERE team_id = ? AND user_id = ?");
        $check_stmt->bind_param("ii", $team_id, $user_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows == 0) {
            $insert_stmt = $conn->prepare("INSERT INTO team_members (team_id, user_id) VALUES (?, ?)");
            $insert_stmt->bind_param("ii", $team_id, $user_id);
            $insert_stmt->execute();
            $insert_stmt->close();
            
            $message = "🎉 Success! You have joined the team <strong>" . htmlspecialchars($team['name']) . "</strong>";
            $message_type = 'success';
        } else {
            $message = "You are already a member of this team.";
            $message_type = 'info';
        }
        $check_stmt->close();
    } else {
        $message = "⚠️ Invalid or expired invite link.";
        $message_type = 'danger';
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Join Team - TaskHive</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/components.css">
    <link rel="stylesheet" href="../assets/css/pages/auth.css">
</head>
<body>
<div class="auth-page">
    <div class="auth-container animate-fadeInUp">
        <div class="auth-logo">
            <div class="logo-icon">🔗</div>
            <h2>Join a Team</h2>
            <p>Paste the invite link you received</p>
        </div>
        
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
            <a href="../user/dashboard.php" class="btn btn-primary btn-block btn-lg btn-rounded">
                <span class="glyphicon glyphicon-home"></span> Go to Dashboard
            </a>
        <?php else: ?>
            <form method="POST" action="">
                <div class="form-group">
                    <label><span class="glyphicon glyphicon-link"></span> Team Invite Link</label>
                    <div class="input-group">
                        <span class="input-group-addon"><span class="glyphicon glyphicon-link"></span></span>
                        <input type="text" name="invite_link" class="form-control" 
                               placeholder="http://localhost/TaskHive/join_team.php?token=..." 
                               required autofocus>
                    </div>
                    <small class="text-muted help-text">Paste the full link or just the token</small>
                </div>
                <button type="submit" class="btn btn-success btn-block btn-lg btn-rounded">
                    <span class="glyphicon glyphicon-log-in"></span> Join Team
                </button>
            </form>
        <?php endif; ?>
        
        <div class="auth-footer">
            <a href="../user/dashboard.php"><span class="glyphicon glyphicon-arrow-left"></span> Back to Dashboard</a>
        </div>
    </div>
</div>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
</body>
</html>

