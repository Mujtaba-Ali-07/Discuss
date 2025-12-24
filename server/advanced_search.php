<?php
include '../common/db.php';

if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $search_term = trim($_GET['search']);
    $search_terms = explode(' ', $search_term);

    // Build search conditions for multiple words
    $title_conditions = [];
    $desc_conditions = [];
    $comment_conditions = [];
    $params = [];
    $types = '';

    foreach ($search_terms as $term) {
        if (strlen($term) > 2) { // Only search for terms longer than 2 characters
            $title_conditions[] = "q.title LIKE ?";
            $desc_conditions[] = "q.description LIKE ?";
            $comment_conditions[] = "cm.comment_text LIKE ?";
            $like_term = "%" . $term . "%";
            $params[] = $like_term;
            $params[] = $like_term;
            $params[] = $like_term;
            $types .= 'sss';
        }
    }

    if (empty($title_conditions)) {
        echo "<div class='alert alert-warning'>Please use longer search terms (minimum 3 characters).</div>";
        exit;
    }

    $title_condition = implode(' OR ', $title_conditions);
    $desc_condition = implode(' OR ', $desc_conditions);
    $comment_condition = implode(' OR ', $comment_conditions);

    $query = "
        SELECT DISTINCT
            q.*, 
            u.username,
            u.id as user_id,
            COUNT(DISTINCT ql.id) as like_count,
            COUNT(DISTINCT c.id) as comment_count,
            -- Advanced relevance scoring
            ( 
                (CASE WHEN (" . $title_condition . ") THEN 3 ELSE 0 END) +
                (CASE WHEN (" . $desc_condition . ") THEN 2 ELSE 0 END) +
                (CASE WHEN EXISTS (SELECT 1 FROM comments cm WHERE cm.question_id = q.id AND (" . $comment_condition . ")) THEN 1 ELSE 0 END)
            ) as relevance_score
        FROM questions q 
        LEFT JOIN users u ON q.user_id = u.id 
        LEFT JOIN question_likes ql ON q.id = ql.question_id 
        LEFT JOIN comments c ON q.id = c.question_id 
        WHERE (" . $title_condition . ") OR (" . $desc_condition . ") 
           OR EXISTS (SELECT 1 FROM comments cm WHERE cm.question_id = q.id AND (" . $comment_condition . "))
        GROUP BY q.id 
        ORDER BY relevance_score DESC, like_count DESC, q.created_at DESC
    ";

    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        // Rest of the display code same as search_questions.php
        // ... [include the display code from search_questions.php]
    }
}
