<?php
require_once '../config/includes/db_connect.php';
require_once '../config/includes/functions.php';

// Hide SQL errors from users
error_reporting(0);
mysqli_report(MYSQLI_REPORT_OFF);

if (!isLoggedIn() || !isAdmin()) {
    redirect('../auth/login.php');
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user = ['username' => '', 'email' => '', 'role' => 'user', 'id' => 0];
$is_edit = false;

if ($id) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows) {
        $user = $result->fetch_assoc();
        $is_edit = true;
    }
    $stmt->close();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitize($conn, $_POST['username']);
    $email = sanitize($conn, $_POST['email']);
    $role = sanitize($conn, $_POST['role']);
    $password = $_POST['password'];

    if ($is_edit) {
        // Update
        $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, role = ? WHERE id = ?");
        $stmt->bind_param("sssi", $username, $email, $role, $id);
        if ($stmt->execute()) {
            if (!empty($password)) {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt_pass = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt_pass->bind_param("si", $hashed, $id);
                $stmt_pass->execute();
                $stmt_pass->close();
            }
            $success = "User updated.";
            // Refresh data
            $user['username'] = $username;
            $user['email'] = $email;
            $user['role'] = $role;
        } else {
            $error = "Error updating user. Please try again.";
        }
        $stmt->close();
    } else {
        // Add
        if (empty($password)) {
            $error = "Password is required for new users.";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $username, $email, $hashed, $role);
            if ($stmt->execute()) {
                $success = "User added.";
                $id = $conn->insert_id;
                $is_edit = true;
            } else {
                $error = "Error adding user. Please try again.";
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
    <title><?php echo $is_edit ? 'Edit' : 'Add'; ?> User - TaskHive</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/components.css">
    <link rel="stylesheet" href="../assets/css/pages/admin.css">
</head>
<body class="admin-body">
<div class="user-form-container">
    <div class="back-container"></div>
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
    
    <div class="form-card animate-fadeInUp">
        <div class="form-header">
            <h2><?php echo $is_edit ? '✏️ Edit User' : '➕ Add New User'; ?></h2>
            <p><?php echo $is_edit ? 'Update user information and permissions' : 'Create a new user account'; ?></p>
        </div>
        
        <form method="POST" action="" id="userForm">
            <div class="form-group">
                <label><span class="glyphicon glyphicon-user" style="color: var(--primary);"></span> Username</label>
                <input type="text" name="username" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" required placeholder="Enter username">
            </div>
            
            <div class="form-group">
                <label><span class="glyphicon glyphicon-envelope" style="color: var(--primary);"></span> Email Address</label>
                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required placeholder="Enter email address">
            </div>
            
            <div class="form-group">
                <label>
                    <span class="glyphicon glyphicon-lock" style="color: var(--primary);"></span> Password 
                    <?php if($is_edit): ?>
                        <small>(Leave blank to keep current password)</small>
                    <?php endif; ?>
                </label>
                <input type="password" name="password" class="form-control" id="passwordInput" <?php echo $is_edit ? '' : 'required'; ?> placeholder="Enter password">
                <div class="password-strength" id="passwordStrength">
                    <div class="password-strength-bar" id="passwordStrengthBar"></div>
                </div>
                <div class="password-strength-text" id="passwordStrengthText"></div>
            </div>
            
            <div class="form-group">
                <label><span class="glyphicon glyphicon-king" style="color: var(--primary);"></span> Role</label>
                <div class="role-select-wrapper">
                    <select name="role" id="roleSelect" class="role-hidden-select">
                        <option value="user" <?php echo $user['role'] == 'user' ? 'selected' : ''; ?>>User</option>
                        <option value="admin" <?php echo $user['role'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
                    </select>
                    <div class="role-custom-select" id="roleCustomSelect">
                        <span class="role-badge <?php echo $user['role']; ?>" id="roleBadge">
                            <?php echo strtoupper($user['role']); ?>
                        </span>
                        <span class="role-select-arrow">▼</span>
                    </div>
                    <div class="role-dropdown" id="roleDropdown">
                        <div class="role-option" data-value="user">User</div>
                        <div class="role-option" data-value="admin">Admin</div>
                    </div>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary btn-lg btn-rounded btn-block" style="padding: 16px; margin-top: 16px;">
                <?php echo $is_edit ? '💾 Update User' : '✨ Create User'; ?>
            </button>
            
            <a href="admin.php?view=users" class="btn btn-secondary btn-lg btn-rounded btn-block" style="padding: 16px; margin-top: 12px;">
                <span class="glyphicon glyphicon-arrow-left"></span> Back to Admin Panel
            </a>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script>
$(document).ready(function() {
    // Password strength indicator
    $('#passwordInput').on('input', function() {
        var password = $(this).val();
        if (password.length > 0) {
            $('#passwordStrength, #passwordStrengthText').show();
            var strength = 0;
            var text = '';
            var color = '';
            
            if (password.length >= 8) strength += 25;
            if (password.match(/[a-z]+/)) strength += 25;
            if (password.match(/[A-Z]+/)) strength += 25;
            if (password.match(/[0-9]+/)) strength += 25;
            
            if (strength <= 25) {
                text = 'Weak';
                color = '#ef4444';
            } else if (strength <= 50) {
                text = 'Fair';
                color = '#f59e0b';
            } else if (strength <= 75) {
                text = 'Good';
                color = '#10b981';
            } else {
                text = 'Strong';
                color = '#06b6d4';
            }
            
            $('#passwordStrengthBar').css({
                'width': strength + '%',
                'background': color
            });
            $('#passwordStrengthText').text(text).css('color', color);
        } else {
            $('#passwordStrength, #passwordStrengthText').hide();
        }
    });
    
    // Role badge update
    $('#roleSelect').on('change', function() {
        var role = $(this).val();
        var badge = $('#roleBadge');
        badge.removeClass('admin user').addClass(role);
        badge.text(role.toUpperCase());
    });
    
    // Custom Role Dropdown
    var $customSelect = $('#roleCustomSelect');
    var $dropdown = $('#roleDropdown');
    var $hiddenSelect = $('#roleSelect');
    
    $customSelect.on('click', function(e) {
        e.stopPropagation();
        $dropdown.toggleClass('open');
        $customSelect.toggleClass('open');
    });
    
    $('.role-option').on('click', function() {
        var value = $(this).data('value');
        var text = $(this).text();
        
        $hiddenSelect.val(value).trigger('change');
        $dropdown.removeClass('open');
        $customSelect.removeClass('open');
    });
    
    $(document).on('click', function() {
        $dropdown.removeClass('open');
        $customSelect.removeClass('open');
    });
    
    // Form validation
    $('#userForm').on('submit', function(e) {
        var username = $('input[name="username"]').val().trim();
        var email = $('input[name="email"]').val().trim();
        var password = $('input[name="password"]').val();
        
        if (username.length < 3) {
            e.preventDefault();
            alert('Username must be at least 3 characters long');
            return false;
        }
        
        <?php if (!$is_edit): ?>
        if (password.length < 6) {
            e.preventDefault();
            alert('Password must be at least 6 characters long');
            return false;
        }
        <?php endif; ?>
    });
});
</script>
</body>
</html>
