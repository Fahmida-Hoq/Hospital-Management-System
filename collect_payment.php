<?php
session_start();
include 'config/db.php';
include 'includes/header.php';

$p_id = $_GET['id'];

if (isset($_GET['pay_id'])) {
    $bill_id = $_GET['pay_id'];
    $conn->query("UPDATE billing SET status = 'Paid' WHERE bill_id = $bill_id");
    header("Location: collect_payment.php?id=$p_id");
}

$bills = $conn->query("SELECT * FROM billing WHERE patient_id = $p_id");
?>
<div class="container my-5">
    <div class="card shadow">
        <div class="card-header bg-success text-white"><h5>Collect Payments</h5></div>
        <div class="card-body">
            <table class="table">
                <thead><tr><th>Description</th><th>Amount</th><th>Status</th><th>Action</th></tr></thead>
                <tbody>
                    <?php while($b = $bills->fetch_assoc()): ?>
                    <tr>
                        <td><?= $b['description'] ?></td>
                        <td>Tk. <?= $b['amount'] ?></td>
                        <td><?= $b['status'] ?></td>
                        <td>
                            <?php if($b['status'] == 'Unpaid'): ?>
                                <a href="collect_payment.php?id=<?= $p_id ?>&pay_id=<?= $b['bill_id'] ?>" class="btn btn-sm btn-success">Mark Paid</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <a href="receptionist_admitted_patients.php" class="btn btn-dark">Back</a>
        </div>
    </div>
</div>