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
    $doctor_id = (int)$_POST['doctor_id'];
    $date = $_POST['scheduled_time'];
    
    $time = date("H:i:s", strtotime($_POST['appointment_time'])); 
    $consultation_fee = 500.00; 
    $selected_day = date("l", strtotime($date));

    if (!empty($doctor_id) && !empty($date) && !empty($time)) {
        
        $availability_sql = "SELECT start_time, end_time FROM doctor_schedules 
                             WHERE doctor_id = ? AND day_of_week = ? AND status = 'Active'";
        $stmt_avail = $conn->prepare($availability_sql);
        $stmt_avail->bind_param("is", $doctor_id, $selected_day);
        $stmt_avail->execute();
        $res_avail = $stmt_avail->get_result();

        if ($res_avail->num_rows == 0) {
            $message = "<div class='alert alert-warning fw-bold'>
                            <i class='fas fa-calendar-times'></i> SCHEDULE ERROR: 
                            This doctor is not available on {$selected_day}s. 
                        </div>";
        } else {
            $schedule = $res_avail->fetch_assoc();
            
            if ($time < $schedule['start_time'] || $time > $schedule['end_time']) {
                 $message = "<div class='alert alert-danger fw-bold'>
                                <i class='fas fa-clock'></i> TIME ERROR: 
                                Doctor is available from " . date("h:i A", strtotime($schedule['start_time'])) . " to " . date("h:i A", strtotime($schedule['end_time'])) . "
                            </div>";
            } else {
                
            
                $doc_limit_sql = "SELECT daily_limit FROM doctors WHERE doctor_id = ?";
                $stmt_doc_limit = $conn->prepare($doc_limit_sql);
                $stmt_doc_limit->bind_param("i", $doctor_id);
                $stmt_doc_limit->execute();
                $doc_limit_res = $stmt_doc_limit->get_result()->fetch_assoc();
                
                
                $max_patients = $doc_limit_res['daily_limit'] ?? 30;

                $limit_sql = "SELECT COUNT(*) as current_total FROM appointments 
                              WHERE doctor_id = ? 
                              AND scheduled_time = ? 
                              AND status != 'cancelled'";
                $stmt_limit = $conn->prepare($limit_sql);
                $stmt_limit->bind_param("is", $doctor_id, $date);
                $stmt_limit->execute();
                $limit_res = $stmt_limit->get_result()->fetch_assoc();

                if ($limit_res['current_total'] >= $max_patients) {
                    $message = "<div class='alert alert-warning fw-bold'>
                                    <i class='fas fa-users'></i> DAILY LIMIT REACHED: 
                                    This doctor has reached their limit of {$max_patients} patients for this date.
                                </div>";
                } else {
               

                    $check_sql = "SELECT appointment_id FROM appointments 
                                  WHERE doctor_id = ? 
                                  AND scheduled_time = ? 
                                  AND appointment_time = ? 
                                  AND status != 'cancelled'";
                    
                    $stmt_check = $conn->prepare($check_sql);
                    $stmt_check->bind_param("iss", $doctor_id, $date, $time);
                    $stmt_check->execute();
                    $result_check = $stmt_check->get_result();

                    if ($result_check->num_rows > 0) {
                        $message = "<div class='alert alert-danger fw-bold'>
                                        <i class='fas fa-exclamation-circle'></i> TIME CONFLICT: 
                                        The doctor is already booked for " . date("h:i A", strtotime($time)) . ". 
                                    </div>";
                    } else {
                        $_SESSION['temp_appointment'] = [
                            'doctor_id' => $doctor_id,
                            'scheduled_time' => $date,
                            'appointment_time' => $time,
                            'amount' => $consultation_fee,
                            'type' => 'OUTDOOR_CONSULTATION'
                        ];

                        header("Location: payment_gateway.php");
                        exit();
                    }
                } 
            }
        }
    } else {
        $message = "<div class='alert alert-warning'>Please fill out all fields.</div>";
    }
}


if (isset($_GET['payment_success']) && isset($_SESSION['temp_appointment'])) {
    $data = $_SESSION['temp_appointment'];
    $pay_method = $_GET['method'] ?? 'Online';

    $conn->begin_transaction();
    try {
        
        $sql = "INSERT INTO appointments (patient_id, doctor_id, scheduled_time, appointment_time, status, payment_status) VALUES (?, ?, ?, ?, 'confirmed', 'Paid')";
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            throw new Exception("SQL Prepare Failed: " . $conn->error);
        }

        $stmt->bind_param("iiss", $patient_id, $data['doctor_id'], $data['scheduled_time'], $data['appointment_time']);
        $stmt->execute();

        $doc_name_sql = "SELECT u.full_name FROM doctors d JOIN users u ON d.user_id = u.user_id WHERE d.doctor_id = ?";
        $d_stmt = $conn->prepare($doc_name_sql);
        $d_stmt->bind_param("i", $data['doctor_id']);
        $d_stmt->execute();
        $doctor_name = $d_stmt->get_result()->fetch_assoc()['full_name'];

        $bill_desc = "Consultation Fee (Paid) - " . $doctor_name;
        $bill_sql = "INSERT INTO billing (patient_id, description, amount, status, bill_type, payment_method, billing_date) VALUES (?, ?, ?, 'Paid', 'Outdoor', ?, CURDATE())";
        $b_stmt = $conn->prepare($bill_sql);
        
        if (!$b_stmt) {
            throw new Exception("Billing SQL Failed: " . $conn->error);
        }

        $b_stmt->bind_param("isds", $patient_id, $bill_desc, $data['amount'], $pay_method);
        $b_stmt->execute();

        $conn->commit();
        unset($_SESSION['temp_appointment']);
        $message = "<div class='alert alert-success'>Payment Successful! Appointment confirmed.</div>";
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
                    <?php echo $message; ?>
                </div>
                <form method="post">
                    <div class="mb-4">
                        <label class="form-label fw-bold">Select Doctor</label>
                        <select class="form-select" name="doctor_id" required>
                            <option value="">Choose a Doctor</option>
                            <?php while($doctor = $doctors->fetch_assoc()): ?>
                                <option value="<?php echo $doctor['doctor_id']; ?>"><?php echo htmlspecialchars($doctor['full_name']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Date</label>
                            <input type="date" class="form-control" name="scheduled_time" min="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Time</label>
                            <input type="time" class="form-control" name="appointment_time" required>
                        </div>
                    </div>
                    <button type="submit" name="request_appointment" class="btn btn-primary w-100 py-2 fw-bold">PROCEED TO PAYMENT</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>