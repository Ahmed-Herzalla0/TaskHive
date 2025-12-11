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
$stmt = $conn->prepare("SELECT s.*, t.title as task_title, t.team_id, u.username, tm.leader_id
                        FROM submissions s
                        INNER JOIN tasks t ON s.task_id = t.id
                        INNER JOIN users u ON s.user_id = u.id
                        INNER JOIN teams tm ON t.team_id = tm.id
                        WHERE s.id = ?");
$stmt->bind_param("i", $submission_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("Submission not found");
}

$submission = $result->fetch_assoc();
$stmt->close();

// Check authorization (team leader, submitter, or admin)
if ($submission['leader_id'] != $_SESSION['user_id'] && 
    $submission['user_id'] != $_SESSION['user_id'] && 
    !isAdmin()) {
    die("Access denied");
}

// Get file info
$file_path = $submission['file_path'];
$file_name = basename($file_path);
$file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
// Build absolute path properly for Windows/XAMPP
$relative_path = str_replace('../', '', $file_path);
$abs_file_path = __DIR__ . '/../' . $relative_path;
$file_size = file_exists($abs_file_path) ? filesize($abs_file_path) : 0;

// Code extensions that can be viewed
$code_extensions = array('php', 'html', 'htm', 'css', 'scss', 'sass', 'less', 'js', 'jsx', 'ts', 'tsx', 'json', 'xml', 'yaml', 'yml', 'py', 'java', 'jar', 'cpp', 'c', 'h', 'hpp', 'cs', 'sql', 'txt', 'md', 'markdown', 'sh', 'bash', 'bat', 'ps1', 'cmd', 'rb', 'go', 'rs', 'swift', 'kt', 'kts', 'scala', 'r', 'lua', 'pl', 'pm', 'vue', 'svelte', 'asp', 'aspx', 'jsp', 'ini', 'conf', 'cfg', 'env', 'htaccess', 'gitignore', 'dockerfile', 'makefile', 'gradle', 'properties', 'log', 'csv');
$can_view_code = in_array($file_ext, $code_extensions);

// Read file content if viewable
$file_content = '';
if ($can_view_code && file_exists($file_path) && $file_size < 1024 * 1024) { // Max 1MB for viewing
    $file_content = file_get_contents($file_path);
}

// Syntax highlighting language map
$language_map = array(
    'php' => 'php',
    'html' => 'html',
    'css' => 'css',
    'js' => 'javascript',
    'json' => 'json',
    'xml' => 'xml',
    'py' => 'python',
    'java' => 'java',
    'cpp' => 'cpp',
    'c' => 'c',
    'sql' => 'sql',
    'sh' => 'bash',
    'bat' => 'batch',
    'txt' => 'text'
);
$language = isset($language_map[$file_ext]) ? $language_map[$file_ext] : 'text';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Submission - <?php echo htmlspecialchars($submission['task_title']); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/components.css">
    <link rel="stylesheet" href="../assets/css/pages/task.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.8.0/styles/github-dark.min.css">
</head>
<body>
<div class="submission-header">
    <div class="container">
        <h2><span class="glyphicon glyphicon-file"></span> Submission Details</h2>
        <p><?php echo htmlspecialchars($submission['task_title']); ?></p>
    </div>
</div>

<div class="container" style="max-width: 1100px;">
    <div class="row">
        <div class="col-md-12">
            <a href="<?php if($submission['user_id'] == $submission['leader_id']){?>../team/team_details.php?id=<?php echo $submission['team_id']; }else{?>../task/team_tasks.php?id=<?php echo $submission['team_id']; }?>" class="btn btn-secondary btn-rounded">
                <span class="glyphicon glyphicon-arrow-left"></span> Back to Team
            </a>
            
            <?php if ($submission['user_id'] == $_SESSION['user_id'] || $submission['leader_id'] == $_SESSION['user_id'] || isAdmin()): ?>
            <div class="pull-right">
                <a href="edit_submission.php?id=<?php echo $submission_id; ?>" class="btn btn-warning btn-rounded">
                    <span class="glyphicon glyphicon-edit"></span> Edit
                </a>
                <a href="#" class="btn btn-danger btn-rounded" onclick="confirmDelete(); return false;">
                    <span class="glyphicon glyphicon-trash"></span> Delete
                </a>
            </div>
            <?php endif; ?>
            <div class="clearfix"></div>
            <hr style="border-color: var(--gray-200);">
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="file-info animate-fadeInUp">
                <h4><span class="glyphicon glyphicon-info-sign" style="color: var(--primary);"></span> File Information</h4>
                <div class="row">
                    <div class="col-md-6">
                        <p><strong style="color: var(--gray-700);">Filename:</strong> <span style="color: var(--gray-600);"><?php echo htmlspecialchars($file_name); ?></span></p>
                        <p><strong style="color: var(--gray-700);">Type:</strong> <span class="info-badge">.<?php echo strtoupper($file_ext); ?></span></p>
                        <p><strong style="color: var(--gray-700);">Size:</strong> <span style="color: var(--gray-600);"><?php echo round($file_size / 1024, 2); ?> KB</span></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong style="color: var(--gray-700);">Submitted by:</strong> <span style="color: var(--gray-600);"><?php echo htmlspecialchars($submission['username']); ?></span></p>
                        <p><strong style="color: var(--gray-700);">Date:</strong> <span style="color: var(--gray-600);"><?php echo date('M d, Y - H:i', strtotime($submission['submitted_at'])); ?></span></p>
                        <p><strong style="color: var(--gray-700);">Version:</strong> 
                            <span class="badge badge-info">v<?php echo $submission['version']; ?></span>
                            <?php if ($submission['is_latest']): ?>
                                <span class="badge badge-success">Latest</span>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
                <?php if (!empty($submission['comments'])): ?>
                <div class="comment-box">
                    <strong style="color: var(--gray-700);"><span class="glyphicon glyphicon-comment"></span> Comments:</strong>
                    <p style="margin: 10px 0 0 0; color: var(--gray-600);"><?php echo nl2br(htmlspecialchars($submission['comments'])); ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if ($can_view_code && !empty($file_content)): ?>
    <!-- Code Viewer -->
    <div class="row animate-fadeInUp" style="animation-delay: 0.1s;">
        <div class="col-md-12" style="margin-bottom: 40px;">
            <div class="code-viewer">
                <div class="code-header">
                    <div class="code-title">
                        <span class="glyphicon glyphicon-file"></span> <?php echo htmlspecialchars($file_name); ?>
                        <span class="info-badge" style="background: rgba(255,255,255,0.1); color: white;">Language: <?php echo strtoupper($language); ?></span>
                        <span class="info-badge" style="background: rgba(255,255,255,0.1); color: white;">Lines: <?php echo count(explode("\n", $file_content)); ?></span>
                    </div>
                    <div>
                        <a href="download_file.php?id=<?php echo $submission_id; ?>" class="btn btn-sm btn-success btn-rounded">
                            <span class="glyphicon glyphicon-download"></span> Download
                        </a>
                    </div>
                </div>
                <div class="code-content">
                    <pre><code class="<?php echo $language; ?>"><?php echo htmlspecialchars($file_content); ?></code></pre>
                </div>
            </div>
        </div>
    </div>
    <?php else: ?>
    <!-- Download Zone -->
    <div class="row animate-fadeInUp" style="animation-delay: 0.1s;">
        <div class="col-md-12">
            <div class="download-zone">
                <div class="file-icon">
                    <span class="glyphicon glyphicon-download-alt"></span>
                </div>
                <h3 style="color: var(--gray-800);"><?php echo htmlspecialchars($file_name); ?></h3>
                <p style="color: var(--gray-500);">
                    <?php if (in_array($file_ext, array('zip', 'rar', '7z', 'tar', 'gz'))): ?>
                        <span class="glyphicon glyphicon-compressed"></span> Archive File
                    <?php elseif (in_array($file_ext, array('pdf', 'doc', 'docx'))): ?>
                        <span class="glyphicon glyphicon-file"></span> Document File
                    <?php elseif (in_array($file_ext, array('jpg', 'jpeg', 'png', 'gif'))): ?>
                        <span class="glyphicon glyphicon-picture"></span> Image File
                    <?php else: ?>
                        <span class="glyphicon glyphicon-file"></span> Binary File
                    <?php endif; ?>
                    - <?php echo round($file_size / 1024, 2); ?> KB
                </p>
                <p style="color: var(--gray-400);">This file cannot be previewed online. Download to view.</p>
                <br>
                <a href="download_file.php?id=<?php echo $submission_id; ?>" class="btn btn-primary btn-lg btn-rounded">
                    <span class="glyphicon glyphicon-download"></span> Download File
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.8.0/highlight.min.js"></script>
<script>
    hljs.highlightAll();
    
    function confirmDelete() {
        if (confirm('Are you sure you want to delete this submission?\n\nThis action cannot be undone!\n\nThe file will be permanently deleted.')) {
            window.location.href = 'delete_submission.php?id=<?php echo $submission_id; ?>&token=<?php echo get_csrf_token(); ?>';
        }
    }
</script>
</body>
</html>
