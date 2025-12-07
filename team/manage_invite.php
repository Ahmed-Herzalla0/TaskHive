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

// Verify user is team leader
$stmt = $conn->prepare("SELECT * FROM teams WHERE id = ? AND leader_id = ?");
$stmt->bind_param("ii", $team_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    redirect('../user/dashboard.php');
}

$team = $result->fetch_assoc();
$stmt->close();

$success = '';
$error = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'];
    
    if ($action == 'regenerate') {
        $new_token = bin2hex(random_bytes(16));
        $expiry = !empty($_POST['expiry_days']) ? date('Y-m-d H:i:s', strtotime('+' . (int)$_POST['expiry_days'] . ' days')) : null;
        
        $stmt = $conn->prepare("UPDATE teams SET invite_token = ?, token_expiry = ? WHERE id = ?");
        $stmt->bind_param("ssi", $new_token, $expiry, $team_id);
        
        if ($stmt->execute()) {
            $success = "Invite link regenerated successfully!";
            $team['invite_token'] = $new_token;
            $team['token_expiry'] = $expiry;
            log_activity($conn, $_SESSION['user_id'], 'regenerate_invite', "Regenerated invite link for team: " . $team['name']);
        } else {
            $error = "Error regenerating invite link. Please try again.";
        }
        $stmt->close();
    } elseif ($action == 'update_expiry') {
        $expiry = !empty($_POST['expiry_days']) ? date('Y-m-d H:i:s', strtotime('+' . (int)$_POST['expiry_days'] . ' days')) : null;
        
        $stmt = $conn->prepare("UPDATE teams SET token_expiry = ? WHERE id = ?");
        $stmt->bind_param("si", $expiry, $team_id);
        
        if ($stmt->execute()) {
            $success = "Expiry date updated successfully!";
            $team['token_expiry'] = $expiry;
        } else {
            $error = "Error updating expiry date. Please try again.";
        }
        $stmt->close();
    } elseif ($action == 'disable') {
        $stmt = $conn->prepare("UPDATE teams SET invite_token = NULL, token_expiry = NULL WHERE id = ?");
        $stmt->bind_param("i", $team_id);
        
        if ($stmt->execute()) {
            $success = "Invite link disabled successfully!";
            $team['invite_token'] = null;
            $team['token_expiry'] = null;
            log_activity($conn, $_SESSION['user_id'], 'disable_invite', "Disabled invite link for team: " . $team['name']);
        } else {
            $error = "Error disabling invite link. Please try again.";
        }
        $stmt->close();
    }
}

$invite_url = $team['invite_token'] ? "http://" . $_SERVER['HTTP_HOST'] . "/TaskHive/join_team.php?token=" . $team['invite_token'] : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Invite Link - TaskHive</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/components.css">
    <link rel="stylesheet" href="../assets/css/pages/team.css">
    <style>
        /* Fix for select dropdown text visibility */
        select, select.form-control {
            color: #333 !important;
            background-color: #fff !important;
            -webkit-text-fill-color: #333 !important;
            opacity: 1 !important;
        }
    </style>
