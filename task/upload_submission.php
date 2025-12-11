<?php
require_once '../config/includes/db_connect.php';
require_once '../config/includes/functions.php';

// Hide SQL errors from users
error_reporting(0);
mysqli_report(MYSQLI_REPORT_OFF);

if (!isLoggedIn()) {
    redirect('../auth/login.php');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $task_id = (int)$_POST['task_id'];
    $user_id = $_SESSION['user_id'];
    $comments = sanitize($conn, $_POST['comments']);

    // Handle File Upload
    $target_dir = "../assets/images/uploads/";
    if(isset($_FILES["submission_file"]) && $_FILES["submission_file"]["error"] == 0) {
        // Allowed file types for submissions
        $allowed_types = array(
            // Archives (للمشاريع الكاملة)
            'zip', 'rar', '7z', 'tar', 'gz',
            // Documents
            'pdf', 'doc', 'docx', 'txt', 'md', 'markdown', 'csv', 'log',
            // Code files (يمكن عرضها)
            'php', 'html', 'htm', 'css', 'scss', 'sass', 'less',
            'js', 'jsx', 'ts', 'tsx', 'json', 'xml', 'yaml', 'yml',
            'py', 'java', 'jar', 'cpp', 'c', 'h', 'hpp', 'cs',
            'sql', 'sh', 'bash', 'bat', 'ps1', 'cmd',
            'rb', 'go', 'rs', 'swift', 'kt', 'kts', 'scala', 'r', 'lua', 'pl', 'pm',
            'vue', 'svelte', 'asp', 'aspx', 'jsp',
            'ini', 'conf', 'cfg', 'env', 'htaccess', 'gitignore', 'dockerfile', 'makefile', 'gradle', 'properties',
            // Images (للـ screenshots)
            'jpg', 'jpeg', 'png', 'gif'
        );
        $allowed_mimes = array(
            // Archives
            'application/zip', 'application/x-zip-compressed',
            'application/x-rar-compressed', 'application/x-rar',
            'application/x-7z-compressed',
            'application/x-tar', 'application/gzip',
            // Documents
            'application/pdf',
            'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'text/plain',
            // Code files
            'text/html', 'text/css', 'text/javascript',
            'application/javascript', 'application/json',
            'application/xml', 'text/xml',
            'text/x-php', 'application/x-httpd-php',
            'text/x-python', 'text/x-java', 'text/x-c',
            'application/x-sql',
            // Images
            'image/jpeg', 'image/png', 'image/gif'
        );
        $file_ext = strtolower(pathinfo($_FILES["submission_file"]["name"], PATHINFO_EXTENSION));
        
        // Get MIME type (fallback for older PHP versions)
        $file_mime = '';
        if (function_exists('mime_content_type')) {
            $file_mime = mime_content_type($_FILES["submission_file"]["tmp_name"]);
        } elseif (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $file_mime = finfo_file($finfo, $_FILES["submission_file"]["tmp_name"]);
            finfo_close($finfo);
        } else {
            $file_mime = $_FILES["submission_file"]["type"];
        }
        
        // Validate file extension AND mime type
        if (!in_array($file_ext, $allowed_types)) {
            // Get team_id for redirect
            $team_stmt = $conn->prepare("SELECT team_id FROM tasks WHERE id = ?");
            $team_stmt->bind_param("i", $task_id);
            $team_stmt->execute();
            $team_result = $team_stmt->get_result();
            $team_row = $team_result->fetch_assoc();
            $team_stmt->close();
            
            $_SESSION['message'] = '<strong><span class="glyphicon glyphicon-ban-circle"></span> File Type Not Allowed!</strong><br>The file extension <strong>.' . htmlspecialchars($file_ext) . '</strong> is not allowed. Allowed types: .zip, .rar, .pdf, .doc, .php, .html, .css, .js, .jpg, .png, etc.';
            $_SESSION['message_type'] = 'danger';
            
            $redirect_url = $team_row ? 'team_tasks.php?id=' . $team_row['team_id'] : '../user/dashboard.php';
            redirect($redirect_url);
        }
        
        // Optional: MIME validation (can be bypassed in some PHP versions)
        if (!empty($file_mime) && !in_array($file_mime, $allowed_mimes)) {
            // Soft validation - log but don't block
            error_log("MIME type warning: " . $file_mime . " for file " . $_FILES['submission_file']['name']);
        }
        // Validate file size (max 50MB for archives, 10MB for others)
        $max_size = in_array($file_ext, array('zip', 'rar', '7z', 'tar', 'gz')) ? 50 * 1024 * 1024 : 10 * 1024 * 1024;
        $max_size_mb = $max_size / (1024 * 1024);
        
        if ($_FILES["submission_file"]["size"] > $max_size) {
            $file_size_mb = round($_FILES["submission_file"]["size"] / (1024 * 1024), 2);
            
            // Get team_id for redirect
            $team_stmt = $conn->prepare("SELECT team_id FROM tasks WHERE id = ?");
            $team_stmt->bind_param("i", $task_id);
            $team_stmt->execute();
            $team_result = $team_stmt->get_result();
            $team_row = $team_result->fetch_assoc();
            $team_stmt->close();
            
            $_SESSION['message'] = '<strong><span class="glyphicon glyphicon-warning-sign"></span> File Too Large!</strong><br>Your file is <strong>' . $file_size_mb . ' MB</strong>, maximum allowed is <strong>' . $max_size_mb . ' MB</strong>.';
            $_SESSION['message_type'] = 'danger';
            
            $redirect_url = $team_row ? 'team_tasks.php?id=' . $team_row['team_id'] : '../user/dashboard.php';
            redirect($redirect_url);
        }
        
        $filename = time() . "_" . uniqid() . "_" . basename($_FILES["submission_file"]["name"]);
        $target_file = $target_dir . $filename;
        if (move_uploaded_file($_FILES["submission_file"]["tmp_name"], $target_file)) {
            // Get current version number (major version only for user submissions)
            $version_stmt = $conn->prepare("SELECT COALESCE(MAX(CAST(SUBSTRING_INDEX(version, '.', 1) AS UNSIGNED)), 0) + 1 as next_version FROM submissions WHERE task_id = ? AND user_id = ?");
            $version_stmt->bind_param("ii", $task_id, $user_id);
            $version_stmt->execute();
            $version_result = $version_stmt->get_result();
            $version_row = $version_result->fetch_assoc();
            $new_version = (string)$version_row['next_version'];
            $version_stmt->close();
            
            // Mark all previous submissions as not latest
            $update_stmt = $conn->prepare("UPDATE submissions SET is_latest = FALSE WHERE task_id = ? AND user_id = ?");
            $update_stmt->bind_param("ii", $task_id, $user_id);
            $update_stmt->execute();
            $update_stmt->close();
            
            // Insert new submission with version
            $stmt = $conn->prepare("INSERT INTO submissions (task_id, user_id, file_path, comments, version, is_latest) VALUES (?, ?, ?, ?, ?, TRUE)");
            $stmt->bind_param("iisss", $task_id, $user_id, $target_file, $comments, $new_version);
            if ($stmt->execute()) {
                // Update task status
                $update_stmt = $conn->prepare("UPDATE tasks SET status = 'submitted' WHERE id = ?");
                $update_stmt->bind_param("i", $task_id);
                $update_stmt->execute();
                $update_stmt->close();
                
                // Get team_id for redirect
                $team_stmt = $conn->prepare("SELECT team_id FROM tasks WHERE id = ?");
                $team_stmt->bind_param("i", $task_id);
                $team_stmt->execute();
                $team_result = $team_stmt->get_result();
                $team_row = $team_result->fetch_assoc();
                $team_stmt->close();
                
                redirect('team_tasks.php?id=' . $team_row['team_id']);
            } else {
                $_SESSION['message'] = "Error saving submission. Please try again.";
                $_SESSION['message_type'] = "danger";
                redirect('../user/dashboard.php');
            }
            $stmt->close();
        } else {
            $_SESSION['message'] = "Error uploading file. Please try again.";
            $_SESSION['message_type'] = "danger";
            redirect('../user/dashboard.php');
        }
    } else {
        $_SESSION['message'] = "No file uploaded.";
        $_SESSION['message_type'] = "warning";
        redirect('../user/dashboard.php');
    }
}
?>
