<?php
$host = 'localhost';
$db = 'pawpetcares';
$user = 'root';  // Default XAMPP user
$pass = '';      // Default empty password

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