</head>
<body>
<div class="invite-container">
    <div style="margin-bottom: 20px;">
        <a href="team_details.php?id=<?php echo $team_id; ?>" class="btn-back">← Back to Team</a>
    </div>
    
    <?php if($error): ?>
        <div class="alert alert-danger">
            <strong>Error!</strong> <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>
    
    <?php if($success): ?>
        <div class="alert alert-success">
            <strong>Success!</strong> <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>
    
    <div class="invite-card">
        <div class="invite-header">
            <h2>🔗 Manage Invite Link</h2>
            <p style="color: #777; margin: 0;">
                Team: <strong><?php echo htmlspecialchars($team['name']); ?></strong>
                <?php if ($invite_url): ?>
                    <?php if ($team['token_expiry'] && strtotime($team['token_expiry']) < time()): ?>
                        <span class="status-badge status-expired">EXPIRED</span>
                    <?php else: ?>
                        <span class="status-badge status-active">ACTIVE</span>
                    <?php endif; ?>
                <?php else: ?>
                    <span class="status-badge status-disabled">DISABLED</span>
                <?php endif; ?>
            </p>
        </div>
        
        <?php if ($invite_url): ?>
            <h4>Current Invite Link:</h4>
            <div class="invite-link-box">
                <input type="text" id="inviteLink" value="<?php echo $invite_url; ?>" 
                       readonly class="form-control" style="border: none; background: transparent; font-family: monospace;">
            </div>
            <button onclick="copyLink()" class="btn btn-primary btn-block">
                <span class="glyphicon glyphicon-copy"></span> Copy Link
            </button>
            
            <?php if ($team['token_expiry']): ?>
                <div class="alert alert-info" style="margin-top: 20px;">
                    <strong>⏰ Expires:</strong> <?php echo date('F d, Y h:i A', strtotime($team['token_expiry'])); ?>
                    <?php 
                    $time_left = strtotime($team['token_expiry']) - time();
                    if ($time_left > 0) {
                        $days = floor($time_left / 86400);
                        $hours = floor(($time_left % 86400) / 3600);
                        echo "<br><small>(" . $days . " days, " . $hours . " hours remaining)</small>";
                    } else {
                        echo "<br><small style='color: #d9534f;'>This link has expired!</small>";
                    }
                    ?>
                </div>
            <?php else: ?>
                <div class="alert alert-success" style="margin-top: 20px;">
                    <strong>∞ Never Expires</strong> - This link will work indefinitely
                </div>
            <?php endif; ?>
            
            <hr>
            
            <h4>Regenerate Link</h4>
            <form method="POST" action="">
                <input type="hidden" name="action" value="regenerate">
                <div class="form-group">
                    <label>Expiry Duration (Optional)</label>
                    <select name="expiry_days" style="width:100%; height:48px; padding:12px; border:2px solid #e5e7eb; border-radius:12px; font-size:15px; color:#333; background:#fff;">
                        <option value="">Never Expires</option>
                        <option value="1">1 Day</option>
                        <option value="7">7 Days</option>
                        <option value="30">30 Days</option>
                        <option value="90">90 Days</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-warning btn-block">
                    <span class="glyphicon glyphicon-refresh"></span> Generate New Link
                </button>
            </form>
            
            <hr>
            
            <h4>Update Expiry Only</h4>
            <form method="POST" action="">
                <input type="hidden" name="action" value="update_expiry">
                <div class="form-group">
                    <label>New Expiry Duration</label>
                    <select name="expiry_days" style="width:100%; height:48px; padding:12px; border:2px solid #e5e7eb; border-radius:12px; font-size:15px; color:#333; background:#fff;">
                        <option value="">Never Expires</option>
                        <option value="1">1 Day</option>
                        <option value="7">7 Days</option>
                        <option value="30">30 Days</option>
                        <option value="90">90 Days</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-info btn-block">
                    <span class="glyphicon glyphicon-time"></span> Update Expiry
                </button>
            </form>
            
            <hr>
            
            <form method="POST" action="" onsubmit="return confirm('Are you sure? This will disable the current invite link!');">
                <input type="hidden" name="action" value="disable">
                <button type="submit" class="btn btn-danger btn-block">
                    <span class="glyphicon glyphicon-ban-circle"></span> Disable Invite Link
                </button>
            </form>
            
        <?php else: ?>
            <div class="alert alert-warning">
                <strong>⚠️ No Active Invite Link</strong><br>
                Generate a new invite link to allow members to join your team.
            </div>
            
            <form method="POST" action="">
                <input type="hidden" name="action" value="regenerate">
                <div class="form-group">
                    <label>Expiry Duration (Optional)</label>
                    <select name="expiry_days" style="width:100%; height:48px; padding:12px; border:2px solid #e5e7eb; border-radius:12px; font-size:15px; color:#333; background:#fff;">
                        <option value="">Never Expires</option>
                        <option value="1">1 Day</option>
                        <option value="7">7 Days</option>
                        <option value="30">30 Days</option>
                        <option value="90">90 Days</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-success btn-block btn-lg">
                    <span class="glyphicon glyphicon-plus"></span> Generate Invite Link
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script>
function copyLink() {
    var copyText = document.getElementById("inviteLink");
    copyText.select();
    document.execCommand("copy");
    
    var btn = event.target.closest('button');
    var originalHtml = btn.innerHTML;
    btn.innerHTML = '<span class="glyphicon glyphicon-ok"></span> Copied!';
    btn.className = 'btn btn-success btn-block';
    
    setTimeout(function() {
        btn.innerHTML = originalHtml;
        btn.className = 'btn btn-primary btn-block';
    }, 2000);
}
</script>
</body>
</html>
