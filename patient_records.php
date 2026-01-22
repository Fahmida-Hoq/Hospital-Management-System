<?php
session_start();
include 'config/db.php';
include 'includes/header.php';

if (!isset($_SESSION['patient_id'])) {
    header("Location: login.php");
    exit();
}

$patient_id = $_SESSION['patient_id'];


$info_sql = "SELECT p.*, a.admission_id, a.admission_date, a.admission_fee, b.ward_name, b.bed_number 
             FROM patients p 
             LEFT JOIN admissions a ON p.patient_id = a.patient_id AND a.status = 'admitted'
             LEFT JOIN beds b ON a.bed_id = b.bed_id
             WHERE p.patient_id = $patient_id";
$info_res = $conn->query($info_sql);
$info = $info_res->fetch_assoc();

$adm_id = $info['admission_id'] ?? 0;
$admission_reg_fee = (float)($info['admission_fee'] ?? 0);
$total_charges = 0;
$bed_fee = 0;
$doc_fee = 0;

if ($adm_id > 0) {
    $days = (new DateTime($info['admission_date']))->diff(new DateTime(date('Y-m-d')))->days ?: 1;
    $bed_rate = (strpos($info['ward_name'], 'ICU') !== false) ? 5000 : 1500;
    $bed_fee = $days * $bed_rate;
    $doc_fee = 500.00; 
    $total_charges += ($bed_fee + $doc_fee + $admission_reg_fee);
}

$lab_query = "SELECT SUM(test_fees) as total FROM lab_tests WHERE patient_id = $patient_id AND status = 'completed'";
$lab_data = $conn->query($lab_query)->fetch_assoc();
$total_lab = (float)($lab_data['total'] ?? 0);
$total_charges += $total_lab;


$pay_query = "SELECT SUM(amount) as total_paid FROM billing WHERE patient_id = $patient_id AND status = 'paid'";
$pay_data = $conn->query($pay_query)->fetch_assoc();
$total_paid = (float)($pay_data['total_paid'] ?? 0);

$current_due = $total_charges - $total_paid;
?>

