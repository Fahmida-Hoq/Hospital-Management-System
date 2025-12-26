<?php
session_start();
include 'config/db.php';

$p_id = $_GET['id'];

// Fetch Patient and Admission Details
$p_query = $conn->query("SELECT * FROM patients WHERE patient_id = $p_id");
$p = $p_query->fetch_assoc();

// Fetch All Paid Charges
$bill_query = $conn->query("SELECT * FROM billing WHERE patient_id = $p_id");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Invoice - <?= htmlspecialchars($p['name']) ?></title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        @media print {
            .no-print { display: none; }
            .container { width: 100%; border: none; }
        }
        .invoice-box { border: 1px solid #eee; padding: 30px; margin-top: 20px; }
        .hospital-logo { font-size: 28px; font-weight: bold; color: #dc3545; letter-spacing: 2px; }
    </style>
</head>
<body>

<div class="container mb-5">
    <div class="no-print mt-4 text-right">
        <button onclick="window.print()" class="btn btn-primary">Print Invoice</button>
        <a href="receptionist_admitted_patients.php" class="btn btn-secondary">Back</a>
    </div>

    <div class="invoice-box bg-white">
        <div class="row">
            <div class="col-6">
                <div class="hospital-logo">HMS</div>
                <p></p>
            </div>
            <div class="col-6 text-right">
                <h4>INVOICE</h4>
                <p>Date: <?= date('d M Y') ?><br>Invoice #: INV-<?= $p['patient_id'] . time() ?></p>
            </div>
        </div>

        <hr>

        <div class="row mb-4">
            <div class="col-6">
                <h6><strong>PATIENT INFO:</strong></h6>
                <div><?= htmlspecialchars($p['name']) ?></div>
                <div>Age/Gender: <?= $p['age'] ?> / <?= $p['gender'] ?></div>
                <div>Phone: <?= $p['phone'] ?></div>
            </div>
            <div class="col-6 text-right">
                <h6><strong>ADMISSION DETAILS:</strong></h6>
                <div>Ward: <?= $p['ward'] ?> | Bed: <?= $p['bed'] ?></div>
                <div>Admitted: <?= date('d M Y', strtotime($p['admission_date'])) ?></div>
                <div>Guardian: <?= htmlspecialchars($p['guardian_name']) ?></div>
            </div>
        </div>

        <table class="table table-bordered">
            <thead class="thead-light">
                <tr>
                    <th>Description</th>
                    <th class="text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $total = 0;
                while($row = $bill_query->fetch_assoc()): 
                    $total += $row['amount'];
                ?>
                <tr>
                    <td><?= htmlspecialchars($row['description']) ?></td>
                    <td class="text-right">TK <?= number_format($row['amount'], 2) ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th class="text-right">TOTAL PAID:</th>
                    <th class="text-right text-success">TK <?= number_format($total, 2) ?></th>
                </tr>
            </tfoot>
        </table>

        <div class="mt-5 row">
            <div class="col-6">
                <p class="small text-muted">Thank you for choosing HMS. Wish you a speedy recovery!</p>
            </div>
            <div class="col-6 text-right">
                <br><br>
                <div style="border-top: 1px solid #000; width: 200px; display: inline-block;">Authorized Signature</div>
            </div>
        </div>
    </div>
</div>

</body>
</html>