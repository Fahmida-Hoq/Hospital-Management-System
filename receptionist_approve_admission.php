<?php
session_start();
include 'config/db.php';

if ($_SESSION['role'] !== 'receptionist') exit;

$request_id = (int)$_POST['request_id'];
$patient_id = (int)$_POST['patient_id'];

$guardian_name  = $_POST['guardian_name'];
$guardian_phone = $_POST['guardian_phone'];
$address        = $_POST['address'];
$ward           = $_POST['ward'];
$cabin          = $_POST['cabin'];
$bed            = $_POST['bed'];

$conn->begin_transaction();

/* CONVERT OPD → IPD */
$conn->query("
    UPDATE patients SET
        patient_type='Indoor',
        status='Admitted',
        guardian_name='$guardian_name',
        guardian_phone='$guardian_phone',
        address='$address',
        ward='$ward',
        cabin='$cabin',
        bed='$bed',
        admitted_date=NOW()
    WHERE patient_id=$patient_id
");

/* CLOSE REQUEST */
$conn->query("
    UPDATE admission_requests
    SET request_status='Admitted'
    WHERE request_id=$request_id
");

$conn->commit();

header("Location: receptionist_admitted_patients.php");
