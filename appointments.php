<?php
session_start();
include_once 'db.php'; // Ensure db.php is accessible for get_db_connection()

// --- START: New create_notification_for_staff Function (Now Protected) ---
// Function to create a notification for staff/admin when a new appointment is requested
if (!function_exists('create_notification_for_staff')) {
    function create_notification_for_staff($conn, $notification_type, $related_id, $pet_id, $status) {
        // 1. Get Pet Name for the message
        $pet_name = "A pet";
        $sql_pet = "SELECT pet_name FROM pets WHERE pet_id = ?";
        $stmt_pet = $conn->prepare($sql_pet);
        if ($stmt_pet) {
            $stmt_pet->bind_param("i", $pet_id);
            $stmt_pet->execute();
            $result_pet = $stmt_pet->get_result();
            if ($row_pet = $result_pet->fetch_assoc()) {
                $pet_name = htmlspecialchars($row_pet['pet_name']);
            }
            $stmt_pet->close();
        }
        
        // 2. Define notification details
        // Using a hardcoded staff/admin ID (35) as found in the notifications data.
        $staff_user_id = 35; 
        
        switch ($notification_type) {
            case 'new_appointment':
                $title = 'New Appointment Request';
                $message = "New check-up appointment requested for pet $pet_name (Status: $status).";
                // Link points to the admin's appointment management page
                $link = "dashboard.php?action=manage_appointments&appointment_id=$related_id";
                break;
            default:
                return; // Exit if type is unknown
        }
        
        // 3. Insert notification into the notifications table
        $sql = "INSERT INTO notifications (user_id, title, message, link, is_read) 
                VALUES (?, ?, ?, ?, 0)";
        
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            // user_id is int, others are strings
            $stmt->bind_param("isss", $staff_user_id, $title, $message, $link);
            $stmt->execute();
            $stmt->close();
        }
    }
}
// --- END: New create_notification_for_staff Function (Now Protected) ---


// Check for session validity and role (must be client)
if (!isset($_SESSION['user_id']) || ($_SESSION['user_rules'] ?? 0) != 0) {
    echo "<div class='p-6 text-red-600 font-semibold'>Access Denied or Session Expired.</div>";
    exit;
}

$client_id = (int)$_SESSION['user_id'];
$conn = get_db_connection();
$message = '';
$message_type = '';

// --- Handle Appointment Submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_appointment'])) {
    $pet_id = (int)$_POST['pet_id'];
    $requested_date = trim($_POST['requested_date']);
    $requested_time_slot = trim($_POST['requested_time_slot']);
    $appointment_type = trim($_POST['appointment_type']);
    $reason_for_visit = trim($_POST['reason_for_visit']);

    // Simple validation
    if (empty($pet_id) || empty($requested_date) || empty($requested_time_slot) || empty($appointment_type)) {
        $message = "Please fill in all required fields.";
        $message_type = 'error';
    } elseif (strtotime($requested_date) < strtotime('today')) {
        $message = "The requested date cannot be in the past.";
        $message_type = 'error';
    } else {
        $initial_status = 'Pending Review';

        // Insert into the new appointments table (assumed structure)
        $sql = "INSERT INTO appointments (client_id, pet_id, appointment_type, requested_date, requested_time_slot, reason_for_visit, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("iisssss", $client_id, $pet_id, $appointment_type, $requested_date, $requested_time_slot, $reason_for_visit, $initial_status);
            
            if ($stmt->execute()) {
                $new_appointment_id = $conn->insert_id;
                
                // Notify Staff (This is where the new function is called)
                if (function_exists('create_notification_for_staff')) {
                    create_notification_for_staff($conn, 'new_appointment', $new_appointment_id, $pet_id, $initial_status);
                }
                
                $message = "Appointment request submitted successfully! A staff member will review and confirm your slot soon.";
                $message_type = 'success';
                // Clear POST data to prevent resubmission
                $_POST = array(); 
            } else {
                $message = "Database error: " . $stmt->error;
                $message_type = 'error';
            }
            $stmt->close();
        } else {
            $message = "Failed to prepare database statement.";
            $message_type = 'error';
        }
    }
}

