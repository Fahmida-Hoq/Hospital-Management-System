<?php
session_start();

include 'config/db.php';
include 'includes/header.php';

// Access Control: Must be logged in and role must be 'doctor'
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'doctor') {
    // Path to login.php is simple, as it's in the same directory
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch doctor and user details
$sql = "SELECT u.full_name, u.email, d.doctor_id, d.specialization, d.department
        FROM users u 
        JOIN doctors d ON u.user_id = d.user_id
        WHERE u.user_id = ?";
$stmt = query($sql, [$user_id], "i");
$doctor_data = $stmt->get_result()->fetch_assoc();

if (!$doctor_data) {
   
    header("Location: logout.php"); 
    exit();
}

$_SESSION['doctor_id'] = $doctor_data['doctor_id']; 
$doctor_name = htmlspecialchars($doctor_data['full_name']);

// Fetch upcoming appointments count
$today = date('Y-m-d');
$appt_sql = "SELECT COUNT(*) AS count FROM appointments 
             WHERE doctor_id = ? AND appointment_date >= ?";
$stmt_appt = query($appt_sql, [$_SESSION['doctor_id'], $today], "is");
$upcoming_count = $stmt_appt->get_result()->fetch_assoc()['count'];
?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
        <h2 class="text-success">👨‍⚕️ Welcome, <?php echo $doctor_name; ?>!</h2>
        <a href="logout.php" class="btn btn-danger">Logout</a>
    </div>

    <div class="alert alert-info">
        **Specialization:** <?php echo htmlspecialchars($doctor_data['specialization']); ?> | 
        **Department:** <?php echo htmlspecialchars($doctor_data['department']); ?>
    </div>

    <h3 class="mt-4 mb-3 text-secondary">Dashboard Overview</h3>
    <div class="row g-4">
        
        <div class="col-md-4">
            <div class="card shadow-sm h-100 bg-light">
                <div class="card-body">
                    <i class="h3 text-primary mb-3 d-block">⚙️</i>
                    <h5 class="card-title">Profile & Schedules</h5>
                    <p class="card-text">Update your specialization, phone, and manage your weekly duty shifts.</p>
                    <a href="doctor_profile.php" class="btn btn-primary">Manage Profile</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card shadow-sm h-100 bg-light">
                <div class="card-body">
                    <i class="h3 text-success mb-3 d-block">📋</i>
                    <h5 class="card-title">View Appointments</h5>
                    <p class="card-text">You have **<?php echo $upcoming_count; ?>** upcoming appointments to review and treat.</p>
                    <a href="doctor_view_appointments.php" class="btn btn-success">View Details</a>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm h-100 bg-light">
                <div class="card-body">
                    <i class="h3 text-warning mb-3 d-block">💊</i>
                    <h5 class="card-title">Patient Records</h5>
                    <p class="card-text">Access past prescriptions, test results, and patient history.</p>
                    <a href="#" class="btn btn-warning disabled">View Records (Soon)</a>
                </div>
            </div>
        </div>
        
    </div>
</div>

<?php 
// --- CRITICAL PATH CORRECTION: Simple relative path ---
include 'includes/footer.php'; 
?>