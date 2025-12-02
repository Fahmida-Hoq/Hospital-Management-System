<?php
session_start();
include 'config/db.php';
include 'includes/header.php';

// Access Control
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'doctor') {
    header("Location: login.php");
    exit();
}

$message = '';
$patient_id = isset($_GET['patient_id']) ? (int)$_GET['patient_id'] : 0;
$doctor_user_id = (int)$_SESSION['user_id'];

// fetch doctor_id
$doctor_row = query("SELECT doctor_id FROM doctors WHERE user_id = ?", [$doctor_user_id], "i")->get_result()->fetch_assoc();
$doctor_id = isset($doctor_row['doctor_id']) ? (int)$doctor_row['doctor_id'] : 0;

if ($patient_id <= 0) {
    $message = "<div class='alert alert-danger'>Invalid Patient ID.</div>";
}

// Handle admission suggestion POST
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action'] === 'suggest_admission') {
    $suggested_ward_type = trim($_POST['suggested_ward_type'] ?? '');
    $suggested_department = trim($_POST['suggested_department'] ?? '');
    $admission_reason_doc = trim($_POST['admission_reason_doc'] ?? '');

    // Basic validation
    if ($patient_id <= 0 || $doctor_id <= 0) {
        $message = "<div class='alert alert-danger'>Invalid operation.</div>";
    } elseif ($suggested_ward_type === '' || $admission_reason_doc === '' || $suggested_department === '') {
        $message = "<div class='alert alert-warning'>Please complete all fields before submitting.</div>";
    } else {
        // Check for existing pending request
        $check_sql = "SELECT request_id FROM admission_requests WHERE patient_id = ? AND request_status = 'Pending Reception'";
        $check_stmt = query($check_sql, [$patient_id], "i");
        $has_pending = ($check_stmt->get_result()->num_rows > 0);

        if ($has_pending) {
            $message = "<div class='alert alert-warning'>An admission request for this patient is already pending reception review.</div>";
        } else {
            $insert_sql = "INSERT INTO admission_requests (patient_id, doctor_id, suggested_ward, suggested_department, doctor_reason, request_status)
                           VALUES (?, ?, ?, ?, ?, 'Pending Reception')";
            $insert_stmt = query($insert_sql, [$patient_id, $doctor_id, $suggested_ward_type, $suggested_department, $admission_reason_doc], "iisss");

            if ($insert_stmt && $insert_stmt->affected_rows > 0) {
                // Optionally update the patients table to record suggested fields
                query("UPDATE patients SET suggested_ward = ?, suggested_department = ?, admission_reason = ? WHERE patient_id = ?", [$suggested_ward_type, $suggested_department, $admission_reason_doc, $patient_id], "sssi");

                $message = "<div class='alert alert-success'>Admission suggestion submitted successfully to Reception for review.</div>";
            } else {
                $message = "<div class='alert alert-danger'>Failed to submit admission suggestion. Database error.</div>";
            }
        }
    }
}

// Fetch patient details
$patient_sql = "SELECT u.full_name, u.email, u.phone AS user_phone, p.age, p.gender, p.address, p.status AS patient_status, p.room, p.bed
                FROM patients p
                JOIN users u ON p.user_id = u.user_id
                WHERE p.patient_id = ?";
$patient_stmt = query($patient_sql, [$patient_id], "i");
$patient = $patient_stmt->get_result()->fetch_assoc();

// Fetch latest admission request for display
$request_row = query("SELECT * FROM admission_requests WHERE patient_id = ? ORDER BY request_date DESC LIMIT 1", [$patient_id], "i")->get_result()->fetch_assoc();
$request_status = $request_row['request_status'] ?? 'None';

