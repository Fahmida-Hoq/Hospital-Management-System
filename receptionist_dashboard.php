<?php
session_start();
include 'config/db.php';
include 'includes/header.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'receptionist') {
    header("Location: login.php");
    exit();
}

$receptionist_name = htmlspecialchars($_SESSION['full_name'] ?? 'Reception Staff');

/* TODAY APPOINTMENTS */
$res_today = query("
    SELECT COUNT(*) 
    FROM appointments 
    WHERE status='confirmed'
    AND DATE(scheduled_time)=CURDATE()
");
$appointments_today = $res_today->get_result()->fetch_row()[0] ?? 0;

/* Pending admissions */
$res_adm = query("
    SELECT COUNT(*) FROM admission_requests
    WHERE request_status='Pending Reception'
");
$pending_admissions = $res_adm->get_result()->fetch_row()[0];

/* Total admitted */
$res_admitted = query("
    SELECT COUNT(*) FROM patients
    WHERE patient_type='Indoor'
");
$total_admitted = $res_admitted->get_result()->fetch_row()[0];

/* UNPAID BILLS */
$res_bills = query("
    SELECT COUNT(bill_id)
    FROM billing
    WHERE status='unpaid'
");
$unpaid_bills = $res_bills->get_result()->fetch_row()[0] ?? 0;

/* TOTAL PATIENTS */
$res_patients = query("SELECT COUNT(patient_id) FROM patients");
$total_patients = $res_patients->get_result()->fetch_row()[0] ?? 0;
?>

<div class="container my-5">

<div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
    <h2 class="text-info">Welcome, <?= $receptionist_name ?>!</h2>
    <a href="logout.php" class="btn btn-danger">Logout</a>
</div>

<div class="row g-4">

<div class="col-md-3">
<div class="card bg-primary text-white text-center">
<h5>Appointments Today</h5>
<h2><?= $appointments_today ?></h2>
</div>
</div>

<div class="col-md-3">
<div class="card bg-success text-white text-center">
<h5>Pending Admissions</h5>
<h2><?= $pending_admissions ?></h2>
</div>
</div>

<div class="col-md-3">
<div class="card bg-info text-white text-center">
<h5>Admitted Patients</h5>
<h2><?= $total_admitted ?></h2>
</div>
</div>

<div class="col-md-3">
<div class="card bg-warning text-dark text-center">
<h5>Unpaid Bills</h5>
<h2><?= $unpaid_bills ?></h2>
</div>
</div>

</div>

<hr>

<div class="row g-3 mt-4">

<div class="col-md-4">
<a href="receptionist_manage_appointments.php" class="btn btn-primary w-100">Manage Appointments</a>
</div>

<div class="col-md-4">
<a href="receptionist_manage_admissions.php" class="btn btn-success w-100">
Manage Admissions
</a>
</div>

<div class="col-md-4">
<a href="receptionist_admitted_patients.php" class="btn btn-info w-100">
View Admitted Patients
</a>
</div>

<div class="row mt-4">
    <div class="col-md-4">
        <div class="card bg-danger text-white shadow">
            <div class="card-body">
                <h5>Emergency Desk</h5>
                <p>Admit a patient immediately without an appointment.</p>
                <a href="receptionist_emergency_admission.php" class="btn btn-light btn-sm fw-bold">
                    <i class="fas fa-ambulance"></i> Open Emergency Form
                </a>
            </div>
        </div>
    </div>
</div>



</div>
</div>

<?php include 'includes/footer.php'; ?>
