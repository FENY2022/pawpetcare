<?php
// --- DATABASE CONFIGURATION --- //
$host = 'localhost';
$db = 'pawpetcares';
$user = 'root';       // Default XAMPP user
$pass = '';           // Default empty password

// Initialize connection handling at the beginning of the function
function get_db_connection() {
    global $host, $db, $user, $pass;

    // Create a new MySQLi connection
    $conn = new mysqli($host, $user, $pass, $db);

    // Check for connection errors
    if ($conn->connect_error) {
        die("Database connection failed: " . $conn->connect_error);
    }

    return $conn;
}

// Example of how to use the database:

try {
    $conn = get_db_connection();
} catch (ConnectionException $e) {
    echo "$e->getMessage() .\n";
}
?>