<?php
session_start();
include 'config/db.php';
include 'includes/header.php';

/* =========================
   AUTH
========================= */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header("Location: login.php");
    exit();
}

$doctor_user_id = (int)$_SESSION['user_id'];

/* =========================
   GET DOCTOR ID
========================= */
$stmt = $conn->prepare("SELECT doctor_id FROM doctors WHERE user_id=?");
$stmt->bind_param("i", $doctor_user_id);
$stmt->execute();
$doctor_id = (int)($stmt->get_result()->fetch_assoc()['doctor_id'] ?? 0);
$stmt->close();

$patient_id = (int)($_GET['patient_id'] ?? 0);
if ($patient_id <= 0) {
    echo "<div class='container my-5 alert alert-danger'>Invalid patient</div>";
    include 'includes/footer.php';
    exit();
}

$errors = [];
$success = "";

/* =========================
   ASSIGN LAB TEST
========================= */
if (isset($_POST['assign_lab'])) {
    $test_name = trim($_POST['test_name']);

    if ($test_name === '') {
        $errors[] = "Test name required";
    } else {
        $stmt = $conn->prepare("
            INSERT INTO lab_tests 
            (patient_id, test_name, status, doctor_notified)
            VALUES (?, ?, 'pending', 0)
        ");
        $stmt->bind_param("is", $patient_id, $test_name);
        $stmt->execute();
        $stmt->close();
        $success = "Lab test assigned";
    }
}

/* =========================
   PRESCRIPTION
========================= */
if (isset($_POST['prescribe'])) {
    $medicines = trim($_POST['prescribed_medicines']);
    $notes = trim($_POST['doctor_notes']);

    if ($medicines === '') {
        $errors[] = "Medicines required";
    } else {
        $stmt = $conn->prepare("
            INSERT INTO prescriptions
            (patient_id, doctor_id, prescribed_medicines, doctor_notes)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->bind_param("iiss", $patient_id, $doctor_id, $medicines, $notes);
        $stmt->execute();
        $stmt->close();
        $success = "Prescription saved";
    }
}

/* =========================
   ADMISSION REQUEST
========================= */
if (isset($_POST['suggest_admission'])) {
    $reason = trim($_POST['admission_reason']);
    $dept = trim($_POST['suggested_department']);
    $ward = trim($_POST['suggested_ward']);

    if ($reason === '') {
        $errors[] = "Admission reason required";
    } else {
        $stmt = $conn->prepare("
            INSERT INTO admission_requests
            (patient_id, doctor_id, suggested_ward, suggested_department, doctor_reason, request_status, request_date)
            VALUES (?, ?, ?, ?, ?, 'Pending Reception', NOW())
        ");
        $stmt->bind_param("iisss", $patient_id, $doctor_id, $ward, $dept, $reason);
        $stmt->execute();
        $stmt->close();

        $conn->query("
            UPDATE patients 
            SET admission_reason='$reason'
            WHERE patient_id=$patient_id
        ");

        $success = "Admission request sent to reception";
    }
}

/* =========================
   FETCH PATIENT
========================= */
$stmt = $conn->prepare("
    SELECT p.*, COALESCE(u.full_name, p.name) AS patient_name
    FROM patients p
    LEFT JOIN users u ON p.user_id=u.user_id
    WHERE p.patient_id=?
");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$patient = $stmt->get_result()->fetch_assoc();
$stmt->close();

/* =========================
   LAB TESTS
========================= */
$stmt = $conn->prepare("
    SELECT test_name, status, result
    FROM lab_tests
    WHERE patient_id=?
    ORDER BY test_id DESC
");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$lab_tests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

/* =========================
   PRESCRIPTIONS
========================= */
$stmt = $conn->prepare("
    SELECT prescribed_medicines, doctor_notes
    FROM prescriptions
    WHERE patient_id=?
    ORDER BY prescription_id DESC
");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$prescriptions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<div class="container my-5">

<h3> Patient: <?= htmlspecialchars($patient['patient_name']) ?></h3>

<?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
<?php foreach ($errors as $e): ?><div class="alert alert-danger"><?= $e ?></div><?php endforeach; ?>

<p><strong>Status:</strong> <?= htmlspecialchars($patient['status']) ?></p>

<?php if ($patient['status'] === 'Indoor'): ?>
<p><strong>Ward:</strong> <?= $patient['ward'] ?> |
<strong>Bed:</strong> <?= $patient['bed'] ?></p>
<?php endif; ?>

<hr>

<h5> Lab Tests</h5>
<?php foreach ($lab_tests as $t): ?>
    <div><?= htmlspecialchars($t['test_name']) ?> — <?= $t['status'] ?></div>
<?php endforeach; ?>

<form method="post" class="mt-2">
    <input name="test_name" class="form-control mb-2" placeholder="Test name" required>
    <button name="assign_lab" class="btn btn-primary">Assign Test</button>
</form>

<hr>

<h5> Prescription</h5>
<form method="post">
    <textarea name="prescribed_medicines" class="form-control mb-2" placeholder="Medicines" required></textarea>
    <textarea name="doctor_notes" class="form-control mb-2" placeholder="Doctor notes"></textarea>
    <button name="prescribe" class="btn btn-success">Save Prescription</button>
</form>

<hr>

<h5> Admission Recommendation</h5>
<form method="post">
    <textarea name="admission_reason" class="form-control mb-2" placeholder="Reason"></textarea>
    <input name="suggested_department" class="form-control mb-2" placeholder="Department">
    <input name="suggested_ward" class="form-control mb-2" placeholder="Ward">
    <button name="suggest_admission" class="btn btn-warning">Send Admission Request</button>
</form>

</div>

<?php include 'includes/footer.php'; ?>
