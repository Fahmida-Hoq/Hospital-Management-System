<?php
session_start();
include 'config/db.php';
include 'includes/header.php';


  // AUTH CHECK
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['lab','labtech'])) {
    header("Location: login.php");
    exit();
}
   //GET TEST ID
$test_id = (int)($_GET['test_id'] ?? 0);
if ($test_id <= 0) {
    echo "<div class='container my-5 alert alert-danger'>Invalid lab test</div>";
    include 'includes/footer.php';
    exit();
}

$success = "";
$errors = [];

   //SAVE LAB RESULT
if (isset($_POST['save_result'])) {

    $result = trim($_POST['result']);

    if ($result === '') {
        $errors[] = "Result cannot be empty";
    } else {

        $sql = "
        UPDATE lab_tests 
        SET result = '$result',
            status = 'completed',
            doctor_notified = 0
        WHERE test_id = $test_id
        ";

        if ($conn->query($sql)) {
            $success = "Lab result submitted successfully";
        } else {
            $errors[] = "Update failed: " . $conn->error;
        }
    }
}


 //  FETCH LAB TEST
$sql = "
SELECT 
    lt.test_id,
    lt.test_name,
    lt.status,
    lt.result,
    p.name AS patient_name
FROM lab_tests lt
JOIN appointments a ON lt.appointment_id = a.appointment_id
JOIN patients p ON a.patient_id = p.patient_id
WHERE lt.test_id = $test_id
";

$res = $conn->query($sql);
$test = $res ? $res->fetch_assoc() : null;

if (!$test) {
    echo "<div class='container my-5 alert alert-danger'>Lab test not found</div>";
    include 'includes/footer.php';
    exit();
}
?>

<div class="container my-5">

<h3>Lab Test Report</h3>

<?php if ($success): ?>
    <div class="alert alert-success"><?= $success ?></div>
<?php endif; ?>

<?php foreach ($errors as $e): ?>
    <div class="alert alert-danger"><?= $e ?></div>
<?php endforeach; ?>

<div class="card p-4 shadow-sm">

    <p><strong>Patient:</strong> <?= htmlspecialchars($test['patient_name']) ?></p>
    <p><strong>Test Name:</strong> <?= htmlspecialchars($test['test_name']) ?></p>
    <p><strong>Status:</strong> <?= htmlspecialchars($test['status']) ?></p>

    <form method="post">
        <label class="mt-3">Test Result</label>
        <textarea name="result" class="form-control mb-3" rows="5"
                  <?= $test['status'] === 'completed' ? 'readonly' : '' ?>
        ><?= htmlspecialchars($test['result']) ?></textarea>

        <?php if ($test['status'] !== 'completed'): ?>
            <button name="save_result" class="btn btn-success">
                Save Result
            </button>
        <?php else: ?>
            <div class="alert alert-info mt-3">
                This test is already completed.
            </div>
        <?php endif; ?>
    </form>

</div>

<a href="lab_pending_tests.php" class="btn btn-secondary mt-3">⬅ Back</a>

</div>

<?php include 'includes/footer.php'; ?>
