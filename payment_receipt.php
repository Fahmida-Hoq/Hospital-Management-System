<?php
session_start();
include 'config/db.php';
include 'includes/header.php';

$adm_id = (int)$_GET['adm_id'];
$method = $_GET['method'];

// Fetch patient info for receipt
$res = $conn->query("SELECT p.name FROM admissions a JOIN patients p ON a.patient_id = p.patient_id WHERE a.admission_id = $adm_id");
$p = $res->fetch_assoc();
?>
<div class="container my-5 text-center">
    <div class="card mx-auto shadow p-5" style="max-width: 500px;">
        <i class="fas fa-check-circle text-success display-1 mb-4"></i>
        <h2 class="fw-bold">Payment Verified</h2>
        <p class="text-muted">Patient <strong><?= $p['name'] ?></strong> has been successfully discharged.</p>
        <div class="alert alert-info py-2">Method: <?= $method ?></div>
        <hr>
        <button onclick="window.print()" class="btn btn-outline-dark me-2">Print Receipt</button>
        <a href="doctor_indoor_patients.php" class="btn btn-primary">Return to Ward</a>
    </div>
</div>