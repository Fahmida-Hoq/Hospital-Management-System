<?php
session_start();
include 'config/db.php';

if (isset($_POST['submit_admission'])) {
    // Save all form data into a SESSION so we can use it AFTER payment
    $_SESSION['temp_adm'] = [
        'name' => mysqli_real_escape_string($conn, $_POST['name']),
        'email' => mysqli_real_escape_string($conn, $_POST['email']),
        'phone' => mysqli_real_escape_string($conn, $_POST['phone']),
        'doctor_id' => (int)$_POST['doctor_id'],
        'bed_id' => (int)$_POST['bed_id'],
        'admission_fee' => (float)$_POST['admission_fee'],
        'pay_method' => $_POST['admission_pay_method'],
        'blood_group' => mysqli_real_escape_string($conn, $_POST['blood_group']),
        'guardian_name' => mysqli_real_escape_string($conn, $_POST['guardian_name']),
        'guardian_phone' => mysqli_real_escape_string($conn, $_POST['guardian_phone']),
        'relation' => mysqli_real_escape_string($conn, $_POST['relation']),
        'reason' => mysqli_real_escape_string($conn, $_POST['reason']),
        'address' => mysqli_real_escape_string($conn, $_POST['address']),
        'admission_date' => $_POST['admission_date'],
        'password' => $_POST['generated_password'], // The random password
        'type' => 'NEW_ADMISSION' // To tell the gateway this is an admission
    ];

    // Redirect to Payment Gateway
    header("Location: payment_gateway.php");
    exit();
} else {
    header("Location: admit_patient_form.php");
    exit();
}