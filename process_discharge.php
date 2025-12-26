<?php
session_start();
include 'config/db.php';

$p_id = $_GET['p_id'];
$bed_no = $_GET['bed_no'];

$conn->begin_transaction();
try {
    // 1. Make the bed available again
    $conn->query("UPDATE beds SET status = 'Available' WHERE bed_number = '$bed_no'");

    // 2. Archive the patient record
    $conn->query("UPDATE patients SET status = 'Discharged', ward = NULL, bed = NULL WHERE patient_id = $p_id");

    $conn->commit();
    echo "<script>alert('Discharged!'); window.location='receptionist_admitted_patients.php';</script>";
} catch (Exception $e) {
    $conn->rollback();
    die("Error: " . $e->getMessage());
}
?>