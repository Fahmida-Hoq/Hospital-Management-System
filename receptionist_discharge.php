<?php
session_start();
include 'config/db.php';
include 'includes/header.php';

// Fetch all currently admitted patients and their bed info
$query = "SELECT a.admission_id, a.patient_id, p.name, b.bed_id, b.bed_number, b.ward_name 
          FROM admissions a
          JOIN patients p ON a.patient_id = p.patient_id
          JOIN beds b ON a.bed_id = b.bed_id
          WHERE a.status = 'Admitted'";
$res = $conn->query($query);
?>

<div class="container my-5">
    <h3>Current Indoor Patients (Occupying Beds)</h3>
    <table class="table table-striped shadow">
        <thead class="table-dark">
            <tr>
                <th>Patient Name</th>
                <th>Ward</th>
                <th>Bed No</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = $res->fetch_assoc()): ?>
            <tr>
                <td><?= $row['name'] ?></td>
                <td><?= $row['ward_name'] ?></td>
                <td><?= $row['bed_number'] ?></td>
                <td>
                    <a href="process_discharge.php?id=<?= $row['admission_id'] ?>&bed_id=<?= $row['bed_id'] ?>" 
                       class="btn btn-warning btn-sm" 
                       onclick="return confirm('Release this bed and discharge patient?')">
                       Discharge & Release Bed
                    </a>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>