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

$team_id = isset($_GET['team_id']) ? (int)$_GET['team_id'] : 0;
// Verify user is leader of this team
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM teams WHERE id = ? AND leader_id = ?");
$stmt->bind_param("ii", $team_id, $user_id);
$stmt->execute();
$check = $stmt->get_result();
if ($check->num_rows == 0) {
    die("Access denied.");
}
$stmt->close();

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Invalid request. Please try again.";
    } else {
        $title = sanitize($conn, trim($_POST['title']));
        $description = sanitize($conn, trim($_POST['description']));
        $assigned_to = !empty($_POST['assigned_to']) ? (int)$_POST['assigned_to'] : null;
        $start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
        $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
        
        // Validate dates
        if ($start_date && $end_date && strtotime($end_date) < strtotime($start_date)) {
            $error = "End date cannot be before start date.";
        } else {
            $stmt = $conn->prepare("INSERT INTO tasks (team_id, title, description, assigned_to, start_date, end_date) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ississ", $team_id, $title, $description, $assigned_to, $start_date, $end_date);
            if ($stmt->execute()) {
                redirect('../team/team_details.php?id=' . $team_id);
            } else {
                $error = "Error creating task. Please try again.";
            }
            $stmt->close();
        }
    }
}

// Get team members for assignment
$stmt = $conn->prepare("SELECT u.id, u.username FROM users u JOIN team_members tm ON u.id = tm.user_id WHERE tm.team_id = ?");
$stmt->bind_param("i", $team_id);
$stmt->execute();
$members = $stmt->get_result();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Task - TaskHive</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/components.css">
    <link rel="stylesheet" href="../assets/css/pages/task.css">
    <script src="../assets/js/main.js"></script>
</head>
<body class="page-body">
<?php render_unified_navbar(th_nav_template('user', [
    'links' => [
        ['label' => 'Dashboard', 'href' => '../user/dashboard.php', 'icon' => 'glyphicon glyphicon-home', 'slug' => 'dashboard'],
        ['label' => 'Team Overview', 'href' => '../team/team_details.php?id=' . $team_id, 'icon' => 'glyphicon glyphicon-eye-open', 'slug' => 'team-overview'],
        ['label' => 'Team Tasks', 'href' => '../task/team_tasks.php?id=' . $team_id, 'icon' => 'glyphicon glyphicon-tasks', 'slug' => 'team-tasks'],
        ['label' => 'Create Task', 'href' => '../task/create_task.php?team_id=' . $team_id, 'icon' => 'glyphicon glyphicon-plus', 'slug' => 'create-task'],
    ],
    'active' => 'create-task',
])); ?>

<!-- Page Header -->
<div class="page-header-gradient">
    <div class="container">
        <h1><span class="glyphicon glyphicon-plus"></span> Create New Task</h1>
        <p class="page-header-subtitle">Assign work to your team members</p>
    </div>
</div>

<div class="container">
    <div class="row">
        <div class="col-md-8 col-md-offset-2">
            <div class="form-card animate-fadeInUp">
                <?php if(isset($error)): ?>
                    <div class="alert alert-danger">
                        <span class="glyphicon glyphicon-exclamation-sign"></span> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    
                    <div class="form-group">
                        <label><span class="glyphicon glyphicon-tag"></span> Task Title</label>
                        <input type="text" name="title" class="form-control" placeholder="e.g., Build Login System" required>
                    </div>
                    
                    <div class="form-group">
                        <label><span class="glyphicon glyphicon-align-left"></span> Description</label>
                        <textarea name="description" class="form-control" rows="5" placeholder="Describe the task requirements..."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label><span class="glyphicon glyphicon-user"></span> Assign To (Optional)</label>
                        <select name="assigned_to" style="width:100%; height:48px; padding:12px; border:2px solid #e5e7eb; border-radius:12px; font-size:15px; color:#333; background:#fff;">
                            <option value="">-- Anyone can work on this --</option>
                            <?php while($m = $members->fetch_assoc()): ?>
                                <option value="<?php echo $m['id']; ?>"><?php echo htmlspecialchars($m['username']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><span class="glyphicon glyphicon-calendar"></span> Start Date</label>
                                <input type="datetime-local" name="start_date" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><span class="glyphicon glyphicon-time"></span> End Date</label>
                                <input type="datetime-local" name="end_date" class="form-control">
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-success btn-block btn-lg btn-rounded form-submit-btn">
                        <span class="glyphicon glyphicon-ok"></span> Create Task
                    </button>
                    
                    <div class="form-footer-link">
                        <a href="../team/team_details.php?id=<?php echo $team_id; ?>">
                            <span class="glyphicon glyphicon-arrow-left"></span> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
</body>
</html>
