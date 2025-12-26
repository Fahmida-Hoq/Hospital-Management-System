<?php
session_start();
include 'config/db.php';
include 'includes/header.php';

if(isset($_POST['save_bill'])) {
    $p_id = $_POST['patient_id'];
    $desc = $_POST['desc'];
    $amt = $_POST['amt'];

    $stmt = $conn->prepare("INSERT INTO billing (patient_id, description, amount) VALUES (?, ?, ?)");
    $stmt->bind_param("isd", $p_id, $desc, $amt);
    $stmt->execute();
    echo "<script>alert('Charge Added!'); window.location='receptionist_admitted_patients.php';</script>";
}
?>
<div class="container my-5">
    <div class="col-md-5 mx-auto card shadow p-4">
        <h5 class="mb-4">Add Hospital Charge</h5>
        <form method="POST">
            <input type="hidden" name="patient_id" value="<?= $_GET['id'] ?>">
            <div class="mb-3">
                <label>Description</label>
                <input type="text" name="desc" class="form-control" placeholder="e.g. Bed Rent" required>
            </div>
            <div class="mb-3">
                <label>Amount (Tk.)</label>
                <input type="number" step="0.01" name="amt" class="form-control" required>
            </div>
            <button type="submit" name="save_bill" class="btn btn-primary w-100">Save Charge</button>
        </form>
    </div>
</div>