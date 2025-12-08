<?php
// config/includes/config.php - Configuration

// Base URL (project lives directly in htdocs)
$base_url = 'http://localhost';

// No extra project path needed
$project_path = '';

// Full base URL
define('BASE_URL', $base_url . $project_path);
define('SITE_URL', $base_url . $project_path);

// Directories
define('AUTH_URL', BASE_URL . '/auth');
define('USER_URL', BASE_URL . '/user');
define('ADMIN_URL', BASE_URL . '/admin');
define('TEAM_URL', BASE_URL . '/team');
define('TASK_URL', BASE_URL . '/task');
define('ASSETS_URL', BASE_URL . '/assets');
?>
