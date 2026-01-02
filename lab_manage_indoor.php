<?php
session_start();
include 'config/db.php';
include 'includes/header.php';

$sql = "SELECT lt.*, p.name as patient_name 
        FROM lab_tests lt 
        JOIN patients p ON lt.patient_id = p.patient_id 
        WHERE lt.admission_id > 0 
        ORDER BY lt.status DESC, lt.test_id DESC";
$res = $conn->query($sql);
?>

<div class="container my-4">
    <h3 class="fw-bold border-bottom pb-2">INDOOR LABORATORY QUEUE</h3>
    
    <table class="table table-hover shadow-sm bg-white">
        <thead class="table-dark">
            <tr>
                <th>Patient</th>
                <th>Test Requested</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if($res && $res->num_rows > 0): ?>
                <?php while($row = $res->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['patient_name']) ?> (Adm ID: <?= $row['admission_id'] ?>)</td>
                    <td><?= htmlspecialchars($row['test_name']) ?></td>
                    <td>
                        <span class="badge <?= $row['status'] == 'pending' ? 'bg-warning text-dark' : 'bg-success' ?>">
                            <?= strtoupper($row['status']) ?>
                        </span>
                    </td>
                    <td>
                        <?php if($row['status'] == 'pending'): ?>
                            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#reportModal<?= $row['test_id'] ?>">
                                Write Report
                            </button>
                        <?php else: ?>
                            <span class="text-muted small">Done (<?= $row['test_fees'] ?> Tk)</span>
                        <?php endif; ?>
                    </td>
                </tr>

                <div class="modal fade" id="reportModal<?= $row['test_id'] ?>" tabindex="-1" aria-labelledby="label<?= $row['test_id'] ?>" aria-hidden="true">
                    <div class="modal-dialog">
                        <form action="lab_submit_logic_indoor.php" method="POST">
                            <div class="modal-content text-dark">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="label<?= $row['test_id'] ?>">Result: <?= htmlspecialchars($row['test_name']) ?></h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body text-start">
                                    <input type="hidden" name="test_id" value="<?= $row['test_id'] ?>">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Lab Findings:</label>
                                        <textarea name="findings" class="form-control" rows="4" required></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Test Fee (Tk):</label>
                                        <input type="number" name="fees" class="form-control" value="500.00" step="0.01">
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="submit" name="submit_report" class="btn btn-success w-100">SUBMIT FINAL REPORT</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="4" class="text-center py-4">No indoor lab requests found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>