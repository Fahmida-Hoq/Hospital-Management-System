<?php
session_start();
include 'config/db.php';

if (!isset($_GET['adm_id'])) {
    die("Admission ID missing.");
}

$adm_id = (int)$_GET['adm_id'];


$sql = "SELECT a.*, p.patient_id, p.name as p_name, p.phone, p.address, 
               u.full_name as dr_name, b.ward_name, b.bed_number 
        FROM admissions a 
        JOIN patients p ON a.patient_id = p.patient_id
        JOIN beds b ON a.bed_id = b.bed_id 
        JOIN doctors d ON a.doctor_id = d.doctor_id
        JOIN users u ON d.user_id = u.user_id
        WHERE a.admission_id = $adm_id";

$res = $conn->query($sql);
if (!$res || $res->num_rows == 0) {
    die("Admission record not found.");
}
$data = $res->fetch_assoc();
$p_id = $data['patient_id'];
$admission_fee = (float)($data['admission_fee'] ?? 0);


$date_admitted = new DateTime($data['admission_date']);
$date_today = new DateTime(date('Y-m-d'));
$diff = $date_admitted->diff($date_today);
$days_stayed = $diff->days > 0 ? $diff->days : 1; 

$bed_rate = (strpos($data['ward_name'], 'ICU') !== false) ? 5000 : 1500; 
$total_bed_cost = $days_stayed * $bed_rate;
$standard_consultation = 500;

$adm_date_str = $data['admission_date'];
$appt_query = "SELECT COUNT(*) as extra_visits FROM appointments 
               WHERE patient_id = $p_id AND scheduled_time >= '$adm_date_str' AND status = 'Confirmed'";
$extra_visits = (int)$conn->query($appt_query)->fetch_assoc()['extra_visits'];
$extra_consultation_fee = $extra_visits * 500;


$lab_sql = "SELECT test_name, test_fees, payment_status FROM lab_tests WHERE patient_id = $p_id AND status = 'completed'";
$lab_results = $conn->query($lab_sql);
$total_lab_cost = 0;
$lab_paid_at_counter = 0;

$lab_items = [];
while($row = $lab_results->fetch_assoc()){
    $fee = (float)$row['test_fees'];
    $total_lab_cost += $fee;
    if($row['payment_status'] == 'paid' || $row['payment_status'] == 'Paid'){
        $lab_paid_at_counter += $fee;
    }
    $lab_items[] = $row;
}


$pay_query = "SELECT SUM(amount) as total_paid FROM billing WHERE patient_id = $p_id AND status = 'paid'";
$total_already_paid = (float)($conn->query($pay_query)->fetch_assoc()['total_paid'] ?? 0);

$total_settled_amount = $total_already_paid + $lab_paid_at_counter;

