<?php
session_start();
include 'config/db.php';
include 'includes/header.php';

// Role Check
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['lab','labtech'])) {
    header("Location: login.php");
    exit();
}

// Handle Status Updates
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $new_status = ($_GET['action'] == 'start') ? 'processing' : 'completed';
    $conn->query("UPDATE lab_tests SET status = '$new_status' WHERE test_id = $id");
    header("Location: lab_manage_tests.php");
}

// Fetch Tests: Fixed the query to join Patients and Appointments correctly
$sql = "SELECT lt.*, p.name as patient_name, p.phone 
        FROM lab_tests lt 
        JOIN appointments a ON lt.appointment_id = a.appointment_id 
        JOIN patients p ON a.patient_id = p.patient_id 
        ORDER BY lt.test_id DESC";
$result = $conn->query($sql);
?>

<div class="container my-5">
    <h3 class="mb-4">Laboratory Queue</h3>
    <div class="table-responsive bg-white p-3 shadow-sm rounded">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>Patient</th>
                    <th>Test Name</th>
                    <th>Ordered Date</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td>
                        <strong><?= htmlspecialchars($row['patient_name']) ?></strong><br>
                        <small class="text-muted"><?= htmlspecialchars($row['phone']) ?></small>
                    </td>
                    <td><?= htmlspecialchars($row['test_name']) ?></td>
                    <td><?= date('d M, Y', strtotime($row['created_at'])) ?></td>
                    <td>
                        <?php 
                        $badge = ($row['status'] == 'pending') ? 'bg-warning' : (($row['status'] == 'processing') ? 'bg-info' : 'bg-success');
                        echo "<span class='badge $badge'>".ucfirst($row['status'])."</span>";
                        ?>
                    </td>
                    <td>
                        <?php if($row['status'] == 'pending'): ?>
                            <a href="?action=start&id=<?= $row['test_id'] ?>" class="btn btn-sm btn-primary">Start Testing</a>
                        <?php elseif($row['status'] == 'processing'): ?>
                            <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#resModal<?= $row['test_id'] ?>">Complete</button>
                        <?php else: ?>
                            <span class="text-success small">Done</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>