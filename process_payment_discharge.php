<?php
session_start();
include 'config/db.php';
include 'includes/header.php';

// Fix for "Invalid Access" - check for the button name from your gateway
if (isset($_POST['confirm_final_payment'])) {
    $adm_id = (int)$_POST['adm_id'];
    $amount = (float)$_POST['total_amount'];
    $method = $_POST['pay_method'];

    // 1. Record the Payment in the database
    $sql_pay = "INSERT INTO billing (admission_id, amount, payment_method, payment_status) 
                VALUES ($adm_id, $amount, '$method', 'Paid')";
    
    if ($conn->query($sql_pay)) {
        // 2. Fetch Bed ID to release it
        $adm_res = $conn->query("SELECT bed_id FROM admissions WHERE admission_id = $adm_id");
        $adm_data = $adm_res->fetch_assoc();
        $bed_id = $adm_data['bed_id'];

        // 3. Update Bed to available and mark patient as discharged
        $conn->query("UPDATE beds SET status = 'available' WHERE bed_id = $bed_id");
        $conn->query("UPDATE admissions SET status = 'discharged', discharge_date = NOW() WHERE admission_id = $adm_id");

        // 4. Final Success Screen with your custom Invoice Link
        ?>
        <div class="container my-5 text-center">
            <div class="card shadow-lg border-0 p-5 mx-auto" style="max-width: 550px; border-radius: 20px;">
                <div class="mb-4">
                    <i class="fas fa-check-circle text-success" style="font-size: 80px;"></i>
                </div>
                <h1 class="text-success fw-bold mb-3">Discharge Complete!</h1>
                <p class="lead text-muted">Bed #<?= $bed_id ?> is now vacant and ready for new patients.</p>
                
                <div class="p-4 bg-light rounded-3 border mb-4">
                    <h5 class="mb-1 text-dark">Amount Paid: <strong>BDT <?= number_format($amount, 2) ?></strong></h5>
                    <span class="badge bg-primary">Method: <?= $method ?></span>
                </div>

                <div class="d-grid gap-3">
                    <a href="indoor_patient_invoice.php?adm_id=<?= $adm_id ?>" target="_blank" class="btn btn-dark btn-lg shadow-sm">
                        <i class="fas fa-file-invoice-dollar me-2"></i> VIEW & PRINT INVOICE
                    </a>
                    
                    <a href="view_indoor_patients.php" class="btn btn-outline-primary">
                        Return to Patient List
                    </a>
                </div>
            </div>
        </div>
        <?php
    } else {
        // Handle database errors
        echo "<div class='container mt-5 alert alert-danger'>Database Error: " . $conn->error . "</div>";
    }
} else {
    // Prevent direct access to this script
    header("Location: view_indoor_patients.php");
    exit();
}
include 'includes/footer.php';
?>