<?php
session_start();
include 'config/db.php';
include 'includes/header.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'receptionist') {
    header("Location: login.php");
    exit();
}

$receptionist_name = htmlspecialchars($_SESSION['full_name'] ?? 'Reception Staff');

// Helper function to safely run queries and prevent Fatal Errors if tables are missing
function safe_count($sql) {
    global $conn;
    $res = $conn->query($sql);
    if($res) {
        $row = $res->fetch_row();
        return $row[0] ?? 0;
    }
    return 0; // Return 0 if query fails
}

/* --- STATS SECTION --- */
$appointments_today = safe_count("SELECT COUNT(*) FROM appointments WHERE status='confirmed' AND DATE(scheduled_time)=CURDATE()");
$pending_admissions = safe_count("SELECT COUNT(*) FROM admission_requests WHERE request_status='Pending Reception'");
$total_admitted = safe_count("SELECT COUNT(*) FROM admissions WHERE status='Admitted'");
$unpaid_bills = safe_count("SELECT COUNT(*) FROM billing WHERE status='unpaid'");
?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
        <h2 class="text-info">Welcome, <?= $receptionist_name ?>!</h2>
        <a href="logout.php" class="btn btn-danger">Logout</a>
    </div>

    <div class="row g-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white text-center shadow-sm">
                <div class="card-body"><h5>Appointments Today</h5><h2><?= $appointments_today ?></h2></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white text-center shadow-sm">
                <div class="card-body"><h5>Pending Admissions</h5><h2><?= $pending_admissions ?></h2></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white text-center shadow-sm">
                <div class="card-body"><h5>Admitted Patients</h5><h2><?= $total_admitted ?></h2></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-dark text-center shadow-sm">
                <div class="card-body"><h5>Unpaid Bills</h5><h2><?= $unpaid_bills ?></h2></div>
            </div>
        </div>
    </div>

    <hr class="my-5">

    <div class="row g-3">
        <div class="col-md-4">
            <a href="receptionist_manage_appointments.php" class="btn btn-primary w-100">Manage Appointments</a>
        </div>
        <div class="col-md-4">
            <a href="receptionist_manage_admissions.php" class="btn btn-success w-100">Manage Admissions</a>
        </div>
        <div class="col-md-4">
            <a href="receptionist_admitted_patients.php" class="btn btn-info w-100">View Admitted Patients</a>
        </div>
    </div>

    <div class="row mt-4 g-4">
        <div class="col-md-4">
            <div class="card bg-danger text-white shadow h-100">
                <div class="card-body">
                    <h5>Emergency Desk</h5>
                    <p class="small">Admit a patient immediately without an appointment.</p>
                    <a href="receptionist_emergency_admission.php" class="btn btn-light btn-sm fw-bold">
                        <i class="fas fa-ambulance"></i> Open Emergency Form
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card bg-dark text-white shadow h-100">
                <div class="card-body">
                    <h5>Manual Admission</h5>
                    <p class="small">Directly admit a registered patient into a bed/ward.</p>
                    <a href="admit_patient_form.php" class="btn btn-outline-light btn-sm fw-bold">
                        <i class="fas fa-plus-circle"></i> New Admission Manual
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card bg-secondary text-white shadow h-100">
                <div class="card-body">
                    <h5>Indoor Patient Registry</h5>
                    <p class="small">Full registry with contact, guardian, and medical details.</p>
                    <a href="view_indoor_patients.php" class="btn btn-info btn-sm fw-bold text-white">
                        <i class="fas fa-list"></i> Open Indoor Registry
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>