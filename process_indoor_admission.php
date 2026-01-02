<?php
session_start();
include 'config/db.php';

if (isset($_POST['submit_admission'])) {
    // 1. Collect and Sanitize Inputs
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $doctor_id = (int)$_POST['doctor_id'];
    $bed_id = (int)$_POST['bed_id'];
    $fee = (float)$_POST['admission_fee'];
    $blood_group = mysqli_real_escape_string($conn, $_POST['blood_group']);
    $guardian = mysqli_real_escape_string($conn, $_POST['guardian_name']);
    $g_phone = mysqli_real_escape_string($conn, $_POST['guardian_phone']);
    $relation = mysqli_real_escape_string($conn, $_POST['relation']);
    $reason = mysqli_real_escape_string($conn, $_POST['reason']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $adm_date = $_POST['admission_date'];

    $password = password_hash("patient123", PASSWORD_DEFAULT);

    $conn->begin_transaction();

    try {
        // STEP 1: Create User
        $sql_user = "INSERT INTO users (full_name, email, password, role) 
                     VALUES ('$name', '$email', '$password', 'patient')";
        if (!$conn->query($sql_user)) throw new Exception("User Creation Failed: " . $conn->error);
        $user_id = $conn->insert_id;

        // STEP 2: Create Patient Profile
        $sql_patient = "INSERT INTO patients (user_id, name, email, phone, patient_type, address) 
                        VALUES ($user_id, '$name', '$email', '$phone', 'Indoor', '$address')";
        if (!$conn->query($sql_patient)) throw new Exception("Patient Profile Failed: " . $conn->error);
        $patient_id = $conn->insert_id;

        // STEP 3: Create Admission Record (FIXED COLUMN NAMES)
        $sql_admission = "INSERT INTO admissions (
            patient_id, doctor_id, bed_id, admission_fee, 
            blood_group, guardian_name, guardian_phone, 
            admission_date, status
        ) VALUES (
            $patient_id, $doctor_id, $bed_id, $fee, 
            '$blood_group', '$guardian', '$g_phone', 
            '$adm_date', 'Admitted'
        )";
        
        if (!$conn->query($sql_admission)) {
            // This will show you exactly which column name is wrong in your DB
            throw new Exception("Admission Entry Failed: " . $conn->error);
        }

        // STEP 4: Mark Bed/Cabin as Occupied
        $conn->query("UPDATE beds SET status = 'Occupied' WHERE bed_id = $bed_id");

        // STEP 5: Generate Initial Bill
        $conn->query("INSERT INTO billing (patient_id, description, amount, status, billing_date) 
                      VALUES ($patient_id, 'Admission Fee (Indoor)', $fee, 'unpaid', '$adm_date')");

        $conn->commit();
        header("Location: view_indoor_patients.php?status=success");
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        die("Fatal Error: " . $e->getMessage()); 
    }
} else {
    header("Location: admit_patient_form.php");
    exit();
}