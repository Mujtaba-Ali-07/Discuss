<?php
// Check if user is admin (you'll need to add an 'is_admin' column to users table)
session_start();
include '../common/db.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user']['useremail'])) {
    header("Location: /discuss?login=true");
    exit();
}

// Check if user is admin (you need to implement this check)
$user_email = $_SESSION['user']['useremail'];
$stmt = $conn->prepare("SELECT is_admin FROM users WHERE useremail = ?");
$stmt->bind_param("s", $user_email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// if (!$user || $user['is_admin'] != 1) {
//     echo "Access denied. Admin only.";
//     exit();
// }

// Fetch all feedback
$query = "
    SELECT f.*, u.username 
    FROM feedback f 
    LEFT JOIN users u ON f.user_id = u.id 
    ORDER BY f.created_at DESC
";
$result = $conn->query($query);
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>📊 User Feedback</h2>
        <a href="?feedback=true" class="btn btn-outline-primary">Give Feedback</a>
    </div>

    <?php if ($result->num_rows > 0): ?>
        <div class="card">
            <div class="card-header">
                <div class="row">
                    <div class="col-md-3"><strong>User</strong></div>
                    <div class="col-md-2"><strong>Type</strong></div>
                    <div class="col-md-1"><strong>Rating</strong></div>
                    <div class="col-md-4"><strong>Feedback</strong></div>
                    <div class="col-md-2"><strong>Date</strong></div>
                </div>
            </div>
            <div class="list-group list-group-flush">
                <?php while ($feedback = $result->fetch_assoc()): ?>
                    <div class="list-group-item">
                        <div class="row align-items-center">
                            <div class="col-md-3">
                                <?php echo htmlspecialchars($feedback['username'] ?: 'Anonymous'); ?>
                            </div>
                            <div class="col-md-2">
                                <span class="badge 
                                    <?php echo $feedback['feedback_type'] == 'suggestion' ? 'bg-info' : 
                                           ($feedback['feedback_type'] == 'bug' ? 'bg-danger' : 'bg-success'); ?>">
                                    <?php echo ucfirst($feedback['feedback_type']); ?>
                                </span>
                            </div>
                            <div class="col-md-1">
                                <?php echo str_repeat('⭐', $feedback['rating']); ?>
                            </div>
                            <div class="col-md-4">
                                <p class="mb-0 text-truncate" title="<?php echo htmlspecialchars($feedback['feedback_text']); ?>">
                                    <?php echo htmlspecialchars(substr($feedback['feedback_text'], 0, 100)); ?>
                                    <?php if (strlen($feedback['feedback_text']) > 100): ?>...<?php endif; ?>
                                </p>
                            </div>
                            <div class="col-md-2">
                                <small class="text-muted">
                                    <?php echo date('M j, Y', strtotime($feedback['created_at'])); ?>
                                </small>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-info">
            No feedback submissions yet.
        </div>
    <?php endif; ?>
</div>