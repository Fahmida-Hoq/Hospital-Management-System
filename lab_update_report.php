<?php
// lab_update_report.php
session_start();
include 'config/db.php';
include 'includes/header.php';
if (!isset($_SESSION['user_id']) || (!in_array($_SESSION['role'], ['lab','labtech']))) {
    header("Location: login.php");
    exit();
}

$test_id = (int)($_GET['test_id'] ?? 0);
if ($test_id <= 0) { echo "<div class='alert alert-danger'>Invalid test id</div>"; include 'includes/footer.php'; exit; }

$errors = [];
$success = '';

// fetch test
$stmt = $conn->prepare("SELECT lt.*, COALESCE(p.name, u.full_name) AS patient_name FROM lab_tests lt JOIN patients p ON lt.patient_id = p.patient_id LEFT JOIN users u ON p.user_id = u.user_id WHERE lt.test_id = ?");
$stmt->bind_param("i", $test_id);
$stmt->execute();
$test = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$test) { echo "<div class='alert alert-danger'>Test not found</div>"; include 'includes/footer.php'; exit; }

// handle upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['report_file'])) {
    if ($_FILES['report_file']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['report_file']['name'], PATHINFO_EXTENSION);
        $targetDir = __DIR__ . '/uploads/reports';
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
        $targetFile = $targetDir . '/report_' . $test_id . '_' . time() . '.' . $ext;
        $publicPath = 'uploads/reports/' . basename($targetFile);

        if (move_uploaded_file($_FILES['report_file']['tmp_name'], $targetFile)) {
            // update lab_tests
            $u = $conn->prepare("UPDATE lab_tests SET report_file = ?, status = 'completed', date_completed = NOW(), doctor_notified = 0 WHERE test_id = ?");
            if ($u) {
                $u->bind_param("si", $publicPath, $test_id);
                if ($u->execute()) {
                    $success = "Report uploaded and test marked completed. Doctor will be notified.";
                } else $errors[] = "DB execute error: " . $u->error;
                $u->close();
            } else $errors[] = "DB prepare error: " . $conn->error;
        } else $errors[] = "Failed to move uploaded file.";
    } else $errors[] = "Upload error code: " . $_FILES['report_file']['error'];
}
?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center">
        <h3>Update Test: <?= htmlspecialchars($test['test_name']) ?> — <?= htmlspecialchars($test['patient_name']) ?></h3>
        <a href="lab_pending_tests.php" class="btn btn-secondary">Back</a>
    </div>

    <?php if ($success) echo "<div class='alert alert-success'>".htmlspecialchars($success)."</div>"; ?>
    <?php if (!empty($errors)) { echo "<div class='alert alert-danger'>"; foreach ($errors as $e) echo "<div>".htmlspecialchars($e)."</div>"; echo "</div>"; } ?>

    <form method="post" enctype="multipart/form-data" class="card p-3">
        <div class="mb-2">
            <label>Upload report (PDF, JPG, PNG)</label>
            <input type="file" name="report_file" accept=".pdf,image/*" required class="form-control">
        </div>
        <div class="mb-2">
            <label>Report notes (optional)</label>
            <textarea name="report_notes" class="form-control"></textarea>
        </div>
        <button class="btn btn-success">Upload & Complete</button>
    </form>

    <?php if (!empty($test['report_file'])): ?>
        <div class="mt-3"><a href="<?= htmlspecialchars($test['report_file']) ?>" target="_blank">View existing report</a></div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
