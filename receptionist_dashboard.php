<?php
session_start();

include 'config/db.php';
include 'includes/header.php';

//  ACCESS CONTROL
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'receptionist') {
    header("Location: login.php");
    exit();
}

$receptionist_name = $_SESSION['full_name'] ?? 'Receptionist Staff' ;

// Count how many appointments are waiting for approval 
$pending_count_sql = "SELECT COUNT(appointment_id) FROM appointments WHERE status = 'pending'";
$pending_count_result = query($pending_count_sql);
$pending_count = $pending_count_result->get_result()->fetch_row()[0] ?? 0;

// Count how many appointments are confirmed for today
//  Changed scheduled_time to appointment_date
$today_count_sql = "SELECT COUNT(appointment_id) FROM appointments WHERE status = 'confirmed' AND appointment_date = CURDATE()";
$today_count_result = query($today_count_sql);
$today_count = $today_count_result->get_result()->fetch_row()[0] ?? 0;

// Count how many bills are unpaid 
$pending_bills_sql = "SELECT COUNT(bill_id) FROM billing WHERE payment_status = 'pending'";
$pending_bills_result = query($pending_bills_sql);
$pending_bills = $pending_bills_result->get_result()->fetch_row()[0] ?? 0;

?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
        <h2 class="text-primary">Receptionist Dashboard</h2>
        <a href="logout.php" class="btn btn-outline-danger">Logout</a>
    </div>
    
    <p class="lead">Welcome back, **<?php echo htmlspecialchars($receptionist_name); ?>**! Here are your key tasks for the day.</p>
    <h4 class="mb-4 mt-5">Daily Snapshot</h4>
    <div class="row mb-5">
        
        <div class="col-md-4">
            <div class="card text-white bg-warning mb-3 shadow">
                <div class="card-body">
                    <h5 class="card-title">New Appointment Requests</h5>
                    <p class="card-text h1"><?php echo $pending_count; ?></p>
                    <a href="receptionist_manage_appointments.php" class="btn btn-light btn-sm mt-2">Action Required</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card text-white bg-info mb-3 shadow">
                <div class="card-body">
                    <h5 class="card-title">Confirmed Appointments Today</h5>
                    <p class="card-text h1"><?php echo $today_count; ?></p>
                    <a href="receptionist_manage_appointments.php" class="btn btn-light btn-sm mt-2">View Schedule</a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-success mb-3 shadow">
                <div class="card-body">
                    <h5 class="card-title">Unpaid Bills at Counter</h5>
                    <p class="card-text h1"><?php echo $pending_bills; ?></p>
                    <a href="receptionist_billing.php" class="btn btn-light btn-sm mt-2">Manage Payments</a>
                </div>
            </div>
        </div>
    </div>
    <h4 class="mb-4">Receptionist Tasks</h4>
    <div class="row">
        
        <div class="col-md-4 mb-3">
            <a href="receptionist_manage_appointments.php" class="btn btn-lg btn-block btn-primary w-100 shadow-sm">
                <i class="fas fa-calendar-check me-2"></i> 1. Approve Appointments
            </a>
        </div>
        
        <div class="col-md-4 mb-3">
            <a href="receptionist_billing.php" class="btn btn-lg btn-block btn-success w-100 shadow-sm">
                <i class="fas fa-file-invoice-dollar me-2"></i> 2. Billing and Payments
            </a>
        </div>
        
        <div class="col-md-4 mb-3">
            <a href="receptionist_check_in.php" class="btn btn-lg btn-block btn-secondary w-100 shadow-sm">
                <i class="fas fa-door-open me-2"></i> 3. Patient Check-In
            </a>
        </div>
        
        <div class="col-md-6 mb-3">
            <a href="receptionist_notify_staff.php" class="btn btn-lg btn-block btn-info w-100 shadow-sm">
                <i class="fas fa-bell me-2"></i> 4. Notify Staff & Patients
            </a>
        </div>
        
        <div class="col-md-6 mb-3">
            <a href="receptionist_reports.php" class="btn btn-lg btn-block btn-dark w-100 shadow-sm">
                <i class="fas fa-chart-line me-2"></i> 5. Daily Reports
            </a>
        </div>
        
    </div>
</div>

<?php include 'includes/footer.php'; ?>