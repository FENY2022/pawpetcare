<?php
session_start();
include 'db.php'; // Includes the connection AND the notification function

// !! IMPORTANT: Assuming client's ID is stored in session
// You MUST have this set in your login code for this to work
if (!isset($_SESSION['user_id'])) {
    // If no client is logged in, redirect to login page
    header("Location: login.php"); // <-- CHANGE TO YOUR LOGIN PAGE
    exit();
}

$client_id = $_SESSION['user_id'];
$message = '';
$message_type = '';

// Get database connection
$conn = get_db_connection();

// --- Handle Client's New Vaccination Request ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'request_vaccine') {
    
    $pet_id = $_POST['pet_id'];
    $vaccine_name = $_POST['vaccine_name'];
    $notes = !empty($_POST['notes']) ? $_POST['notes'] : NULL;
    $status = "Pending"; // <-- ALWAYS "Pending" from client request

    // Security Check: Ensure the pet belongs to the logged-in client
    $pet_check_sql = "SELECT * FROM pets WHERE pet_id = ? AND client_id = ?";
    $pet_stmt = $conn->prepare($pet_check_sql);
    $pet_stmt->bind_param("ii", $pet_id, $client_id);
    $pet_stmt->execute();
    $pet_result = $pet_stmt->get_result();
    
    if ($pet_result->num_rows == 1) {
        try {
            // Insert the new vaccination record as "Pending"
            $sql = "INSERT INTO vaccinations (pet_id, status, vaccine_name, notes) 
                    VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("isss", $pet_id, $status, $vaccine_name, $notes);
            $stmt->execute();
            
            $new_vaccine_id = $conn->insert_id;
            
            // --- TRIGGER STAFF NOTIFICATION ---
            // This function now lives in db.php and is available
            create_notification_for_staff($conn, 'add_vaccine', $new_vaccine_id, $pet_id, $status);
            
            $message = 'Your vaccination request has been sent! A staff member will review it shortly.';
            $message_type = 'success';

        } catch (Exception $e) {
            $message = 'Error: Could not submit your request. ' . $e->getMessage();
            $message_type = 'error';
        }
    } else {
        $message = 'Error: Invalid pet selection.';
        $message_type = 'error';
    }
}

// --- Fetch Data for This Client's Display ---

// Fetch ONLY this client's pets for the dropdown
$pets_result = $conn->query("SELECT pet_id, pet_name FROM pets WHERE client_id = $client_id ORDER BY pet_name ASC");
$pets_list = $pets_result->fetch_all(MYSQLI_ASSOC);

// Fetch ONLY this client's vaccination records
$vaccinations_result = $conn->query("
    SELECT 
        p.pet_name, 
        v.*
    FROM vaccinations v
    JOIN pets p ON v.pet_id = p.pet_id
    WHERE p.client_id = $client_id
    ORDER BY v.next_due ASC, v.date_given DESC
");
$vaccinations_list = $vaccinations_result->fetch_all(MYSQLI_ASSOC);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Pet Vaccinations</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f5f7fa; }
        .card { background: white; border-radius: 12px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05); }
    </style>
