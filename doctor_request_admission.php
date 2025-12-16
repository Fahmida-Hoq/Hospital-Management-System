<?php
session_start();
include 'config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    exit("Unauthorized");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patient_id = (int)$_POST['patient_id'];
    $ward = trim($_POST['suggested_ward']);
    $dept = trim($_POST['suggested_department']);
    $reason = trim($_POST['doctor_reason']);

    $stmt = $conn->prepare("
        INSERT INTO admission_requests
        (patient_id, doctor_id, suggested_ward, suggested_department, doctor_reason)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->bind_param(
        "iisss",
        $patient_id,
        $_SESSION['user_id'],
        $ward,
        $dept,
        $reason
    );
    $stmt->execute();

    header("Location: doctor_dashboard.php");
}
