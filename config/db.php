<?php

define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root'); 
define('DB_PASSWORD', '');
define('DB_NAME', 'hospital management'); 
$conn = null;
function get_db_connection() {
    global $conn;
    if ($conn === null) {
        $conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }
    }
    return $conn;
}

function query($sql, $params = [], $types = "") {
    $conn = get_db_connection();
    
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die('Database Error (Prepare failed): ' . $conn->error . ' Query: ' . $sql);
    }
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    if ($stmt->error) {
        die('Database Error (Execution failed): ' . $stmt->error . ' Query: ' . $sql);
    }
    return $stmt;
}
get_db_connection();

?>