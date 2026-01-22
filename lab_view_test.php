<?php
session_start();
include 'config/db.php';
include 'includes/header.php';

// Check if user is logged in as doctor
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: doctor_lab_notifications.php");
    exit();
}

$test_id = (int)$_GET['id'];

// IMPROVED QUERY: Using LEFT JOINs and selecting all columns from lab_tests
$sql = "SELECT l.*, 
               p.name as patient_name, p.age, p.gender, 
               u.full_name as doctor_name 
        FROM lab_tests l
        LEFT JOIN patients p ON l.patient_id = p.patient_id
        LEFT JOIN doctors d ON l.doctor_id = d.doctor_id
        LEFT JOIN users u ON d.user_id = u.user_id
        WHERE l.test_id = $test_id";

$res = $conn->query($sql);

if (!$res || $res->num_rows == 0) {
    echo "<div class='container my-5'><div class='alert alert-danger'>Report not found.</div></div>";
    include 'includes/footer.php';
    exit();
}

$report = $res->fetch_assoc();


$lab_result_data = "";
if (!empty($report['test_result'])) {
    $lab_result_data = $report['test_result'];
} elseif (!empty($report['result'])) {
    $lab_result_data = $report['result'];
} elseif (!empty($report['test_value'])) {
    $lab_result_data = $report['test_value'];
} else {
    $lab_result_data = "No results recorded.";
}
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="mb-4">
                <a href="doctor_lab_notifications.php" class="btn btn-secondary shadow-sm">
                    <i class="fas fa-arrow-left"></i> Back to Notifications
                </a>
            </div>

            <div class="card shadow border-0">
                <div class="card-header bg-white border-bottom py-4 text-center">
                    <h2 class="text-uppercase mb-1 text-primary fw-bold">Lab Investigation Report</h2>
                    <p class="text-muted mb-0">Hospital Management System - Official Lab Result</p>
                </div>
                
                <div class="card-body p-4">
                    <div class="row mb-4 border-bottom pb-3">
                        <div class="col-6">
                            <table class="table table-borderless table-sm mb-0">
                                <tr>
                                    <td class="text-muted" style="width: 100px;">Patient:</td>
                                    <td class="fw-bold"><?= htmlspecialchars($report['patient_name'] ?? 'N/A') ?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Age/Sex:</td>
                                    <td><?= (int)($report['age'] ?? 0) ?> Y / <?= htmlspecialchars($report['gender'] ?? 'N/A') ?></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-6 text-end">
                            <table class="table table-borderless table-sm mb-0">
                                <tr>
                                    <td class="text-muted">Date:</td>
                                    <td><?= date('d-M-Y', strtotime($report['created_at'])) ?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Ref. Doctor:</td>
                                    <td><?= htmlspecialchars($report['doctor_name'] ?? 'General') ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <div class="py-2">
                        <h5 class="fw-bold text-dark border-start border-4 border-primary ps-3 mb-4">
                            Test: <?= htmlspecialchars($report['test_name']) ?>
                        </h5>
                        
                        <div class="bg-light p-4 rounded border">
                            <label class="small text-uppercase text-muted fw-bold d-block mb-2">Diagnostic Result Findings</label>
                            <div class="fs-7 text-dark fw-bold" style="white-space: pre-wrap; font-family: monospace;">
                                <?= htmlspecialchars($lab_result_data) ?>
                            </div>
                        </div>

                        <?php if(!empty($report['remarks'])): ?>
                        <div class="mt-4">
                            <label class="small text-uppercase text-muted fw-bold d-block mb-2">Technician Remarks</label>
                            <div class="p-3 border rounded bg-white italic shadow-sm">
                                <?= nl2br(htmlspecialchars($report['remarks'])) ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card-footer bg-light py-3 text-center border-0">
                    <p class="small text-muted mb-0">
                        Status: <strong class="text-success"><?= strtoupper($report['status']) ?></strong> 
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>