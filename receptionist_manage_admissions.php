<?php
session_start();
include 'config/db.php';
include 'includes/header.php';

// Fixed Query to match your screenshot
$query = "SELECT ar.*, p.name, p.phone 
          FROM admission_requests ar 
          JOIN patients p ON ar.patient_id = p.patient_id 
          WHERE ar.request_status LIKE '%Pending Reception%'";

$res = $conn->query($query);
?>

<div class="container my-5">
    <h3>Pending Admission Requests</h3>
    <?php if ($res->num_rows == 0): ?>
        <div class="alert alert-info">No pending requests found in the database.</div>
    <?php else: ?>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Patient</th>
                    <th>Suggested Ward</th>
                    <th>Reason</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $res->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['name']) ?></td>
                    <td><?= htmlspecialchars($row['suggested_ward']) ?></td>
                    <td><?= htmlspecialchars($row['reason']) ?></td>
                    <td>
                        <a href="receptionist_admission_form.php?id=<?= $row['request_id'] ?>" class="btn btn-primary">Process</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>