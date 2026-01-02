<?php
session_start();
include 'config/db.php'; 
include 'includes/header.php'; 

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    // Using your custom query function
    $sql = "SELECT user_id, full_name, password, role FROM users WHERE email = ?";
    $stmt = query($sql, [$email], "s");
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Verify password
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['email'] = $email;
            $_SESSION['full_name'] = $user['full_name']; 
            
            // --- UPDATED REDIRECTION LOGIC ---
            switch ($user['role']) {
                case 'patient':
                    $patient_sql = "SELECT patient_id FROM patients WHERE user_id = ?";
                    $patient_stmt = query($patient_sql, [$user['user_id']], "i");
                    $patient_result = $patient_stmt->get_result()->fetch_assoc();
                    
                    if ($patient_result) {
                        $_SESSION['patient_id'] = $patient_result['patient_id'];
                        header("Location: patient_dashboard.php");
                        exit();
                    } else {
                        $message = "<div class='alert alert-danger'>Patient profile missing.</div>";
                    }
                    break;

                case 'doctor':
                    $doctor_sql = "SELECT doctor_id FROM doctors WHERE user_id = ?";
                    $doctor_stmt = query($doctor_sql, [$user['user_id']], "i");
                    $doctor_result = $doctor_stmt->get_result()->fetch_assoc();
                    
                    if ($doctor_result) {
                        // Crucial for Indoor Logic: Doctor needs their ID to see their assigned indoor patients
                        $_SESSION['doctor_id'] = $doctor_result['doctor_id'];
                        header("Location: doctor_dashboard.php"); 
                        exit();
                    } else {
                        $message = "<div class='alert alert-danger'>Doctor profile missing.</div>";
                    }
                    break;
                    
                case 'labtech':
                    $labtech_sql = "SELECT labtech_id FROM lab_technicians WHERE user_id = ?";
                    $labtech_stmt = query($labtech_sql, [$user['user_id']], "i");
                    $labtech_result = $labtech_stmt->get_result()->fetch_assoc();

                    if ($labtech_result) {
                        $_SESSION['labtech_id'] = $labtech_result['labtech_id'];
                        header("Location: lab_dashboard.php"); 
                        exit();
                    } else {
                         $message = "<div class='alert alert-danger'>Lab Tech profile missing.</div>";
                    }
                    break;

                case 'receptionist': 
                    // Receptionists manage the Inpatient Registry
                    header("Location: receptionist_dashboard.php");
                    exit();
                    break;

                case 'admin': 
                    header("Location: admin.php");
                    exit();
                    break;
                    
                default:
                    $message = "<div class='alert alert-warning'>Dashboard for {$user['role']} not configured.</div>";
            }
            
        } else {
            $message = "<div class='alert alert-danger'>Invalid email or password.</div>";
        }
    } else {
        $message = "<div class='alert alert-danger'>Invalid email or password.</div>";
    }
}
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow-lg p-4 border-0">
                <div class="text-center mb-4">
                    <i class="fas fa-hospital-symbol fa-3x text-primary"></i>
                    <h2 class="card-title mt-2 text-primary">HMS Login</h2>
                </div>
                
                <?php echo $message; ?>
                
                <form method="post" action="login.php">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email address</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">Login to System</button>
                    <p class="text-center mt-3 small text-muted">Patient access? <a href="patient_register.php" class="text-decoration-none">Register here</a></p>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>