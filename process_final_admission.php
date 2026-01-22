<?php
session_start();
include 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $req_id = $_POST['request_id'];
    $p_id = $_POST['patient_id'];
    $bed_id = $_POST['bed_id'];
    
    $g_name = $_POST['g_name'];
    $g_phone = $_POST['g_phone'];
    $g_rel = $_POST['g_relation']; 
    $history = $_POST['medical_history'];
    $blood = $_POST['blood_group'];


    $bed_q = $conn->query("SELECT * FROM beds WHERE bed_id = $bed_id");
    $bed_data = $bed_q->fetch_assoc();
    $ward = $bed_data['ward_name'];
    $bed_no = $bed_data['bed_number'];

    $conn->begin_transaction();

    try {
       
        $conn->query("UPDATE beds SET status = 'Occupied' WHERE bed_id = $bed_id");

        $conn->query("UPDATE admission_requests SET request_status = 'Admitted' WHERE request_id = $req_id");

        $stmt = $conn->prepare("UPDATE patients SET 
            patient_type = 'Indoor', 
            status = 'Admitted', 
            guardian_name = ?, 
            guardian_phone = ?, 
            admission_reason = ?, 
            ward = ?, 
            bed = ?, 
            admission_date = NOW() 
            WHERE patient_id = ?");
        
        if (!$stmt) { throw new Exception($conn->error); }

        $stmt->bind_param("sssssi", $g_name, $g_phone, $history, $ward, $bed_no, $p_id);
        $stmt->execute();

        $conn->commit();
    
        echo "<script>alert('Patient Admitted Successfully!'); window.location='receptionist_admitted_patients.php';</script>";
    } catch (Exception $e) {
        $conn->rollback();
        die("Fatal Error: " . $e->getMessage());
    }
}