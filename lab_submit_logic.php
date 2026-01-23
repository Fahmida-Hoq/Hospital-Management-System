<?php
session_start();
include 'config/db.php';

if (isset($_POST['submit_report'])) {

    $_SESSION['temp_outpatient_data'] = [
        'test_id' => (int)$_POST['test_id'],
        'patient_id' => (int)$_POST['patient_id'],
        'test_name' => mysqli_real_escape_string($conn, $_POST['test_name']),
        'result_text' => mysqli_real_escape_string($conn, $_POST['result_text']),
        'amount' => (float)$_POST['billing_amount'],
        'method' => $_POST['payment_method']
    ];

    header("Location: process_lab_payment.php");
    exit();
}
?>