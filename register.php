<?php
include 'config/db.php';
include 'includes/header.php';

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $age = $_POST['age'];
    $gender = $_POST['gender'];
    $address = $_POST['address'];
    $phone = $_POST['phone'];

    // 1. Check if email already exists
    $check_sql = "SELECT user_id FROM users WHERE email = ?";
    $stmt_check = query($check_sql, [$email], "s");
    
    if ($stmt_check->get_result()->num_rows > 0) {
        $message = "<div class='alert alert-danger'>Email already registered!</div>";
    } else {
        // Start transaction for atomicity
        $conn->begin_transaction();

        try {
            // 2. Insert into users table
            $hashed_password = hash_password($password);
            $user_sql = "INSERT INTO users (full_name, email, password, role) VALUES (?, ?, ?, 'patient')";
            $stmt_user = query($user_sql, [$full_name, $email, $hashed_password], "sss");
            
            $user_id = $conn->insert_id;

            // 3. Insert into patients table
            $patient_sql = "INSERT INTO patients (user_id, age, gender, address, phone) VALUES (?, ?, ?, ?, ?)";
            $stmt_patient = query($patient_sql, [$user_id, $age, $gender, $address, $phone], "iisss");

            $conn->commit();
            $message = "<div class='alert alert-success'>Registration successful! You can now <a href='login.php'>log in</a>.</div>";

        } catch (Exception $e) {
            $conn->rollback();
            $message = "<div class='alert alert-danger'>Registration failed: " . $e->getMessage() . "</div>";
        }
    }
}
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-lg p-4">
                <h2 class="card-title text-center text-primary mb-4">Patient Registration</h2>
                <?php echo $message; ?>
                <form method="post" action="register.php">
                    <div class="mb-3">
                        <label for="full_name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="full_name" name="full_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email address</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>

                    <h5 class="mt-4 mb-3 text-secondary">Personal Details</h5>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="age" class="form-label">Age</label>
                            <input type="number" class="form-control" id="age" name="age" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="gender" class="form-label">Gender</label>
                            <select class="form-select" id="gender" name="gender" required>
                                <option value="">Select Gender</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone</label>
                        <input type="text" class="form-control" id="phone" name="phone" required>
                    </div>
                    <div class="mb-3">
                        <label for="address" class="form-label">Address</label>
                        <textarea class="form-control" id="address" name="address" rows="2" required></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100">Register</button>
                    <p class="text-center mt-3">Already have an account? <a href="login.php">Login here</a></p>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>