<?php
session_start();
include 'config/db.php';
include 'includes/header.php';

// Security Check: Only allow receptionists
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'receptionist') {
    header("Location: login.php"); 
    exit();
}

/** * LOGIC EXPLANATION:
 * We join 'admission_requests' with 'patients' to get the person to be admitted.
 * We join 'doctors' and 'users' to get the name of the doctor who made the request.
 * We filter by 'Pending Reception' so the receptionist only sees new work.
 */
$query = "SELECT 
            ar.*, 
            p.name AS patient_name, 
            p.phone AS patient_phone, 
            u.full_name AS doctor_name 
          FROM admission_requests ar 
          JOIN patients p ON ar.patient_id = p.patient_id 
          JOIN doctors d ON ar.doctor_id = d.doctor_id
          JOIN users u ON d.user_id = u.user_id
          WHERE ar.request_status LIKE '%Pending Reception%'
          ORDER BY ar.request_date DESC";

$res = $conn->query($query);

if (!$res) {
    die("<div class='container my-5'><div class='alert alert-danger shadow-sm'>
            <h4 class='alert-heading'><i class='fas fa-exclamation-triangle me-2'></i>Database Error</h4>
            <p class='mb-0'>" . $conn->error . "</p>
         </div></div>");
}
?>

<div class="container-fluid px-4 my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-uppercase mb-0">
                <i class="fas fa-hospital-alt me-2 text-primary"></i>Admission Work-Queue
            </h3>
            <p class="text-muted small mb-0">Reviewing doctor recommendations for inpatient admission</p>
        </div>
        <div class="text-end">
            <span class="badge bg-danger fs-6 shadow-sm">
                <i class="fas fa-bell me-1"></i> <?= $res->num_rows ?> Pending Requests
            </span>
        </div>
    </div>

    <?php if ($res->num_rows == 0): ?>
        <div class="card p-5 text-center shadow-sm border-0" style="border-radius: 15px;">
            <div class="card-body">
                <i class="fas fa-clipboard-check fa-4x text-success mb-3"></i>
                <h4 class="fw-bold">Queue is Empty</h4>
                <p class="text-muted">No pending admission requests from doctors at the moment.</p>
                <a href="reception_dashboard.php" class="btn btn-outline-primary">Back to Dashboard</a>
            </div>
        </div>
    <?php else: ?>
        <div class="card border-0 shadow-sm" style="border-radius: 15px; overflow: hidden;">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-dark text-white">
                        <tr>
                            <th class="ps-4 py-3">Patient Information</th>
                            <th class="py-3">Referring Doctor</th>
                            <th class="py-3">Target Ward/Dept</th>
                            <th class="py-3">Reason for Admission</th>
                            <th class="text-center py-3">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $res->fetch_assoc()): ?>
                        <tr>
                            <td class="ps-4">
                                <div class="fw-bold text-dark"><?= htmlspecialchars($row['patient_name']) ?></div>
                                <div class="text-muted small"><i class="fas fa-phone-alt me-1"></i><?= htmlspecialchars($row['patient_phone']) ?></div>
                            </td>
                            
                            <td>
                                <div class="fw-bold"><i class="fas fa-user-md text-primary me-1"></i> <?= htmlspecialchars($row['doctor_name']) ?></div>
                                <div class="badge bg-light text-dark border small fw-normal">
                                    <?= htmlspecialchars($row['suggested_department']) ?>
                                </div>
                            </td>

                            <td>
                                <span class="badge bg-info text-dark rounded-pill px-3">
                                    <i class="fas fa-bed me-1"></i><?= htmlspecialchars($row['suggested_ward']) ?>
                                </span>
                                <div class="text-muted x-small mt-1" style="font-size: 0.75rem;">
                                    Date: <?= date('d M, Y', strtotime($row['suggested_admit_date'])) ?>
                                </div>
                            </td>

                            <td style="max-width: 250px;">
                                <p class="text-muted mb-0 small text-truncate-2" title="<?= htmlspecialchars($row['reason']) ?>">
                                    <?= htmlspecialchars($row['reason']) ?>
                                </p>
                            </td>

                            <td class="text-center">
                                <a href="admit_patient_form.php?request_id=<?= $row['request_id'] ?>&patient_id=<?= $row['patient_id'] ?>" 
                                   class="btn btn-primary btn-sm px-4 rounded-pill shadow-sm">
                                    Allocate Bed <i class="fas fa-arrow-right ms-1"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
    /* Styling to handle long text nicely */
    .text-truncate-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;  
        overflow: hidden;
    }
</style>

<?php include 'includes/footer.php'; ?>