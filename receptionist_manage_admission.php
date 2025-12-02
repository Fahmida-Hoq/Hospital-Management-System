<?php
session_start();
include 'config/db.php';
include 'includes/header.php';

// Access Control - Only Receptionists can access this page
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'receptionist') {
    header("Location: login.php");
    exit();
}

$message = '';

// --- 1. Handle Admission Action (POST) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'admit') {
    $request_id = (int)$_POST['request_id'];
    $patient_id = (int)$_POST['patient_id'];
    $ward_name = $_POST['ward_name'];
    $room_number = $_POST['room_number'];
    $bed_number = $_POST['bed_number'];
    
    // Safety check for empty fields
    if (empty($ward_name) || empty($room_number) || empty($bed_number)) {
        $message = "<div class='alert alert-danger'>Admission failed. Please fill in Ward, Room, and Bed details.</div>";
    } else {
        // Start transaction for multi-table updates
        global $conn;
        $conn->begin_transaction();
        
        try {
            // A. Insert into admitted_patients table (Logistical details)
            $admitted_sql = "INSERT INTO admitted_patients 
                             (request_id, patient_id, ward_name, room_number, bed_number) 
                             VALUES (?, ?, ?, ?, ?)";
            query($admitted_sql, [$request_id, $patient_id, $ward_name, $room_number, $bed_number], "iisss");

            // B. Update admission_requests status (Set to Admitted)
            $request_update_sql = "UPDATE admission_requests SET request_status = 'Admitted' WHERE request_id = ?";
            query($request_update_sql, [$request_id], "i");
            
            // C. Update patients status (Change status to 'Admitted')
            $patient_update_sql = "UPDATE patients SET status = 'Admitted' WHERE patient_id = ?";
            query($patient_update_sql, [$patient_id], "i");
            
            $conn->commit();
            $message = "<div class='alert alert-success'>Patient successfully admitted and assigned to **Bed {$bed_number}** in **{$ward_name}**.</div>";

        } catch (Exception $e) {
            $conn->rollback();
            $message = "<div class='alert alert-danger'>Admission failed. A critical database error occurred: " . $e->getMessage() . "</div>"; 
        }
    }
}

// --- 2. Fetch Pending Admission Requests (GET) ---
$sql = "SELECT 
            ar.request_id, 
            ar.request_date,
            ar.suggested_ward,       /* Accommodation Type (Ward/Cabin) */
            ar.suggested_department, /* Doctor's Suggested Department */
            ar.doctor_reason,        /* Doctor's reason for admission */
            p.patient_id, 
            u_p.full_name AS patient_name,
            u_p.phone,
            u_d.full_name AS doctor_name
        FROM 
            admission_requests ar
        JOIN 
            patients p ON ar.patient_id = p.patient_id
        JOIN 
            users u_p ON p.user_id = u_p.user_id
        JOIN 
            doctors d ON ar.doctor_id = d.doctor_id
        JOIN
            users u_d ON d.user_id = u_d.user_id
        WHERE 
            ar.request_status = 'Pending Reception'
        ORDER BY 
            ar.request_date ASC";

$requests_result = query($sql)->get_result();
$num_pending = $requests_result->num_rows;
?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
        <h2 class="text-info">🏥 Admission Management (Pending)</h2>
        <a href="receptionist_dashboard.php" class="btn btn-secondary"> Dashboard</a>
    </div>

    <?php echo $message; ?>

    <p class="lead">
        You have **<?php echo $num_pending; ?>** admission requests awaiting bed assignment.
    </p>

    <?php if ($num_pending > 0): ?>
    <div class="table-responsive">
        <table class="table table-bordered table-striped table-hover shadow-sm">
            <thead class="bg-info text-white">
                <tr>
                    <th>Req. ID</th>
                    <th>Patient Name</th>
                    <th>Accommodation Type</th>
                    <th>Suggested Dept.</th>
                    <th>Doctor</th>
                    <th>Date Requested</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while($req = $requests_result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $req['request_id']; ?></td>
                    <td><?php echo htmlspecialchars($req['patient_name']); ?></td>
                    <td><span class="badge bg-primary"><?php echo htmlspecialchars($req['suggested_ward']); ?></span></td>
                    <td><?php echo htmlspecialchars($req['suggested_department']); ?></td>
                    <td>Dr. <?php echo htmlspecialchars($req['doctor_name']); ?></td>
                    <td><?php echo date('M d, Y h:i A', strtotime($req['request_date'])); ?></td>
                    <td>
                        <button type="button" class="btn btn-sm btn-success" 
                                data-bs-toggle="modal" 
                                data-bs-target="#admitModal<?php echo $req['request_id']; ?>">
                            Admit Patient
                        </button>
                    </td>
                </tr>
                
                <div class="modal fade" id="admitModal<?php echo $req['request_id']; ?>" tabindex="-1" aria-labelledby="admitModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form method="POST" action="receptionist_manage_admissions.php">
                                <div class="modal-header bg-success text-white">
                                    <h5 class="modal-title" id="admitModalLabel">Finalize Admission: <?php echo htmlspecialchars($req['patient_name']); ?></h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <input type="hidden" name="action" value="admit">
                                    <input type="hidden" name="request_id" value="<?php echo $req['request_id']; ?>">
                                    <input type="hidden" name="patient_id" value="<?php echo $req['patient_id']; ?>">
                                    
                                    <p><strong>Accommodation Type Requested:</strong> <span class="badge bg-primary"><?php echo htmlspecialchars($req['suggested_ward']); ?></span></p>
                                    <p><strong>Suggested Department:</strong> <?php echo htmlspecialchars($req['suggested_department']); ?></p>
                                    <div class="alert alert-info">
                                        <strong>Doctor's Reason:</strong> <?php echo htmlspecialchars($req['doctor_reason']); ?>
                                    </div>

                                    <hr>
                                    
                                    <h5>Final Bed Assignment</h5>
                                    <div class="mb-3">
                                        <label for="ward_name_<?php echo $req['request_id']; ?>" class="form-label">Hospital Ward / Unit</label>
                                        <select class="form-select" id="ward_name_<?php echo $req['request_id']; ?>" name="ward_name" required>
                                            <option value="">Select Final Ward/Unit</option>
                                            <option value="Ward A - General">Ward A - General</option>
                                            <option value="Ward B - Private">Ward B - Private</option>
                                            <option value="ICU Unit 1">ICU Unit 1</option>
                                            <option value="Maternity Unit">Maternity Unit</option>
                                        </select>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="room_number_<?php echo $req['request_id']; ?>" class="form-label">Room Number</label>
                                            <input type="text" class="form-control" name="room_number" placeholder="e.g., A101" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="bed_number_<?php echo $req['request_id']; ?>" class="form-label">Bed Number</label>
                                            <input type="text" class="form-control" name="bed_number" placeholder="e.g., 03" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-success">Confirm Admission</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
        <div class="alert alert-success">You have no pending admission requests at this time.</div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>