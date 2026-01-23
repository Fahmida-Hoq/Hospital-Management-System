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

$lab_details_query = "SELECT test_name, test_fees, payment_status, payment_method FROM lab_tests 
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

$pay_query = "SELECT description, amount FROM billing 
              WHERE patient_id = $patient_id AND status = 'Paid'";
$pay_res = $conn->query($pay_query);

$paid_items_list = [];
$total_already_paid = 0;
while ($p_row = $pay_res->fetch_assoc()) {
    $paid_items_list[] = $p_row;
    $total_already_paid += (float)$p_row['amount'];
}

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
                    <table class="table">
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
                                        <td class="ps-4 italic small text-muted">
                                            <?= htmlspecialchars($lab['test_name']) ?>
                                            <?php if($lab['payment_status'] == 'paid'): ?>
                                                <span class="badge bg-success-subtle text-success ms-1" style="font-size: 10px;">Paid</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end small text-muted"><?= number_format($lab['test_fees'], 2) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>

                            <tr class="fw-bold border-top h5">
                                <td>GROSS TOTAL BILLABLE</td>
                                <td class="text-end"><?= number_format($running_gross_total, 2) ?></td>
                            </tr>

                            <tr class="table-success"><td colspan="2"><small class="fw-bold text-success text-uppercase">Deductions (Paid Items)</small></td></tr>
                            <?php if (!empty($paid_items_list)): ?>
                                <?php foreach ($paid_items_list as $paid): ?>
                                    <tr class="text-success small">
                                        <td class="ps-4 italic">Settled: <?= htmlspecialchars($paid['description']) ?></td>
                                        <td class="text-end">- <?= number_format($paid['amount'], 2) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>

                            <tr class="table-warning fw-bold h4">
                                <td>NET BALANCE DUE</td>
                                <td class="text-end text-danger">BDT <?= number_format(max(0, $balance_due), 2) ?></td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="mt-4 no-print">
                        <button onclick="window.print()" class="btn btn-outline-dark fw-bold">PRINT BILL SUMMARY</button>
                        
                        <?php if($balance_due <= 0): ?>
                            <form action="process_indoor_discharge.php" method="POST" class="d-inline">
                                <input type="hidden" name="adm_id" value="<?= $adm_id ?>">
                                <button type="submit" class="btn btn-dark fw-bold px-4 ms-2">PROCEED TO DISCHARGE</button>
                            </form>
                        <?php else: ?>
                            <div class="alert alert-info d-inline-block py-2 px-3 ms-2 mb-0 small">
                                <i class="fas fa-info-circle"></i> Clear balance to enable Discharge.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4 no-print">
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
                            <input type="number" name="total_amount" class="form-control form-control-lg fw-bold text-primary" 
                                   value="<?= ($balance_due > 0) ? $balance_due : 0 ?>" max="<?= ($balance_due > 0) ? $balance_due : 0 ?>" required>
                        </div>
                        <label class="form-label fw-bold small text-muted">Method</label>
                        <div class="list-group mb-4">
                            <label class="list-group-item d-flex gap-2"><input class="form-check-input" type="radio" name="pay_method" value="Cash" checked> Cash</label>
                            <label class="list-group-item d-flex gap-2"><input class="form-check-input" type="radio" name="pay_method" value="Card"> Card</label>
                            <label class="list-group-item d-flex gap-2"><input class="form-check-input" type="radio" name="pay_method" value="bKash"> bKash</label>
                        </div>
                        <button type="submit" class="btn btn-success btn-lg w-100 fw-bold">PROCESS PAYMENT</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>