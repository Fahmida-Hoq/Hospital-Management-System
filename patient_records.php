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
            <div class="card mb-4 shadow-sm border-primary">
                <div class="card-header bg-primary text-white">Current Information</div>
                <div class="card-body">
                    <p><strong>Status:</strong> <span class="badge bg-info text-dark"><?= $info['status'] ?></span></p>
                    <p><strong>Blood Group:</strong> <?= $info['blood_group'] ?? 'N/A' ?></p>
                    <hr>
                    <!-- <p class="mb-1 text-muted small uppercase fw-bold">Attending Physician</p>
                    <h5 class="text-primary font-weight-bold"><i class="fas fa-user-md mr-2"></i> <?= $info['referred_by_doctor'] ?? 'Unassigned' ?></h5> -->
                    
                    
                    <p><strong>Ward:</strong> <?= $info['ward'] ?? 'N/A' ?></p>
                    <p><strong>Bed:</strong> <?= $info['bed'] ?? 'N/A' ?></p>
                </div>
            </div>
        </div>
<div class="card shadow-sm mt-2">
    <div class="card-header bg-info text-white">My Lab Reports</div>
    <div class="table-responsive">
       <table class="table mb-2">
            <thead><tr><th>Test Name</th><th>Result</th><th>Date</th></tr></thead>
            <tbody>
                <?php
                $lab_q = $conn->query("SELECT * FROM lab_tests WHERE appointment_id IN (SELECT appointment_id FROM appointments WHERE patient_id = $patient_id) AND status = 'completed'");
                while($lab = $lab_q->fetch_assoc()): ?>
                <tr>
                    <td><?= $lab['test_name'] ?></td>
                    <td><strong><?= $lab['result'] ?></strong></td>
                    <td><?= date('d M, Y', strtotime($lab['created_at'])) ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
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
                                <td>
                                    <span class="text-capitalize small text-muted d-block"><?= $b['bill_type'] ?? 'Service' ?></span>
                                    <strong><?= $b['description'] ?></strong>
                                </td>
                                <td><?= date('d M, Y', strtotime($b['billing_date'])) ?></td>
                                <td>Tk. <?= number_format($b['amount'], 2) ?></td>
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
                                <th colspan="2" class="text-danger h5">Tk. <?= number_format($total, 2) ?></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>