<?php
session_start();
include 'config/db.php';
include 'includes/header.php';

// Access control
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'receptionist') {
    header("Location: login.php");
    exit();
}

$errors = [];
$success = "";


  // CONFIRM ADMISSION

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_submit'])) {

    $patient_id = (int)($_POST['patient_id'] ?? 0);
    $ward  = trim($_POST['ward'] ?? '');
    $cabin = trim($_POST['cabin'] ?? '');
    $bed   = trim($_POST['bed'] ?? '');

    if ($patient_id <= 0) {
        $errors[] = "Invalid patient selected.";
    }
    if ($ward === '' || $bed === '') {
        $errors[] = "Ward and Bed are required.";
    }

    if (empty($errors)) {

        // Update PATIENT 
        $stmt = $conn->prepare("
            UPDATE patients
            SET ward = ?, cabin = ?, bed = ?, status = 'Indoor', patient_type = 'Indoor'
            WHERE patient_id = ?
        ");

        if (!$stmt) {
            $errors[] = "Database error: " . $conn->error;
        } else {
            $stmt->bind_param("sssi", $ward, $cabin, $bed, $patient_id);

            if ($stmt->execute()) {

                //  Update ADMISSION REQUEST 
                $req = $conn->prepare("
                    UPDATE admission_requests
                    SET request_status = 'Accepted'
                    WHERE patient_id = ?
                      AND request_status = 'Pending Reception'
                ");
                if ($req) {
                    $req->bind_param("i", $patient_id);
                    $req->execute();
                    $req->close();
                }

                $success = "Patient admitted successfully.";
            } else {
                $errors[] = "Execution failed: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}


   //FETCH PENDING REQUESTS

$sql = "
SELECT 
    ar.request_id,
    ar.patient_id,
    u.full_name AS patient_name,
    p.age,
    p.gender,
    ar.suggested_department,
    ar.suggested_ward,
    ar.doctor_reason,
    ar.request_date
FROM admission_requests ar
JOIN patients p ON ar.patient_id = p.patient_id
JOIN users u ON p.user_id = u.user_id
SELECT u.full_name AS patient_name
WHERE ar.request_status = 'Pending Reception'
ORDER BY ar.request_date ASC
";

$result = $conn->query($sql);
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
            <?php foreach ($errors as $e): ?>
                <div><?= htmlspecialchars($e) ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <h4>Pending Admission Requests</h4>

    <?php if ($result && $result->num_rows > 0): ?>
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead class="table-light">
                <tr>
                    <th>Patient</th>
                    <th>Age</th>
                    <th>Gender</th>
                    <th>Department</th>
                    <th>Suggested Ward</th>
                    <th>Reason</th>
                    <th>Date</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['patient_name']) ?></td>
                        <td><?= htmlspecialchars($row['age']) ?></td>
                        <td><?= htmlspecialchars($row['gender']) ?></td>
                        <td><?= htmlspecialchars($row['suggested_department']) ?></td>
                        <td><?= htmlspecialchars($row['suggested_ward']) ?></td>
                        <td><?= nl2br(htmlspecialchars($row['doctor_reason'])) ?></td>
                        <td><?= htmlspecialchars($row['request_date']) ?></td>
                        <td>
                            <button type="button"
                                    class="btn btn-sm btn-primary"
                                    onclick="populateAssign(
                                        <?= (int)$row['patient_id'] ?>,
                                        <?= json_encode($row['patient_name']) ?>
                                    )">
                                Assign
                            </button>
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

    <form method="post" id="assignForm" class="card p-3 mt-3">
        <input type="hidden" name="patient_id" id="form_patient_id">

        <div class="mb-3">
            <label>Patient</label>
            <input type="text" id="form_patient_name" class="form-control">
        </div>

        <div class="row">
            <div class="col-md-4 mb-3">
                <label>Ward *</label>
                <input type="text" name="ward" class="form-control" required>
            </div>
            <div class="col-md-4 mb-3">
                <label>Cabin (optional)</label>
                <input type="text" name="cabin" class="form-control">
            </div>
            <div class="col-md-4 mb-3">
                <label>Bed *</label>
                <input type="text" name="bed" class="form-control" required>
            </div>
        </div>

        <button type="submit" name="assign_submit" class="btn btn-success">
            Confirm Admission
        </button>
    </form>
</div>

<script>
function populateAssign(pid, pname) {
    document.getElementById('form_patient_id').value = pid;
    document.getElementById('form_patient_name').value = pname;
    document.getElementById('assignForm')
        .scrollIntoView({ behavior: 'smooth' });
}
</script>

<?php include 'includes/footer.php'; ?>
