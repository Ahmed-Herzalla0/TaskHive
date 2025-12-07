<?php
require_once '../config/includes/db_connect.php';
require_once '../config/includes/functions.php';
require_once '../config/includes/navbar.php';

// Hide SQL errors from users
error_reporting(0);
mysqli_report(MYSQLI_REPORT_OFF);

if (!isLoggedIn()) {
    redirect('../auth/login.php');
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Get current user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = sanitize($conn, trim($_POST['first_name'] ?? ''));
    $last_name = sanitize($conn, trim($_POST['last_name'] ?? ''));
    $username = sanitize($conn, trim($_POST['username'] ?? ''));
    $email = sanitize($conn, trim($_POST['email'] ?? ''));
    
    // Handle profile picture upload
    $profile_pic = $user['profile_pic'];
    if(isset($_FILES["profile_pic"]) && $_FILES["profile_pic"]["error"] == 0) {
        $allowed_types = array('jpg', 'jpeg', 'png', 'gif');
        $file_ext = strtolower(pathinfo($_FILES["profile_pic"]["name"], PATHINFO_EXTENSION));
        
        if (!in_array($file_ext, $allowed_types)) {
            $error = "Invalid file type. Only JPG, PNG, and GIF are allowed.";
        } elseif ($_FILES["profile_pic"]["size"] > 5 * 1024 * 1024) {
            $error = "File size exceeds 5MB limit.";
        } else {
            $filename = time() . "_" . uniqid('', true) . "." . $file_ext;
            $target_file = "../assets/images/uploads/" . $filename;
            if (move_uploaded_file($_FILES["profile_pic"]["tmp_name"], $target_file)) {
                if ($user['profile_pic'] && file_exists($user['profile_pic'])) {
                    unlink($user['profile_pic']);
                }
                $profile_pic = $target_file;
            } else {
                $error = "Error uploading file.";
            }
        }
    }
    
    if (!$error) {
        // Update basic info
        $stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, username = ?, email = ?, profile_pic = ? WHERE id = ?");
        $stmt->bind_param("sssssi", $first_name, $last_name, $username, $email, $profile_pic, $user_id);
        
        if ($stmt->execute()) {
            $_SESSION['username'] = $username;
            
            // Update password if provided
            if (!empty($_POST['new_password'])) {
                $new_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
                $stmt2 = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt2->bind_param("si", $new_password, $user_id);
                $stmt2->execute();
                $stmt2->close();
            }
            
            $success = "Profile updated successfully!";
            
            // Refresh user data
            $stmt3 = $conn->prepare("SELECT * FROM users WHERE id = ?");
            $stmt3->bind_param("i", $user_id);
            $stmt3->execute();
            $user = $stmt3->get_result()->fetch_assoc();
            $stmt3->close();
        } else {
            $error = "Error updating profile. Please try again.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - TaskHive</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/components.css">
    <link rel="stylesheet" href="../assets/css/pages/user.css">
</head>
<body class="user-body">
<!-- Modern Navbar -->
<?php render_unified_navbar(th_nav_template('user', [
    'active' => 'profile',
])); ?>

<div class="container">
    <div class="profile-container animate-fadeInUp">
        <div class="profile-header">
            <div class="profile-preview" onclick="$('#profile_pic_input').click();">
                <?php if ($user['profile_pic']): ?>
                    <img id="profile_preview" src="<?php echo htmlspecialchars($user['profile_pic']); ?>" alt="Profile">
                <?php else: ?>
                    <span id="profile_initial"><?php echo strtoupper(htmlspecialchars(substr($user['username'], 0, 1))); ?></span>
                    <img id="profile_preview" src="#" alt="Profile" class="hidden-preview">
                <?php endif; ?>
                <div class="camera-icon">
                    <span class="glyphicon glyphicon-camera"></span>
                </div>
            </div>
            <h2>Edit Profile</h2>
            <p>Update your personal information and settings</p>
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
        
        <form method="POST" action="" enctype="multipart/form-data">
            <input type="file" name="profile_pic" id="profile_pic_input" class="hidden-input" accept="image/*" onchange="readURL(this);">
            
            <!-- Personal Information -->
            <div class="form-section">
                <h4><span class="glyphicon glyphicon-info-sign"></span> Personal Information</h4>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>First Name</label>
                            <input type="text" name="first_name" class="form-control" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Last Name</label>
                            <input type="text" name="last_name" class="form-control" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>
            </div>
            
            <!-- Change Password -->
            <div class="form-section">
                <h4><span class="glyphicon glyphicon-lock"></span> Change Password</h4>
                <p class="section-hint">Leave blank if you don't want to change your password</p>
                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" name="new_password" class="form-control" placeholder="Enter new password (optional)">
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary btn-lg btn-rounded">
                    <span class="glyphicon glyphicon-floppy-disk"></span> Save Changes
                </button>
                <a href="dashboard.php" class="btn btn-secondary btn-lg btn-rounded">
                    <span class="glyphicon glyphicon-remove"></span> Cancel
                </a>
            </div>
        </form>
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
            $('#profile_initial').hide();
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
</body>
</html>
