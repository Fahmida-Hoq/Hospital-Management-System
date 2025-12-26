<?php
session_start();
include 'config/db.php';

if (isset($_POST['admit_emergency'])) {
    $name    = $_POST['name'];
    $age     = $_POST['age'];
    $gender  = $_POST['gender'];
    $g_name  = $_POST['g_name'];
    $g_phone = $_POST['g_phone'];
    $blood   = $_POST['blood'];
    $bed_id  = $_POST['bed_id'];
    $reason  = $_POST['reason'];
    
    // Create a temporary email and password for the patient to log in later
    $temp_email = strtolower(str_replace(' ', '', $name)) . rand(10, 99) . "@hms.com";
    $temp_pass = "patient123"; 
    $hash = password_hash($temp_pass, PASSWORD_DEFAULT);

    // Get bed info
    $bed_res = $conn->query("SELECT * FROM beds WHERE bed_id = $bed_id");
    $bed_data = $bed_res->fetch_assoc();
    $ward = $bed_data['ward_name'];
    $b_no = $bed_data['bed_number'];

    $conn->begin_transaction();

    try {
        // 1. Create User Account (So they can use the Patient Dashboard)
        $u_stmt = $conn->prepare("INSERT INTO users (full_name, email, password, role) VALUES (?, ?, ?, 'patient')");
        $u_stmt->bind_param("sss", $name, $temp_email, $hash);
        $u_stmt->execute();
        $user_id = $conn->insert_id;

        // 2. Create Patient Record (Marked as Emergency)
        // Note: Added 'Emergency' to admission_reason or a dedicated column if you have one
        $p_stmt = $conn->prepare("INSERT INTO patients (user_id, name, age, gender, guardian_name, guardian_phone, blood_group, ward, bed, status, patient_type, admission_reason, admission_date) 
                                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Admitted', 'Indoor', ?, NOW())");
        
        $reason_tagged = "[EMERGENCY] " . $reason;
        $p_stmt->bind_param("isssssssss", $user_id, $name, $age, $gender, $g_name, $g_phone, $blood, $ward, $b_no, $reason_tagged);
        $p_stmt->execute();

        // 3. Mark Bed as Occupied
        $conn->query("UPDATE beds SET status = 'Occupied' WHERE bed_id = $bed_id");

        $conn->commit();
        
        // Show the temporary credentials so receptionist can tell the guardian
        echo "<script>
                alert('Success! Patient Admitted.\\nLogin Email: $temp_email\\nPass: $temp_pass'); 
                window.location='receptionist_admitted_patients.php';
              </script>";

    } catch (Exception $e) {
        $conn->rollback();
        die("Error: " . $e->getMessage());
    }
}