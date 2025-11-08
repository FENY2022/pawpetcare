<?php
session_start();
include 'db.php'; // Includes connection and helper functions

// --- 1. ACCESS CONTROL ---
// Staff must have user_rules 1 (Healthcare) or 2 (Admin)
if (!isset($_SESSION['loggedin']) || ($_SESSION['user_rules'] != 1 && $_SESSION['user_rules'] != 2)) {
    header("Location: login.php"); // Redirect non-staff users
    exit;
}

$message = '';
$message_type = '';
$conn = get_db_connection();
$staff_id = $_SESSION['user_id'];
$staff_name = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];


// --- 2. HANDLE FORM SUBMISSION (UPDATE STATUS) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'schedule_vaccine') {
    
    $vaccination_id = (int)$_POST['vaccination_id'];
    $new_status = $_POST['new_status'];
    $date_input = $_POST['date_input'] ?? NULL; // Can be date_given or next_due
    $batch_no = !empty($_POST['batch_no']) ? $_POST['batch_no'] : NULL;
    $admin_notes = !empty($_POST['admin_notes']) ? $_POST['admin_notes'] : NULL;

    // Determine which date column to update based on status
    $date_column = '';
    if ($new_status == 'Scheduled') {
        $date_column = 'next_due';
    } elseif ($new_status == 'Completed') {
        $date_column = 'date_given';
    }

    try {
        // Prepare SQL statement (dynamically set date column)
        // Use a placeholder for the date column name is not safe, so we validate it
        if ($date_column) {
            $sql = "UPDATE vaccinations SET status = ?, $date_column = ?, batch_no = ?, administered_by = ?, notes = CONCAT(COALESCE(notes, ''), '\n\nAdmin Note: ', ?) WHERE id = ? AND status = 'Pending'";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssssi", $new_status, $date_input, $batch_no, $staff_name, $admin_notes, $vaccination_id);
        } else {
             // For 'Cancelled' status which has no date column
            $sql = "UPDATE vaccinations SET status = ?, batch_no = ?, administered_by = ?, notes = CONCAT(COALESCE(notes, ''), '\n\nAdmin Note: ', ?) WHERE id = ? AND status = 'Pending'";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssi", $new_status, $batch_no, $staff_name, $admin_notes, $vaccination_id);
        }
        
        
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $message = "Vaccination request #$vaccination_id successfully updated to **$new_status**.";
            $message_type = 'success';
            
            // Optional: Create a notification back to the client about the new status
            // (You would need a create_notification_for_client function for this)

        } else {
            $message = 'Error updating vaccination request. It may have already been processed.';
            $message_type = 'error';
        }
        $stmt->close();
        
    } catch (Exception $e) {
        $message = 'Database Error: ' . $e->getMessage();
        $message_type = 'error';
    }
}


// --- 3. FETCH PENDING VACCINATION REQUESTS ---
$pending_requests = [];
$sql_pending = "
    SELECT 
        v.id AS vaccination_id, 
        v.vaccine_name, 
        v.notes AS client_notes,
        p.pet_id,
        p.pet_name,
        p.pet_image_path,
        u.first_name,
        u.last_name,
        u.contact_number,
        u.email
    FROM vaccinations v
    JOIN pets p ON v.pet_id = p.pet_id
    JOIN users u ON p.client_id = u.id
    WHERE v.status = 'Pending'
    ORDER BY v.id ASC
";
$result_pending = $conn->query($sql_pending);
if ($result_pending) {
    while ($row = $result_pending->fetch_assoc()) {
        $pending_requests[] = $row;
    }
}


