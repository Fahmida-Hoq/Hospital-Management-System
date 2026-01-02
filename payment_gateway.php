<?php
session_start();
include 'config/db.php';
include 'includes/header.php';

if (!isset($_POST['pay_method'])) {
    header("Location: doctor_indoor_patients.php");
    exit();
}

$adm_id = $_POST['adm_id'];
$amount = $_POST['total_amount'];
$method = $_POST['pay_method'];
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow-lg border-0" style="border-radius: 20px;">
                <div class="card-header bg-dark text-white text-center py-3" style="border-radius: 20px 20px 0 0;">
                    <h5 class="mb-0">Secure Payment Gateway</h5>
                </div>
                <div class="card-body p-4">
                    <div class="text-center mb-4">
                        <small class="text-muted">Payable Amount</small>
                        <h2 class="fw-bold text-primary">BDT <?= number_format($amount, 2) ?></h2>
                    </div>

                    <form action="process_payment_discharge.php" method="POST">
                        <input type="hidden" name="adm_id" value="<?= $adm_id ?>">
                        <input type="hidden" name="total_amount" value="<?= $amount ?>">
                        <input type="hidden" name="pay_method" value="<?= $method ?>">

                        <?php if ($method == 'bKash'): ?>
                            <div class="bg-danger text-white p-3 rounded mb-3 text-center">
                                <h4 class="fw-bold mb-0">bKash</h4>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Your bKash Account Number</label>
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
                                <p>Please confirm that you have received physical currency from the patient.</p>
                            </div>
                        <?php endif; ?>

                        <<button type="submit" name="confirm_final_payment" class="btn btn-success btn-lg w-100 mt-4 shadow fw-bold">
    PAY & DISCHARGE NOW
</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>