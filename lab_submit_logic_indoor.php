<?php
session_start();
include 'config/db.php';

if (isset($_POST['submit_report'])) {
    $test_id = (int)$_POST['test_id'];
    $findings = $conn->real_escape_string($_POST['findings']);
    $fees = (float)$_POST['fees'];

    $sql = "UPDATE lab_tests 
            SET result = '$findings', 
                test_fees = '$fees', 
                status = 'completed' 
            WHERE test_id = $test_id";

    if ($conn->query($sql)) {
        header("Location: lab_manage_indoor.php?msg=Report Submitted");
        exit();
    } else {
        die("Update Error: " . $conn->error);
    }
}
?>