// --- Fetch Client's Approved Pets for the Form Dropdown ---
$pets = [];
$sql_pets = "SELECT pet_id, pet_name, pet_species FROM pets WHERE client_id = ? AND is_approved = 1 ORDER BY pet_name ASC";
$stmt_pets = $conn->prepare($sql_pets);
$stmt_pets->bind_param("i", $client_id);
$stmt_pets->execute();
$result_pets = $stmt_pets->get_result();
while ($row = $result_pets->fetch_assoc()) {
    $pets[] = $row;
}
$stmt_pets->close();


// --- Fetch Existing Appointments ---
$appointments = [];
$sql_appointments = "
    SELECT a.*, p.pet_name, p.pet_species 
    FROM appointments a
    JOIN pets p ON a.pet_id = p.pet_id
    WHERE a.client_id = ?
    ORDER BY a.requested_date DESC, a.created_at DESC
";
$stmt_appts = $conn->prepare($sql_appointments);
$stmt_appts->bind_param("i", $client_id);
$stmt_appts->execute();
$result_appts = $stmt_appts->get_result();
while ($row = $result_appts->fetch_assoc()) {
    $appointments[] = $row;
}
$stmt_appts->close();

$conn->close();

// Helper for status styling
function get_status_badge($status) {
    switch ($status) {
        case 'Scheduled':
            return '<span class="bg-blue-100 text-blue-800 px-3 py-1 text-xs font-semibold rounded-full">Scheduled</span>';
        case 'Completed':
            return '<span class="bg-green-100 text-green-800 px-3 py-1 text-xs font-semibold rounded-full">Completed</span>';
        case 'Cancelled':
            return '<span class="bg-red-100 text-red-800 px-3 py-1 text-xs font-semibold rounded-full">Cancelled</span>';
        case 'Pending Review':
        default:
            return '<span class="bg-yellow-100 text-yellow-800 px-3 py-1 text-xs font-semibold rounded-full">Pending Review</span>';
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointments - PawPetCares</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f5f7fa;
        }
        
        .card {
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }

        .btn-primary {
            background-color: #4361ee;
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
        }
        
        .btn-primary:hover {
            background-color: #3a56d4;
        }
        
        input[type="date"], select, textarea, input[type="text"] {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 10px;
            width: 100%;
            transition: border-color 0.2s;
        }
        
        input[type="date"]:focus, select:focus, textarea:focus, input[type="text"]:focus {
            outline: none;
            border-color: #4361ee;
            box-shadow: 0 0 0 1px #4361ee;
        }

        .alert-success {
            background-color: #d1fae5;
            color: #065f46;
            border-left: 5px solid #10b981;
        }

        .alert-error {
            background-color: #fee2e2;
            color: #991b1b;
            border-left: 5px solid #ef4444;
        }

        .table-header {
            background-color: #f1f5f9;
        }

        /* Responsive Table Scroll */
        @media (max-width: 768px) {
            .table-container {
                overflow-x: auto;
            }
            .min-w-full {
                min-width: 700px;
            }
        }
    </style>
</head>
<body class="p-6 md:p-8 bg-gray-100">

    <h1 class="text-3xl font-bold text-gray-800 mb-2">Book Doctor's Check-up</h1>
    <p class="text-gray-500 mb-8">Request an appointment for a general health check or a specific concern for your pet.</p>

    <div class="card p-6 mb-8">
        <h2 class="text-xl font-semibold text-gray-700 mb-4 flex items-center">
            <i class="fas fa-calendar-plus mr-3 text-2xl text-[#4361ee]"></i> New Appointment Request
        </h2>

        <?php if (!empty($message)): ?>
            <div class="p-4 mb-4 rounded-lg <?php echo $message_type === 'success' ? 'alert-success' : 'alert-error'; ?>">
                <p class="font-medium"><?php echo htmlspecialchars($message); ?></p>
            </div>
        <?php endif; ?>

        <?php if (empty($pets)): ?>
            <div class="p-6 text-center text-gray-500 bg-red-50 rounded-lg border border-red-200">
                <i class="fas fa-exclamation-triangle text-3xl mb-3 text-red-500"></i>
                <p class="font-semibold">No Approved Pets Found.</p>
                <p class="text-sm">You must have an approved pet registered to request an appointment.</p>
                <a href="dashboard.php?action=add_pet" class="text-sm text-red-600 mt-2 block hover:underline">Register a pet now</a>
            </div>
        <?php else: ?>
            <form method="POST" action="appointments.php" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                
                <div>
                    <label for="pet_id" class="block text-sm font-medium text-gray-700 mb-2">Select Pet <span class="text-red-500">*</span></label>
                    <select id="pet_id" name="pet_id" required>
                        <option value="" disabled selected>Choose your pet...</option>
                        <?php foreach ($pets as $pet): ?>
                            <option value="<?php echo $pet['pet_id']; ?>">
                                <?php echo htmlspecialchars($pet['pet_name']) . ' (' . htmlspecialchars($pet['pet_species']) . ')'; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="appointment_type" class="block text-sm font-medium text-gray-700 mb-2">Type of Appointment <span class="text-red-500">*</span></label>
                    <select id="appointment_type" name="appointment_type" required>
                        <option value="" disabled selected>Select service type...</option>
                        <option value="General Check-up">General Check-up</option>
                        <option value="Sickness/Injury Consultation">Sickness/Injury Consultation</option>
                        <option value="Follow-up">Follow-up</option>
                        <option value="Other">Other (Specify below)</option>
                    </select>
                </div>

                <div>
                    <label for="requested_date" class="block text-sm font-medium text-gray-700 mb-2">Preferred Date <span class="text-red-500">*</span></label>
                    <input type="date" id="requested_date" name="requested_date" min="<?php echo date('Y-m-d'); ?>" required>
                </div>

                <div>
                    <label for="requested_time_slot" class="block text-sm font-medium text-gray-700 mb-2">Preferred Time Slot <span class="text-red-500">*</span></label>
                    <select id="requested_time_slot" name="requested_time_slot" required>
                        <option value="" disabled selected>Select time slot...</option>
                        <option value="Morning (9:00 AM - 12:00 PM)">Morning (9:00 AM - 12:00 PM)</option>
                        <option value="Afternoon (1:00 PM - 4:00 PM)">Afternoon (1:00 PM - 4:00 PM)</option>
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label for="reason_for_visit" class="block text-sm font-medium text-gray-700 mb-2">Reason for Visit (Optional)</label>
                    <textarea id="reason_for_visit" name="reason_for_visit" rows="3" placeholder="Briefly describe the pet's condition or reason for the appointment..."></textarea>
                </div>

                <div class="md:col-span-2 flex justify-end">
                    <button type="submit" name="submit_appointment" class="btn-primary">
                        <i class="fas fa-paper-plane mr-2"></i> Submit Appointment Request
                    </button>
                </div>
            </form>
        <?php endif; ?>
    </div>


    <div class="card p-6">
        <h2 class="text-xl font-semibold text-gray-700 mb-4 flex items-center">
            <i class="fas fa-list-alt mr-3 text-2xl text-gray-500"></i> My Appointment History
        </h2>
        
        <?php if (empty($appointments)): ?>
            <div class="p-6 text-center text-gray-500 bg-gray-50 rounded-lg border border-dashed border-gray-300">
                <i class="fas fa-box-open text-3xl mb-3 text-gray-400"></i>
                <p class="font-medium">No appointments found.</p>
                <p class="text-sm">Use the form above to book your first doctor's check-up.</p>
            </div>
        <?php else: ?>
            <div class="table-container shadow-md rounded-lg">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="table-header">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Pet</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Type</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Requested Date/Time</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Status</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Notes</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($appointments as $appt): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <p class="text-sm font-semibold text-gray-900"><?php echo htmlspecialchars($appt['pet_name']); ?></p>
                                <p class="text-xs text-gray-500"><?php echo htmlspecialchars($appt['pet_species']); ?></p>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                <?php echo htmlspecialchars($appt['appointment_type']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <p class="text-sm font-medium text-gray-900"><?php echo date('M d, Y', strtotime($appt['requested_date'])); ?></p>
                                <p class="text-xs text-gray-500"><?php echo htmlspecialchars($appt['requested_time_slot']); ?></p>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php echo get_status_badge(htmlspecialchars($appt['status'])); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 max-w-xs overflow-hidden text-ellipsis">
                                <?php 
                                $notes = htmlspecialchars($appt['staff_notes'] ?? $appt['reason_for_visit']); 
                                echo substr($notes, 0, 50) . (strlen($notes) > 50 ? '...' : '');
                                ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

</body>
</html>