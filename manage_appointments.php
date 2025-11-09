<?php
session_start();
include_once 'db.php'; // Includes get_db_connection()

// Security: Ensure only Healthcare (1) or Admin (2) can access
if (!isset($_SESSION['loggedin']) || ($_SESSION['user_rules'] != 1 && $_SESSION['user_rules'] != 2)) {
    die("Access Denied. You do not have permission to view this page.");
}

$conn = get_db_connection();
$message = ''; // For success/error messages

// --- 1. HANDLE FORM SUBMISSIONS (UPDATE/SCHEDULE/CANCEL) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['vaccination_id'])) {
    $action = $_POST['action'] ?? '';
    $vaccination_id = (int)$_POST['vaccination_id'];
    $admin_notes = $_POST['admin_notes'] ?? '';
    
    // Append new notes to existing notes
    $note_prefix = "\n\nAdmin Note (" . date('Y-m-d') . "): ";
    $full_admin_notes = $note_prefix . $admin_notes;

    try {
        if ($action == 'schedule') {
            $schedule_date = $_POST['schedule_date'];
            $administered_by = $_POST['administered_by'];
            
            $sql = "UPDATE vaccinations 
                    SET status = 'Scheduled', next_due = ?, administered_by = ?, notes = COALESCE(CONCAT(notes, ?), ?) 
                    WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssi", $schedule_date, $administered_by, $full_admin_notes, $full_admin_notes, $vaccination_id);
            $message = "Vaccination successfully scheduled.";

        } elseif ($action == 'complete') {
            $date_given = $_POST['date_given'];
            $batch_no = $_POST['batch_no'];
            $administered_by = $_POST['administered_by'];

            $sql = "UPDATE vaccinations 
                    SET status = 'Completed', date_given = ?, batch_no = ?, administered_by = ?, notes = COALESCE(CONCAT(notes, ?), ?) 
                    WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssi", $date_given, $batch_no, $administered_by, $full_admin_notes, $full_admin_notes, $vaccination_id);
            $message = "Vaccination successfully marked as completed.";

        } elseif ($action == 'cancel') {
            $sql = "UPDATE vaccinations 
                    SET status = 'Cancelled', notes = COALESCE(CONCAT(notes, ?), ?) 
                    WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssi", $full_admin_notes, $full_admin_notes, $vaccination_id);
            $message = "Vaccination successfully cancelled.";
        }
        
        if (isset($stmt)) {
            $stmt->execute();
            $stmt->close();
            $_SESSION['message'] = ['text' => $message, 'type' => 'success'];
        }

    } catch (Exception $e) {
        $_SESSION['message'] = ['text' => 'An error occurred: ' . $e->getMessage(), 'type' => 'error'];
    }
    
    // Redirect to self to avoid form resubmission
    header("Location: manage_appointments.php?status=" . urlencode($_GET['status'] ?? 'Pending'));
    exit;
}

// Check for success/error messages from redirect
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}


// --- 2. FETCH DATA FOR DISPLAY ---
$current_status = $_GET['status'] ?? 'Pending'; // Default to 'Pending'
$allowed_statuses = ['Pending', 'Scheduled', 'Completed', 'Cancelled', 'All'];

if (!in_array($current_status, $allowed_statuses)) {
    $current_status = 'Pending';
}

$vaccinations = [];
$sql = "SELECT v.*, p.pet_name, u.first_name, u.last_name, u.contact_number 
        FROM vaccinations v
        JOIN pets p ON v.pet_id = p.pet_id
        JOIN users u ON p.client_id = u.id"; // Join on pets.client_id

if ($current_status != 'All') {
    $sql .= " WHERE v.status = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $current_status);
} else {
    $stmt = $conn->prepare($sql);
}

$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $vaccinations[] = $row;
}
$stmt->close();
$conn->close();

