<?php
session_start();
include 'config/db.php';
include 'includes/header.php';

// --- LOGIC 1: Handle the "Mark Paid" button click ---
if (isset($_GET['pay_id'])) {
    $bill_id = (int)$_GET['pay_id'];
    $p_id = (int)$_GET['id'];
    $conn->query("UPDATE billing SET status = 'Paid' WHERE bill_id = $bill_id");
    header("Location: collect_payment.php?id=$p_id");
    exit();
}
$p_id = 0;
$is_appointment_fee = false;

if (isset($_GET['id'])) {
    $p_id = (int)$_GET['id'];
} elseif (isset($_SESSION['temp_appointment'])) {
    $p_id = (int)$_SESSION['temp_appointment']['patient_id'];
    $is_appointment_fee = true;
}

if ($p_id == 0) {
    echo "<div class='container mt-5'><div class='alert alert-danger'>No patient selected for payment.</div></div>";
    exit();
}

if (isset($_POST['confirm_appt_payment'])) {
    $data = $_SESSION['temp_appointment'];
    $pat_id = $data['patient_id'];
    $doc_id = $data['doctor_id'];
    $date = $data['date'];
    $time = $data['time'];
    $reason = $data['reason'];
    $amt = $data['amount'];
    $sql = "INSERT INTO appointments (patient_id, doctor_id, scheduled_time, appointment_time, status) 
            VALUES ($pat_id, $doc_id, '$date', '$time', 'Confirmed')";
    
    if ($conn->query($sql)) {
      
        $bill_desc = "Consultation Fee (Paid at Counter)";
        $conn->query("INSERT INTO billing (patient_id, description, amount, status, bill_type) 
                      VALUES ($pat_id, '$bill_desc', $amt, 'Paid', 'Consultation')");
        
        unset($_SESSION['temp_appointment']);
        echo "<script>alert('Payment Received & Appointment Confirmed'); window.location.href='receptionist_manage_appointments.php';</script>";
        exit();
    }
}

$bills = $conn->query("SELECT * FROM billing WHERE patient_id = $p_id");
$patient_data = $conn->query("SELECT name FROM patients WHERE patient_id = $p_id")->fetch_assoc();
?>

<div class="container my-5">
    <div class="card shadow border-0">
        <div class="card-header bg-success text-white py-3">
            <h5 class="mb-0">Payment Collection: <?= htmlspecialchars($patient_data['name'] ?? 'Patient') ?></h5>
        </div>
        <div class="card-body">
            
            <?php if ($is_appointment_fee): ?>
                <div class="bg-light p-4 rounded text-center mb-4 border">
                    <h4 class="text-muted">Consultation Fee Due</h4>
                    <h1 class="display-4 fw-bold text-success">Tk. <?= $_SESSION['temp_appointment']['amount'] ?></h1>
                    <p>Collect the cash from the patient and click the button below to confirm the appointment.</p>
                    <form method="POST">
                        <button type="submit" name="confirm_appt_payment" class="btn btn-success btn-lg px-5 shadow">
                            COLLECT CASH & CONFIRM APPOINTMENT
                        </button>
                    </form>
                </div>
                <hr>
            <?php endif; ?>

            <h6 class="fw-bold mb-3">Previous Billing History</h6>
            <table class="table table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Description</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($bills->num_rows > 0): ?>
                        <?php while($b = $bills->fetch_assoc()): ?>
                        <tr>
                            <td><?= $b['description'] ?></td>
                            <td>Tk. <?= number_format($b['amount'], 2) ?></td>
                            <td>
                                <span class="badge <?= $b['status'] == 'Paid' ? 'bg-success' : 'bg-danger' ?>">
                                    <?= $b['status'] ?>
                                </span>
                            </td>
                            <td>
                                <?php if($b['status'] == 'Unpaid'): ?>
                                    <a href="collect_payment.php?id=<?= $p_id ?>&pay_id=<?= $b['bill_id'] ?>" class="btn btn-sm btn-outline-success">Mark Paid</a>
                                <?php else: ?>
                                    <span class="text-muted small">Completed</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="text-center">No existing bills found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <div class="mt-4">
                <a href="receptionist_manage_appointments.php" class="btn btn-dark">Back to Appointments</a>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>