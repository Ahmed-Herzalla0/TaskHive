<?php
// إخفاء أخطاء SQL عن المستخدم
error_reporting(0);
ini_set('display_errors', 0);
mysqli_report(MYSQLI_REPORT_OFF);

require_once '../config/includes/db_connect.php';
require_once '../config/includes/functions.php';
require_once '../config/includes/navbar.php';

if (isLoggedIn()) {
    if (isAdmin()) {
        redirect('../admin/admin_dashboard.php');
    } else {
        redirect('../user/dashboard.php');
    }
}

$error = '';
$success = '';

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// AJAX check for username/email availability
if (isset($_GET['check'])) {
    header('Content-Type: application/json');
    $response = ['available' => true, 'message' => ''];
    
    if ($_GET['check'] === 'username' && !empty($_GET['value'])) {
        $username = $conn->real_escape_string(trim($_GET['value']));
        $result = $conn->query("SELECT id FROM users WHERE username = '$username' LIMIT 1");
        if ($result && $result->num_rows > 0) {
            $response = ['available' => false, 'message' => 'Username is already taken'];
        }
    } elseif ($_GET['check'] === 'email' && !empty($_GET['value'])) {
        $email = $conn->real_escape_string(trim($_GET['value']));
        $result = $conn->query("SELECT id FROM users WHERE email = '$email' LIMIT 1");
        if ($result && $result->num_rows > 0) {
            $response = ['available' => false, 'message' => 'Email is already registered'];
        }
    }
    
    echo json_encode($response);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Invalid request. Please try again.";
    } else {
        // Input validation
        $first_name = sanitize($conn, trim($_POST['first_name']));
        $last_name = sanitize($conn, trim($_POST['last_name']));
        $username = sanitize($conn, trim($_POST['username']));
        $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
        $password_input = $_POST['password'];
        
        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid email format.";
        }
        // Validate password strength (min 8 chars)
        elseif (strlen($password_input) < 8) {
            $error = "Password must be at least 8 characters long.";
        }
        // Validate username (alphanumeric only)
        elseif (!preg_match('/^[a-zA-Z0-9_]{3,50}$/', $username)) {
            $error = "Username must be 3-50 characters (letters, numbers, underscore only).";
        } else {
            // التحقق من وجود اسم المستخدم أو الإيميل مسبقاً
            $check_user = $conn->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
            $check_user->bind_param("s", $username);
            $check_user->execute();
            if ($check_user->get_result()->num_rows > 0) {
                $error = "Username is already taken. Please choose another.";
                $check_user->close();
            } else {
                $check_user->close();
                
                $check_email = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
                $check_email->bind_param("s", $email);
                $check_email->execute();
                if ($check_email->get_result()->num_rows > 0) {
                    $error = "Email is already registered. Please use another email.";
                    $check_email->close();
                } else {
                    $check_email->close();
                    
            $password = password_hash($password_input, PASSWORD_DEFAULT);
            $role = 'user'; // Default role for new registrations

            // Handle File Upload with validation
            $target_dir = "../assets/images/uploads/";
            $profile_pic = "";
            if(isset($_FILES["profile_pic"]) && $_FILES["profile_pic"]["error"] == 0) {
                $allowed_types = array('jpg', 'jpeg', 'png', 'gif');
                $allowed_mimes = array('image/jpeg', 'image/png', 'image/gif');
                $file_ext = strtolower(pathinfo($_FILES["profile_pic"]["name"], PATHINFO_EXTENSION));
                
                // Get MIME type (fallback for older PHP versions)
                $file_mime = '';
                if (function_exists('mime_content_type')) {
                    $file_mime = mime_content_type($_FILES["profile_pic"]["tmp_name"]);
                } elseif (function_exists('finfo_open')) {
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $file_mime = finfo_file($finfo, $_FILES["profile_pic"]["tmp_name"]);
                    finfo_close($finfo);
                } else {
                    $file_mime = $_FILES["profile_pic"]["type"];
                }
        
                // Validate file extension AND mime type
                if (!in_array($file_ext, $allowed_types) || !in_array($file_mime, $allowed_mimes)) {
                    $error = "Invalid file type. Only JPG, PNG, and GIF images are allowed.";
                }
                // Validate file size (max 5MB)
                elseif ($_FILES["profile_pic"]["size"] > 5 * 1024 * 1024) {
                    $error = "File size exceeds 5MB limit.";
                } else {
                    $filename = time() . "_" . uniqid() . "." . $file_ext;
                    $target_file = $target_dir . $filename;
                    if (move_uploaded_file($_FILES["profile_pic"]["tmp_name"], $target_file)) {
                        $profile_pic = $target_file;
                    } else {
                        $error = "Sorry, there was an error uploading your file.";
                    }
                }
            }

            if (!$error) {
                try {
                    $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, username, email, password, role, profile_pic) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("sssssss", $first_name, $last_name, $username, $email, $password, $role, $profile_pic);
                    if ($stmt->execute()) {
                        $new_user_id = $stmt->insert_id;
                        log_activity($conn, $new_user_id, 'register', "New user registered: $username");
                        $success = "Account created successfully. <a href='login.php'>Login here</a>";
                        // Reset CSRF token
                        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                    } else {
                        $error = "Registration failed. Please try again.";
                    }
                    $stmt->close();
                } catch (Exception $e) {
                    error_log("Registration error: " . $e->getMessage());
                    $error = "Registration failed. Please try again.";
                }
            }
                }
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
    <title>Register - TaskHive</title>
    
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
        ['label' => 'Login', 'href' => 'login.php', 'slug' => 'login'],
    ],
    'actions' => [
        ['label' => 'Get Started', 'href' => 'register.php', 'style' => 'primary', 'slug' => 'register', 'active' => true],
    ],
])); ?>

