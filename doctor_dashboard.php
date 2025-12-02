<?php
session_start();
include 'config/db.php';
include 'includes/header.php';

// Access Control - ensure consistent session keys
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'doctor') {
    header("Location: login.php");
    exit();
}

$doctor_user_id = (int)$_SESSION['user_id'];
$doctor_name = htmlspecialchars($_SESSION['full_name'] ?? 'Doctor');

// Fetch doctor_id from doctors table (doctors.user_id -> doctor_id)
$doctor_data_sql = "SELECT doctor_id FROM doctors WHERE user_id = ?";
$stmt = query($doctor_data_sql, [$doctor_user_id], "i");
$doctor_data = $stmt->get_result()->fetch_assoc();
$doctor_id = isset($doctor_data['doctor_id']) ? (int)$doctor_data['doctor_id'] : 0;

// Confirmed appointments today
$confirmed_count = 0;
if ($doctor_id) {
    $confirmed_sql = "SELECT COUNT(appointment_id) FROM appointments 
                      WHERE doctor_id = ? AND status = 'confirmed' 
                      AND DATE(scheduled_time) = CURDATE()";
    $stmt_confirmed = query($confirmed_sql, [$doctor_id], "i");
    $confirmed_count = (int)$stmt_confirmed->get_result()->fetch_row()[0];
}

// Pending lab/test results assigned by this doctor
$pending_results_count = 0;
if ($doctor_id) {
    $results_sql = "SELECT COUNT(t.test_id) FROM lab_tests t
                    JOIN prescriptions pr ON t.prescription_id = pr.prescription_id
                    WHERE pr.doctor_id = ? AND t.status = 'pending'";
    $stmt_results = query($results_sql, [$doctor_id], "i");
    $pending_results_count = (int)$stmt_results->get_result()->fetch_row()[0];
}

// Recent patients seen by this doctor (via appointments) - show last 25
$patients_sql = "SELECT DISTINCT p.patient_id, u.full_name, p.status AS patient_status,
                        (SELECT MAX(a.scheduled_time) FROM appointments a WHERE a.patient_id = p.patient_id AND a.doctor_id = ?) AS last_appointment
                 FROM patients p
                 JOIN users u ON p.user_id = u.user_id
                 LEFT JOIN appointments ap ON ap.patient_id = p.patient_id AND ap.doctor_id = ?
                 WHERE (ap.doctor_id = ? OR EXISTS (SELECT 1 FROM appointments a2 WHERE a2.patient_id = p.patient_id AND a2.doctor_id = ?))
                 ORDER BY last_appointment DESC
                 LIMIT 50";
$patients_stmt = query($patients_sql, [$doctor_id, $doctor_id, $doctor_id, $doctor_id], "iiii");
$patients = $patients_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
        <h2 class="text-info">Doctor Dashboard</h2>
        <div>
            <span class="me-3">Welcome, <strong> <?php echo $doctor_name; ?></strong></span>
            <a href="logout.php" class="btn btn-sm btn-danger">Logout</a>
        </div>
    </div>

    <p class="lead">Here's your daily overview and recent patients.</p>

    <h3 class="mt-4 mb-3 text-secondary">Daily Overview</h3>
    <div class="row g-4">
        <div class="col-md-6">
            <div class="card bg-primary text-white shadow-sm">
                <div class="card-body">
                    <h1 class="display-3 mb-0"><?php echo $confirmed_count; ?></h1>
                    <p class="h5">Confirmed Appointments Today</p>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card bg-warning text-dark shadow-sm">
                <div class="card-body">
                    <h1 class="display-3 mb-0"><?php echo $pending_results_count; ?></h1>
                    <p class="h5">Pending Lab / Test Results</p>
                </div>
            </div>
        </div>
    </div>

    <h3 class="mt-5 mb-3 text-secondary">Quick Actions</h3>
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <a href="doctor_schedule.php" class="btn btn-success w-100 py-3 shadow-sm">View Today's Schedule</a>
        </div>
        <div class="col-md-4">
            <a href="doctor_view_patient.php" class="btn btn-info w-100 py-3 shadow-sm">Open Patient by ID</a>
        </div>
        <div class="col-md-4">
            <a href="doctor_prescribe_lab.php" class="btn btn-primary w-100 py-3 shadow-sm">Write Prescriptions / Lab Orders</a>
        </div>
    </div>

    <h3 class="mt-4 mb-3 text-secondary">Recent Patients (linked)</h3>
    <div class="card shadow-sm">
        <div class="card-body">
            <?php if (!empty($patients)): ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead><tr><th>Patient</th><th>Status</th><th>Last Visit</th><th>Action</th></tr></thead>
                        <tbody>
                        <?php foreach ($patients as $p): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($p['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($p['patient_status'] ?? 'Unknown'); ?></td>
                                <td><?php echo $p['last_appointment'] ? date('Y-m-d H:i', strtotime($p['last_appointment'])) : '—'; ?></td>
                                <td>
                                    <a href="doctor_view_patient.php?patient_id=<?php echo (int)$p['patient_id']; ?>" class="btn btn-sm btn-outline-primary">Open</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info mb-0">No patients found for your account yet.</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
