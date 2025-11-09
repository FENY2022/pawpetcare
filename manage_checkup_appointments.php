<?php
// Note: session_start() and role check are already done in dashboard.php
// This file is included in an iframe, so direct access should be blocked 
// by checking the session, which is implicitly handled by dashboard.php.

// Assumes db.php contains get_db_connection(). We are including it here 
// as this script is loaded into an iframe.
include_once 'db.php'; 

// --- Function to Fetch Check-up Appointments ---
function get_checkup_appointments($conn) {
    // Select all non-vaccination appointments. This is a crucial distinction.
    $sql = "
        SELECT 
            a.appointment_id, 
            a.requested_date, 
            a.requested_time_slot, 
            a.reason_for_visit, 
            a.status,
            a.appointment_type,
            u.first_name AS client_first_name, 
            u.last_name AS client_last_name,
            p.pet_name AS pet_name,      /* FIX: Changed p.name to p.pet_name */
            p.pet_species AS species     /* FIX: Changed p.species to p.pet_species and aliased for consistency */
        FROM 
            appointments a
        INNER JOIN 
            users u ON a.client_id = u.id
        INNER JOIN 
            pets p ON a.pet_id = p.pet_id
        WHERE 
            a.appointment_type != 'Vaccination'
        ORDER BY 
            a.requested_date ASC, a.requested_time_slot ASC
    ";
    $result = $conn->query($sql);
    
    if ($result) {
        return $result->fetch_all(MYSQLI_ASSOC);
    } else {
        // In a real application, log the error
        // error_log("Database error fetching appointments: " . $conn->error);
        return [];
    }
}

// --- Handle Status Update Form Submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $conn = get_db_connection();
    $appointment_id = (int)$_POST['appointment_id'];
    $new_status = $_POST['status'];

    // Security: Validate the new status against the ENUM values
    $valid_statuses = ['Pending Review', 'Scheduled', 'Cancelled', 'Completed'];
    if (in_array($new_status, $valid_statuses)) {
        $update_sql = "UPDATE appointments SET status = ? WHERE appointment_id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("si", $new_status, $appointment_id);
        
        if ($stmt->execute()) {
            // Success message or refresh
            // Redirect to prevent form resubmission and show success message
            header("Location: manage_checkup_appointments.php?status=success");
            exit;
        } else {
            // Failure message or log error
            $error_message = "Error updating status: " . $conn->error;
        }
        $stmt->close();
    } else {
        $error_message = "Invalid status provided.";
    }
    $conn->close();
}

// Fetch data for display
$conn = get_db_connection();
$appointments = get_checkup_appointments($conn);
$conn->close(); 

