<?php
require_once '../config/includes/db_connect.php';
require_once '../config/includes/functions.php';
require_once '../config/includes/navbar.php';

// Hide SQL errors from users
error_reporting(0);
mysqli_report(MYSQLI_REPORT_OFF);

if (!isLoggedIn() || !isAdmin()) {
    redirect('../auth/login.php');
}

$view = isset($_GET['view']) ? $_GET['view'] : 'users';

// Verify CSRF token for delete operations
$csrf_valid = isset($_GET['token']) && verify_csrf_token($_GET['token']);

// Handle Delete User
if (isset($_GET['delete']) && $view == 'users' && $csrf_valid) {
    $id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    redirect('admin.php');
}

// Handle Delete Team
if (isset($_GET['delete_team']) && $csrf_valid) {
    $id = (int)$_GET['delete_team'];
    // Delete cascade handled by DB
    $stmt = $conn->prepare("DELETE FROM teams WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    redirect('admin.php?view=teams');
}

// Handle Delete Task
if (isset($_GET['delete_task']) && $csrf_valid) {
    $id = (int)$_GET['delete_task'];
    $stmt = $conn->prepare("DELETE FROM tasks WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    redirect('admin.php?view=tasks');
}

// Get search parameter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

if ($view == 'users') {
    // Get sorting parameters
    $sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'created_at';
    $order = isset($_GET['order']) ? $_GET['order'] : 'DESC';
    
    // Validate sort column
    $allowed_sorts = ['id', 'username', 'email', 'role', 'created_at'];
    if (!in_array($sort_by, $allowed_sorts)) {
        $sort_by = 'created_at';
    }
    
    // Validate order
    $order = ($order == 'ASC') ? 'ASC' : 'DESC';
    
    // Build query with search
    $query = "SELECT * FROM users";
    if (!empty($search)) {
        $search_term = $conn->real_escape_string($search);
        $query .= " WHERE id LIKE '%$search_term%' OR username LIKE '%$search_term%' OR email LIKE '%$search_term%' OR first_name LIKE '%$search_term%' OR last_name LIKE '%$search_term%' OR role LIKE '%$search_term%'";
    }
    $query .= " ORDER BY $sort_by $order";
    $users = $conn->query($query);
    
} elseif ($view == 'teams') {
    // Get sorting parameters
    $sort_by = isset($_GET['sort']) ? $_GET['sort'] : 't.created_at';
    $order = isset($_GET['order']) ? $_GET['order'] : 'DESC';
    
    // Validate sort column
    $allowed_sorts = ['t.id', 't.name', 'leader_name', 't.created_at'];
    if (!in_array($sort_by, $allowed_sorts)) {
        $sort_by = 't.created_at';
    }
    
    // Validate order
    $order = ($order == 'ASC') ? 'ASC' : 'DESC';
    
    // Build query with search
    $query = "SELECT t.*, u.username as leader_name FROM teams t JOIN users u ON t.leader_id = u.id";
    if (!empty($search)) {
        $search_term = $conn->real_escape_string($search);
        $query .= " WHERE t.id LIKE '%$search_term%' OR t.name LIKE '%$search_term%' OR t.description LIKE '%$search_term%' OR u.username LIKE '%$search_term%'";
    }
    $query .= " ORDER BY $sort_by $order";
    $teams = $conn->query($query);
    
} elseif ($view == 'tasks') {
    // Get sorting parameters
    $sort_by = isset($_GET['sort']) ? $_GET['sort'] : 't.created_at';
    $order = isset($_GET['order']) ? $_GET['order'] : 'DESC';
    
    // Validate sort column
    $allowed_sorts = ['t.id', 't.title', 'team_name', 't.status', 't.created_at'];
    if (!in_array($sort_by, $allowed_sorts)) {
        $sort_by = 't.created_at';
    }
    
    // Validate order
    $order = ($order == 'ASC') ? 'ASC' : 'DESC';
    
    // Build query with search
    $query = "SELECT t.*, tm.name as team_name, u.username as assigned_name FROM tasks t LEFT JOIN teams tm ON t.team_id = tm.id LEFT JOIN users u ON t.assigned_to = u.id";
    if (!empty($search)) {
        $search_term = $conn->real_escape_string($search);
        $query .= " WHERE t.id LIKE '%$search_term%' OR t.title LIKE '%$search_term%' OR t.description LIKE '%$search_term%' OR tm.name LIKE '%$search_term%' OR u.username LIKE '%$search_term%' OR t.status LIKE '%$search_term%'";
    }
    $query .= " ORDER BY $sort_by $order";
    $tasks = $conn->query($query);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - TaskHive</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/components.css">
    <link rel="stylesheet" href="../assets/css/pages/admin.css">
</head>
<body class="admin-body">
<?php
$adminNav = th_nav_template('admin');
if ($view === 'teams') {
    $adminNav['active'] = 'teams';
} elseif ($view === 'tasks') {
    $adminNav['active'] = 'tasks';
} else {
    $adminNav['active'] = 'users';
}
render_unified_navbar($adminNav);
?>

<div class="container admin-main-container">
    <div class="user-table-container animate-fadeIn">
        <div class="user-table-header">
            <div class="row">
                <div class="col-md-6">
                    <h3 style="margin: 0 0 8px 0;">
                        <?php if($view == 'users'): ?>
                            <span class="glyphicon glyphicon-user"></span> User Management
                        <?php elseif($view == 'teams'): ?>
                            <span class="glyphicon glyphicon-flag"></span> Team Management
                        <?php else: ?>
                            <span class="glyphicon glyphicon-tasks"></span> Task Management
                        <?php endif; ?>
                    </h3>
                    <small style="opacity: 0.9;">
                        <?php if($view == 'users'): ?>
                            Manage all system users
                        <?php elseif($view == 'teams'): ?>
                            Manage all teams
                        <?php else: ?>
                            Manage all tasks
                        <?php endif; ?>
                    </small>
                </div>
                <div class="col-md-6 text-right">
                    <?php if($view == 'users'): ?>
                    <a href="admin_user_form.php" class="btn btn-lg btn-rounded" style="background: white; color: var(--primary); font-weight: 700; padding: 12px 28px;">
                        <span class="glyphicon glyphicon-plus"></span> Add New User
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Search Box -->
        <div class="search-box">
            <form method="GET" action="admin.php" class="form-inline search-form-responsive">
                <input type="hidden" name="view" value="<?php echo htmlspecialchars($view); ?>">
                <?php if(isset($_GET['sort'])): ?>
                    <input type="hidden" name="sort" value="<?php echo htmlspecialchars($_GET['sort']); ?>">
                <?php endif; ?>
                <?php if(isset($_GET['order'])): ?>
                    <input type="hidden" name="order" value="<?php echo htmlspecialchars($_GET['order']); ?>">
                <?php endif; ?>
                <div class="input-group search-input-group">
                    <input type="text" name="search" class="form-control search-input" placeholder="🔍 Search by ID, name, email..." value="<?php echo htmlspecialchars($search); ?>">
                    <span class="input-group-btn">
                        <button type="submit" class="btn btn-primary btn-search">
                            <span class="glyphicon glyphicon-search"></span><span class="search-btn-text"> Search</span>
                        </button>
                    </span>
                </div>
                <?php if(!empty($search)): ?>
                    <a href="admin.php?view=<?php echo $view; ?>" class="btn btn-secondary btn-rounded btn-clear-search">
                        <span class="glyphicon glyphicon-remove"></span><span class="clear-btn-text"> Clear</span>
                    </a>
                <?php endif; ?>
            </form>
        </div>
        
        <?php
        // Helper functions for sorting
        function getSortClass($column, $current_sort, $current_order) {
            if ($column == $current_sort) {
                return $current_order == 'ASC' ? 'sorted-asc' : 'sorted-desc';
            }
            return 'sortable';
        }
        
        function getSortUrl($column, $current_sort, $next_order, $search_param) {
            $order = ($current_sort == $column) ? $next_order : 'ASC';
            return "admin.php?sort=$column&order=$order$search_param";
        }
        ?>
        
        <div class="table-responsive">
            <?php if($view == 'users'): ?>
            <!-- Users Table -->
            <?php
            $current_sort = isset($_GET['sort']) ? $_GET['sort'] : 'created_at';
            $current_order = isset($_GET['order']) ? $_GET['order'] : 'DESC';
            $next_order = ($current_order == 'ASC') ? 'DESC' : 'ASC';
            $search_param = !empty($search) ? '&search=' . urlencode($search) : '';
            ?>
            <table class="table table-hover" style="margin: 0;">
                <thead style="background: #f8f9fa;">
                    <tr>
                        <th style="padding: 15px;" class="<?php echo getSortClass('id', $current_sort, $current_order); ?>" onclick="window.location.href='<?php echo getSortUrl('id', $current_sort, $next_order, $search_param); ?>'">ID</th>
                        <th class="<?php echo getSortClass('username', $current_sort, $current_order); ?>" onclick="window.location.href='<?php echo getSortUrl('username', $current_sort, $next_order, $search_param); ?>'">User</th>
                        <th class="<?php echo getSortClass('email', $current_sort, $current_order); ?>" onclick="window.location.href='<?php echo getSortUrl('email', $current_sort, $next_order, $search_param); ?>'">Email</th>
                        <th class="<?php echo getSortClass('role', $current_sort, $current_order); ?>" onclick="window.location.href='<?php echo getSortUrl('role', $current_sort, $next_order, $search_param); ?>'">Role</th>
                        <th class="<?php echo getSortClass('created_at', $current_sort, $current_order); ?>" onclick="window.location.href='<?php echo getSortUrl('created_at', $current_sort, $next_order, $search_param); ?>'">Joined</th>
                        <th style="cursor: default;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                            <?php while($row = $users->fetch_assoc()): ?>
                            <tr style="border-left: 3px solid <?php echo $row['role'] == 'admin' ? '#d9534f' : '#5bc0de'; ?>;">
                                <td style="padding: 15px; vertical-align: middle;"><strong>#<?php echo $row['id']; ?></strong></td>
                                <td style="vertical-align: middle;">
                                    <?php if(isset($row['profile_pic']) && $row['profile_pic']): ?>
                                        <img src="<?php echo htmlspecialchars($row['profile_pic']); ?>" alt="pic" class="user-avatar">
                                    <?php else: ?>
                                        <div class="user-placeholder"><?php echo strtoupper(htmlspecialchars(substr($row['username'], 0, 1))); ?></div>
                                    <?php endif; ?>
                                    <strong><?php echo htmlspecialchars($row['username']); ?></strong>
                                    <?php if(isset($row['first_name'])): ?>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td style="vertical-align: middle;"><?php echo htmlspecialchars($row['email']); ?></td>
                                <td style="vertical-align: middle;">
                                    <span class="label label-<?php echo $row['role'] == 'admin' ? 'danger' : 'info'; ?>" style="padding: 6px 12px; font-size: 12px;">
                                        <?php echo strtoupper($row['role']); ?>
                                    </span>
                                </td>
                                <td style="vertical-align: middle;">
                                    <small class="text-muted">
                                        <span class="glyphicon glyphicon-calendar"></span>
                                        <?php echo date('M d, Y', strtotime($row['created_at'])); ?>
                                    </small>
                                </td>
                                <td class="table-actions" style="vertical-align: middle;">
                                    <a href="admin_user_form.php?id=<?php echo $row['id']; ?>" class="btn btn-warning btn-sm">
                                        <span class="glyphicon glyphicon-edit"></span> Edit
                                    </a>
                                    <a href="admin.php?delete=<?php echo $row['id']; ?>&token=<?php echo get_csrf_token(); ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this user?')">
                                        <span class="glyphicon glyphicon-trash"></span> Delete
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
            
            <?php elseif($view == 'teams'): ?>
            <!-- Teams Table -->
            <?php
            $current_sort = isset($_GET['sort']) ? $_GET['sort'] : 't.created_at';
            $current_order = isset($_GET['order']) ? $_GET['order'] : 'DESC';
            $next_order = ($current_order == 'ASC') ? 'DESC' : 'ASC';
            $search_param = !empty($search) ? '&search=' . urlencode($search) : '';
            ?>
            <table class="table table-hover" style="margin: 0;">
                <thead style="background: #f8f9fa;">
                    <tr>
                        <th style="padding: 15px;" class="<?php echo getSortClass('t.id', $current_sort, $current_order); ?>" onclick="window.location.href='admin.php?view=teams&sort=t.id&order=<?php echo ($current_sort == 't.id') ? $next_order : 'ASC'; ?><?php echo $search_param; ?>'">ID</th>
                        <th class="<?php echo getSortClass('t.name', $current_sort, $current_order); ?>" onclick="window.location.href='admin.php?view=teams&sort=t.name&order=<?php echo ($current_sort == 't.name') ? $next_order : 'ASC'; ?><?php echo $search_param; ?>'">Team Name</th>
                        <th class="<?php echo getSortClass('leader_name', $current_sort, $current_order); ?>" onclick="window.location.href='admin.php?view=teams&sort=leader_name&order=<?php echo ($current_sort == 'leader_name') ? $next_order : 'ASC'; ?><?php echo $search_param; ?>'">Leader</th>
                        <th class="<?php echo getSortClass('t.created_at', $current_sort, $current_order); ?>" onclick="window.location.href='admin.php?view=teams&sort=t.created_at&order=<?php echo ($current_sort == 't.created_at') ? $next_order : 'ASC'; ?><?php echo $search_param; ?>'">Created</th>
                        <th style="cursor: default;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($teams && $teams->num_rows > 0): ?>
                        <?php while($row = $teams->fetch_assoc()): ?>
                        <tr style="border-left: 3px solid #4A90E2;">
                            <td style="padding: 15px; vertical-align: middle;"><strong>#<?php echo $row['id']; ?></strong></td>
                        <td style="vertical-align: middle;">
                            <strong><?php echo htmlspecialchars($row['name']); ?></strong>
                        </td>
                        <td style="vertical-align: middle;">
                            <span class="glyphicon glyphicon-user"></span> <?php echo htmlspecialchars($row['leader_name']); ?>
                        </td>
                        <td style="vertical-align: middle;">
                            <small class="text-muted">
                                <span class="glyphicon glyphicon-calendar"></span>
                                <?php echo date('M d, Y', strtotime($row['created_at'])); ?>
                            </small>
                        </td>
                        <td class="table-actions" style="vertical-align: middle;">
                            <a href="../team/edit_team.php?id=<?php echo $row['id']; ?>" class="btn btn-warning btn-sm">
                                <span class="glyphicon glyphicon-edit"></span> Edit
                            </a>
                            <a href="admin.php?delete_team=<?php echo $row['id']; ?>&view=teams&token=<?php echo get_csrf_token(); ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this team? This will also delete all tasks and submissions!')">
                                <span class="glyphicon glyphicon-trash"></span> Delete
                            </a>
                        </td>
                    </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 40px;">
                                <div style="color: #999; font-size: 16px;">
                                    <span class="glyphicon glyphicon-inbox" style="font-size: 48px; display: block; margin-bottom: 15px;"></span>
                                    <strong>No teams found</strong>
                                    <p>No teams match your search criteria.</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <?php elseif($view == 'tasks'): ?>
            <!-- Tasks Table -->
            <?php
            $current_sort = isset($_GET['sort']) ? $_GET['sort'] : 't.created_at';
            $current_order = isset($_GET['order']) ? $_GET['order'] : 'DESC';
            $next_order = ($current_order == 'ASC') ? 'DESC' : 'ASC';
            $search_param = !empty($search) ? '&search=' . urlencode($search) : '';
            ?>
            <table class="table table-hover" style="margin: 0;">
                <thead style="background: #f8f9fa;">
                    <tr>
                        <th style="padding: 15px;" class="<?php echo getSortClass('t.id', $current_sort, $current_order); ?>" onclick="window.location.href='admin.php?view=tasks&sort=t.id&order=<?php echo ($current_sort == 't.id') ? $next_order : 'ASC'; ?><?php echo $search_param; ?>'">ID</th>
                        <th class="<?php echo getSortClass('t.title', $current_sort, $current_order); ?>" onclick="window.location.href='admin.php?view=tasks&sort=t.title&order=<?php echo ($current_sort == 't.title') ? $next_order : 'ASC'; ?><?php echo $search_param; ?>'">Task Title</th>
                        <th class="<?php echo getSortClass('team_name', $current_sort, $current_order); ?>" onclick="window.location.href='admin.php?view=tasks&sort=team_name&order=<?php echo ($current_sort == 'team_name') ? $next_order : 'ASC'; ?><?php echo $search_param; ?>'">Team</th>
                        <th style="cursor: default;">Assigned To</th>
                        <th class="<?php echo getSortClass('t.status', $current_sort, $current_order); ?>" onclick="window.location.href='admin.php?view=tasks&sort=t.status&order=<?php echo ($current_sort == 't.status') ? $next_order : 'ASC'; ?><?php echo $search_param; ?>'">Status</th>
                        <th class="<?php echo getSortClass('t.created_at', $current_sort, $current_order); ?>" onclick="window.location.href='admin.php?view=tasks&sort=t.created_at&order=<?php echo ($current_sort == 't.created_at') ? $next_order : 'ASC'; ?><?php echo $search_param; ?>'">Created</th>
                        <th style="cursor: default;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($tasks && $tasks->num_rows > 0): ?>
                        <?php while($row = $tasks->fetch_assoc()): ?>
                        <tr style="border-left: 3px solid <?php 
                            echo $row['status'] == 'approved' ? '#5cb85c' : 
                                ($row['status'] == 'rejected' ? '#d9534f' : 
                                ($row['status'] == 'submitted' ? '#5bc0de' : '#f0ad4e')); 
                        ?>;">
                            <td style="padding: 15px; vertical-align: middle;"><strong>#<?php echo $row['id']; ?></strong></td>
                        <td style="vertical-align: middle;">
                            <strong><?php echo htmlspecialchars($row['title']); ?></strong>
                            <?php if ($row['start_date'] || $row['end_date']): ?>
                            <br>
                            <small style="background: #e3f2fd; padding: 3px 8px; border-radius: 3px; display: inline-block; margin-top: 5px;">
                                <?php if ($row['start_date']): ?>
                                    <span class="glyphicon glyphicon-play-circle" style="color: #4caf50; font-size: 10px;"></span>
                                    <?php echo date('M d, Y', strtotime($row['start_date'])); ?>
                                <?php endif; ?>
                                <?php if ($row['start_date'] && $row['end_date']): ?>
                                    →
                                <?php endif; ?>
                                <?php if ($row['end_date']): ?>
                                    <span class="glyphicon glyphicon-flag" style="color: #f44336; font-size: 10px;"></span>
                                    <?php echo date('M d, Y', strtotime($row['end_date'])); ?>
                                <?php endif; ?>
                            </small>
                            <?php endif; ?>
                        </td>
                        <td style="vertical-align: middle;">
                            <span class="glyphicon glyphicon-flag"></span> <?php echo htmlspecialchars($row['team_name']); ?>
                        </td>
                        <td style="vertical-align: middle;">
                            <?php echo $row['assigned_name'] ? htmlspecialchars($row['assigned_name']) : '<em class="text-muted">Anyone</em>'; ?>
                        </td>
                        <td style="vertical-align: middle;">
                            <span class="label label-<?php 
                                echo $row['status'] == 'approved' ? 'success' : 
                                    ($row['status'] == 'rejected' ? 'danger' : 
                                    ($row['status'] == 'submitted' ? 'info' : 'warning')); 
                            ?>" style="padding: 6px 12px; font-size: 12px;">
                                <?php echo strtoupper($row['status']); ?>
                            </span>
                        </td>
                        <td style="vertical-align: middle;">
                            <small class="text-muted">
                                <span class="glyphicon glyphicon-calendar"></span>
                                <?php echo date('M d, Y', strtotime($row['created_at'])); ?>
                            </small>
                        </td>
                        <td class="table-actions" style="vertical-align: middle;">
                            <a href="../task/edit_task.php?id=<?php echo $row['id']; ?>" class="btn btn-warning btn-sm">
                                <span class="glyphicon glyphicon-edit"></span> Edit
                            </a>
                            <a href="admin.php?delete_task=<?php echo $row['id']; ?>&view=tasks&token=<?php echo get_csrf_token(); ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this task? This will also delete all submissions!')">
                                <span class="glyphicon glyphicon-trash"></span> Delete
                            </a>
                        </td>
                    </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 40px;">
                                <div style="color: #999; font-size: 16px;">
                                    <span class="glyphicon glyphicon-inbox" style="font-size: 48px; display: block; margin-bottom: 15px;"></span>
                                    <strong>No tasks found</strong>
                                    <p>No tasks match your search criteria.</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
</div>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
<script src="../assets/js/main.js"></script>
</body>
</html>
