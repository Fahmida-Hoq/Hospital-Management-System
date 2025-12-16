<?php
session_start();
include 'config/db.php';
include 'includes/header.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header("Location: login.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];

/* get doctor_id */
$stmt = $conn->prepare("SELECT doctor_id FROM doctors WHERE user_id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$doctor_id = (int)$stmt->get_result()->fetch_row()[0];
$stmt->close();

$sql = "
SELECT a.appointment_id, a.scheduled_time, a.status,
       COALESCE(u.full_name, p.name) AS patient_name, p.patient_id
FROM appointments a
JOIN patients p ON a.patient_id=p.patient_id
LEFT JOIN users u ON p.user_id=u.user_id
WHERE a.doctor_id=?
ORDER BY a.scheduled_time DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$appointments = $stmt->get_result();
$stmt->close();
?>

<div class="container my-5">
    <h3>📅 My Appointments</h3>

    <table class="table table-striped mt-3">
        <tr>
            <th>Patient</th>
            <th>Date & Time</th>
            <th>Status</th>
            <th>Action</th>
        </tr>

        <?php while ($a = $appointments->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($a['patient_name']) ?></td>
                <td><?= htmlspecialchars($a['scheduled_time']) ?></td>
                <td><?= htmlspecialchars($a['status']) ?></td>
                <td>
                    <a class="btn btn-sm btn-primary"
                       href="doctor_view_patient.php?patient_id=<?= $a['patient_id'] ?>">
                        Open
                    </a>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>

    <a href="doctor_dashboard.php" class="btn btn-secondary">Back</a>
</div>

<?php include 'includes/footer.php'; ?>
