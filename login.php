<?php
session_start();
include 'config/db.php'; 
include 'includes/header.php'; 

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    // Fetch user from the main users table
    $sql = "SELECT user_id, full_name, password, role FROM users WHERE email = ?";
    $stmt = query($sql, [$email], "s");
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Verify the hashed password
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['email'] = $email;
            $_SESSION['full_name'] = $user['full_name']; 
            
            $uid = $user['user_id'];

            // --- ROLE-BASED REDIRECTION & PROFILE FIX ---
            switch ($user['role']) {
                case 'patient':
                    $p_res = query("SELECT patient_id FROM patients WHERE user_id = ?", [$uid], "i")->get_result()->fetch_assoc();
                    if (!$p_res) {
                        // Creates profile entry using only user_id to avoid "Unknown column" errors
                        query("INSERT INTO patients (user_id) VALUES (?)", [$uid], "i");
                        $p_id = $conn->insert_id;
                    } else {
                        $p_id = $p_res['patient_id'];
                    }
                    $_SESSION['patient_id'] = $p_id;
                    header("Location: patient_dashboard.php");
                    exit();

                case 'doctor':
                    $d_res = query("SELECT doctor_id FROM doctors WHERE user_id = ?", [$uid], "i")->get_result()->fetch_assoc();
                    if (!$d_res) {
                        // Fixes error in image_7b560a.png by not guessing name column
                        query("INSERT INTO doctors (user_id) VALUES (?)", [$uid], "i");
                        $d_id = $conn->insert_id;
                    } else {
                        $d_id = $d_res['doctor_id'];
                    }
                    $_SESSION['doctor_id'] = $d_id;
                    header("Location: doctor_dashboard.php"); 
                    exit();
                    
                case 'labtech':
                    $l_res = query("SELECT labtech_id FROM lab_technicians WHERE user_id = ?", [$uid], "i")->get_result()->fetch_assoc();
                    if (!$l_res) {
                        query("INSERT INTO lab_technicians (user_id) VALUES (?)", [$uid], "i");
                        $l_id = $conn->insert_id;
                    } else {
                        $l_id = $l_res['labtech_id'];
                    }
                    $_SESSION['labtech_id'] = $l_id;
                    header("Location: lab_dashboard.php"); 
                    exit();

                case 'receptionist': 
                    header("Location: receptionist_dashboard.php");
                    exit();

                case 'admin': 
                    header("Location: admin_dashboard.php");
                    exit();
                    
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
                        <label for="email" class="form-label fw-bold">Email address</label>
                        <div class="input-group">
                            
                            <input type="email" class="form-control" id="email" name="email" placeholder="Enter email" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label fw-bold">Password</label>
                        <div class="input-group">
                            
                            <input type="password" class="form-control" id="password" name="password" placeholder="Enter password" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 py-2 fw-bold shadow-sm">Login to System</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>