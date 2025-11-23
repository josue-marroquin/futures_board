<?php
$servername = "localhost";
$username = "root";
$password = "";
$db = "trading_panel";
$conn;

// Create connection
try {
    $conn = mysqli_connect($servername, $username, $password, $db);    
} catch (Exception $e) {
    echo "An error occurred: " . $e->getMessage();
}

?>