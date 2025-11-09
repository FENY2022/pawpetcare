<?php
session_start();
require_once 'db.php'; // Include your database connection

// Get database connection
$conn = get_db_connection();

// Check if connection was successful
if (!$conn) {
    header("Location: profile.php?profile_error=db_conn");
    exit;
}

// Check if this is a POST request
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 1. --- SECURITY CHECK ---
    // Check if user is logged in and the form user_id matches the session user_id
    if (!isset($_SESSION['user_id']) || !isset($_POST['user_id']) || $_SESSION['user_id'] != $_POST['user_id']) {
        // Authorization failed
        header("Location: profile.php?profile_error=auth_failed");
        exit;
    }

    $user_id = (int)$_POST['user_id'];

    // 2. --- GET & SANITIZE POST DATA ---
    $first_name = trim($_POST['first_name']);
    $middle_name = trim($_POST['middle_name']);
    $last_name = trim($_POST['last_name']);
    $contact_number = trim($_POST['contact_number']);
    $email = trim($_POST['email']);
    $street = trim($_POST['street']);
    $barangay = trim($_POST['barangay']);
    $city = trim($_POST['city']);
    $province = trim($_POST['province']);
    $postal_code = trim($_POST['postal_code']);

    // 3. --- VALIDATION ---
    // Check for empty required fields
    if (empty($first_name) || empty($last_name) || empty($email) || empty($contact_number)) {
        header("Location: profile.php?profile_error=empty");
        exit;
    }

    // Check for valid email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: profile.php?profile_error=invalid_email");
        exit;
    }

    // 4. --- EMAIL UNIQUENESS CHECK ---
    // Check if this email is already taken by ANOTHER user
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->bind_param("si", $email, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Email already exists for another user
        $stmt->close();
        $conn->close();
        header("Location: profile.php?profile_error=email_exists");
        exit;
    }
    $stmt->close();


    // 5. --- UPDATE DATABASE ---
    // All checks passed, proceed with update
    $sql = "UPDATE users SET 
                first_name = ?, 
                middle_name = ?, 
                last_name = ?, 
                contact_number = ?, 
                email = ?, 
                street = ?, 
                barangay = ?, 
                city = ?, 
                province = ?, 
                postal_code = ? 
            WHERE id = ?";

    $stmt = $conn->prepare($sql);
    // s = string, i = integer. 10 strings, 1 integer
    $stmt->bind_param("ssssssssssi", 
        $first_name, 
        $middle_name, 
        $last_name, 
        $contact_number, 
        $email, 
        $street, 
        $barangay, 
        $city, 
        $province, 
        $postal_code,
        $user_id
    );

    if ($stmt->execute()) {
        // 6. --- UPDATE SESSION & REDIRECT ---
        // Update session variables so the user sees the change immediately
        $_SESSION['first_name'] = $first_name;
        
        $stmt->close();
        $conn->close();
        header("Location: profile.php?profile_success=1");
        exit;
    } else {
        // Database update failed
        $stmt->close();
        $conn->close();
        header("Location: profile.php?profile_error=db_update");
        exit;
    }

} else {
    // Not a POST request, redirect back to profile
    header("Location: profile.php");
    exit;
}
?>