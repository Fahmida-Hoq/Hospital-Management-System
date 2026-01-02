<?php
session_start();
include 'config/db.php';
include 'includes/header.php';

// SQL joins everything: Patients, Admissions, Doctors (via Users), and Beds
$sql = "SELECT a.*, p.name as p_name, u.full_name as dr_name, b.bed_number, b.ward_name 
        FROM admissions a 
        JOIN patients p ON a.patient_id = p.patient_id 
        JOIN beds b ON a.bed_id = b.bed_id 
        JOIN doctors d ON a.doctor_id = d.doctor_id 
        JOIN users u ON d.user_id = u.user_id
        WHERE a.status = 'Admitted'";

$result = $conn->query($sql);
?>

<div class="container-fluid my-5 px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">Current Indoor Patients</h2>
        <a href="admit_patient_form.php" class="btn btn-primary">+ New Admission</a>
    </div>

    <div class="table-responsive bg-white shadow-sm rounded">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-dark text-white">
                <tr>
                    <th>Bed / Ward</th>
                    <th>Patient Name & ID</th>
                    <th>Guardian Info</th>
                    <th>Supervising Doctor</th>
                    <th>Admitted Since</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php 
               
                if ($result && $result->num_rows > 0): 
                    while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td class="fw-bold"><?= $row['ward_name'] ?> - B<?= $row['bed_number'] ?></td>
                        <td>
                            <strong><?= $row['p_name'] ?></strong><br>
                            <small class="text-muted">ID: <?= $row['patient_id'] ?></small>
                        </td>
                        <td>
                            <small><?= $row['guardian_name'] ?> (<?= $row['guardian_phone'] ?>)</small>
                        </td>
                        <td> <?= $row['dr_name'] ?></td>
                        <td><?= date('d M, Y', strtotime($row['admission_date'])) ?></td>
                        <td><span class="badge bg-success">Admitted</span></td>
                        <td>
                            <a href="generate_bill.php?adm_id=<?= $row['admission_id'] ?>" class="btn btn-sm btn-outline-danger">Bill/Discharge</a>
                        <a href="indoor_patient_invoice.php?adm_id=<?= $row['admission_id'] ?>" target="_blank" class="btn btn-sm btn-outline-dark">
    Print Invoice
</a>
                        </td>
                    </tr>
                <?php endwhile; 
                else: ?>
                    <tr><td colspan="7" class="text-center py-5 text-muted">No patients currently admitted.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>