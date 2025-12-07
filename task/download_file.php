<?php
require_once '../config/includes/db_connect.php';
require_once '../config/includes/functions.php';

// Hide SQL errors from users
error_reporting(0);
mysqli_report(MYSQLI_REPORT_OFF);

if (!isLoggedIn()) {
    die("Unauthorized access");
}

$submission_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get submission details with authorization check
$stmt = $conn->prepare("SELECT s.*, t.title as task_title, t.team_id, tm.leader_id
                        FROM submissions s
                        INNER JOIN tasks t ON s.task_id = t.id
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

// Get file path (stored as relative ../assets/images/uploads/...)
$stored_path = $submission['file_path'];
// Normalize: strip any leading ../ and prepend project root relative to this file
$relative_path = ltrim(str_replace('..', '', $stored_path), '/\\');
$abs_candidate = __DIR__ . '/../' . $relative_path;
$abs_path = realpath($abs_candidate) ?: $abs_candidate; // realpath fails if missing file; keep candidate for existence check
$uploads_dir = realpath(__DIR__ . '/../assets/images/uploads');

// Validate path stays inside uploads directory (prefix match)
if (!$uploads_dir || strpos($abs_path, $uploads_dir) !== 0) {
    die("Invalid file path");
}

// Check if file exists
if (!file_exists($abs_path)) {
    die("File not found on server");
}

// Get file info
$file_name = basename($abs_path);
$file_size = filesize($abs_path);
$file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

// Set MIME type
$mime_types = array(
    // Archives
    'zip' => 'application/zip',
    'rar' => 'application/x-rar-compressed',
    '7z' => 'application/x-7z-compressed',
    'tar' => 'application/x-tar',
    'gz' => 'application/gzip',
    // Documents
    'pdf' => 'application/pdf',
    'doc' => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'txt' => 'text/plain',
    // Code files
    'php' => 'text/plain',
    'html' => 'text/html',
    'css' => 'text/css',
    'js' => 'application/javascript',
    'json' => 'application/json',
    'xml' => 'application/xml',
    'py' => 'text/plain',
    'java' => 'text/plain',
    'cpp' => 'text/plain',
    'c' => 'text/plain',
    'sql' => 'text/plain',
    // Images
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'gif' => 'image/gif'
);

$mime_type = isset($mime_types[$file_ext]) ? $mime_types[$file_ext] : 'application/octet-stream';

// Log download activity
log_activity($conn, $_SESSION['user_id'], 'download_submission', 
             "Downloaded submission #$submission_id: $file_name");

// Set headers for download
header('Content-Description: File Transfer');
header('Content-Type: ' . $mime_type);
header('Content-Disposition: attachment; filename="' . $file_name . '"');
header('Content-Transfer-Encoding: binary');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . $file_size);

// Clear output buffer
if (ob_get_level()) {
    ob_clean();
    flush();
}

// Read and output file
readfile($abs_path);
exit;
?>
