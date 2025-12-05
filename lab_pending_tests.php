<?php
session_start();
include 'config/db.php';
include 'includes/header.php';
if (!isset($_SESSION['user_id']) || (!in_array($_SESSION['role'], ['lab','labtech']))) {
    header("Location: login.php");
    exit();
}

$stmt = $conn->prepare("SELECT lt.*, COALESCE(p.name, u.full_name) AS patient_name
                        FROM lab_tests lt
                        JOIN patients p ON lt.patient_id = p.patient_id
                        LEFT JOIN users u ON p.user_id = u.user_id
                        WHERE lt.status IN ('pending','processing')
                        ORDER BY lt.date_requested ASC");
$stmt->execute();
$tests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<div class="container my-5">
    <h3>Pending / Processing Tests</h3>

    <?php if (empty($tests)): ?>
        <div class="alert alert-info">No pending tests.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead><tr><th>ID</th><th>Patient</th><th>Test</th><th>Status</th><th>Requested</th><th>Action</th></tr></thead>
                <tbody>
                    <?php foreach ($tests as $t): ?>
                    <tr>
                        <td><?= (int)$t['test_id'] ?></td>
                        <td><?= htmlspecialchars($t['patient_name']) ?></td>
                        <td><?= htmlspecialchars($t['test_name']) ?></td>
                        <td><?= htmlspecialchars($t['status']) ?></td>
                        <td><?= htmlspecialchars($t['date_requested']) ?></td>
                        <td>
                            <a class="btn btn-sm btn-primary" href="lab_update_report.php?test_id=<?= (int)$t['test_id'] ?>">Open</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
