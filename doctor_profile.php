<?php
session_start();
include 'config/db.php';
include 'includes/header.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header("Location: login.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$success = "";
$errors = [];

/*  FETCH DOCTOR INFO  */
$sql = "
SELECT d.*, u.full_name, u.email 
FROM doctors d 
JOIN users u ON d.user_id = u.user_id
WHERE d.user_id = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$doctor = $stmt->get_result()->fetch_assoc();
$stmt->close();

/* UPDATE PROFILE  */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['full_name']);
    $phone = trim($_POST['phone']);
    $specialization = trim($_POST['specialization']);
    $experience = trim($_POST['experience']);
    $password = trim($_POST['password']);

    if ($name === '' || $phone === '' || $specialization === '') {
        $errors[] = "Name, Phone, and Specialization are required.";
    }

    if (empty($errors)) {

        // update users table
        $u = $conn->prepare("UPDATE users SET full_name=?, phone=? WHERE user_id=?");
        $u->bind_param("ssi", $name, $phone, $user_id);
        $u->execute();
        $u->close();

        // update doctors table
        $d = $conn->prepare("UPDATE doctors SET specialization=?, experience=? WHERE user_id=?");
        $d->bind_param("ssi", $specialization, $experience, $user_id);
        $d->execute();
        $d->close();

        // password update (optional)
        if ($password !== '') {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $p = $conn->prepare("UPDATE users SET password=? WHERE user_id=?");
            $p->bind_param("si", $hash, $user_id);
            $p->execute();
            $p->close();
        }

        $_SESSION['full_name'] = $name;
        $success = "Profile updated successfully.";
    }
}
?>

<div class="container my-5">
    <h3>⚙ Doctor Profile</h3>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>

    <?php if ($errors): ?>
        <div class="alert alert-danger">
            <?php foreach ($errors as $e) echo "<div>$e</div>"; ?>
        </div>
    <?php endif; ?>

    <form method="post" class="card p-4 mt-3">
        <div class="mb-3">
            <label>Full Name</label>
            <input name="full_name" class="form-control" value="<?= htmlspecialchars($doctor['full_name']) ?>">
        </div>

        <div class="mb-3">
            <label>Email (readonly)</label>
            <input class="form-control" value="<?= htmlspecialchars($doctor['email']) ?>" readonly>
        </div>

        <div class="mb-3">
            <label>Phone</label>
            <input name="phone" class="form-control" value="<?= htmlspecialchars($doctor['phone']) ?>">
        </div>

        <div class="mb-3">
            <label>Specialization</label>
            <input name="specialization" class="form-control" value="<?= htmlspecialchars($doctor['specialization']) ?>">
        </div>

        <div class="mb-3">
            <label>New Password (optional)</label>
            <input name="password" type="password" class="form-control">
        </div>

        <button class="btn btn-primary">Update Profile</button>
        <a href="doctor_dashboard.php" class="btn btn-secondary">Back</a>
    </form>
</div>

<?php include 'includes/footer.php'; ?>
