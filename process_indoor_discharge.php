<?php
session_start();
include 'config/db.php';

// Changed from GET to POST to match the form in generate_bill.php
if (isset($_POST['adm_id'])) {
    $adm_id = (int)$_POST['adm_id'];
    $discharge_date = date('Y-m-d H:i:s');

    // Start transaction to ensure all steps happen together
    $conn->begin_transaction();

    try {
        // 1. Fetch Admission details (Patient ID and Bed ID) before updating
        $data_res = $conn->query("SELECT patient_id, bed_id FROM admissions WHERE admission_id = $adm_id");
        if ($data_res->num_rows == 0) throw new Exception("Admission record not found.");
        
        $row = $data_res->fetch_assoc();
        $p_id = $row['patient_id'];
        $bed_id = $row['bed_id'];

        // STEP 1: Update Admission Record status to 'Discharged'
        $sql_adm = "UPDATE admissions SET status = 'Discharged', discharge_date = '$discharge_date' WHERE admission_id = $adm_id";
        if (!$conn->query($sql_adm)) throw new Exception("Failed to update admission status");

        // STEP 2: Make the Bed Available again
        $sql_bed = "UPDATE beds SET status = 'Available' WHERE bed_id = $bed_id";
        if (!$conn->query($sql_bed)) throw new Exception("Failed to release bed");

        // STEP 3: Ensure any remaining unpaid billing records for this patient are marked paid
        // This is a safety step to clear the ledger for the discharged patient
        $sql_bill = "UPDATE billing SET status = 'paid' WHERE patient_id = $p_id AND status = 'unpaid'";
        $conn->query($sql_bill);

        $conn->commit();
        
        // REDIRECT: To view_indoor_patient.php as requested
        header("Location: view_indoor_patient.php?msg=Patient Discharged successfully");
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        die("Discharge Error: " . $e->getMessage());
    }
} else {
    // If accessed directly without ID, go back to the indoor records
    header("Location: view_indoor_patient.php");
    exit();
}
?>