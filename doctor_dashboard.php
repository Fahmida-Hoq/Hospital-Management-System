<?php
session_start();
include 'config/db.php';
include 'includes/header.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header("Location: login.php");
    exit();
}

$doctor_user_id = (int)$_SESSION['user_id'];
$doctor_name = htmlspecialchars($_SESSION['full_name'] ?? 'Doctor');

/* ======================
   GET DOCTOR ID
====================== */
$doctor_id = 0;
$stmt = $conn->prepare("SELECT doctor_id FROM doctors WHERE user_id = ?");
if ($stmt) {
    $stmt->bind_param("i", $doctor_user_id);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    $doctor_id = (int)($r['doctor_id'] ?? 0);
    $stmt->close();
}

/* ======================
   DASHBOARD COUNTS
====================== */
$appointments_today = 0;
$pending_labs = 0;
$indoor_patients = 0;
$outdoor_patients = 0;

/* Appointments Today */
$stmt = $conn->prepare("SELECT COUNT(*) FROM appointments WHERE doctor_id=? AND status='confirmed' AND DATE(scheduled_time)=CURDATE()");
if ($stmt) {
    $stmt->bind_param("i", $doctor_id);
    $stmt->execute();
    $appointments_today = (int)$stmt->get_result()->fetch_row()[0];
    $stmt->close();
}

/* Pending Lab Notifications (Fixed to match Dr. Name) */
$stmt = $conn->prepare("SELECT COUNT(*) FROM lab_tests l JOIN patients p ON l.patient_id = p.patient_id WHERE p.referred_by_doctor = ? AND l.status = 'completed'");
if ($stmt) {
    $stmt->bind_param("s", $doctor_name);
    $stmt->execute();
    $pending_labs = (int)$stmt->get_result()->fetch_row()[0];
    $stmt->close();
}

/* Indoor / Outdoor Patients (Fixed logic using Referred Doctor Name) */
$stmt = $conn->prepare("SELECT patient_type, COUNT(*) as total FROM patients WHERE referred_by_doctor = ? GROUP BY patient_type");
if ($stmt) {
    $stmt->bind_param("s", $doctor_name);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        if ($row['patient_type'] === 'Indoor')  $indoor_patients  = $row['total'];
        if ($row['patient_type'] === 'Outdoor') $outdoor_patients = $row['total'];
    }
    $stmt->close();
}

/* ======================
   RECENT PATIENTS
====================== */
$patients = [];
$stmt = $conn->prepare("SELECT patient_id, name, patient_type FROM patients WHERE referred_by_doctor = ? ORDER BY patient_id DESC LIMIT 10");
if ($stmt) {
    $stmt->bind_param("s", $doctor_name);
    $stmt->execute();
    $patients = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
?>

<div class="container my-5">
    <div class="d-flex justify-content-between mb-4">
        <h2>👨‍⚕️ Doctor Dashboard</h2>
        <div>
            <strong><?= $doctor_name ?></strong>
            <a href="logout.php" class="btn btn-sm btn-danger ms-2">Logout</a>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card text-bg-primary shadow-sm border-0">
                <div class="card-body">
                    <p class="mb-1">Appointments Today</p>
                    <h3 class="mb-0"><?= $appointments_today ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-bg-warning shadow-sm border-0">
                <div class="card-body">
                    <p class="mb-1">Lab Reports</p>
                    <h3 class="mb-0"><?= $pending_labs ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-bg-success shadow-sm border-0">
                <div class="card-body">
                    <p class="mb-1">Indoor Patients</p>
                    <h3 class="mb-0"><?= $indoor_patients ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-bg-info shadow-sm border-0 text-white">
                <div class="card-body">
                    <p class="mb-1">Outdoor Patients</p>
                    <h3 class="mb-0"><?= $outdoor_patients ?></h3>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3"><a href="doctor_appointments.php" class="btn btn-outline-primary w-100 py-2">My Appointments</a></div>
        <div class="col-md-3"><a href="doctor_patients.php" class="btn btn-outline-success w-100 py-2">My Patients</a></div>
        <div class="col-md-3"><a href="doctor_lab_notifications.php" class="btn btn-outline-warning w-100 py-2">Lab Notifications</a></div>
        <div class="col-md-3"><a href="doctor_profile.php" class="btn btn-outline-dark w-100 py-2">Edit Profile</a></div>
    </div>
<div class="col-md-4">
    <div class="card border-0 shadow-sm bg-danger text-white p-3">
        <h5>My Indoor Wards</h5>
        <?php 
           $my_indoor = $conn->query("SELECT COUNT(*) FROM admissions WHERE status='Admitted' AND doctor_id='".$_SESSION['user_id']."'")->fetch_row()[0];
        ?>
        <div class="display-6 fw-bold"><?= $my_indoor ?> Patients</div>
        <a href="doctor_indoor_patients.php" class="text-white small mt-2 d-block">Go to Ward →</a>
    </div>
</div>
    <div class="card shadow-sm">
        <div class="card-header bg-white"><h5 class="mb-0">Recent Patients Assigned to You</h5></div>
        <div class="card-body p-0">
            <?php if ($patients): ?>
                <table class="table table-hover mb-0">
                    <thead class="table-light"><tr><th>Name</th><th>Type</th><th>Action</th></tr></thead>
                    <tbody>
                        <?php foreach ($patients as $p): ?>
                            <tr>
                                <td><?= htmlspecialchars($p['name']) ?></td>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars($p['patient_type']) ?></span></td>
                                <td><a href="doctor_view_patient.php?patient_id=<?= $p['patient_id'] ?>" class="btn btn-sm btn-primary px-3">Open</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="p-4 text-center text-muted">No patients yet.</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>