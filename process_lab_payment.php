<?php
session_start();
include 'config/db.php';

if (!isset($_SESSION['temp_outpatient_data'])) {
    header("Location: lab_manage_tests.php");
    exit();
}

$data = $_SESSION['temp_outpatient_data'];
$method = $data['method'];

if (isset($_POST['final_confirm'])) {
    $t_id = $data['test_id'];
    $p_id = $data['patient_id'];
    $amt = $data['amount'];
    $res_text = $data['result_text'];
    $t_name = $data['test_name'];
    
   
    $conn->query("UPDATE lab_tests SET status='completed', result='$res_text', test_fees=$amt, completed_at=NOW() WHERE test_id=$t_id");

    
    $desc = "Lab Test: $t_name (Paid via $method)";
    $conn->query("INSERT INTO billing (patient_id, description, amount, status, billing_date) VALUES ($p_id, '$desc', $amt, 'Paid', NOW())");

    unset($_SESSION['temp_outpatient_data']);
    header("Location: print_lab_receipt.php?test_id=$t_id&method=$method");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Payment Gateway</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="card mx-auto shadow" style="max-width: 450px;">
            <div class="card-header text-center bg-primary text-white py-3">
                <h4 class="mb-0">OUTDOOR PAYMENT: <?= strtoupper($method) ?></h4>
            </div>
            <div class="card-body p-4">
                <h5 class="text-center mb-4">Amount to Pay: <strong>TK <?= number_format($data['amount'], 2) ?></strong></h5>
                
                <form method="POST">
                    <?php if($method == 'bKash'): ?>
                        <div class="mb-3">
                            <label class="form-label">bKash Mobile Number:</label>
                            <input type="text" name="bkash_num" class="form-control" placeholder="017XXXXXXXX" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Transaction PIN:</label>
                            <input type="password" name="bkash_pin" class="form-control" placeholder="****" required>
                        </div>

                    <?php elseif($method == 'Card'): ?>
                        <div class="mb-3">
                            <label class="form-label">Card Holder Name:</label>
                            <input type="text" name="card_name" class="form-control" placeholder="Full Name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Card Number:</label>
                            <input type="text" name="card_no" class="form-control" placeholder="1234 5678 9101 1121" required>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-7">
                                <label class="form-label">Expiry (MM/YY):</label>
                                <input type="text" name="exp" class="form-control" placeholder="MM/YY" required>
                            </div>
                            <div class="col-5">
                                <label class="form-label">CVV:</label>
                                <input type="password" name="cvv" class="form-control" placeholder="123" required>
                            </div>
                        </div>

                    <?php else: ?>
                        <div class="alert alert-info text-center py-4">
                            <p class="mb-1">Please collect cash in hand:</p>
                            <h3 class="fw-bold">TK <?= $data['amount'] ?></h3>
                        </div>
                    <?php endif; ?>

                    <button type="submit" name="final_confirm" class="btn btn-success btn-lg w-100 py-3 fw-bold mt-2">CONFIRM & PRINT</button>
                    <div class="text-center mt-3">
                        <a href="lab_manage_tests.php" class="text-danger text-decoration-none small">Cancel Transaction</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>