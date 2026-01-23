<?php
session_start();
include 'config/db.php';

if (isset($_POST['submit_report'])) {
    $test_id = (int)$_POST['test_id'];
    $patient_id = (int)$_POST['patient_id']; // Ensure this is sent from your modal form
    $test_name = $conn->real_escape_string($_POST['test_name']);
    $findings = $conn->real_escape_string($_POST['result_text']);
    $fees = (float)$_POST['billing_amount'];
    $method = $conn->real_escape_string($_POST['payment_method']);

    // 1. Update lab_tests table with findings and mark as paid
    $sql = "UPDATE lab_tests 
            SET result = '$findings', 
                test_fees = '$fees', 
                status = 'completed',
                payment_status = 'paid',
                payment_method = '$method',
                completed_at = NOW()
            WHERE test_id = $test_id";

    if ($conn->query($sql)) {
        // 2. Insert into billing table as 'Paid' 
        // This ensures 'generate_bill.php' sees this as already settled
        $bill_desc = "Lab Test: $test_name ($method)";
        $bill_sql = "INSERT INTO billing (patient_id, description, amount, status, billing_date) 
                     VALUES ($patient_id, '$bill_desc', $fees, 'Paid', NOW())";
        $conn->query($bill_sql);

        // 3. Redirect to the receipt page with a PRINT button (No JS required for redirect)
        header("Location: print_lab_receipt.php?test_id=$test_id&method=$method");
        exit();
    } else {
        die("Update Error: " . $conn->error);
    }
}
?>