<?php
session_start();
include 'config/db.php';
include 'includes/header.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'receptionist') {
    header("Location: login.php");
    exit();
}

$success = "";
$error = "";

/**
 * LOGIC: HANDLING THE FORM SUBMISSION
 */
if (isset($_POST['book_walkin'])) {
    $doctor_id = (int)$_POST['doctor_id'];
    $date = $_POST['appt_date'];
    $time = $_POST['appt_time'];
    $reason = mysqli_real_escape_string($conn, $_POST['reason']);
    $fee = 500.00; 
    $patient_id = 0;

    // CASE 1: NEW PATIENT REGISTRATION
    if (!empty($_POST['full_name']) && !empty($_POST['email'])) {
        $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $phone = mysqli_real_escape_string($conn, $_POST['phone']);
        $age = (int)$_POST['age'];
        $gender = $_POST['gender'];
        $address = mysqli_real_escape_string($conn, $_POST['address']);
        
        // SET FIXED PASSWORD AS REQUESTED
        $fixed_password = "123456";
        $hashed_password = password_hash($fixed_password, PASSWORD_DEFAULT); 

        $user_sql = "INSERT INTO users (full_name, email, password, role) VALUES ('$full_name', '$email', '$hashed_password', 'patient')";
        if ($conn->query($user_sql)) {
            $user_id = $conn->insert_id;
            $patient_sql = "INSERT INTO patients (user_id, name, phone, age, gender, address) VALUES ($user_id, '$full_name', '$phone', $age, '$gender', '$address')";
            $conn->query($patient_sql);
            $patient_id = $conn->insert_id;
            
            // Notify that account is created with the fixed password
            $_SESSION['new_patient_msg'] = "Account created successfully! Default Password: 123456";
        }
    } 
    // CASE 2: EXISTING PATIENT
    else if (!empty($_POST['existing_patient_id'])) {
        $patient_id = (int)$_POST['existing_patient_id'];
    }

    // REDIRECT BOTH TO PAYMENT GATEWAY
    if ($patient_id > 0) {
        $_SESSION['temp_appointment'] = [
            'patient_id' => $patient_id,
            'doctor_id' => $doctor_id,
            'amount' => $fee,
            'reason' => $reason,
            'date' => $date,
            'time' => $time
        ];
        
        header("Location: collect_payment.php");
        exit();
    } else {
        $error = "Please either fill in New Patient details OR select an Existing Patient.";
    }
}

// Fetch Doctors and Patients for the dropdowns
$doctors = $conn->query("SELECT d.doctor_id, d.specialization, u.full_name FROM doctors d JOIN users u ON d.user_id = u.user_id");
$existing_patients = $conn->query("SELECT p.patient_id, u.full_name, p.phone FROM patients p JOIN users u ON p.user_id = u.user_id");
$all_appts = $conn->query("SELECT a.*, u_p.full_name as p_name, u_d.full_name as d_name FROM appointments a JOIN patients p ON a.patient_id = p.patient_id JOIN users u_p ON p.user_id = u_p.user_id JOIN doctors d ON a.doctor_id = d.doctor_id JOIN users u_d ON d.user_id = u_d.user_id ORDER BY a.appointment_id DESC LIMIT 10");
?>

<div class="container my-5">
    <div class="row">
        <div class="col-md-5">
            <div class="card shadow border-0">
                <div class="card-header bg-primary text-white py-3">
                    <h5 class="mb-0">Create Appointment & Collect Fee</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        
                        <div class="alert alert-info py-2">
                            <small><strong>Note:</strong> A Consultation Fee of <b>BDT 500.00</b> will be charged.</small>
                        </div>

                        <h6>Option A: New Patient Registration</h6>
                        <input type="text" name="full_name" class="form-control mb-2" placeholder="Full Name">
                        <input type="email" name="email" class="form-control mb-2" placeholder="Email">
                        <input type="text" name="phone" class="form-control mb-2" placeholder="Phone">
                        <div class="row mb-3">
                            <div class="col"><input type="number" name="age" class="form-control" placeholder="Age"></div>
                            <div class="col">
                                <select name="gender" class="form-select">
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                </select>
                            </div>
                        </div>

                        <hr>
                        <h6>Option B: Select Existing Patient</h6>
                        <select name="existing_patient_id" class="form-select mb-3">
                            <option value="">-- Choose Patient --</option>
                            <?php mysqli_data_seek($existing_patients, 0); while($ep = $existing_patients->fetch_assoc()): ?>
                                <option value="<?= $ep['patient_id'] ?>"><?= $ep['full_name'] ?> (<?= $ep['phone'] ?>)</option>
                            <?php endwhile; ?>
                        </select>

                        <hr>
                        <h6 class="text-danger">Appointment Details</h6>
                        <select name="doctor_id" class="form-select mb-2" required>
                            <option value="">-- Select Doctor --</option>
                            <?php mysqli_data_seek($doctors, 0); while($d = $doctors->fetch_assoc()): ?>
                                <option value="<?= $d['doctor_id'] ?>"><?= $d['full_name'] ?> (<?= $d['specialization'] ?>)</option>
                            <?php endwhile; ?>
                        </select>
                        <div class="row mb-2">
                            <div class="col"><input type="date" name="appt_date" class="form-control" value="<?= date('Y-m-d') ?>"></div>
                            <div class="col"><input type="time" name="appt_time" class="form-control" required></div>
                        </div>
                        <input type="text" name="reason" class="form-control mb-3" placeholder="Reason (e.g. Regular Checkup)" required>

                        <button type="submit" name="book_walkin" class="btn btn-success btn-lg w-100 fw-bold">PROCEED TO PAYMENT</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-7">
            <?php if(isset($_SESSION['new_patient_msg'])): ?>
                <div class="alert alert-success shadow-sm">
                    <i class="fas fa-user-plus me-2"></i> <?= $_SESSION['new_patient_msg'] ?>
                    <?php unset($_SESSION['new_patient_msg']); ?>
                </div>
            <?php endif; ?>

            <div class="card shadow-sm border-0">
                <div class="card-header bg-dark text-white">Recent Appointments</div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Patient</th>
                                <th>Doctor</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $all_appts->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['p_name']) ?></td>
                                <td><?= htmlspecialchars($row['d_name']) ?></td>
                                <td><?= date('d M', strtotime($row['scheduled_time'])) ?> | <?= $row['appointment_time'] ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>