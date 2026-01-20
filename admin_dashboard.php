<?php
session_start();
include 'config/db.php';
include 'includes/header.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}
$staff_count = $conn->query("SELECT COUNT(*) FROM users WHERE role IN ('doctor', 'receptionist', 'labtech')")->fetch_row()[0];
$total_patients = $conn->query("SELECT COUNT(*) FROM patients")->fetch_row()[0];
$admitted_now = $conn->query("SELECT COUNT(*) FROM admissions WHERE status = 'admitted'")->fetch_row()[0];
$total_revenue = $conn->query("SELECT SUM(amount) FROM billing WHERE status = 'paid'")->fetch_row()[0] ?? 0;
?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
        <div>
            <h2 class="text-dark fw-bold mb-0"><i class="fas fa-shield-alt text-danger me-2"></i>HMS</h2>
            <p class="text-muted mb-0">Full system oversight and administrative control</p>
        </div>
        <div class="text-end">
            <span class="badge bg-dark px-3 py-2 mb-2">Logged in as: Admin</span><br>
            <a href="logout.php" class="btn btn-sm btn-outline-danger">Secure Logout</a>
        </div>
    </div>

    <div class="row g-3 mb-5">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-primary text-white p-3 h-100">
                <div class="d-flex justify-content-between">
                    <div><h5>Staff</h5><p class="display-6 fw-bold mb-0"><?= $staff_count ?></p></div>
                    <i class="fas fa-user-md fa-3x opacity-50"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-success text-white p-3 h-100">
                <div class="d-flex justify-content-between">
                    <div><h5>Total Patients</h5><p class="display-6 fw-bold mb-0"><?= $total_patients ?></p></div>
                    <i class="fas fa-users fa-3x opacity-50"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-warning text-dark p-3 h-100">
                <div class="d-flex justify-content-between">
                    <div><h5>Indoor (Admitted)</h5><p class="display-6 fw-bold mb-0"><?= $admitted_now ?></p></div>
                    <i class="fas fa-bed fa-3x opacity-50"></i>
                </div>
            </div>
        </div>
    
    <div class="row g-4">
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body text-center p-4">
                    <div class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                        <i class="fas fa-user-cog fa-2x text-primary"></i>
                    </div>
                    <h5 class="fw-bold">Staff Control</h5>
                    <p class="text-muted small">Add, delete, and manage Doctor, Receptionist, and Lab Technician accounts.</p>
                    <a href="admin_manage_users.php" class="btn btn-outline-primary w-100 rounded-pill">Manage Staff</a>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body text-center p-4">
                    <div class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                        <i class="fas fa-file-medical-alt fa-2x text-success"></i>
                    </div>
                    <h5 class="fw-bold">Patient Records</h5>
                    <p class="text-muted small">View all patient data, active indoor admissions, and outdoor consultation history.</p>
                    <a href="admin_view_patients.php" class="btn btn-outline-success w-100 rounded-pill">View Records</a>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body text-center p-4">
                    <div class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                        <i class="fas fa-hospital fa-2x text-info"></i>
                    </div>
                    <h5 class="fw-bold">Inventory & Rooms</h5>
                    <p class="text-muted small">Monitor bed availability, ward status, and assign rooms via receptionist tools.</p>
                    <a href="admin_manage_rooms.php" class="btn btn-outline-info w-100 rounded-pill">Room Management</a>
                </div>
            </div>
        </div>

    

     

<?php include 'includes/footer.php'; ?>