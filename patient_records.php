<?php
session_start();
include 'config/db.php';
include 'includes/header.php';

$patient_id = $_SESSION['patient_id'];

// Simulation of Online Payment
if (isset($_POST['pay_online'])) {
    $conn->query("UPDATE billing SET status = 'Paid' WHERE patient_id = $patient_id AND status = 'Unpaid'");
    echo "<script>alert('Payment Successful via Online Portal!'); window.location='patient_records.php';</script>";
}

$bills = $conn->query("SELECT * FROM billing WHERE patient_id = $patient_id ORDER BY billing_date DESC");
$info = $conn->query("SELECT * FROM patients WHERE patient_id = $patient_id")->fetch_assoc();
?>

<div class="container my-5">
    <h3 class="mb-4">My Medical & Billing Records</h3>

    <div class="row">
        <div class="col-md-4">
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-primary text-white">Current Information</div>
                <div class="card-body">
                    <p><strong>Status:</strong> <?= $info['status'] ?></p>
                    <p><strong>Blood Group:</strong> <?= $info['blood_group'] ?? 'N/A' ?></p>
                    <p><strong>Reason:</strong> <?= $info['admission_reason'] ?? 'None' ?></p>
                    <hr>
                    <p><strong>Ward:</strong> <?= $info['ward'] ?? 'N/A' ?></p>
                    <p><strong>Bed:</strong> <?= $info['bed'] ?? 'N/A' ?></p>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                    <span>Statement of Charges</span>
                    <form method="POST">
                        <button type="submit" name="pay_online" class="btn btn-success btn-sm">Pay All Online</button>
                    </form>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Description</th>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $total = 0;
                            while($b = $bills->fetch_assoc()): 
                                if($b['status'] == 'Unpaid') $total += $b['amount'];
                            ?>
                            <tr>
                                <td><?= $b['description'] ?></td>
                                <td><?= date('d M', strtotime($b['billing_date'])) ?></td>
                                <td>Rs. <?= number_format($b['amount'], 2) ?></td>
                                <td>
                                    <span class="badge bg-<?= $b['status'] == 'Paid' ? 'success' : 'danger' ?>">
                                        <?= $b['status'] ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <th colspan="2" class="text-end">Total Unpaid:</th>
                                <th colspan="2" class="text-danger">Rs. <?= number_format($total, 2) ?></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>