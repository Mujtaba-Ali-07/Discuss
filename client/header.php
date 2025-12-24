<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<nav class="navbar navbar-expand-lg navbar-light bg-light">
    <div class="container-fluid">
        <a class="navbar-brand" href="./">
            <img src="./public/logo.png" style="height: 70px;" alt="Discuss Project Logo">
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <!-- Left Navigation -->
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link <?php echo !isset($_GET['filter']) && !isset($_GET['search']) ? 'active' : ''; ?>" href="./">Home</a>
                </li>

                <?php if (isset($_SESSION['user']['username']) && !empty($_SESSION['user']['username'])) { ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo isset($_GET['ask']) ? 'active' : ''; ?>" href="?ask=true">Ask Question</a>
                    </li>
                <?php } ?>

                <li class="nav-item">
                    <a class="nav-link <?php echo isset($_GET['feedback']) ? 'active' : ''; ?>" href="?feedback=true">Feedback</a>
                </li>
            </ul>

            <!-- Right Navigation -->
            <div class="d-flex align-items-center">
                <!-- Search Form -->
                <form class="d-flex me-3" method="get" action="./">
                    <div class="input-group">
                        <input class="form-control" type="search" name="search" placeholder="Search questions..." 
                               value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>" 
                               aria-label="Search">
                        <button class="btn btn-outline-primary" type="submit">Search</button>
                        <?php if (isset($_GET['search']) && !empty($_GET['search'])): ?>
                            <a href="./" class="btn btn-outline-secondary">Clear</a>
                        <?php endif; ?>
                    </div>
                </form>

                <!-- User Profile or Auth Links -->
                <?php if (isset($_SESSION['user']['username']) && !empty($_SESSION['user']['username'])) {
                    // Get user profile picture and admin status
                    $user_email = $_SESSION['user']['useremail'];
                    include './common/db.php';
                    $stmt = $conn->prepare("SELECT profile_picture, is_admin FROM users WHERE useremail = ?");
                    $stmt->bind_param("s", $user_email);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $user_data = $result->fetch_assoc();

                    $profile_picture = !empty($user_data['profile_picture'])
                        ? "./public/uploads/" . $user_data['profile_picture']
                        : "./public/default-avatar.png";
                    
                    // Get only first name for display
                    $full_name = $_SESSION['user']['username'];
                    $first_name = explode(' ', $full_name)[0];
                    $is_admin = $user_data && $user_data['is_admin'] == 1;
                ?>
                    <div class="dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="navbarDropdown" 
                           role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <img src="<?php echo $profile_picture; ?>"
                                class="rounded-circle me-2"
                                alt="Profile"
                                style="width: 32px; height: 32px; object-fit: cover;">
                            <span class="fw-medium"><?php echo htmlspecialchars($first_name); ?></span>
                            <?php if ($is_admin): ?>
                                <span class="badge bg-primary ms-1" style="font-size: 0.6em; padding: 0.2em 0.4em;">Admin</span>
                            <?php endif; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                            <li>
                                <a class="dropdown-item" href="?profile=true">
                                    <span class="me-2">👤</span>My Profile
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="?ask=true">
                                    <span class="me-2">❓</span>Ask Question
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="?feedback=true">
                                    <span class="me-2">📝</span>Give Feedback
                                </a>
                            </li>
                            
                            <?php if ($is_admin): ?>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item text-primary fw-bold" href="?admin=true">
                                        <span class="me-2">👑</span>Admin Panel
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-primary" href="?logout=true">
                                    <span class="me-2">🚪</span>Logout
                                </a>
                            </li>
                        </ul>
                    </div>
                <?php } else { ?>
                    <div class="d-flex align-items-center">
                        <a class="nav-link" href="?login=true">Login</a>
                        <span class="text-muted">|</span>
                        <a class="nav-link" href="?signup=true">Signup</a>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>
</nav>

<!-- Success/Error Messages -->
<?php if (isset($_GET['feedback']) && $_GET['feedback'] == 'success'): ?>
    <div class="container mt-3">
        <div class="alert alert-primary alert-dismissible fade show" role="alert">
            ✅ Thank you for your feedback! We appreciate your input.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    </div>
<?php endif; ?>

<?php if (isset($_GET['question']) && $_GET['question'] == 'posted'): ?>
    <div class="container mt-3">
        <div class="alert alert-primary alert-dismissible fade show" role="alert">
            ✅ Your question has been posted successfully!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    </div>
<?php endif; ?>

<?php if (isset($_GET['profile']) && isset($_GET['upload']) && $_GET['upload'] == 'success'): ?>
    <div class="container mt-3">
        <div class="alert alert-primary alert-dismissible fade show" role="alert">
            ✅ Profile picture updated successfully!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    </div>
<?php endif; ?>

<!-- Mobile Search (Hidden on desktop) -->
<div class="container d-lg-none mt-3">
    <form method="get" action="./" class="d-flex">
        <input class="form-control me-2" type="search" name="search" placeholder="Search questions..." 
               value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
        <button class="btn btn-outline-primary" type="submit">Search</button>
    </form>
</div>



<script>
// Add active class to current page links
document.addEventListener('DOMContentLoaded', function() {
    const currentUrl = window.location.href;
    const navLinks = document.querySelectorAll('.nav-link');
    
    navLinks.forEach(link => {
        if (link.href === currentUrl || 
            (link.href.includes('?') && currentUrl.includes(link.href.split('?')[1]))) {
            link.classList.add('active');
        }
    });
    
    // Auto-dismiss alerts after 5 seconds
    setTimeout(() => {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);
});
</script>