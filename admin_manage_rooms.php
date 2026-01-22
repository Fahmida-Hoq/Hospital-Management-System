<?php
session_start();
include 'config/db.php';
include 'includes/header.php';
$query = "SELECT 
            b.bed_id, 
            b.ward_name, 
            b.bed_number, 
            b.status as bed_status,
            p.name as patient_name
          FROM beds b 
          LEFT JOIN admissions a ON b.bed_id = a.bed_id AND a.status = 'Admitted'
          LEFT JOIN patients p ON a.patient_id = p.patient_id
          ORDER BY b.ward_name ASC, b.bed_number ASC";

$rooms = $conn->query($query);
?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold"><i class="fas fa-hospital text-primary me-2"></i>Ward & Bed Status</h2>
        <div>
            <span class="badge bg-success">Available</span>
            <span class="badge bg-danger">Occupied</span>
        </div>
    </div>

    <div class="row g-3">
        <?php if ($rooms && $rooms->num_rows > 0): ?>
            <?php while($row = $rooms->fetch_assoc()): 
                // In your DB, status is 'Available' or 'Occupied'
                $is_occupied = (strtolower($row['bed_status']) == 'occupied');
            ?>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100 <?= $is_occupied ? 'border-start border-danger border-4' : 'border-start border-success border-4' ?>">
                    <div class="card-body">
                        <h6 class="text-muted small text-uppercase mb-1"><?= htmlspecialchars($row['ward_name']) ?></h6>
                        <h5 class="fw-bold">Bed <?= htmlspecialchars($row['bed_number']) ?></h5>
                        
                        <?php if($is_occupied): ?>
                            <span class="badge bg-danger mb-2">Occupied</span>
                            <p class="small mb-0 text-dark fw-bold text-truncate">
                                <i class="fas fa-user me-1"></i> <?= htmlspecialchars($row['patient_name'] ?? 'In Treatment') ?>
                            </p>
                        <?php else: ?>
                            <span class="badge bg-success mb-2">Available</span>
                            <p class="small text-muted mb-0">Ready for Admission</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12 text-center py-5">
                <p class="text-muted">No beds found in the database table.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>