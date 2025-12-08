<?php
require_once __DIR__ . '/../bootstrap.php';

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function sanitize($conn, $str) {
    return mysqli_real_escape_string($conn, htmlspecialchars($str)); //الدالة هدفها تنظيف البيانات قبل تخزينها في قاعدة البيانات أو عرضها في الصفحة
}

function log_activity($conn, $user_id, $action, $details = ''){ //تسجيل النشاطات
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $stmt = $conn->prepare("INSERT INTO activity_log (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $user_id, $action, $details, $ip_address);
    $stmt->execute();
    $stmt->close();
}

// Generate CSRF token if not exists
function get_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verify CSRF token
function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
?>
