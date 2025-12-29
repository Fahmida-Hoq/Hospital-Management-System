<?php
session_start();
include 'config/db.php';

// Check for login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'patient') {
    header("Location: login.php");
    exit();
}

// Check if patient_id is in session (set during login)
if (!isset($_SESSION['patient_id'])) {
    die("Patient profile not found. Please log in again.");
}

$patient_id = $_SESSION['patient_id'];
$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $doctor_id = $_POST['doctor_id'];
    $date = $_POST['scheduled_time'];
    $time = $_POST['appointment_time'];
    
    
    $consultation_fee = 500.00; 

    if (!empty($doctor_id) && !empty($date) && !empty($time)) {
        
        // 1. Insert into Appointments table
        $sql = "INSERT INTO appointments (patient_id, doctor_id, scheduled_time, appointment_time, status) VALUES (?, ?, ?, ?, 'pending')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiss", $patient_id, $doctor_id, $date, $time);
        
        if ($stmt->execute()) {
            
            // 2. Fetch Doctor Name for the Bill Description
            $doc_name_sql = "SELECT u.full_name FROM doctors d JOIN users u ON d.user_id = u.user_id WHERE d.doctor_id = ?";
            $d_stmt = $conn->prepare($doc_name_sql);
            $d_stmt->bind_param("i", $doctor_id);
            $d_stmt->execute();
            $d_res = $d_stmt->get_result()->fetch_assoc();
            $doctor_name = $d_res['full_name'];

            // 3. Insert into Billing table
            $bill_desc = "Consultation Fee - Dr. " . $doctor_name;
            $bill_sql = "INSERT INTO billing (patient_id, description, amount, status, bill_type) VALUES (?, ?, ?, 'Unpaid', 'Consultation')";
            $b_stmt = $conn->prepare($bill_sql);
            $b_stmt->bind_param("isd", $patient_id, $bill_desc, $consultation_fee);
            $b_stmt->execute();

            $message = "<div class='alert alert-success'>Appointment requested successfully! A fee of " . $consultation_fee . " TK has been added to your bill.</div>";
        } else {
            $message = "<div class='alert alert-danger'>Failed to book appointment: " . $conn->error . "</div>";
        }
    } else {
        $message = "<div class='alert alert-warning'>Please fill out all fields.</div>";
    }
}

// Fetch doctors for dropdown
$doctor_sql = "SELECT d.doctor_id, u.full_name, d.specialization FROM doctors d JOIN users u ON d.user_id = u.user_id ORDER BY u.full_name";
$doctors = $conn->query($doctor_sql);

include 'includes/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-7">
            <div class="card shadow-lg p-4">
                <h2 class="card-title text-center text-primary mb-4">Book New Appointment</h2>
                <?php echo $message; ?>
                <form method="post" action="book_appointment.php">
                    <div class="mb-3">
                        <label for="doctor_id" class="form-label">Select Doctor (Consultation Fee: 500 TK)</label>
                        <select class="form-select" id="doctor_id" name="doctor_id" required>
                            <option value="">Choose a Doctor</option>
                            <?php while($doctor = $doctors->fetch_assoc()): ?>
                                <option value="<?php echo $doctor['doctor_id']; ?>">
                                    <?php echo htmlspecialchars($doctor['full_name'] . ' - ' . $doctor['specialization']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="scheduled_time" class="form-label">Date</label>
                            <input type="date" class="form-control" id="scheduled_time" name="scheduled_time" min="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="appointment_time" class="form-label">Time</label>
                            <input type="time" class="form-control" id="appointment_time" name="appointment_time" required>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100 mt-3">Request Appointment</button>
                    <a href="patient_dashboard.php" class="btn btn-secondary w-100 mt-2">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>