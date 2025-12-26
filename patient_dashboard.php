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

// 1. Fetch Current Admission Info (Connects to Receptionist data)
$stmt = $conn->prepare("SELECT status, ward, bed, admission_date FROM patients WHERE patient_id = ?");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$p_data = $stmt->get_result()->fetch_assoc();

// 2. Calculate Total Due
$bill_res = $conn->query("SELECT SUM(amount) as due FROM billing WHERE patient_id = $patient_id AND status = 'Unpaid'");
$bill_data = $bill_res->fetch_assoc();
$total_due = $bill_data['due'] ?? 0;
?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
        <h2 class="text-primary"><i class="fas fa-user-circle"></i> Welcome, <?= $patient_name; ?></h2>
        <a href="logout.php" class="btn btn-outline-danger">Logout</a>
    </div>

    <?php if ($p_data['status'] === 'Admitted'): ?>
    <div class="card mb-4 border-primary shadow-sm">
        <div class="card-body bg-light">
            <h5 class="text-primary"><i class="fas fa-bed"></i> Current Admission Details</h5>
            <div class="row mt-3">
                <div class="col-md-4"><strong>Ward:</strong> <?= $p_data['ward'] ?></div>
                <div class="col-md-4"><strong>Bed Number:</strong> <?= $p_data['bed'] ?></div>
                <div class="col-md-4"><strong>Admitted On:</strong> <?= date('d M Y', strtotime($p_data['admission_date'])) ?></div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-md-4">
            <div class="card shadow-sm h-100 border-top-<?= ($total_due > 0) ? 'danger' : 'success' ?> border-4">
                <div class="card-body text-center">
                    <i class="fas fa-file-invoice-dollar fa-3x mb-3 text-muted"></i>
                    <h5>Financial Status</h5>
                    <h3 class="<?= ($total_due > 0) ? 'text-danger' : 'text-success' ?>">
                        Tk. <?= number_format($total_due, 2) ?>
                    </h3>
                    <p class="small text-muted">Total Outstanding Balance</p>
                    <a href="patient_records.php" class="btn btn-dark w-100">View Bills & Pay</a>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-body text-center">
                    <i class="fas fa-calendar-check fa-3x mb-3 text-muted"></i>
                    <h5>Appointments</h5>
                    <p>Schedule a new visit or check your upcoming schedule.</p>
                    <a href="book_appointment.php" class="btn btn-primary w-100">Book New</a>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-body text-center">
                    <i class="fas fa-folder-open fa-3x mb-3 text-muted"></i>
                    <h5>Medical Records</h5>
                    <p>Access your prescriptions, history, and test results.</p>
                    <a href="patient_records.php" class="btn btn-warning w-100">View Records</a>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="col-md-4">
    <div class="card shadow-sm h-100">
        <div class="card-body text-center">
            <i class="fas fa-prescription fa-3x mb-3 text-warning"></i>
            <h5>My Prescriptions</h5>
            <p>View medications and dosages assigned by your doctor.</p>
            <a href="patient_prescriptions.php" class="btn btn-warning w-100">Open Pharmacy Records</a>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>