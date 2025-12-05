<?php
session_start();
include 'config/db.php';
include 'includes/header.php';
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'lab') { header("Location: login.php"); exit; }

$test_id = (int)($_GET['test_id'] ?? 0);
if ($test_id <= 0) { echo "<div class='container mt-5 alert alert-danger'>Invalid test ID</div>"; include 'includes/footer.php'; exit; }

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['report_file'])) {
    $f = $_FILES['report_file'];
    if ($f['error'] === 0) {
        $ext = pathinfo($f['name'], PATHINFO_EXTENSION);
        $targetDir = 'uploads/reports';
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
        $target = $targetDir . '/report_' . time() . '_' . rand(100,999) . '.' . $ext;
        if (move_uploaded_file($f['tmp_name'], $target)) {
            $stmt = $conn->prepare("UPDATE lab_tests SET status = 'completed', report_file = ?, date_completed = NOW() WHERE test_id = ?");
            $stmt->bind_param("si", $target, $test_id);
            $stmt->execute();
            $stmt->close();
            $message = 'Report uploaded and test marked completed.';
        } else $message = 'Failed to move uploaded file.';
    } else $message = 'Upload error.';
}

// fetch test
$stmt = $conn->prepare("SELECT lt.*, COALESCE(p.name, u.full_name) AS patient_name FROM lab_tests lt JOIN patients p ON lt.patient_id = p.patient_id LEFT JOIN users u ON p.user_id = u.user_id WHERE lt.test_id = ?");
$stmt->bind_param("i",$test_id); $stmt->execute(); $test = $stmt->get_result()->fetch_assoc(); $stmt->close();
?>

<div class="container my-5">
  <h3>Test: <?=htmlspecialchars($test['test_name'] ?? '')?> for <?=htmlspecialchars($test['patient_name'] ?? '')?></h3>
  <?php if($message): ?><div class="alert alert-info"><?=htmlspecialchars($message)?></div><?php endif; ?>

  <form method="post" enctype="multipart/form-data" class="card p-3">
    <div class="mb-2"><label>Upload Report (PDF/JPG)</label><input type="file" name="report_file" accept=".pdf,image/*" required class="form-control"></div>
    <button class="btn btn-primary">Upload & Mark Complete</button>
  </form>

  <?php if(!empty($test['report_file'])): ?>
    <div class="mt-3"><a href="<?=htmlspecialchars($test['report_file'])?>" target="_blank">View current report</a></div>
  <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
