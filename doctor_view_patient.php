<?php
// doctor_view_patient.php
session_start();
include 'config/db.php';
include 'includes/header.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'doctor') {
    header("Location: login.php");
    exit();
}

$doctor_user_id = (int)$_SESSION['user_id'];

// get doctor_id
$stmt = $conn->prepare("SELECT doctor_id FROM doctors WHERE user_id = ?");
$stmt->bind_param("i", $doctor_user_id);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
$doctor_id = (int)($res['doctor_id'] ?? 0);
$stmt->close();

$patient_id = (int)($_GET['patient_id'] ?? 0);
if ($patient_id <= 0) {
    echo "<div class='container my-5'><div class='alert alert-danger'>Invalid patient ID.</div></div>";
    include 'includes/footer.php';
    exit();
}

$errors = [];
$success = '';

// 1) Assign lab test
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_lab'])) {
    $test_name = trim($_POST['test_name'] ?? '');
    $test_desc = trim($_POST['test_description'] ?? '');
    if ($test_name === '') $errors[] = "Test name required.";
    else {
        $ins = $conn->prepare("INSERT INTO lab_tests (patient_id, doctor_id, test_name, test_description, status, date_requested, doctor_notified) VALUES (?, ?, ?, ?, 'pending', NOW(), 0)");
        if ($ins) {
            $ins->bind_param("iiss", $patient_id, $doctor_id, $test_name, $test_desc);
            if ($ins->execute()) $success = "Lab test assigned.";
            else $errors[] = "Execute lab insert error: " . $ins->error;
            $ins->close();
        } else $errors[] = "Prepare error: " . $conn->error;
    }
}

// 2) Prescribe medicine
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['prescribe'])) {
    $medicine = trim($_POST['medicine'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    $ins = $conn->prepare("INSERT INTO prescriptions (patient_id, doctor_id, medicine, notes, date_given) VALUES (?, ?, ?, ?, NOW())");
    if ($ins) {
        $ins->bind_param("iiss", $patient_id, $doctor_id, $medicine, $notes);
        if ($ins->execute()) $success = "Prescription saved.";
        else $errors[] = "Execute prescription error: " . $ins->error;
        $ins->close();
    } else $errors[] = "Prepare error: " . $conn->error;
}

// 3) Suggest admission (doctor suggests to receptionist)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['suggest_admission'])) {
    $reason = trim($_POST['admission_reason'] ?? '');
    $suggested_dept = trim($_POST['suggested_department'] ?? '');
    $suggested_ward = trim($_POST['suggested_ward'] ?? '');
    if ($reason === '') $errors[] = "Admission reason required.";
    else {
        $ins = $conn->prepare("INSERT INTO admission_requests (patient_id, doctor_id, suggested_ward, suggested_department, doctor_reason, request_status, request_date) VALUES (?, ?, ?, ?, ?, 'Pending Reception', NOW())");
        if ($ins) {
            $ins->bind_param("iisss", $patient_id, $doctor_id, $suggested_ward, $suggested_dept, $reason);
            if ($ins->execute()) {
                // update patient admission reason
                $u = $conn->prepare("UPDATE patients SET admission_reason = ? WHERE patient_id = ?");
                if ($u) {
                    $u->bind_param("si", $reason, $patient_id);
                    $u->execute();
                    $u->close();
                }
                $success = "Admission suggestion sent to Reception.";
            } else $errors[] = "Execute admission request error: " . $ins->error;
            $ins->close();
        } else $errors[] = "Prepare error: " . $conn->error;
    }
}

// 4) Discharge (only if patient is Outdoor or doctor chooses)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['discharge'])) {
    $notes = trim($_POST['discharge_notes'] ?? '');
    $u = $conn->prepare("UPDATE patients SET status = 'Discharged' WHERE patient_id = ?");
    if ($u) {
        $u->bind_param("i", $patient_id);
        if ($u->execute()) {
            // log
            $log = $conn->prepare("INSERT INTO admissions_log (patient_id, action_by, action_type, note) VALUES (?, ?, 'discharge', ?)");
            if ($log) {
                $note = $notes ?: 'Discharged by doctor';
                $log->bind_param("iis", $patient_id, $_SESSION['user_id'], $note);
                $log->execute();
                $log->close();
            }
            $success = "Patient discharged.";
        } else $errors[] = "Execute discharge error: " . $u->error;
        $u->close();
    } else $errors[] = "Prepare error: " . $conn->error;
}

