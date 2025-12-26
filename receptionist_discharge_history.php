<?php
session_start();
include 'config/db.php';
include 'includes/header.php';

// Fetch patients who are no longer in the hospital
$query = "SELECT * FROM patients WHERE status = 'Discharged' ORDER BY discharge_date DESC";
$res = $conn->query($query);
?>

<div class="container-fluid my-5 px-4">
    <div class="card shadow border-0">
        <div class="card-header bg-secondary text-white p-3 d-flex justify-content-between">
            <h4 class="mb-0"><i class="fas fa-archive mr-2"></i> Patient Discharge Archive</h4>
            <a href="receptionist_admitted_patients.php" class="btn btn-light btn-sm">Back to Current Patients</a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>Patient Details</th>
                            <th>Guardian Information</th>
                            <th>Medical Summary</th>
                            <th>Stay Duration</th>
                            <th>Financials</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($res->num_rows > 0): ?>
                            <?php while ($p = $res->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($p['name']) ?></strong><br>
                                    <small class="text-muted">ID: #<?= $p['patient_id'] ?> | <?= $p['gender'] ?></small>
                                </td>
                                <td>
                                    <?= htmlspecialchars($p['guardian_name']) ?><br>
                                    <small><?= $p['guardian_phone'] ?></small>
                                </td>
                                <td>
                                    <small><strong>Reason:</strong> <?= htmlspecialchars($p['admission_reason']) ?></small><br>
                                    <small><strong>Blood Group:</strong> <?= $p['blood_group'] ?></small>
                                </td>
                                <td>
                                    <small>Admit: <?= date('d M Y', strtotime($p['admission_date'])) ?></small><br>
                                    <small class="text-danger">Exit: <?= date('d M Y', strtotime($p['discharge_date'])) ?></small>
                                </td>
                                <td>
                                    <?php 
                                    // Total bill calculation for history
                                    $bill_q = $conn->query("SELECT SUM(amount) as total FROM billing WHERE patient_id = ".$p['patient_id']);
                                    $bill = $bill_q->fetch_assoc();
                                    ?>
                                    <span class="badge bg-success">Paid: Rs. <?= number_format($bill['total'], 2) ?></span>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center p-5 text-muted">No discharge records found in the archive.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>