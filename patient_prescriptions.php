<?php
session_start();
include 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$u_id = (int)$_SESSION['user_id'];


$sql = "SELECT 
            p.*, 
            u_dr.full_name AS dr_name
        FROM prescriptions p
        INNER JOIN patients pat ON p.patient_id = pat.patient_id
        LEFT JOIN doctors d ON p.doctor_id = d.doctor_id
        LEFT JOIN users u_dr ON d.user_id = u_dr.user_id
        WHERE pat.user_id = $u_id
        ORDER BY p.date_prescribed DESC, p.prescription_id DESC";

$pres_result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Patient Prescription History</title>
    <style>
        
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f0f2f5; padding: 20px; color: #333; }
        .rx-container { max-width: 850px; margin: auto; background: #fff; border: 1px solid #000; padding: 40px; box-shadow: 0 4px 15px rgba(0,0,0,0.2); }
        .hospital-header { text-align: center; border-bottom: 3px double #000; padding-bottom: 15px; margin-bottom: 30px; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { background: #eee; border: 1px solid #0000003b; padding: 12px; text-align: left; font-size: 14px; }
        td { border: 1px solid #000; padding: 15px; vertical-align: top; }
        
        .medicine-name { font-size: 18px; font-weight: bold; color: #d9534f; margin-bottom: 5px; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; text-transform: uppercase; background: #e9ecef; border: 1px solid #ccc; }
        
        
        .btn-row { display: flex; justify-content: space-between; margin-bottom: 20px; }
        .back-btn { display: inline-block; text-decoration: none; color: #000; font-weight: bold; border: 1px solid #000; padding: 8px 15px; background: #fff; }
        .back-btn:hover { background: #000; color: #fff; cursor: pointer; }
        .print-btn { background: #28a745; color: #fff; border-color: #28a745; }
        .print-btn:hover { background: #218838; border-color: #1e7e34; }

       
        @media print {
            body { background: white; padding: 0; margin: 0; }
            .no-print { display: none !important; } 
            .rx-container { 
                box-shadow: none; 
                border: none; 
                width: 100%; 
                max-width: 100%; 
                margin: 0; 
                padding: 10px; 
            }
            .medicine-name { color: #000; }
            th { background: #fff !important; color: #000; }
        }
    </style>
</head>
<body>

<div class="rx-container">
    <div class="btn-row no-print">
        <a href="patient_dashboard.php" class="back-btn">← DASHBOARD</a>
        <button class="back-btn print-btn" onclick="window.print()">🖨️ PRINT REPORT</button>
    </div>

    <div class="hospital-header">
        <h1 style="margin:0; letter-spacing: 2px;">HMS</h1>
        <p style="margin:5px 0; color: #666;">Medication History </p>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 20%;">Date</th>
                <th style="width: 50%;">Prescription Details</th>
                <th style="width: 30%;">Doctor</th>
                
            </tr>
        </thead>
        <tbody>
            <?php if ($pres_result && $pres_result->num_rows > 0): ?>
                <?php while($row = $pres_result->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <strong><?= date('d M, Y', strtotime($row['date_prescribed'])) ?></strong><br>
                            <?php if(!empty($row['admission_id'])): ?>
                                <span class="badge">In-Patient</span>
                            <?php else: ?>
                                <span class="badge">Out-Patient</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="medicine-name"><?= htmlspecialchars($row['prescribed_medicines']) ?></div>
                            <div style="font-size: 14px; margin-top: 10px;">
                                <strong>Instructions:</strong> <?= htmlspecialchars($row['doctor_notes'] ?: 'Follow standard dosage') ?>
                            </div>
                        </td>
                        <td>
                            <strong> <?= htmlspecialchars($row['dr_name'] ?: 'Physician') ?></strong>
                        </td>
                        
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="3" style="text-align: center; padding: 50px;">
                        No medication records found.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div style="margin-top: 80px; text-align: right;">
        <div style="display: inline-block; border-top: 1px solid #000; width: 200px; text-align: center; padding-top: 5px; font-size: 14px;">
            Authorized Signature
        </div>
    </div>
</div>

</body>
</html>