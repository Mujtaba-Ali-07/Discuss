<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Discuss Project</title>
    <?php include 'client/commonFile.php'; ?>
</head>

<body>
    <?php
    session_start();

    // Handle logout
    if (isset($_GET['logout'])) {
        session_destroy();
        header("Location: /discuss");
        exit();
    }

    include 'client/header.php';

if (isset($_GET['login']) && !isset($_SESSION['user']['username'])) {
    include 'client/login.php';
} elseif (isset($_GET['signup']) && !isset($_SESSION['user']['username'])) {
    include 'client/signup.php';
} elseif (isset($_GET['ask']) && isset($_SESSION['user']['username'])) {
    include 'client/ask_question.php';
} elseif (isset($_GET['profile']) && isset($_SESSION['user']['username'])) {
    include 'client/profile.php';
} elseif (isset($_GET['feedback']) && isset($_SESSION['user']['username'])) {
    include 'client/feedback.php';
} elseif (isset($_GET['admin']) && isset($_SESSION['user']['username'])) {
    include 'client/admin.php';
} else {
        // Homepage content
        echo "<div class='container mt-4'>";

        // Display user info if logged in
        // if (isset($_SESSION['user']['username'])) {
        //     echo "<div class='alert alert-info'>";
        //     echo "<p>Welcome back, " . htmlspecialchars($_SESSION['user']['username']) . "!</p>";
        //     echo "<a href='?ask=true' class='btn btn-primary'>Ask a Question</a>";
        //     echo "</div>";
        // } else {
        //     echo "<p>Please <a href='?login=true'>login</a> or <a href='?signup=true'>signup</a> to ask questions.</p>";
        // }

        // Display filter info
        $current_filter = 'random';
        if (isset($_GET['filter'])) {
            $current_filter = $_GET['filter'];
        }

        echo "<div class='d-flex justify-content-between align-items-center mb-4'>";
        echo "<h3>";
        switch ($current_filter) {
            case 'latest':
                echo "📅 Latest Questions";
                break;
            case 'popular':
                echo "🔥 Popular Questions";
                break;
            case 'search':
                echo "🔍 Search Results";
                break;
            default:
                echo "🎲 Random Questions";
                break;
        }
        echo "</h3>";

        // Filter buttons
        echo "<div class='btn-group'>";
        echo "<a href='./' class='btn btn-outline-primary " . ($current_filter == 'random' ? 'active' : '') . "'>🎲 Random</a>";
        echo "<a href='?filter=latest' class='btn btn-outline-primary " . ($current_filter == 'latest' ? 'active' : '') . "'>📅 Latest</a>";
        echo "<a href='?filter=popular' class='btn btn-outline-primary " . ($current_filter == 'popular' ? 'active' : '') . "'>🔥 Popular</a>";
        echo "</div>";
        echo "</div>";

        // Check if search is active
        if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
            include 'server/search_questions.php';
        } else {
            // Display questions based on filter
            switch ($current_filter) {
                case 'latest':
                    include 'server/latest_questions.php';
                    break;
                case 'popular':
                    include 'server/popular_questions.php';
                    break;
                default:
                    include 'server/random_questions.php';
                    break;
            }
        }

        echo "</div>";
    }
    ?>
</body>

</html>