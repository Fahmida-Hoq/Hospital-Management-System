<?php
session_start();
include 'config/db.php';

// Check for login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'patient') {
    header("Location: login.php");
    exit();
}

$patient_id = $_SESSION['patient_id'];
$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $doctor_id = $_POST['doctor_id'];
    $date = $_POST['scheduled_time'];
    $time = $_POST['appointment_time'];
    
    if (!empty($doctor_id) && !empty($date) && !empty($time)) {
        $sql = "INSERT INTO appointments (patient_id, doctor_id, scheduled_time, appointment_time, status) VALUES (?, ?, ?, ?, 'pending')";
        $stmt = query($sql, [$patient_id, $doctor_id, $date, $time], "iiss");
        
        if ($stmt->affected_rows > 0) {
            $message = "<div class='alert alert-success'>Appointment requested successfully! Status is **Pending** until approved by staff. <a href='view_appointments.php'>View details</a>.</div>";
        } else {
            $message = "<div class='alert alert-danger'>Failed to book appointment. Please try again.</div>";
        }
    } else {
        $message = "<div class='alert alert-warning'>Please fill out all fields.</div>";
    }
}

// Fetch available doctors for the form dropdown
$doctor_sql = "SELECT d.doctor_id, u.full_name, d.specialization FROM doctors d JOIN users u ON d.user_id = u.user_id ORDER BY u.full_name";
$stmt = query($doctor_sql);
$doctors = $stmt->get_result();

include 'includes/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-7">
            <div class="card shadow-lg p-4">
                <h2 class="card-title text-center text-primary mb-4">Book New Appointment</h2>
                <?php echo $message; ?>
                <form method="post" action="book_appointment.php">
                    <div class="mb-3">
                        <label for="doctor_id" class="form-label">Select Doctor</label>
                        <select class="form-select" id="doctor_id" name="doctor_id" required>
                            <option value="">Choose a Doctor</option>
                            <?php while($doctor = $doctors->fetch_assoc()): ?>
                                <option value="<?php echo $doctor['doctor_id']; ?>">
                                    <?php echo htmlspecialchars($doctor['full_name'] . ' - ' . $doctor['specialization']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="scheduled_time" class="form-label">Date</label>
                            <input type="date" class="form-control" id="scheduled_time" name="scheduled_time" min="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="appointment_time" class="form-label">Time</label>
                            <input type="time" class="form-control" id="appointment_time" name="appointment_time" required>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100 mt-3">Request Appointment</button>
                    <a href="patient_dashboard.php" class="btn btn-secondary w-100 mt-2">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>