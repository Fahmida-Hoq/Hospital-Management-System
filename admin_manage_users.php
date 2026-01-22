<?php
session_start();
include 'config/db.php';
include 'includes/header.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') { header("Location: login.php"); exit(); }


if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $conn->query("DELETE FROM users WHERE user_id = $id");
    header("Location: admin_manage_users.php");
}

$users = $conn->query("SELECT * FROM users WHERE role != 'patient' ORDER BY created_at DESC");
?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">Staff Management</h2>
        <a href="register_staff.php" class="btn btn-primary">+ Add New Staff</a>
    </div>

    <div class="table-responsive bg-white shadow-sm rounded border">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-light">
                <tr>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Joined Date</th>
                    <th class="text-center">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $users->fetch_assoc()): ?>
                <tr>
                    <td><strong><?= $row['full_name'] ?></strong></td>
                    <td><?= $row['email'] ?></td>
                    <td><span class="badge bg-info text-dark"><?= strtoupper($row['role']) ?></span></td>
                    <td><?= date('d M, Y', strtotime($row['created_at'])) ?></td>
                    <td class="text-center">
                        <a href="?delete=<?= $row['user_id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Remove this staff member?')">Delete</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>