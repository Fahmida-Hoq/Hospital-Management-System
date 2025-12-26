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
$stmt = $conn->prepare("
    SELECT COUNT(*) 
    FROM appointments 
    WHERE doctor_id=? 
      AND status='confirmed' 
      AND DATE(scheduled_time)=CURDATE()
");
if ($stmt) {
    $stmt->bind_param("i", $doctor_id);
    $stmt->execute();
    $appointments_today = (int)$stmt->get_result()->fetch_row()[0];
    $stmt->close();
}

/* Pending Lab Notifications */
$stmt = $conn->prepare("
    SELECT COUNT(*) 
    FROM lab_tests 
    WHERE doctor_id=? 
      AND status='completed' 
      AND doctor_notified=0
");
if ($stmt) {
    $stmt->bind_param("i", $doctor_id);
    $stmt->execute();
    $pending_labs = (int)$stmt->get_result()->fetch_row()[0];
    $stmt->close();
}

/* Indoor / Outdoor Patients */
$stmt = $conn->prepare("
    SELECT p.status, COUNT(*) total
    FROM patients p
    JOIN appointments a ON a.patient_id = p.patient_id
    WHERE a.doctor_id=?
    GROUP BY p.status
");
if ($stmt) {
    $stmt->bind_param("i", $doctor_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        if ($row['status'] === 'Indoor')  $indoor_patients  = $row['total'];
        if ($row['status'] === 'Outdoor') $outdoor_patients = $row['total'];
    }
    $stmt->close();
}

/* ======================
   RECENT PATIENTS
====================== */
$patients = [];
$stmt = $conn->prepare("
    SELECT DISTINCT p.patient_id,
           COALESCE(u.full_name, p.name) AS name,
           p.status
    FROM appointments a
    JOIN patients p ON a.patient_id=p.patient_id
    LEFT JOIN users u ON p.user_id=u.user_id
    WHERE a.doctor_id=?
    ORDER BY p.patient_id DESC
    LIMIT 10
");
if ($stmt) {
    $stmt->bind_param("i", $doctor_id);
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

    <!-- STATS -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card text-bg-primary">
                <div class="card-body">
                    Appointments Today
                    <h3><?= $appointments_today ?></h3>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card text-bg-warning">
                <div class="card-body">
                    Lab Reports
                    <h3><?= $pending_labs ?></h3>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card text-bg-success">
                <div class="card-body">
                    Indoor Patients
                    <h3><?= $indoor_patients ?></h3>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card text-bg-info">
                <div class="card-body">
                    Outdoor Patients
                    <h3><?= $outdoor_patients ?></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- ACTION BUTTONS -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <a href="doctor_appointments.php" class="btn btn-outline-primary w-100">My Appointments</a>
        </div>
        <div class="col-md-3">
            <a href="doctor_patients.php" class="btn btn-outline-success w-100"> My Patients</a>
        </div>
        <div class="col-md-3">
            <a href="doctor_lab_notifications.php" class="btn btn-outline-warning w-100"> Lab Notifications</a>
        </div>
        <div class="col-md-3">
            <a href="doctor_profile.php" class="btn btn-outline-dark w-100">Edit Profile</a>
        </div>
    </div>

    <!-- RECENT PATIENTS -->
    <h4>Recent Patients</h4>
    <?php if ($patients): ?>
        <table class="table table-striped">
            <tr><th>Name</th><th>Status</th><th>Action</th></tr>
            <?php foreach ($patients as $p): ?>
                <tr>
                    <td><?= htmlspecialchars($p['name']) ?></td>
                    <td><?= htmlspecialchars($p['status']) ?></td>
                    <td>
                        <a href="doctor_view_patient.php?patient_id=<?= $p['patient_id'] ?>"
                           class="btn btn-sm btn-primary">Open</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <div class="alert alert-info">No patients yet.</div>
    <?php endif; ?>

</div>

<?php include 'includes/footer.php'; ?>
