<?php
session_start();
include '../config/db.php'; // NOTE: Need to go up one directory level
include '../includes/header.php'; // NOTE: Need to go up one directory level

// Access Control
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'receptionist') {
    header("Location: ../login.php");
    exit();
}

// Handle Approval/Rejection Actions
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    $appt_id = $_POST['appointment_id'];
    $status = ($_POST['action'] == 'approve') ? 'approved' : 'rejected';
    
    $sql = "UPDATE appointments SET status = ? WHERE appointment_id = ?";
    $stmt = query($sql, [$status, $appt_id], "si");
    
    if ($stmt->affected_rows > 0) {
        $message = "<div class='alert alert-success'>Appointment $appt_id successfully **$status**!</div>";
    } else {
        $message = "<div class='alert alert-danger'>Error updating appointment.</div>";
    }
}

// Fetch all pending appointments
$sql_pending = "SELECT 
                    a.appointment_id, a.appointment_date, a.appointment_time,
                    u_pat.full_name AS patient_name,
                    u_doc.full_name AS doctor_name,
                    d.specialization
                FROM appointments a
                JOIN patients p ON a.patient_id = p.patient_id
                JOIN users u_pat ON p.user_id = u_pat.user_id
                JOIN doctors d ON a.doctor_id = d.doctor_id
                JOIN users u_doc ON d.user_id = u_doc.user_id
                WHERE a.status = 'pending'
                ORDER BY a.appointment_date ASC, a.appointment_time ASC";

$stmt_pending = query($sql_pending);
$pending_appointments = $stmt_pending->get_result();

$receptionist_name = htmlspecialchars($_SESSION['email']); // Assuming name isn't stored separately easily
?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
        <h2 class="text-primary">🧑‍💼 Receptionist Dashboard</h2>
        <a href="../logout.php" class="btn btn-danger">Logout</a>
    </div>

    <?php echo $message; ?>
    <div class="alert alert-info">
        Welcome, <?php echo $receptionist_name; ?>. You manage all incoming appointment requests.
    </div>

    <h3 class="mt-4 mb-3 text-secondary">Pending Appointment Requests (<?php echo $pending_appointments->num_rows; ?>)</h3>
    
    <?php if ($pending_appointments->num_rows > 0): ?>
    <div class="table-responsive">
        <table class="table table-bordered table-striped table-hover shadow-sm">
            <thead class="bg-primary text-white">
                <tr>
                    <th>ID</th>
                    <th>Date/Time</th>
                    <th>Patient</th>
                    <th>Doctor</th>
                    <th>Specialization</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $pending_appointments->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['appointment_id']; ?></td>
                    <td><?php echo date('M d, Y', strtotime($row['appointment_date'])) . ' at ' . date('h:i A', strtotime($row['appointment_time'])); ?></td>
                    <td><?php echo htmlspecialchars($row['patient_name']); ?></td>
                    <td>Dr. <?php echo htmlspecialchars($row['doctor_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['specialization']); ?></td>
                    <td>
                        <form method="post" action="dashboard.php" style="display:inline;">
                            <input type="hidden" name="appointment_id" value="<?php echo $row['appointment_id']; ?>">
                            <button type="submit" name="action" value="approve" class="btn btn-sm btn-success">Approve</button>
                            <button type="submit" name="action" value="reject" class="btn btn-sm btn-danger">Reject</button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
        <div class="alert alert-success">No pending appointments to review!</div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>