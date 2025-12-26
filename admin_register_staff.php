<?php
session_start();
include 'config/db.php';
include 'includes/header.php';

// Access Control
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect required user data
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $role = $_POST['role'];
    $phone = $_POST['phone'];
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert into users table
    $user_sql = "INSERT INTO users (full_name, email, password, role, phone) VALUES (?, ?, ?, ?, ?)";
    $stmt_user = query($user_sql, [$full_name, $email, $hashed_password, $role, $phone], "sssss");

    if ($stmt_user->affected_rows === 1) {
        $new_user_id = $stmt_user->insert_id;
        $success = true;

        //  Insert into specific role table
        if ($role == 'doctor') {
            $specialization = $_POST['specialization'];
            $department = $_POST['department'];

            $doctor_sql = "INSERT INTO doctors (user_id, specialization, department) VALUES (?, ?, ?)";
            $stmt_doctor = query($doctor_sql, [$new_user_id, $specialization, $department], "iss");
            
            if ($stmt_doctor->affected_rows === 0) {
                 $success = false;
                 // Handle error (optional: delete user record)
            }
        } elseif ($role == 'labtech') {
            $department = $_POST['department'];

            $labtech_sql = "INSERT INTO lab_technicians (user_id, department) VALUES (?, ?)";
            $stmt_labtech = query($labtech_sql, [$new_user_id, $department], "is");
            
            if ($stmt_labtech->affected_rows === 0) {
                $success = false;
                // Handle error 
            }
        }
        
        //  Set status message
        if ($success) {
            $message = "<div class='alert alert-success'>Successfully registered new staff member: **{$full_name}** ({$role}).</div>";
        } else {
             $message = "<div class='alert alert-danger'>Staff user created, but role-specific details failed to save. Contact support.</div>";
        }
    } else {
        $message = "<div class='alert alert-danger'>Error registering user. Email may already exist.</div>";
    }
}
?>

<div class="container my-5">
    <h2 class="text-danger mb-4"> Staff Registration</h2>
    <p class="lead">Register new Doctors, Lab Technicians, and Receptionists.</p>
    
    <?php echo $message; ?>
    
    <div class="card shadow-lg p-4">
        <form method="post" action="admin_register_staff.php">
            
            <h4 class="mb-3">User Details</h4>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="full_name" class="form-label">Full Name</label>
                    <input type="text" class="form-control" id="full_name" name="full_name" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="phone" class="form-label">Phone</label>
                    <input type="text" class="form-control" id="phone" name="phone" required>
                </div>
            </div>

            <h4 class="mt-4 mb-3">Role & Professional Details</h4>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="role" class="form-label">Role</label>
                    <select class="form-select" id="role" name="role" required onchange="toggleFields(this.value)">
                        <option value="">Select Role</option>
                        <option value="doctor">Doctor</option>
                        <option value="labtech">Lab Technician</option>
                        <option value="receptionist">Receptionist</option>
                    </select>
                </div>
                
                <div class="col-md-4 mb-3 doctor-field" style="display:none;">
                    <label for="specialization" class="form-label">Specialization (Doctor Only)</label>
                    <input type="text" class="form-control" id="specialization" name="specialization">
                </div>
                
                <div class="col-md-4 mb-3 doctor-field labtech-field" style="display:none;">
                    <label for="department" class="form-label">Department</label>
                    <select class="form-select" id="department" name="department">
                        <option value="">Select Dept (Required for Doctor/LabTech)</option>
                        <option value="Cardiology">Cardiology</option>
                        <option value="Pediatrics">Pediatrics</option>
                        <option value="Internal Medicine">Internal Medicine</option>
                        <option value="Neurology">Neurology</option>
                        <option value="Surgery">Surgery</option>
                        <option value="General">General</option>
                        <option value="Lab">Lab</option>
                    </select>
                </div>
            </div>

            <button type="submit" class="btn btn-danger w-100 mt-4">Register Staff</button>
        </form>
    </div>
</div>

<script>
function toggleFields(role) {
    // Hide all role-specific fields first
    document.querySelectorAll('.doctor-field, .labtech-field').forEach(function(el) {
        el.style.display = 'none';
        el.querySelector('input, select').removeAttribute('required');
    });

    if (role === 'doctor') {
        document.querySelectorAll('.doctor-field').forEach(function(el) {
            el.style.display = 'block';
            el.querySelector('input, select').setAttribute('required', 'required');
        });
    } else if (role === 'labtech') {
        document.querySelectorAll('.labtech-field').forEach(function(el) {
            el.style.display = 'block';
            el.querySelector('input, select').setAttribute('required', 'required');
        });
    }
}
</script>

<?php include 'includes/footer.php'; ?>