<?php
session_start();
include 'config/db.php';
include 'includes/header.php';

$adm_id = (int)$_GET['adm_id'];

$sql = "SELECT a.*, p.name, p.patient_id, b.ward_name, b.bed_number 
        FROM admissions a 
        JOIN patients p ON a.patient_id = p.patient_id
        JOIN beds b ON a.bed_id = b.bed_id 
        WHERE a.admission_id = $adm_id";
$res = $conn->query($sql);
$data = $res->fetch_assoc();
$patient_id = $data['patient_id'];
$admission_date = $data['admission_date'];


$admission_fee_paid = (float)($data['admission_fee'] ?? 0);

$days = (new DateTime($admission_date))->diff(new DateTime(date('Y-m-d')))->days ?: 1;
$total_bed_fee = $days * (($data['ward_name'] == 'ICU') ? 5000 : 1500);

$lab_details_query = "SELECT test_name, test_fees FROM lab_tests 
                      WHERE patient_id = $patient_id AND status = 'completed'";
$lab_details_res = $conn->query($lab_details_query);

$total_lab_fee = 0;
$lab_rows = [];
while ($row = $lab_details_res->fetch_assoc()) {
    $lab_rows[] = $row;
    $total_lab_fee += (float)$row['test_fees'];
}


$appt_query = "SELECT COUNT(*) as total_appts FROM appointments 
               WHERE patient_id = $patient_id 
               AND scheduled_time >= '$admission_date' 
               AND status = 'Confirmed'";
$appt_res = $conn->query($appt_query);
$appt_data = $appt_res->fetch_assoc();
$extra_appointments_count = (int)$appt_data['total_appts'];
$total_appt_fees = $extra_appointments_count * 500.00; 

$doctor_consultation = 500.00; 
$running_gross_total = $total_bed_fee + $doctor_consultation + $total_lab_fee + $admission_fee_paid + $total_appt_fees;

$pay_query = "SELECT SUM(amount) as total_paid FROM billing 
              WHERE patient_id = $patient_id 
              AND status = 'Paid'";
$pay_res = $conn->query($pay_query);
$pay_data = $pay_res->fetch_assoc();
$total_already_paid = (float)($pay_data['total_paid'] ?? 0);


$balance_due = $running_gross_total - $total_already_paid;
?>

<div class="container my-5">
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-dark text-white p-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">RUNNING BILL SUMMARY</h5>
                    <span class="badge bg-danger">Status: <?= $data['status'] ?></span>
                </div>
                <div class="card-body p-4">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr><th>Description</th><th class="text-end">Amount (TK)</th></tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Bed Charges (<?= $data['ward_name'] ?> x <?= $days ?> Days)</td>
                                <td class="text-end"><?= number_format($total_bed_fee, 2) ?></td>
                            </tr>
                            
                            <tr>
                                <td>Standard Admission Consultation Fee</td>
                                <td class="text-end"><?= number_format($doctor_consultation, 2) ?></td>
                            </tr>

                            <?php if($extra_appointments_count > 0): ?>
                            <tr>
                                <td>Extra Doctor Consultations (<?= $extra_appointments_count ?> visits)</td>
                                <td class="text-end"><?= number_format($total_appt_fees, 2) ?></td>
                            </tr>
                            <?php endif; ?>

                            <tr>
                                <td>Admission Registration Fee</td>
                                <td class="text-end"><?= number_format($admission_fee_paid, 2) ?></td>
                            </tr>

                            <?php if (!empty($lab_rows)): ?>
                                <tr class="table-light"><td colspan="2"><small class="fw-bold text-muted text-uppercase">Laboratory Breakdown</small></td></tr>
                                <?php foreach ($lab_rows as $lab): ?>
                                    <tr>
                                        <td class="ps-4 italic small text-muted"><?= htmlspecialchars($lab['test_name']) ?></td>
                                        <td class="text-end small text-muted"><?= number_format($lab['test_fees'], 2) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>

                            <tr class="fw-bold border-top h5">
                                <td>GROSS TOTAL BILLABLE</td>
                                <td class="text-end"><?= number_format($running_gross_total, 2) ?></td>
                            </tr>
                            
                            <tr class="text-success fw-bold">
                                <td>TOTAL PAID TO DATE (Incl. Appointments & Reg)</td>
                                <td class="text-end">- <?= number_format($total_already_paid, 2) ?></td>
                            </tr>

                            <tr class="table-warning fw-bold h4">
                                <td>NET BALANCE DUE</td>
                                <td class="text-end text-danger">BDT <?= number_format(max(0, $balance_due), 2) ?></td>
                            </tr>
                        </tbody>
                    </table>

                    <?php if($balance_due <= 0): ?>
                        <div class="alert alert-success d-flex align-items-center mt-4">
                            <div>
                                <strong>Account Cleared!</strong>
                                <form action="process_indoor_discharge.php" method="POST" class="mt-2">
                                    <input type="hidden" name="adm_id" value="<?= $adm_id ?>">
                                    <button type="submit" class="btn btn-dark w-100 fw-bold">PROCEED TO DISCHARGE</button>
                                </form>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info mt-4">
                            Note: Discharge button will be enabled once the Balance Due is 0.00.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white text-center">
                    <h5 class="mb-0">MAKE A PAYMENT</h5>
                </div>
                <div class="card-body p-4">
                    <form action="payment_gateway.php" method="POST">
                        <input type="hidden" name="adm_id" value="<?= $adm_id ?>">
                        <input type="hidden" name="is_partial" value="1">
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">Amount to Pay</label>
                            <div class="input-group">
                                <span class="input-group-text">TK</span>
                                <input type="number" name="total_amount" class="form-control form-control-lg fw-bold text-primary" 
                                       value="<?= ($balance_due > 0) ? $balance_due : 0 ?>" max="<?= ($balance_due > 0) ? $balance_due : 0 ?>" required>
                            </div>
                        </div>

                        <label class="form-label fw-bold small text-muted">Payment Method</label>
                        <div class="list-group mb-4">
                            <label class="list-group-item d-flex gap-2 py-3">
                                <input class="form-check-input flex-shrink-0" type="radio" name="pay_method" value="Cash" checked>
                                <span><i class="fas fa-money-bill-wave text-success me-2"></i> Cash Payment</span>
                            </label>
                            <label class="list-group-item d-flex gap-2 py-3">
                                <input class="form-check-input flex-shrink-0" type="radio" name="pay_method" value="Card">
                                <span><i class="fas fa-credit-card text-primary me-2"></i> Credit/Debit Card</span>
                            </label>
                            <label class="list-group-item d-flex gap-2 py-3">
                                <input class="form-check-input flex-shrink-0" type="radio" name="pay_method" value="bKash">
                                <span class="text-danger fw-bold">bKash</span>
                            </label>
                        </div>

                        <button type="submit" class="btn btn-success btn-lg w-100 shadow-sm fw-bold">PROCESS PAYMENT</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>