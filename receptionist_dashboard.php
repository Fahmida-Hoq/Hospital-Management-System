<?php
session_start();
include 'config/db.php';
include 'includes/header.php';

// Access Control
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'receptionist') {
    header("Location: login.php");
    exit();
}

// FIX: Safely retrieve the full_name. This prevents the "Undefined array key" warning.
$receptionist_name = htmlspecialchars($_SESSION['full_name'] ?? 'Reception Staff');

// --- 1. Today's Confirmed Appointments ---
// FIX 1: Using 'scheduled_time' to match DB (fixes image_0d1593.png).
// The query function handles the connection, removing the need for get_db_connection() (fixes image_1876a1.png).
$appointments_today_sql = "SELECT COUNT(appointment_id) FROM appointments WHERE status = 'confirmed' AND DATE(scheduled_time) = CURDATE()";
$stmt_appt = query($appointments_today_sql);
$appointments_today_count = $stmt_appt->get_result()->fetch_row()[0] ?? 0;

// --- 2. Pending Admission Requests ---
// This assumes 'admission_requests' table exists.
$pending_admissions_sql = "SELECT COUNT(request_id) FROM admission_requests WHERE request_status = 'Pending Reception'";
$stmt_admissions = query($pending_admissions_sql);
$pending_admissions_count = $stmt_admissions->get_result()->fetch_row()[0] ?? 0;

// --- 3. Pending Bills (Unpaid) ---
// FIX 2: Uses the 'status' column, which MUST be added to the 'billing' table via SQL (fixes image_0d0df9.png).
$pending_bills_sql = "SELECT COUNT(bill_id) FROM billing WHERE status = 'unpaid'";
$stmt_bills = query($pending_bills_sql);
$pending_bills_count = $stmt_bills->get_result()->fetch_row()[0] ?? 0;

// --- 4. Total Registered Patients ---
$total_patients_sql = "SELECT COUNT(patient_id) FROM patients";
$stmt_patients = query($total_patients_sql);
$total_patients_count = $stmt_patients->get_result()->fetch_row()[0] ?? 0;
?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
        <h2 class="text-info">Welcome, **<?php echo $receptionist_name; ?>**!</h2>
        <a href="logout.php" class="btn btn-danger">Logout</a>
    </div>

    <h3 class="mt-4 mb-3 text-secondary">Daily Overview</h3>
    <div class="row g-4">
        
        <div class="col-md-3">
            <div class="card shadow-sm h-100 bg-primary text-white">
                <div class="card-body text-center">
                    <i class="h1 mb-3 d-block fas fa-calendar-check"></i>
                    <h5 class="card-title">Appointments Today</h5>
                    <p class="h2"><?php echo $appointments_today_count; ?></p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card shadow-sm h-100 bg-success text-white">
                <div class="card-body text-center">
                    <i class="h1 mb-3 d-block fas fa-bed"></i>
                    <h5 class="card-title">Pending Admissions</h5>
                    <p class="h2"><?php echo $pending_admissions_count; ?></p>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm h-100 bg-warning text-dark">
                <div class="card-body text-center">
                    <i class="h1 mb-3 d-block fas fa-file-invoice-dollar"></i>
                    <h5 class="card-title">Unpaid Bills</h5>
                    <p class="h2"><?php echo $pending_bills_count; ?></p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card shadow-sm h-100 bg-info text-white">
                <div class="card-body text-center">
                    <i class="h1 mb-3 d-block fas fa-users"></i>
                    <h5 class="card-title">Total Patients</h5>
                    <p class="h2"><?php echo $total_patients_count; ?></p>
                </div>
            </div>
        </div>
        
    </div>

    <h3 class="mt-5 mb-3 text-secondary">Reception Core Tasks</h3>
    <div class="row g-3">
        <div class="col-md-4">
            <a href="receptionist_manage_appointments.php" class="btn btn-primary w-100 py-3 shadow-sm">
                1. Manage Appointments
            </a>
        </div>
        <div class="col-md-4">
            <a href="receptionist_manage_admissions.php" class="btn btn-success w-100 py-3 shadow-sm">
                2. Manage Admissions
            </a>
        </div>
        <div class="col-md-4">
            <a href="receptionist_manage_billing.php" class="btn btn-warning w-100 py-3 shadow-sm">
                3. Handle Billing & Payments
            </a>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>