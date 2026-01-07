<?php
session_start();
include 'config/db.php';
include 'includes/header.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'receptionist') {
    header("Location: login.php"); 
    exit();
}


$query = "SELECT ar.*, p.name, p.phone 
          FROM admission_requests ar 
          JOIN patients p ON ar.patient_id = p.patient_id 
          WHERE ar.request_status LIKE '%Pending Reception%'";

$res = $conn->query($query);


if (!$res) {
    die("<div class='container my-5'><div class='alert alert-danger shadow-sm'>
            <h4 class='alert-heading'><i class='fas fa-exclamation-triangle me-2'></i>Database Query Failed</h4>
            <p class='mb-0'><strong>Error:</strong> " . $conn->error . "</p>
         </div></div>");
}
?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-uppercase mb-0">
                <i class="fas fa-hospital-user me-2 text-primary"></i>Pending Admission Requests
            </h3>
            <p class="text-muted small mb-0">Doctor's suggestions awaiting processing</p>
        </div>
        <span class="badge bg-danger p-2 shadow-sm">
            <?= $res->num_rows ?> New Requests Found
        </span>
    </div>

    <?php if ($res->num_rows == 0): ?>
        <div class="card p-5 text-center shadow-sm border-0" style="border-radius: 15px;">
            <div class="card-body">
                <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
                <h4 class="fw-bold">All Caught Up!</h4>
                <p class="text-muted">No pending admission requests found at this time.</p>
            </div>
        </div>
    <?php else: ?>
        <div class="card border-0 shadow-sm" style="border-radius: 15px; overflow: hidden;">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-dark text-white">
                        <tr>
                            <th class="ps-4 py-3">Patient Name</th>
                            <th class="py-3">Suggested Ward</th>
                            <th class="py-3">Reason for Admission</th>
                            <th class="text-center py-3">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $res->fetch_assoc()): ?>
                        <tr>
                            <td class="fw-bold ps-4">
                                <?= htmlspecialchars($row['name']) ?>
                                <br><small class="text-muted"><?= htmlspecialchars($row['phone']) ?></small>
                            </td>
                            <td>
                                <span class="badge bg-info text-dark rounded-pill px-3">
                                    <?= htmlspecialchars($row['suggested_ward']) ?>
                                </span>
                            </td>
                            <td>
                                <small class="text-muted"><?= htmlspecialchars($row['reason']) ?></small>
                            </td>
                            <td class="text-center">
                                <a href="admit_patient_form.php?request_id=<?= $row['request_id'] ?>" class="btn btn-primary btn-sm px-4 rounded-pill shadow-sm">
                                    Process Admission
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

<?php include 'includes/footer.php'; ?>