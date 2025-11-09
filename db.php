<?php
// --- DATABASE CONFIGURATION --- //
// Use constants for credentials to make them globally available without scope issues.
define('DB_HOST', 'localhost');
define('DB_DB', 'pawpetcares');
define('DB_USER', 'root');
define('DB_PASS', '');       // Default empty password

/**
 * Creates and returns a new MySQLi connection.
 */
function get_db_connection() {
    // Create a new MySQLi connection using the defined constants
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_DB);

    // Check for connection errors
    if ($conn->connect_error) {
        // This is line 13. The error will no longer happen here.
        die("Database connection failed: " . $conn->connect_error);
    }

    return $conn;
}

// --- NOTIFICATION FUNCTION ---
// This function is unchanged but will now work correctly
// as it will be passed a valid $conn object.
/**
 * Creates notifications for all staff members (Admins and Healthcare).
 *
 * @param mysqli $conn The database connection object.
 * @param string $type The type of notification (e.g., 'add_vaccine').
 * @param int $reference_id The ID of the item (e.g., the new vaccination_id).
 * @param int $pet_id The ID of the pet involved.
 * @param string $status The current status (e.g., 'Pending').
 */
function create_notification_for_staff($conn, $type, $reference_id, $pet_id, $status) {
    
    // 1. Get pet's name
    $pet_name = "A pet";
    try {
        $pet_stmt = $conn->prepare("SELECT pet_name FROM pets WHERE pet_id = ?");
        $pet_stmt->bind_param("i", $pet_id);
        $pet_stmt->execute();
        $pet_result = $pet_stmt->get_result();
        if ($pet_row = $pet_result->fetch_assoc()) {
            $pet_name = $pet_row['pet_name'];
        }
        $pet_stmt->close();
    } catch (Exception $e) { /* Ignore error, proceed with default name */ }

    // 2. Define title, message, and link
    $title = "";
    $message = "";
    $link = ""; 
    
    if ($type == 'add_vaccine') {
        $title = "New Vaccine Request"; // Title for your 'notifications' table
        $message = "New vaccination request for " . htmlspecialchars($pet_name) . " (Status: " . htmlspecialchars($status) . ").";
        
        // !! IMPORTANT: Change this to your ADMIN/STAFF page
        $link = "dashboard.php?action=admin_vaccinations&pet_id={$pet_id}&vaccine_id={$reference_id}";
    }
    
    if (empty($message) || empty($title)) {
        return; // Don't proceed if no message/title was generated
    }

    // 3. Find all staff/admin users (rules 1 and 2)
    // Your SQL files show user_rules = 1 (Staff/Admin)
    $staff_sql = "SELECT id FROM users WHERE user_rules IN (1, 2)";
    $staff_result = $conn->query($staff_sql);
    
    if ($staff_result && $staff_result->num_rows > 0) {
        
        // 4. Prepare the new INSERT statement (matches notifications(2).sql)
        $notify_sql = "INSERT INTO notifications (user_id, title, message, link, is_read, created_at) 
                       VALUES (?, ?, ?, ?, 0, NOW())";
        $notify_stmt = $conn->prepare($notify_sql);
        
        if(!$notify_stmt) {
             return; // Handle prepare error
        }

        // 5. Loop and insert a notification for each staff member
        while ($staff_row = $staff_result->fetch_assoc()) {
            $staff_id = $staff_row['id'];
            
            // Bind params: i = staff_id, s = title, s = message, s = link
            $notify_stmt->bind_param("isss", $staff_id, $title, $message, $link);
            $notify_stmt->execute();
        }
        $notify_stmt->close();
    }
}
?>