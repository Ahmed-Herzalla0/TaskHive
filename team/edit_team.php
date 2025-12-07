<?php
require_once '../config/includes/db_connect.php';
require_once '../config/includes/functions.php';

// Hide SQL errors from users
error_reporting(0);
mysqli_report(MYSQLI_REPORT_OFF);

if (!isLoggedIn()) {
    redirect('../auth/login.php');
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
    $team_name = sanitize($conn, $_POST['team_name']);
    
    if (empty($team_name)) {
        $error = "Team name is required";
    } else {
        $stmt = $conn->prepare("UPDATE teams SET name = ? WHERE id = ?");
        $stmt->bind_param("si", $team_name, $team_id);
        
        if ($stmt->execute()) {
            $success = "Team updated successfully!";
            log_activity($conn, $_SESSION['user_id'], 'update_team', "Updated team: $team_name");
            $team['name'] = $team_name;
        } else {
            $error = "Error updating team. Please try again.";
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
    <title>Edit Team - TaskHive</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/components.css">
    <link rel="stylesheet" href="../assets/css/pages/team.css">
</head>
<body>
<div class="auth-page">
    <div class="edit-team-container animate-fadeInUp">
        <div class="back-container">
            <a href="<?php echo isAdmin() ? '../admin/admin.php?view=teams' : 'team_details.php?id=' . $team_id; ?>" class="btn btn-secondary btn-rounded">
                <span class="glyphicon glyphicon-arrow-left"></span> Back
            </a>
            <a href="#" class="btn btn-danger btn-rounded" onclick="confirmDelete(); return false;">
                <span class="glyphicon glyphicon-trash"></span> Delete Team
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
        
        <div class="team-card">
            <div class="team-header">
                <div class="logo-icon">✏️</div>
                <h2>Edit Team</h2>
                <p>Update your team information</p>
            </div>
            
            <form method="POST" action="" id="teamForm">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <div class="form-group">
                    <label><span class="glyphicon glyphicon-flag"></span> Team Name</label>
                    <input type="text" name="team_name" class="form-control" 
                           value="<?php echo htmlspecialchars($team['name']); ?>" 
                           maxlength="100" required id="teamNameInput">
                    <div class="char-counter">
                        <span id="nameCount"><?php echo strlen($team['name']); ?></span>/100
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block btn-lg btn-rounded">
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
    $('#teamNameInput').on('input', function() {
        $('#nameCount').text($(this).val().length);
    });
    
    $('#teamForm').on('submit', function(e) {
        var teamName = $('#teamNameInput').val().trim();
        if (teamName.length < 3) {
            e.preventDefault();
            alert('Team name must be at least 3 characters long');
            return false;
        }
    });
});

function confirmDelete() {
    if (confirm('Are you sure you want to delete this team?\n\nThis will also delete:\n• All tasks\n• All submissions\n• All team members\n\nThis action cannot be undone!')) {
        window.location.href = 'delete_team.php?id=<?php echo $team_id; ?>&token=<?php echo get_csrf_token(); ?>';
    }
}
</script>
</body>
</html>
