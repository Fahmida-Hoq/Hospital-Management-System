<?php
// Run this file ONCE in your browser (e.g., http://localhost/hms/generate_hash.php)
$password = "123456"; 
echo "Hash for '{$password}': <br><br>";
echo password_hash($password, PASSWORD_DEFAULT);

?>