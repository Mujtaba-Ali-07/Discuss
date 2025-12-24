<?php
include './common/db.php';

if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $search_term = trim($_GET['search']);
    $search_like = "%" . $search_term . "%";

    // Search in questions title and description, and also in comments
    $query = "
        SELECT DISTINCT
            q.*, 
            u.username,
            u.id as user_id,
            COUNT(DISTINCT ql.id) as like_count,
            COUNT(DISTINCT c.id) as comment_count,
            -- Calculate relevance score
            (CASE 
                WHEN q.title LIKE ? THEN 3
                WHEN q.description LIKE ? THEN 2
                WHEN EXISTS (
                    SELECT 1 FROM comments cm 
                    WHERE cm.question_id = q.id AND cm.comment_text LIKE ?
                ) THEN 1
                ELSE 0
            END) as relevance_score
        FROM questions q 
        LEFT JOIN users u ON q.user_id = u.id 
        LEFT JOIN question_likes ql ON q.id = ql.question_id 
        LEFT JOIN comments c ON q.id = c.question_id 
        WHERE q.title LIKE ? OR q.description LIKE ? 
           OR EXISTS (
               SELECT 1 FROM comments cm 
               WHERE cm.question_id = q.id AND cm.comment_text LIKE ?
           )
        GROUP BY q.id 
        ORDER BY relevance_score DESC, q.created_at DESC
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssssss", $search_like, $search_like, $search_like, $search_like, $search_like, $search_like);
    $stmt->execute();
    $result = $stmt->get_result();

    echo "<div class='alert alert-info mb-3'>";
    echo "<h4>Search Results for: \"<strong>" . htmlspecialchars($search_term) . "</strong>\"</h4>";
    echo "<p class='mb-0'>Found " . $result->num_rows . " results</p>";
    echo "</div>";

    if ($result->num_rows > 0) {
        // Display search results
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

            // Highlight search term in results
            $highlighted_title = preg_replace(
                "/(" . preg_quote($search_term, '/') . ")/i",
                "<mark class='bg-warning'>$1</mark>",
                htmlspecialchars($question['title'])
            );

            $highlighted_description = preg_replace(
                "/(" . preg_quote($search_term, '/') . ")/i",
                "<mark class='bg-warning'>$1</mark>",
                htmlspecialchars($question['description'])
            );
?>
            <div class='card mb-3 border-primary'>
                <div class='card-body'>
                    <!-- Relevance Badge -->
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <h5 class='card-title'><?php echo $highlighted_title; ?></h5>
                        <?php if ($question['relevance_score'] > 0): ?>
                            <span class="badge bg-success ms-2">
                                Relevance: <?php echo $question['relevance_score']; ?>/3
                            </span>
                        <?php endif; ?>
                    </div>

                    <p class='card-text'><?php echo $highlighted_description; ?></p>

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
                            <form method='post' action='./server/requests.php' class='d-inline'>
                                <input type='hidden' name='question_id' value='<?php echo $question['id']; ?>'>
                                <button type='button' class='btn btn-sm btn-outline-primary like-btn'
                                    data-question-id='<?php echo $question['id']; ?>'>
                                    <span class="like-icon">👍</span>
                                    <span class="like-count"><?php echo $question['like_count']; ?></span>
                                </button>
                            </form>



                            <!-- Comment Button -->
                            <button type='button' class='btn btn-sm btn-outline-secondary comment-toggle-btn'
                                data-bs-toggle='collapse'
                                data-bs-target='#comments<?php echo $question['id']; ?>'>
                                💬 <span class="comment-count"><?php echo $question['comment_count']; ?></span>
                            </button>
                        </div>
                    </div>

                    <!-- Comments Section -->
                    <div class='collapse mt-3' id='searchComments<?php echo $question['id']; ?>'>
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
                            $comment_result = $comment_stmt->get_result(); ?>

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
        echo "<div class='alert alert-warning'>";
        echo "<h5>No results found</h5>";
        echo "<p class='mb-0'>Try different keywords or browse <a href='./'>all questions</a>.</p>";
        echo "</div>";

        // Show suggested searches
        $suggested_searches = [
            "Try more general keywords",
            "Check spelling",
            "Search in question titles only",
            "Browse all questions"
        ];

        echo "<div class='card'>";
        echo "<div class='card-body'>";
        echo "<h6>Suggestions:</h6>";
        echo "<ul class='mb-0'>";
        foreach ($suggested_searches as $suggestion) {
            echo "<li>" . $suggestion . "</li>";
        }
        echo "</ul>";
        echo "</div>";
        echo "</div>";
    }

    $conn->close();
} else {
    echo "<div class='alert alert-warning'>Please enter a search term.</div>";
}
?>