<div class="container my-5">
    <div class="row mb-4 align-items-center">
        <div class="col">
            <h2 class="fw-bold">Records</h2>
            <p class="text-muted">Welcome back, <?= htmlspecialchars($info['name'] ?? 'Patient') ?></p>
        </div>
        <div class="col-auto">
            <?php if($adm_id > 0): ?>
                <span class="badge bg-danger p-2 px-4 shadow-sm">Status: Currently Admitted (Indoor)</span>
            <?php else: ?>
                <span class="badge bg-success p-2 px-4 shadow-sm">Status: Outpatient (Outdoor)</span>
            <?php endif; ?>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-primary text-white fw-bold">Admission & Stay Details</div>
                <div class="card-body">
                    <?php if($adm_id > 0): ?>
                        <p class="mb-2"><strong>Ward:</strong> <?= htmlspecialchars($info['ward_name']) ?></p>
                        <p class="mb-2"><strong>Bed Number:</strong> <?= htmlspecialchars($info['bed_number']) ?></p>
                        <p class="mb-0"><strong>Admission Date:</strong> <?= date('d M, Y', strtotime($info['admission_date'])) ?></p>
                    <?php else: ?>
                        <div class="text-center py-3">
                            <i class="fas fa-user-clock fa-2x text-muted mb-2"></i>
                            <p class="text-muted mb-0">No active admission record found.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-info text-white fw-bold">Recent Lab Results</div>
                <div class="list-group list-group-flush">
                    <?php
                    $lab_q = $conn->query("SELECT * FROM lab_tests WHERE patient_id = $patient_id AND status = 'completed' ORDER BY created_at DESC LIMIT 5");
                    if($lab_q->num_rows > 0):
                        while($lab = $lab_q->fetch_assoc()): ?>
                            <div class="list-group-item">
                                <small class="text-muted"><?= date('d M', strtotime($lab['created_at'])) ?></small>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="fw-bold small"><?= $lab['test_name'] ?></span>
                                    <span class="badge bg-light text-primary border"><?= $lab['result'] ?></span>
                                </div>
                            </div>
                        <?php endwhile; 
                    else: echo "<p class='p-3 text-muted small'>No results found.</p>"; endif; ?>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card border-0 shadow-lg bg-dark text-white mb-4">
                <div class="card-body p-4">
                    <div class="row align-items-center">
                        <div class="col">
                            <h6 class="text-uppercase small opacity-75">Net Balance Due</h6>
                            <h1 class="display-5 fw-bold">TK <?= number_format(max(0, $current_due), 2) ?></h1>
                            <p class="small text-info mb-0">Total Bill: TK <?= number_format($total_charges, 2) ?> | Paid: TK <?= number_format($total_paid, 2) ?></p>
                        </div>
                        <div class="col-auto">
                            <?php if($current_due > 0): ?>
                                <button type="button" class="btn btn-success btn-lg px-5 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#payOnlineModal">
                                    PAY ONLINE
                                </button>
                            <?php else: ?>
                                <div class="text-center">
                                    <i class="fas fa-check-circle fa-2x text-success mb-1"></i>
                                    <div class="small fw-bold text-success">PAID</div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-bold py-3 border-bottom">Bill Breakdown & History</div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr><th>Date</th><th>Service</th><th>Type</th><th class="text-end">Amount</th></tr>
                        </thead>
                        <tbody>
                            <?php if($adm_id > 0): ?>
                                <tr>
                                    <td><?= date('d M, Y', strtotime($info['admission_date'])) ?></td>
                                    <td>Admission Registration Fee</td>
                                    <td><span class="badge bg-warning-subtle text-warning border">Indoor</span></td>
                                    <td class="text-end fw-bold">TK <?= number_format($admission_reg_fee, 2) ?></td>
                                </tr>
                                <tr>
                                    <td><?= date('d M, Y', strtotime($info['admission_date'])) ?></td>
                                    <td>Bed Charges (<?= $info['ward_name'] ?>)</td>
                                    <td><span class="badge bg-warning-subtle text-warning border">Indoor</span></td>
                                    <td class="text-end fw-bold">TK <?= number_format($bed_fee, 2) ?></td>
                                </tr>
                                <tr>
                                    <td><?= date('d M, Y') ?></td>
                                    <td>Consultation Fee</td>
                                    <td><span class="badge bg-warning-subtle text-warning border">Indoor</span></td>
                                    <td class="text-end fw-bold">TK <?= number_format($doc_fee, 2) ?></td>
                                </tr>
                            <?php endif; ?>
                            <?php 
                            $lab_bills = $conn->query("SELECT * FROM lab_tests WHERE patient_id = $patient_id AND status = 'completed'");
                            while($lb = $lab_bills->fetch_assoc()): ?>
                                <tr>
                                    <td><?= date('d M, Y', strtotime($lb['created_at'])) ?></td>
                                    <td>Lab: <?= $lb['test_name'] ?></td>
                                    <td><span class="badge bg-info-subtle text-info border">Outdoor</span></td>
                                    <td class="text-end fw-bold">TK <?= number_format($lb['test_fees'], 2) ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="payOnlineModal" tabindex="-1" aria-labelledby="payOnlineModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="process_payment_discharge.php" method="POST">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="payOnlineModalLabel">Online Payment</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" name="patient_id" value="<?= $patient_id ?>">
                    <input type="hidden" name="adm_id" value="<?= $adm_id ?>">
                    <input type="hidden" name="source" value="patient_portal">

                    <div class="mb-4 text-center">
                        <label class="form-label text-muted small fw-bold text-uppercase">Total Payable (TK)</label>
                        <input type="number" name="total_amount" class="form-control form-control-lg fw-bold text-center fs-2" 
                               value="<?= $current_due ?>" max="<?= $current_due ?>" min="1" required>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" name="payment_method" value="bKash" class="btn btn-outline-danger py-3 fw-bold">Pay via bKash</button>
                        <button type="submit" name="payment_method" value="Card" class="btn btn-outline-primary py-3 fw-bold">Pay via Credit/Debit Card</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php include 'includes/footer.php'; ?>
