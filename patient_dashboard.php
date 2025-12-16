<?php
session_start();
include 'config/db.php';
include 'includes/header.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'patient' || !isset($_SESSION['patient_id'])) {
    header("Location: login.php");
    exit();
}

$patient_id = $_SESSION['patient_id'];
$patient_name = htmlspecialchars($_SESSION['full_name'] ?? 'Patient User');
// upcoming appointments count
$today = date('Y-m-d');
$appt_sql = "SELECT COUNT(*) AS count FROM appointments 
             WHERE patient_id = ? AND scheduled_time >= ?"; 
// --- END FIX ---

$stmt_appt = query($appt_sql, [$patient_id, $today], "is");
$upcoming_count = $stmt_appt->get_result()->fetch_assoc()['count'] ?? 0;
?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
        <h2 class="text-primary">Welcome, <?php echo $patient_name; ?>!</h2>
        <a href="logout.php" class="btn btn-danger">Logout</a>
    </div>

    <h3 class="mt-4 mb-3 text-secondary">Patient Dashboard</h3>
    <div class="row g-4">
        
        <div class="col-md-4">
            <div class="card shadow-sm h-100 bg-light">
                <div class="card-body">
                    <i class="h3 text-success mb-3 d-block fas fa-clipboard-list"></i>
                    <h5 class="card-title">View Appointments</h5>
                    <p class="card-text">You have **<?php echo $upcoming_count; ?>** upcoming appointment(s). Review history and status.</p>
                    <a href="patient_view_appointment.php" class="btn btn-success">View Appointments</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card shadow-sm h-100 bg-light">
                <div class="card-body">
                    <i class="h3 text-primary mb-3 d-block fas fa-calendar-alt"></i>
                    <h5 class="card-title">Book New Appointment</h5>
                    <p class="card-text">Easily schedule your next visit with your preferred doctor.</p>
                    <a href="book_appointment.php" class="btn btn-primary">Book Now</a>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm h-100 bg-light">
                <div class="card-body">
                    <i class="h3 text-warning mb-3 d-block fas fa-flask"></i>
                    <h5 class="card-title">Test Results & Bills</h5>
                    <p class="card-text">Access lab results, prescriptions, and billing statements.</p>
                    <a href="patient_records.php" class="btn btn-warning">View Records</a>
                </div>
            </div>
      
        
    </div>
</div>

<?php include 'includes/footer.php'; ?>