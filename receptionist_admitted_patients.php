<?php
session_start();
include 'config/db.php';
include 'includes/header.php';

// This query pulls all patient info and checks their total unpaid bills
$query = "SELECT p.*, 
          (SELECT SUM(amount) FROM billing WHERE patient_id = p.patient_id AND status = 'Unpaid') as unpaid_total,
          (SELECT COUNT(*) FROM billing WHERE patient_id = p.patient_id) as total_charges
          FROM patients p 
          WHERE p.status = 'Admitted' 
          ORDER BY p.admission_date DESC";
$res = $conn->query($query);
?>

<div class="container-fluid my-5 px-4">
    <div class="card shadow border-0">
        <div class="card-header bg-dark text-white p-3">
            <h4 class="mb-0">Current Admitted Patients (Indoor)</h4>
        </div>
        <div class="card-body p-0">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th>Patient & Guardian</th>
                        <th>Medical Info</th>
                        <th>Ward/Bed</th>
                        <th>Bill Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($p = $res->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($p['name']) ?></strong><br>
                            <small class="text-muted">Guardian: <?= htmlspecialchars($p['guardian_name']) ?> (<?= $p['guardian_phone'] ?>)</small>
                        </td>
                        <td>
                            <span class="badge bg-danger">Blood: <?= $p['blood_group'] ?? 'N/A' ?></span><br>
                            <small><?= htmlspecialchars($p['admission_reason']) ?></small>
                        </td>
                        <td>
                            <span class="text-primary fw-bold"><?= $p['ward'] ?></span><br>
                            <span class="badge bg-info text-dark">Bed: <?= $p['bed'] ?></span>
                        </td>
                        <td>
                            <?php if($p['total_charges'] == 0): ?>
                                <span class="badge bg-secondary">No Bill Added</span>
                            <?php elseif($p['unpaid_total'] > 0): ?>
                                <span class="text-danger fw-bold">DUE: Tk. <?= number_format($p['unpaid_total'], 2) ?></span>
                            <?php else: ?>
                                <span class="text-success fw-bold">✓ Fully Paid</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <a href="add_bill.php?id=<?= $p['patient_id'] ?>" class="btn btn-sm btn-outline-primary">Add Charge</a>
                            <a href="collect_payment.php?id=<?= $p['patient_id'] ?>" class="btn btn-sm btn-warning">Payment</a>
                            <a href="print_invoice.php?id=<?= $p['patient_id'] ?>" target="_blank" class="btn btn-sm btn-info">
    <i class="fas fa-print"></i> Print Bill
</a>
                            <?php if($p['total_charges'] > 0 && $p['unpaid_total'] == 0): ?>
                                <a href="process_discharge.php?p_id=<?= $p['patient_id'] ?>&bed_no=<?= urlencode($p['bed']) ?>" 
                                   class="btn btn-sm btn-danger" onclick="return confirm('Discharge patient?')">Discharge</a>
                            <?php else: ?>
                                <button class="btn btn-sm btn-secondary" disabled>Discharge</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>