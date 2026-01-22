<?php
session_start();
include 'config/db.php';
include 'includes/header.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header("Location: login.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$res = $conn->query("SELECT doctor_id FROM doctors WHERE user_id=$user_id");
$doctor_id = (int)$res->fetch_row()[0];

if (isset($_GET['delete_id'])) {
    $appt_id = (int)$_GET['delete_id'];
    $conn->query("DELETE FROM appointments WHERE appointment_id = $appt_id AND doctor_id = $doctor_id");
    header("Location: doctor_appointments.php?msg=Deleted");
    exit();
}

// SQL: Fetching appointment_time for consultation
$sql = "SELECT a.appointment_id, a.scheduled_time, a.appointment_time, a.status, p.name as patient_name, p.patient_id
        FROM appointments a
        JOIN patients p ON a.patient_id=p.patient_id
        WHERE a.doctor_id=$doctor_id
        ORDER BY a.scheduled_time DESC, a.appointment_time ASC";
$appointments = $conn->query($sql);
?>

<div class="container my-5">
    <h3>My Appointments</h3>
    <table class="table table-striped mt-3">
        <thead>
            <tr>
                <th>Patient</th>
                <th>Consultation Date & Time</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($a = $appointments->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($a['patient_name']) ?></td>
                    <td>
                        <strong><?= date('d M, Y', strtotime($a['scheduled_time'])) ?></strong><br>
                        <span class="text-primary font-weight-bold">
                            <i class="far fa-clock"></i> <?= date('h:i A', strtotime($a['appointment_time'])) ?>
                        </span>
                    </td>
                    <td><span class="badge bg-info"><?= $a['status'] ?></span></td>
                    <td>
                        <a class="btn btn-sm btn-primary" href="doctor_view_patient.php?patient_id=<?= $a['patient_id'] ?>">Open</a>
                        <a class="btn btn-sm btn-danger" href="doctor_appointments.php?delete_id=<?= $a['appointment_id'] ?>">Delete</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
<?php include 'includes/footer.php'; ?>