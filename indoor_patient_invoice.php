<?php
session_start();
include 'config/db.php';

// Ensure ID is passed
if (!isset($_GET['adm_id'])) {
    die("Admission ID missing.");
}

$adm_id = (int)$_GET['adm_id'];

// 1. Fetch detailed data including Ward/Bed names and Doctor names
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

// 2. Stay Duration Logic
$date_admitted = new DateTime($data['admission_date']);
$date_today = new DateTime(date('Y-m-d'));
$diff = $date_admitted->diff($date_today);
$days_stayed = $diff->days > 0 ? $diff->days : 1; 

// 3. Pricing Logic
$bed_rate = ($data['ward_name'] == 'ICU') ? 5000 : 1500; 
$total_bed_cost = $days_stayed * $bed_rate;
$doctor_consultation = 1000;
$admission_fee = (float)$data['admission_fee'];

// 4. FIXED: Fetch Lab Tests directly from lab_tests table
$p_id = $data['patient_id'];
$lab_sql = "SELECT test_name as description, test_fees as amount 
            FROM lab_tests 
            WHERE patient_id = $p_id AND status = 'completed'";
$lab_results = $conn->query($lab_sql);

$total_lab_cost = 0;
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
        .paid-stamp { position: absolute; top: 20%; right: 10%; color: rgba(40, 167, 69, 0.2); font-size: 80px; font-weight: bold; border: 10px solid rgba(40, 167, 69, 0.2); padding: 10px; transform: rotate(-15deg); text-transform: uppercase; pointer-events: none; }
        @media print { .no-print { display: none; } .invoice-card { border: none; margin: 0; } }
    </style>
</head>
<body class="bg-light">

<div class="container no-print mt-3 text-center">
    <button onclick="window.print()" class="btn btn-danger px-4 shadow-sm">Print Official Invoice</button>
    <a href="doctor_indoor_patients.php" class="btn btn-secondary px-4 shadow-sm">Back to Registry</a>
</div>

<div class="invoice-card shadow-sm">
    
    
    <div class="row header-bg mb-4">
        <div class="col-md-6">
            <h2 class="fw-bold text-danger">HMS</h2>
            <p class="small text-muted mb-0">Hospital Management System</p>
        </div>
        <div class="col-md-6 text-md-end">
            <h4 class="fw-bold">PATIENT INVOICE</h4>
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
                <td><?= $data['ward_name'] ?> Charges</td>
                <td class="text-center"><?= number_format($bed_rate) ?></td>
                <td class="text-center"><?= $days_stayed ?></td>
                <td class="text-end"><?= number_format($total_bed_cost) ?></td>
            </tr>
            <tr>
                <td>Doctor Consultation Fees</td>
                <td class="text-center"><?= number_format($doctor_consultation) ?></td>
                <td class="text-center">1</td>
                <td class="text-end"><?= number_format($doctor_consultation) ?></td>
            </tr>
            <tr>
                <td>Admission Registration Fee</td>
                <td class="text-center">-</td>
                <td class="text-center">1</td>
                <td class="text-end"><?= number_format($admission_fee) ?></td>
            </tr>

            <?php if($lab_results && $lab_results->num_rows > 0): ?>
                <tr class="table-light"><td colspan="4" class="fw-bold text-primary">Lab Tests & Diagnostics</td></tr>
                <?php while($lab = $lab_results->fetch_assoc()): 
                    $total_lab_cost += (float)$lab['amount']; ?>
                    <tr>
                        <td colspan="3" class="ps-4 italic text-muted"><?= htmlspecialchars($lab['description']) ?></td>
                        <td class="text-end"><?= number_format($lab['amount'], 2) ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php endif; ?>
        </tbody>
        <tfoot class="h5 table-dark">
            <tr>
                <th colspan="3" class="text-end">GRAND TOTAL:</th>
                <th class="text-end">
                    BDT <?= number_format($total_bed_cost + $doctor_consultation + $admission_fee + $total_lab_cost, 2) ?>
                </th>
            </tr>
        </tfoot>
    </table>

    <div class="row mt-5">
        <div class="col-6 text-center">
            <hr class="w-50 mx-auto">
            <p class="small">Accounts Officer</p>
        </div>
        <div class="col-6 text-center">
            <hr class="w-50 mx-auto">
            <p class="small">Patient/Guardian</p>
        </div>
    </div>
</div>

</body>
</html>