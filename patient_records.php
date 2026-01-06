<?php
session_start();
include 'config/db.php';
include 'includes/header.php';

// Ensure patient is logged in
if (!isset($_SESSION['patient_id'])) {
    header("Location: login.php");
    exit();
}

$patient_id = $_SESSION['patient_id'];

// 1. Get Patient & Admission Info
$info_sql = "SELECT p.*, a.admission_id, a.admission_date, b.ward_name, b.bed_number 
             FROM patients p 
             LEFT JOIN admissions a ON p.patient_id = a.patient_id AND a.status = 'admitted'
             LEFT JOIN beds b ON a.bed_id = b.bed_id
             WHERE p.patient_id = $patient_id";
$info_res = $conn->query($info_sql);
$info = $info_res->fetch_assoc();

$adm_id = $info['admission_id'] ?? 0;

// 2. Calculate Running Bill
$running_total = 0;
if ($adm_id > 0) {
    $days = (new DateTime($info['admission_date']))->diff(new DateTime(date('Y-m-d')))->days ?: 1;
    $bed_fee = $days * (($info['ward_name'] == 'ICU') ? 5000 : 1500);
    $doc_fee = 500.00;
    
    $lab_query = "SELECT SUM(test_fees) as total FROM lab_tests 
                  WHERE patient_id = $patient_id AND status = 'completed'";
    $lab_data = $conn->query($lab_query)->fetch_assoc();
    $total_lab = (float)($lab_data['total'] ?? 0);
    
    $running_total = $bed_fee + $doc_fee + $total_lab;
}

// 3. Get Payment History
$pay_query = "SELECT SUM(amount) as total_paid FROM billing WHERE patient_id = $patient_id AND status = 'paid'";
$pay_data = $conn->query($pay_query)->fetch_assoc();
$total_paid = (float)($pay_data['total_paid'] ?? 0);

$current_due = $running_total - $total_paid;
?>

