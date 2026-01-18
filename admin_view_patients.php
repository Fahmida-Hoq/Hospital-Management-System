<?php
session_start();
include 'config/db.php';
include 'includes/header.php';

// Security check: Only Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php"); exit();
}

/** * MASTER QUERY: Connects Admissions, Patients, Doctors, and Users
 * This matches your exact database structure.
 */
$sql = "SELECT a.*, p.name as p_name, u.full_name as dr_name, b.bed_number, b.ward_name 
        FROM admissions a 
        JOIN patients p ON a.patient_id = p.patient_id 
        JOIN beds b ON a.bed_id = b.bed_id 
        JOIN doctors d ON a.doctor_id = d.doctor_id 
        JOIN users u ON d.user_id = u.user_id
        WHERE a.status = 'Admitted'
        ORDER BY a.admission_date DESC";

$result = $conn->query($sql);
?>

<div class="container-fluid my-5 px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0">Admin: Patient Records</h2>
            <p class="text-muted small">Maintaining Indoor Admissions & Billing Oversight</p>
        </div>
        <a href="admin_dashboard.php" class="btn btn-dark btn-sm shadow-sm">Back to Dashboard</a>
    </div>

    <div class="table-responsive bg-white shadow-sm rounded border">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-light">
                <tr class="text-secondary small text-uppercase">
                    <th class="ps-3">Bed / Ward</th>
                    <th>Patient Name & ID</th>
                    <th>Guardian Info</th>
                    <th>Supervising Doctor</th>
                    <th>Admitted Since</th>
                    <th>Status</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td class="ps-3 fw-bold">
                            <?= htmlspecialchars($row['ward_name']) ?> - B<?= htmlspecialchars($row['bed_number']) ?>
                        </td>
                        <td>
                            <strong><?= htmlspecialchars($row['p_name']) ?></strong><br>
                            <small class="text-muted">ID: <?= $row['patient_id'] ?></small>
                        </td>
                        <td>
                            <small class="d-block"><strong><?= htmlspecialchars($row['guardian_name'] ?? 'N/A') ?></strong></small>
                            <small class="text-muted"><?= htmlspecialchars($row['guardian_phone'] ?? '') ?></small>
                        </td>
                        <td class="text-primary fw-bold">
                             <?= htmlspecialchars($row['dr_name']) ?>
                        </td>
                        <td><?= date('d M, Y', strtotime($row['admission_date'])) ?></td>
                        <td><span class="badge bg-success">Admitted</span></td>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm">
                                <a href="generate_bill.php?adm_id=<?= $row['admission_id'] ?>" class="btn btn-outline-danger">
                                    Bill/Discharge
                                </a>
                                <a href="indoor_patient_invoice.php?adm_id=<?= $row['admission_id'] ?>" target="_blank" class="btn btn-outline-dark">
                                    <i class="fas fa-print">Print Invoice</i>
                                </a>
                                 <a href="delete_admission.php?adm_id=<?= $row['admission_id'] ?>&bed_id=<?= $row['bed_id'] ?>" 
           class="btn btn-danger" 
           onclick="return confirm('Are you sure you want to delete this admission record? This will also free the bed.')">
            <i class="fas fa-trash"></i> Delete
        </a>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center py-5">
                            <div class="alert alert-info d-inline-block">
                                No active indoor admissions found matching the current database structure.
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>