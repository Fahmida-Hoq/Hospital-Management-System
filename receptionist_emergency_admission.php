<?php
session_start();
include 'config/db.php';
include 'includes/header.php';

// Fetch real doctors dynamically from your 'users' table
$doctor_query = $conn->query("SELECT full_name FROM users WHERE role = 'doctor'");

// Fetch only available beds
$beds = $conn->query("SELECT * FROM beds WHERE status = 'Available'");
?>

<div class="container my-5">
    <div class="card border-danger shadow-lg">
        <div class="card-header bg-danger text-white p-3">
            <h4 class="mb-0"><i class="fas fa-ambulance me-2"></i> Direct Emergency Admission</h4>
        </div>
        <div class="card-body">
            <form action="process_emergency.php" method="POST">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Patient Full Name</label>
                        <input type="text" name="name" class="form-control" placeholder="Enter patient name" required>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label fw-bold">Age</label>
                        <input type="number" name="age" class="form-control" required>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label fw-bold">Gender</label>
                        <select name="gender" class="form-control" required>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Guardian Name</label>
                        <input type="text" name="g_name" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Guardian Phone</label>
                        <input type="text" name="g_phone" class="form-control" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold text-primary">Assign Attending Doctor</label>
                        <select name="doctor_name" class="form-select border-primary" required>
                            <option value="">-- Choose Doctor --</option>
                            <?php while($doc = $doctor_query->fetch_assoc()): ?>
                                <option value="<?= htmlspecialchars($doc['full_name']) ?>">
                                    <?= htmlspecialchars($doc['full_name']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Blood Group</label>
                        <input type="text" name="blood" class="form-control" placeholder="e.g. O+">
                    </div>

                    <div class="col-md-12 mb-3">
                        <label class="form-label fw-bold">Select Available Bed</label>
                        <select name="bed_id" class="form-control" required>
                            <option value="">-- Choose Ward & Bed --</option>
                            <?php while($b = $beds->fetch_assoc()): ?>
                                <option value="<?= $b['bed_id'] ?>">
                                    <?= $b['ward_name'] ?> - Bed: <?= $b['bed_number'] ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="col-12 mb-3">
                        <label class="form-label fw-bold">Emergency Reason / Symptoms</label>
                        <textarea name="reason" class="form-control" rows="3" placeholder="Describe the medical emergency..." required></textarea>
                    </div>
                </div>

                <div class="alert alert-warning py-2 small">
                    <i class="fas fa-info-circle"></i> <strong>Note:</strong> An Admission Fee of <strong>1000 TK</strong> will be automatically added to the bill.
                </div>

                <hr>
                <div class="d-flex justify-content-between">
                    <a href="receptionist_admitted_patients.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" name="admit_emergency" class="btn btn-danger px-5">Admit Patient Now</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>