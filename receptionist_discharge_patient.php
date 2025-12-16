<?php
session_start();
include 'config/db.php';
include 'includes/header.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'receptionist') {
    header("Location: login.php");
    exit();
}

$success = '';
$error = '';

/* =====================
   DISCHARGE PATIENT
===================== */
if (isset($_POST['discharge'])) {
    $patient_id = (int)$_POST['patient_id'];

    $stmt = $conn->prepare("
        UPDATE patients
        SET status = 'Discharged',
            ward = NULL,
            cabin = NULL,
            bed = NULL
        WHERE patient_id = ?
    ");
    $stmt->bind_param("i", $patient_id);

    if ($stmt->execute()) {
        $success = "Patient discharged successfully.";
    } else {
        $error = "Discharge failed.";
    }
    $stmt->close();
}

/* =====================
   FETCH ADMITTED PATIENTS
===================== */
$patients = $conn->query("
    SELECT p.patient_id, u.full_name, p.ward, p.bed
    FROM patients p
    JOIN users u ON p.user_id = u.user_id
    WHERE p.status = 'Indoor'
");
?>

<div class="container my-5">
    <h2>Discharge Patient</h2>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Patient</th>
                <th>Ward</th>
                <th>Bed</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($p = $patients->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($p['full_name']) ?></td>
                <td><?= $p['ward'] ?></td>
                <td><?= $p['bed'] ?></td>
                <td>
                    <form method="post">
                        <input type="hidden" name="patient_id" value="<?= $p['patient_id'] ?>">
                        <button type="submit" name="discharge" class="btn btn-danger btn-sm">
                            Discharge
                        </button>
                    </form>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>

<?php include 'includes/footer.php'; ?>
