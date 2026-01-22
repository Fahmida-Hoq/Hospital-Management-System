<?php
session_start();
include 'config/db.php';
include 'includes/header.php';


$temp_adm = $_SESSION['temp_adm'] ?? [];
$temp_app = $_SESSION['temp_appointment'] ?? [];


if (($temp_adm['type'] ?? '') == 'NEW_ADMISSION') {
    $adm_id = 0; 
    $amount = $temp_adm['admission_fee'];
    $method = $temp_adm['pay_method'];
    $process_file = "process_indoor_admission.php"; 
    $title = "Admission Fee Payment (Advance)";
} 

else if (!empty($temp_app)) {
    $adm_id = 0; 
    $amount = $temp_app['amount'];
    $method = $_GET['method'] ?? 'bKash'; 
    $process_file = "book_appointment.php?payment_success=1&method=" . $method;
    $title = "Outdoor Consultation Payment";
}

else if (isset($_POST['pay_method'])) {
    $adm_id = $_POST['adm_id'];
    $amount = $_POST['total_amount'];
    $method = $_POST['pay_method'];
    $process_file = "process_payment_discharge.php";
    $title = isset($_POST['is_partial']) ? "In-Stay Partial Payment" : "Final Settlement Payment";
} 
else {
    header("Location: dashboard.php");
    exit();
}
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow-lg border-0" style="border-radius: 20px;">
                <div class="card-header bg-dark text-white text-center py-3" style="border-radius: 20px 20px 0 0;">
                    <h5 class="mb-0"><?= $title ?></h5>
                </div>
                <div class="card-body p-4">
                    <div class="text-center mb-4">
                        <small class="text-muted">Payable Amount</small>
                        <h2 class="fw-bold text-primary">BDT <?= number_format($amount, 2) ?></h2>
                    </div>

                    <?php if (isset($_SESSION['temp_appointment'])): ?>
                    <div class="btn-group w-100 mb-4" role="group">
                        <a href="?method=bKash" class="btn <?= $method == 'bKash' ? 'btn-danger' : 'btn-outline-danger' ?>">bKash</a>
                        <a href="?method=Card" class="btn <?= $method == 'Card' ? 'btn-primary' : 'btn-outline-primary' ?>">Credit Card</a>
                    </div>
                    <?php endif; ?>

                    <form action="<?= $process_file ?>" method="POST">
                        <input type="hidden" name="adm_id" value="<?= $adm_id ?>">
                        <input type="hidden" name="total_amount" value="<?= $amount ?>">
                        <input type="hidden" name="pay_method" value="<?= $method ?>">
                        <?php if(isset($_POST['is_partial'])): ?>
                            <input type="hidden" name="is_partial" value="1">
                        <?php endif; ?>

                        <?php if ($method == 'bKash'): ?>
                            <div class="bg-danger text-white p-3 rounded mb-3 text-center">
                                <h4 class="fw-bold mb-0">bKash</h4>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">bKash Account Number</label>
                                <input type="text" class="form-control form-control-lg text-center" placeholder="017XXXXXXXX" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">bKash PIN</label>
                                <input type="password" class="form-control form-control-lg text-center" placeholder="****" required>
                            </div>

                        <?php elseif ($method == 'Card'): ?>
                            <div class="bg-primary text-white p-3 rounded mb-3 text-center">
                                <h4 class="fw-bold mb-0">VISA / MasterCard</h4>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Cardholder Name</label>
                                <input type="text" class="form-control" placeholder="NAME ON CARD" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Card Number</label>
                                <input type="text" class="form-control" placeholder="0000 0000 0000 0000" required>
                            </div>
                            <div class="row">
                                <div class="col-6">
                                    <label class="small fw-bold">Expiry</label>
                                    <input type="text" class="form-control" placeholder="MM/YY" required>
                                </div>
                                <div class="col-6">
                                    <label class="small fw-bold">CVV</label>
                                    <input type="password" class="form-control" placeholder="***" required>
                                </div>
                            </div>

                        <?php else: ?>
                            <div class="alert alert-success text-center">
                                <i class="fas fa-hand-holding-usd fa-3x mb-3"></i>
                                <p>Please confirm that you have received physical currency (Cash).</p>
                            </div>
                        <?php endif; ?>

                        <button type="submit" name="confirm_final_payment" class="btn btn-success btn-lg w-100 mt-4 shadow fw-bold">
                            CONFIRM & PAY NOW
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>