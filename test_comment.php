<?php
session_start();
include './common/db.php';

// Simulate a logged in user for testing
$_SESSION['user'] = [
    'username' => 'TestUser',
    'useremail' => 'test@example.com'
];

// Test database connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "Database connection successful!<br>";

// Test if comments table exists
$result = $conn->query("SHOW TABLES LIKE 'comments'");
if ($result->num_rows > 0) {
    echo "Comments table exists!<br>";
} else {
    echo "Comments table does NOT exist!<br>";
}

// Check session
if (isset($_SESSION['user']['useremail'])) {
    echo "User is logged in: " . $_SESSION['user']['useremail'] . "<br>";
} else {
    echo "User is NOT logged in!<br>";
}
?>