<?php
// Database connection parameters
$servername = "localhost";
$username = "root"; 
$password = "";     
$dbname = "hospital management"; 

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
//echo "Connected successfully"; // Uncomment to test connection


function query($sql, $params = [], $types = "") {
    global $conn;
    $stmt = $conn->prepare($sql);
    
    if ($params) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    return $stmt;
}

// Function for hashing passwords
function hash_password($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}
?>