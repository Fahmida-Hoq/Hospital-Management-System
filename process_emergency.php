<?php
session_start();
include 'config/db.php';

if (isset($_POST['admit_emergency'])) {
    // Form Data
    $name = $_POST['name'];
    $age = $_POST['age'];
    $gender = $_POST['gender'];
    $g_name = $_POST['g_name'];
    $g_phone = $_POST['g_phone'];
    $blood = $_POST['blood'];
    $doctor = $_POST['doctor_name'];
    $bed_id = $_POST['bed_id'];
    $reason = "[EMERGENCY] " . $_POST['reason'];

    // Generate credentials
    $temp_email = strtolower(str_replace(' ', '', $name)) . rand(10, 99) . "@hms.com";
    $temp_pass = "patient123";
    $hash = password_hash($temp_pass, PASSWORD_DEFAULT);

    $conn->begin_transaction();

    try {
        // 1. Get Bed Details
        $bed_res = $conn->query("SELECT * FROM beds WHERE bed_id = $bed_id");
        $bed_data = $bed_res->fetch_assoc();
        $ward = $bed_data['ward_name'];
        $b_no = $bed_data['bed_number'];

        // 2. Create User Account
        $u_stmt = $conn->prepare("INSERT INTO users (full_name, email, password, role) VALUES (?, ?, ?, 'patient')");
        $u_stmt->bind_param("sss", $name, $temp_email, $hash);
        $u_stmt->execute();
        $user_id = $conn->insert_id;

        // 3. Create Patient Record (Including Doctor Name)
        $p_stmt = $conn->prepare("INSERT INTO patients (user_id, name, age, gender, guardian_name, guardian_phone, blood_group, ward, bed, status, patient_type, referred_by_doctor, admission_reason, admission_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Admitted', 'Indoor', ?, ?, NOW())");
        $p_stmt->bind_param("isssssssssss", $user_id, $name, $age, $gender, $g_name, $g_phone, $blood, $ward, $b_no, $doctor, $reason);
        $p_stmt->execute();
        $patient_id = $conn->insert_id;

        // 4. AUTOMATIC BILLING: Admission Fee (1000 TK)
        $bill_stmt = $conn->prepare("INSERT INTO billing (patient_id, description, amount, bill_type, status, billing_date) VALUES (?, 'Emergency Admission Fee', 1000, 'Admission', 'Unpaid', NOW())");
        $bill_stmt->bind_param("i", $patient_id);
        $bill_stmt->execute();

        // 5. Update Bed Status
        $conn->query("UPDATE beds SET status = 'Occupied' WHERE bed_id = $bed_id");

        $conn->commit();
        header("Location: emergency_slip.php?email=$temp_email&pass=$temp_pass&id=$user_id");

    } catch (Exception $e) {
        $conn->rollback();
        die("System Error: " . $e->getMessage());
    }
}