<?php 
include 'config/db.php';
include 'includes/header.php'; 
?>

<header class="hero-section">
    <div class="container">
        <h1>Your Health, Our Priority</h1>
        <p class="lead">Seamlessly manage appointments, check doctor availability, and view your health records.</p>
        
        </div>
</header>

<section class="container my-5">
    <h2 class="text-center mb-4 text-primary">Key Features</h2>
    <div class="row text-center">
        <div class="col-md-4 mb-4">
            <div class="card feature-card p-3 shadow-sm">
                <i class="h1 text-success mb-3">📅</i>
                <div class="card-body">
                    <h5 class="card-title">Easy Appointment Scheduling</h5>
                    <p class="card-text">Book your appointments with your preferred doctor in a few simple clicks.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card feature-card p-3 shadow-sm">
                <i class="h1 text-info mb-3">🧑‍⚕️</i>
                <div class="card-body">
                    <h5 class="card-title">Check Doctor Availability</h5>
                    <p class="card-text">View real-time availability of our specialist doctors.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card feature-card p-3 shadow-sm">
                <i class="h1 text-warning mb-3">🔒</i>
                <div class="card-body">
                    <h5 class="card-title">Secure Patient Access</h5>
                    <p class="card-text">Log in to view your detailed appointment history and personal health details.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>