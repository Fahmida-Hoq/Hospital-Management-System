<?php
session_start();
include 'config/db.php';
include 'includes/header.php';

// Access Control
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'receptionist') {
    header("Location: login.php");
    exit();
}

$receptionist_name = htmlspecialchars($_SESSION['full_name'] ?? 'Reception Staff');

//
// ---------- 1. TODAY’S CONFIRMED APPOINTMENTS ----------
//
$sql_today_appt = "
    SELECT COUNT(appointment_id) 
    FROM appointments 
    WHERE status = 'confirmed' 
    AND DATE(scheduled_time) = CURDATE()
";
$res_today = query($sql_today_appt);
$appointments_today = $res_today->get_result()->fetch_row()[0] ?? 0;

//
// ---------- 2. PENDING ADMISSIONS FROM DOCTOR ----------
// doctor sends admission request → receptionist approves
//
$sql_pending_admissions = "
    SELECT COUNT(patient_id)
    FROM patients
    WHERE status = 'Pending Admission'
";
$res_adm = query($sql_pending_admissions);
$pending_admissions = $res_adm->get_result()->fetch_row()[0] ?? 0;

//
// ---------- 3. TOTAL ADMITTED PATIENTS ----------
// ward / bed / cabin assigned
//
$sql_admitted = "
    SELECT COUNT(patient_id)
    FROM patients
    WHERE ward IS NOT NULL OR bed IS NOT NULL OR cabin IS NOT NULL
";
$res_admitted = query($sql_admitted);
$total_admitted = $res_admitted->get_result()->fetch_row()[0] ?? 0;

//
// ---------- 4. UNPAID BILLS ----------
//
$sql_bills = "SELECT COUNT(bill_id) FROM billing WHERE status = 'unpaid'";
$res_bills = query($sql_bills);
$unpaid_bills = $res_bills->get_result()->fetch_row()[0] ?? 0;

//
// ---------- 5. TOTAL REGISTERED PATIENTS ----------
//
$sql_patients = "SELECT COUNT(patient_id) FROM patients";
$res_patients = query($sql_patients);
$total_patients = $res_patients->get_result()->fetch_row()[0] ?? 0;
?>

<div class="container my-5">

    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
        <h2 class="text-info">Welcome, <?php echo $receptionist_name; ?>!</h2>
        <a href="logout.php" class="btn btn-danger">Logout</a>
    </div>

    <h3 class="mt-4 mb-3 text-secondary">Daily Overview</h3>

    <div class="row g-4">

        <!-- TODAY APPOINTMENTS -->
        <div class="col-md-3">
            <div class="card shadow-sm h-100 bg-primary text-white">
                <div class="card-body text-center">
                    <i class="fas fa-calendar-check h1 mb-3"></i>
                    <h5>Appointments Today</h5>
                    <p class="h2"><?php echo $appointments_today; ?></p>
                </div>
            </div>
        </div>

        <!-- PENDING ADMISSIONS -->
        <div class="col-md-3">
            <div class="card shadow-sm h-100 bg-success text-white">
                <div class="card-body text-center">
                    <i class="fas fa-hospital-user h1 mb-3"></i>
                    <h5>Pending Admissions</h5>
                    <p class="h2"><?php echo $pending_admissions; ?></p>
                </div>
            </div>
        </div>

        <!-- CURRENTLY ADMITTED -->
        <div class="col-md-3">
            <div class="card shadow-sm h-100 bg-info text-white">
                <div class="card-body text-center">
                    <i class="fas fa-procedures h1 mb-3"></i>
                    <h5>Admitted Patients</h5>
                    <p class="h2"><?php echo $total_admitted; ?></p>
                </div>
            </div>
        </div>

        <!-- UNPAID BILLS -->
        <div class="col-md-3">
            <div class="card shadow-sm h-100 bg-warning text-dark">
                <div class="card-body text-center">
                    <i class="fas fa-file-invoice-dollar h1 mb-3"></i>
                    <h5>Unpaid Bills</h5>
                    <p class="h2"><?php echo $unpaid_bills; ?></p>
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
                2. Manage Admissions (Ward/Bed/Cabin)
            </a>
        </div>

        <div class="col-md-4">
            <a href="receptionist_manage_billing.php" class="btn btn-warning w-100 py-3 shadow-sm">
                3. Billing & Payments
            </a>
        </div>

    </div>

</div>

<?php include 'includes/footer.php'; ?>
