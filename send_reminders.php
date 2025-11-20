<?php
// This script would be run by a server cron job (e.g., once a day)
include 'config/db.php';

// 1. Find appointments scheduled for tomorrow
$tomorrow = date('Y-m-d', strtotime('+1 day'));

$sql = "SELECT u.email, u.full_name, a.appointment_date, a.appointment_time, du.full_name AS doctor_name
        FROM appointments a
        JOIN patients p ON a.patient_id = p.patient_id
        JOIN users u ON p.user_id = u.user_id
        JOIN doctors d ON a.doctor_id = d.doctor_id
        JOIN users du ON d.user_id = du.user_id
        WHERE a.appointment_date = ? AND a.status = 'approved'";

$stmt = query($sql, [$tomorrow], "s");
$appointments = $stmt->get_result();

if ($appointments->num_rows > 0) {
    while($appt = $appointments->fetch_assoc()) {
        $subject = "Appointment Reminder: Tomorrow, " . date('M d, Y', strtotime($appt['appointment_date']));
        $body = "Dear " . htmlspecialchars($appt['full_name']) . ",\n\n" .
                "This is a reminder for your appointment scheduled for tomorrow:\n" .
                "Date: " . date('M d, Y', strtotime($appt['appointment_date'])) . "\n" .
                "Time: " . date('h:i A', strtotime($appt['appointment_time'])) . "\n" .
                "Doctor: Dr. " . htmlspecialchars($appt['doctor_name']) . "\n\n" .
                "Please arrive 15 minutes early.\n\n" .
                "Hospital Management Team";
        
        // In a real system, you would use PHP's mail() function or a service like PHPMailer
        // mail($appt['email'], $subject, $body, "From: no-reply@yourhospital.com");
        echo "Sent reminder to " . $appt['email'] . "\n";
    }
} else {
    echo "No appointments scheduled for tomorrow.\n";
}
?>