<div class="auth-page">
<div class="auth-container auth-container-wide animate-fadeInUp">
    <div class="auth-logo">
        <div class="logo-icon">🐝</div>
        <h2>Create Account</h2>
        <p>Join TaskHive and start collaborating</p>
    </div>
    
    <?php if($error): ?>
        <div class="alert alert-danger">
            <span class="glyphicon glyphicon-exclamation-sign"></span>
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>
    <?php if($success): ?>
        <div class="alert alert-success">
            <span class="glyphicon glyphicon-ok-sign"></span>
            <?php echo $success; ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" action="" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label>First Name</label>
                    <input type="text" name="first_name" class="form-control" placeholder="John" required>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>Last Name</label>
                    <input type="text" name="last_name" class="form-control" placeholder="Doe" required>
                </div>
            </div>
        </div>
        
        <div class="form-group">
            <label>Username</label>
            <div class="input-group">
                <span class="input-group-addon">@</span>
                <input type="text" name="username" id="username" class="form-control" placeholder="username" required>
            </div>
            <div id="username-feedback" class="field-feedback"></div>
        </div>
        
        <div class="form-group">
            <label>Email Address</label>
            <div class="input-group">
                <span class="input-group-addon"><i class="glyphicon glyphicon-envelope"></i></span>
                <input type="email" name="email" id="email" class="form-control" placeholder="john@example.com" required>
            </div>
            <div id="email-feedback" class="field-feedback"></div>
        </div>
        
        <div class="form-group">
            <label>Password</label>
            <div class="input-group">
                <span class="input-group-addon"><i class="glyphicon glyphicon-lock"></i></span>
                <input type="password" name="password" id="password" class="form-control" placeholder="Min. 8 characters" required>
            </div>
            <div id="password-strength" class="password-strength-meter">
                <div class="strength-bar"></div>
                <span class="strength-text"></span>
            </div>
        </div>
        
        <div class="form-group text-center">
            <label class="profile-label">Profile Photo (Optional)</label>
            <div class="profile-preview" onclick="document.getElementById('profile_pic_input').click();">
                <i class="glyphicon glyphicon-camera" id="profile_icon"></i>
                <img id="profile_preview" src="#" alt="Preview" class="preview-img">
                <div class="camera-icon">
                    <i class="glyphicon glyphicon-plus" style="margin: 0 0 0 2px;"></i>
                </div>
            </div>
            <input type="file" name="profile_pic" id="profile_pic_input" class="hidden-input" accept="image/*" onchange="readURL(this);">
        </div>
        
        <button type="submit" class="btn btn-primary btn-block btn-lg">
            <i class="glyphicon glyphicon-user"></i> Create Account
        </button>
    </form>
    
    <div class="auth-footer">
        <p>Already have an account? <a href="login.php">Sign in</a></p>
        <a href="../index.php" class="back-link">
            <span class="glyphicon glyphicon-arrow-left"></span> Back to Home
        </a>
    </div>
