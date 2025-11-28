<?php
session_start();
include 'config/db.php';
include 'includes/header.php';

// Access Control
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

$admin_name = $_SESSION['full_name'] ?? 'System Administrator';

// 1. Total Staff (Doctor, Receptionist, Lab Tech)
$total_staff = query("SELECT COUNT(user_id) FROM users WHERE role IN ('doctor', 'receptionist', 'labtech')")->get_result()->fetch_row()[0] ?? 0;
// 2. Total Patients
$total_patients = query("SELECT COUNT(patient_id) FROM patients")->get_result()->fetch_row()[0] ?? 0;
?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
        <h2 class="text-danger"> Admin </h2>
        <a href="logout.php" class="btn btn-outline-danger">Logout</a>
    </div>
    
    <p class="lead">Welcome, **<?php echo htmlspecialchars($admin_name); ?>**! Use this dashboard to manage all users and system settings.</p>

    <h4 class="mb-4 mt-5">System Users Overview</h4>
    <div class="row mb-5">
        
        <div class="col-md-6">
            <div class="card text-white bg-primary mb-3 shadow">
                <div class="card-body">
                    <h5 class="card-title">Total Staff (Doctor, Reception, Lab)</h5>
                    <p class="card-text h1"><?php echo $total_staff; ?></p>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card text-white bg-success mb-3 shadow">
                <div class="card-body">
                    <h5 class="card-title">Total Registered Patients</h5>
                    <p class="card-text h1"><?php echo $total_patients; ?></p>
                </div>
            </div>
        </div>
    </div>

    <h4 class="mb-4">Admin Core Tasks</h4>
    <div class="row">
        
        <div class="col-md-4 mb-3">
            <a href="admin_register_staff.php" class="btn btn-lg btn-block btn-info w-100 shadow-sm">
                <i class="fas fa-user-plus me-2"></i> 1. Register New Staff 
            </a>
        </div>
        
        <div class="col-md-4 mb-3">
            <a href="admin_manage_users.php" class="btn btn-lg btn-block btn-primary w-100 shadow-sm">
                <i class="fas fa-users-cog me-2"></i> 2. Manage User Accounts
            </a>
        </div>
        
        <div class="col-md-4 mb-3">
            <a href="admin_system_reports.php" class="btn btn-lg btn-block btn-secondary w-100 shadow-sm">
                <i class="fas fa-chart-bar me-2"></i> 3. View System Reports
            </a>
        </div>
        
    </div>
</div>

<?php include 'includes/footer.php'; ?>