</head>
<body class="p-6 md:p-8">

    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
        <h1 class="text-3xl font-bold text-gray-800">My Pet Vaccinations</h1>
        <button id="open-modal-btn" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg shadow-md transition duration-300 flex items-center">
            <i class="fas fa-plus mr-2"></i> Request New Vaccination
        </button>
    </div>

    <?php if ($message): ?>
    <div id="alert-message" class="p-4 mb-4 rounded-lg <?php echo $message_type == 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
        <i class="fas <?php echo $message_type == 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?> mr-2"></i>
        <?php echo htmlspecialchars($message); ?>
    </div>
    <?php endif; ?>

    <div class="card p-6">
        <h2 class="text-xl font-semibold mb-4">Vaccination History</h2>
        <div class="overflow-x-auto">
            <table class="w-full min-w-max text-left" id="vaccinations-table">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="p-4 text-sm font-semibold text-gray-600 uppercase">Pet Name</th>
                        <th class="p-4 text-sm font-semibold text-gray-600 uppercase">Vaccine</th>
                        <th class="p-4 text-sm font-semibold text-gray-600 uppercase">Status</th>
                        <th class="p-4 text-sm font-semibold text-gray-600 uppercase">Date Given</th>
                        <th class="p-4 text-sm font-semibold text-gray-600 uppercase">Next Due</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($vaccinations_list)): ?>
                        <tr>
                            <td colspan="5" class="p-4 text-center text-gray-500">You have no vaccination records.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($vaccinations_list as $v): 
                            $status_class = '';
                            $status_text = htmlspecialchars($v['status']);
                            if ($v['status'] == 'Pending') { $status_class = 'bg-blue-100 text-blue-800'; }
                            elseif ($v['status'] == 'Scheduled') { $status_class = 'bg-yellow-100 text-yellow-800'; }
                            elseif ($v['status'] == 'Completed') { $status_class = 'bg-green-100 text-green-800'; }
                            elseif ($v['status'] == 'Cancelled') { $status_class = 'bg-gray-100 text-gray-800'; }
                        ?>
                        <tr class="hover:bg-gray-50">
                            <td class="p-4 font-medium text-gray-900"><?php echo htmlspecialchars($v['pet_name']); ?></td>
                            <td class="p-4 text-gray-600"><?php echo htmlspecialchars($v['vaccine_name']); ?></td>
                            <td class="p-4">
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $status_class; ?>">
                                    <?php echo $status_text; ?>
                                </span>
                            </td>
                            <td class="p-4 text-gray-600"><?php echo !empty($v['date_given']) ? date('M d, Y', strtotime($v['date_given'])) : 'N/A'; ?></td>
                            <td class="p-4 text-gray-600"><?php echo !empty($v['next_due']) ? date('M d, Y', strtotime($v['next_due'])) : 'N/A'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="vaccine-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 hidden z-50">
        <div class="card w-full max-w-lg">
            <div class="flex justify-between items-center p-5 border-b border-gray-200">
                <h3 class="text-xl font-semibold text-gray-900" id="modal-title">Request New Vaccination</h3>
                <button id="close-modal-btn" class="text-gray-400 hover:text-gray-600">&times;</button>
            </div>
            
            <form id="vaccine-form" action="my_vaccinations.php" method="POST" class="p-6 space-y-4">
                <input type="hidden" name="action" value="request_vaccine">

                <div>
                    <label for="pet-id" class="block mb-2 text-sm font-medium text-gray-700">Which pet is this for?</label>
                    <select id="pet-id" name="pet_id" class="w-full p-2.5 border border-gray-300 rounded-lg" required>
                        <option value="" disabled selected>Select your pet...</option>
                        <?php foreach ($pets_list as $pet): ?>
                            <option value="<?php echo $pet['pet_id']; ?>">
                                <?php echo htmlspecialchars($pet['pet_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="vaccine-name" class="block mb-2 text-sm font-medium text-gray-700">Vaccine</label>
                    <select id="vaccine-name" name="vaccine_name" class="w-full p-2.5 border border-gray-300 rounded-lg" required>
                        <option value="" disabled selected>Select a vaccine...</option>
                        <option value="Anti-Rabies">Anti-Rabies</option>
                        <option value="5-in-1 (DHPPi-L)">5-in-1 (DHPPi-L)</option>
                        <option value="8-in-1">8-in-1</option>
                        <option value="Deworming">Deworming</option>
                        <option value="Kennel Cough">Kennel Cough</option>
                        <option value="Other">Other (Specify in notes)</option>
                    </select>
                </div>

                <div>
                    <label for="notes" class="block mb-2 text-sm font-medium text-gray-700">Notes (Optional)</label>
                    <textarea id="notes" name="notes" rows="3" placeholder="e.g., I am available on weekends..." class="w-full p-2.5 border border-gray-300 rounded-lg"></textarea>
                </div>
                
                <div class="flex justify-end items-center pt-4 border-t border-gray-200">
                    <button type="button" id="cancel-btn" class="text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-lg px-5 py-2.5 mr-3">Cancel</button>
                    <button type="submit" class="text-white bg-blue-600 hover:bg-blue-700 rounded-lg px-5 py-2.5">Submit Request</button>
                </div>
            </form>
        </div>
    </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('vaccine-modal');
    const openModalBtn = document.getElementById('open-modal-btn');
    const closeModalBtn = document.getElementById('close-modal-btn');
    const cancelBtn = document.getElementById('cancel-btn');

    const showModal = () => modal.classList.remove('hidden');
    const hideModal = () => modal.classList.add('hidden');

    openModalBtn.addEventListener('click', showModal);
    closeModalBtn.addEventListener('click', hideModal);
    cancelBtn.addEventListener('click', hideModal);

    // Hide success/error message after 5 seconds
    setTimeout(() => {
        const alertMessage = document.getElementById('alert-message');
        if (alertMessage) {
            alertMessage.style.display = 'none';
        }
    }, 5000);
});
</script>

</body>
</html>