<?php
session_start();
include 'config/db.php';
include 'includes/header.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header("Location: login.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];

$stmt = $conn->prepare("SELECT doctor_id FROM doctors WHERE user_id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$doctor_id = (int)$stmt->get_result()->fetch_row()[0];
$stmt->close();

$sql = "
SELECT DISTINCT p.patient_id,
       COALESCE(u.full_name, p.name) AS patient_name,
       p.status
FROM appointments a
JOIN patients p ON a.patient_id=p.patient_id
LEFT JOIN users u ON p.user_id=u.user_id
WHERE a.doctor_id=?
ORDER BY p.patient_id DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$patients = $stmt->get_result();
$stmt->close();
?>

<div class="container my-5">
    <h3> My Patients</h3>

    <table class="table table-bordered mt-3">
        <tr>
            <th>Name</th>
            <th>Status</th>
            <th>Action</th>
        </tr>

        <?php while ($p = $patients->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($p['patient_name']) ?></td>
                <td><?= htmlspecialchars($p['status']) ?></td>
                <td>
                    <a href="doctor_view_patient.php?patient_id=<?= $p['patient_id'] ?>"
                       class="btn btn-sm btn-success">Open</a>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>

    <a href="doctor_dashboard.php" class="btn btn-secondary">Back</a>
</div>

<?php include 'includes/footer.php'; ?>
