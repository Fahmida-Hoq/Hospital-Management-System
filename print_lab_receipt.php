<?php
session_start();
include 'config/db.php';
if(!isset($_GET['test_id'])) exit("Invalid ID");

$test_id = (int)$_GET['test_id'];
$sql = "SELECT l.*, p.name FROM lab_tests l JOIN patients p ON l.patient_id = p.patient_id WHERE l.test_id = $test_id";
$res = $conn->query($sql);
$data = $res->fetch_assoc();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Lab Receipt #<?= $test_id ?></title>
    <style>
        body { font-family: Arial, sans-serif; padding: 40px; }
        .receipt { border: 2px solid #000; padding: 30px; max-width: 600px; margin: auto; position: relative; }
        .header { text-align: center; border-bottom: 2px solid #000; margin-bottom: 20px; }
        .item { display: flex; justify-content: space-between; margin: 10px 0; }
        .paid-stamp { 
            position: absolute; top: 50%; left: 50%; transform: translate(-50%,-50%) rotate(-15deg);
            border: 5px solid green; color: green; font-size: 50px; padding: 10px; opacity: 0.2; font-weight: bold;
        }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    <div class="no-print" style="text-align: center; margin-bottom: 20px;">
        <p>Press <strong>Ctrl + P</strong> to print this receipt.</p>
        <a href="patient_records.php">Back to Records</a>
    </div>
    
    <div class="receipt">
        <div class="paid-stamp">PAID</div>
        <div class="header">
            <h2>HOSPITAL MANAGEMENT SYSTEM</h2>
            <p>Laboratory Payment Receipt</p>
        </div>
        <div class="item"><span>Patient Name:</span> <strong><?= $data['name'] ?></strong></div>
        <div class="item"><span>Date:</span> <strong><?= date('d M, Y', strtotime($data['created_at'])) ?></strong></div>
        <hr>
        <div class="item"><span>Test Name:</span> <span><?= $data['test_name'] ?></span></div>
        <div class="item"><span>Status:</span> <span>Completed</span></div>
        <div class="item" style="font-size: 20px; font-weight: bold; margin-top: 20px;">
            <span>Amount Paid:</span> <span>TK <?= number_format($data['test_fees'], 2) ?></span>
        </div>
        <p style="margin-top: 40px; font-size: 12px; text-align: center;">This is a system generated receipt.</p>
    </div>
</body>
</html>