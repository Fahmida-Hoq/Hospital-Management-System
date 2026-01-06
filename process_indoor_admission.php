<?php
session_start();
include 'config/db.php';

// --- STAGE 1: Redirecting to Payment Gateway ---
if (isset($_POST['submit_admission'])) {
    // Save all form data into a SESSION
    $_SESSION['temp_adm'] = [
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
    
    // Hash the auto-generated password
    $hashed_pw = password_hash($data['password'], PASSWORD_DEFAULT);

    $conn->begin_transaction();

    try {
        // 1. Create User Account for Patient
        $sql_user = "INSERT INTO users (full_name, email, password, role) 
                     VALUES ('{$data['name']}', '{$data['email']}', '$hashed_pw', 'patient')";
        if (!$conn->query($sql_user)) throw new Exception("User Creation Failed");
        $user_id = $conn->insert_id;

        // 2. Create Patient Profile
        $sql_patient = "INSERT INTO patients (user_id, name, email, phone, patient_type, address) 
                        VALUES ($user_id, '{$data['name']}', '{$data['email']}', '{$data['phone']}', 'Indoor', '{$data['address']}')";
        if (!$conn->query($sql_patient)) throw new Exception("Patient Profile Failed");
        $patient_id = $conn->insert_id;

        // 3. Create Admission Record
        $sql_admission = "INSERT INTO admissions (
            patient_id, doctor_id, bed_id, admission_fee, 
            blood_group, guardian_name, guardian_phone, 
            admission_date, status
        ) VALUES (
            $patient_id, {$data['doctor_id']}, {$data['bed_id']}, {$data['admission_fee']}, 
            '{$data['blood_group']}', '{$data['guardian_name']}', '{$data['guardian_phone']}', 
            '{$data['admission_date']}', 'Admitted'
        )";
        if (!$conn->query($sql_admission)) throw new Exception("Admission Entry Failed");

        // 4. Occupy Bed
        $conn->query("UPDATE beds SET status = 'Occupied' WHERE bed_id = {$data['bed_id']}");

        // 5. Record the Payment in Billing table
        $pay_method = $_POST['pay_method'];
        $conn->query("INSERT INTO billing (patient_id, description, amount, status, billing_date, payment_method) 
                      VALUES ($patient_id, 'Admission Fee (Paid)', {$data['admission_fee']}, 'paid', '{$data['admission_date']}', '$pay_method')");

        $conn->commit();
        
        // Clear the temporary session
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