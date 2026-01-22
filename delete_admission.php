<?php
session_start();
include 'config/db.php';

if (isset($_GET['adm_id']) && isset($_GET['bed_id'])) {
    $adm_id = $_GET['adm_id'];
    $bed_id = $_GET['bed_id'];

    $conn->begin_transaction();

    try {
        
        $conn->query("DELETE FROM admissions WHERE admission_id = '$adm_id'");

        $conn->query("UPDATE beds SET status = 'Available' WHERE bed_id = '$bed_id'");

        $conn->commit();
        header("Location: admin_patient_records.php?msg=Deleted Successfully");
        exit(); 
    } catch (Exception $e) {
        $conn->rollback();
        echo "Error deleting record: " . $e->getMessage();
    }
}
?>