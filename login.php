<?php
session_start();
include 'config/db.php'; 
include 'includes/header.php'; 

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $sql = "SELECT user_id, full_name, password, role FROM users WHERE email = ?";
    $stmt = query($sql, [$email], "s");
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        //  Verify password
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['email'] = $email;
            $_SESSION['full_name'] = $user['full_name']; 
            
            // --- REDIRECTION LOGIC BASED ON ROLE ---
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
                        $message = "<div class='alert alert-danger'>Patient profile details missing. Contact administrator.</div>";
                    }
                    break;

                case 'doctor':
                    // Fetch doctor_id from the doctors table (needed for doctor-specific queries)
                    $doctor_sql = "SELECT doctor_id FROM doctors WHERE user_id = ?";
                    $doctor_stmt = query($doctor_sql, [$user['user_id']], "i");
                    $doctor_result = $doctor_stmt->get_result()->fetch_assoc();
                    
                    if ($doctor_result) {
                        $_SESSION['doctor_id'] = $doctor_result['doctor_id'];
                        header("Location: doctor_dashboard.php"); 
                        exit();
                    } else {
                        $message = "<div class='alert alert-danger'>Doctor profile details missing. Contact administrator.</div>";
                    }
                    break;
                    
                case 'labtech':
                    // Fetch labtech_id from the lab_technicians table (needed for lab tech-specific queries)
                    $labtech_sql = "SELECT labtech_id FROM lab_technicians WHERE user_id = ?";
                    $labtech_stmt = query($labtech_sql, [$user['user_id']], "i");
                    $labtech_result = $labtech_stmt->get_result()->fetch_assoc();

                    if ($labtech_result) {
                        $_SESSION['labtech_id'] = $labtech_result['labtech_id'];
                        header("Location: lab_dashboard.php"); 
                        exit();
                    } else {
                         $message = "<div class='alert alert-danger'>Lab Technician profile details missing. Contact administrator.</div>";
                    }
                    break;

                case 'admin': 
                    header("Location: admin.php");
                    exit();
                    break;

                case 'receptionist': 
                    header("Location: receptionist_dashboard.php");
                    exit();
                    break;
                    
                default:
                    $message = "<div class='alert alert-warning'>Login successful, but a dashboard for your role ({$user['role']}) is not yet configured.</div>";
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
            <div class="card shadow-lg p-4">
                <h2 class="card-title text-center text-primary mb-4">User Login</h2>
                <?php echo $message; ?>
                <form method="post" action="login.php">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email address</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Login</button>
                    <p class="text-center mt-3">Don't have an account? <a href="patient_register.php">Register now</a></p>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>