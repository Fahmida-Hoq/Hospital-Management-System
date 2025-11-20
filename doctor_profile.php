<?php
session_start();
include 'config/db.php';
include 'includes/header.php';

if (!isset($_SESSION['doctor_id'])) {
    header("Location: login.php");
    exit();
}

$doctor_id = $_SESSION['doctor_id'];
$message = '';

// --- Handle Form Submissions ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 1. Update Profile Details
    if (isset($_POST['update_profile'])) {
        $specialization = $_POST['specialization'];
        $department = $_POST['department'];
        $phone = $_POST['phone'];

        $sql = "UPDATE doctors SET specialization=?, department=?, phone=? WHERE doctor_id=?";
        $stmt = query($sql, [$specialization, $department, $phone, $doctor_id], "sssi");
        
        if ($stmt->affected_rows > 0) {
            $message = "<div class='alert alert-success'>Profile details updated successfully!</div>";
        } else if ($conn->errno) {
             $message = "<div class='alert alert-danger'>Error updating profile: " . $conn->error . "</div>";
        } else {
             $message = "<div class='alert alert-info'>No changes detected.</div>";
        }
    }
    
    // 2. Manage Schedule (Add/Update/Delete)
    if (isset($_POST['update_schedule'])) {
        $day = $_POST['day_of_week'];
        $start = $_POST['start_time'];
        $end = $_POST['end_time'];
        
        // Use REPLACE INTO to either insert a new schedule or update an existing one for that day
        $sql = "REPLACE INTO doctor_schedules (doctor_id, day_of_week, start_time, end_time, status) 
                VALUES (?, ?, ?, ?, 'Active')";
        $stmt = query($sql, [$doctor_id, $day, $start, $end], "isss");

        if ($stmt->affected_rows > 0 || $stmt->affected_rows == 2) { // REPLACE INTO returns 2 on update
            $message = "<div class='alert alert-success'>Schedule for $day updated successfully!</div>";
        } else {
            $message = "<div class='alert alert-danger'>Failed to update schedule.</div>";
        }
    }
}

// --- Fetch Current Data ---
// Fetch static profile data
$profile_sql = "SELECT u.full_name, d.specialization, d.department, d.phone 
                FROM doctors d JOIN users u ON d.user_id = u.user_id 
                WHERE d.doctor_id = ?";
$stmt_profile = query($profile_sql, [$doctor_id], "i");
$profile_data = $stmt_profile->get_result()->fetch_assoc();

// Fetch dynamic schedule data
$schedule_sql = "SELECT * FROM doctor_schedules WHERE doctor_id = ? ORDER BY FIELD(day_of_week, 'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday')";
$stmt_schedule = query($schedule_sql, [$doctor_id], "i");
$schedules = $stmt_schedule->get_result()->fetch_all(MYSQLI_ASSOC);
$schedule_map = array_column($schedules, null, 'day_of_week');

$days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
        <h2 class="text-primary">👨‍⚕️ Profile Management: <?php echo htmlspecialchars($profile_data['full_name']); ?></h2>
        <a href="doctor_dashboard.php" class="btn btn-secondary">⬅️ Dashboard</a>
    </div>

    <?php echo $message; ?>

    <div class="row">
        
        <div class="col-md-6">
            <div class="card shadow-sm p-4 mb-4">
                <h4 class="text-success mb-3">Update Personal Details</h4>
                <form method="post" action="doctor_profile.php">
                    <input type="hidden" name="update_profile" value="1">
                    
                    <div class="mb-3">
                        <label for="specialization" class="form-label">Specialization</label>
                        <input type="text" class="form-control" id="specialization" name="specialization" value="<?php echo htmlspecialchars($profile_data['specialization']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="department" class="form-label">Department</label>
                        <input type="text" class="form-control" id="department" name="department" value="<?php echo htmlspecialchars($profile_data['department']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone</label>
                        <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($profile_data['phone']); ?>" required>
                    </div>
                    
                    <button type="submit" class="btn btn-success w-100">Save Profile</button>
                </form>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card shadow-sm p-4 mb-4">
                <h4 class="text-info mb-3">Manage Weekly Schedule/Shift</h4>
                
                <form method="post" action="doctor_profile.php" class="mb-4">
                    <input type="hidden" name="update_schedule" value="1">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="day_of_week" class="form-label">Day</label>
                            <select class="form-select" id="day_of_week" name="day_of_week" required>
                                <option value="">Select Day</option>
                                <?php foreach($days as $day): ?>
                                    <option value="<?php echo $day; ?>"><?php echo $day; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="start_time" class="form-label">Start Time</label>
                            <input type="time" class="form-control" id="start_time" name="start_time" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="end_time" class="form-label">End Time</label>
                            <input type="time" class="form-control" id="end_time" name="end_time" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-info w-100">Add/Update Shift</button>
                </form>

                <h5 class="mt-4">Current Duty Roster:</h5>
                <ul class="list-group">
                    <?php 
                    $has_schedule = false;
                    foreach($days as $day): 
                        if (isset($schedule_map[$day])):
                            $has_schedule = true;
                            $sch = $schedule_map[$day];
                    ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            **<?php echo $day; ?>:** <span class="badge bg-success">
                                <?php echo date('h:i A', strtotime($sch['start_time'])) . ' - ' . date('h:i A', strtotime($sch['end_time'])); ?>
                            </span>
                            </li>
                    <?php 
                        endif;
                    endforeach; 
                    if (!$has_schedule):
                    ?>
                        <li class="list-group-item text-center text-muted">No shifts defined yet.</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>