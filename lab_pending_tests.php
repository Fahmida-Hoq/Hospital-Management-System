<?php
session_start();
include 'config/db.php';
include 'includes/header.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['lab','labtech'])) {
    header("Location: login.php");
    exit();
}

$tests = [];

$sql = "
SELECT 
    lt.test_id,
    lt.test_name,
    lt.status,
    p.name AS patient_name
FROM lab_tests lt
JOIN appointments a ON lt.appointment_id = a.appointment_id
JOIN patients p ON a.patient_id = p.patient_id
WHERE lt.status IN ('pending','processing')
ORDER BY lt.test_id DESC
";

$res = $conn->query($sql);
if ($res) {
    $tests = $res->fetch_all(MYSQLI_ASSOC);
}
?>

<div class="container my-5">
<h3> Pending Lab Tests</h3>

<?php if (!$tests): ?>
    <div class="alert alert-info">No pending tests.</div>
<?php else: ?>
<table class="table table-bordered">
<tr>
    <th>ID</th>
    <th>Patient</th>
    <th>Test</th>
    <th>Status</th>
    <th>Action</th>
</tr>
<?php foreach ($tests as $t): ?>
<tr>
    <td><?= $t['test_id'] ?></td>
    <td><?= htmlspecialchars($t['patient_name']) ?></td>
    <td><?= htmlspecialchars($t['test_name']) ?></td>
    <td><?= $t['status'] ?></td>
    <td>
        <a class="btn btn-sm btn-primary"
           href="lab_update_report.php?test_id=<?= $t['test_id'] ?>">
           Open
        </a>
    </td>
</tr>
<?php endforeach; ?>
</table>
<?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
