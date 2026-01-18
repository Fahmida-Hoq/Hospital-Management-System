<?php
session_start();
include 'config/db.php';
include 'includes/header.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['receptionist', 'admin'])) {
    header("Location: login.php"); exit();
}
$is_request = false;
$req_data = [];
if (isset($_GET['request_id'])) {
    $request_id = (int)$_GET['request_id'];
    $req_sql = "SELECT ar.*, p.*, u.email FROM admission_requests ar 
                JOIN patients p ON ar.patient_id = p.patient_id 
                LEFT JOIN users u ON p.user_id = u.user_id
                WHERE ar.request_id = $request_id";
    $req_res = $conn->query($req_sql);
    if ($req_res && $req_res->num_rows > 0) {
        $req_data = $req_res->fetch_assoc();
        $is_request = true;
    }
}
$doctors = $conn->query("SELECT u.full_name, d.doctor_id, d.department FROM doctors d JOIN users u ON d.user_id = u.user_id");
$beds = $conn->query("SELECT * FROM beds WHERE status = 'Available' ORDER BY ward_name ASC");
$fixed_password = "123456";
?>

<div class="container my-5">
    <div class="card shadow-lg border-0">
        <div class="p-4 text-white" style="background-color: #b31b1b;">
            <div class="d-flex justify-content-between align-items-center">
                <h2 class="mb-0 fw-bold">
                    <?= $is_request ? "Admit Existing Patient: " . htmlspecialchars($req_data['name']) : "New Indoor Patient Registration & Admission" ?>
                </h2>
                <i class="fas fa-hospital-user fa-3x opacity-50"></i>
            </div>
        </div>

        <form action="process_indoor_admission.php" method="POST" class="card-body p-5 bg-light">
            
            <?php if($is_request): ?>
                <input type="hidden" name="request_id" value="<?= $req_data['request_id'] ?>">
                <input type="hidden" name="patient_id" value="<?= $req_data['patient_id'] ?>">
                <input type="hidden" name="is_existing" value="1">
            <?php endif; ?>

            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <label class="form-label text-muted small fw-bold">Assigned Doctor</label>
                    <select name="doctor_id" class="form-select border-0 border-bottom rounded-0 shadow-none bg-light" required>
                        <option value="">Select Doctor</option>
                        <?php while($d = $doctors->fetch_assoc()): ?>
                            <option value="<?= $d['doctor_id'] ?>"> <?= $d['full_name'] ?> (<?= $d['department'] ?>)</option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label text-muted small fw-bold">Admission Date</label>
                    <input type="date" name="admission_date" class="form-control border-0 border-bottom rounded-0 shadow-none bg-light" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label text-muted small fw-bold">Admission Fee to be Paid (Tk)</label>
                    <input type="number" name="admission_fee" class="form-control border-0 border-bottom rounded-0 shadow-none bg-white fw-bold text-success" placeholder="Enter Amount Paid" required>
                </div>
            </div>

            <h5 class="text-danger border-bottom pb-2 mb-4">Patient Information</h5>
            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <label class="form-label text-muted small fw-bold">Patient Full Name</label>
                    <input type="text" name="name" class="form-control border-0 border-bottom rounded-0 shadow-none bg-light" 
                           value="<?= $is_request ? htmlspecialchars($req_data['name']) : '' ?>" required>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label text-muted small fw-bold">Login Email (Auto-Generated/Confirmed)</label>
                    <input type="email" name="email" class="form-control border-0 border-bottom rounded-0 shadow-none bg-white fw-bold" 
                           value="<?= $is_request ? htmlspecialchars($req_data['email'] ?? '') : '' ?>" 
                           placeholder="patient@example.com" required>
                    
                    <input type="hidden" name="generated_password" value="<?= $fixed_password ?>">
                    <div class="mt-2 p-2 bg-white border rounded">
                        <small class="text-muted">The patient will use this email and password <span class="badge bg-primary">123456</span> to access Indoor Facilities.</small>
                    </div>
                </div>
     <div class="col-md-4">
                    <label class="form-label text-muted small fw-bold">Age</label>
                    <input type="text" name="age" class="form-control border-0 border-bottom rounded-0 shadow-none bg-light" 
                           value="<?= $is_request ? htmlspecialchars($req_data['age']) : '' ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label text-muted small fw-bold">Phone Number</label>
                    <input type="text" name="phone" class="form-control border-0 border-bottom rounded-0 shadow-none bg-light" 
                           value="<?= $is_request ? htmlspecialchars($req_data['phone']) : '' ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label text-muted small fw-bold">Blood Group</label>
                    <select name="blood_group" class="form-select border-0 border-bottom rounded-0 shadow-none bg-light">
                        <option value="<?= $is_request ? $req_data['blood_group'] : '' ?>">
                            <?= ($is_request && $req_data['blood_group']) ? $req_data['blood_group'] : 'Select' ?>
                        </option>
                        <option>A+</option><option>B+</option><option>O+</option><option>AB+</option>
                        <option>A-</option><option>B-</option><option>O-</option><option>AB-</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label text-muted small fw-bold">Gender</label>
                    <select name="gender" class="form-select border-0 border-bottom rounded-0 shadow-none bg-light">
                        <option <?= ($is_request && $req_data['gender'] == 'Male') ? 'selected' : '' ?>>Male</option>
                        <option <?= ($is_request && $req_data['gender'] == 'Female') ? 'selected' : '' ?>>Female</option>
                        <option <?= ($is_request && $req_data['gender'] == 'Other') ? 'selected' : '' ?>>Other</option>
                    </select>
                </div>
            </div>

            <h5 class="text-danger border-bottom pb-2 mb-4">Guardian & Emergency Info</h5>
            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <label class="form-label text-muted small fw-bold">Guardian Name</label>
                    <input type="text" name="guardian_name" class="form-control border-0 border-bottom rounded-0 shadow-none bg-light" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label text-muted small fw-bold">Relation</label>
                    <input type="text" name="relation" class="form-control border-0 border-bottom rounded-0 shadow-none bg-light">
                </div>
                <div class="col-md-4">
                    <label class="form-label text-muted small fw-bold">Guardian Phone</label>
                    <input type="text" name="guardian_phone" class="form-control border-0 border-bottom rounded-0 shadow-none bg-light" required>
                </div>
                <div class="col-md-12">
                    <label class="form-label text-muted small fw-bold">Reason for Admission</label>
                    <textarea name="reason" class="form-control border-0 border-bottom rounded-0 shadow-none bg-light" rows="2" ><?= $is_request ? htmlspecialchars($req_data['reason']) : '' ?></textarea>
                </div>
            </div>

            <h5 class="text-primary border-bottom pb-2 mb-4"><i class="fas fa-wallet me-2"></i> Initial Payment Settlement</h5>
            <div class="row g-4 mb-4 bg-white p-3 border rounded shadow-sm mx-0">
                <div class="col-md-12">
                    <label class="form-label text-muted small fw-bold d-block mb-3">Admission Fee Payment Method</label>
                    <div class="d-flex gap-5">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="admission_pay_method" id="cash" value="Cash" checked>
                            <label class="form-check-label fw-bold" for="cash"><i class="fas fa-money-bill-wave text-success"></i> Cash</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="admission_pay_method" id="card" value="Card">
                            <label class="form-check-label fw-bold" for="card"><i class="fas fa-credit-card text-primary"></i> Card</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="admission_pay_method" id="bkash" value="bKash">
                            <label class="form-check-label fw-bold text-danger" for="bkash">bKash</label>
                        </div>
                    </div>
                </div>
            </div>

            <h5 class="text-danger border-bottom pb-2 mb-4">Accommodation</h5>
            <div class="col-md-12 mb-4">
                <label class="form-label text-muted small fw-bold">Assign Bed/Cabin (Suggested: <?= $is_request ? $req_data['suggested_ward'] : 'N/A' ?>)</label>
                <select name="bed_id" class="form-select border-0 border-bottom rounded-0 shadow-none bg-light" required>
                    <option value="">-- Select Available --</option>
                    <?php while($b = $beds->fetch_assoc()): ?>
                        <option value="<?= $b['bed_id'] ?>"> <?= strtoupper($b['ward_name']) ?> - No: <?= $b['bed_number'] ?></option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="d-flex justify-content-end gap-3">
                <button type="reset" class="btn btn-secondary px-5 rounded-0">RESET</button>
                <button type="submit" name="submit_admission" class="btn btn-danger px-5 rounded-0" style="background-color: #b31b1b;">CONFIRM ADMISSION & PAY</button>
            </div>
        </form>
    </div>
</div>
<?php include 'includes/footer.php'; ?>