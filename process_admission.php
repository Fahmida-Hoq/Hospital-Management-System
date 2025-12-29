<?php
session_start();
include 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $doctor = $_POST['doctor_name']; // Chosen from your Dr. list
    $bed_id = $_POST['bed_id'];
    $type = 'Indoor';

    $conn->begin_transaction();
    try {
        // 1. Create Patient Record
        $stmt = $conn->prepare("INSERT INTO patients (name, patient_type, referred_by_doctor, status, admission_date) VALUES (?, ?, ?, 'Admitted', NOW())");
        $stmt->bind_param("sss", $name, $type, $doctor);
        $stmt->execute();
        $patient_id = $conn->insert_id;

        // 2. AUTOMATIC ADMISSION FEE (1000 TK)
        $bill_stmt = $conn->prepare("INSERT INTO billing (patient_id, description, amount, bill_type, status, billing_date) VALUES (?, 'Standard Indoor Admission Fee', 1000.00, 'Admission', 'Unpaid', NOW())");
        $bill_stmt->bind_param("is", $patient_id);
        $bill_stmt->execute();

        // 3. Update Bed Status to Occupied
        $conn->query("UPDATE beds SET status = 'Occupied' WHERE bed_id = $bed_id");

        $conn->commit();
        header("Location: receptionist_admitted_patients.php?success=1");
    } catch (Exception $e) {
        $conn->rollback();
        die("Error: " . $e->getMessage());
    }
}
?>