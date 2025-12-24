<?php
session_start();
include '../common/db.php';

// Function to send JSON responses
function send_json_response($success, $message, $data = []) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit();
}

// Function to check if user has already liked a question
function hasUserLikedQuestion($conn, $user_id, $question_id) {
    $check_stmt = $conn->prepare("SELECT id FROM question_likes WHERE user_id = ? AND question_id = ?");
    $check_stmt->bind_param("ii", $user_id, $question_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    return $result->num_rows > 0;
}

// Handle signup
if (isset($_POST['signup'])) {
    $username = trim($_POST['username']);
    $useremail = trim($_POST['useremail']);
    $userpassword = trim($_POST['userpassword']);
    $usercountry = trim($_POST['usercountry']);

    // Basic validation
    if (empty($username) || empty($useremail) || empty($userpassword)) {
        echo "All fields are required!";
        exit();
    }

    $user = $conn->prepare("INSERT INTO `users` (`username`, `useremail`, `userpassword`, `usercountry`) VALUES (?, ?, ?, ?)");
    $user->bind_param("ssss", $username, $useremail, $userpassword, $usercountry);

    $result = $user->execute();
    if ($result) {
        $_SESSION['user'] = ["username" => $username, "useremail" => $useremail];
        header("Location: /discuss");
        exit();
    } else {
        echo "Error registering user: " . $conn->error;
    }
}

// Handle login
if (isset($_POST['login'])) {
    $useremail = trim($_POST['useremail']);
    $userpassword = trim($_POST['userpassword']);

    $stmt = $conn->prepare("SELECT username, useremail FROM users WHERE useremail = ? AND userpassword = ?");
    $stmt->bind_param("ss", $useremail, $userpassword);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $_SESSION['user'] = [
            "username" => $user['username'],
            "useremail" => $user['useremail']
        ];
        header("Location: /discuss");
        exit();
    } else {
        echo "Invalid email or password!";
    }
}

