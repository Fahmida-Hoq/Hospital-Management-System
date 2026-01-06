<?php
session_start();
include 'config/db.php';
include 'includes/header.php';

if (isset($_POST['confirm_final_payment'])) {
    $adm_id = (int)$_POST['adm_id'];
    $amount = (float)$_POST['total_amount'];
    $method = $_POST['pay_method'];
    
    // Check if the user is a Patient (logged in via portal) or an Admin
    $is_patient_portal = isset($_SESSION['patient_id']) && !isset($_SESSION['user_id']);

    // Check if this is a partial payment (from the hidden input we added in generate_bill or patient portal)
    $is_partial = isset($_SESSION['is_partial_payment']) || (isset($_POST['is_partial']) && $_POST['is_partial'] == '1');

    // 1. Record the Payment in the billing table
    $pat_res = $conn->query("SELECT patient_id, bed_id FROM admissions WHERE admission_id = $adm_id");
    $pat_data = $pat_res->fetch_assoc();
    $patient_id = $pat_data['patient_id'];
    $bed_id = $pat_data['bed_id'];

    $sql_pay = "INSERT INTO billing (patient_id, admission_id, amount, description, payment_method, status, billing_date) 
                VALUES ($patient_id, $adm_id, $amount, 'Indoor Payment', '$method', 'paid', NOW())";
    
    if ($conn->query($sql_pay)) {
        
        if ($is_partial) {
            // CASE A: PARTIAL PAYMENT (Keep patient admitted)
            ?>
            <div class="container my-5 text-center">
                <div class="card shadow-lg border-0 p-5 mx-auto" style="max-width: 550px; border-radius: 20px;">
                    <div class="mb-4">
                        <i class="fas fa-receipt text-primary" style="font-size: 80px;"></i>
                    </div>
                    <h1 class="text-primary fw-bold mb-3">Payment Received!</h1>
                    <p class="lead text-muted">Partial payment of <strong>BDT <?= number_format($amount, 2) ?></strong> has been credited to the account.</p>
                    
                    <div class="d-grid gap-3">
                        <?php if ($is_patient_portal): ?>
                            <a href="patient_records.php" class="btn btn-primary btn-lg shadow-sm">
                                RETURN TO MY RECORDS
                            </a>
                        <?php else: ?>
                            <a href="generate_bill.php?adm_id=<?= $adm_id ?>" class="btn btn-primary btn-lg shadow-sm">
                                RETURN TO BILLING
                            </a>
                        <?php endif; ?>
                        
                        <a href="view_indoor_patients.php" class="btn btn-outline-secondary">
                            Back to Patient List
                        </a>
                    </div>
                </div>
            </div>
            <?php
        } else {
            // CASE B: FINAL SETTLEMENT (Discharge the patient)
            $conn->query("UPDATE beds SET status = 'available' WHERE bed_id = $bed_id");
            $conn->query("UPDATE admissions SET status = 'discharged', discharge_date = NOW() WHERE admission_id = $adm_id");

            ?>
            <div class="container my-5 text-center">
                <div class="card shadow-lg border-0 p-5 mx-auto" style="max-width: 550px; border-radius: 20px;">
                    <div class="mb-4">
                        <i class="fas fa-check-circle text-success" style="font-size: 80px;"></i>
                    </div>
                    <h1 class="text-success fw-bold mb-3">Discharge Complete!</h1>
                    <p class="lead text-muted">The final balance has been cleared. The patient is officially discharged.</p>
                    
                    <div class="p-4 bg-light rounded-3 border mb-4">
                        <h5 class="mb-1 text-dark">Final Amount Paid: <strong>BDT <?= number_format($amount, 2) ?></strong></h5>
                        <span class="badge bg-primary">Method: <?= $method ?></span>
                    </div>

                    <div class="d-grid gap-3">
                        <a href="indoor_patient_invoice.php?adm_id=<?= $adm_id ?>" target="_blank" class="btn btn-dark btn-lg shadow-sm">
                            <i class="fas fa-file-invoice-dollar me-2"></i> VIEW & PRINT FINAL INVOICE
                        </a>
                        <a href="<?= $is_patient_portal ? 'patient_records.php' : 'view_indoor_patients.php' ?>" class="btn btn-outline-primary">
                            Return to Dashboard
                        </a>
                    </div>
                </div>
            </div>
            <?php
        }
    } else {
        echo "<div class='container mt-5 alert alert-danger'>Database Error: " . $conn->error . "</div>";
    }
} else {
    header("Location: view_indoor_patients.php");
    exit();
}
include 'includes/footer.php';
?>