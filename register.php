<?php
session_start();
include 'config/db.php';
include 'includes/header.php';

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';
    $age = (int)($_POST['age'] ?? 0);
    $gender = trim($_POST['gender'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $patient_type = $_POST['patient_type'] ?? 'Outdoor';

    $guardian_name = null; $guardian_phone = null;
    if ($patient_type === 'Indoor') {
        $guardian_name = trim($_POST['guardian_name'] ?? '');
        $guardian_phone = trim($_POST['guardian_phone'] ?? '');
    }

    if ($name === '') $errors[] = 'Full Name is required';
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email is required';
    if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters';
    if ($password !== $password2) $errors[] = 'Passwords do not match';
    if ($age <= 0) $errors[] = 'Please enter a valid age';
    if ($gender === '') $errors[] = 'Gender selection is required';
    if ($phone === '') $errors[] = 'Phone number is required';
    if ($patient_type === 'Indoor' && ($guardian_name === '' || $guardian_phone === '')) $errors[] = 'Guardian details are required for Indoor patients';

    if (empty($errors)) {
        $conn->begin_transaction();
        try {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $u = $conn->prepare("INSERT INTO users (full_name, email, password, role, created_at) VALUES (?, ?, ?, 'patient', NOW())");
            $u->bind_param("sss", $name, $email, $hash);
            $u->execute();
            $user_id = $conn->insert_id;
            $u->close();

            $p = $conn->prepare("INSERT INTO patients (name, email, password, patient_type, user_id, age, gender, address, phone, guardian_name, guardian_phone, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', NOW())");
            $p->bind_param("ssssisissss", $name, $email, $hash, $patient_type, $user_id, $age, $gender, $address, $phone, $guardian_name, $guardian_phone);
            $p->execute();
            $p->close();

            $conn->commit();
            header("Location: login.php?registered=1");
            exit;
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = "Database Error: " . $e->getMessage();
        }
    }
}
?>

<style>
    .reg-card { border-radius: 15px; border: none; }
    .reg-header { background: linear-gradient(45deg, #007bff, #0056b3); color: white; border-radius: 15px 15px 0 0 !important; }
    .form-label { font-weight: 600; color: #495057; font-size: 0.9rem; }
    .section-title { border-bottom: 2px solid #f8f9fa; padding-bottom: 10px; margin-bottom: 20px; color: #007bff; font-weight: bold; }
    #indoorFields { background-color: #f8f9fa; border-radius: 10px; padding: 20px; border-left: 5px solid #007bff; }
</style>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="card reg-card shadow-lg">
                <div class="card-header reg-header p-4 text-center">
                    <h3 class="mb-0">Patient Registration</h3>
                    <p class="mb-0 opacity-75">Create your account to manage appointments and bills</p>
                </div>
                
                <div class="card-body p-4 p-md-5">
                    <?php if(!empty($errors)): ?>
                        <div class="alert alert-danger shadow-sm">
                            <ul class="mb-0">
                                <?php foreach($errors as $er) echo '<li>'.htmlspecialchars($er).'</li>'; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="post">
                        <h5 class="section-title"><i class="fas fa-lock me-2"></i> Account Security</h5>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Full Name</label>
                                <input name="name" class="form-control" placeholder="Enter full name" required value="<?=htmlspecialchars($_POST['name'] ?? '')?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email Address</label>
                                <input type="email" name="email" class="form-control" placeholder="example@mail.com" required value="<?=htmlspecialchars($_POST['email'] ?? '')?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Password</label>
                                <input type="password" name="password" class="form-control" placeholder="Min. 6 characters" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Confirm Password</label>
                                <input type="password" name="password2" class="form-control" placeholder="Repeat password" required>
                            </div>
                        </div>

                        <h5 class="section-title mt-4"><i class="fas fa-user me-2"></i> Personal Information</h5>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Age</label>
                                <input type="number" name="age" class="form-control" required value="<?=htmlspecialchars($_POST['age'] ?? '')?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Gender</label>
                                <select name="gender" class="form-select" required>
                                    <option value="">Select</option>
                                    <option value="Male" <?= (($_POST['gender'] ?? '')==='Male')?' selected':''?>>Male</option>
                                    <option value="Female" <?= (($_POST['gender'] ?? '')==='Female')?' selected':''?>>Female</option>
                                    <option value="Other" <?= (($_POST['gender'] ?? '')==='Other')?' selected':''?>>Other</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Phone Number</label>
                                <input name="phone" class="form-control" placeholder="017xxxxxxxx" required value="<?=htmlspecialchars($_POST['phone'] ?? '')?>">
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Present Address</label>
                                <textarea name="address" class="form-control" rows="2" placeholder="House, Road, Area..." required><?=htmlspecialchars($_POST['address'] ?? '')?></textarea>
                            </div>
                        </div>

                        <h5 class="section-title mt-4"><i class="fas fa-hospital me-2"></i> Admission Category</h5>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Patient Type</label>
                                <select name="patient_type" id="patient_type" class="form-select border-primary">
                                    <option value="Outdoor" <?= (($_POST['patient_type'] ?? '')==='Outdoor')?'selected':'' ?>>Outdoor (Consultation Only)</option>
                                    <option value="Indoor" <?= (($_POST['patient_type'] ?? '')==='Indoor')?'selected':'' ?>>Indoor (Stay in Hospital)</option>
                                </select>
                                <small class="text-muted">Indoor patients require a guardian.</small>
                            </div>
                        </div>

                        <div id="indoorFields" class="mt-3" style="display: <?= (($_POST['patient_type'] ?? '')==='Indoor') ? 'block' : 'none' ?>">
                            <h6 class="mb-3 text-primary fw-bold">Guardian Details</h6>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Guardian Name</label>
                                    <input name="guardian_name" class="form-control" value="<?=htmlspecialchars($_POST['guardian_name'] ?? '')?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Guardian Phone</label>
                                    <input name="guardian_phone" class="form-control" value="<?=htmlspecialchars($_POST['guardian_phone'] ?? '')?>">
                                </div>
                            </div>
                        </div>

                        <div class="d-grid gap-2 mt-5">
                            <button class="btn btn-primary btn-lg shadow">Complete Registration</button>
                            <p class="text-center mt-3">Already have an account? <a href="login.php">Login here</a></p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('patient_type').addEventListener('change', function(){
    const fields = document.getElementById('indoorFields');
    if(this.value === 'Indoor') {
        fields.style.display = 'block';
        fields.classList.add('animate__animated', 'animate__fadeIn');
    } else {
        fields.style.display = 'none';
    }
});
</script>

<?php include 'includes/footer.php'; ?>