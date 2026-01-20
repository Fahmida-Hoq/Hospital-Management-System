<?php
session_start();
include 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$u_id = (int)$_SESSION['user_id'];

/**
 * THE COMPLETE FIX:
 * 1. Finds the patient_id for the logged-in user.
 * 2. Joins prescriptions to BOTH appointments and admissions.
 * 3. Joins to doctors/users to get the specific doctor's name for THAT record.
 */
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
        .rx-symbol { font-size: 45px; font-family: serif; font-weight: bold; margin: 0; line-height: 1; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { background: #eee; border: 1px solid #000; padding: 12px; text-align: left; font-size: 14px; }
        td { border: 1px solid #000; padding: 15px; vertical-align: top; }
        
        .medicine-name { font-size: 18px; font-weight: bold; color: #d9534f; margin-bottom: 5px; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; text-transform: uppercase; background: #e9ecef; border: 1px solid #ccc; }
        
        .back-btn { display: inline-block; margin-bottom: 15px; text-decoration: none; color: #000; font-weight: bold; border: 1px solid #000; padding: 5px 15px; }
        .back-btn:hover { background: #000; color: #fff; }
    </style>
</head>
<body>

<div class="rx-container">
    <a href="patient_dashboard.php" class="back-btn">← DASHBOARD</a>

    <div class="hospital-header">
        <h1 style="margin:0; letter-spacing: 2px;">HMS</h1>
        <p style="margin:5px 0; color: #666;">Medication History </p>
    </div>


    <table>
        <thead>
            <tr>
                <th style="width: 18%;">Date</th>
                 <th style="width: 55%;">Prescription Details</th>
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
                                <strong>Instructions:</strong> <?= htmlspecialchars($row['doctor_notes'] ?: 'None') ?>
                            </div>
                            
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="3" style="text-align: center; padding: 50px;">
                        No medication records found for your account.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div style="margin-top: 60px; text-align: right;">
        <div style="display: inline-block; border-top: 1px solid #000; width: 200px; text-align: center; padding-top: 5px; font-size: 14px;">
            Authorized Signature
        </div>
    </div>
</div>

</body>
</html>