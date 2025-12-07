<?php
require_once '../config/includes/db_connect.php';
require_once '../config/includes/functions.php';
require_once '../config/includes/navbar.php';

// Hide SQL errors from users
error_reporting(0);
mysqli_report(MYSQLI_REPORT_OFF);

if (isLoggedIn()) {
    if (isAdmin()) {
        redirect('../admin/admin_dashboard.php');
    } else {
        redirect('../user/dashboard.php');
    }
}

$error = '';

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Invalid request. Please try again.";
    } else {
        // Rate limiting - max 5 login attempts per 15 minutes
        $ip = $_SERVER['REMOTE_ADDR'];
        $attempts_key = 'login_attempts_' . md5($ip);
        $attempts = isset($_SESSION[$attempts_key]) ? $_SESSION[$attempts_key] : ['count' => 0, 'time' => time()];
        
        if ($attempts['count'] >= 5 && (time() - $attempts['time']) < 900) {
            $wait_time = ceil((900 - (time() - $attempts['time'])) / 60);
            $error = "Too many login attempts. Please wait $wait_time minutes.";
        } else {
            if ((time() - $attempts['time']) >= 900) {
                $attempts = ['count' => 0, 'time' => time()];
            }
            
            $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
            $password = $_POST['password'];

            $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            
            if ($user['role'] == 'admin') {
                redirect('../admin/admin_dashboard.php');
            } else {
                redirect('../user/dashboard.php');
            }
            } else {
                $error = "Invalid email or password.";
                $attempts['count']++;
                $attempts['time'] = time();
                $_SESSION[$attempts_key] = $attempts;
            }
        } else {
            $error = "Invalid email or password.";
            $attempts['count']++;
            $attempts['time'] = time();
            $_SESSION[$attempts_key] = $attempts;
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
    <title>Login - TaskHive</title>
    <meta name="description" content="Login to TaskHive - Team Management Platform">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/components.css">
    <link rel="stylesheet" href="../assets/css/pages/auth.css">
</head>
<body class="auth-body">
<?php render_unified_navbar(th_nav_template('landing', [
    'theme' => 'auth',
    'class' => 'th-navbar--static',
    'links' => [
        ['label' => 'Home', 'href' => '../index.php', 'slug' => 'overview'],
        ['label' => 'Register', 'href' => 'register.php', 'slug' => 'register'],
    ],
    'actions' => [
        ['label' => 'Login', 'href' => 'login.php', 'style' => 'primary', 'slug' => 'login', 'active' => true],
    ],
])); ?>

<div class="auth-page">
<div class="auth-container animate-fadeInUp">
    <div class="auth-logo">
        <div class="logo-icon">🐝</div>
        <h2>Welcome Back!</h2>
        <p>Sign in to your TaskHive account</p>
    </div>
    
    <?php if($error): ?>
        <div class="alert alert-danger">
            <span class="glyphicon glyphicon-exclamation-sign"></span>
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" action="">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        
        <div class="form-group">
            <label>Email Address</label>
            <div class="input-group">
                <span class="input-group-addon"><i class="glyphicon glyphicon-envelope"></i></span>
                <input type="email" name="email" class="form-control" placeholder="Enter your email" required autofocus>
            </div>
        </div>
        
        <div class="form-group">
            <label>Password</label>
            <div class="input-group">
                <span class="input-group-addon"><i class="glyphicon glyphicon-lock"></i></span>
                <input type="password" name="password" class="form-control" placeholder="Enter your password" required>
            </div>
        </div>
        
        <button type="submit" class="btn btn-primary btn-block btn-lg">
            <i class="glyphicon glyphicon-log-in"></i> Sign In
        </button>
    </form>
    
    <div class="auth-footer">
        <p>Don't have an account? <a href="register.php">Create one</a></p>
        <a href="../index.php" class="back-link">
            <span class="glyphicon glyphicon-arrow-left"></span> Back to Home
        </a>
    </div>
</div>
</div>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
<script src="../assets/js/main.js"></script>
</body>
</html>
