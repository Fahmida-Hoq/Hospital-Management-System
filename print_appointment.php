<?php
session_start();
include 'config/db.php';
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Error: Appointment ID is missing.");
}

$appt_id = (int)$_GET['id'];

$sql = "SELECT a.*, 
               p.name as p_name, p.phone as p_phone, 
               u.full_name as d_name, d.specialization,
               b.amount as fee, b.payment_method
        FROM appointments a
        INNER JOIN patients p ON a.patient_id = p.patient_id
        INNER JOIN doctors d ON a.doctor_id = d.doctor_id
        INNER JOIN users u ON d.user_id = u.user_id
        LEFT JOIN billing b ON (b.patient_id = a.patient_id AND b.bill_type = 'Outdoor')
        WHERE a.appointment_id = $appt_id 
        LIMIT 1";

$result = $conn->query($sql);

if (!$result) {
    die("Database Error: " . $conn->error);
}

$data = $result->fetch_assoc();
if (!$data) {
    die("Error: No appointment found in the database for ID #" . $appt_id);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Appointment Slip #<?php echo $appt_id; ?></title>
    <style>
        body { font-family: sans-serif; padding: 30px; color: #333; }
        .slip-container { max-width: 600px; margin: auto; border: 1px solid #ddd; padding: 20px; }
        .header { text-align: center; border-bottom: 2px solid #0056b3; margin-bottom: 20px; }
        .row { display: flex; justify-content: space-between; margin-bottom: 10px; }
        .label { color: #666; font-size: 14px; }
        .value { font-weight: bold; }
        .footer { margin-top: 30px; font-size: 12px; text-align: center; color: #888; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>

    <div class="no-print" style="text-align:center; margin-bottom: 20px;">
        <button onclick="window.print()" style="padding:10px 20px; cursor:pointer;">Print Now (Ctrl+P)</button>
    </div>

    <div class="slip-container">
        <div class="header">
            <h2>HMS MEDICAL CENTER</h2>
            <p>Consultation Appointment Slip</p>
        </div>

        <div class="row">
            <div>
                <span class="label">Appointment ID:</span><br>
                <span class="value">#<?php echo $data['appointment_id']; ?></span>
            </div>
            <div style="text-align: right;">
                <span class="label">Consultation Date:</span><br>
                <span class="value"><?php echo date('d-M-Y', strtotime($data['scheduled_time'])); ?></span>
            </div>
        </div>

        <hr>

        <div class="row">
            <div>
                <span class="label">Patient Name:</span><br>
                <span class="value"><?php echo htmlspecialchars($data['p_name']); ?></span>
            </div>
            <div style="text-align: right;">
                <span class="label">Consultation Time:</span><br>
                <span class="value" style="color: #0056b3;"><?php echo date('h:i A', strtotime($data['appointment_time'])); ?></span>
            </div>
        </div>

        <div class="row" style="margin-top: 15px;">
            <div>
                <span class="label">Assigned Doctor:</span><br>
                <span class="value"> <?php echo htmlspecialchars($data['d_name']); ?></span>
            </div>
            <div style="text-align: right;">
                <span class="label">Department:</span><br>
                <span class="value"><?php echo htmlspecialchars($data['specialization']); ?></span>
            </div>
        </div>

        <hr>

        <div class="row">
            <div>
                <span class="label">Payment Status:</span><br>
                <span class="value"><?php echo $data['fee'] ? 'PAID (' . $data['payment_method'] . ')' : 'Pending'; ?></span>
            </div>
            <div style="text-align: right;">
                <span class="label">Fee:</span><br>
                <span class="value"><?php echo number_format($data['fee'] ?? 500, 2); ?> TK</span>
            </div>
        </div>

        <div class="footer">
            <p>Please bring this slip with you on the day of your appointment.</p>
            <p>Generated on: <?php echo date('Y-m-d H:i:s'); ?></p>
        </div>
    </div>

</body>
</html>