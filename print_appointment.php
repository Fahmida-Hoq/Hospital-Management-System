<?php
session_start();
include 'config/db.php';

if (!isset($_GET['id'])) {
    die("Appointment ID missing.");
}

$appointment_id = (int)$_GET['id'];

// Updated SQL: Joining tables to ensure all doctor and patient info is available
$sql = "SELECT a.*, p.name as patient_name, u.full_name as doctor_name, d.specialization 
        FROM appointments a
        JOIN patients p ON a.patient_id = p.patient_id
        JOIN doctors d ON a.doctor_id = d.doctor_id
        JOIN users u ON d.user_id = u.user_id
        WHERE a.appointment_id = $appointment_id";

$res = $conn->query($sql);
if (!$res || $res->num_rows == 0) {
    die("Appointment record not found.");
}
$app = $res->fetch_assoc();

/** * FIXED: Handle the 'payment_status' error.
 * If the column exists in the DB, we use it. 
 * Otherwise, we default to 'Paid' since payments are made during booking.
 */
$payment_status = isset($app['payment_status']) ? $app['payment_status'] : 'Paid';

// If it's a confirmed appointment, it should be marked as Paid
if ($app['status'] == 'Confirmed' || strtolower($payment_status) == 'paid') {
    $display_status = "PAID";
    $text_class = "text-success";
} else {
    $display_status = "PENDING";
    $text_class = "text-danger";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Appointment Slip #<?= $appointment_id ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .slip-container { max-width: 700px; margin: 40px auto; padding: 30px; border: 1px solid #dee2e6; border-radius: 8px; background-color: #fff; }
        .dashed-divider { border-top: 2px dashed #eee; margin: 25px 0; }
        .hospital-logo { font-size: 28px; font-weight: 800; color: #0d6efd; letter-spacing: 1px; }
        @media print { .no-print { display: none; } .slip-container { border: none; margin: 0; padding: 10px; } }
    </style>
</head>
<body class="bg-light">

<div class="container text-center no-print mt-4">
    <button onclick="window.print()" class="btn btn-primary px-4 shadow-sm">Print Slip (Ctrl+P)</button>
    <a href="patient_records.php" class="btn btn-outline-secondary px-4 shadow-sm">Back to Records</a>
</div>

<div class="slip-container shadow-sm">
    <div class="text-center mb-4">
        <h2 class="hospital-logo mb-0">HMS</h2>
        <p class="text-muted text-uppercase small fw-bold">Consultation Appointment Slip</p>
    </div>

    <div class="row g-4">
        <div class="col-6">
            <label class="text-muted small text-uppercase">Appointment ID</label>
            <p class="fw-bold mb-0">#<?= $app['appointment_id'] ?></p>
        </div>
        <div class="col-6 text-end">
            <label class="text-muted small text-uppercase">Appointment Date</label>
            <p class="fw-bold mb-0"><?= date('d-M-Y', strtotime($app['scheduled_time'])) ?></p>
        </div>

        <div class="col-6">
            <label class="text-muted small text-uppercase">Patient Name</label>
            <p class="fw-bold mb-0"><?= htmlspecialchars($app['patient_name']) ?></p>
        </div>
        <div class="col-6 text-end">
            <label class="text-muted small text-uppercase">Reporting Time</label>
            <p class="fw-bold mb-0 text-primary"><?= date('h:i A', strtotime($app['scheduled_time'])) ?></p>
        </div>

        <div class="col-6">
            <label class="text-muted small text-uppercase">Assigned Doctor</label>
            <p class="fw-bold mb-0"> <?= htmlspecialchars($app['doctor_name']) ?></p>
        </div>
        <div class="col-6 text-end">
            <label class="text-muted small text-uppercase">Department</label>
            <p class="fw-bold mb-0"><?= htmlspecialchars($app['specialization']) ?></p>
        </div>
    </div>

    <div class="dashed-divider"></div>

    <div class="row align-items-center">
        <div class="col-6">
            <label class="text-muted small text-uppercase">Payment Status</label>
            <p class="fw-bold mb-0 <?= $text_class ?> fs-5">
                <i class="fas fa-check-circle"></i> <?= $display_status ?>
            </p>
        </div>
        <div class="col-6 text-end">
            <label class="text-muted small text-uppercase">Consultation Fee</label>
            <p class="fw-bold mb-0 fs-4">500.00 TK</p>
        </div>
    </div>

    <div class="mt-5 text-center bg-light p-3 rounded">
        <p class="small text-muted mb-0">Please arrive 15 minutes before your scheduled time and bring the slip.</p>
       
    </div>
</div>

</body>
</html>