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

// Check if user is logged in and admin
if (!isset($_SESSION['user']['useremail'])) {
    send_json_response(false, 'Please login!');
}

$user_email = $_SESSION['user']['useremail'];
$check_stmt = $conn->prepare("SELECT is_admin FROM users WHERE useremail = ?");
$check_stmt->bind_param("s", $user_email);
$check_stmt->execute();
$result = $check_stmt->get_result();
$admin_check = $result->fetch_assoc();

if (!$admin_check || $admin_check['is_admin'] != 1) {
    send_json_response(false, 'Access denied!');
}

// Handle actions
if (isset($_GET['action']) && $_GET['action'] == 'get_user' && isset($_GET['id'])) {
    $user_id = intval($_GET['id']);
    
    $query = "
        SELECT u.*, 
               COUNT(DISTINCT q.id) as question_count,
               COUNT(DISTINCT c.id) as comment_count
        FROM users u 
        LEFT JOIN questions q ON u.id = q.user_id 
        LEFT JOIN comments c ON u.id = c.user_id 
        WHERE u.id = ?
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if ($user) {
        $profile_pic = !empty($user['profile_picture'])
            ? "./public/uploads/" . $user['profile_picture']
            : "./public/default-avatar.png";
        
        $html = '
        <div class="row">
            <div class="col-md-4 text-center">
                <img src="' . $profile_pic . '" 
                     class="rounded-circle mb-3" 
                     style="width: 150px; height: 150px; object-fit: cover;">
                <h5>' . htmlspecialchars($user['username']) . '</h5>
                ' . ($user['is_admin'] == 1 ? '<span class="badge bg-danger">Admin</span>' : '') . '
            </div>
            <div class="col-md-8">
                <table class="table table-sm">
                    <tr>
                        <th>Email:</th>
                        <td>' . htmlspecialchars($user['useremail']) . '</td>
                    </tr>
                    <tr>
                        <th>Country:</th>
                        <td>' . htmlspecialchars($user['usercountry']) . '</td>
                    </tr>
                    <tr>
                        <th>Joined:</th>
                        <td>' . date('M j, Y', strtotime($user['created_at'])) . '</td>
                    </tr>
                    <tr>
                        <th>Questions:</th>
                        <td>' . $user['question_count'] . '</td>
                    </tr>
                    <tr>
                        <th>Comments:</th>
                        <td>' . $user['comment_count'] . '</td>
                    </tr>
                </table>
            </div>
        </div>';
        
        send_json_response(true, 'User data loaded', ['html' => $html]);
    } else {
        send_json_response(false, 'User not found');
    }
}

send_json_response(false, 'Invalid action');
?>