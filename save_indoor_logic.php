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
        // Table: lab_tests | Columns: patient_id, admission_id, test_name, status
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

// --- HANDLE PRESCRIPTION (FIXED FOR MULTIPLE MEDICINES) ---
if (isset($_POST['add_prescription'])) {
    $adm_id = (int)$_POST['adm_id'];
    $p_id = (int)$_POST['p_id'];
    $doctor_id = (int)$_SESSION['user_id'];
    
    // Arrays received from the dynamic form
    $med_names = $_POST['med_name'];
    $dosages = $_POST['dosage'];
    $freqs = $_POST['freq'];

    $success_count = 0;
    for ($i = 0; $i < count($med_names); $i++) {
        // Skip if medicine name is empty
        if(empty($med_names[$i])) continue;

        $m = $conn->real_escape_string($med_names[$i]);
        $d = $conn->real_escape_string($dosages[$i]);
        $f = $conn->real_escape_string($freqs[$i]);
        
        // This creates the string: "Napa (500mg) - 1+0+1"
        $med_info = $m . " (" . $d . ") - " . $f;

        // Ensure the column name 'prescribed_medicines' matches your database exactly
        $sql = "INSERT INTO prescriptions (patient_id, doctor_id, admission_id, appointment_id, prescribed_medicines, date_prescribed) 
                VALUES ('$p_id', '$doctor_id', '$adm_id', NULL, '$med_info', NOW())";
        
        if ($conn->query($sql)) {
            $success_count++;
        }
    }

    header("Location: manage_indoor_treatment.php?adm_id=$adm_id&patient_id=$p_id&msg=$success_count Medicines Added");
    exit();

    } else {
        die("Prescription Error: " . $conn->error);
    }

?>