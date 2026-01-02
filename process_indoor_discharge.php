<?php
session_start();
include 'config/db.php';

if (isset($_GET['adm_id']) && isset($_GET['bed_id'])) {
    $adm_id = (int)$_GET['adm_id'];
    $bed_id = (int)$_GET['bed_id'];
    $total_amount = (float)$_GET['total'];
    $discharge_date = date('Y-m-d H:i:s');

    // Start transaction to ensure all 3 steps happen together
    $conn->begin_transaction();

    try {
        // STEP 1: Update Admission Record
        $sql_adm = "UPDATE admissions SET status = 'Discharged', discharge_date = '$discharge_date' WHERE admission_id = $adm_id";
        if (!$conn->query($sql_adm)) throw new Exception("Failed to update admission status");

        // STEP 2: Make the Bed Available again
        $sql_bed = "UPDATE beds SET status = 'Available' WHERE bed_id = $bed_id";
        if (!$conn->query($sql_bed)) throw new Exception("Failed to release bed");

        // STEP 3: Update Billing status to 'paid'
        // We look for the patient_id associated with this admission first
        $patient_res = $conn->query("SELECT patient_id FROM admissions WHERE admission_id = $adm_id");
        $patient_data = $patient_res->fetch_assoc();
        $p_id = $patient_data['patient_id'];

        $sql_bill = "UPDATE billing SET status = 'paid', amount = $total_amount WHERE patient_id = $p_id AND status = 'unpaid'";
        $conn->query($sql_bill);

        $conn->commit();
        header("Location: view_indoor_patients.php?msg=Discharged successfully");
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        die("Discharge Error: " . $e->getMessage());
    }
} else {
    header("Location: view_indoor_patients.php");
    exit();
}