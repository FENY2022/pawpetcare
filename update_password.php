<?php
session_start();
require_once 'db.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // --- Validation ---
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        header("Location: profile.php?pass_error=empty");
        exit;
    }

    if ($new_password !== $confirm_password) {
        header("Location: profile.php?pass_error=mismatch");
        exit;
    }

    if (strlen($new_password) < 8) {
        header("Location: profile.php?pass_error=short");
        exit;
    }

    // --- Verify Current Password ---
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        $hashed_password_from_db = $user['password'];

        // Use password_verify to check if the entered current password is correct
        if (password_verify($current_password, $hashed_password_from_db)) {
            
            // --- Current password is correct, proceed to update ---
            $new_password_hashed = password_hash($new_password, PASSWORD_DEFAULT);

            $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $update_stmt->bind_param("si", $new_password_hashed, $user_id);

            if ($update_stmt->execute()) {
                // Success
                header("Location: profile.php?pass_success=changed");
                exit;
            } else {
                // Database update failed
                header("Location: profile.php?pass_error=db");
                exit;
            }
            $update_stmt->close();
        } else {
            // Current password was incorrect
            header("Location: profile.php?pass_error=incorrect");
            exit;
        }
    } else {
        // User not found (should not happen if session is valid)
        header("Location: profile.php?pass_error=nouser");
        exit;
    }
    $stmt->close();
    $conn->close();

} else {
    // Redirect if accessed directly without POST method
    header("Location: profile.php");
    exit;
}
?>