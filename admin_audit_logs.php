<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Logic to identify the logged-in user
$is_logged_in = isset($_SESSION['user_id']);
$display_name = $_SESSION['full_name'] ?? 'User';
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm py-3">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php">
            <i class="fas fa-hospital-alt me-2 text-primary"></i>HMS
        </a>
        
        <div class="ms-auto d-flex align-items-center">
            <?php if ($is_logged_in): ?>
                <div class="text-white me-3">
                    <i class="fas fa-user-circle me-1 text-info"></i>
                    Logged in as: <span class="fw-bold text-info"><?= htmlspecialchars($display_name) ?></span>
                </div>
                <a href="logout.php" class="btn btn-outline-danger btn-sm rounded-pill px-3">
                    <i class="fas fa-sign-out-alt me-1"></i>Logout
                </a>
            <?php else: ?>
                <div class="text-white-50 me-3 small">No one logged in yet</div>
                <a href="login.php" class="btn btn-primary btn-sm rounded-pill px-4">Login</a>
            <?php endif; ?>
        </div>
    </div>
</nav>