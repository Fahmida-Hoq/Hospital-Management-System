<?php
session_start();
include 'config/db.php';

// Check if user is logged in (needed for doctor_id)
if(!isset($_SESSION['user_id'])){
    die("Error: Please log in first.");
}

// --- HANDLE LAB TEST ---
if (isset($_POST['order_lab'])) {
    $adm_id = (int)$_POST['adm_id'];
    $p_id = (int)$_POST['p_id'];
    $test_name = !empty($_POST['custom_test']) ? $_POST['custom_test'] : $_POST['test_dropdown'];

    if (!empty($test_name)) {
        // Correct Table: lab_tests | Columns: patient_id, admission_id, appointment_id, test_name, status
        $sql = "INSERT INTO lab_tests (patient_id, admission_id, appointment_id, test_name, status) 
                VALUES ('$p_id', '$adm_id', NULL, '$test_name', 'pending')";
        
        if ($conn->query($sql)) {
            header("Location: manage_indoor_treatment.php?adm_id=$adm_id&patient_id=$p_id&msg=Lab Requested");
            exit();
        } else {
            die("Lab Error: " . $conn->error);
        }
    }
}

// --- HANDLE PRESCRIPTION ---
if (isset($_POST['add_prescription'])) {
    $adm_id = (int)$_POST['adm_id'];
    $p_id = (int)$_POST['p_id'];
    $doctor_id = (int)$_SESSION['user_id']; 
    
    // Combine details into the format your table expects: 'prescribed_medicines'
    $med_info = $conn->real_escape_string($_POST['med_name'] . " (" . $_POST['dosage'] . ") - " . $_POST['freq']);

    // Correct Table: prescriptions | Column: prescribed_medicines
    $sql = "INSERT INTO prescriptions (patient_id, doctor_id, admission_id, appointment_id, prescribed_medicines, date_prescribed) 
            VALUES ('$p_id', '$doctor_id', '$adm_id', NULL, '$med_info', NOW())";
    
    if ($conn->query($sql)) {
        header("Location: manage_indoor_treatment.php?adm_id=$adm_id&patient_id=$p_id&msg=Medicine Added");
        exit();
    } else {
        die("Prescription Error: " . $conn->error);
    }
}
?>