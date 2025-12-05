<?php
session_start();
include 'config/db.php';
include 'includes/header.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'doctor') {
    header("Location: login.php");
    exit();
}

$doctor_user_id = (int)$_SESSION['user_id'];
$doctor_name = htmlspecialchars($_SESSION['full_name'] ?? 'Doctor');

// get doctor_id
$doctor_id = 0;
$stmt = $conn->prepare("SELECT doctor_id FROM doctors WHERE user_id = ?");
if ($stmt) {
    $stmt->bind_param("i", $doctor_user_id);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    $doctor_id = (int)($r['doctor_id'] ?? 0);
    $stmt->close();
}

// confirmed appointments today
$confirmed_count = 0;
if ($doctor_id) {
    $stmt = $conn->prepare("SELECT COUNT(appointment_id) FROM appointments WHERE doctor_id = ? AND status = 'confirmed' AND DATE(scheduled_time) = CURDATE()");
    if ($stmt) {
        $stmt->bind_param("i", $doctor_id);
        $stmt->execute();
        $confirmed_count = (int)$stmt->get_result()->fetch_row()[0];
        $stmt->close();
    }
}

// pending lab/test results
$pending_results_count = 0;
if ($doctor_id) {
    $stmt = $conn->prepare("SELECT COUNT(t.test_id) FROM lab_tests t JOIN prescriptions pr ON t.prescription_id = pr.prescription_id WHERE pr.doctor_id = ? AND t.status = 'pending'");
    if ($stmt) {
        $stmt->bind_param("i", $doctor_id);
        $stmt->execute();
        $pending_results_count = (int)$stmt->get_result()->fetch_row()[0];
        $stmt->close();
    }
}

// recent patients (limit 50) who had appointments with this doctor or exist in patients table (safe fallback)
$patients = [];
if ($doctor_id) {
    $sql = "SELECT DISTINCT p.patient_id, COALESCE(u.full_name, p.name) AS full_name, p.status AS patient_status
            FROM patients p
            LEFT JOIN users u ON p.user_id = u.user_id
            LEFT JOIN appointments a ON a.patient_id = p.patient_id AND a.doctor_id = ?
            WHERE a.doctor_id = ? OR a.doctor_id IS NULL
            ORDER BY p.patient_id DESC
            LIMIT 50";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("ii", $doctor_id, $doctor_id);
        $stmt->execute();
        $patients = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
}
?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
        <h2 class="text-info">Doctor Dashboard</h2>
        <div>
            <span class="me-3">Welcome, <strong> <?= $doctor_name ?></strong></span>
            <a href="logout.php" class="btn btn-sm btn-danger">Logout</a>
        </div>
    </div>

    <p class="lead">Here's your daily overview.</p>

    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h1 class="display-4 mb-0"><?= $confirmed_count ?></h1>
                    <p>Confirmed Appointments Today</p>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card bg-warning text-dark">
                <div class="card-body">
                    <h1 class="display-4 mb-0"><?= $pending_results_count ?></h1>
                    <p>Pending Lab / Test Results</p>
                </div>
            </div>
        </div>
    </div>

    <h4>Recent Patients</h4>
    <div class="card shadow-sm">
        <div class="card-body">
            <?php if (!empty($patients)): ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead><tr><th>Patient</th><th>Status</th><th>Action</th></tr></thead>
                        <tbody>
                            <?php foreach ($patients as $p): ?>
                                <tr>
                                    <td><?= htmlspecialchars($p['full_name']) ?></td>
                                    <td><?= htmlspecialchars($p['patient_status'] ?? 'Pending') ?></td>
                                    <td><a class="btn btn-sm btn-outline-primary" href="doctor_view_patient.php?patient_id=<?= (int)$p['patient_id'] ?>">Open</a></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info mb-0">No patients found.</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
