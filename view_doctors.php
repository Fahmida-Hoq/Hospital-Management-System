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
   // $filter_message = "<p class='lead'>Showing doctors in the **" . htmlspecialchars($_GET['department']) . "** department.</p>";
} else {
    $filter_message = "<p class='lead'>Browse our full list of specialists below.</p>";
}
$sql = "SELECT 
            u.full_name, 
            d.specialization, 
            d.department, 
            d.doctor_id 
        FROM 
            doctors d
        JOIN 
            users u ON d.user_id = u.user_id
        {$department_filter}
        ORDER BY 
            u.full_name";

$stmt = query($sql, $params, $types);
$doctors = $stmt->get_result();
?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
        <h2 class="text-primary"><?php echo $page_title; ?></h2>
        <a href="index.php" class="btn btn-outline-secondary">Back to Home</a>
    </div>
    
    <?php echo $filter_message; ?>
    <div class="row row-cols-1 row-cols-md-3 g-4 mt-4">
        <?php if ($doctors->num_rows > 0): ?>
            <?php while($doctor = $doctors->fetch_assoc()): ?>
            <div class="col">
                <div class="card h-100 shadow-sm border-0 text-center">
                    <div class="card-body">
                        <i class="fas fa-user-md fa-3x text-info mb-3"></i>
                        <h5 class="card-title text-danger fw-bold"><?php echo htmlspecialchars($doctor['full_name']); ?></h5>
                        
                        <p class="card-text">
                            **Specialization:** <span class="badge bg-success"><?php echo htmlspecialchars($doctor['specialization']); ?></span><br>
                            **Department:** <span class="badge bg-primary"><?php echo htmlspecialchars($doctor['department']); ?></span>
                        </p>
                        
                        <a href="book_appointment.php?doctor_id=<?php echo $doctor['doctor_id']; ?>" 
                           class="btn btn-success mt-2">Check Availability & Book</a>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="alert alert-warning">No doctors found matching the criteria.</div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>