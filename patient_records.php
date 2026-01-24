<?php
session_start();
include 'config/db.php';
include 'includes/header.php';

if (!isset($_SESSION['patient_id'])) {
    header("Location: login.php");
    exit();
}

$patient_id = $_SESSION['patient_id'];

// Fetch Patient and Admission Info
$info_sql = "SELECT p.*, a.admission_id, a.admission_date, a.admission_fee, b.ward_name, b.bed_number 
             FROM patients p 
             LEFT JOIN admissions a ON p.patient_id = a.patient_id AND a.status = 'admitted'
             LEFT JOIN beds b ON a.bed_id = b.bed_id
             WHERE p.patient_id = $patient_id";
$info_res = $conn->query($info_sql);
$info = $info_res->fetch_assoc();

$adm_id = $info['admission_id'] ?? 0;
$admission_reg_fee = (float)($info['admission_fee'] ?? 0);

// --- UPDATED FINANCIAL LOGIC ---
$total_charges = 0;
$total_settled = 0;


if ($adm_id > 0) {
    $admission_date = $info['admission_date'];
    $days = (new DateTime($admission_date))->diff(new DateTime(date('Y-m-d')))->days ?: 1;
    $bed_rate = (strpos($info['ward_name'], 'ICU') !== false) ? 5000 : 1500;
    
    $bed_fee = $days * $bed_rate;
    $standard_doc_fee = 500.00; 
    
    $total_charges += ($bed_fee + $standard_doc_fee + $admission_reg_fee);

   
    $appt_query = "SELECT COUNT(*) as extra_visits FROM appointments 
                   WHERE patient_id = $patient_id AND scheduled_time >= '$admission_date' AND status = 'Confirmed'";
    $extra_count = (int)$conn->query($appt_query)->fetch_assoc()['extra_visits'];
    $extra_fees = $extra_count * 500.00;
    $total_charges += $extra_fees;
}

$lab_query = "SELECT test_fees, payment_status FROM lab_tests WHERE patient_id = $patient_id AND status = 'completed'";
$lab_res = $conn->query($lab_query);
while($lab = $lab_res->fetch_assoc()) {
    $fee = (float)$lab['test_fees'];
    $total_charges += $fee;

    if($lab['payment_status'] == 'paid' || $lab['payment_status'] == 'Paid') {
        $total_settled += $fee;
    }
}

$pay_query = "SELECT SUM(amount) as total_paid FROM billing WHERE patient_id = $patient_id AND status = 'paid'";
$total_settled += (float)($conn->query($pay_query)->fetch_assoc()['total_paid'] ?? 0);

$current_due = $total_charges - $total_settled;
?>

<div class="container my-5">
    <div class="row mb-4 align-items-center">
        <div class="col">
            <h2 class="fw-bold">Records</h2>
            <p class="text-muted">Welcome back, <?= htmlspecialchars($info['name']) ?></p>
        </div>
        <div class="col-auto">
            <span class="badge <?= ($adm_id > 0) ? 'bg-danger' : 'bg-success' ?> p-2 px-4 shadow-sm">
                Status: <?= ($adm_id > 0) ? 'Currently Admitted (Indoor)' : 'Outpatient (Outdoor)' ?>
            </span>
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
                        <p class="text-muted mb-0">No active admission record found.</p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-info text-white fw-bold">Recent Lab Results</div>
                <div class="list-group list-group-flush">
                    <?php
                    $res_q = $conn->query("SELECT * FROM lab_tests WHERE patient_id = $patient_id AND status = 'completed' ORDER BY created_at DESC LIMIT 3");
                    while($lr = $res_q->fetch_assoc()): ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <small class="fw-bold"><?= $lr['test_name'] ?></small>
                            <span class="badge bg-light text-primary border"><?= $lr['result'] ?></span>
                        </div>
                    <?php endwhile; ?>
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
                            <p class="small text-info mb-0">Total Bill: TK <?= number_format($total_charges, 2) ?> | Settled: TK <?= number_format($total_settled, 2) ?></p>
                        </div>
                        <div class="col-auto">
                            <?php if($current_due <= 0): ?>
                                <span class="h4 text-success fw-bold"><i class="fas fa-check-circle"></i> FULLY PAID</span>
                            <?php else: ?>
                              
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-bold py-3 border-bottom">Service & Payment History</div>
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
                                <?php if($extra_count > 0): ?>
                                <tr>
                                    <td>-</td>
                                    <td>Extra Doctor Consultations (<?= $extra_count ?> visits)</td>
                                    <td><span class="badge bg-warning-subtle text-warning border">Indoor</span></td>
                                    <td class="text-end fw-bold">TK <?= number_format($extra_fees, 2) ?></td>
                                </tr>
                                <?php endif; ?>
                            <?php endif; ?>

                            <?php 
                            $labs = $conn->query("SELECT * FROM lab_tests WHERE patient_id = $patient_id AND status = 'completed' ORDER BY created_at DESC");
                            while($lb = $labs->fetch_assoc()): ?>
                                <tr>
                                    <td><?= date('d M, Y', strtotime($lb['created_at'])) ?></td>
                                    <td>
                                        Lab: <?= $lb['test_name'] ?> 
                                        <?php if($lb['payment_status'] == 'paid' || $lb['payment_status'] == 'Paid'): ?>
                                            <span class="badge bg-success-subtle text-success ms-1" style="font-size: 10px;"></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge border <?= ($adm_id > 0) ? 'bg-warning-subtle text-warning' : 'bg-info-subtle text-info' ?>">
                                            <?= ($adm_id > 0) ? 'Indoor' : 'Outdoor' ?>
                                        </span>
                                    </td>
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