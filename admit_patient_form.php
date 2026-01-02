<?php
session_start();
include 'config/db.php';
include 'includes/header.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['receptionist', 'admin'])) {
    header("Location: login.php"); exit();
}

// 1. Fetch Doctors (Joining users to get names as per image_c2ac28.jpg)
$doctors = $conn->query("SELECT u.full_name, d.doctor_id, d.department 
                         FROM doctors d JOIN users u ON d.user_id = u.user_id");

// 2. Fetch Available Beds and Cabins 
$beds = $conn->query("SELECT * FROM beds WHERE status = 'Available' ORDER BY ward_name ASC");
$admission_fee = 500 ; 
?>

<div class="container my-5">
    <div class="card shadow-lg border-0">
        <div class="p-4 text-white" style="background-color: #b31b1b;">
            <div class="d-flex justify-content-between align-items-center">
                <h2 class="mb-0 fw-bold">New Indoor Patient Registration & Admission</h2>
                <i class="fas fa-hospital-user fa-3x opacity-50"></i>
            </div>
        </div>

        <form action="process_indoor_admission.php" method="POST" class="card-body p-5 bg-light">
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
                    <label class="form-label text-muted small fw-bold">Admission Fee (Tk)</label>
                    <input type="number" name="admission_fee" class="form-control border-0 border-bottom rounded-0 shadow-none bg-white fw-bold" value="<?= $admission_fee ?>" required>
                </div>
            </div>

            <h5 class="text-danger border-bottom pb-2 mb-4">New Patient Registration Details</h5>
            <p class="text-muted small">This will create a new account for the patient using their email.</p>

            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <label class="form-label text-muted small fw-bold">Patient Full Name</label>
                    <input type="text" name="name" class="form-control border-0 border-bottom rounded-0 shadow-none bg-light" placeholder="Enter Full Name" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label text-muted small fw-bold">Email Address (For Patient Login)</label>
                    <input type="email" name="email" class="form-control border-0 border-bottom rounded-0 shadow-none bg-light" placeholder="example@mail.com" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label text-muted small fw-bold">Phone Number</label>
                    <input type="text" name="phone" class="form-control border-0 border-bottom rounded-0 shadow-none bg-light" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label text-muted small fw-bold">Blood Group</label>
                    <select name="blood_group" class="form-select border-0 border-bottom rounded-0 shadow-none bg-light">
                        <option value="">Select</option>
                        <option>A+</option><option>B+</option><option>O+</option><option>AB+</option>
                        <option>A-</option><option>B-</option><option>O-</option><option>AB-</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label text-muted small fw-bold">Gender</label>
                    <select name="gender" class="form-select border-0 border-bottom rounded-0 shadow-none bg-light">
                        <option>Male</option><option>Female</option><option>Other</option>
                    </select>
                </div>
            </div>
<div class="col-md-6">
                    <label class="form-label text-muted small fw-bold">Full Address</label>
                    <input type="text" name="address" class="form-control border-0 border-bottom rounded-0 shadow-none bg-light">
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
                    <textarea name="reason" class="form-control border-0 border-bottom rounded-0 shadow-none bg-light" rows="2" required></textarea>
                </div>
            </div>

            <h5 class="text-danger border-bottom pb-2 mb-4">Accommodation (Bed/Cabin)</h5>
            <div class="row g-4">
                <div class="col-md-6">
                    <label class="form-label text-muted small fw-bold">Assign Bed or Cabin</label>
                    <select name="bed_id" class="form-select border-0 border-bottom rounded-0 shadow-none bg-light" required>
                        <option value="">-- Select Available Allocation --</option>
                        <?php if($beds->num_rows > 0): ?>
                            <?php while($b = $beds->fetch_assoc()): ?>
                                <option value="<?= $b['bed_id'] ?>">
                                    <?= strtoupper($b['ward_name']) ?> - <?= ($b['ward_name'] == 'Cabin') ? 'Cabin No' : 'Bed No' ?>: <?= $b['bed_number'] ?>
                                </option>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <option value="">No Beds/Cabins Available</option>
                        <?php endif; ?>
                    </select>
                </div>
                

            <div class="d-flex justify-content-end gap-3 mt-5">
                <button type="reset" class="btn btn-secondary px-5 rounded-0">RESET</button>
                <button type="submit" name="submit_admission" class="btn btn-danger px-5 rounded-0" style="background-color: #b31b1b;">SUBMIT & REGISTER</button>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>