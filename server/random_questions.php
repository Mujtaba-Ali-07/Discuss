<?php
include './common/db.php';

// Fetch all questions with user info and like count in RANDOM order
$query = "
    SELECT 
        q.*, 
        u.username,
        u.id as user_id,
        COUNT(DISTINCT ql.id) as like_count,
        COUNT(DISTINCT c.id) as comment_count
    FROM questions q 
    LEFT JOIN users u ON q.user_id = u.id 
    LEFT JOIN question_likes ql ON q.id = ql.question_id 
    LEFT JOIN comments c ON q.id = c.question_id 
    GROUP BY q.id 
    ORDER BY RAND()
";

$result = $conn->query($query);

if ($result->num_rows > 0) {
    while ($question = $result->fetch_assoc()) {
        // Get asker's profile picture for this question
        $profile_stmt = $conn->prepare("SELECT profile_picture FROM users WHERE id = ?");
        $profile_stmt->bind_param("i", $question['user_id']);
        $profile_stmt->execute();
        $profile_result = $profile_stmt->get_result();
        $profile_data = $profile_result->fetch_assoc();

        $asker_profile_pic = !empty($profile_data['profile_picture'])
            ? "./public/uploads/" . $profile_data['profile_picture']
            : "./public/default-avatar.png";
?>
        <div class='card mb-3'>
            <div class='card-body'>
                <h5 class='card-title'><?php echo htmlspecialchars($question['title']); ?></h5>
                <p class='card-text'><?php echo htmlspecialchars($question['description']); ?></p>

                <div class='d-flex justify-content-between align-items-center'>
                    <small class='text-muted'>
                        <div class='d-flex align-items-center'>
                            <img src="<?php echo $asker_profile_pic; ?>"
                                class="rounded-circle me-2"
                                alt="User"
                                style="width: 24px; height: 24px; object-fit: cover;">
                            Asked by: <?php echo htmlspecialchars($question['username']); ?> |
                            <?php echo date('M j, Y g:i A', strtotime($question['created_at'])); ?>
                        </div>
                    </small>

                    <div class='btn-group'>
                        <!-- Like Button -->
                        <button type='button' class='btn btn-sm btn-outline-primary like-btn'
                            data-question-id='<?php echo $question['id']; ?>'>
                            <span class="like-icon">👍</span>
                            <span class="like-count"><?php echo $question['like_count']; ?></span>
                        </button>

                        <!-- Comment Button -->
                        <button type='button' class='btn btn-sm btn-outline-secondary comment-toggle-btn'
                            data-bs-toggle='collapse'
                            data-bs-target='#comments<?php echo $question['id']; ?>'>
                            💬 <span class="comment-count"><?php echo $question['comment_count']; ?></span>
                        </button>
                    </div>
                </div>

                <!-- Comments Section -->
                <div class='collapse mt-3' id='comments<?php echo $question['id']; ?>'>
                    <div class='card card-body'>
                        <h6>Comments:</h6>
                        <?php
                        // Fetch comments for this question
                        $comment_query = "
                            SELECT c.*, u.username, u.profile_picture 
                            FROM comments c 
                            LEFT JOIN users u ON c.user_id = u.id 
                            WHERE c.question_id = ? 
                            ORDER BY c.created_at ASC
                        ";
                        $comment_stmt = $conn->prepare($comment_query);
                        $comment_stmt->bind_param("i", $question['id']);
                        $comment_stmt->execute();
                        $comment_result = $comment_stmt->get_result();
                        ?>

                        <div class="comments-container">
                            <?php if ($comment_result->num_rows > 0) {
                                while ($comment = $comment_result->fetch_assoc()) {
                                    $commenter_profile_pic = !empty($comment['profile_picture'])
                                        ? "./public/uploads/" . $comment['profile_picture']
                                        : "./public/default-avatar.png";
                            ?>
                                    <div class='border-bottom pb-2 mb-2'>
                                        <div class='d-flex align-items-center mb-1'>
                                            <img src="<?php echo $commenter_profile_pic; ?>"
                                                class="rounded-circle me-2"
                                                alt="Commenter"
                                                style="width: 20px; height: 20px; object-fit: cover;">
                                            <strong><?php echo htmlspecialchars($comment['username']); ?>:</strong>
                                        </div>
                                        <?php echo htmlspecialchars($comment['comment_text']); ?>
                                        <br><small class='text-muted'><?php echo date('M j, Y g:i A', strtotime($comment['created_at'])); ?></small>
                                    </div>
                            <?php
                                }
                            } else {
                                echo "<p class='text-muted'>No comments yet.</p>";
                            }
                            ?>
                        </div>

                        <!-- Add comment form (only for logged in users) -->
                        <?php if (isset($_SESSION['user']['username'])) { ?>
                            <form method='post' class='mt-3 comment-form' data-question-id='<?php echo $question['id']; ?>'>
                                <div class='input-group'>
                                    <input type='text' name='comment_text' class='form-control comment-input'
                                        placeholder='Add a comment...' required>
                                    <button type='submit' class='btn btn-primary'>Comment</button>
                                </div>
                            </form>
                        <?php } else { ?>
                            <p class='text-muted'>Please <a href='?login=true'>login</a> to comment.</p>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
<?php
    }
} else {
    echo "<div class='alert alert-info'>No questions yet. Be the first to ask a question!</div>";
}

// Close database connection
$conn->close();
?>