<div class="container my-5">
    <div class="row mb-4 align-items-center">
        <div class="col">
            <h2 class="fw-bold">Patient Dashboard</h2>
            <p class="text-muted">Welcome back, <?= htmlspecialchars($info['name']) ?></p>
        </div>
        <div class="col-auto">
            <span class="badge bg-<?= ($adm_id > 0) ? 'danger' : 'success' ?> p-2 px-3">
                <?= ($adm_id > 0) ? 'Currently Admitted' : 'Outpatient' ?>
            </span>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-primary text-white fw-bold">My Stay Details</div>
                <div class="card-body">
                    <p class="mb-1"><strong>Ward:</strong> <?= $info['ward_name'] ?? 'N/A' ?></p>
                    <p class="mb-1"><strong>Bed No:</strong> <?= $info['bed_number'] ?? 'N/A' ?></p>
                    <p class="mb-0"><strong>Admitted On:</strong> <?= ($adm_id > 0) ? date('d M, Y', strtotime($info['admission_date'])) : 'N/A' ?></p>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-info text-white fw-bold">Recent Lab Results</div>
                <div class="list-group list-group-flush">
                    <?php
                    $lab_q = $conn->query("SELECT * FROM lab_tests WHERE patient_id = $patient_id AND status = 'completed' ORDER BY created_at DESC LIMIT 5");
                    if($lab_q && $lab_q->num_rows > 0):
                        while($lab = $lab_q->fetch_assoc()): ?>
                            <div class="list-group-item">
                                <small class="text-muted"><?= date('d M', strtotime($lab['created_at'])) ?></small>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="fw-bold"><?= htmlspecialchars($lab['test_name']) ?></span>
                                    <span class="text-primary fw-bold"><?= htmlspecialchars($lab['result']) ?></span>
                                </div>
                            </div>
                        <?php endwhile; 
                    else: echo "<p class='p-3 text-muted small'>No reports found.</p>"; endif; ?>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card border-0 shadow-lg bg-dark text-white mb-4">
                <div class="card-body p-4">
                    <div class="row align-items-center">
                        <div class="col">
                            <h6 class="text-uppercase small opacity-75">Current Net Balance Due</h6>
                            <h1 class="display-5 fw-bold">TK <?= number_format(max(0, $current_due), 2) ?></h1>
                            <p class="mb-0 small text-info">Total Charges: TK <?= number_format($running_total, 2) ?> | Paid: TK <?= number_format($total_paid, 2) ?></p>
                        </div>
                        <div class="col-auto">
                            <?php if($current_due > 0 && $adm_id > 0): ?>
                                <button type="button" class="btn btn-success btn-lg px-4 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#payModal">
                                    <i class="fas fa-wallet me-2"></i> PAY NOW
                                </button>
                            <?php else: ?>
                                <span class="badge bg-success p-2">UP TO DATE</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-bold py-3">Transaction & Service History</div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Description</th>
                                <th>Type</th>
                                <th>Method</th>
                                <th class="text-end">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $lab_bills = $conn->query("SELECT * FROM lab_tests WHERE patient_id = $patient_id AND status = 'completed' ORDER BY created_at DESC");
                            while($lb = $lab_bills->fetch_assoc()): ?>
                            <tr>
                                <td class="small"><?= date('d M, Y', strtotime($lb['created_at'])) ?></td>
                                <td><span class="fw-bold">Lab: <?= htmlspecialchars($lb['test_name']) ?></span></td>
                                <td><span class="badge bg-secondary-subtle text-secondary border">Service</span></td>
                                <td><small class="text-muted">N/A</small></td>
                                <td class="text-end fw-bold text-danger">TK <?= number_format($lb['test_fees'], 2) ?></td>
                            </tr>
                            <?php endwhile; ?>

                            <?php 
                            $bills = $conn->query("SELECT * FROM billing WHERE patient_id = $patient_id AND status = 'paid' ORDER BY billing_date DESC");
                            while($b = $bills->fetch_assoc()): ?>
                            <tr class="table-success-subtle">
                                <td class="small"><?= date('d M, Y', strtotime($b['billing_date'])) ?></td>
                                <td><span class="fw-bold"><?= htmlspecialchars($b['description']) ?></span></td>
                                <td><span class="badge bg-success">Payment</span></td>
                                <td><span class="fw-bold small"><?= htmlspecialchars($b['payment_method'] ?? 'N/A') ?></span></td>
                                <td class="text-end fw-bold text-success">TK <?= number_format($b['amount'], 2) ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="payModal" tabindex="-1" aria-labelledby="payModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="payment_gateway.php" method="POST">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="payModalLabel">Make a Payment</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" name="adm_id" value="<?= $adm_id ?>">
                    <input type="hidden" name="is_partial" value="1">
                    
                    <div class="mb-4 text-center">
                        <label class="form-label text-muted">Amount to Pay</label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text">TK</span>
                            <input type="number" name="total_amount" class="form-control fw-bold text-center" 
                                   value="<?= $current_due ?>" max="<?= $current_due ?>" min="1" required>
                        </div>
                    </div>

                    <label class="form-label small fw-bold">Select Method</label>
                    <div class="d-flex gap-3">
                        <div class="w-100">
                            <input type="radio" class="btn-check" name="pay_method" id="pay_bkash" value="bKash" checked>
                            <label class="btn btn-outline-danger w-100 py-3 fw-bold" for="pay_bkash">bKash</label>
                        </div>
                        <div class="w-100">
                            <input type="radio" class="btn-check" name="pay_method" id="pay_card" value="Card">
                            <label class="btn btn-outline-primary w-100 py-3 fw-bold" for="pay_card">Card</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="submit" class="btn btn-success btn-lg w-100 fw-bold">PROCEED TO SECURE PAYMENT</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<?php include 'includes/footer.php'; ?>