<?php
include 'config/db.php';
include 'includes/header.php';

$res = $conn->query("SELECT * FROM beds ORDER BY ward_name ASC");
?>

<div class="container my-5">
    <h3>Hospital Bed Live Status</h3>
    <div class="row">
        <?php while($bed = $res->fetch_assoc()): ?>
            <div class="col-md-3 mb-3">
                <div class="card <?= $bed['status'] == 'Available' ? 'bg-success' : 'bg-danger' ?> text-white text-center">
                    <div class="card-body">
                        <h5><?= $bed['ward_name'] ?></h5>
                        <p>Bed: <?= $bed['bed_number'] ?></p>
                        <strong><?= $bed['status'] ?></strong>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
</div>