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

if (isset($_POST['book_walkin'])) {
    $doctor_id = (int)$_POST['doctor_id'];
    $date = $_POST['appt_date'];
    $time = $_POST['appt_time'];
    $reason = mysqli_real_escape_string($conn, $_POST['reason']);
    $fee = 500.00;

    $patient_id = 0;

    // --- CASE 1: NEW PATIENT (Registration + Booking) ---
    if ($_POST['patient_type'] == 'new') {
        $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $phone = mysqli_real_escape_string($conn, $_POST['phone']);
        $age = (int)$_POST['age'];
        $gender = $_POST['gender'];
        $address = mysqli_real_escape_string($conn, $_POST['address']);
        
        // FIX: Hash the password so login.php can verify it
        $hashed_password = password_hash('12345', PASSWORD_DEFAULT); 

        // Create User Entry
        $user_sql = "INSERT INTO users (full_name, email, password, role) VALUES ('$full_name', '$email', '$hashed_password', 'patient')";
        if ($conn->query($user_sql)) {
            $user_id = $conn->insert_id;
            // Create Patient Profile Entry
            $patient_sql = "INSERT INTO patients (user_id, name, phone, age, gender, address) VALUES ($user_id, '$full_name', '$phone', $age, '$gender', '$address')";
            $conn->query($patient_sql);
            $patient_id = $conn->insert_id;
        }
    } 
    // --- CASE 2: EXISTING/ADMITTED PATIENT ---
    else {
        $patient_id = (int)$_POST['existing_patient_id'];
    }

    if ($patient_id > 0) {
        // 1. Block Appointment Slot
        $sql = "INSERT INTO appointments (patient_id, doctor_id, scheduled_time, appointment_time, status) 
                VALUES ($patient_id, $doctor_id, '$date', '$time', 'Confirmed')";
        
        if ($conn->query($sql)) {
            // 2. Automate Billing (FIX: Removed the manual "Dr. " string)
            $doc_info = $conn->query("SELECT u.full_name FROM doctors d JOIN users u ON d.user_id = u.user_id WHERE d.doctor_id = $doctor_id")->fetch_assoc();
            $doctor_name = $doc_info['full_name'];
            $bill_desc = "Consultation Fee - " . $doctor_name . " (Reason: $reason)";
            
            $conn->query("INSERT INTO billing (patient_id, description, amount, status, bill_type) 
                          VALUES ($patient_id, '$bill_desc', $fee, 'Unpaid', 'Consultation')");

            $success = "Booking Successful! Patient can now log in with their email and password '12345'.";
        }
    } else {
        $error = "Failed to identify or create patient.";
    }
}

// Fetching lists for the form
$doctors = $conn->query("SELECT d.doctor_id, d.specialization, u.full_name FROM doctors d JOIN users u ON d.user_id = u.user_id");
$existing_patients = $conn->query("SELECT p.patient_id, u.full_name, p.phone FROM patients p JOIN users u ON p.user_id = u.user_id");
$all_appts = $conn->query("SELECT a.*, u_p.full_name as p_name, u_d.full_name as d_name 
                            FROM appointments a 
                            JOIN patients p ON a.patient_id = p.patient_id 
                            JOIN users u_p ON p.user_id = u_p.user_id 
                            JOIN doctors d ON a.doctor_id = d.doctor_id 
                            JOIN users u_d ON d.user_id = u_d.user_id 
                            ORDER BY a.appointment_id DESC");
?>

<div class="container-fluid my-5">
    <div class="row">
        <div class="col-md-4">
            <div class="card shadow border-primary">
                <div class="card-header bg-primary text-white">Walk-in Registration & Booking</div>
                <div class="card-body">
                    <form method="post">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Patient Status</label>
                            <select name="patient_type" id="patient_type" class="form-select bg-light" onchange="togglePatientFields()">
                                <option value="new">New Patient (Register Now)</option>
                                <option value="existing">Existing / Admitted Patient</option>
                            </select>
                        </div>

                        <div id="new_patient_fields">
                            <div class="mb-2">
                                <input type="text" name="full_name" class="form-control" placeholder="Full Name" required>
                            </div>
                            <div class="row mb-2">
                                <div class="col"><input type="email" name="email" class="form-control" placeholder="Email" required></div>
                                <div class="col"><input type="text" name="phone" class="form-control" placeholder="Phone"></div>
                            </div>
                            <div class="row mb-2">
                                <div class="col"><input type="number" name="age" class="form-control" placeholder="Age"></div>
                                <div class="col">
                                    <select name="gender" class="form-select">
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3">
                                <textarea name="address" class="form-control" placeholder="Address" rows="2"></textarea>
                            </div>
                        </div>

                        <div id="existing_patient_fields" style="display:none;">
                            <div class="mb-3">
                                <select name="existing_patient_id" class="form-select">
                                    <option value="">-- Select Admitted Patient --</option>
                                    <?php while($ep = $existing_patients->fetch_assoc()): ?>
                                        <option value="<?= $ep['patient_id'] ?>"><?= $ep['full_name'] ?> (<?= $ep['phone'] ?>)</option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>

                        <hr>
                        <div class="mb-2">
                            <label class="small fw-bold text-muted">Select Doctor</label>
                            <select name="doctor_id" class="form-select" required>
                                <?php mysqli_data_seek($doctors, 0); while($d = $doctors->fetch_assoc()): ?>
                                    <option value="<?= $d['doctor_id'] ?>"><?= $d['full_name'] ?> (<?= $d['specialization'] ?>)</option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="row mb-2">
                            <div class="col"><input type="date" name="appt_date" class="form-control" value="<?= date('Y-m-d') ?>"></div>
                            <div class="col"><input type="time" name="appt_time" class="form-control" required></div>
                        </div>
                        <div class="mb-3">
                            <input type="text" name="reason" class="form-control" placeholder="Reason for visit">
                        </div>
                        <button name="book_walkin" class="btn btn-success w-100">Confirm Registration & Appointment</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <?php if($success) echo "<div class='alert alert-success'>$success</div>"; ?>
            <?php if($error) echo "<div class='alert alert-danger'>$error</div>"; ?>
            <div class="card shadow-sm">
                <div class="card-header bg-dark text-white">Recent Appointments</div>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Patient Name</th>
                                <th>Doctor</th>
                                <th>Time</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $all_appts->fetch_assoc()): ?>
                            <tr>
                                <td><?= $row['p_name'] ?></td>
                                <td><?= $row['d_name'] ?></td> <td><?= $row['scheduled_time'] ?> | <?= $row['appointment_time'] ?></td>
                                <td><span class="badge bg-primary"><?= $row['status'] ?></span></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function togglePatientFields() {
    var type = document.getElementById('patient_type').value;
    document.getElementById('new_patient_fields').style.display = (type === 'new') ? 'block' : 'none';
    document.getElementById('existing_patient_fields').style.display = (type === 'existing') ? 'block' : 'none';
}
</script>

<?php include 'includes/footer.php'; ?>