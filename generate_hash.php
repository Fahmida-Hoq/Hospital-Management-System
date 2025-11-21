<?php
// Run this file ONCE in your browser 
$password = "123456"; 
echo "Hash for '{$password}': <br><br>";
echo password_hash($password, PASSWORD_DEFAULT);

?>