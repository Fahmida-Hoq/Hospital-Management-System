<?php
session_start();
include 'config/db.php';
include 'includes/header.php';

$request_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch Patient and Request Details
$stmt = $conn->prepare("SELECT ar.*, p.name FROM admission_requests ar JOIN patients p ON ar.patient_id = p.patient_id WHERE ar.request_id = ?");
$stmt->bind_param("i", $request_id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

// Fetch AVAILABLE Beds
$beds = $conn->query("SELECT * FROM beds WHERE status = 'Available'");

if (!$data) {
    echo "<div class='container mt-5 alert alert-danger'>Request Not Found!</div>";
    exit;
}
?>

<div class="container my-5">
    <div class="card shadow">
        <div class="card-header bg-primary text-white"><h4>Finalize Admission: <?= htmlspecialchars($data['name']) ?></h4></div>
        <div class="card-body">
            <form action="process_final_admission.php" method="POST">
                <input type="hidden" name="request_id" value="<?= $request_id ?>">
                <input type="hidden" name="patient_id" value="<?= $data['patient_id'] ?>">

                <h5>Medical Details</h5>
                <div class="row mb-3">
                    <div class="col-md-8">
                        <label>Medical Reports / Symptoms</label>
                        <textarea name="medical_history" class="form-control" placeholder="Enter stroke details, etc." required></textarea>
                    </div>
                    <div class="col-md-4">
                        <label>Blood Group</label>
                        <input type="text" name="blood_group" class="form-control" placeholder="e.g. O+">
                    </div>
                </div>

                <h5>Guardian Details</h5>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label>Guardian Name</label>
                        <input type="text" name="g_name" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label>Guardian Phone</label>
                        <input type="text" name="g_phone" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label>Relationship</label>
                        <input type="text" name="g_relation" class="form-control" required>
                    </div>
                </div>

                <h5>Bed Allocation</h5>
                <div class="mb-3">
                    <label>Select Ward & Bed</label>
                    <select name="bed_id" class="form-control" required>
                        <option value="">-- Choose Available Bed --</option>
                        <?php while($b = $beds->fetch_assoc()): ?>
                            <option value="<?= $b['bed_id'] ?>"><?= $b['ward_name'] ?> - Bed <?= $b['bed_number'] ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <button type="submit" class="btn btn-success w-100">Confirm and Admit Patient</button>
            </form>
        </div>
    </div>
</div>