$gross_total = $total_bed_cost + $standard_consultation + $extra_consultation_fee + $admission_fee + $total_lab_cost;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice - <?= htmlspecialchars($data['p_name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .invoice-card { max-width: 900px; margin: 30px auto; padding: 40px; border: 1px solid #eee; background: white; position: relative; }
        .header-bg { background-color: #f8f9fa; padding: 20px; border-radius: 5px; }
        .paid-stamp { position: absolute; top: 15%; right: 5%; color: rgba(40, 167, 69, 0.2); font-size: 80px; font-weight: bold; border: 10px solid rgba(40, 167, 69, 0.2); padding: 10px; transform: rotate(-15deg); text-transform: uppercase; pointer-events: none; z-index: 10; }
        @media print { .no-print { display: none; } .invoice-card { border: none; margin: 0; width: 100%; max-width: 100%; } }
    </style>
</head>
<body class="bg-light">

<div class="container no-print mt-3 text-center">
    <button onclick="window.print()" class="btn btn-danger px-4 shadow-sm">Print Official Invoice</button>
    <a href="generate_bill.php?adm_id=<?= $adm_id ?>" class="btn btn-secondary px-4 shadow-sm">Back to Billing</a>
</div>

<div class="invoice-card shadow-sm">
    <?php if($total_settled_amount >= $gross_total): ?>
        <div class="paid-stamp">FULL PAID</div>
    <?php endif; ?>

    <div class="row header-bg mb-4">
        <div class="col-6">
            <h2 class="fw-bold text-danger mb-0">HMS</h2>
            <p class="small text-muted">Excellence in Healthcare</p>
        </div>
        <div class="col-6 text-end">
            <h4 class="fw-bold">SETTLEMENT INVOICE</h4>
            <p class="mb-0">Invoice: #IND-<?= $adm_id ?></p>
            <p>Date: <?= date('d M, Y') ?></p>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-6">
            <h6 class="text-muted border-bottom pb-1">PATIENT</h6>
            <h5 class="fw-bold mb-1"><?= htmlspecialchars($data['p_name']) ?></h5>
            <p class="small mb-0">Phone: <?= htmlspecialchars($data['phone']) ?></p>
            <p class="small">ID: P-<?= $p_id ?></p>
        </div>
        <div class="col-6 text-md-end">
            <h6 class="text-muted border-bottom pb-1">STAY DETAILS</h6>
            <p class="small mb-0"><strong>Ward:</strong> <?= $data['ward_name'] ?> (Bed: <?= $data['bed_number'] ?>)</p>
            <p class="small"><strong>Period:</strong> <?= date('d M', strtotime($data['admission_date'])) ?> - <?= date('d M') ?> (<?= $days_stayed ?> Days)</p>
        </div>
    </div>

    <table class="table table-bordered align-middle">
        <thead class="table-dark">
            <tr>
                <th>Description</th>
                <th class="text-center">Rate</th>
                <th class="text-center">Qty</th>
                <th class="text-end">Total (Tk)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Admission Registration Fee</td>
                <td class="text-center"><?= number_format($admission_fee) ?></td>
                <td class="text-center">1</td>
                <td class="text-end"><?= number_format($admission_fee, 2) ?></td>
            </tr>
            <tr>
                <td>Bed/Cabin Charges (<?= $data['ward_name'] ?>)</td>
                <td class="text-center"><?= number_format($bed_rate) ?></td>
                <td class="text-center"><?= $days_stayed ?></td>
                <td class="text-end"><?= number_format($total_bed_cost, 2) ?></td>
            </tr>
            <tr>
                <td>Standard Consultation Fee</td>
                <td class="text-center">500</td>
                <td class="text-center">1</td>
                <td class="text-end">500.00</td>
            </tr>
            <?php if($extra_consultation_fee > 0): ?>
            <tr>
                <td>Extra Doctor Consultations</td>
                <td class="text-center">500</td>
                <td class="text-center"><?= $extra_visits ?></td>
                <td class="text-end"><?= number_format($extra_consultation_fee, 2) ?></td>
            </tr>
            <?php endif; ?>

            <?php if(!empty($lab_items)): ?>
                <tr class="table-light"><td colspan="4" class="fw-bold small">Laboratory & Diagnostics</td></tr>
                <?php foreach($lab_items as $lb): ?>
                    <tr>
                        <td colspan="3" class="ps-4 small">
                            <?= htmlspecialchars($lb['test_name']) ?>
                            <?php if($lb['payment_status'] == 'paid'): ?>
                                <span class="badge bg-success-subtle text-success ms-1" style="font-size: 9px;">Paid at Lab</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end small"><?= number_format($lb['test_fees'], 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
        <tfoot class="h6">
            <tr>
                <th colspan="3" class="text-end">Gross Total:</th>
                <th class="text-end">TK <?= number_format($gross_total, 2) ?></th>
            </tr>
            <tr class="text-success">
                <th colspan="3" class="text-end">Total Settled (Incl. Lab & Advance):</th>
                <th class="text-end">- TK <?= number_format($total_settled_amount, 2) ?></th>
            </tr>
            <tr class="table-dark h5">
                <th colspan="3" class="text-end">NET BALANCE DUE:</th>
                <th class="text-end">BDT <?= number_format(max(0, $gross_total - $total_settled_amount), 2) ?></th>
            </tr>
        </tfoot>
    </table>

    <div class="row mt-5 pt-3">
        <div class="col-4 text-center">
            <hr class="w-75 mx-auto">
            <p class="small">Billing Officer</p>
        </div>
        <div class="col-4"></div>
        <div class="col-4 text-center">
            <hr class="w-75 mx-auto">
            <p class="small">Patient/Guardian</p>
        </div>
    </div>
</div>
</body>
</html>