// --- 4. CHECK FOR URL PARAMETERS (FROM NOTIFICATION) ---
$highlight_id = (int)($_GET['vaccine_id'] ?? 0);
$highlight_pet_id = (int)($_GET['pet_id'] ?? 0);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Manage Vaccinations</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f5f7fa; }
        .card { background: white; border-radius: 12px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05); }
        .highlight-row { background-color: #ffe8cc; border: 2px solid #ff9900; } /* Highlight for notification click */
    </style>
</head>
<body class="p-6 md:p-8">

    <h1 class="text-3xl font-bold text-gray-800 mb-6">💉 Manage Vaccination Requests</h1>

    <?php if ($message): ?>
    <div id="alert-message" class="p-4 mb-4 rounded-lg <?php echo $message_type == 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
        <i class="fas <?php echo $message_type == 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?> mr-2"></i>
        <?php echo $message; ?>
    </div>
    <?php endif; ?>

    <div class="card p-6">
        <h2 class="text-xl font-semibold mb-4 text-blue-800">Pending Requests (<?php echo count($pending_requests); ?>)</h2>
        
        <?php if (empty($pending_requests)): ?>
            <p class="text-gray-500 text-center p-8">🎉 No pending vaccination requests! Great job.</p>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full min-w-max text-left" id="pending-requests-table">
                    <thead class="bg-blue-50 border-b border-blue-200">
                        <tr>
                            <th class="p-4 text-sm font-semibold text-blue-700 uppercase">Req ID</th>
                            <th class="p-4 text-sm font-semibold text-blue-700 uppercase">Pet Image</th> 
                            <th class="p-4 text-sm font-semibold text-blue-700 uppercase">Pet Name</th>
                            <th class="p-4 text-sm font-semibold text-blue-700 uppercase">Client Name</th>
                            <th class="p-4 text-sm font-semibold text-blue-700 uppercase">Vaccine</th>
                            <th class="p-4 text-sm font-semibold text-blue-700 uppercase">Client Notes</th>
                            <th class="p-4 text-sm font-semibold text-blue-700 uppercase">Action</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($pending_requests as $request): ?>
                            <?php 
                                $is_highlighted = ($request['vaccination_id'] == $highlight_id);
                                $row_class = $is_highlighted ? 'highlight-row' : 'hover:bg-gray-50';
                            ?>
                            <tr class="<?php echo $row_class; ?>" data-id="<?php echo $request['vaccination_id']; ?>">
                                <td class="p-4 font-bold text-gray-900">#<?php echo $request['vaccination_id']; ?></td>
                                
                                <td class="p-4">
                                    <?php if (!empty($request['pet_image_path'])): ?>
                                        <img src="<?php echo htmlspecialchars($request['pet_image_path']); ?>" 
                                             alt="<?php echo htmlspecialchars($request['pet_name']); ?>'s photo" 
                                             class="w-12 h-12 rounded-full object-cover border-2 border-gray-200">
                                    <?php else: ?>
                                        <span class="flex items-center justify-center w-12 h-12 rounded-full bg-gray-200 text-gray-500">
                                            <i class="fas fa-paw"></i>
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td class="p-4 text-gray-600">
                                    <span class="font-medium"><?php echo htmlspecialchars($request['pet_name']); ?></span> (ID: <?php echo $request['pet_id']; ?>)
                                </td>
                                <td class="p-4 text-gray-600">
                                    <?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?>
                                </td>
                                <td class="p-4 text-gray-600 font-medium"><?php echo htmlspecialchars($request['vaccine_name']); ?></td>
                                <td class="p-4 text-sm text-gray-500 max-w-xs truncate" title="<?php echo htmlspecialchars($request['client_notes']); ?>">
                                    <?php echo htmlspecialchars($request['client_notes'] ?? 'No notes'); ?>
                                </td>
                                <td class="p-4">
                                    <button onclick="openModal(<?php echo htmlspecialchars(json_encode($request)); ?>)" 
                                            class="text-xs bg-indigo-500 hover:bg-indigo-600 text-white font-semibold py-1 px-3 rounded-full transition duration-150">
                                        Schedule / Approve
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>


    <div id="schedule-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 hidden z-50">
        <div class="card w-full max-w-xl max-h-[90vh] flex flex-col">
            
            <div class="flex-shrink-0 flex justify-between items-center p-5 border-b border-gray-200 bg-indigo-50">
                <h3 class="text-xl font-bold text-indigo-800">Update Vaccination Status</h3>
                <button id="close-modal-btn" class="text-gray-600 hover:text-gray-800 text-2xl">&times;</button>
            </div>
            
            <form id="schedule-form" action="admin_vaccinations.php" method="POST" class="flex-grow flex flex-col overflow-hidden">
                
                <div class="flex-grow p-6 space-y-4 overflow-y-auto">
                    <input type="hidden" name="action" value="schedule_vaccine">
                    <input type="hidden" name="vaccination_id" id="modal-vaccination-id" value="">
                    
                    <div class="flex justify-center">
                        <img id="modal-pet-image" src="" alt="Pet Photo" class="w-24 h-24 rounded-full object-cover border-4 border-gray-200">
                        <span id="modal-pet-placeholder" class="hidden items-center justify-center w-24 h-24 rounded-full bg-gray-200 text-gray-500 text-3xl">
                            <i class="fas fa-paw"></i>
                        </span>
                    </div>
                    
                    <div class="border p-4 rounded-lg bg-gray-50">
                        <p class="text-sm text-gray-700">Request ID: <span id="modal-req-id" class="font-semibold"></span></p>
                        <p class="text-sm text-gray-700">Pet: <span id="modal-pet-name" class="font-semibold"></span></p>
                        <p class="text-sm text-gray-700">Vaccine: <span id="modal-vaccine-name" class="font-semibold text-blue-600"></span></p>
                        <p class="text-sm text-gray-700 mt-2">Client Notes: <span id="modal-client-notes" class="text-gray-500 italic"></span></p>
                    </div>

                    <div>
                        <label for="new-status" class="block mb-2 text-sm font-medium text-gray-700">Status Update</label>
                        <select id="new-status" name="new_status" class="w-full p-2.5 border border-gray-300 rounded-lg" required onchange="toggleDateLabel(this.value)">
                            <option value="Scheduled">Scheduled (Set Next Due Date)</option>
                            <option value="Completed">Completed (Set Date Given)</option>
                            <option value="Cancelled">Cancelled (No Date Needed)</option>
                        </select>
                    </div>

                    <div id="date-input-group">
                        <label for="date-input" id="date-label" class="block mb-2 text-sm font-medium text-gray-700">Next Due Date (Schedule)</label>
                        <input type="date" id="date-input" name="date_input" class="w-full p-2.5 border border-gray-300 rounded-lg">
                    </div>
                    
                    <div id="batch-no-group">
                        <label for="batch-no" class="block mb-2 text-sm font-medium text-gray-700">Batch/Certificate No. (Optional)</label>
                        <input type="text" id="batch-no" name="batch_no" placeholder="Enter Batch/Certificate Number" class="w-full p-2.5 border border-gray-300 rounded-lg">
                    </div>

                    <div>
                        <label for="admin-notes" class="block mb-2 text-sm font-medium text-gray-700">Admin/Healthcare Notes (e.g., reason for cancellation or schedule details)</label>
                        <textarea id="admin-notes" name="admin_notes" rows="3" placeholder="Add administrative notes here..." class="w-full p-2.5 border border-gray-300 rounded-lg"></textarea>
                    </div>
                </div>
                
                <div class="flex-shrink-0 flex justify-end items-center p-6 pt-4 border-t border-gray-200">
                    <button type="button" onclick="hideModal()" class="text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-lg px-5 py-2.5 mr-3">Cancel</button>
                    <button type="submit" class="text-white bg-green-600 hover:bg-green-700 rounded-lg px-5 py-2.5 font-semibold">Update Request</button>
                </div>
            </form>
        </div>
    </div>


<script>
    const modal = document.getElementById('schedule-modal');
    const closeBtn = document.getElementById('close-modal-btn');
    const dateInputGroup = document.getElementById('date-input-group');
    const batchNoGroup = document.getElementById('batch-no-group');
    const dateLabel = document.getElementById('date-label');
    const dateInput = document.getElementById('date-input');

    // Function to show the modal and populate data
    function openModal(request) {
        document.getElementById('modal-vaccination-id').value = request.vaccination_id;
        document.getElementById('modal-req-id').textContent = request.vaccination_id;
        document.getElementById('modal-pet-name').textContent = request.pet_name;
        document.getElementById('modal-vaccine-name').textContent = request.vaccine_name;
        document.getElementById('modal-client-notes').textContent = request.client_notes || 'No notes provided.';

        const petImage = document.getElementById('modal-pet-image');
        const petPlaceholder = document.getElementById('modal-pet-placeholder');
        if (request.pet_image_path) {
            petImage.src = request.pet_image_path;
            petImage.alt = request.pet_name + "'s photo";
            petImage.classList.remove('hidden');
            petPlaceholder.classList.add('hidden');
        } else {
            petImage.classList.add('hidden');
            petPlaceholder.classList.remove('hidden');
        }

        // Reset form to default status
        document.getElementById('new-status').value = 'Scheduled';
        dateInput.required = true;
        dateLabel.textContent = 'Next Due Date (Schedule)';
        dateInputGroup.classList.remove('hidden');
        batchNoGroup.classList.add('hidden'); // Hide batch no by default for scheduling
        document.getElementById('admin-notes').value = ''; // Clear notes
        document.getElementById('batch-no').value = ''; // Clear batch no
        document.getElementById('date-input').value = ''; // Clear date

        modal.classList.remove('hidden');

        // Optional: Scroll to the top of the modal content
        // MODIFICATION: Scroll the *inner* content div, not the card
        modal.querySelector('.overflow-y-auto').scrollTop = 0;
    }

    function hideModal() {
        modal.classList.add('hidden');
        dateInput.required = false; // Reset required state
    }
    
    // Function to dynamically change the date input label and required state
    function toggleDateLabel(status) {
        if (status === 'Scheduled') {
            dateLabel.textContent = 'Next Due Date (Schedule)';
            dateInput.required = true;
            dateInputGroup.classList.remove('hidden');
            batchNoGroup.classList.add('hidden'); // Hide Batch No.
        } else if (status === 'Completed') {
            dateLabel.textContent = 'Date Given (Completion)';
            dateInput.required = true;
            dateInputGroup.classList.remove('hidden');
            batchNoGroup.classList.remove('hidden'); // Show Batch No.
        } else if (status === 'Cancelled') {
            dateInput.required = false;
            dateInputGroup.classList.add('hidden');
            batchNoGroup.classList.add('hidden'); // Hide Batch No.
        }
    }

    closeBtn.addEventListener('click', hideModal);
    
    // --- Highlight Row Logic (From Notification Click) ---
    document.addEventListener('DOMContentLoaded', function() {
        // Check if a row needs to be highlighted (from URL parameters)
        const highlightId = parseInt(new URLSearchParams(window.location.search).get('vaccine_id'));
        if (highlightId) {
            const rowToHighlight = document.querySelector(`tr[data-id="${highlightId}"]`);
            if (rowToHighlight) {
                // Remove highlight after a few seconds
                setTimeout(() => {
                    rowToHighlight.classList.remove('highlight-row');
                }, 5000); 

                // Automatically open the modal for the highlighted request
                // We need to find the request data, which is tricky without re-fetching
                // For simplicity, we'll just focus on the row for now.
                // A better approach would be to pass all data to JS or fetch only the highlighted one.
                
                // OPTIONAL: Auto-scroll to the row
                rowToHighlight.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }

        // Fix for date column not updating on initial modal load
        toggleDateLabel(document.getElementById('new-status').value);
    });
</script>

</body>
</html>