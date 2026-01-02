<?php
session_start();
include 'config/db.php';
include 'includes/header.php';

$adm_id = (int)$_GET['adm_id'];

// 1. Get Admission and Appointment Data
$sql = "SELECT a.*, p.name, b.ward_name, b.bed_number 
        FROM admissions a 
        JOIN patients p ON a.patient_id = p.patient_id
        JOIN beds b ON a.bed_id = b.bed_id 
        WHERE a.admission_id = $adm_id";
$res = $conn->query($sql);
$data = $res->fetch_assoc();

// 2. Calculations
$days = (new DateTime($data['admission_date']))->diff(new DateTime(date('Y-m-d')))->days ?: 1;
$total_bed_fee = $days * (($data['ward_name'] == 'ICU') ? 5000 : 1500);

// 3. FIXED: Lab Fees from lab_tests using patient_id
$lab_query = "SELECT SUM(test_fees) as total FROM lab_tests 
              WHERE patient_id = " . $data['patient_id'] . " AND status = 'completed'";
$lab_res = $conn->query($lab_query);
$lab_data = $lab_res->fetch_assoc();
$total_lab_fee = (float)($lab_data['total'] ?? 0);

$grand_total = $total_bed_fee + 1000 + $total_lab_fee + (float)$data['admission_fee'];
?>

<div class="container my-5">
    <div class="card shadow-lg border-0 mx-auto" style="max-width: 750px;">
        <div class="card-header bg-dark text-white p-4">
            <h4 class="mb-0 text-center">INDOOR PATIENT FINAL BILL</h4>
        </div>
        <div class="card-body p-5">
            <div class="row mb-4">
                <div class="col-6"><h5>Patient: <?= $data['name'] ?></h5></div>
                <div class="col-6 text-end"><h5>Date: <?= date('d M Y') ?></h5></div>
            </div>

            <table class="table table-bordered mb-4">
                <thead class="bg-light text-uppercase small">
                    <tr><th>Description</th><th class="text-end">Amount</th></tr>
                </thead>
                <tbody>
                    <tr><td>Bed Charge (<?= $days ?> Days)</td><td class="text-end"><?= number_format($total_bed_fee, 2) ?></td></tr>
                    <tr><td>Doctor Consultation</td><td class="text-end">1,000.00</td></tr>
                    <tr><td>Lab & Diagnostics</td><td class="text-end fw-bold text-primary"><?= number_format($total_lab_fee, 2) ?></td></tr>
                    <tr><td>Admission Fee</td><td class="text-end"><?= number_format($data['admission_fee'], 2) ?></td></tr>
                    <tr class="table-dark h5"><td>GRAND TOTAL</td><td class="text-end">BDT <?= number_format($grand_total, 2) ?></td></tr>
                </tbody>
            </table>

            <div class="bg-light p-4 rounded border">
                <h6 class="fw-bold text-center mb-3">SELECT PAYMENT METHOD</h6>
                <form action="payment_gateway.php" method="POST" class="mt-4 p-4 bg-light border rounded shadow-sm">
    <input type="hidden" name="adm_id" value="<?php echo $adm_id; ?>">
    <input type="hidden" name="total_amount" value="<?php echo $grand_total; ?>">
    
    <h5 class="fw-bold mb-3"><i class="fas fa-credit-card me-2"></i> Select Payment Method</h5>
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="form-check border p-3 rounded h-100 shadow-sm bg-white">
                <input class="form-check-input" type="radio" name="pay_method" id="cash" value="Cash" checked>
                <label class="form-check-label w-100 fw-bold" for="cash">
                    <i class="fas fa-money-bill-wave text-success me-1"></i> Cash
                </label>
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-check border p-3 rounded h-100 shadow-sm bg-white">
                <input class="form-check-input" type="radio" name="pay_method" id="card" value="Card">
                <label class="form-check-label w-100 fw-bold" for="card">
                    <i class="fas fa-university text-primary me-1"></i> Card
                </label>
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-check border p-3 rounded h-100 shadow-sm bg-white">
                <input class="form-check-input" type="radio" name="pay_method" id="bkash" value="bKash">
                <label class="form-check-label w-100 fw-bold text-danger" for="bkash">
                    bKash
                </label>
            </div>
        </div>
    </div>

    <button type="submit" name="confirm_payment" class="btn btn-success btn-lg">
        PROCEED TO PAYMENT
    </button>
</form>
            </div>
        </div>
    </div>
</div>