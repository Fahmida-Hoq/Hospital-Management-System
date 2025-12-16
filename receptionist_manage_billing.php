<?php
session_start();
include 'config/db.php';
include 'includes/header.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'receptionist') {
    header("Location: login.php");
    exit();
}

$success = '';
$error = '';

/* =====================
   CREATE BILL
===================== */
if (isset($_POST['create_bill'])) {
    $patient_id = (int)$_POST['patient_id'];
    $amount = (float)$_POST['amount'];

    if ($patient_id > 0 && $amount > 0) {
        $stmt = $conn->prepare("
            INSERT INTO billing (patient_id, total_amount, status)
            VALUES (?, ?, 'unpaid')
        ");
        $stmt->bind_param("id", $patient_id, $amount);

        if ($stmt->execute()) {
            $success = "Bill created successfully.";
        } else {
            $error = "Failed to create bill.";
        }
        $stmt->close();
    } else {
        $error = "Invalid bill data.";
    }
}

/* =====================
   MARK PAYMENT PAID
===================== */
if (isset($_POST['pay_bill'])) {
    $bill_id = (int)$_POST['bill_id'];

    $stmt = $conn->prepare("
        UPDATE billing
        SET status = 'paid', paid_at = NOW()
        WHERE bill_id = ?
    ");
    $stmt->bind_param("i", $bill_id);

    if ($stmt->execute()) {
        $success = "Payment completed.";
    } else {
        $error = "Payment failed.";
    }
    $stmt->close();
}

/* =====================
   FETCH ADMITTED PATIENTS
===================== */
$patients = $conn->query("
    SELECT p.patient_id, u.full_name
    FROM patients p
    JOIN users u ON p.user_id = u.user_id
    WHERE p.status = 'Indoor'
");

/* =====================
   FETCH BILLS
===================== */
$bills = $conn->query("
    SELECT b.*, u.full_name
    FROM billing b
    JOIN patients p ON b.patient_id = p.patient_id
    JOIN users u ON p.user_id = u.user_id
    ORDER BY b.created_at DESC
");
?>

<div class="container my-5">
    <h2>Billing & Payments</h2>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <hr>

    <h4>Create Bill</h4>
    <form method="post" class="row g-3">
        <div class="col-md-5">
            <label>Patient</label>
            <select name="patient_id" class="form-control" required>
                <option value="">Select patient</option>
                <?php while ($p = $patients->fetch_assoc()): ?>
                    <option value="<?= $p['patient_id'] ?>">
                        <?= htmlspecialchars($p['full_name']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="col-md-4">
            <label>Total Amount</label>
            <input type="number" step="0.01" name="amount" class="form-control" required>
        </div>

        <div class="col-md-3 d-flex align-items-end">
            <button type="submit" name="create_bill" class="btn btn-success w-100">
                Create Bill
            </button>
        </div>
    </form>

    <hr>

    <h4>Billing Records</h4>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Patient</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($b = $bills->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($b['full_name']) ?></td>
                <td><?= $b['total_amount'] ?></td>
                <td><?= ucfirst($b['status']) ?></td>
                <td>
                    <?php if ($b['status'] === 'unpaid'): ?>
                        <form method="post">
                            <input type="hidden" name="bill_id" value="<?= $b['bill_id'] ?>">
                            <button type="submit" name="pay_bill" class="btn btn-sm btn-primary">
                                Mark Paid
                            </button>
                        </form>
                    <?php else: ?>
                        <span class="badge bg-success">Paid</span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>

<?php include 'includes/footer.php'; ?>
