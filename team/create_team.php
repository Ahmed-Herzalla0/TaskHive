<?php
require_once '../config/includes/db_connect.php';
require_once '../config/includes/functions.php';

// Hide SQL errors from users
error_reporting(0);
mysqli_report(MYSQLI_REPORT_OFF);

if (!isLoggedIn()) {
    redirect('../auth/login.php');
}

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Invalid request. Please try again.";
    } else {
        $name = sanitize($conn, trim($_POST['name']));
        $leader_id = $_SESSION['user_id'];
        $token = bin2hex(random_bytes(16)); // Generate random token
        $expiry = date('Y-m-d H:i:s', strtotime('+24 hours'));

        $stmt = $conn->prepare("INSERT INTO teams (leader_id, name, invite_token, token_expiry) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $leader_id, $name, $token, $expiry);
        if ($stmt->execute()) {
            redirect('../user/dashboard.php');
        } else {
            $error = "Error creating team. Please try again.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Team - TaskHive</title>
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
            <div class="logo-icon">🐝</div>
            <h2>Create New Team</h2>
            <p>Build something amazing together</p>
        </div>
        
        <?php if(isset($error)): ?>
            <div class="alert alert-danger">
                <span class="glyphicon glyphicon-exclamation-sign"></span> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <div class="form-group">
                <label><span class="glyphicon glyphicon-flag"></span> Team Name</label>
                <div class="input-group">
                    <span class="input-group-addon"><span class="glyphicon glyphicon-flag"></span></span>
                    <input type="text" name="name" class="form-control" placeholder="Enter team name" required autofocus>
                </div>
                <small class="text-muted help-text">Choose a unique name for your development team</small>
            </div>
            
            <button type="submit" class="btn btn-primary btn-block btn-lg btn-rounded">
                <span class="glyphicon glyphicon-plus"></span> Create Team
            </button>
        </form>
        
        <div class="auth-footer">
            <a href="../user/dashboard.php"><span class="glyphicon glyphicon-arrow-left"></span> Back to Dashboard</a>
        </div>
    </div>
</div>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
</body>
</html>
