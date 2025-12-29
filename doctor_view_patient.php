<?php
session_start();
include 'config/db.php';
include 'includes/header.php';

// Role Security: Ensure only Doctors can access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header("Location: login.php");
    exit();
}

$doctor_user_id = (int)$_SESSION['user_id'];
$doctor_name = $_SESSION['full_name'] ?? 'Doctor';

/* ======================
    GET DOCTOR ID
====================== */
$res = $conn->query("SELECT doctor_id FROM doctors WHERE user_id=$doctor_user_id");
if (!$res || $res->num_rows === 0) {
    echo "<div class='container my-5'><div class='alert alert-danger'>Doctor profile not found.</div></div>";
    include 'includes/footer.php';
    exit();
}
$doctor_id = (int)$res->fetch_assoc()['doctor_id'];

$patient_id = (int)($_GET['patient_id'] ?? 0);
if ($patient_id <= 0) { header("Location: doctor_dashboard.php"); exit(); }

$success = "";
$errors = [];

/* ==============================================
    ACTION: ASSIGN SELECTIVE LAB TESTS (FIXED)
============================================== */
if (isset($_POST['assign_lab'])) {
    // Find the latest appointment ID for this patient/doctor
    $appt_check = $conn->query("SELECT appointment_id FROM appointments WHERE patient_id = $patient_id AND doctor_id = $doctor_id ORDER BY appointment_id DESC LIMIT 1");
    $current_appt_id = ($appt_check->num_rows > 0) ? $appt_check->fetch_assoc()['appointment_id'] : 0;

    $tests_to_insert = !empty($_POST['lab_tests']) ? $_POST['lab_tests'] : [];
    if (!empty($_POST['custom_test_name'])) { $tests_to_insert[] = trim($_POST['custom_test_name']); }

    if (!empty($tests_to_insert) && $current_appt_id > 0) {
        foreach ($tests_to_insert as $test_name) {
            $test_name = $conn->real_escape_string($test_name);
            // ADDED appointment_id to the query below
            $sql = "INSERT INTO lab_tests (patient_id, doctor_id, appointment_id, doctor_name, test_name, status) 
                    VALUES ($patient_id, $doctor_id, $current_appt_id, '$doctor_name', '$test_name', 'pending')";
            $conn->query($sql);
        }
        $success = "Lab tests ordered successfully!";
    } else {
        $errors[] = "Error: No active appointment found or no test selected.";
    }
}

