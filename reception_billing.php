<?php
include 'config/db.php';
// Example for a specific admission
$admission_id = $_GET['id']; 

$data = $conn->query("SELECT a.*, p.name, b.daily_charge, b.bed_type, d.full_name as dr_name
                      FROM admissions a 
                      JOIN patients p ON a.patient_id = p.patient_id
                      JOIN beds b ON a.bed_id = b.bed_id
                      JOIN doctors d ON a.doctor_id = d.doctor_id
                      WHERE a.admission_id = $admission_id")->fetch_assoc();

// Calculate Bed Days
$start = new DateTime($data['admission_date']);
$end = new DateTime(); // Today
$days = $start->diff($end)->days + 1; // Minimum 1 day
$total_bed_fee = $days * $data['daily_charge'];
?>

<div class="container mt-5">
    <div class="card p-4 shadow">
        <h4>Final Billing Statement</h4>
        <hr>
        <p><strong>Patient:</strong> <?= $data['name'] ?></p>
        <p><strong>Consultant:</strong> Dr. <?= $data['dr_name'] ?></p>
        
        <table class="table border">
            <tr><td>Admission Fee</td><td class="text-end">Tk<?= number_filter($data['admission_fee']) ?></td></tr>
            <tr><td>Bed Charge (<?= $data['bed_type'] ?> - <?= $days ?> Days)</td><td class="text-end">$<?= number_format($total_bed_fee, 2) ?></td></tr>
            <tr>
                <td>Lab Tests & Medicines</td>
                <td class="text-end">
                    <?php
                    $extras = $conn->query("SELECT SUM(amount) FROM billing WHERE patient_id = ".$data['patient_id']." AND status='Unpaid'")->fetch_row()[0];
                    echo "$".number_format($extras, 2);
                    ?>
                </td>
            </tr>
            <tr class="table-dark">
                <th>Total Payable</th>
                <th class="text-end">$<?= number_format($data['admission_fee'] + $total_bed_fee + $extras, 2) ?></th>
            </tr>
        </table>
        <button class="btn btn-success">Confirm Payment & Discharge</button>
    </div>
</div>