// Fetch patient details
$stmt = $conn->prepare("SELECT p.*, COALESCE(u.full_name, p.name) AS display_name, u.email FROM patients p LEFT JOIN users u ON p.user_id = u.user_id WHERE p.patient_id = ?");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$patient = $stmt->get_result()->fetch_assoc();
$stmt->close();

// fetch lab tests for this patient
$tstmt = $conn->prepare("SELECT * FROM lab_tests WHERE patient_id = ? ORDER BY date_requested DESC");
$tstmt->bind_param("i", $patient_id);
$tstmt->execute();
$lab_tests = $tstmt->get_result()->fetch_all(MYSQLI_ASSOC);
$tstmt->close();

// fetch prescriptions
$pstmt = $conn->prepare("SELECT * FROM prescriptions WHERE patient_id = ? ORDER BY date_given DESC");
$pstmt->bind_param("i", $patient_id);
$pstmt->execute();
$prescriptions = $pstmt->get_result()->fetch_all(MYSQLI_ASSOC);
$pstmt->close();

// Doctor notifications: new completed tests where doctor_notified = 0
$notify_list = [];
$nstmt = $conn->prepare("SELECT test_id, test_name, report_file FROM lab_tests WHERE doctor_id = ? AND status = 'completed' AND doctor_notified = 0");
$nstmt->bind_param("i", $doctor_id);
$nstmt->execute();
$notify_list = $nstmt->get_result()->fetch_all(MYSQLI_ASSOC);
$nstmt->close();

