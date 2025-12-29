<?php
session_start();
include 'config/db.php';
include 'includes/header.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header("Location: login.php");
    exit();
}

$doctor_name = $_SESSION['full_name'];

// Fetch all Indoor Patients for this doctor
$indoor_res = $conn->query("SELECT * FROM patients WHERE referred_by_doctor = '$doctor_name' AND patient_type = 'Indoor' ORDER BY name ASC");

// Fetch all Outdoor Patients for this doctor
$outdoor_res = $conn->query("SELECT * FROM patients WHERE referred_by_doctor = '$doctor_name' AND patient_type = 'Outdoor' ORDER BY name ASC");
?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3><i class="fas fa-user-injured me-2"></i> My Patient List</h3>
        <a href="doctor_dashboard.php" class="btn btn-secondary btn-sm">Back to Dashboard</a>
    </div>

    <ul class="nav nav-tabs mb-3" id="patientTabs" role="tablist">
        <li class="nav-item">
            <button class="nav-link active" id="indoor-tab" data-bs-toggle="tab" data-bs-target="#indoor" type="button">Indoor (In-Patients)</button>
        </li>
        <li class="nav-item">
            <button class="nav-link" id="outdoor-tab" data-bs-toggle="tab" data-bs-target="#outdoor" type="button">Outdoor (Out-Patients)</button>
        </li>
    </ul>

    <div class="tab-content border p-3 rounded bg-white shadow-sm">
        <div class="tab-pane fade show active" id="indoor">
            <table class="table table-hover">
                <thead><tr><th>Name</th><th>Ward/Bed</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php while($p = $indoor_res->fetch_assoc()): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($p['name']) ?></strong></td>
                        <td><?= $p['ward'] ?> - <?= $p['bed'] ?></td>
                        <td><span class="badge bg-info"><?= $p['status'] ?></span></td>
                        <td><a href="doctor_view_patient.php?patient_id=<?= $p['patient_id'] ?>" class="btn btn-sm btn-primary">Medical Profile</a></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <div class="tab-pane fade" id="outdoor">
            <table class="table table-hover">
                <thead><tr><th>Name</th><th>Phone</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php while($p = $outdoor_res->fetch_assoc()): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($p['name']) ?></strong></td>
                        <td><?= $p['phone'] ?? 'N/A' ?></td>
                        <td><span class="badge bg-secondary"><?= $p['status'] ?></span></td>
                        <td><a href="doctor_view_patient.php?patient_id=<?= $p['patient_id'] ?>" class="btn btn-sm btn-primary">Medical Profile</a></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>