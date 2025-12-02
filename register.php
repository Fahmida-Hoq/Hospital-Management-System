<?php
include 'config/db.php';
include 'includes/header.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = $_POST['name'];
    $age = $_POST['age'];
    $gender = $_POST['gender'];
    $phone = $_POST['phone'];
    $type = $_POST['type']; // indoor / outdoor

    // indoor only
    $guardian = $_POST['guardian'] ?? NULL;
    $problem = $_POST['problem'] ?? NULL;
    $room = $_POST['room'] ?? NULL;
    $bed = $_POST['bed'] ?? NULL;

    // Insert patient
    $sql = query("INSERT INTO patients (name, age, gender, phone, type, guardian, problem, room, bed, status)
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");

    $sql->bind_param("sissssssss", $name, $age, $gender, $phone, $type,
                     $guardian, $problem, $room, $bed);

    if ($sql->execute()) {
        echo "<script>alert('Patient registered successfully'); window.location='index.php';</script>";
    } else {
        echo "<script>alert('Error while saving');</script>";
    }
}
?>

<div class="container mt-5 mb-5">
    <h2 class="text-center">Patient Registration</h2>

    <form method="POST" id="regForm" class="border p-4 rounded shadow-sm">

        <label class="fw-bold">Patient Name:</label>
        <input type="text" name="name" class="form-control mb-3" required>

        <label class="fw-bold">Age:</label>
        <input type="number" name="age" class="form-control mb-3" required>

        <label class="fw-bold">Gender:</label>
        <select name="gender" class="form-control mb-3" required>
            <option value="">Select gender</option>
            <option>Male</option>
            <option>Female</option>
        </select>

        <label class="fw-bold">Phone:</label>
        <input type="text" name="phone" class="form-control mb-3" required>

        <label class="fw-bold">Patient Type:</label>
        <select name="type" id="ptype" class="form-control mb-3" required>
            <option value="">Select type</option>
            <option value="Outdoor">Outdoor</option>
            <option value="Indoor">Indoor</option>
        </select>

        <!-- Indoor Section -->
        <div id="indoorFields" style="display:none;">

            <label class="fw-bold">Guardian Name:</label>
            <input type="text" name="guardian" class="form-control mb-3">

            <label class="fw-bold">Problem / Disease:</label>
            <textarea name="problem" class="form-control mb-3"></textarea>

            <label class="fw-bold">Room No:</label>
            <input type="text" name="room" class="form-control mb-3">

            <label class="fw-bold">Bed No:</label>
            <input type="text" name="bed" class="form-control mb-3">

        </div>

        <button type="submit" class="btn btn-primary w-100">Register Patient</button>
    </form>
</div>

<script>
document.getElementById("ptype").addEventListener("change", function() {
    let type = this.value;
    let indoor = document.getElementById("indoorFields");

    if (type === "Indoor") {
        indoor.style.display = "block";
    } else {
        indoor.style.display = "none";
    }
});
</script>

<?php include 'includes/footer.php'; ?>