</div>
</div>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
<script src="../assets/js/main.js"></script>
<script>
function readURL(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function (e) {
            $('#profile_preview').attr('src', e.target.result).show();
            $('#profile_icon').hide();
        };
        reader.readAsDataURL(input.files[0]);
    }
}

$(document).ready(function() {
    var usernameTimer, emailTimer;
    
    // التحقق من اسم المستخدم
    $('#username').on('input', function() {
        var input = $(this);
        var value = input.val().trim();
        var feedback = $('#username-feedback');
        
        clearTimeout(usernameTimer);
        feedback.html('');
        input.removeClass('input-error input-success');
        
        if (value.length >= 3) {
            usernameTimer = setTimeout(function() {
                $.get('register.php', {check: 'username', value: value}, function(response) {
                    if (!response.available) {
                        input.addClass('input-error');
                        feedback.html('<span class="text-danger"><i class="glyphicon glyphicon-remove"></i> ' + response.message + '</span>');
                    } else {
                        input.addClass('input-success');
                        feedback.html('<span class="text-success"><i class="glyphicon glyphicon-ok"></i> Username is available</span>');
                    }
                });
            }, 500);
        }
    });
    
    // التحقق من الإيميل
    $('#email').on('input', function() {
        var input = $(this);
        var value = input.val().trim();
        var feedback = $('#email-feedback');
        var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        
        clearTimeout(emailTimer);
        feedback.html('');
        input.removeClass('input-error input-success');
        
        if (emailRegex.test(value)) {
            emailTimer = setTimeout(function() {
                $.get('register.php', {check: 'email', value: value}, function(response) {
                    if (!response.available) {
                        input.addClass('input-error');
                        feedback.html('<span class="text-danger"><i class="glyphicon glyphicon-remove"></i> ' + response.message + '</span>');
                    } else {
                        input.addClass('input-success');
                        feedback.html('<span class="text-success"><i class="glyphicon glyphicon-ok"></i> Email is available</span>');
                    }
                });
            }, 500);
        }
    });
    
    // مؤشر قوة كلمة السر
    $('#password').on('input', function() {
        var password = $(this).val();
        var strength = 0;
        var feedback = '';
        var color = '';
        
        if (password.length >= 8) strength++;
        if (password.length >= 12) strength++;
        if (/[a-z]/.test(password)) strength++;
        if (/[A-Z]/.test(password)) strength++;
        if (/[0-9]/.test(password)) strength++;
        if (/[^a-zA-Z0-9]/.test(password)) strength++;
        
        if (password.length === 0) {
            $('#password-strength').hide();
            return;
        }
        
        $('#password-strength').show();
        
        if (strength <= 2) {
            feedback = 'Weak';
            color = '#e74c3c';
            width = '25%';
        } else if (strength <= 4) {
            feedback = 'Medium';
            color = '#f39c12';
            width = '50%';
        } else if (strength <= 5) {
            feedback = 'Strong';
            color = '#27ae60';
            width = '75%';
        } else {
            feedback = 'Very Strong';
            color = '#27ae60';
            width = '100%';
        }
        
        $('.strength-bar').css({'width': width, 'background-color': color});
        $('.strength-text').text(feedback).css('color', color);
    });
    
    // منع الإرسال إذا كان هناك أخطاء
    $('form').on('submit', function(e) {
        if ($('.input-error').length > 0) {
            e.preventDefault();
            alert('Please fix the errors before submitting.');
        }
    });
});
</script>
<style>
.input-error { border-color: #e74c3c !important; }
.input-success { border-color: #27ae60 !important; }
.field-feedback { margin-top: 5px; font-size: 13px; }
.password-strength-meter { margin-top: 8px; display: none; }
.strength-bar { height: 4px; border-radius: 2px; transition: all 0.3s; width: 0; }
.strength-text { font-size: 12px; margin-top: 4px; display: block; }
</style>
</body>
</html>
