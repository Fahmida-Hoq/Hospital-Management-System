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

    // validation
    if ($name === '') $errors[] = 'Name required';
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email required';
    if (strlen($password) < 6) $errors[] = 'Password >= 6 chars';
    if ($password !== $password2) $errors[] = 'Password mismatch';
    if ($age <= 0) $errors[] = 'Valid age required';
    if ($gender === '') $errors[] = 'Gender required';
    if ($phone === '') $errors[] = 'Phone required';
    if ($patient_type === 'Indoor' && ($guardian_name === '' || $guardian_phone === '')) $errors[] = 'Guardian required for Indoor';

    if (empty($errors)) {
        // transaction: users -> patients
        $conn->begin_transaction();
        try {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $u = $conn->prepare("INSERT INTO users (full_name, email, password, role, created_at) VALUES (?, ?, ?, 'patient', NOW())");
            if (!$u) throw new Exception("Prepare users failed: " . $conn->error);
            $u->bind_param("sss", $name, $email, $hash);
            $u->execute();
            $user_id = $conn->insert_id;
            $u->close();

            $p = $conn->prepare("INSERT INTO patients (name, email, password, patient_type, user_id, age, gender, address, phone, guardian_name, guardian_phone, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', NOW())");
            if (!$p) throw new Exception("Prepare patients failed: " . $conn->error);
            $p->bind_param("ssssisissss", $name, $email, $hash, $patient_type, $user_id, $age, $gender, $address, $phone, $guardian_name, $guardian_phone);
            $p->execute();
            $p->close();

            $conn->commit();
            header("Location: login.php?registered=1");
            exit;
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = "DB error: " . $e->getMessage();
        }
    }
}
?>

<div class="container my-5">
  <h2>Patient Registration</h2>
  <?php if(!empty($errors)): ?>
    <div class="alert alert-danger">
      <?php foreach($errors as $er) echo '<div>'.htmlspecialchars($er).'</div>'; ?>
    </div>
  <?php endif; ?>

  <form method="post" class="card p-4 shadow-sm">
    <div class="row">
      <div class="col-md-6 mb-3"><label>Full Name</label><input name="name" class="form-control" required value="<?=htmlspecialchars($_POST['name'] ?? '')?>"></div>
      <div class="col-md-6 mb-3"><label>Email</label><input type="email" name="email" class="form-control" required value="<?=htmlspecialchars($_POST['email'] ?? '')?>"></div>
      <div class="col-md-6 mb-3"><label>Password</label><input type="password" name="password" class="form-control" required></div>
      <div class="col-md-6 mb-3"><label>Confirm Password</label><input type="password" name="password2" class="form-control" required></div>
      <div class="col-md-4 mb-3"><label>Age</label><input type="number" name="age" class="form-control" required value="<?=htmlspecialchars($_POST['age'] ?? '')?>"></div>
      <div class="col-md-4 mb-3"><label>Gender</label><select name="gender" class="form-select" required><option value="">Select</option><option<?= (($_POST['gender'] ?? '')==='Male')?' selected':''?>>Male</option><option<?= (($_POST['gender'] ?? '')==='Female')?' selected':''?>>Female</option><option<?= (($_POST['gender'] ?? '')==='Other')?' selected':''?>>Other</option></select></div>
      <div class="col-md-4 mb-3"><label>Phone</label><input name="phone" class="form-control" required value="<?=htmlspecialchars($_POST['phone'] ?? '')?>"></div>
      <div class="col-md-12 mb-3"><label>Address</label><textarea name="address" class="form-control" required><?=htmlspecialchars($_POST['address'] ?? '')?></textarea></div>

      <div class="col-md-6 mb-3">
        <label>Patient Type</label>
        <select name="patient_type" id="patient_type" class="form-select">
          <option value="Outdoor" <?= (($_POST['patient_type'] ?? '')==='Outdoor')?'selected':'' ?>>Outdoor</option>
          <option value="Indoor" <?= (($_POST['patient_type'] ?? '')==='Indoor')?'selected':'' ?>>Indoor</option>
        </select>
      </div>
    </div>

    <div id="indoorFields" style="display: <?= (($_POST['patient_type'] ?? '')==='Indoor') ? 'block' : 'none' ?>">
      <h5 class="mt-3">Guardian details (Indoor)</h5>
      <div class="row">
        <div class="col-md-6 mb-3"><label>Guardian Name</label><input name="guardian_name" class="form-control" value="<?=htmlspecialchars($_POST['guardian_name'] ?? '')?>"></div>
        <div class="col-md-6 mb-3"><label>Guardian Phone</label><input name="guardian_phone" class="form-control" value="<?=htmlspecialchars($_POST['guardian_phone'] ?? '')?>"></div>
      </div>
    </div>

    <button class="btn btn-primary mt-3">Register</button>
  </form>
</div>

<script>
document.getElementById('patient_type').addEventListener('change', function(){
    document.getElementById('indoorFields').style.display = (this.value === 'Indoor') ? 'block' : 'none';
});
</script>

<?php include 'includes/footer.php'; ?>
