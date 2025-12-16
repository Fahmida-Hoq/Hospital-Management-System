<?php
session_start();
include 'config/db.php';
include 'includes/header.php';

/* =========================
   AUTH CHECK
========================= */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient' || !isset($_SESSION['patient_id'])) {
    header("Location: login.php");
    exit();
}

$patient_id = (int)$_SESSION['patient_id'];

/* =========================
   PATIENT INFO
========================= */
$stmt = $conn->prepare("
    SELECT name, status, ward, cabin, bed
    FROM patients
    WHERE patient_id = ?
");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$patient = $stmt->get_result()->fetch_assoc();
$stmt->close();

/* =========================
   PRESCRIPTIONS
========================= */
$prescriptions = [];
$stmt = $conn->prepare("
    SELECT prescribed_medicines, doctor_notes
    FROM prescriptions
    WHERE patient_id = ?
    ORDER BY prescription_id DESC
");
if (!$stmt) {
    die("Prescription query error: " . $conn->error);
}
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$prescriptions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

/* =========================
   LAB TESTS
========================= */
$lab_tests = [];
$stmt = $conn->prepare("
    SELECT test_name, result, status
    FROM lab_tests
    WHERE patient_id = ?
    ORDER BY test_id DESC
");
if ($stmt) {
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $lab_tests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

/* =========================
   BILLING
========================= */
$bills = [];
$stmt = $conn->prepare("
    SELECT bill_id, total_amount, payment_status, created_at
    FROM billing
    WHERE patient_id = ?
    ORDER BY bill_id DESC
");
if ($stmt) {
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $bills = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
?>

<div class="container my-5">

    <h2 class="mb-4">📁 My Medical Records</h2>

    <!-- PATIENT STATUS -->
    <div class="card mb-4">
        <div class="card-body">
            <h5>🧾 Patient Status</h5>
            <p><strong>Status:</strong> <?= htmlspecialchars($patient['status']) ?></p>

            <?php if ($patient['status'] === 'Indoor'): ?>
                <p><strong>Ward:</strong> <?= htmlspecialchars($patient['ward']) ?></p>
                <p><strong>Cabin:</strong> <?= htmlspecialchars($patient['cabin']) ?></p>
                <p><strong>Bed:</strong> <?= htmlspecialchars($patient['bed']) ?></p>
            <?php endif; ?>
        </div>
    </div>

    <!-- PRESCRIPTIONS -->
    <h4>💊 Prescriptions</h4>
    <?php if ($prescriptions): ?>
        <?php foreach ($prescriptions as $p): ?>
            <div class="card mb-2">
                <div class="card-body">
                    <strong>Medicines:</strong>
                    <p><?= nl2br(htmlspecialchars($p['prescribed_medicines'])) ?></p>

                    <strong>Doctor Notes:</strong>
                    <p><?= nl2br(htmlspecialchars($p['doctor_notes'])) ?></p>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="alert alert-info">No prescriptions available.</div>
    <?php endif; ?>

    <!-- LAB TESTS -->
    <h4 class="mt-4">🧪 Lab Tests</h4>
    <?php if ($lab_tests): ?>
        <table class="table table-bordered">
            <tr>
                <th>Test</th>
                <th>Status</th>
                <th>Result</th>
            </tr>
            <?php foreach ($lab_tests as $lt): ?>
                <tr>
                    <td><?= htmlspecialchars($lt['test_name']) ?></td>
                    <td><?= htmlspecialchars($lt['status']) ?></td>
                    <td><?= htmlspecialchars($lt['result'] ?? 'Pending') ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <div class="alert alert-info">No lab tests found.</div>
    <?php endif; ?>

    <!-- BILLING -->
    <h4 class="mt-4">💳 Billing & Invoices</h4>
    <?php if ($bills): ?>
        <table class="table table-striped">
            <tr>
                <th>Bill ID</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Date</th>
                <th>Invoice</th>
            </tr>
            <?php foreach ($bills as $b): ?>
                <tr>
                    <td>#<?= $b['bill_id'] ?></td>
                    <td>৳<?= number_format($b['total_amount'], 2) ?></td>
                    <td><?= htmlspecialchars($b['payment_status']) ?></td>
                    <td><?= htmlspecialchars($b['created_at']) ?></td>
                    <td>
                        <a href="invoice.php?bill_id=<?= $b['bill_id'] ?>"
                           class="btn btn-sm btn-outline-primary">
                            View Invoice
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <div class="alert alert-info">No billing records available.</div>
    <?php endif; ?>

</div>

<?php include 'includes/footer.php'; ?>
