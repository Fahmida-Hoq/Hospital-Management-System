<?php
session_start();
include 'config/db.php';

if (isset($_POST['submit_report'])) {
    $test_id = (int)$_POST['test_id'];
    $patient_id = (int)$_POST['patient_id'];
    $test_name = mysqli_real_escape_string($conn, $_POST['test_name']);
    $result_text = mysqli_real_escape_string($conn, $_POST['result_text']);
    $amount = (float)$_POST['billing_amount'];

    // 1. Update lab_tests table
    $update = "UPDATE lab_tests SET 
               status = 'completed', 
               result = '$result_text', 
               test_fees = $amount, 
               completed_at = NOW() 
               WHERE test_id = $test_id";
    $conn->query($update);

    // 2. Add to billing table so receptionist/patient can see it
    $bill = "INSERT INTO billing (patient_id, description, amount, status) 
             VALUES ($patient_id, 'Lab Test: $test_name', $amount, 'Unpaid')";
    $conn->query($bill);

    // 3. Redirect back
    header("Location: lab_manage_tests.php?success=1");
    exit();
}