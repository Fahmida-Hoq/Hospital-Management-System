<?php
// lab_submit_result.php logic
session_start();
include 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $test_id = (int)$_POST['test_id'];
    $result_text = mysqli_real_escape_string($conn, $_POST['result_text']);

    // 1. Get the price and patient info for this test
    $info_query = "SELECT lt.appointment_id, lt.test_name, a.patient_id, m.fee 
                   FROM lab_tests lt 
                   JOIN appointments a ON lt.appointment_id = a.appointment_id 
                   JOIN lab_tests_master m ON lt.test_name = m.test_name 
                   WHERE lt.test_id = $test_id";
    $info = $conn->query($info_query)->fetch_assoc();
    
    $patient_id = $info['patient_id'];
    $amount = $info['fee'];
    $description = "Lab Test: " . $info['test_name'];

    // 2. Update the Lab Test status and result
    $update_sql = "UPDATE lab_tests SET status = 'completed', result = '$result_text' WHERE test_id = $test_id";
    
    if ($conn->query($update_sql)) {
        // 3. Create the Bill automatically
        $bill_sql = "INSERT INTO billing (patient_id, description, amount, status, bill_type) 
                     VALUES ($patient_id, '$description', $amount, 'Unpaid', 'Lab')";
        $conn->query($bill_sql);

        header("Location: lab_manage_tests.php?msg=Completed_and_Billed");
    }
}