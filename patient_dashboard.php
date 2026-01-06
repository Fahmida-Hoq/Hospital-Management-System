<?php
session_start();
include 'config/db.php';
include 'includes/header.php';

// Access Control
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'patient') {
    header("Location: login.php");
    exit();
}

$patient_id = $_SESSION['patient_id'];
$patient_name = htmlspecialchars($_SESSION['full_name'] ?? 'Patient');

// 1. Fetch Current Admission Info
$stmt = $conn->prepare("SELECT status, ward, bed, admission_date FROM patients WHERE patient_id = ?");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$p_data = $stmt->get_result()->fetch_assoc();

// 2. Fetch Billing Summary
$bill_res = $conn->query("SELECT SUM(amount) as due FROM billing WHERE patient_id = $patient_id AND status = 'Unpaid'");
$bill_data = $bill_res->fetch_assoc();
$total_due = $bill_data['due'] ?? 0;
?>

<style>
    :root {
        --hospital-light_purple: #9550ceff;
        --hospital-sky_blue: #b1c1da81;
        --glass-bg: rgba(255, 255, 255, 0.95);
    }
    body { background-color: #ffffffff; font-family: 'Inter', sans-serif; }
    
    .hero-section {
        background: linear-gradient(135deg, var(--hospital-light_purple) 0%, var(--hospital-sky_blue) 100%);
        padding: 60px 0;
        border-radius: 0 0 40px 40px;
        margin-bottom: -50px;
        color: white;
    }
    
    .dashboard-card {
        border: none;
        border-radius: 20px;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        background: var(--glass-bg);
        overflow: hidden;
    }
    
    .dashboard-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 15px 30px rgba(0,0,0,0.1) !important;
    }

    .icon-box {
        width: 70px;
        height: 70px;
        border-radius: 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
        font-size: 28px;
    }
    
    .bg-soft-white { background-color: #e3f2fd; color: #ffffffff; }
    .bg-soft-white { background-color: #e8eaf6; color: #ffffffff; }
    .bg-soft-teal { background-color: #e0f2f1; color: #00897b; }

    .status-badge {
        background: rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(5px);
        padding: 8px 20px;
        border-radius: 50px;
        font-size: 14px;
        border: 1px solid rgba(255,255,255,0.3);
    }

    .btn-action {
        border-radius: 12px;
        padding: 10px 20px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-size: 13px;
    }
</style>

<div class="hero-section shadow-lg">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-7">
                <h1 class="fw-bold mb-1">Hello, <?= $patient_name; ?></h1>
                <p class="opacity-75 mb-0">Manage your health records and appointments securely.</p>
            </div>
            <div class="col-md-5 text-md-end mt-4 mt-md-0">
                <span class="status-badge">
                    <i class="fas fa-circle-notch fa-spin me-2"></i> 
                    Account Status: <span class="fw-bold"><?= $p_data['status'] ?></span>
                </span>
                <a href="logout.php" class="btn btn-link text-white text-decoration-none ms-3 small">
                    <i class="fas fa-power-off me-1"></i> Logout
                </a>
            </div>
        </div>
    </div>
</div>

<div class="container" style="margin-top: 20px;">
    <?php if ($p_data['status'] === 'Admitted'): ?>
    <div class="row mb-4 justify-content-center">
        <div class="col-md-12">
            <div class="dashboard-card card shadow-sm border-start border-4 border-info">
                <div class="card-body py-4 px-5">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <div class="icon-box bg-soft-blue mb-0">
                                <i class="fas fa-hospital-user"></i>
                            </div>
                        </div>
                        <div class="col">
                            <h5 class="fw-bold text-dark mb-1">Active Indoor Admission</h5>
                            <p class="text-muted mb-0 small">
                                Ward: <strong><?= $p_data['ward'] ?></strong> | 
                                Bed: <strong><?= $p_data['bed'] ?></strong> | 
                                Since: <strong><?= date('d M Y', strtotime($p_data['admission_date'])) ?></strong>
                            </p>
                        </div>
                        <div class="col-md-auto text-end">
                             <a href="patient_records.php" class="btn btn-outline-primary btn-action">View Full Bill</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="dashboard-card card shadow-sm p-4 text-center h-100">
                <div class="icon-box bg-soft-blue">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <h5 class="fw-bold text-dark">Appointments</h5>
                <p class="text-muted small px-3">Schedule a new visit or check your upcoming clinic schedule.</p>
                <div class="mt-auto">
                    <a href="book_appointment.php" class="btn btn-primary btn-action w-100 shadow-sm">Book New Visit</a>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="dashboard-card card shadow-sm p-4 text-center h-100">
                <div class="icon-box bg-soft-indigo">
                    <i class="fas fa-file-medical-alt"></i>
                </div>
                <h5 class="fw-bold text-dark">Medical Records</h5>
                <p class="text-muted small px-3">Access your detailed medical history, lab results, and invoices.</p>
                <div class="mt-auto">
                    <a href="patient_records.php" class="btn btn-indigo btn-action w-100 shadow-sm text-white" style="background-color: #3f51b5;">Review History</a>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="dashboard-card card shadow-sm p-4 text-center h-100">
                <div class="icon-box bg-soft-teal">
                    <i class="fas fa-pills"></i>
                </div>
                <h5 class="fw-bold text-dark">Prescriptions</h5>
                <p class="text-muted small px-3">Check active medications and dosages assigned by your physician.</p>
                <div class="mt-auto">
                    <a href="patient_prescriptions.php" class="btn btn-teal btn-action w-100 shadow-sm text-white" style="background-color: #00897b;">View Pharmacy</a>
                </div>
            </div>
        </div>
    </div>

   

<?php include 'includes/footer.php'; ?>