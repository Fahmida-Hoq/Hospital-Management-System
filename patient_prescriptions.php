<?php
session_start();
include 'config/db.php';
include 'includes/header.php';

if (!isset($_SESSION['patient_id'])) {
    header("Location: login.php");
    exit();
}

$patient_id = (int)$_SESSION['patient_id'];

// Fetch all prescriptions for this patient - Added error handling
$pres_result = $conn->query("SELECT * FROM prescriptions WHERE patient_id = $patient_id ORDER BY date_prescribed DESC");
?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="text-primary"><i class="fas fa-pills me-2"></i> My Prescriptions</h3>
        <a href="patient_dashboard.php" class="btn btn-outline-secondary btn-sm">Back to Dashboard</a>
    </div>

    <?php 
    // Check if query was successful and has rows
    if ($pres_result && $pres_result->num_rows > 0): ?>
        <div class="row">
            <?php while($p = $pres_result->fetch_assoc()): ?>
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm border-start border-primary border-4">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <h5 class="card-title text-dark"><?= htmlspecialchars($p['medicine_name'] ?? 'N/A') ?></h5>
                                <span class="badge bg-info text-dark"><?= htmlspecialchars($p['dosage'] ?? '') ?></span>
                            </div>
                            <h6 class="card-subtitle mb-2 text-muted small">Prescribed by: Dr. <?= htmlspecialchars($p['doctor_name'] ?? 'Medical Staff') ?></h6>
                            <p class="card-text mt-3">
                                <strong>Duration:</strong> <?= htmlspecialchars($p['duration'] ?? 'As directed') ?><br>
                                <strong>Instructions:</strong> <?= htmlspecialchars($p['instructions'] ?? 'None') ?>
                            </p>
                            <div class="text-end border-top pt-2">
                                <small class="text-muted">Date: <?= isset($p['date_prescribed']) ? date('d M Y', strtotime($p['date_prescribed'])) : 'N/A' ?></small>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php elseif (!$pres_result): ?>
        <div class="alert alert-danger text-center">
            <i class="fas fa-exclamation-triangle me-2"></i> Database Error: <?= $conn->error ?>
        </div>
    <?php else: ?>
        <div class="alert alert-warning text-center">
            <i class="fas fa-info-circle me-2"></i> No active prescriptions found. Please contact your attending doctor.
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>