// Handle asking a question
if (isset($_POST['ask_question'])) {
    if (!isset($_SESSION['user']['useremail'])) {
        echo "Please login to ask a question!";
        exit();
    }

    $question_title = trim($_POST['question_title']);
    $question_description = trim($_POST['question_description']);
    $user_email = $_SESSION['user']['useremail'];

    // Get user ID
    $stmt = $conn->prepare("SELECT id FROM users WHERE useremail = ?");
    $stmt->bind_param("s", $user_email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $user_id = $user['id'];

    // Insert question
    $stmt = $conn->prepare("INSERT INTO questions (user_id, title, description) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $user_id, $question_title, $question_description);
    
    if ($stmt->execute()) {
        header("Location: /discuss");
        exit();
    } else {
        echo "Error posting question: " . $conn->error;
    }
}

// Handle liking a question (AJAX version)
if (isset($_POST['like_question_ajax'])) {
    if (!isset($_SESSION['user']['useremail'])) {
        send_json_response(false, 'Please login to like questions!');
    }

    $question_id = intval($_POST['question_id']);
    $user_email = $_SESSION['user']['useremail'];

    // Get user ID
    $stmt = $conn->prepare("SELECT id FROM users WHERE useremail = ?");
    $stmt->bind_param("s", $user_email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $user_id = $user['id'];

    // Check if already liked
    $already_liked = hasUserLikedQuestion($conn, $user_id, $question_id);
    
    if ($already_liked) {
        // Unlike
        $delete_stmt = $conn->prepare("DELETE FROM question_likes WHERE user_id = ? AND question_id = ?");
        $delete_stmt->bind_param("ii", $user_id, $question_id);
        if ($delete_stmt->execute()) {
            // Get updated like count
            $count_stmt = $conn->prepare("SELECT COUNT(*) as like_count FROM question_likes WHERE question_id = ?");
            $count_stmt->bind_param("i", $question_id);
            $count_stmt->execute();
            $count_result = $count_stmt->get_result();
            $count_data = $count_result->fetch_assoc();
            
            send_json_response(true, 'Unliked', [
                'liked' => false,
                'like_count' => $count_data['like_count'],
                'question_id' => $question_id
            ]);
        } else {
            send_json_response(false, 'Error unliking question');
        }
    } else {
        // Like
        $insert_stmt = $conn->prepare("INSERT INTO question_likes (user_id, question_id) VALUES (?, ?)");
        $insert_stmt->bind_param("ii", $user_id, $question_id);
        if ($insert_stmt->execute()) {
            // Get updated like count
            $count_stmt = $conn->prepare("SELECT COUNT(*) as like_count FROM question_likes WHERE question_id = ?");
            $count_stmt->bind_param("i", $question_id);
            $count_stmt->execute();
            $count_result = $count_stmt->get_result();
            $count_data = $count_result->fetch_assoc();
            
            send_json_response(true, 'Liked', [
                'liked' => true,
                'like_count' => $count_data['like_count'],
                'question_id' => $question_id
            ]);
        } else {
            send_json_response(false, 'Error liking question');
        }
    }
}

// Handle adding comments (AJAX version)
if (isset($_POST['add_comment_ajax'])) {
    if (!isset($_SESSION['user']['useremail'])) {
        send_json_response(false, 'Please login to comment!');
    }

    $question_id = intval($_POST['question_id']);
    $comment_text = trim($_POST['comment_text']);
    
    if (empty($comment_text)) {
        send_json_response(false, 'Comment cannot be empty');
    }

    $user_email = $_SESSION['user']['useremail'];

    // Get user ID and username
    $stmt = $conn->prepare("SELECT id, username, profile_picture FROM users WHERE useremail = ?");
    $stmt->bind_param("s", $user_email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $user_id = $user['id'];
    $username = $user['username'];
    $profile_picture = $user['profile_picture'];

    // Insert comment
    $stmt = $conn->prepare("INSERT INTO comments (user_id, question_id, comment_text) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $user_id, $question_id, $comment_text);
    
    if ($stmt->execute()) {
        $comment_id = $stmt->insert_id;
        
        // Get comment count for this question
        $count_stmt = $conn->prepare("SELECT COUNT(*) as comment_count FROM comments WHERE question_id = ?");
        $count_stmt->bind_param("i", $question_id);
        $count_stmt->execute();
        $count_result = $count_stmt->get_result();
        $count_data = $count_result->fetch_assoc();
        
        // Prepare comment HTML
        $profile_pic_url = !empty($profile_picture) 
            ? "./public/uploads/" . $profile_picture 
            : "./public/default-avatar.png";
        
        $comment_html = '
        <div class="border-bottom pb-2 mb-2">
            <div class="d-flex align-items-center mb-1">
                <img src="' . $profile_pic_url . '" 
                     class="rounded-circle me-2" 
                     alt="Commenter" 
                     style="width: 20px; height: 20px; object-fit: cover;">
                <strong>' . htmlspecialchars($username) . ':</strong>
            </div>
            ' . htmlspecialchars($comment_text) . '
            <br><small class="text-muted">Just now</small>
        </div>';
        
        send_json_response(true, 'Comment added successfully', [
            'comment_id' => $comment_id,
            'comment_html' => $comment_html,
            'comment_count' => $count_data['comment_count'],
            'question_id' => $question_id,
            'username' => $username
        ]);
    } else {
        send_json_response(false, 'Error posting comment: ' . $conn->error);
    }
}

// Handle profile picture upload
if (isset($_POST['upload_profile_picture'])) {
    if (!isset($_SESSION['user']['useremail'])) {
        echo "Please login to upload profile picture!";
        exit();
    }

    $user_email = $_SESSION['user']['useremail'];
    
    // File upload handling
    $target_dir = "../public/uploads/";
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $imageFileType = strtolower(pathinfo($_FILES["profile_picture"]["name"], PATHINFO_EXTENSION));
    $new_filename = "profile_" . time() . "_" . uniqid() . "." . $imageFileType;
    $target_file = $target_dir . $new_filename;
    
    // Check if image file is actual image
    $check = getimagesize($_FILES["profile_picture"]["tmp_name"]);
    if ($check === false) {
        echo "File is not an image.";
        exit();
    }
    
    // Check file size (max 2MB)
    if ($_FILES["profile_picture"]["size"] > 2000000) {
        echo "Sorry, your file is too large.";
        exit();
    }
    
    // Allow certain file formats
    if (!in_array($imageFileType, ["jpg", "png", "jpeg", "gif"])) {
        echo "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
        exit();
    }
    
    if (move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $target_file)) {
        // Update database with profile picture path
        $stmt = $conn->prepare("UPDATE users SET profile_picture = ? WHERE useremail = ?");
        $stmt->bind_param("ss", $new_filename, $user_email);
        
        if ($stmt->execute()) {
            // Update session with new profile picture
            $_SESSION['user']['profile_picture'] = $new_filename;
            header("Location: /discuss?profile=true");
            exit();
        } else {
            echo "Error updating profile picture: " . $conn->error;
        }
    } else {
        echo "Sorry, there was an error uploading your file.";
    }
}

// Handle question deletion
if (isset($_POST['delete_question'])) {
    if (!isset($_SESSION['user']['useremail'])) {
        echo "Please login to delete questions!";
        exit();
    }

    $question_id = $_POST['question_id'];
    $user_email = $_SESSION['user']['useremail'];

    // Get user ID
    $stmt = $conn->prepare("SELECT id FROM users WHERE useremail = ?");
    $stmt->bind_param("s", $user_email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $user_id = $user['id'];

    // Verify user owns the question before deleting
    $verify_stmt = $conn->prepare("SELECT id FROM questions WHERE id = ? AND user_id = ?");
    $verify_stmt->bind_param("ii", $question_id, $user_id);
    $verify_stmt->execute();
    
    if ($verify_stmt->get_result()->num_rows > 0) {
        // User owns the question, proceed with deletion
        $delete_stmt = $conn->prepare("DELETE FROM questions WHERE id = ?");
        $delete_stmt->bind_param("i", $question_id);
        
        if ($delete_stmt->execute()) {
            header("Location: /discuss?profile=true");
            exit();
        } else {
            echo "Error deleting question: " . $conn->error;
        }
    } else {
        echo "You don't have permission to delete this question!";
    }
}

// Handle feedback submission
if (isset($_POST['submit_feedback'])) {
    if (!isset($_SESSION['user']['useremail'])) {
        echo "Please login to submit feedback!";
        exit();
    }

    $feedback_type = $_POST['feedback_type'];
    $rating = intval($_POST['rating']);
    $feedback_text = trim($_POST['feedback_text']);
    $user_email = $_SESSION['user']['useremail'];

    // Validate rating
    if ($rating < 1 || $rating > 5) {
        $rating = 5;
    }

    // Validate feedback text
    if (empty($feedback_text)) {
        echo "Feedback text cannot be empty!";
        exit();
    }

    // Get user ID
    $stmt = $conn->prepare("SELECT id FROM users WHERE useremail = ?");
    $stmt->bind_param("s", $user_email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $user_id = $user['id'];

    // Insert feedback
    $stmt = $conn->prepare("INSERT INTO feedback (user_id, feedback_type, rating, feedback_text) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isis", $user_id, $feedback_type, $rating, $feedback_text);
    
    if ($stmt->execute()) {
        header("Location: /discuss?feedback=success");
        exit();
    } else {
        echo "Error submitting feedback: " . $conn->error;
    }
}

// Fallback handlers for non-AJAX requests (for browsers without JavaScript)
if (isset($_POST['like_question'])) {
    if (!isset($_SESSION['user']['useremail'])) {
        echo "Please login to like questions!";
        exit();
    }

    $question_id = $_POST['question_id'];
    $user_email = $_SESSION['user']['useremail'];

    // Get user ID
    $stmt = $conn->prepare("SELECT id FROM users WHERE useremail = ?");
    $stmt->bind_param("s", $user_email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $user_id = $user['id'];

    // Check if already liked
    $check_stmt = $conn->prepare("SELECT id FROM question_likes WHERE user_id = ? AND question_id = ?");
    $check_stmt->bind_param("ii", $user_id, $question_id);
    $check_stmt->execute();
    
    if ($check_stmt->get_result()->num_rows > 0) {
        // Unlike
        $delete_stmt = $conn->prepare("DELETE FROM question_likes WHERE user_id = ? AND question_id = ?");
        $delete_stmt->bind_param("ii", $user_id, $question_id);
        $delete_stmt->execute();
    } else {
        // Like
        $insert_stmt = $conn->prepare("INSERT INTO question_likes (user_id, question_id) VALUES (?, ?)");
        $insert_stmt->bind_param("ii", $user_id, $question_id);
        $insert_stmt->execute();
    }
    
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit();
}

if (isset($_POST['add_comment'])) {
    if (!isset($_SESSION['user']['useremail'])) {
        echo "Please login to comment!";
        exit();
    }

    $question_id = $_POST['question_id'];
    $comment_text = $_POST['comment_text'];
    $user_email = $_SESSION['user']['useremail'];

    // Get user ID
    $stmt = $conn->prepare("SELECT id FROM users WHERE useremail = ?");
    $stmt->bind_param("s", $user_email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $user_id = $user['id'];

    // Insert comment
    $stmt = $conn->prepare("INSERT INTO comments (user_id, question_id, comment_text) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $user_id, $question_id, $comment_text);
    
    if ($stmt->execute()) {
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit();
    } else {
        echo "Error posting comment: " . $conn->error;
    }
}

// Handle make admin
if (isset($_POST['make_admin'])) {
    if (!isset($_SESSION['user']['useremail'])) {
        echo "Please login!";
        exit();
    }

    // Check if current user is admin
    $user_email = $_SESSION['user']['useremail'];
    $check_stmt = $conn->prepare("SELECT is_admin FROM users WHERE useremail = ?");
    $check_stmt->bind_param("s", $user_email);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    $admin_check = $result->fetch_assoc();
    
    if (!$admin_check || $admin_check['is_admin'] != 1) {
        echo "You don't have permission to perform this action!";
        exit();
    }

    $user_id = intval($_POST['user_id']);
    
    // Make user admin
    $update_stmt = $conn->prepare("UPDATE users SET is_admin = 1 WHERE id = ?");
    $update_stmt->bind_param("i", $user_id);
    
    if ($update_stmt->execute()) {
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit();
    } else {
        echo "Error making user admin: " . $conn->error;
    }
}

// Handle delete question (admin version)
if (isset($_POST['delete_question_admin'])) {
    if (!isset($_SESSION['user']['useremail'])) {
        echo "Please login!";
        exit();
    }

    // Check if current user is admin
    $user_email = $_SESSION['user']['useremail'];
    $check_stmt = $conn->prepare("SELECT is_admin FROM users WHERE useremail = ?");
    $check_stmt->bind_param("s", $user_email);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    $admin_check = $result->fetch_assoc();
    
    if (!$admin_check || $admin_check['is_admin'] != 1) {
        echo "You don't have permission to perform this action!";
        exit();
    }

    $question_id = intval($_POST['question_id']);
    
    // Delete question
    $delete_stmt = $conn->prepare("DELETE FROM questions WHERE id = ?");
    $delete_stmt->bind_param("i", $question_id);
    
    if ($delete_stmt->execute()) {
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit();
    } else {
        echo "Error deleting question: " . $conn->error;
    }
}

// If no action matched
echo "Invalid request!";
?>