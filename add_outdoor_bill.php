<?php
session_start();
include 'config/db.php';
include 'includes/header.php';

$p_id = $_GET['id'];
$p_query = $conn->query("SELECT name, patient_type FROM patients WHERE patient_id = $p_id");
$p = $p_query->fetch_assoc();

if (isset($_POST['save_bill'])) {
    $desc = $_POST['description'];
    $amount = $_POST['amount'];
    
    $stmt = $conn->prepare("INSERT INTO billing (patient_id, description, amount, status, billing_date) VALUES (?, ?, ?, 'Unpaid', NOW())");
    $stmt->bind_param("isd", $p_id, $desc, $amount);
    
    if ($stmt->execute()) {
        echo "<script>alert('Bill Generated!'); window.location='receptionist_admitted_patients.php';</script>";
    }
}
?>

<div class="container my-5">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0">Generate Outdoor Bill: <?= htmlspecialchars($p['name']) ?></h5>
        </div>
        <div class="card-body">
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Service Type</label>
                    <select name="description" class="form-select" required>
                        <option value="">-- Select Service --</option>
                        <option value="General Consultation Fee">General Consultation Fee</option>
                        <option value="Specialist Consultation Fee">Specialist Consultation Fee</option>
                        <option value="Blood Test (CBC)">Blood Test (CBC)</option>
                        <option value="X-Ray Chest">X-Ray Chest</option>
                        <option value="ECG">ECG</option>
                        <option value="Ultrasonography">Ultrasonography</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Amount (TK)</label>
                    <input type="number" name="amount" class="form-control" placeholder="Enter Amount in TK" required>
                </div>
                <button type="submit" name="save_bill" class="btn btn-primary">Generate & Print Receipt</button>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>