/* =============================
    ACTION: SAVE PRESCRIPTION (OPD FIXED)
============================= */
if (isset($_POST['prescribe'])) {
    $medicines = $_POST['med_name'] ?? [];
    $dosages = $_POST['med_dosage'] ?? [];
    $frequencies = $_POST['med_freq'] ?? [];
    $notes = $conn->real_escape_string($_POST['doctor_notes']);
    $diet = $conn->real_escape_string($_POST['diet_rules']);

    // 1. Logic: Find the latest appointment to link this prescription to
    $appt_query = $conn->query("SELECT appointment_id FROM appointments 
                                WHERE patient_id = $patient_id 
                                AND doctor_id = $doctor_id 
                                ORDER BY appointment_id DESC LIMIT 1");
    
    // Check if appointment exists, else set to NULL
    if ($appt_query && $appt_query->num_rows > 0) {
        $appt_data = $appt_query->fetch_assoc();
        $appointment_id = $appt_data['appointment_id'];
    } else {
        $appointment_id = "NULL"; 
    }

    // 2. Format the medicine data
    $prescription_detail = "";
    for ($i = 0; $i < count($medicines); $i++) {
        if (!empty(trim($medicines[$i]))) {
            $prescription_detail .= $conn->real_escape_string($medicines[$i]) . " | " . 
                                    $conn->real_escape_string($dosages[$i]) . " | " . 
                                    $conn->real_escape_string($frequencies[$i]) . "\n";
        }
    }

    // 3. Insert into Database
    if ($prescription_detail != "") {
        // We do NOT wrap $appointment_id in quotes so it can be the word NULL
        $sql = "INSERT INTO prescriptions (patient_id, doctor_id, appointment_id, prescribed_medicines, doctor_notes, diet_rules, created_at) 
                VALUES ($patient_id, $doctor_id, $appointment_id, '$prescription_detail', '$notes', '$diet', NOW())";
        
        if($conn->query($sql)) {
            $success = "Prescription saved successfully!";
            
            // 4. Update Appointment status to 'Completed' automatically
            if($appointment_id !== "NULL") {
                $conn->query("UPDATE appointments SET status='Completed' WHERE appointment_id=$appointment_id");
            }
        } else {
            $errors[] = "Database Error: " . $conn->error;
        }
    } else {
        $errors[] = "Please add at least one medicine.";
    }
}

/* =============================
   ACTION: ADMISSION REQUEST
============================= */
if (isset($_POST['suggest_admission'])) {
    $reason = $conn->real_escape_string($_POST['admission_reason']);
    $dept = $conn->real_escape_string($_POST['suggested_department']);
    $ward = $conn->real_escape_string($_POST['suggested_ward']);
    $date = $_POST['suggested_admit_date'];
    $sql = "INSERT INTO admission_requests (patient_id, doctor_id, reason, suggested_department, suggested_ward, suggested_admit_date, request_status, request_date) 
            VALUES ($patient_id, $doctor_id, '$reason', '$dept', '$ward', '$date', 'Pending Reception', NOW())";
    if ($conn->query($sql)) { $success = "Admission request sent!"; }
}

/* =============================
   FETCH DATA
============================= */
$p_query = $conn->query("SELECT p.*, COALESCE(u.full_name, p.name) AS patient_name FROM patients p LEFT JOIN users u ON p.user_id = u.user_id WHERE p.patient_id = $patient_id");
$patient = $p_query->fetch_assoc();

$l_res = $conn->query("SELECT * FROM lab_tests WHERE patient_id = $patient_id ORDER BY test_id DESC");
$lab_tests = ($l_res) ? $l_res->fetch_all(MYSQLI_ASSOC) : [];

$pr_res = $conn->query("SELECT * FROM prescriptions WHERE patient_id = $patient_id ORDER BY created_at DESC");
$prescriptions = ($pr_res) ? $pr_res->fetch_all(MYSQLI_ASSOC) : [];

$admit_res = $conn->query("SELECT * FROM admission_requests WHERE patient_id = $patient_id AND request_status = 'Pending Reception' LIMIT 1");
$pending_admission = ($admit_res && $admit_res->num_rows > 0);
?>

<div class="container my-5">
    <div class="row mb-4 align-items-center">
        <div class="col-md-8">
            <h2>Patient: <?= htmlspecialchars($patient['patient_name']) ?></h2>
            <p class="text-muted">Type: <span class="badge bg-secondary"><?= $patient['patient_type'] ?></span></p>
        </div>
        <div class="col-md-4 text-end">
            <a href="doctor_dashboard.php" class="btn btn-outline-dark">Back to Dashboard</a>
        </div>
    </div>

    <?php if ($success): ?><div class="alert alert-success shadow-sm"><?= $success ?></div><?php endif; ?>
    <?php if (!empty($errors)): foreach($errors as $err): ?>
        <div class="alert alert-danger"><?= $err ?></div>
    <?php endforeach; endif; ?>

    <div class="row">
        <div class="col-md-7">
            <div class="card mb-4 shadow-sm border-0">
                <div class="card-header bg-dark text-white">Laboratory Investigation History</div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>Test</th><th>Status</th><th>Findings / Fee</th></tr></thead>
                        <tbody>
                            <?php if(empty($lab_tests)): ?><tr><td colspan="3" class="text-center py-4 text-muted">No tests assigned.</td></tr><?php endif; ?>
                            <?php foreach ($lab_tests as $t): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($t['test_name']) ?></strong></td>
                                <td><span class="badge bg-<?= ($t['status']=='completed')?'success':'warning' ?>"><?= ucfirst($t['status']) ?></span></td>
                                <td>
                                    <?php if ($t['status'] === 'completed'): ?>
                                        <div class="text-success small fw-bold"><?= htmlspecialchars($t['result'] ?? '') ?></div>
                                        <div class="text-muted" style="font-size: 0.8rem;">Fee: <?= number_format($t['test_fees'], 2) ?> TK</div>
                                    <?php else: ?>
                                        <span class="text-muted small italic">Awaiting Lab Processing</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card shadow-sm mt-4">
                <div class="card-header bg-primary text-white">Prescription History</div>
                <div class="card-body">
                    <?php if(empty($prescriptions)): ?><p class="text-center text-muted">No prescriptions recorded.</p><?php endif; ?>
                    <?php foreach ($prescriptions as $pr): ?>
                        <div class="border rounded p-3 mb-3 bg-light shadow-sm">
                            <h6 class="text-muted border-bottom pb-2"><?= date('d M Y, h:i A', strtotime($pr['created_at'])) ?></h6>
                            <table class="table table-sm table-bordered bg-white">
                                <thead class="table-secondary">
                                    <tr><th>Medication Name</th><th>Dosage</th><th>Frequency</th></tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $lines = explode("\n", trim($pr['prescribed_medicines']));
                                    foreach($lines as $line): 
                                        $parts = explode(" | ", $line);
                                        if(count($parts) < 3) continue;
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars($parts[0]) ?></td>
                                        <td><?= htmlspecialchars($parts[1]) ?></td>
                                        <td><?= htmlspecialchars($parts[2]) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <?php if(!empty($pr['diet_rules'])): ?>
                                <div class="mt-2 text-danger"><strong>Rules/Diet:</strong> <?= htmlspecialchars($pr['diet_rules']) ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="col-md-5">
            <div class="card mb-4 border-primary shadow-sm">
                <div class="card-body">
                    <h5 class="text-primary mb-3">Order Lab Tests</h5>
                    <form method="post">
                        <label class="small fw-bold">Select Common Tests:</label>
                        <div style="max-height: 120px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; border-radius: 5px; background: #f9f9f9;" class="mb-2">
                            <?php 
                            $test_list = ["CBC", "HBA1C", "Blood Sugar (R)", "X-Ray Chest", "ECG", "Urine R/E", "LFT", "S. Creatinine", "Lipid Profile"];
                            foreach($test_list as $lt): 
                            ?>
                            <div class="form-check mb-1">
                                <input class="form-check-input" type="checkbox" name="lab_tests[]" value="<?= $lt ?>" id="chk_<?= $lt ?>">
                                <label class="form-check-label" for="chk_<?= $lt ?>"><?= $lt ?></label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <label class="small fw-bold">Or Write Other Test Name:</label>
                        <input type="text" name="custom_test_name" class="form-control mb-3" placeholder="Enter test name manually">
                        <button name="assign_lab" class="btn btn-primary w-100 shadow-sm">Assign Tests</button>
                    </form>
                </div>
            </div>
<div class="mt-4">
    <h4>Lab Reports for this Patient</h4>
    <?php
    // FIX: Define the missing variable by finding the latest appointment
    $appt_lookup = $conn->query("SELECT appointment_id FROM appointments 
                                WHERE patient_id = $patient_id 
                                ORDER BY appointment_id DESC LIMIT 1");
    
    $current_appt_id = ($appt_lookup && $appt_lookup->num_rows > 0) ? $appt_lookup->fetch_assoc()['appointment_id'] : 0;

    // Only run query if we have an ID
    if ($current_appt_id > 0) {
        $doctor_lab = $conn->query("SELECT * FROM lab_tests WHERE appointment_id = $current_appt_id AND status = 'completed'");
        
        if ($doctor_lab && $doctor_lab->num_rows > 0) {
            while($report = $doctor_lab->fetch_assoc()): ?>
                <div class="alert alert-secondary shadow-sm">
                    <strong><?= htmlspecialchars($report['test_name']) ?>:</strong> 
                    <p class="mb-1"><?= nl2br(htmlspecialchars($report['result'])) ?></p>
                    <small class="text-muted">Reported on: <?= date('d M Y', strtotime($report['created_at'])) ?></small>
                </div>
            <?php endwhile;
        } else {
            echo "<p class='text-muted italic'>No completed reports for the current appointment.</p>";
        }
    } else {
        echo "<p class='text-muted'>No active appointment found for this patient.</p>";
    }
    ?>
</div>
            <div class="card mb-4 border-success shadow-sm">
                <div class="card-body">
                    <h5 class="text-success mb-3"><i class="fas fa-pills"></i> New Prescription</h5>
                    <form method="post">
                        <table class="table table-sm" id="medTable">
                            <thead>
                                <tr style="font-size: 0.8rem;">
                                    <th>Medicine Name</th><th>Dosage</th><th>Frequency</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><input type="text" name="med_name[]" class="form-control form-control-sm" placeholder="Napa" required></td>
                                    <td><input type="text" name="med_dosage[]" class="form-control form-control-sm" placeholder="500mg"></td>
                                    <td><input type="text" name="med_freq[]" class="form-control form-control-sm" placeholder="1+0+1"></td>
                                </tr>
                            </tbody>
                        </table>
                        <button type="button" class="btn btn-sm btn-outline-success mb-3" onclick="addRow()">+ Add Medicine</button>
                        <label class="small fw-bold">Rules to Maintain (Diet/Notes):</label>
                        <textarea name="diet_rules" class="form-control mb-2" rows="2" placeholder="No oily food, walk 20 mins etc."></textarea>
                        <label class="small fw-bold">Doctor's Private Notes:</label>
                        <textarea name="doctor_notes" class="form-control mb-3" rows="2"></textarea>
                        <button name="prescribe" class="btn btn-success w-100 shadow-sm">Save Prescription</button>
                    </form>
                </div>
            </div>

            <div class="card border-warning shadow-sm">
                <div class="card-body">
                    <h5 class="text-warning mb-3">Admission Request</h5>
                    <?php if ($pending_admission): ?>
                        <div class="alert alert-warning py-2">Admission Request is Pending Reception.</div>
                    <?php else: ?>
                        <form method="post">
                            <textarea name="admission_reason" class="form-control mb-2" placeholder="Reason for Admission" required></textarea>
                            <label class="small text-muted mb-1">Select Department:</label>
                            <select name="suggested_department" class="form-select mb-2" required>
                                <option value="" disabled selected>-- Choose Department --</option>
                                <option value="General Medicine">General Medicine</option>
                                <option value="Surgery">Surgery</option>
                                <option value="Gynae & Obs">Gynae & Obs</option>
                                <option value="Paediatrics">Paediatrics</option>
                                <option value="Cardiology">Cardiology</option>
                            </select>
                            <label class="small text-muted mb-1">Select Ward/Cabin:</label>
                            <select name="suggested_ward" class="form-select mb-2" required>
                                <option value="" disabled selected>-- Choose Ward --</option>
                                <option value="Male Ward">Male General Ward</option>
                                <option value="Female Ward">Female General Ward</option>
                                <option value="ICU">ICU</option>
                                <option value="Private Cabin">Private Cabin</option>
                            </select>
                            <label class="small text-muted mb-1">Suggested Admission Date:</label>
                            <input type="date" name="suggested_admit_date" class="form-control mb-3" value="<?= date('Y-m-d') ?>" required>
                            <button name="suggest_admission" class="btn btn-warning w-100 shadow-sm">Send Admission Request</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function addRow() {
    var table = document.getElementById("medTable").getElementsByTagName('tbody')[0];
    var row = table.insertRow(-1);
    row.innerHTML = '<td><input type="text" name="med_name[]" class="form-control form-control-sm"></td>' +
                    '<td><input type="text" name="med_dosage[]" class="form-control form-control-sm"></td>' +
                    '<td><input type="text" name="med_freq[]" class="form-control form-control-sm"></td>';
}
</script>

<?php include 'includes/footer.php'; ?>