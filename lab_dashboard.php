<?php
session_start();
include 'config/db.php';
include 'includes/header.php';

if (!isset($_SESSION['user_id']) || (!in_array($_SESSION['role'], ['lab','labtech']))) {
    header("Location: login.php");
    exit();
}

$lab_name = htmlspecialchars($_SESSION['full_name'] ?? 'Lab Tech');

// Stats Logic
$pending = $conn->query("SELECT COUNT(*) FROM lab_tests WHERE status = 'pending'")->fetch_row()[0];
$processing = $conn->query("SELECT COUNT(*) FROM lab_tests WHERE status = 'processing'")->fetch_row()[0];
$completed = $conn->query("SELECT COUNT(*) FROM lab_tests WHERE status = 'completed'")->fetch_row()[0];
?>

<div class="container my-5">
    <div class="row mb-4 align-items-center">
        <div class="col-md-8">
            <h2 class="fw-bold">Welcome, <?= $lab_name ?></h2>
            <p class="text-muted">Manage diagnostic requests and patient reports.</p>
        </div>
        <div class="col-md-4 text-end">
            <span class="badge bg-success p-2">System Online</span>
        </div>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm bg-warning text-dark p-3">
                <h5>Pending Tests</h5>
                <div class="display-5 fw-bold"><?= $pending ?></div>
                <a href="lab_manage_tests.php?status=pending" class="text-dark small">View Details →</a>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm bg-info text-white p-3">
                <h5>In Processing</h5>
                <div class="display-5 fw-bold"><?= $processing ?></div>
                <a href="lab_manage_tests.php?status=processing" class="text-white small">View Details →</a>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm bg-success text-white p-3">
                <h5>Completed</h5>
                <div class="display-5 fw-bold"><?= $completed ?></div>
                <a href="lab_manage_tests.php?status=completed" class="text-white small">View Details →</a>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white fw-bold">Lab Operations</div>
        <div class="card-body">
            <div class="row text-center">
                <div class="col-md-3">
                    <a href="lab_manage_tests.php" class="btn btn-outline-primary w-100 py-3">
                        <i class="bi bi-list-check d-block fs-3"></i> All Test Requests
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>