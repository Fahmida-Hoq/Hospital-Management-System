<?php
session_start();
include 'config/db.php';
include 'includes/header.php';

// Access Control
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'labtech') {
    header("Location: login.php");
    exit();
}

$labtech_name = $_SESSION['full_name'] ?? 'Lab Technician';
//  New Test Requests 
$pending_tests = query("SELECT COUNT(test_id) FROM lab_tests WHERE status = 'pending'")->get_result()->fetch_row()[0] ?? 0;
// Tests Currently in Progress
$processing_tests = query("SELECT COUNT(test_id) FROM lab_tests WHERE status = 'processing'")->get_result()->fetch_row()[0] ?? 0;
?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
        <h2 class="text-info">Lab Technician </h2>
        <a href="logout.php" class="btn btn-outline-danger">Logout</a>
    </div>
    
    <p class="lead">Welcome, **<?php echo htmlspecialchars($labtech_name); ?>**! Here are your current lab tasks.</p>

    <h4 class="mb-4 mt-5">Test Queue Snapshot</h4>
    <div class="row mb-5">
        
        <div class="col-md-6">
            <div class="card text-white bg-warning mb-3 shadow">
                <div class="card-body">
                    <h5 class="card-title">New Test Requests (Pending)</h5>
                    <p class="card-text h1"><?php echo $pending_tests; ?></p>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card text-white bg-primary mb-3 shadow">
                <div class="card-body">
                    <h5 class="card-title">Tests Currently in Progress</h5>
                    <p class="card-text h1"><?php echo $processing_tests; ?></p>
                </div>
            </div>
        </div>
    </div>

    <h4 class="mb-4">Lab Core Tasks</h4>
    <div class="row">
        
        <div class="col-md-6 mb-3">
            <a href="labtech_process_tests.php" class="btn btn-lg btn-block btn-primary w-100 shadow-sm">
                <i class="fas fa-clipboard-list me-2"></i> 1. Process New Test Requests
            </a>
        </div>
        
        <div class="col-md-6 mb-3">
            <a href="labtech_enter_results.php" class="btn btn-lg btn-block btn-success w-100 shadow-sm">
                <i class="fas fa-file-upload me-2"></i> 2. Enter Test Results
            </a>
        </div>
        
    </div>
</div>

<?php include 'includes/footer.php'; ?>