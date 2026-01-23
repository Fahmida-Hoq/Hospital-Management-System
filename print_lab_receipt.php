<?php
session_start();
include 'config/db.php';
$test_id = (int)$_GET['test_id'];
$method = $_GET['method'] ?? 'Paid';

$sql = "SELECT l.*, p.name FROM lab_tests l JOIN patients p ON l.patient_id = p.patient_id WHERE l.test_id = $test_id";
$res = $conn->query($sql)->fetch_assoc();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Print Receipt</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; padding: 20px; text-align: center; }
        .receipt-box { border: 2px solid black; padding: 25px; width: 450px; margin: auto; text-align: left; position: relative; background: #fff; }
        .paid-stamp { position: absolute; top: 40%; left: 20%; font-size: 70px; color: rgba(0,128,0,0.15); border: 8px solid; transform: rotate(-15deg); padding: 10px; font-weight: bold; pointer-events: none; }
        
        .btn-print { background: #198754; color: white; padding: 15px 40px; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; font-size: 18px; margin-bottom: 25px; }
        .btn-back { display: block; margin-top: 10px; color: #6c757d; text-decoration: none; font-weight: bold; }
        
        @media print { .no-print { display: none; } body { padding: 0; } .receipt-box { box-shadow: none; border: 2px solid #000; width: 100%; } }
    </style>
</head>
<body>

    <div class="no-print">
        <button class="btn-print" onclick="window.print()">PRINT NOW</button>
        <a href="lab_manage_tests.php" class="btn-back">Return to Lab Queue</a>
        <hr style="width: 50%; margin: 30px auto;">
    </div>

    <div class="receipt-box">
        <div class="paid-stamp">PAID</div>
        <div style="text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 15px;">
            <h2 style="margin:0;">HMS</h2>
            <small>Official Payment Receipt</small>
        </div>
        
        <p><strong>Receipt No:</strong> #LAB-<?= $test_id ?></p>
        <p><strong>Patient:</strong> <?= htmlspecialchars($res['name']) ?></p>
        <p><strong>Test Name:</strong> <?= htmlspecialchars($res['test_name']) ?></p>
        <p><strong>Payment Method:</strong> <?= htmlspecialchars($method) ?></p>
        <p><strong>Date:</strong> <?= date('d M, Y | h:i A', strtotime($res['completed_at'])) ?></p>
        
        <div style="margin-top: 20px; padding-top: 10px; border-top: 1px dashed #000; text-align: right;">
            <h3 style="margin:0;">Total Paid: TK <?= number_format($res['test_fees'], 2) ?></h3>
        </div>
        
        <p style="text-align:center; font-size:11px; margin-top: 40px; color: #555;">Thank you. Please keep this receipt for your records.</p>
    </div>

</body>
</html>