<?php
session_start();
include 'config/db.php';
include 'includes/header.php';

$department_filter = '';
$params = [];
$types = '';
$page_title = "Specialist Doctor Directory";
$filter_message = '';

if (isset($_GET['department']) && !empty($_GET['department'])) {
    $department_filter = "WHERE d.department = ?";
    $params[] = $_GET['department'];
    $types = 's';
    $page_title = htmlspecialchars($_GET['department']) . " Specialists";
} else {
    $filter_message = "<p class='lead'>Browse our full list of specialists below.</p>";
}

// FIX: Changed 'available_day' to 'day_of_week' based on common schema errors
// Also added error handling so the page doesn't go blank if the query fails
$sql = "SELECT 
            u.full_name, 
            d.specialization, 
            d.department, 
            d.doctor_id,
            (SELECT GROUP_CONCAT(CONCAT(day_of_week, ': ', TIME_FORMAT(start_time, '%h:%i %p'), '-', TIME_FORMAT(end_time, '%h:%i %p')) SEPARATOR '<br>') 
             FROM doctor_schedules 
             WHERE doctor_id = d.doctor_id) as schedule
        FROM 
            doctors d
        JOIN 
            users u ON d.user_id = u.user_id
        {$department_filter}
        ORDER BY 
            u.full_name";

$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$doctors = $stmt->get_result();
?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
        <h2 class="text-primary"><?php echo $page_title; ?></h2>
        <a href="index.php" class="btn btn-outline-secondary">Back to Home</a>
    </div>
    
    <?php echo $filter_message; ?>
    <div class="row row-cols-1 row-cols-md-3 g-4 mt-4">
        <?php if ($doctors && $doctors->num_rows > 0): ?>
            <?php while($doctor = $doctors->fetch_assoc()): ?>
            <div class="col">
                <div class="card h-100 shadow-sm border-0 text-center">
                    <div class="card-body">
                        <i class="fas fa-user-md fa-3x text-info mb-3"></i>
                        <h5 class="card-title text-danger fw-bold"><?php echo htmlspecialchars($doctor['full_name']); ?></h5>
                        
                        <p class="card-text">
                            <strong>Specialization:</strong> <span class="badge bg-success"><?php echo htmlspecialchars($doctor['specialization']); ?></span><br>
                            <strong>Department:</strong> <span class="badge bg-primary"><?php echo htmlspecialchars($doctor['department']); ?></span>
                        </p>

                        <div class="mt-3 p-2 border rounded bg-light">
                            <h6 class="fw-bold mb-1" style="font-size: 0.9rem;"><i class="fas fa-calendar-alt me-1"></i> Availability</h6>
                            <div class="text-muted small">
                                <?php echo $doctor['schedule'] ? $doctor['schedule'] : "No schedule set"; ?>
                            </div>
                        </div>
                        
                        <a href="login.php?doctor_id=<?php echo $doctor['doctor_id']; ?>" 
                           class="btn btn-success mt-3">Book Now</a>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12 text-center">
                <div class="alert alert-warning">No doctors found matching the criteria.</div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>