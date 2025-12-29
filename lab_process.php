<?php
session_start();
include 'config/db.php';

if (isset($_GET['complete_test'])) {
    $test_id = $_GET['test_id'];

    // 1. Get test details (fees and patient ID)
    $test_data = $conn->query("SELECT * FROM lab_tests WHERE test_id = $test_id")->fetch_assoc();
    $p_id = $test_data['patient_id'];
    $fees = $test_data['test_fees'];
    $t_name = "Lab Test: " . $test_data['test_name'];

    $conn->begin_transaction();

    try {
        // 2. Update Lab Test Status
        $conn->query("UPDATE lab_tests SET status = 'Completed', completed_date = NOW() WHERE test_id = $test_id");

        // 3. AUTOMATIC BILLING: Add the lab fee to the patient's bill
        $bill_stmt = $conn->prepare("INSERT INTO billing (patient_id, description, amount, bill_type, status, billing_date) VALUES (?, ?, ?, 'Lab', 'Unpaid', NOW())");
        $bill_stmt->bind_param("isd", $p_id, $t_name, $fees);
        $bill_stmt->execute();

        $conn->commit();
        header("Location: lab_dashboard.php?success=1");
    } catch (Exception $e) {
        $conn->rollback();
        die("Billing Error: " . $e->getMessage());
    }
}
?>