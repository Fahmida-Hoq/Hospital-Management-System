<?php
session_start();
include 'config/db.php';
include 'includes/header.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = $conn->real_escape_string($_POST['full_name']);
    $email = $conn->real_escape_string($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $role = $conn->real_escape_string($_POST['role']);
    $specialization = $conn->real_escape_string($_POST['specialization'] ?? 'General');

    // Check if email already exists
    $check = $conn->query("SELECT user_id FROM users WHERE email = '$email'");
    if ($check->num_rows > 0) {
        $message = "<div class='alert alert-danger'>Email already exists!</div>";
    } else {
        // Step 1: Add to users table
        $sql = "INSERT INTO users (full_name, email, password, role) VALUES ('$full_name', '$email', '$password', '$role')";
        
        if ($conn->query($sql)) {
            $new_user_id = $conn->insert_id; // Get the ID we just created

            // Step 2: Create the specific profile based on role
            if ($role === 'doctor') {
                $conn->query("INSERT INTO doctors (user_id, name, specialization) VALUES ('$new_user_id', '$full_name', '$specialization')");
            } 
            elseif ($role === 'lab_technician') {
                // Ensure you have a lab_technicians table with a user_id column
                $conn->query("INSERT INTO lab_technicians (user_id, name) VALUES ('$new_user_id', '$full_name')");
            }
            // For receptionists, they usually only need the users table record to access the dashboard.

            $message = "<div class='alert alert-success fw-bold text-center'>New $role added successfully! They can now log in.</div>";
        } else {
            $message = "<div class='alert alert-danger'>Error: " . $conn->error . "</div>";
        }
    }
}
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-dark text-white text-center py-3">
                    <h5 class="mb-0 fw-bold">STAFF REGISTRATION</h5>
                </div>
                <div class="card-body p-4 bg-light">
                    <?= $message ?>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">FULL NAME</label>
                            <input type="text" name="full_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">EMAIL ADDRESS</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">PASSWORD</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">ASSIGN ROLE</label>
                            <select name="role" id="roleSelect" class="form-select" onchange="toggleSpec()" required>
                                <option value="receptionist">Receptionist</option>
                                <option value="doctor">Doctor</option>
                                <option value="lab_technician">Lab Technician</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div id="specField" class="mb-3" style="display:none;">
                            <label class="form-label small fw-bold">SPECIALIZATION (For Doctors)</label>
                            <input type="text" name="specialization" class="form-control" placeholder="e.g. Cardiology">
                        </div>
                        <button type="submit" class="btn btn-primary w-100 fw-bold py-2">REGISTER STAFF</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleSpec() {
    document.getElementById('specField').style.display = (document.getElementById('roleSelect').value === 'doctor') ? 'block' : 'none';
}
</script>

<?php include 'includes/footer.php'; ?>