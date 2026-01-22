<?php
session_start();
include 'config/db.php';
include 'includes/header.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$u_id = $_SESSION['user_id']; 

$doc_query = $conn->query("SELECT doctor_id FROM doctors WHERE user_id = '$u_id'");
$doc_data = $doc_query->fetch_assoc();
if (!$doc_data) {
    echo "<div class='container my-5'><div class='alert alert-warning'>
            <h4>Access Restricted</h4>
            <p>This page is only for registered doctors. Please log in with a doctor account.</p>
            <a href='login.php' class='btn btn-primary'>Go to Login</a>
          </div></div>";
    include 'includes/footer.php';
    exit();
}

$doctor_id = $doc_data['doctor_id'];
$sql = "SELECT a.*, p.name as p_name, p.phone, b.ward_name, b.bed_number 
        FROM admissions a
        JOIN patients p ON a.patient_id = p.patient_id
        JOIN beds b ON a.bed_id = b.bed_id
        WHERE a.doctor_id = '$doctor_id' AND a.status = 'Admitted'";
$result = $conn->query($sql);
?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold">My Indoor Patients (Ward/Cabin)</h3>
        <span class="badge bg-danger p-2 px-3">Active Admissions</span>
    </div>

    <div class="table-responsive">
        <table class="table table-hover bg-white shadow-sm border align-middle">
            <thead class="table-dark">
                <tr>
                    <th>Patient Details</th>
                    <th>Location</th>
                    <th>Admission Date</th>
                    <th class="text-center">Manage Treatment Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if($result && $result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($row['p_name']) ?></strong><br>
                            <small class="text-muted"><?= htmlspecialchars($row['phone']) ?></small>
                        </td>
                        <td>
                            <span class="badge bg-info text-dark"><?= strtoupper($row['ward_name']) ?></span>
                            <div class="small fw-bold mt-1">Bed No: <?= $row['bed_number'] ?></div>
                        </td>
                        <td><?= date('d M Y', strtotime($row['admission_date'])) ?></td>
                      
                           
                               <td class="text-center">
    <a href="manage_indoor_treatment.php?adm_id=<?= $row['admission_id'] ?>&patient_id=<?= $row['patient_id'] ?>" class="btn btn-sm btn-primary px-3">
        <i class="fas fa-folder-open"></i> Open Patient Record
    </a>
</td>         
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="text-center py-5">
                            <i class="fas fa-user-injured fa-3x text-light mb-3"></i>
                            <p class="text-muted mb-0">No patients currently under your supervision.</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class= "contaiiner-5"> 
    
                </div>
    

        

<?php include 'includes/footer.php'; ?>