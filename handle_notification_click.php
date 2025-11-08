<?php
session_start();
include 'db.php'; // Include your database connection file

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if notification ID and redirect URL are provided
if (isset($_GET['id']) && isset($_GET['redirect'])) {
    
    $notification_id = (int)$_GET['id'];
    $user_id = (int)$_SESSION['user_id'];
    $redirect_url = $_GET['redirect']; // This is the non-url-encoded URL

    // Get database connection
    $conn = get_db_connection();

    // Mark the notification as read *only for this user*
    $sql = "UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $notification_id, $user_id);
    $stmt->execute();
    $stmt->close();
    $conn->close();

    // Redirect the user to the provided link
    header("Location: " . $redirect_url);
    exit();
    
} else {
    // If parameters are missing, just go to the dashboard
    header("Location: dashboard.php");
    exit();
}
?>