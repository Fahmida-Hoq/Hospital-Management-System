<?php
session_start();
include 'config/db.php';
include 'includes/header.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header("Location: login.php");
    exit();
}

$doctor_user_id = (int)$_SESSION['user_id'];
$doctor_id = 0;
$stmt = $conn->prepare("SELECT doctor_id FROM doctors WHERE user_id = ?");
if ($stmt) {
    $stmt->bind_param("i", $doctor_user_id);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    $doctor_id = (int)($r['doctor_id'] ?? 0);
    $stmt->close();
}
$reports = [];
$stmt = $conn->prepare("
    SELECT l.*, p.name as patient_name 
    FROM lab_tests l 
    JOIN patients p ON l.patient_id = p.patient_id 
    WHERE l.doctor_id = ? 
      AND l.status = 'completed'
    ORDER BY l.test_id DESC
");

if ($stmt) {
    $stmt->bind_param("i", $doctor_id);
    $stmt->execute();
    $reports = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-flask"></i> Lab Test Notifications</h2>
        <a href="doctor_dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <?php if (!empty($reports)): ?>
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Patient</th>
                            <th>Test Name</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reports as $row): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($row['patient_name']) ?></strong></td>
                            <td><?= htmlspecialchars($row['test_name']) ?></td>
                            <td><span class="badge bg-success">Completed</span></td>
                            <td>
                                <a href="lab_view_test.php?id=<?= $row['test_id'] ?>" class="btn btn-sm btn-primary">View Results</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="alert alert-info text-center">
                    <p class="mb-0">No completed lab reports found for your patients.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>