<?php
session_start();
include 'config/db.php';


if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'patient') {
    header("Location: login.php");
    exit();
}

if (!isset($_SESSION['patient_id'])) {
    die("Patient profile not found. Please log in again.");
}

$patient_id = $_SESSION['patient_id'];
$message = '';


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['request_appointment'])) {
    $doctor_id = $_POST['doctor_id'];
    $date = $_POST['scheduled_time'];
    $time = $_POST['appointment_time'];
    $consultation_fee = 500.00; 

    if (!empty($doctor_id) && !empty($date) && !empty($time)) {
  
        $_SESSION['temp_appointment'] = [
            'doctor_id' => $doctor_id,
            'scheduled_time' => $date,
            'appointment_time' => $time,
            'amount' => $consultation_fee,
            'type' => 'OUTDOOR_CONSULTATION'
        ];

        
        header("Location: payment_gateway.php");
        exit();
    } else {
        $message = "<div class='alert alert-warning'>Please fill out all fields.</div>";
    }
}

if (isset($_GET['payment_success']) && isset($_SESSION['temp_appointment'])) {
    $data = $_SESSION['temp_appointment'];
    $pay_method = isset($_GET['method']) ? $_GET['method'] : 'Online';

    $conn->begin_transaction();

    try {
      
        $sql = "INSERT INTO appointments (patient_id, doctor_id, scheduled_time, appointment_time, status) VALUES (?, ?, ?, ?, 'confirmed')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiss", $patient_id, $data['doctor_id'], $data['scheduled_time'], $data['appointment_time']);
        $stmt->execute();

   
        $doc_name_sql = "SELECT u.full_name FROM doctors d JOIN users u ON d.user_id = u.user_id WHERE d.doctor_id = ?";
        $d_stmt = $conn->prepare($doc_name_sql);
        $d_stmt->bind_param("i", $data['doctor_id']);
        $d_stmt->execute();
        $doctor_name = $d_stmt->get_result()->fetch_assoc()['full_name'];

        $bill_desc = "Consultation Fee (Paid) -  " . $doctor_name;
        $bill_sql = "INSERT INTO billing (patient_id, description, amount, status, bill_type, payment_method, billing_date) VALUES (?, ?, ?, 'Paid', 'Outdoor', ?, CURDATE())";
        $b_stmt = $conn->prepare($bill_sql);
        $b_stmt->bind_param("isds", $patient_id, $bill_desc, $data['amount'], $pay_method);
        $b_stmt->execute();

        $conn->commit();
        unset($_SESSION['temp_appointment']);
        $message = "<div class='alert alert-success'>Payment Successful! Appointment confirmed for Outdoor Patient.</div>";
    } catch (Exception $e) {
        $conn->rollback();
        $message = "<div class='alert alert-danger'>Database Error: " . $e->getMessage() . "</div>";
    }
}
$doctor_sql = "SELECT d.doctor_id, u.full_name, d.specialization FROM doctors d JOIN users u ON d.user_id = u.user_id ORDER BY u.full_name";
$doctors = $conn->query($doctor_sql);

include 'includes/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-7">
            <div class="card shadow-lg p-4 border-0">
                <div class="text-center mb-4">
                    <h2 class="text-primary fw-bold">Book Consultation</h2>
                    <p class="text-muted">Outdoor Patient Appointment</p>
                </div>
                
                <?php echo $message; ?>
                
                <form method="post" action="book_appointment.php">
                    <div class="mb-4">
                        <label class="form-label fw-bold">Select Doctor</label>
                        <select class="form-select border-primary" name="doctor_id" required>
                            <option value="">Choose a Doctor</option>
                            <?php while($doctor = $doctors->fetch_assoc()): ?>
                                <option value="<?php echo $doctor['doctor_id']; ?>">
                                    <?php echo htmlspecialchars($doctor['full_name'] . ' (' . $doctor['specialization'] . ')'); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Date</label>
                            <input type="date" class="form-control" name="scheduled_time" min="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Preferred Time</label>
                            <input type="time" class="form-control" name="appointment_time" required>
                        </div>
                    </div>

                    <div class="bg-light p-3 rounded mb-4 text-center border">
                        <span class="text-muted d-block">Consultation Fee</span>
                        <h3 class="text-success mb-0">500 TK</h3>
                    </div>
                    
                    <button type="submit" name="request_appointment" class="btn btn-primary w-100 py-2 fw-bold shadow-sm">
                        PROCEED TO PAYMENT
                    </button>
                    <a href="patient_dashboard.php" class="btn btn-link w-100 mt-2 text-decoration-none text-muted">Go Back</a>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>