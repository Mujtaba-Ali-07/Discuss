<div class="container mt-4">
    <div class="row">
        <div class="col-md-4">
            <!-- Profile Card -->
            <div class="card">
                <div class="card-body text-center">
                    <?php
                    include './common/db.php';
                    $user_email = $_SESSION['user']['useremail'];
                    $stmt = $conn->prepare("SELECT username, useremail, profile_picture FROM users WHERE useremail = ?");
                    $stmt->bind_param("s", $user_email);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $user_data = $result->fetch_assoc();

                    $profile_picture = !empty($user_data['profile_picture'])
                        ? "./public/uploads/" . $user_data['profile_picture']
                        : "./public/default-avatar.png";
                    ?>

                    <img src="<?php echo $profile_picture; ?>"
                        class="rounded-circle mb-3"
                        alt="Profile Picture"
                        style="width: 150px; height: 150px; object-fit: cover;">

                    <h4><?php echo htmlspecialchars($user_data['username']); ?></h4>
                    <p class="text-muted"><?php echo htmlspecialchars($user_data['useremail']); ?></p>

                    <!-- Profile Picture Upload Form -->
                    <form method="post" action="./server/requests.php" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="profile_picture" class="form-label">Change Profile Picture</label>
                            <input class="form-control" type="file" name="profile_picture" id="profile_picture" accept="image/*" required>
                        </div>
                        <button type="submit" name="upload_profile_picture" class="btn btn-primary btn-sm">
                            Upload Picture
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <!-- User's Questions -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">My Questions</h5>
                </div>
                <div class="card-body">
                    <?php
                    // Get user's questions
                    $user_id_stmt = $conn->prepare("SELECT id FROM users WHERE useremail = ?");
                    $user_id_stmt->bind_param("s", $user_email);
                    $user_id_stmt->execute();
                    $user_result = $user_id_stmt->get_result();
                    $user_row = $user_result->fetch_assoc();
                    $user_id = $user_row['id'];

                    $questions_query = "
                SELECT q.*, 
                       COUNT(DISTINCT ql.id) as like_count,
                       COUNT(DISTINCT c.id) as comment_count
                FROM questions q 
                LEFT JOIN question_likes ql ON q.id = ql.question_id 
                LEFT JOIN comments c ON q.id = c.question_id 
                WHERE q.user_id = ?
                GROUP BY q.id 
                ORDER BY q.created_at DESC
            ";

                    $stmt = $conn->prepare($questions_query);
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    if ($result->num_rows > 0) {
                        while ($question = $result->fetch_assoc()) {
                            echo "
                    <div class='border-bottom pb-3 mb-3'>
                        <div class='d-flex justify-content-between align-items-start'>
                            <div class='flex-grow-1'>
                                <h6>" . htmlspecialchars($question['title']) . "</h6>
                                <p class='text-muted small mb-2'>" . htmlspecialchars(substr($question['description'], 0, 150)) . "...</p>
                                <div class='d-flex gap-3 text-muted small mb-2'>
                                    <span>👍 " . $question['like_count'] . " likes</span>
                                    <span>💬 " . $question['comment_count'] . " comments</span>
                                    <span>📅 " . date('M j, Y', strtotime($question['created_at'])) . "</span>
                                </div>
                                
                                <!-- Comments Section -->
                                <div class='mt-3'>";

                            // Fetch comments for this specific question
                            $comments_query = "
                        SELECT c.*, u.username, u.profile_picture 
                        FROM comments c 
                        LEFT JOIN users u ON c.user_id = u.id 
                        WHERE c.question_id = ? 
                        ORDER BY c.created_at ASC
                    ";
                            $comments_stmt = $conn->prepare($comments_query);
                            $comments_stmt->bind_param("i", $question['id']);
                            $comments_stmt->execute();
                            $comments_result = $comments_stmt->get_result();

                            if ($comments_result->num_rows > 0) {
                                echo "<h6 class='mb-2'>Comments on this question:</h6>";
                                echo "<div class='comments-container' style='max-height: 200px; overflow-y: auto;'>";

                                while ($comment = $comments_result->fetch_assoc()) {
                                    $commenter_profile_pic = !empty($comment['profile_picture'])
                                        ? "./public/uploads/" . $comment['profile_picture']
                                        : "./public/default-avatar.png";

                                    echo "
                            <div class='border-start border-primary ps-3 mb-2'>
                                <div class='d-flex align-items-center mb-1'>
                                    <img src='" . $commenter_profile_pic . "' 
                                         class='rounded-circle me-2' 
                                         alt='Commenter' 
                                         style='width: 20px; height: 20px; object-fit: cover;'>
                                    <strong class='small'>" . htmlspecialchars($comment['username']) . "</strong>
                                    <small class='text-muted ms-2'>" . date('M j, Y g:i A', strtotime($comment['created_at'])) . "</small>
                                </div>
                                <p class='small mb-1'>" . htmlspecialchars($comment['comment_text']) . "</p>
                            </div>";
                                }
                                echo "</div>"; // Close comments-container
                            } else {
                                echo "<p class='small text-muted'>No comments yet on this question.</p>";
                            }

                            echo "
                                </div>
                            </div>
                            <form method='post' action='./server/requests.php' class='ms-3'>
                                <input type='hidden' name='question_id' value='" . $question['id'] . "'>
                                <button type='submit' name='delete_question' class='btn btn-danger btn-sm' 
                                        onclick='return confirm(\"Are you sure you want to delete this question? All comments and likes will also be deleted.\")'>
                                    🗑️
                                </button>
                            </form>
                        </div>
                    </div>";
                        }
                    } else {
                        echo "<p class='text-muted'>You haven't asked any questions yet.</p>";
                        echo "<a href='?ask=true' class='btn btn-primary'>Ask Your First Question</a>";
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>