// Check for status message from redirect
$status_message = '';
if (isset($_GET['status']) && $_GET['status'] === 'success') {
    $status_message = '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                          <strong class="font-bold">Success!</strong>
                          <span class="block sm:inline">Appointment status updated.</span>
                      </div>';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Doctor's Check-up Appointments</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Re-using dashboard styles for consistency */
        :root { --primary: #4361ee; }
        body { font-family: 'Inter', sans-serif; background-color: #f5f7fa; color: #1e293b; padding: 20px; }
        .card { background: white; border-radius: 12px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05); }
        /* Simple modal overlay styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: none; /* Hidden by default */
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        .modal-content {
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            position: relative;
        }
    </style>
</head>
<body>

    <div class="max-w-7xl mx-auto">
        <h2 class="text-3xl font-bold text-gray-800 mb-6 flex items-center">
            <i class="fas fa-stethoscope mr-3 text-primary"></i> Doctor's Check-up Appointments
        </h2>
        
        <?php echo $status_message; ?>
        <?php if (isset($error_message)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <strong class="font-bold">Error!</strong>
                <span class="block sm:inline"><?php echo htmlspecialchars($error_message); ?></span>
            </div>
        <?php endif; ?>

        <div class="card overflow-hidden">
            <div class="overflow-x-auto">
                <?php if (empty($appointments)): ?>
                    <p class="p-6 text-gray-500 text-center">No check-up appointments found.</p>
                <?php else: ?>
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Appointment ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Client & Pet</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type & Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reason for Visit</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($appointments as $appt): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($appt['appointment_id']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($appt['client_first_name'] . ' ' . $appt['client_last_name']); ?></div>
                                    <div class="text-xs text-gray-500">Pet: <?php echo htmlspecialchars($appt['pet_name']) . ' (' . htmlspecialchars($appt['species']) . ')'; ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 font-semibold"><?php echo htmlspecialchars($appt['appointment_type']); ?></div>
                                    <div class="text-sm text-gray-500"><?php echo date('M d, Y', strtotime($appt['requested_date'])) . ' at ' . htmlspecialchars($appt['requested_time_slot']); ?></div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 max-w-xs overflow-hidden truncate hover:overflow-visible hover:whitespace-normal">
                                    <?php echo htmlspecialchars($appt['reason_for_visit'] ?? 'N/A'); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php 
                                        $status_class = [
                                            'Pending Review' => 'bg-yellow-100 text-yellow-800',
                                            'Scheduled' => 'bg-blue-100 text-blue-800',
                                            'Cancelled' => 'bg-red-100 text-red-800',
                                            'Completed' => 'bg-green-100 text-green-800'
                                        ][$appt['status']] ?? 'bg-gray-100 text-gray-800';
                                    ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $status_class; ?>">
                                        <?php echo htmlspecialchars($appt['status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <form method="POST" class="inline-block">
                                        <input type="hidden" name="appointment_id" value="<?php echo $appt['appointment_id']; ?>">
                                        <input type="hidden" name="update_status" value="1">
                                        <select name="status" class="block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary sm:text-sm">
                                            <option value="Pending Review" <?php echo $appt['status'] == 'Pending Review' ? 'selected' : ''; ?>>Pending Review</option>
                                            <option value="Scheduled" <?php echo $appt['status'] == 'Scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                                            <option value="Completed" <?php echo $appt['status'] == 'Completed' ? 'selected' : ''; ?>>Completed</option>
                                            <option value="Cancelled" <?php echo $appt['status'] == 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                        </select>
                                        <button type="submit" class="mt-2 text-primary hover:text-primary-dark">
                                            <i class="fas fa-save mr-1"></i> Update
                                        </button>
                                    </form>
                                    <?php
                                        // Package the data as a JSON string for easy use in JavaScript
                                        $appt_json = json_encode([
                                            'id' => $appt['appointment_id'],
                                            'client' => htmlspecialchars($appt['client_first_name'] . ' ' . $appt['client_last_name']),
                                            'pet' => htmlspecialchars($appt['pet_name']),
                                            'species' => htmlspecialchars($appt['species']),
                                            'type' => htmlspecialchars($appt['appointment_type']),
                                            'date' => date('M d, Y', strtotime($appt['requested_date'])),
                                            'time' => htmlspecialchars($appt['requested_time_slot']),
                                            'reason' => htmlspecialchars($appt['reason_for_visit'] ?? 'N/A'),
                                            'status' => htmlspecialchars($appt['status']),
                                        ]);
                                    ?>
                                    <a href="#" onclick='showDetailsModal(<?php echo $appt_json; ?>); return false;' class="text-indigo-600 hover:text-indigo-900 ml-3">
                                        <i class="fas fa-eye mr-1"></i> View
                                    </a>
                                    </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div id="detailsModal" class="modal-overlay">
        <div class="modal-content">
            <h3 class="text-2xl font-bold text-gray-800 mb-4 border-b pb-2">Appointment Details</h3>
            
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div class="font-semibold text-gray-700">ID:</div>
                <div id="modal-id" class="text-gray-900"></div>

                <div class="font-semibold text-gray-700">Client:</div>
                <div id="modal-client" class="text-gray-900"></div>

                <div class="font-semibold text-gray-700">Pet (Species):</div>
                <div id="modal-pet-species" class="text-gray-900"></div>

                <div class="font-semibold text-gray-700">Type:</div>
                <div id="modal-type" class="text-gray-900"></div>

                <div class="font-semibold text-gray-700">Scheduled Date:</div>
                <div id="modal-date" class="text-gray-900"></div>

                <div class="font-semibold text-gray-700">Time Slot:</div>
                <div id="modal-time" class="text-gray-900"></div>

                <div class="font-semibold text-gray-700">Status:</div>
                <div id="modal-status" class="text-gray-900"></div>
            </div>

            <div class="mt-4">
                <div class="font-semibold text-gray-700 mb-1">Reason for Visit:</div>
                <p id="modal-reason" class="text-gray-900 p-2 bg-gray-50 border rounded"></p>
            </div>

            <div class="flex justify-end mt-6">
                <button onclick="document.getElementById('detailsModal').style.display='none';" class="bg-primary text-white px-4 py-2 rounded hover:bg-blue-700 transition duration-150">
                    Close
                </button>
            </div>
        </div>
    </div>

    <script>
        /**
         * Populates and displays the appointment details modal.
         * @param {Object} appt - The appointment data object (passed as JSON from PHP).
         */
        function showDetailsModal(appt) {
            // Populate the modal content with the appointment details
            document.getElementById('modal-id').innerText = appt.id;
            document.getElementById('modal-client').innerText = appt.client;
            document.getElementById('modal-pet-species').innerText = appt.pet + ' (' + appt.species + ')';
            document.getElementById('modal-type').innerText = appt.type;
            document.getElementById('modal-date').innerText = appt.date;
            document.getElementById('modal-time').innerText = appt.time;
            document.getElementById('modal-status').innerText = appt.status;
            document.getElementById('modal-reason').innerText = appt.reason;

            // Display the modal
            document.getElementById('detailsModal').style.display = 'flex';
        }
    </script>
    </body>
</html>