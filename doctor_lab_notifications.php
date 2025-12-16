<?php
session_start();
include 'config/db.php';
include 'includes/header.php';

$user_id = (int)$_SESSION['user_id'];

$conn->query("UPDATE notifications SET is_read=1 WHERE user_id=$user_id");

$res = $conn->query("
SELECT title, message, created_at
FROM notifications
WHERE user_id=$user_id
ORDER BY created_at DESC
");
?>

<div class="container my-5">
    <h3>Lab Notifications</h3>

    <?php while($n=$res->fetch_assoc()): ?>
        <div class="alert alert-info">
            <strong><?= $n['title'] ?></strong><br>
            <?= $n['message'] ?><br>
            <small><?= $n['created_at'] ?></small>
        </div>
    <?php endwhile; ?>

    <a href="doctor_dashboard.php" class="btn btn-secondary">Back</a>
</div>

<?php include 'includes/footer.php'; ?>
