<?php
require_once '../config/includes/db_connect.php';
require_once '../config/includes/functions.php';

// Hide SQL errors from users
error_reporting(0);
mysqli_report(MYSQLI_REPORT_OFF);

if (!isLoggedIn()) {
    redirect('../auth/login.php');
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

// Check authorization (submitter, team leader, or admin can edit)
if ($submission['user_id'] != $_SESSION['user_id'] && $leader_id != $_SESSION['user_id'] && !isAdmin()) {
    die("Access denied. Only the submitter, team leader, or admin can edit this submission.");
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
        $comments = sanitize($conn, trim($_POST['comments']));
        $replace_file = isset($_FILES["submission_file"]) && $_FILES["submission_file"]["error"] == 0;
        
        $new_file_path = $submission['file_path'];
        
        // Handle file replacement
        if ($replace_file) {
            $target_dir = "../assets/images/uploads/";
            $allowed_types = array(
                'zip', 'rar', '7z', 'tar', 'gz',
                'pdf', 'doc', 'docx', 'txt', 'md', 'markdown', 'csv', 'log',
                'php', 'html', 'htm', 'css', 'scss', 'sass', 'less',
                'js', 'jsx', 'ts', 'tsx', 'json', 'xml', 'yaml', 'yml',
                'py', 'java', 'jar', 'cpp', 'c', 'h', 'hpp', 'cs',
                'sql', 'sh', 'bash', 'bat', 'ps1', 'cmd',
                'rb', 'go', 'rs', 'swift', 'kt', 'kts', 'scala', 'r', 'lua', 'pl', 'pm',
                'vue', 'svelte', 'asp', 'aspx', 'jsp',
                'ini', 'conf', 'cfg', 'env', 'htaccess', 'gitignore', 'dockerfile', 'makefile', 'gradle', 'properties',
                'jpg', 'jpeg', 'png', 'gif'
            );
            
            $file_ext = strtolower(pathinfo($_FILES["submission_file"]["name"], PATHINFO_EXTENSION));
            
            if (!in_array($file_ext, $allowed_types)) {
                $error = "Invalid file type. Please check allowed file types.";
            } else {
                $max_size = in_array($file_ext, array('zip', 'rar', '7z', 'tar', 'gz')) ? 50 * 1024 * 1024 : 10 * 1024 * 1024;
                
                if ($_FILES["submission_file"]["size"] > $max_size) {
                    $error = "File size exceeds limit.";
                } else {
                    // Delete old file
                    if (file_exists($submission['file_path'])) {
                        unlink($submission['file_path']);
                    }
                    
                    // Upload new file
                    $filename = time() . "_" . uniqid() . "_" . basename($_FILES["submission_file"]["name"]);
                    $target_file = $target_dir . $filename;
                    
                    if (move_uploaded_file($_FILES["submission_file"]["tmp_name"], $target_file)) {
                        $new_file_path = $target_file;
                    } else {
                        $error = "Error uploading file.";
                    }
                }
            }
        }
        
        if (!$error) {
            // Check if editor is leader
            $is_leader = ($leader_id == $_SESSION['user_id']);
            $is_owner = ($submission['user_id'] == $_SESSION['user_id']);
            
            $new_version = '';
            
            if ($is_owner) {
                // User editing their own submission - create new major version
                $version_stmt = $conn->prepare("SELECT COALESCE(MAX(CAST(SUBSTRING_INDEX(version, '.', 1) AS UNSIGNED)), 0) + 1 as next_version FROM submissions WHERE task_id = ? AND user_id = ?");
                $version_stmt->bind_param("ii", $submission['task_id'], $_SESSION['user_id']);
                $version_stmt->execute();
                $version_result = $version_stmt->get_result();
                $version_row = $version_result->fetch_assoc();
                $new_version = $version_row['next_version'];
                $version_stmt->close();
            } else if ($is_leader) {
                // Leader editing user's submission - create sub-version
                $current_major = $submission['version'];
                if (strpos($current_major, '.') !== false) {
                    $current_major = explode('.', $current_major)[0];
                }
                
                // Get next sub-version
                $sub_stmt = $conn->prepare("SELECT COALESCE(MAX(CAST(SUBSTRING_INDEX(version, '.', -1) AS UNSIGNED)), 0) + 1 as next_sub 
                                            FROM submissions 
                                            WHERE task_id = ? AND user_id = ? AND version LIKE ?");
                $like_pattern = $current_major . '.%';
                $sub_stmt->bind_param("iis", $submission['task_id'], $submission['user_id'], $like_pattern);
                $sub_stmt->execute();
                $sub_result = $sub_stmt->get_result();
                $sub_row = $sub_result->fetch_assoc();
                $next_sub = $sub_row['next_sub'];
                $sub_stmt->close();
                
                if ($next_sub > 9) {
                    $error = "Maximum sub-versions (9) reached for version $current_major";
                } else {
                    $new_version = $current_major . '.' . $next_sub;
                }
            }
            
            if (!$error && $new_version) {
                // Mark all previous submissions as not latest for this user
                $update_stmt = $conn->prepare("UPDATE submissions SET is_latest = FALSE WHERE task_id = ? AND user_id = ?");
                $update_stmt->bind_param("ii", $submission['task_id'], $submission['user_id']);
                $update_stmt->execute();
                $update_stmt->close();
                
                // Insert new version
                $stmt = $conn->prepare("INSERT INTO submissions (task_id, user_id, file_path, comments, version, is_latest) VALUES (?, ?, ?, ?, ?, TRUE)");
                $stmt->bind_param("iisss", $submission['task_id'], $submission['user_id'], $new_file_path, $comments, $new_version);
                
                if ($stmt->execute()) {
                    // Update task status based on who edited
                    if ($is_leader) {
                        // Leader edit - auto approve
                        $status_stmt = $conn->prepare("UPDATE tasks SET status = 'approved' WHERE id = ?");
                        $status_stmt->bind_param("i", $submission['task_id']);
                        $status_stmt->execute();
                        $status_stmt->close();
                        
                        log_activity($conn, $_SESSION['user_id'], 'edit_submission', "Leader created version $new_version for submission #$submission_id (Auto-approved)");
                        $success = "Submission updated successfully! (Version $new_version created and auto-approved)";
                    } else {
                        // User edit - require approval
                        $status_stmt = $conn->prepare("UPDATE tasks SET status = 'submitted' WHERE id = ?");
                        $status_stmt->bind_param("i", $submission['task_id']);
                        $status_stmt->execute();
                        $status_stmt->close();
                        
                        log_activity($conn, $_SESSION['user_id'], 'edit_submission', "Created version $new_version for submission #$submission_id");
                        $success = "Submission updated successfully! (Version $new_version created - Awaiting approval)";
                    }
                    
                    // Refresh data
                    $submission['file_path'] = $new_file_path;
                    $submission['comments'] = $comments;
                    $submission['version'] = $new_version;
                } else {
                    $error = "Error updating submission. Please try again.";
                }
                $stmt->close();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Submission - <?php echo htmlspecialchars($submission['task_title']); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/components.css">
    <link rel="stylesheet" href="../assets/css/pages/task.css">
</head>
<body class="edit-submission-page">
<div class="edit-container">
    <div style="margin-bottom: 24px;">
        <a href="view_submission.php?id=<?php echo $submission_id; ?>" class="btn btn-secondary btn-rounded">
            <span class="glyphicon glyphicon-arrow-left"></span> Back to Submission
        </a>
    </div>

    <?php if($error): ?>
        <div class="alert alert-danger" style="border-radius: var(--radius-lg); border: none; padding: 16px 20px;">
            <span class="glyphicon glyphicon-exclamation-sign"></span> <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>
    
    <?php if($success): ?>
        <div class="alert alert-success" style="border-radius: var(--radius-lg); border: none; padding: 16px 20px;">
            <span class="glyphicon glyphicon-ok-circle"></span> <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <div class="edit-card animate-fadeInUp">
        <div class="edit-header">
            <h2>✏️ Edit Submission</h2>
            <p style="color: var(--gray-500); margin: 0;">Update your submission for: <strong style="color: var(--gray-700);"><?php echo htmlspecialchars($submission['task_title']); ?></strong></p>
        </div>

        <div class="current-file">
            <h4><span class="glyphicon glyphicon-file"></span> Current File</h4>
            <p style="margin-bottom: 8px;"><strong style="color: var(--gray-600);">Filename:</strong> <span style="color: var(--gray-800);"><?php echo htmlspecialchars(basename($submission['file_path'])); ?></span></p>
            <p style="margin-bottom: 8px;"><strong style="color: var(--gray-600);">Uploaded:</strong> <span style="color: var(--gray-800);"><?php echo date('M d, Y - H:i', strtotime($submission['submitted_at'])); ?></span></p>
            <?php if ($submission['comments']): ?>
                <p style="margin-bottom: 0;"><strong style="color: var(--gray-600);">Comments:</strong> <span style="color: var(--gray-800);"><?php echo htmlspecialchars($submission['comments']); ?></span></p>
            <?php endif; ?>
        </div>

        <form method="POST" action="" enctype="multipart/form-data" id="editForm">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <div class="form-group">
                <label style="font-weight: 600; color: var(--gray-700);"><span class="glyphicon glyphicon-file"></span> Replace File (Optional)</label>
                <input type="file" name="submission_file" id="file-input" style="display: none;"
                       accept=".zip,.rar,.7z,.tar,.gz,.pdf,.doc,.docx,.txt,.php,.html,.css,.js,.json,.xml,.py,.java,.cpp,.c,.h,.cs,.sql,.sh,.bat,.jpg,.jpeg,.png,.gif">
                <div class="file-preview" id="file-drop">
                    <div id="file-placeholder">
                        <span class="glyphicon glyphicon-cloud-upload" style="font-size: 56px; color: var(--primary);"></span>
                        <p style="margin-top: 20px; font-size: 16px; color: var(--gray-600);">
                            <strong style="color: var(--gray-800);">Click to select a new file</strong><br>
                            <small>Or leave empty to keep current file</small>
                        </p>
                    </div>
                    <div id="file-selected" style="display: none;">
                        <span class="glyphicon glyphicon-ok-circle" style="font-size: 56px; color: var(--success);"></span>
                        <p style="margin-top: 20px; font-size: 16px; color: var(--gray-800);">
                            <strong id="selected-filename"></strong><br>
                            <small id="selected-filesize" style="color: var(--gray-500);"></small>
                        </p>
                    </div>
                </div>
                <small style="color: var(--gray-500); margin-top: 12px; display: block;">
                    <strong>Allowed:</strong> .zip, .rar, .pdf, .doc, code files (.php, .js, .py, etc.), images<br>
                    <strong>Max size:</strong> 50MB for archives, 10MB for others
                </small>
            </div>

            <div class="form-group">
                <label style="font-weight: 600; color: var(--gray-700);"><span class="glyphicon glyphicon-comment"></span> Comments</label>
                <textarea name="comments" class="form-control" rows="4" placeholder="Add or update comments about your submission..." style="border-radius: var(--radius-lg); padding: 16px;"><?php echo htmlspecialchars($submission['comments']); ?></textarea>
            </div>

            <button type="submit" class="btn btn-primary btn-lg btn-rounded btn-block" style="padding: 16px; margin-top: 24px;">
                <span class="glyphicon glyphicon-floppy-disk"></span> Update Submission
            </button>
        </form>
    </div>
</div>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
<script>
$(document).ready(function() {
    $('#file-drop').on('click', function() {
        $('#file-input').click();
    });
    
    $('#file-input').on('change', function() {
        var file = this.files[0];
        if (file) {
            $('#file-placeholder').hide();
            $('#file-selected').show();
            $('#selected-filename').text(file.name);
            $('#selected-filesize').text((file.size / 1024).toFixed(2) + ' KB');
            $('#file-drop').addClass('active');
        }
    });
    
    $('#editForm').on('submit', function(e) {
        var comments = $('textarea[name="comments"]').val().trim();
        var hasFile = $('#file-input')[0].files.length > 0;
        
        if (!comments && !hasFile) {
            e.preventDefault();
            alert('Please either update the comments or select a new file.');
            return false;
        }
    });
});
</script>
</body>
</html>
