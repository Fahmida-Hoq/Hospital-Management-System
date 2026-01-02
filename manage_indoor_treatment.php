<?php
session_start();
include 'config/db.php';
include 'includes/header.php';

$adm_id = (int)$_GET['adm_id'];
$p_id = (int)$_GET['patient_id'];

// Fetch Admission & Patient Info
$sql = "SELECT a.*, p.name as p_name, b.ward_name, b.bed_number 
        FROM admissions a 
        JOIN patients p ON a.patient_id = p.patient_id 
        JOIN beds b ON a.bed_id = b.bed_id 
        WHERE a.admission_id = '$adm_id'";
$res = $conn->query($sql);
$data = $res->fetch_assoc();
?>

<div class="container-fluid my-4 px-4">
    <?php if(isset($_GET['msg'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <strong>Success!</strong> <?= htmlspecialchars($_GET['msg']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-dark text-white">Laboratory Investigation History</div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr><th>Test</th><th>Status</th><th>Report/Findings</th><th>Fee</th></tr>
                        </thead>
                        <tbody>
                            <?php
                            $labs = $conn->query("SELECT * FROM lab_tests WHERE admission_id = '$adm_id' ORDER BY test_id DESC");
                            if($labs && $labs->num_rows > 0): 
                                while($l = $labs->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($l['test_name']) ?></td>
                                    <td><span class="badge bg-info"><?= $l['status'] ?></span></td>
                                    <td><?= htmlspecialchars($l['result'] ?? 'Awaiting...') ?></td>
<td><?= htmlspecialchars($l['test_fees'] ?? '0.00') ?> Tk</td>
                                </tr>
                            <?php endwhile; else: ?>
                                <tr><td colspan="4" class="text-center py-3">No lab requests found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-primary text-white">Current Prescriptions</div>
                <div class="card-body p-0">
                    <table class="table mb-0">
                        <thead><tr><th>Medicine</th><th>Dosage</th><th>Frequency</th></tr></thead>
                        <tbody>
                            <?php
                            $meds = $conn->query("SELECT * FROM prescriptions WHERE admission_id = '$adm_id' ORDER BY prescription_id DESC");
                            if($meds && $meds->num_rows > 0): 
                                while($m = $meds->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($m['medicine_name']) ?></td>
                                        <td><?= htmlspecialchars($m['dosage']) ?></td>
                                        <td><?= htmlspecialchars($m['frequency']) ?></td>
                                    </tr>
                            <?php endwhile; else: ?>
                                <tr><td colspan="3" class="text-center py-3">No medicines prescribed yet.</td></tr>
                            <?php endif; ?>
                        </tbody>
                        
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card mb-4 border-primary shadow-sm">
                <div class="card-body">
                    <h6 class="text-primary fw-bold">Order New Lab Test</h6>
                    <form action="save_indoor_logic.php" method="POST">
                        <input type="hidden" name="adm_id" value="<?= $adm_id ?>">
                        <input type="hidden" name="p_id" value="<?= $p_id ?>">
                        <select name="test_dropdown" class="form-select mb-2">
                            <option value="">-- Select Common Test --</option>
                            <option value="Blood Sugar (R)">Blood Sugar (R)</option>
                            <option value="CBC">CBC</option>
                        </select>
                        <input type="text" name="custom_test" class="form-control mb-2" placeholder="OR Enter other test manually">
                        <button type="submit" name="order_lab" class="btn btn-primary w-100">Send to Lab Queue</button>
                    </form>
                </div>
            </div>

             <div class="card mb-4 border-info shadow-sm">
                <div class="card-body">
                    <h6 class="text-info fw-bold">Add Prescription</h6>
                    <form action="save_indoor_logic.php" method="POST">
                        <input type="hidden" name="adm_id" value="<?= $adm_id ?>">
                        <input type="hidden" name="p_id" value="<?= $p_id ?>">
                        <input type="text" name="med_name" class="form-control mb-2" placeholder="Medicine Name" required>
                        <div class="row g-2 mb-2">
                            <div class="col-6"><input type="text" name="dosage" class="form-control" placeholder="Dosage"></div>
                            <div class="col-6"><input type="text" name="freq" class="form-control" placeholder="1+0+1"></div>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-success mb-3" onclick="addRow()">+ Add Medicine</button>
                        <button type="submit" name="add_prescription" class="btn btn-info w-100 text-white">Assign Medicine</button>
                    </form>
                </div>
            </div> 

            <div class="card bg-light border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="fw-bold">Admission Summary</h6>
                    <hr>
                    <p class="small mb-1"><strong>Patient:</strong> <?= $data['p_name'] ?></p>
                    <p class="small mb-1"><strong>Location:</strong> <?= $data['ward_name'] ?> (Bed: <?= $data['bed_number'] ?>)</p>
                    <p class="small mb-1"><strong>Status:</strong> <span class="text-success"><?= $data['status'] ?></span></p>
                    <hr>
                    <a href="generate_bill.php?adm_id=<?= $adm_id ?>" class="btn btn-outline-danger btn-sm w-100 mt-2">Finalize & Discharge</a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>