?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
        <h2 class="text-success">🩺 Patient Chart: <?php echo htmlspecialchars($patient['full_name'] ?? 'N/A'); ?></h2>
        <a href="doctor_dashboard.php" class="btn btn-secondary"> Back to Dashboard</a>
    </div>

    <?php echo $message; ?>

    <?php if ($patient): ?>
    <div class="row">
        <div class="col-md-7">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-success text-white">
                    Patient Information
                </div>
                <div class="card-body">
                    <p><strong>Status:</strong> <span class="badge bg-<?php 
                        $status_class = match($patient['patient_status'] ?? 'Pending') {
                            'Admitted' => 'danger',
                            'Outdoor' => 'primary',
                            'Pending' => 'secondary',
                            default => 'secondary',
                        };
                        echo $status_class;
                    ?>"><?php echo htmlspecialchars($patient['patient_status'] ?? 'Pending'); ?></span></p>

                    <p><strong>Age:</strong> <?php echo htmlspecialchars($patient['age']); ?></p>
                    <p><strong>Gender:</strong> <?php echo htmlspecialchars($patient['gender']); ?></p>
                    <p><strong>Contact:</strong> <?php echo htmlspecialchars($patient['user_phone']); ?> (<?php echo htmlspecialchars($patient['email']); ?>)</p>
                    <p><strong>Address:</strong> <?php echo htmlspecialchars($patient['address']); ?></p>

                    <?php if (!empty($patient['room']) || !empty($patient['bed'])): ?>
                        <p><strong>Room/Bed:</strong> <?php echo htmlspecialchars($patient['room'] ?? ''); ?> / <?php echo htmlspecialchars($patient['bed'] ?? ''); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-md-5">
            <div class="card shadow-lg bg-light">
                <div class="card-header bg-dark text-white">Admission & Treatment Actions</div>
                <div class="card-body">
                    <?php if (($patient['patient_status'] ?? '') === 'Admitted'): ?>
                        <div class="alert alert-danger text-center">This patient is currently <strong>ADMITTED</strong>.</div>

                    <?php elseif ($request_status === 'Pending Reception'): ?>
                        <div class="alert alert-warning text-center">Admission request is <strong>PENDING RECEPTION</strong> review.</div>
                        <p class="small text-muted">You can view the request details in Reception's admission queue.</p>

                    <?php else: ?>
                        <h5>Suggest Patient Admission</h5>
                        <form method="POST" action="doctor_view_patient.php?patient_id=<?php echo $patient_id; ?>">
                            <input type="hidden" name="action" value="suggest_admission">

                            <div class="mb-3">
                                <label for="admission_reason_doc" class="form-label">Medical Reason for Admission</label>
                                <textarea class="form-control" id="admission_reason_doc" name="admission_reason_doc" rows="2" required></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="suggested_department" class="form-label">Suggested Medical Department</label>
                                <select class="form-select" id="suggested_department" name="suggested_department" required>
                                    <option value="">Select Department</option>
                                    <option value="Cardiology">Cardiology</option>
                                    <option value="Neurology">Neurology</option>
                                    <option value="Pathology">Pathology</option>
                                    <option value="General Medicine">General Medicine</option>
                                    <option value="Surgery">Surgery</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="suggested_ward_type" class="form-label">Ward / Cabin Type</label>
                                <select class="form-select" id="suggested_ward_type" name="suggested_ward_type" required>
                                    <option value="">Select Accommodation Type</option>
                                    <option value="General Ward">General Ward</option>
                                    <option value="Semi-Private Cabin">Semi-Private Cabin</option>
                                    <option value="Private Cabin">Private Cabin</option>
                                    <option value="ICU">ICU</option>
                                </select>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-bed me-2"></i> Submit Admission Request
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($request_row): ?>
                <div class="card mt-3">
                    <div class="card-header">Latest Admission Request</div>
                    <div class="card-body">
                        <p><strong>Status:</strong> <?php echo htmlspecialchars($request_row['request_status']); ?></p>
                        <p><strong>Suggested Department:</strong> <?php echo htmlspecialchars($request_row['suggested_department']); ?></p>
                        <p><strong>Suggested Ward:</strong> <?php echo htmlspecialchars($request_row['suggested_ward']); ?></p>
                        <p><strong>Doctor Reason:</strong> <?php echo nl2br(htmlspecialchars($request_row['doctor_reason'])); ?></p>
                        <p class="small text-muted">Requested on: <?php echo date('Y-m-d H:i', strtotime($request_row['request_date'])); ?></p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php else: ?>
        <div class="alert alert-warning">Patient record not found.</div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
