<?php
session_start();
include 'config/db.php';

// Ensure ID is passed
if (!isset($_GET['adm_id'])) {
    die("Admission ID missing.");
}

$adm_id = (int)$_GET['adm_id'];

// 1. Fetch detailed data
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

// 2. Stay Duration Logic
$date_admitted = new DateTime($data['admission_date']);
$date_today = new DateTime(date('Y-m-d'));
$diff = $date_admitted->diff($date_today);
$days_stayed = $diff->days > 0 ? $diff->days : 1; 

// 3. Pricing Logic
$bed_rate = ($data['ward_name'] == 'ICU') ? 5000 : 1500; 
$total_bed_cost = $days_stayed * $bed_rate;
$doctor_consultation = 500;

// 4. Lab Tests (Detailed Breakdown)
$lab_sql = "SELECT test_name as description, test_fees as amount 
            FROM lab_tests 
            WHERE patient_id = $p_id AND status = 'completed'";
$lab_results = $conn->query($lab_sql);
$total_lab_cost = 0;

// 5. NEW: Fetch ALL Payments made (Advance + Dashboard Partials)
$pay_query = "SELECT SUM(amount) as total_paid FROM billing 
              WHERE patient_id = $p_id AND status = 'paid'";
$pay_res = $conn->query($pay_query);
$pay_data = $pay_res->fetch_assoc();
$total_already_paid = (float)($pay_data['total_paid'] ?? 0);

// 6. Final Totals
$gross_total = $total_bed_cost + $doctor_consultation; // We will add lab cost inside the loop
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Indoor Invoice - <?= htmlspecialchars($data['p_name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .invoice-card { max-width: 900px; margin: 30px auto; padding: 40px; border: 1px solid #eee; background: white; position: relative; }
        .header-bg { background-color: #f8f9fa; padding: 20px; border-radius: 5px; }
        .paid-stamp { position: absolute; top: 15%; right: 5%; color: rgba(40, 167, 69, 0.2); font-size: 80px; font-weight: bold; border: 10px solid rgba(40, 167, 69, 0.2); padding: 10px; transform: rotate(-15deg); text-transform: uppercase; pointer-events: none; }
        @media print { .no-print { display: none; } .invoice-card { border: none; margin: 0; } }
    </style>
</head>
<body class="bg-light">

<div class="container no-print mt-3 text-center">
    <button onclick="window.print()" class="btn btn-danger px-4 shadow-sm">Print Official Invoice</button>
    <a href="generate_bill.php?adm_id=<?= $adm_id ?>" class="btn btn-secondary px-4 shadow-sm">Back to Billing</a>
</div>

<div class="invoice-card shadow-sm">
    <?php if($data['status'] == 'Discharged' || ($total_already_paid >= ($total_bed_cost + $doctor_consultation + $total_lab_cost))): ?>
        <div class="paid-stamp">FULL PAID</div>
    <?php endif; ?>

    <div class="row header-bg mb-4">
        <div class="col-md-6">
            <h2 class="fw-bold text-danger">HMS</h2>
            <p class="small text-muted mb-0">Hospital Management System</p>
        </div>
        <div class="col-md-6 text-md-end">
            <h4 class="fw-bold">OFFICIAL SETTLEMENT INVOICE</h4>
            <p class="mb-0">Invoice No: #IND-<?= $data['admission_id'] ?></p>
            <p>Date: <?= date('d M, Y') ?></p>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-6">
            <h6 class="text-muted border-bottom pb-1">PATIENT INFO</h6>
            <h5 class="fw-bold"><?= htmlspecialchars($data['p_name']) ?></h5>
            <p class="small mb-0">Phone: <?= htmlspecialchars($data['phone']) ?></p>
            <p class="small">Address: <?= htmlspecialchars($data['address']) ?></p>
        </div>
        <div class="col-6 text-md-end">
            <h6 class="text-muted border-bottom pb-1">ADMISSION INFO</h6>
            <p class="small mb-0"><strong>Doctor:</strong>  <?= htmlspecialchars($data['dr_name']) ?></p>
            <p class="small mb-0"><strong>Location:</strong> <?= $data['ward_name'] ?> (Bed: <?= $data['bed_number'] ?>)</p>
            <p class="small"><strong>Stay:</strong> <?= date('d M, Y', strtotime($data['admission_date'])) ?> to <?= date('d M, Y') ?> (<?= $days_stayed ?> Days)</p>
        </div>
    </div>

    <table class="table table-bordered">
        <thead class="bg-dark text-white">
            <tr>
                <th>Description of Charges</th>
                <th class="text-center">Rate</th>
                <th class="text-center">Qty/Days</th>
                <th class="text-end">Subtotal (Tk)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Bed/Cabin Charges (<?= $data['ward_name'] ?>)</td>
                <td class="text-center"><?= number_format($bed_rate) ?></td>
                <td class="text-center"><?= $days_stayed ?></td>
                <td class="text-end"><?= number_format($total_bed_cost, 2) ?></td>
            </tr>
            <tr>
                <td>Doctor Consultation Fees</td>
                <td class="text-center"><?= number_format($doctor_consultation) ?></td>
                <td class="text-center">1</td>
                <td class="text-end"><?= number_format($doctor_consultation, 2) ?></td>
            </tr>

            <?php if($lab_results && $lab_results->num_rows > 0): ?>
                <tr class="table-light"><td colspan="4" class="fw-bold text-primary italic small">Lab Tests & Diagnostics</td></tr>
                <?php while($lab = $lab_results->fetch_assoc()): 
                    $total_lab_cost += (float)$lab['amount']; ?>
                    <tr>
                        <td colspan="3" class="ps-4 italic text-muted small"><?= htmlspecialchars($lab['description']) ?></td>
                        <td class="text-end"><?= number_format($lab['amount'], 2) ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php endif; ?>
        </tbody>
        <tfoot class="h6">
            <?php $final_gross = $total_bed_cost + $doctor_consultation + $total_lab_cost; ?>
            <tr>
                <th colspan="3" class="text-end">Gross Total:</th>
                <th class="text-end"><?= number_format($final_gross, 2) ?></th>
            </tr>
            <tr class="text-success">
                <th colspan="3" class="text-end">Less: Total Paid (Advance + Partials):</th>
                <th class="text-end">- <?= number_format($total_already_paid, 2) ?></th>
            </tr>
            <tr class="table-dark h5">
                <th colspan="3" class="text-end text-uppercase">Balance Due:</th>
                <th class="text-end">
                    BDT <?= number_format(max(0, $final_gross - $total_already_paid), 2) ?>
                </th>
            </tr>
        </tfoot>
    </table>

    <div class="row mt-5">
        <div class="col-4 text-center">
            <hr class="w-75 mx-auto">
            <p class="small">Accounts Department</p>
        </div>
        <div class="col-4 text-center"></div>
        <div class="col-4 text-center">
            <hr class="w-75 mx-auto">
            <p class="small">Patient/Guardian Signature</p>
        </div>
    </div>
    
    <div class="mt-4 text-center">
        <p class="text-muted small">This is a computer-generated invoice from HMS. No signature is required for digital verification.</p>
    </div>
</div>

</body>
</html>