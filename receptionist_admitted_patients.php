<?php
session_start();
include 'config/db.php';
include 'includes/header.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'receptionist') {
    header("Location: login.php");
    exit();
}

$sql = "
    SELECT 
        p.patient_id,
        u.full_name AS patient_name,
        p.age,
        p.gender,
        p.ward,
        p.cabin,
        p.bed
    FROM patients p
    JOIN users u ON p.user_id = u.user_id
    WHERE p.status = 'Indoor'
    ORDER BY u.full_name
";

$result = $conn->query($sql);
?>

<div class="container mt-4">
    <h3>Admitted Patients</h3>

    <?php if ($result && $result->num_rows > 0): ?>
        <table class="table table-bordered table-striped mt-3">
            <thead>
                <tr>
                    <th>Patient Name</th>
                    <th>Age</th>
                    <th>Gender</th>
                    <th>Ward</th>
                    <th>Cabin</th>
                    <th>Bed</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['patient_name']) ?></td>
                    <td><?= htmlspecialchars($row['age']) ?></td>
                    <td><?= htmlspecialchars($row['gender']) ?></td>
                    <td><?= htmlspecialchars($row['ward']) ?></td>
                    <td><?= $row['cabin'] ? htmlspecialchars($row['cabin']) : '-' ?></td>
                    <td><?= htmlspecialchars($row['bed']) ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="alert alert-info">No admitted patients found.</div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
