<?php
session_start();
include 'config/db.php';
include 'includes/header.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'receptionist') {
    header("Location: login.php");
    exit();
}

$errors = [];
$success = '';

// --- 1) Handle mark paid action safely ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_paid'])) {
    $bill_id = (int)($_POST['bill_id'] ?? 0);

    if ($bill_id <= 0) {
        $errors[] = "Invalid bill id.";
    } else {
        $sql = "UPDATE billing SET status = 'paid', paid_at = NOW() WHERE bill_id = ?";
        $stmt = $conn->prepare($sql);

        if ($stmt === false) {
            // prepare failed — show DB error for debugging
            $errors[] = "Database error (prepare failed): " . htmlspecialchars($conn->error);
        } else {
            if (!$stmt->bind_param("i", $bill_id)) {
                $errors[] = "Database error (bind_param failed): " . htmlspecialchars($stmt->error);
            } else {
                if (!$stmt->execute()) {
                    $errors[] = "Database error (execute failed): " . htmlspecialchars($stmt->error);
                } else {
                    $success = "Bill #{$bill_id} marked as paid.";
                }
            }
            $stmt->close();
        }
    }
}

// --- 2) Fetch unpaid bills safely ---
$bills = [];
$sql_fetch = "
    SELECT b.bill_id, b.patient_id, b.description, b.amount, b.created_at, COALESCE(p.name, u.full_name) AS patient_name
    FROM billing b
    LEFT JOIN patients p ON b.patient_id = p.patient_id
    LEFT JOIN users u ON p.user_id = u.user_id
    WHERE b.status = 'unpaid'
    ORDER BY b.created_at ASC
";
$stmt2 = $conn->prepare($sql_fetch);
if ($stmt2 === false) {
    $errors[] = "Database error (prepare failed fetching bills): " . htmlspecialchars($conn->error);
} else {
    if (!$stmt2->execute()) {
        $errors[] = "Database error (execute failed fetching bills): " . htmlspecialchars($stmt2->error);
    } else {
        $res = $stmt2->get_result();
        if ($res) {
            $bills = $res->fetch_all(MYSQLI_ASSOC);
        }
    }
    $stmt2->close();
}

?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Billing — Unpaid</h2>
        <a href="receptionist_dashboard.php" class="btn btn-secondary">⬅ Back</a>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <strong>Errors / Debug:</strong>
            <ul class="mb-0">
            <?php foreach ($errors as $e): ?>
                <li><?= htmlspecialchars($e) ?></li>
            <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if (empty($bills)): ?>
        <div class="alert alert-info">No unpaid bills found.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Bill ID</th>
                        <th>Patient</th>
                        <th>Description</th>
                        <th>Amount</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($bills as $bill): ?>
                    <tr>
                        <td><?= (int)$bill['bill_id'] ?></td>
                        <td><?= htmlspecialchars($bill['patient_name'] ?? 'Unknown') ?></td>
                        <td><?= htmlspecialchars($bill['description']) ?></td>
                        <td><?= htmlspecialchars($bill['amount']) ?></td>
                        <td><?= htmlspecialchars($bill['created_at']) ?></td>
                        <td>
                            <form method="post" style="display:inline">
                                <input type="hidden" name="bill_id" value="<?= (int)$bill['bill_id'] ?>">
                                <button class="btn btn-sm btn-success" name="mark_paid" onclick="return confirm('Mark bill #<?= (int)$bill['bill_id'] ?> paid?')">Mark Paid</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
