<?php
session_start();
include 'config/db.php';
include 'includes/header.php';

$sql = "SELECT lt.*, p.name as patient_name FROM lab_tests lt 
        LEFT JOIN patients p ON lt.patient_id = p.patient_id 
        ORDER BY lt.test_id DESC";
$result = $conn->query($sql);
?>

<div class="container mt-5">
    <h2>Laboratory Queue</h2>
    <table class="table table-bordered bg-white">
        <thead>
            <tr>
                <th>Patient</th>
                <th>Test</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= $row['patient_name'] ?> (ID: <?= $row['patient_id'] ?>)</td>
                <td><?= $row['test_name'] ?></td>
                <td><?= $row['status'] ?></td>
                <td>
                    <?php if($row['status'] != 'completed'): ?>
                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#report<?= $row['test_id'] ?>">Write Report</button>
                    <?php else: ?>
                        <span class="text-success">Done</span>
                    <?php endif; ?>
                </td>
            </tr>

            <div class="modal fade" id="report<?= $row['test_id'] ?>" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form action="lab_submit_logic.php" method="POST">
                            <div class="modal-header">
                                <h5 class="modal-title">Submit Result: <?= $row['test_name'] ?></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <input type="hidden" name="test_id" value="<?= $row['test_id'] ?>">
                                <input type="hidden" name="patient_id" value="<?= $row['patient_id'] ?>">
                                <input type="hidden" name="test_name" value="<?= $row['test_name'] ?>">
                                
                                <div class="mb-3">
                                    <label class="form-label">Manual Findings/Report:</label>
                                    <textarea name="result_text" class="form-control" rows="4" required></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Billing Amount:</label>
                                    <input type="number" name="billing_amount" class="form-control" placeholder="500.00" required>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="submit" name="submit_report" class="btn btn-success w-100">SUBMIT AND BILL</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>