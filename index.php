<?php
include 'config/db.php';
include 'includes/header.php'; 

// Fetch unique hospital departments
$dept_sql = "SELECT DISTINCT department FROM doctors ORDER BY department ASC";
$dept_result = query($dept_sql)->get_result();
$departments = $dept_result->fetch_all(MYSQLI_ASSOC);
?>

<!-- HERO SECTION -->
<div class="container my-5">
    <div class="p-5 mb-4 bg-light rounded-4 shadow-sm border">
        <div class="container-fluid py-5">
            <h1 class="display-5 fw-bold text-primary">Welcome to Our Hospital</h1>
            <p class="col-md-8 fs-4 text-muted">
                Your health matters. Explore departments and check doctor availability anytime.
            </p>
            <!--<a href="#departments" class="btn btn-primary btn-lg mt-3 px-4">
                Explore Departments
            </a>-->
        </div>
    </div>

    <!-- FEATURE: HOSPITAL DEPARTMENTS -->
    <h2 class="mb-4 text-center fw-bold text-secondary" id="departments">
        Hospital Departments
    </h2>

    <p class="text-center text-muted mb-4">
        Choose a department to view available doctors and specialties.
    </p>

    <div class="row row-cols-1 row-cols-md-3 g-4 mb-5">

        <?php if (!empty($departments)): ?>
            <?php foreach ($departments as $department_row): 
                $dept_name = htmlspecialchars($department_row['department']);
            ?>
            <div class="col">
                <div class="card h-100 shadow-sm border-0 rounded-4">
                    <div class="card-body">
                        <h5 class="card-title text-primary fw-bold">
                            <?php echo $dept_name; ?>
                        </h5>
                        <p class="card-text text-muted">
                            Explore doctors specialized in <?php echo $dept_name; ?>.
                        </p>
                        <a 
                            href="view_doctors.php?department=<?php echo urlencode($dept_name); ?>" 
                            class="btn btn-outline-primary btn-sm mt-2"
                        >
                            View Doctors
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>

        <?php else: ?>
            <div class="col-12">
                <div class="alert alert-warning text-center">
                    No hospital departments found.
                </div>
            </div>
        <?php endif; ?>

    </div>
</div>

<?php include 'includes/footer.php'; ?>
