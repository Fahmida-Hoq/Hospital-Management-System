<?php
session_start();
include 'config/db.php';
include 'includes/header.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header("Location: login.php");
    exit();
}

$doctor_user_id = (int)$_SESSION['user_id'];

/* GET DOCTOR ID */
$res = $conn->query("SELECT doctor_id FROM doctors WHERE user_id=$doctor_user_id");
if (!$res || $res->num_rows === 0) {
    echo "<div class='alert alert-danger'>Doctor profile not found</div>";
    include 'includes/footer.php';
    exit();
}
$doctor_id = (int)$res->fetch_assoc()['doctor_id'];

/* GET PATIENT */
$patient_id = (int)($_GET['patient_id'] ?? 0);
if ($patient_id <= 0) {
    echo "<div class='alert alert-danger'>Invalid patient</div>";
    include 'includes/footer.php';
    exit();
}

$errors = [];
$success = "";

/* =============================
   CHECK EXISTING ADMISSION REQUEST
============================= */
$admission_request = null;
$res = $conn->query("
    SELECT *
    FROM admission_requests
    WHERE patient_id = $patient_id
    AND doctor_id = $doctor_id
    ORDER BY request_id DESC
    LIMIT 1
");
if ($res && $res->num_rows > 0) {
    $admission_request = $res->fetch_assoc();
}

/* =============================
   ASSIGN LAB TEST
============================= */
if (isset($_POST['assign_lab'])) {

    $test_name = trim($_POST['test_name']);
    $appointment_id = (int)$_POST['appointment_id'];

    if ($test_name === '' || $appointment_id <= 0) {
        $errors[] = "Test name and appointment required";
    } else {
        $test_name = $conn->real_escape_string($test_name);

        if ($conn->query("
            INSERT INTO lab_tests
            (appointment_id, doctor_id, test_name, status, doctor_notified)
            VALUES ($appointment_id, $doctor_id, '$test_name', 'pending', 0)
        ")) {
            $success = "Lab test assigned successfully";
        } else {
            $errors[] = "Lab error: " . $conn->error;
        }
    }
}

/* =============================
   PRESCRIPTION
============================= */
if (isset($_POST['prescribe'])) {

    $med = trim($_POST['prescribed_medicines']);
    $notes = trim($_POST['doctor_notes']);

    if ($med === '') {
        $errors[] = "Medicines field is required";
    } else {

        $med = $conn->real_escape_string($med);
        $notes = $conn->real_escape_string($notes);

        $conn->query("
            INSERT INTO prescriptions
            (patient_id, doctor_id, prescribed_medicines, doctor_notes, created_at)
            VALUES ($patient_id, $doctor_id, '$med', '$notes', NOW())
        ");

        $success = "Prescription saved";
    }
}

/* =============================
   ADMISSION REQUEST
============================= */
if (isset($_POST['suggest_admission'])) {

    if ($admission_request && $admission_request['request_status'] === 'Pending Reception') {
        $errors[] = "Admission request already sent and pending";
    } else {

        $reason = trim($_POST['admission_reason']);
        $dept   = trim($_POST['suggested_department']);
        $ward   = trim($_POST['suggested_ward']);
        $date   = $_POST['suggested_admit_date'];

        if ($reason === '' || $dept === '' || $ward === '' || $date === '') {
            $errors[] = "All admission fields are required";
        } else {

            $reason = $conn->real_escape_string($reason);
            $dept   = $conn->real_escape_string($dept);
            $ward   = $conn->real_escape_string($ward);

           $conn->query("
    INSERT INTO admission_requests
    (patient_id, doctor_id, reason, suggested_department, suggested_ward,
     suggested_admit_date, request_status, request_date)
    VALUES (
        $patient_id,
        $doctor_id,
        '$reason',
        '$dept',
        '$ward',
        '$date',
        'Pending Reception',
        NOW()
    )
");

            

            $success = "Admission request sent to reception";
        }
    }
}

/* =============================
   FETCH PATIENT
============================= */
$res = $conn->query("
    SELECT p.*, COALESCE(u.full_name, p.name) AS patient_name
    FROM patients p
    LEFT JOIN users u ON p.user_id = u.user_id
    WHERE p.patient_id = $patient_id
");
if (!$res || $res->num_rows === 0) {
    echo "<div class='alert alert-danger'>Patient not found</div>";
    include 'includes/footer.php';
    exit();
}
$patient = $res->fetch_assoc();

/* =============================
   FETCH LAB RESULTS
============================= */
$lab_tests = [];
$res = $conn->query("
    SELECT lt.test_name, lt.status, lt.result
    FROM lab_tests lt
    JOIN appointments a ON lt.appointment_id = a.appointment_id
    WHERE a.patient_id = $patient_id
      AND lt.doctor_id = $doctor_id
    ORDER BY lt.test_id DESC
");
if ($res) {
    $lab_tests = $res->fetch_all(MYSQLI_ASSOC);
}
?>

<div class="container my-5">

<h4>Patient: <?= htmlspecialchars($patient['patient_name']) ?></h4>
<p><strong>Patient Type:</strong> <?= htmlspecialchars($patient['patient_type'] ?? 'Outdoor') ?></p>

<?php if ($success): ?>
<div class="alert alert-success"><?= $success ?></div>
<?php endif; ?>

<?php foreach ($errors as $e): ?>
<div class="alert alert-danger"><?= $e ?></div>
<?php endforeach; ?>

<?php if ($admission_request): ?>
<div class="alert alert-info">
    <strong>Admission Status:</strong>
    <?= htmlspecialchars($admission_request['request_status']) ?><br>
    <strong>Suggested Date:</strong>
    <?= htmlspecialchars($admission_request['suggested_admit_date']) ?>
</div>
<?php endif; ?>

<hr>

<h5>Lab Results</h5>
<?php if (!$lab_tests): ?>
<div class="text-muted">No lab tests yet</div>
<?php endif; ?>

<?php foreach ($lab_tests as $t): ?>
<div class="mb-2">
    <strong><?= htmlspecialchars($t['test_name']) ?></strong> —
    <?= htmlspecialchars($t['status']) ?>
    <?php if ($t['status'] === 'completed'): ?>
        <br><strong>Result:</strong> <?= htmlspecialchars($t['result']) ?>
    <?php endif; ?>
</div>
<?php endforeach; ?>

<hr>

<h5>Assign Lab Test</h5>
<form method="post">
    <select name="appointment_id" class="form-control mb-2" required>
        <option value="">Select appointment</option>
        <?php
        $res = $conn->query("
            SELECT appointment_id
            FROM appointments
            WHERE patient_id=$patient_id AND doctor_id=$doctor_id
        ");
        while ($row = $res->fetch_assoc()):
        ?>
            <option value="<?= $row['appointment_id'] ?>">
                Appointment #<?= $row['appointment_id'] ?>
            </option>
        <?php endwhile; ?>
    </select>

    <input name="test_name" class="form-control mb-2"
           placeholder="CBC / X-Ray" required>

    <button name="assign_lab" class="btn btn-primary">Assign</button>
</form>

<hr>

<h5>Prescription</h5>
<form method="post">
    <textarea name="prescribed_medicines" class="form-control mb-2"
              placeholder="Medicines" required></textarea>

    <textarea name="doctor_notes" class="form-control mb-2"
              placeholder="Doctor notes"></textarea>

    <button name="prescribe" class="btn btn-success">Save Prescription</button>
</form>

<hr>

<h5>Admission Recommendation</h5>

<?php if ($admission_request && $admission_request['request_status'] === 'Pending Reception'): ?>
<div class="alert alert-warning">
    Admission request already sent. Waiting for reception approval.
</div>
<?php elseif (($patient['patient_type'] ?? '') === 'Indoor'): ?>
<div class="alert alert-success">
    Patient already admitted.
</div>
<?php else: ?>
<form method="post">
    <textarea name="admission_reason" class="form-control mb-2"
              placeholder="Reason for admission" required></textarea>

    <input name="suggested_department" class="form-control mb-2"
           placeholder="Department" required>

    <input name="suggested_ward" class="form-control mb-2"
           placeholder="Suggested ward" required>

    <label>Suggested Admission Date</label>
    <input type="date" name="suggested_admit_date"
           class="form-control mb-3" required>

    <button name="suggest_admission" class="btn btn-warning">
        Send Admission Request
    </button>
</form>
<?php endif; ?>

</div>

<?php include 'includes/footer.php'; ?>
