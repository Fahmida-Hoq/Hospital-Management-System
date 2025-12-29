<?php
session_start();
include 'config/db.php';
include 'includes/header.php';

// Check if patient ID is provided
if (!isset($_GET['id'])) {
    header("Location: receptionist_admitted_patients.php");
    exit();
}

$p_id = (int)$_GET['id'];
$p_query = $conn->query("SELECT name, referred_by_doctor FROM patients WHERE patient_id = $p_id");
$patient = $p_query->fetch_assoc();

if(isset($_POST['save_bill'])) {
    $category = $_POST['bill_type']; // Category: Consultation, Bed, etc.
    $item_desc = $_POST['desc'];
    $amt = $_POST['amt'];
    
    // Proper Description: e.g., "Consultation - Dr. Elizabeth Shaw (Routine Visit)"
    $final_desc = $category . ": " . $item_desc;

    $stmt = $conn->prepare("INSERT INTO billing (patient_id, description, amount, status, billing_date) VALUES (?, ?, ?, 'Unpaid', NOW())");
    $stmt->bind_param("isd", $p_id, $final_desc, $amt);
    
    if($stmt->execute()) {
        echo "<script>alert('Hospital Charge Added Successfully!'); window.location='receptionist_admitted_patients.php';</script>";
    }
}
?>

<div class="container my-5">
    <div class="col-md-6 mx-auto card shadow border-0">
        <div class="card-header bg-primary text-white p-3">
            <h5 class="mb-0">Add Charge for: <?= htmlspecialchars($patient['name']) ?></h5>
        </div>
        <div class="card-body p-4">
            <form method="POST">
                <input type="hidden" name="patient_id" value="<?= $p_id ?>">
                
                <div class="mb-3">
                    <label class="form-label fw-bold">Charge Category</label>
                    <select name="bill_type" class="form-select" required>
                        <option value="Consultation">Doctor Visit / Consultation</option>
                        <option value="Bed Rent">Bed / Ward Charge</option>
                        <option value="Medicine">Medicine / Pharmacy</option>
                        <option value="Service">Nursing / Service Charge</option>
                        <option value="Other">Other Expenses</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Item Description</label>
                    <input type="text" name="desc" class="form-control" placeholder="e.g. Dr. Elizabeth Shaw Routine Visit" required>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Amount (TK)</label>
                    <div class="input-group">
                        <span class="input-group-text">TK</span>
                        <input type="number" step="0.01" name="amt" class="form-control" required>
                    </div>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" name="save_bill" class="btn btn-primary btn-lg">Save Charge to Patient Bill</button>
                    <a href="receptionist_admitted_patients.php" class="btn btn-light">Back to List</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>