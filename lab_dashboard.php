<?php
// lab_dashboard.php
session_start();
include 'config/db.php';
include 'includes/header.php';

// Accept both 'lab' and 'labtech' roles
if (!isset($_SESSION['user_id']) || (!in_array($_SESSION['role'], ['lab','labtech']))) {
    header("Location: login.php");
    exit();
}

$lab_name = htmlspecialchars($_SESSION['full_name'] ?? 'Lab Tech');

// counts
$q1 = $conn->prepare("SELECT COUNT(test_id) FROM lab_tests WHERE status = 'pending'");
$q1->execute(); $pending = $q1->get_result()->fetch_row()[0]; $q1->close();

$q2 = $conn->prepare("SELECT COUNT(test_id) FROM lab_tests WHERE status = 'processing'");
$q2->execute(); $processing = $q2->get_result()->fetch_row()[0]; $q2->close();

$q3 = $conn->prepare("SELECT COUNT(test_id) FROM lab_tests WHERE status = 'completed'");
$q3->execute(); $completed = $q3->get_result()->fetch_row()[0]; $q3->close();
?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Lab Dashboard — <?= $lab_name ?></h2>
        <a href="logout.php" class="btn btn-danger">Logout</a>
    </div>

    <div class="row g-4">
        <div class="col-md-4"><div class="card p-3"><h5>Pending</h5><div class="display-4"><?= (int)$pending ?></div></div></div>
        <div class="col-md-4"><div class="card p-3"><h5>Processing</h5><div class="display-4"><?= (int)$processing ?></div></div></div>
        <div class="col-md-4"><div class="card p-3"><h5>Completed</h5><div class="display-4"><?= (int)$completed ?></div></div></div>
    </div>

    <hr>
    <a href="lab_pending_tests.php" class="btn btn-primary">Open Pending Tests</a>
</div>

<?php include 'includes/footer.php'; ?>
