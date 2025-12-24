<?php
// Start session (if not already started)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include './common/db.php';

// Check if user is logged in
if (!isset($_SESSION['user']['useremail'])) {
    header("Location: /discuss?login=true");
    exit();
}

// Get user data and check if admin
$user_email = $_SESSION['user']['useremail'];
$stmt = $conn->prepare("SELECT id, username, is_admin FROM users WHERE useremail = ?");
$stmt->bind_param("s", $user_email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Check if user is admin
if (!$user || $user['is_admin'] != 1) {
    echo "<div class='container mt-5'>";
    echo "<div class='alert alert-danger text-center'>";
    echo "<h4>🚫 Access Denied</h4>";
    echo "<p>You don't have permission to access the admin panel.</p>";
    echo "<a href='./' class='btn btn-primary mt-2'>Go Home</a>";
    echo "</div>";
    echo "</div>";
    exit();
}

// First, let's check if we need to add the created_at column
$check_column = $conn->query("SHOW COLUMNS FROM users LIKE 'created_at'");
if ($check_column->num_rows == 0) {
    // Add created_at column if it doesn't exist
    $conn->query("ALTER TABLE users ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
}
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2>👑 Admin Dashboard</h2>
            <p class="text-muted mb-0">Welcome back, <?php echo htmlspecialchars($user['username']); ?> (Admin)</p>
        </div>
        <div>
            <a href="./" class="btn btn-outline-primary">← Back to Site</a>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title">Total Users</h5>
                            <?php
                            $total_users = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
                            ?>
                            <h2><?php echo $total_users; ?></h2>
                        </div>
                        <span class="display-4">👥</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title">Total Questions</h5>
                            <?php
                            $total_questions = $conn->query("SELECT COUNT(*) as count FROM questions")->fetch_assoc()['count'];
                            ?>
                            <h2><?php echo $total_questions; ?></h2>
                        </div>
                        <span class="display-4">❓</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title">Total Comments</h5>
                            <?php
                            $total_comments = $conn->query("SELECT COUNT(*) as count FROM comments")->fetch_assoc()['count'];
                            ?>
                            <h2><?php echo $total_comments; ?></h2>
                        </div>
                        <span class="display-4">💬</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-dark">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title">Feedback</h5>
                            <?php
                            $total_feedback = $conn->query("SELECT COUNT(*) as count FROM feedback")->fetch_assoc()['count'];
                            ?>
                            <h2><?php echo $total_feedback; ?></h2>
                        </div>
                        <span class="display-4">📝</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs Navigation -->
    <ul class="nav nav-tabs mb-4" id="adminTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="users-tab" data-bs-toggle="tab" data-bs-target="#users" type="button">
                👥 Users
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="questions-tab" data-bs-toggle="tab" data-bs-target="#questions" type="button">
                ❓ Questions
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="feedback-tab" data-bs-toggle="tab" data-bs-target="#feedback" type="button">
                📝 Feedback
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="settings-tab" data-bs-toggle="tab" data-bs-target="#settings" type="button">
                ⚙️ Settings
            </button>
        </li>
    </ul>

    <!-- Tab Content -->
    <div class="tab-content" id="adminTabContent">
        <!-- Users Tab -->
        <div class="tab-pane fade show active" id="users" role="tabpanel">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Manage Users</h5>
                    <button class="btn btn-sm btn-outline-primary" onclick="refreshUsers()">
                        ↻ Refresh
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Country</th>
                                    <th>Joined</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Check if created_at column exists, if not order by id
                                $check_created_at = $conn->query("SHOW COLUMNS FROM users LIKE 'created_at'");
                                $order_by = ($check_created_at->num_rows > 0) ? "ORDER BY created_at DESC" : "ORDER BY id DESC";
                                
                                $users_query = "SELECT * FROM users $order_by LIMIT 50";
                                $users_result = $conn->query($users_query);
                                
                                if ($users_result->num_rows > 0) {
                                    while ($user_row = $users_result->fetch_assoc()) {
                                ?>
                                <tr>
                                    <td><?php echo $user_row['id']; ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <?php
                                            $profile_pic = !empty($user_row['profile_picture'])
                                                ? "./public/uploads/" . $user_row['profile_picture']
                                                : "./public/default-avatar.png";
                                            ?>
                                            <img src="<?php echo $profile_pic; ?>" 
                                                 class="rounded-circle me-2" 
                                                 style="width: 30px; height: 30px; object-fit: cover;">
                                            <?php echo htmlspecialchars($user_row['username']); ?>
                                            <?php if ($user_row['is_admin'] == 1): ?>
                                                <span class="badge bg-primary ms-2">Admin</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($user_row['useremail']); ?></td>
                                    <td><?php echo htmlspecialchars($user_row['usercountry']); ?></td>
                                    <td>
                                        <?php 
                                        if (isset($user_row['created_at']) && !empty($user_row['created_at'])) {
                                            echo date('M j, Y', strtotime($user_row['created_at']));
                                        } else {
                                            echo 'N/A';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-success">Active</span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <?php if ($user_row['is_admin'] == 0): ?>
                                                <form method="post" action="./server/requests.php" class="d-inline">
                                                    <input type="hidden" name="user_id" value="<?php echo $user_row['id']; ?>">
                                                    <button type="submit" name="make_admin" class="btn btn-outline-success">
                                                        Make Admin
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            <button type="button" class="btn btn-outline-info" 
                                                    onclick="viewUser(<?php echo $user_row['id']; ?>)">
                                                View
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php
                                    }
                                } else {
                                    echo "<tr><td colspan='7' class='text-center'>No users found</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Questions Tab -->
        <div class="tab-pane fade" id="questions" role="tabpanel">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Manage Questions</h5>
                    <div>
                        <input type="text" class="form-control form-control-sm d-inline-block w-auto" 
                               placeholder="Search questions..." id="questionSearch">
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Title</th>
                                    <th>Asked By</th>
                                    <th>Likes</th>
                                    <th>Comments</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $questions_query = "
                                    SELECT q.*, u.username, 
                                           COUNT(DISTINCT ql.id) as like_count,
                                           COUNT(DISTINCT c.id) as comment_count
                                    FROM questions q 
                                    LEFT JOIN users u ON q.user_id = u.id 
                                    LEFT JOIN question_likes ql ON q.id = ql.question_id 
                                    LEFT JOIN comments c ON q.id = c.question_id 
                                    GROUP BY q.id 
                                    ORDER BY q.created_at DESC 
                                    LIMIT 50
                                ";
                                $questions_result = $conn->query($questions_query);
                                
                                if ($questions_result->num_rows > 0) {
                                    while ($question = $questions_result->fetch_assoc()) {
                                ?>
                                <tr>
                                    <td><?php echo $question['id']; ?></td>
                                    <td>
                                        <a href="#" class="text-decoration-none" 
                                           data-bs-toggle="tooltip" 
                                           title="<?php echo htmlspecialchars($question['description']); ?>">
                                            <?php echo htmlspecialchars(substr($question['title'], 0, 50)); ?>
                                            <?php if (strlen($question['title']) > 50): ?>...<?php endif; ?>
                                        </a>
                                    </td>
                                    <td><?php echo htmlspecialchars($question['username']); ?></td>
                                    <td><?php echo $question['like_count']; ?></td>
                                    <td><?php echo $question['comment_count']; ?></td>
                                    <td><?php echo date('M j, Y', strtotime($question['created_at'])); ?></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="./?question_id=<?php echo $question['id']; ?>" 
                                               class="btn btn-outline-info">View</a>
                                            <form method="post" action="./server/requests.php" class="d-inline">
                                                <input type="hidden" name="question_id" value="<?php echo $question['id']; ?>">
                                                <button type="submit" name="delete_question_admin" 
                                                        class="btn btn-outline-danger"
                                                        onclick="return confirm('Delete this question?')">
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php
                                    }
                                } else {
                                    echo "<tr><td colspan='7' class='text-center'>No questions found</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Feedback Tab -->
        <div class="tab-pane fade" id="feedback" role="tabpanel">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">User Feedback</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php
                        $feedback_query = "
                            SELECT f.*, u.username 
                            FROM feedback f 
                            LEFT JOIN users u ON f.user_id = u.id 
                            ORDER BY f.created_at DESC
                        ";
                        $feedback_result = $conn->query($feedback_query);
                        
                        if ($feedback_result->num_rows > 0) {
                            while ($feedback = $feedback_result->fetch_assoc()) {
                                $badge_class = [
                                    'suggestion' => 'bg-info',
                                    'bug' => 'bg-danger',
                                    'feature' => 'bg-success'
                                ];
                        ?>
                        <div class="col-md-6 mb-3">
                            <div class="card h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <h6 class="card-title mb-0">
                                                <?php echo htmlspecialchars($feedback['username'] ?: 'Anonymous'); ?>
                                            </h6>
                                            <small class="text-muted">
                                                <?php echo date('M j, Y g:i A', strtotime($feedback['created_at'])); ?>
                                            </small>
                                        </div>
                                        <span class="badge <?php echo $badge_class[$feedback['feedback_type']]; ?>">
                                            <?php echo ucfirst($feedback['feedback_type']); ?>
                                        </span>
                                    </div>
                                    <div class="mb-2">
                                        <?php echo str_repeat('⭐', $feedback['rating']); ?>
                                    </div>
                                    <p class="card-text"><?php echo htmlspecialchars($feedback['feedback_text']); ?></p>
                                </div>
                            </div>
                        </div>
                        <?php
                            }
                        } else {
                            echo "<div class='col-12'><p class='text-center'>No feedback submissions yet.</p></div>";
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Settings Tab -->
        <div class="tab-pane fade" id="settings" role="tabpanel">
            <div class="row">
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">⚙️ System Settings</h5>
                        </div>
                        <div class="card-body">
                            <form method="post" action="./server/requests.php">
                                <div class="mb-3">
                                    <label class="form-label">Site Name</label>
                                    <input type="text" class="form-control" name="site_name" 
                                           value="Discuss Project" disabled>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Maintenance Mode</label>
                                    <select class="form-select" name="maintenance_mode">
                                        <option value="0" selected>Disabled</option>
                                        <option value="1">Enabled</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Max File Upload Size (MB)</label>
                                    <input type="number" class="form-control" name="max_upload_size" 
                                           value="2" min="1" max="10">
                                </div>
                                <button type="submit" name="save_settings" class="btn btn-primary">
                                    Save Settings
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">📊 System Information</h5>
                        </div>
                        <div class="card-body">
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item d-flex justify-content-between">
                                    <span>PHP Version</span>
                                    <span class="text-muted"><?php echo phpversion(); ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between">
                                    <span>MySQL Version</span>
                                    <span class="text-muted"><?php echo $conn->server_info; ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between">
                                    <span>Server Software</span>
                                    <span class="text-muted"><?php echo $_SERVER['SERVER_SOFTWARE']; ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between">
                                    <span>Disk Space</span>
                                    <span class="text-muted">
                                        <?php 
                                        $free = disk_free_space("/");
                                        $total = disk_total_space("/");
                                        echo round(($free / $total) * 100) . '% free';
                                        ?>
                                    </span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- User Details Modal -->
<div class="modal fade" id="userModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">User Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="userDetails">
                <!-- User details will be loaded here -->
            </div>
        </div>
    </div>
</div>

<script>
// Initialize tooltips
var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl);
});

// View user details
function viewUser(userId) {
    fetch(`./server/admin_actions.php?action=get_user&id=${userId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('userDetails').innerHTML = data.html;
                new bootstrap.Modal(document.getElementById('userModal')).show();
            }
        });
}

// Refresh users table
function refreshUsers() {
    location.reload();
}

// Search questions
document.getElementById('questionSearch')?.addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const rows = document.querySelectorAll('#questions table tbody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchTerm) ? '' : 'none';
    });
});
</script>