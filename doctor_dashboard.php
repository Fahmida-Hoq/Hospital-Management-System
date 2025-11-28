<?php
session_start();
include 'config/db.php';
include 'includes/header.php';

// Access Control
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'doctor') {
    header("Location: login.php");
    exit();
}

$doctor_user_id = $_SESSION['user_id'];
$name_sql = "SELECT full_name FROM users WHERE user_id = ?";
$stmt_name = query($name_sql, [$doctor_user_id], "i");
$doctor_name = htmlspecialchars($stmt_name->get_result()->fetch_assoc()['full_name'] ?? 'Dr. Staff');



// 1. Confirmed appointments for today 
$today_count = query("SELECT COUNT(a.appointment_id) 
                      FROM appointments a 
                      JOIN doctors d ON a.doctor_id = d.doctor_id
                      WHERE d.user_id = ? AND a.status = 'confirmed' 
                      AND a.appointment_date = CURDATE()", 
                      [$doctor_user_id], "i")->get_result()->fetch_row()[0] ?? 0;

// 2. Total pending test results related to the doctor's patients
$pending_results = query("SELECT COUNT(t.test_id) 
                          FROM lab_tests t
                          JOIN appointments a ON t.appointment_id = a.appointment_id
                          JOIN doctors d ON a.doctor_id = d.doctor_id
                          WHERE d.user_id = ? AND t.status != 'completed'", 
                          [$doctor_user_id], "i")->get_result()->fetch_row()[0] ?? 0;
?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
        <h2 class="text-success">Doctor Dashboard</h2>
        <a href="logout.php" class="btn btn-outline-danger">Logout</a>
    </div>
    
    <p class="lead">Welcome, **<?php echo $doctor_name; ?>! Here is your schedule and patient information.</p>

    <h4 class="mb-4 mt-5">Daily Overview</h4>
    <div class="row mb-5">
        
        <div class="col-md-6">
            <div class="card text-white bg-primary mb-3 shadow">
                <div class="card-body">
                    <h5 class="card-title">Confirmed Appointments Today</h5>
                    <p class="card-text h1"><?php echo $today_count; ?></p>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card text-white bg-warning mb-3 shadow">
                <div class="card-body">
                    <h5 class="card-title">Pending Patient Test Results</h5>
                    <p class="card-text h1"><?php echo $pending_results; ?></p>
                </div>
            </div>
        </div>
    </div>

    <h4 class="mb-4">Doctor Core Tasks</h4>
    <div class="row">
        
        <div class="col-md-4 mb-3">
            <a href="doctor_schedule.php" class="btn btn-lg btn-block btn-success w-100 shadow-sm">
                <i class="fas fa-calendar-day me-2"></i> 1. View Today's Schedule
            </a>
        </div>
        
        <div class="col-md-4 mb-3">
            <a href="doctor_patient_history.php" class="btn btn-lg btn-block btn-info w-100 shadow-sm">
                <i class="fas fa-notes-medical me-2"></i> 2. View Patient Records
            </a>
        </div>
        
        <div class="col-md-4 mb-3">
            <a href="doctor_prescribe.php" class="btn btn-lg btn-block btn-primary w-100 shadow-sm">
                <i class="fas fa-prescription-bottle-alt me-2"></i> 3. Write Prescriptions / Lab Orders
            </a>
        </div>
        
    </div>
</div>

<?php include 'includes/footer.php'; ?>