// After fetching, mark them as notified (so doctor sees them once)
if (!empty($notify_list)) {
    $ids = array_column($notify_list, 'test_id');
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $types = str_repeat('i', count($ids));
    $sql = "UPDATE lab_tests SET doctor_notified = 1 WHERE test_id IN ($placeholders)";
    $up = $conn->prepare($sql);
    if ($up) {
        // bind params dynamically
        $up->bind_param($types, ...$ids);
        $up->execute();
        $up->close();
    }
}
?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center">
        <h3>Patient: <?= htmlspecialchars($patient['display_name'] ?? 'N/A') ?></h3>
        <a href="doctor_dashboard.php" class="btn btn-secondary">Back</a>
    </div>

    <?php if ($success) echo "<div class='alert alert-success mt-3'>".htmlspecialchars($success)."</div>"; ?>
    <?php if (!empty($errors)) { echo "<div class='alert alert-danger mt-3'>"; foreach ($errors as $e) echo "<div>".htmlspecialchars($e)."</div>"; echo "</div>"; } ?>

    <?php if (!empty($notify_list)): ?>
        <div class="alert alert-info mt-3">
            <strong>New lab reports ready:</strong>
            <ul>
                <?php foreach ($notify_list as $n): ?>
                    <li><?= htmlspecialchars($n['test_name']) ?> — <a href="<?= htmlspecialchars($n['report_file']) ?>" target="_blank">View report</a></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="row mt-3">
        <div class="col-md-7">
            <div class="card p-3 mb-3">
                <h5>Patient Info</h5>
                <p><strong>Type:</strong> <?= htmlspecialchars($patient['patient_type'] ?? '') ?></p>
                <p><strong>Status:</strong> <?= htmlspecialchars($patient['status'] ?? '') ?></p>
                <p><strong>Age:</strong> <?= htmlspecialchars($patient['age'] ?? '') ?> — <strong>Phone:</strong> <?= htmlspecialchars($patient['phone'] ?? '') ?></p>
                <p><strong>Ward/Cabin/Bed:</strong> <?= htmlspecialchars($patient['ward'] ?? '') ?> / <?= htmlspecialchars($patient['cabin'] ?? '') ?> / <?= htmlspecialchars($patient['bed'] ?? '') ?></p>
                <p><strong>Admission Reason:</strong> <?= nl2br(htmlspecialchars($patient['admission_reason'] ?? '')) ?></p>
            </div>

            <div class="card p-3 mb-3">
                <h5>Lab Tests</h5>
                <?php if (empty($lab_tests)): ?>
                    <div class="text-muted">No lab tests assigned.</div>
                <?php else: ?>
                    <?php foreach ($lab_tests as $t): ?>
                        <div class="border p-2 mb-2">
                            <strong><?= htmlspecialchars($t['test_name']) ?></strong><br>
                            <small class="text-muted">Status: <?= htmlspecialchars($t['status']) ?> — Requested: <?= htmlspecialchars($t['date_requested']) ?></small>
                            <?php if (!empty($t['report_file'])): ?>
                                <div class="mt-2"><a href="<?= htmlspecialchars($t['report_file']) ?>" target="_blank">View Report</a></div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <hr>
                <h6>Assign New Lab Test</h6>
                <form method="post">
                    <div class="mb-2"><input name="test_name" class="form-control" placeholder="Test name (e.g. CBC, X-Ray)" required></div>
                    <div class="mb-2"><textarea name="test_description" class="form-control" placeholder="Notes for lab technician"></textarea></div>
                    <button name="assign_lab" class="btn btn-primary">Assign Lab Test</button>
                </form>
            </div>

            <div class="card p-3 mb-3">
                <h5>Prescriptions</h5>
                <?php if (empty($prescriptions)) echo "<div class='text-muted'>No prescriptions yet.</div>"; ?>
                <?php foreach ($prescriptions as $pr): ?>
                    <div class="border p-2 mb-2">
                        <div><strong>Given:</strong> <?= htmlspecialchars($pr['date_given']) ?></div>
                        <div><?= nl2br(htmlspecialchars($pr['medicine'])) ?></div>
                        <div class="small text-muted"><?= nl2br(htmlspecialchars($pr['notes'])) ?></div>
                    </div>
                <?php endforeach; ?>

                <hr>
                <h6>New Prescription</h6>
                <form method="post">
                    <div class="mb-2"><textarea name="medicine" class="form-control" placeholder="Medicine details"></textarea></div>
                    <div class="mb-2"><textarea name="notes" class="form-control" placeholder="Notes"></textarea></div>
                    <button name="prescribe" class="btn btn-success">Save Prescription</button>
                </form>
            </div>
        </div>

        <div class="col-md-5">
            <div class="card p-3 mb-3">
                <h5>Admission</h5>
                <form method="post">
                    <div class="mb-2"><textarea name="admission_reason" class="form-control" placeholder="Reason for admission (required)"></textarea></div>
                    <div class="mb-2">
                        <select name="suggested_department" class="form-select mb-2">
                            <option value="">Select Department</option>
                            <option>Cardiology</option>
                            <option>Neurology</option>
                            <option>Pathology</option>
                            <option>General Medicine</option>
                            <option>Surgery</option>
                        </select>
                        <select name="suggested_ward" class="form-select">
                            <option value="">Select Ward Type</option>
                            <option>General Ward</option>
                            <option>Semi-Private Cabin</option>
                            <option>Private Cabin</option>
                            <option>ICU</option>
                        </select>
                    </div>
                    <button name="suggest_admission" class="btn btn-warning w-100">Send Admission Request</button>
                </form>
            </div>

            <div class="card p-3">
                <h5>Discharge</h5>
                <?php if ($patient['patient_type'] === 'Outdoor'): ?>
                    <form method="post">
                        <div class="mb-2"><textarea name="discharge_notes" class="form-control" placeholder="Discharge notes (optional)"></textarea></div>
                        <button name="discharge" class="btn btn-danger w-100">Discharge Patient</button>
                    </form>
                <?php else: ?>
                    <div class="text-muted">Indoor patients should be discharged by Reception after finalization.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
