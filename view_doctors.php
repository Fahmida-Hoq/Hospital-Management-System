<?php 
session_start();
include 'config/db.php';
include 'includes/header.php'; 

// --- PHP Logic to Fetch Doctors ---
$sql_doctors = "SELECT 
                    u.full_name, 
                    d.specialization, 
                    d.department,
                    d.doctor_id 
                FROM users u
                JOIN doctors d ON u.user_id = d.user_id
                WHERE u.role = 'doctor' 
                ORDER BY d.department, u.full_name";

$stmt_doctors = query($sql_doctors);
$doctors = $stmt_doctors->get_result();

?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
        <h2 class="text-secondary">🩺 Specialist Doctor Directory</h2>
        <a href="index.php" class="btn btn-outline-secondary">⬅️ Back to Home</a>
    </div>

    <p class="lead text-center mb-5">
        Browse our list of doctors. Click 'Check Availability & Book' to schedule an appointment.
    </p>
    
    <div class="row">
        <?php if ($doctors->num_rows > 0): ?>
            <?php while($row = $doctors->fetch_assoc()): ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card h-100 shadow-sm border-info">
                    <div class="card-body text-center">
                        <i class="h3 text-info d-block mb-2">🧑‍⚕️</i>
                        <h5 class="card-title text-primary"><?php echo htmlspecialchars($row['full_name']); ?></h5>
                        <p class="card-text mb-1">
                            **Specialization:** <span class="badge bg-success"><?php echo htmlspecialchars($row['specialization']); ?></span>
                        </p>
                        <p class="card-text text-muted mb-3">
                            **Department:** <?php echo htmlspecialchars($row['department']); ?>
                        </p>
                        
                        <a href="patient_appointment.php?doctor_id=<?php echo $row['doctor_id']; ?>" class="btn btn-success mt-2">
                            Check Availability & Book
                        </a>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="alert alert-warning text-center">No doctor profiles found in the system. Please contact the administrator.</div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>