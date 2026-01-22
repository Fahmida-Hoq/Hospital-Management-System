<?php
session_start();
include 'config/db.php';

if (isset($_POST['adm_id'])) {
    $adm_id = (int)$_POST['adm_id'];
    $discharge_date = date('Y-m-d H:i:s');

    $conn->begin_transaction();

    try {
     
        $data_res = $conn->query("SELECT patient_id, bed_id FROM admissions WHERE admission_id = $adm_id");
        if ($data_res->num_rows == 0) throw new Exception("Admission record not found.");
        
        $row = $data_res->fetch_assoc();
        $p_id = $row['patient_id'];
        $bed_id = $row['bed_id'];

        $sql_adm = "UPDATE admissions SET status = 'Discharged', discharge_date = '$discharge_date' WHERE admission_id = $adm_id";
        if (!$conn->query($sql_adm)) throw new Exception("Failed to update admission status");

        $sql_bed = "UPDATE beds SET status = 'Available' WHERE bed_id = $bed_id";
        if (!$conn->query($sql_bed)) throw new Exception("Failed to release bed");

        $sql_bill = "UPDATE billing SET status = 'paid' WHERE patient_id = $p_id AND status = 'unpaid'";
        $conn->query($sql_bill);

        $conn->commit();
        
        
        header("Location: view_indoor_patient.php?msg=Patient Discharged successfully");
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        die("Discharge Error: " . $e->getMessage());
    }
} else {
    
    header("Location: view_indoor_patient.php");
    exit();
}
?>