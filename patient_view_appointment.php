<?php
session_start();
include 'config/db.php';
include 'includes/header.php';

// Access Control
if (!isset($_SESSION['patient_id']) || $_SESSION['role'] != 'patient') {
    header("Location: login.php");
    exit();
}

$patient_id = $_SESSION['patient_id'];

// Fetch all appointments for the patient, joining with doctor user data
$sql = "SELECT 
            a.appointment_date, 
            a.appointment_time, 
            a.status, 
            u_doc.full_name AS doctor_name, 
            d.specialization
        FROM 
            appointments a
        JOIN 
            doctors d ON a.doctor_id = d.doctor_id
        JOIN 
            users u_doc ON d.user_id = u_doc.user_id
        WHERE 
            a.patient_id = ?
        ORDER BY 
            a.appointment_date DESC, a.appointment_time DESC";

$stmt = query($sql, [$patient_id], "i");
$appointments = $stmt->get_result();
?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
        <h2 class="text-success">Appointment History</h2>
        <a href="patient_dashboard.php" class="btn btn-secondary">⬅️ Dashboard</a>
    </div>

    <?php if ($appointments->num_rows > 0): ?>
    <div class="table-responsive">
        <table class="table table-bordered table-striped table-hover shadow-sm">
            <thead class="bg-success text-white">
                <tr>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Doctor</th>
                    <th>Specialization</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $appointments->fetch_assoc()): 
                    $status_class = match($row['status']) {
                        'approved' => 'bg-success',
                        'pending' => 'bg-warning',
                        'completed' => 'bg-info',
                        'cancelled' => 'bg-danger',
                        default => 'bg-secondary',
                    };
                ?>
                <tr>
                    <td><?php echo date('M d, Y', strtotime($row['appointment_date'])); ?></td>
                    <td><?php echo date('h:i A', strtotime($row['appointment_time'])); ?></td>
                    <td>Dr. <?php echo htmlspecialchars($row['doctor_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['specialization']); ?></td>
                    <td><span class="badge <?php echo $status_class; ?>"><?php echo ucfirst($row['status']); ?></span></td>
                    <td>
                        <?php if ($row['status'] == 'pending'): ?>
                            <a href="#" class="btn btn-sm btn-danger disabled">Cancel</a>
                        <?php else: ?>
                            <button class="btn btn-sm btn-outline-secondary disabled">Details</button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
        <div class="alert alert-info">You have not booked any appointments yet.</div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>