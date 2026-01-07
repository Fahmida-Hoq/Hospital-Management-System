<?php
session_start();
include 'config/db.php';

// --- STAGE 1: Redirecting to Payment Gateway ---
if (isset($_POST['submit_admission'])) {
    // Save all form data into a SESSION
    $_SESSION['temp_adm'] = [
        'is_existing' => isset($_POST['is_existing']) ? 1 : 0,
        'patient_id' => isset($_POST['patient_id']) ? (int)$_POST['patient_id'] : 0,
        'request_id' => isset($_POST['request_id']) ? (int)$_POST['request_id'] : 0,
        'name' => mysqli_real_escape_string($conn, $_POST['name']),
        'email' => mysqli_real_escape_string($conn, $_POST['email']),
        'phone' => mysqli_real_escape_string($conn, $_POST['phone']),
        'doctor_id' => (int)$_POST['doctor_id'],
        'bed_id' => (int)$_POST['bed_id'],
        'admission_fee' => (float)$_POST['admission_fee'],
        'pay_method' => $_POST['admission_pay_method'],
        'blood_group' => mysqli_real_escape_string($conn, $_POST['blood_group']),
        'guardian_name' => mysqli_real_escape_string($conn, $_POST['guardian_name']),
        'guardian_phone' => mysqli_real_escape_string($conn, $_POST['guardian_phone']),
        'relation' => mysqli_real_escape_string($conn, $_POST['relation']),
        'reason' => mysqli_real_escape_string($conn, $_POST['reason']),
        'address' => mysqli_real_escape_string($conn, $_POST['address']),
        'admission_date' => $_POST['admission_date'],
        'password' => $_POST['generated_password'], 
        'type' => 'NEW_ADMISSION'
    ];

    header("Location: payment_gateway.php");
    exit();
}

// --- STAGE 2: Final Database Insertion (Triggered from Payment Gateway) ---
if (isset($_POST['confirm_final_payment']) && isset($_SESSION['temp_adm'])) {
    $data = $_SESSION['temp_adm'];
    $hashed_pw = password_hash($data['password'], PASSWORD_DEFAULT);
    $conn->begin_transaction();

    try {
        if ($data['is_existing'] == 1) {
            /** * FIX FOR OUTDOOR PATIENTS:
             * Instead of INSERTing, we UPDATE the existing user to ensure 
             * they get the new credentials provided by the receptionist.
             */
            $p_id = $data['patient_id'];
            
            // 1. Update existing user credentials and ensure role is 'patient'
            $sql_update_user = "UPDATE users u 
                                JOIN patients p ON u.user_id = p.user_id 
                                SET u.email = '{$data['email']}', 
                                    u.password = '$hashed_pw', 
                                    u.role = 'patient' 
                                WHERE p.patient_id = $p_id";
            if (!$conn->query($sql_update_user)) throw new Exception("User Account Update Failed");

            // 2. Update Patient Profile to 'Indoor'
            $sql_update_patient = "UPDATE patients SET 
                                   patient_type = 'Indoor', 
                                   phone = '{$data['phone']}', 
                                   address = '{$data['address']}' 
                                   WHERE patient_id = $p_id";
            if (!$conn->query($sql_update_patient)) throw new Exception("Patient Profile Update Failed");
            
            $patient_id = $p_id;

            // 3. Close the Admission Request
            if ($data['request_id'] > 0) {
                $conn->query("UPDATE admission_requests SET request_status = 'Admitted' WHERE request_id = {$data['request_id']}");
            }

        } else {
            // Logic for BRAND NEW patients (never visited before)
            $sql_user = "INSERT INTO users (full_name, email, password, role) 
                         VALUES ('{$data['name']}', '{$data['email']}', '$hashed_pw', 'patient')";
            if (!$conn->query($sql_user)) throw new Exception("User Creation Failed");
            $user_id = $conn->insert_id;

            $sql_patient = "INSERT INTO patients (user_id, name, email, phone, patient_type, address) 
                            VALUES ($user_id, '{$data['name']}', '{$data['email']}', '{$data['phone']}', 'Indoor', '{$data['address']}')";
            if (!$conn->query($sql_patient)) throw new Exception("Patient Profile Failed");
            $patient_id = $conn->insert_id;
        }

        // 4. Create Admission Record (Same for both New and Existing)
        $sql_admission = "INSERT INTO admissions (
            patient_id, doctor_id, bed_id, admission_fee, 
            blood_group, guardian_name, guardian_phone, 
            admission_date, status
        ) VALUES (
            $patient_id, {$data['doctor_id']}, {$data['bed_id']}, {$data['admission_fee']}, 
            '{$data['blood_group']}', '{$data['guardian_name']}', '{$data['guardian_phone']}', 
            '{$data['admission_date']}', 'admitted'
        )";
        if (!$conn->query($sql_admission)) throw new Exception("Admission Entry Failed");

        // 5. Occupy Bed
        $conn->query("UPDATE beds SET status = 'Occupied' WHERE bed_id = {$data['bed_id']}");

        // 6. Record the Payment
        $pay_method = mysqli_real_escape_string($conn, $_POST['pay_method']);
        $conn->query("INSERT INTO billing (patient_id, description, amount, status, billing_date, payment_method) 
                      VALUES ($patient_id, 'Admission Fee (Paid)', {$data['admission_fee']}, 'paid', '{$data['admission_date']}', '$pay_method')");

        $conn->commit();
        unset($_SESSION['temp_adm']);
        
        header("Location: view_indoor_patients.php?status=admission_success");
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        die("Error processing admission: " . $e->getMessage());
    }
} else {
    header("Location: admit_patient_form.php");
    exit();
}