// Helper function for status badges
function getStatusBadge($status) {
    switch ($status) {
        case 'Pending':
            return 'bg-yellow-100 text-yellow-800';
        case 'Scheduled':
            return 'bg-blue-100 text-blue-800';
        case 'Completed':
            return 'bg-green-100 text-green-800';
        case 'Cancelled':
            return 'bg-red-100 text-red-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Appointments</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; }
        /* Modal styles */
        .modal { transition: opacity 0.25s ease; }
        .modal-content { transition: transform 0.25s ease; }
        /* Thin Scrollbar */
        .thin-scrollbar {
            scrollbar-width: thin;
            scrollbar-color: #94a3b8 #f1f5f9;
        }
        .thin-scrollbar::-webkit-scrollbar { width: 6px; }
        .thin-scrollbar::-webkit-scrollbar-track { background: #f1f5f9; }
        .thin-scrollbar::-webkit-scrollbar-thumb {
            background-color: #94a3b8;
            border-radius: 20px;
        }
    </style>
</head>
<body class="bg-gray-100">

    <div class="p-4 md:p-8 max-w-7xl mx-auto">
        
        <h1 class="text-3xl font-bold text-gray-800 mb-6">Manage Vaccination Appointments</h1>

        <?php if (!empty($message)): ?>
            <div class="mb-4 p-4 rounded-lg <?php echo ($message['type'] == 'success') ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>" role="alert">
                <?php echo htmlspecialchars($message['text']); ?>
            </div>
        <?php endif; ?>

        <div class="mb-5">
            <div class="border-b border-gray-200">
                <nav class="-mb-px flex space-x-6 overflow-x-auto" aria-label="Tabs">
                    <?php foreach ($allowed_statuses as $status): ?>
                        <a href="manage_appointments.php?status=<?php echo $status; ?>" 
                           class="py-3 px-1 border-b-2 font-medium text-sm whitespace-nowrap
                                  <?php echo ($current_status == $status)
                                      ? 'border-blue-600 text-blue-700'
                                      : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?>">
                            <?php echo $status; ?>
                        </a>
                    <?php endforeach; ?>
                </nav>
            </div>
        </div>

        <div class="bg-white shadow-md rounded-xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Pet / Owner</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Contact</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Vaccine</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Date</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($vaccinations)): ?>
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                    No vaccinations found for "<?php echo htmlspecialchars($current_status); ?>" status.
                                Ttd>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($vaccinations as $v): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-semibold text-gray-900"><?php echo htmlspecialchars($v['pet_name']); ?></div>
                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($v['first_name'] . ' ' . $v['last_name']); ?></div>
                                    </td>
                              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"><?php echo htmlspecialchars($v['contact_number'] ?? 'N/A'); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800 font-medium"><?php echo htmlspecialchars($v['vaccine_name']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                        <?php
                                        if ($v['status'] == 'Scheduled') {
                                            echo 'Scheduled: ' . date('M d, Y', strtotime($v['next_due']));
                                        } elseif ($v['status'] == 'Completed') {
                                            echo 'Given: ' . date('M d, Y', strtotime($v['date_given']));
                                        } else {
                                            echo 'Requested'; // For Pending/Cancelled
                                        }
                                        ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo getStatusBadge($v['status']); ?>">
                                            <?php echo htmlspecialchars($v['status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                        <?php if ($v['status'] == 'Pending'): ?>
                                            <button onclick="openModal('schedule', <?php echo $v['id']; ?>, '<?php echo htmlspecialchars($v['pet_name']); ?>', `<?php echo htmlspecialchars($v['notes']); ?>`)" class="text-blue-600 hover:text-blue-800">Schedule</button>
                                        <?php endif; ?>
                                        <?php if ($v['status'] == 'Scheduled'): ?>
                                            <button onclick="openModal('complete', <?php echo $v['id']; ?>, '<?php echo htmlspecialchars($v['pet_name']); ?>', `<?php echo htmlspecialchars($v['notes']); ?>`)" class="text-green-600 hover:text-green-800">Complete</button>
                                        <?php endif; ?>
                                        <?php if ($v['status'] == 'Pending' || $v['status'] == 'Scheduled'): ?>
                                            <button onclick="openModal('cancel', <?php echo $v['id']; ?>, '<?php echo htmlspecialchars($v['pet_name']); ?>', `<?php echo htmlspecialchars($v['notes']); ?>`)" class="text-red-600 hover:text-red-800">Cancel</button>
                                        <?php endif; ?>
                                        <button onclick="openModal('view', <?php echo $v['id']; ?>, '<?php echo htmlspecialchars($v['pet_name']); ?>', `<?php echo htmlspecialchars($v['notes']); ?>`)" class="text-gray-600 hover:text-gray-800">View</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="actionModal" class="modal fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 opacity-0 pointer-events-none">
        <div class="modal-content bg-white rounded-xl shadow-lg w-full max-w-lg p-6 transform -translate-y-10">
            <div class="flex justify-between items-center border-b pb-3 mb-5">
                <h3 class="text-xl font-semibold text-gray-800" id="modalTitle">Update Appointment</h3>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">&times;</button>
            </div>
            
            <form id="modalForm" method="POST" action="manage_appointments.php?status=<?php echo htmlspecialchars($current_status); ?>">
                <input type="hidden" name="vaccination_id" id="vaccination_id">
                <input type="hidden" name="action" id="action_type">
                
                <div class="space-y-4">
                    <div id="schedule_fields" class="hidden space-y-4">
                        <div>
                            <label for="schedule_date" class="block text-sm font-medium text-gray-700 mb-1">Schedule Date</label>
                            <input type="date" name="schedule_date" id="schedule_date" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label for="schedule_administered_by" class="block text-sm font-medium text-gray-700 mb-1">Administered By</label>
                            <input type="text" name="administered_by" id="schedule_administered_by" value="<?php echo htmlspecialchars($_SESSION['first_name'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>

                    <div id="complete_fields" class="hidden space-y-4">
                        <div>
                            <label for="date_given" class="block text-sm font-medium text-gray-700 mb-1">Date Given</label>
                            <input type="date" name="date_given" id="date_given" value="<?php echo date('Y-m-d'); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label for="batch_no" class="block text-sm font-medium text-gray-700 mb-1">Batch No.</label>
                            <input type="text" name="batch_no" id="batch_no" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </div>
                         <div>
                            <label for="complete_administered_by" class="block text-sm font-medium text-gray-700 mb-1">Administered By</label>
                            <input type="text" name="administered_by" id="complete_administered_by" value="<?php echo htmlspecialchars($_SESSION['first_name'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>

                    <div id="view_notes" class="hidden">
                         <h4 class="block text-sm font-medium text-gray-700 mb-1">Request/Admin Notes</h4>
                         <div id="notes_display" class="w-full p-3 h-32 bg-gray-50 border border-gray-200 rounded-md overflow-y-auto thin-scrollbar whitespace-pre-wrap"></div>
                    </div>
                    
                    <div id="notes_field" class="hidden">
                        <label for="admin_notes" class="block text-sm font-medium text-gray-700 mb-1">Add Note (Admin)</label>
                        <textarea name="admin_notes" id="admin_notes" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" placeholder="Add a note for this action..."></textarea>
                    </div>

                </div>

                <div class="mt-6 pt-4 border-t flex justify-end space-x-3">
                    <button type="button" onclick="closeModal()" class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none">
                        Cancel
                    </button>
                    <button type="submit" id="modalSubmitButton" class="py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none">
                        Submit
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    const modal = document.getElementById('actionModal');
    const modalForm = document.getElementById('modalForm');
    const modalTitle = document.getElementById('modalTitle');
    const modalSubmitButton = document.getElementById('modalSubmitButton');
    
    // Hidden field groups
    const scheduleFields = document.getElementById('schedule_fields');
    const completeFields = document.getElementById('complete_fields');
    const notesField = document.getElementById('notes_field');
    const viewNotes = document.getElementById('view_notes');
    const notesDisplay = document.getElementById('notes_display');

    // Input fields
    const vaccinationIdInput = document.getElementById('vaccination_id');
    const actionTypeInput = document.getElementById('action_type');
    const adminNotesInput = document.getElementById('admin_notes');

    function openModal(action, id, petName, currentNotes) {
        // Reset form and hide all optional fields
        modalForm.reset();
        scheduleFields.classList.add('hidden');
        completeFields.classList.add('hidden');
        notesField.classList.add('hidden');
        viewNotes.classList.add('hidden');
        modalSubmitButton.style.display = 'block'; // Show submit button by default
        
        // Set common values
        vaccinationIdInput.value = id;
        actionTypeInput.value = action;
        notesDisplay.textContent = currentNotes || 'No notes available.';
        adminNotesInput.placeholder = 'Add a note...';

        if (action === 'schedule') {
            modalTitle.textContent = `Schedule Vaccine for ${petName}`;
            scheduleFields.classList.remove('hidden');
            notesField.classList.remove('hidden');
            modalSubmitButton.className = "py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none";
            modalSubmitButton.textContent = "Schedule";
            
        } else if (action === 'complete') {
            modalTitle.textContent = `Complete Vaccine for ${petName}`;
            completeFields.classList.remove('hidden');
            notesField.classList.remove('hidden');
            modalSubmitButton.className = "py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none";
            modalSubmitButton.textContent = "Mark as Complete";
            
        } else if (action === 'cancel') {
            modalTitle.textContent = `Cancel Vaccine for ${petName}`;
            notesField.classList.remove('hidden');
            adminNotesInput.placeholder = 'Reason for cancellation...';
            modalSubmitButton.className = "py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none";
            modalSubmitButton.textContent = "Confirm Cancellation";
            
        } else if (action === 'view') {
             modalTitle.textContent = `Notes for ${petName}`;
             viewNotes.classList.remove('hidden');
             modalSubmitButton.style.display = 'none'; // Hide submit button
        }
        
        // Show modal
        modal.classList.remove('opacity-0', 'pointer-events-none');
        modal.querySelector('.modal-content').classList.remove('-translate-y-10');
    }

    function closeModal() {
        modal.classList.add('opacity-0', 'pointer-events-none');
        modal.querySelector('.modal-content').classList.add('-translate-y-10');
    }

    // Close modal on outside click
    modal.addEventListener('click', (event) => {
        if (event.target === modal) {
            closeModal();
        }
    });
    </script>

</body>
</html>