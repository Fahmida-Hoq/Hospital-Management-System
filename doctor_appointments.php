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
$res = $stmt->get_result();
if ($res->num_rows === 0) {
    die("Doctor profile not found.");
}
$doctor_id = (int)$res->fetch_row()[0];
$stmt->close();

if (isset($_GET['delete_id'])) {
    $appt_id = (int)$_GET['delete_id'];

    $delete_query = "DELETE FROM appointments WHERE appointment_id = $appt_id AND doctor_id = $doctor_id";
    
    if ($conn->query($delete_query)) {
        
        header("Location: doctor_appointments.php?msg=Deleted");
        exit();
    }
}

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
    <h3>My Appointments</h3>

    <?php if(isset($_GET['msg'])): ?>
        <div class="alert alert-success">Appointment cancelled successfully.</div>
    <?php endif; ?>

    <table class="table table-striped mt-3">
        <thead>
            <tr>
                <th>Patient</th>
                <th>Date & Time</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($a = $appointments->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($a['patient_name']) ?></td>
                    <td><?= htmlspecialchars($a['scheduled_time']) ?></td>
                    <td>
                        <span class="badge <?= ($a['status'] == 'Completed') ? 'bg-success' : 'bg-warning text-dark' ?>">
                            <?= htmlspecialchars($a['status']) ?>
                        </span>
                    </td>
                    <td>
                        <a class="btn btn-sm btn-primary"
                           href="doctor_view_patient.php?patient_id=<?= $a['patient_id'] ?>">
                            Open
                        </a>

                        <a class="btn btn-sm btn-danger" 
                           href="doctor_appointments.php?delete_id=<?= $a['appointment_id'] ?>"
                           onclick="return confirm('Are you sure you want to delete this appointment?')">
                            Delete
                        </a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <a href="doctor_dashboard.php" class="btn btn-secondary">Back</a>
</div>

<?php include 'includes/footer.php'; ?>