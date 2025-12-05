<?php
session_start();
include 'config/db.php';
include 'includes/header.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'receptionist') {
    header("Location: login.php");
    exit();
}

$errors = [];
$success = '';

// Assign ward/cabin/bed (form POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_submit'])) {
    $patient_id = (int)($_POST['patient_id'] ?? 0);
    $ward = trim($_POST['ward'] ?? '');
    $cabin = trim($_POST['cabin'] ?? '');
    $bed = trim($_POST['bed'] ?? '');

    if ($patient_id <= 0) $errors[] = "Invalid patient.";
    if ($ward === '' && $cabin === '' && $bed === '') $errors[] = "Please provide at least one of ward/cabin/bed.";

    if (empty($errors)) {
        // Update patient record
        $u = $conn->prepare("UPDATE patients SET ward = ?, cabin = ?, bed = ?, status = 'Admitted' WHERE patient_id = ?");
        if (!$u) $errors[] = "DB prepare error: " . $conn->error;
        else {
            $u->bind_param("sssi", $ward, $cabin, $bed, $patient_id);
            if ($u->execute()) {
                // Mark admission_requests as accepted if exists
                $p = $conn->prepare("UPDATE admission_requests SET request_status = 'Accepted', processed_by = ?, processed_date = NOW() WHERE patient_id = ? AND request_status = 'Pending Reception'");
                if ($p) {
                    $proc_by = (int)$_SESSION['user_id'];
                    $p->bind_param("ii", $proc_by, $patient_id);
                    $p->execute();
                    $p->close();
                }
                // Log (optional)
                $log = $conn->prepare("INSERT INTO admissions_log (patient_id, action_by, action_type, note) VALUES (?, ?, 'admit', ?)");
                if ($log) {
                    $note = "Admitted to " . ($ward ?: '-') . " / " . ($cabin ?: '-') . " / Bed " . ($bed ?: '-');
                    $log->bind_param("iis", $patient_id, $_SESSION['user_id'], $note);
                    $log->execute();
                    $log->close();
                }
                $success = "Patient admitted successfully.";
            } else {
                $errors[] = "Execute error: " . $u->error;
            }
            $u->close();
        }
    }
}

// Fetch pending admission requests (doctor → receptionist) and also patients with status 'Pending Admission'
$q = "
    SELECT ar.request_id, ar.patient_id, COALESCE(p.name, u.full_name) AS patient_name, p.age, p.gender, ar.suggested_department, ar.suggested_ward, ar.doctor_reason, ar.request_date
    FROM admission_requests ar
    JOIN patients p ON ar.patient_id = p.patient_id
    LEFT JOIN users u ON p.user_id = u.user_id
    WHERE ar.request_status = 'Pending Reception'
    ORDER BY ar.request_date ASC
";
$res = $conn->query($q);
?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Manage Admissions</h2>
        <a href="receptionist_dashboard.php" class="btn btn-secondary">⬅ Back</a>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <?php foreach ($errors as $e) echo "<div>" . htmlspecialchars($e) . "</div>"; ?>
        </div>
    <?php endif; ?>

    <h4>Pending Admission Requests</h4>
    <?php if ($res && $res->num_rows > 0): ?>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr><th>Patient</th><th>Age</th><th>Gender</th><th>Dept</th><th>Ward Suggestion</th><th>Reason</th><th>Requested</th><th>Action</th></tr>
                </thead>
                <tbody>
                    <?php while ($row = $res->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['patient_name']) ?></td>
                            <td><?= htmlspecialchars($row['age']) ?></td>
                            <td><?= htmlspecialchars($row['gender']) ?></td>
                            <td><?= htmlspecialchars($row['suggested_department']) ?></td>
                            <td><?= htmlspecialchars($row['suggested_ward']) ?></td>
                            <td><?= nl2br(htmlspecialchars($row['doctor_reason'])) ?></td>
                            <td><?= htmlspecialchars($row['request_date']) ?></td>
                            <td>
                                <button class="btn btn-sm btn-primary" onclick="populateAssign(<?= (int)$row['patient_id'] ?>,'<?= addslashes($row['patient_name']) ?>')">Assign</button>
                                <a href="patient_profile.php?patient_id=<?= (int)$row['patient_id'] ?>" class="btn btn-sm btn-info">View</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="alert alert-info">No pending admission requests.</div>
    <?php endif; ?>

    <hr>

    <h4>Assign Ward / Cabin / Bed</h4>
    <form method="post" class="card p-3" id="assignForm">
        <input type="hidden" name="patient_id" id="form_patient_id" value="">
        <div class="mb-3">
            <label>Patient</label>
            <input type="text" id="form_patient_name" class="form-control" readonly>
        </div>
        <div class="row">
            <div class="col-md-4 mb-3">
                <label>Ward</label>
                <input type="text" name="ward" class="form-control" required>
            </div>
            <div class="col-md-4 mb-3">
                <label>Cabin (optional)</label>
                <input type="text" name="cabin" class="form-control">
            </div>
            <div class="col-md-4 mb-3">
                <label>Bed</label>
                <input type="text" name="bed" class="form-control" required>
            </div>
        </div>

        <button type="submit" name="assign_submit" class="btn btn-success">Confirm Admission</button>
    </form>
</div>

<script>
function populateAssign(pid, pname) {
    document.getElementById('form_patient_id').value = pid;
    document.getElementById('form_patient_name').value = pname;
    window.scrollTo({ top: document.getElementById('assignForm').offsetTop - 20, behavior: 'smooth' });
}
</script>

<?php include 'includes/footer.php'; ?>
