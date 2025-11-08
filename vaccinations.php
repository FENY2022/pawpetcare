<?php
session_start();
include 'db.php'; // Include your database connection

// Get database connection
$conn = get_db_connection();

$message = '';
$message_type = '';


// --- NEW NOTIFICATION FUNCTION ---
/**
 * Creates a notification for all staff members (user_rules = 1).
 *
 * @param mysqli $conn The database connection
 * @param string $action The action taken (e.g., 'add_vaccine', 'update_vaccine')
 * @param int $vaccine_id The ID of the vaccination record
 * @param int $pet_id The ID of the pet
 */
function create_notification_for_staff($conn, $action, $vaccine_id, $pet_id) {
    // 1. Get Pet Name for a better message
    $pet_name = "A pet"; // Default
    $pet_sql = "SELECT pet_name FROM pets WHERE pet_id = ?";
    $pet_stmt = $conn->prepare($pet_sql);
    if ($pet_stmt) {
        $pet_stmt->bind_param("i", $pet_id);
        $pet_stmt->execute();
        $pet_result = $pet_stmt->get_result();
        if ($pet_row = $pet_result->fetch_assoc()) {
            $pet_name = $pet_row['pet_name'];
        }
        $pet_stmt->close();
    }

    // 2. Define message based on action
    $title = "";
    $message = "";
    $link = "vaccinations.php#record-" . $vaccine_id; // Link to the page, with an anchor

    if ($action == 'add_vaccine') {
        $title = "New Vaccination Scheduled";
        $message = "A new vaccination has been scheduled for $pet_name.";
    } elseif ($action == 'update_vaccine') {
        $title = "Vaccination Record Updated";
        $message = "The vaccination record for $pet_name has been updated.";
    } else {
        return; // Don't notify for other actions
    }

    // 3. Find all staff members (user_rules = 1 from your users.sql)
    $staff_sql = "SELECT id FROM users WHERE user_rules = 1";
    $staff_result = $conn->query($staff_sql);
    
    if ($staff_result && $staff_result->num_rows > 0) {
        // 4. Prepare notification insert statement
        $notify_sql = "INSERT INTO notifications (user_id, title, message, link) VALUES (?, ?, ?, ?)";
        $notify_stmt = $conn->prepare($notify_sql);
        
        while ($staff_row = $staff_result->fetch_assoc()) {
            $staff_id = $staff_row['id'];
            $notify_stmt->bind_param("isss", $staff_id, $title, $message, $link);
            $notify_stmt->execute();
        }
        $notify_stmt->close();
    }
}
// --- END OF NEW FUNCTION ---


// --- Handle Form Submissions (Create & Update) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';

    // Common fields
    $pet_id = $_POST['pet_id'];
    $vaccine_name = $_POST['vaccine_name'];
    $status = $_POST['status'];
    $date_given = !empty($_POST['date_given']) ? $_POST['date_given'] : NULL;
    $next_due = !empty($_POST['next_due']) ? $_POST['next_due'] : NULL;
    $batch_no = !empty($_POST['batch_no']) ? $_POST['batch_no'] : NULL;
    $administered_by = !empty($_POST['administered_by']) ? $_POST['administered_by'] : NULL;
    $notes = !empty($_POST['notes']) ? $_POST['notes'] : NULL;
    
    // If status is 'Scheduled', force date_given to be NULL
    if ($status == 'Scheduled') {
        $date_given = NULL;
        $administered_by = NULL;
        $batch_no = NULL;
    }

    try {
        if ($action == 'add_vaccine') {
            // --- CREATE ---
            $sql = "INSERT INTO vaccinations (pet_id, status, vaccine_name, date_given, next_due, batch_no, administered_by, notes) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("isssssss", $pet_id, $status, $vaccine_name, $date_given, $next_due, $batch_no, $administered_by, $notes);
            $stmt->execute();
            
            // --- NEW ---
            $new_vaccine_id = $conn->insert_id; // Get the ID of the new record
            create_notification_for_staff($conn, 'add_vaccine', $new_vaccine_id, $pet_id);
            // --- END NEW ---
            
            $message = 'New vaccination record added successfully!';
            $message_type = 'success';

        } elseif ($action == 'update_vaccine') {
            // --- UPDATE ---
            $vaccine_id = $_POST['vaccine_id'];
            $sql = "UPDATE vaccinations SET 
                        pet_id = ?, 
                        status = ?, 
                        vaccine_name = ?, 
                        date_given = ?, 
                        next_due = ?, 
                        batch_no = ?, 
                        administered_by = ?, 
                        notes = ? 
                    WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("isssssssi", $pet_id, $status, $vaccine_name, $date_given, $next_due, $batch_no, $administered_by, $notes, $vaccine_id);
            $stmt->execute();

            // --- NEW ---
            create_notification_for_staff($conn, 'update_vaccine', $vaccine_id, $pet_id);
            // --- END NEW ---

            $message = 'Vaccination record updated successfully!';
            $message_type = 'success';
        }
    } catch (Exception $e) {
        $message = 'Error: ' . $e->getMessage();
        $message_type = 'error';
    }
}

// --- Handle Delete ---
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $vaccine_id = $_GET['id'];
    try {
        $sql = "DELETE FROM vaccinations WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $vaccine_id);
        $stmt->execute();
        $message = 'Vaccination record deleted successfully!';
        $message_type = 'success';
    } catch (Exception $e) {
        $message = 'Error: ' . $e->getMessage();
        $message_type = 'error';
    }
}

// --- Fetch Data for Display ---

// Fetch all pets for the dropdown (Using client_fname, client_lname)
$pets_result = $conn->query("
    SELECT p.pet_id, p.pet_name, c.client_fname, c.client_lname 
    FROM pets p 
    JOIN clients c ON p.client_id = c.client_id 
    ORDER BY p.pet_name ASC
");
$pets_list = $pets_result->fetch_all(MYSQLI_ASSOC);

// Fetch all vaccination records with pet and owner names (Using client_fname, client_lname)
$vaccinations_result = $conn->query("
    SELECT 
        p.pet_name, 
        c.client_fname, 
        c.client_lname,
        v.*
    FROM pets p
    JOIN clients c ON p.client_id = c.client_id
    JOIN vaccinations v ON v.pet_id = p.pet_id
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
    <title>Vaccination Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4361ee;
            --primary-dark: #3a56d4;
            --danger: #ef476f;
            --success: #06d6a0;
            --warning: #ffd166;
            --gray-100: #f1f5f9;
            --gray-500: #64748b;
            --gray-800: #1e293b;
        }
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f5f7fa;
            color: var(--gray-800);
        }
        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }
        .modal-overlay {
            transition: opacity 0.3s ease;
        }
        .modal-content {
            transition: transform 0.3s ease;
        }
        
        /* Thin Scrollbar Styles */
        .thin-scrollbar {
            scrollbar-width: thin;
            scrollbar-color: var(--gray-400) var(--gray-100);
        }
        .thin-scrollbar::-webkit-scrollbar {
            width: 6px;
        }
        .thin-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }
        .thin-scrollbar::-webkit-scrollbar-thumb {
            background-color: var(--gray-400);
            border-radius: 20px;
        }
    </style>
</head>
<body class="p-6 md:p-8">

    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
        <h1 class="text-3xl font-bold text-gray-800">Vaccination Management</h1>
        <button id="open-modal-btn" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg shadow-md transition duration-300 flex items-center">
            <i class="fas fa-plus mr-2"></i> Add New Vaccination
        </button>
    </div>

    <?php if ($message): ?>
    <div id="alert-message" class="p-4 mb-4 rounded-lg <?php echo $message_type == 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
        <i class="fas <?php echo $message_type == 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?> mr-2"></i>
        <?php echo htmlspecialchars($message); ?>
        <button onclick="document.getElementById('alert-message').style.display='none'" class="float-right font-bold">&times;</button>
    </div>
    <?php endif; ?>

    <div class="card p-6">
        <div class="mb-4">
            <div class="relative">
                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                <input type="text" id="search-bar" onkeyup="filterTable()" placeholder="Search by pet, owner, vaccine..." class="pl-10 pr-4 py-2 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 w-full md:w-1/3">
            </div>
        </div>

        <div class="overflow-x-auto thin-scrollbar">
            <table class="w-full min-w-max text-left" id="vaccinations-table">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="p-4 text-sm font-semibold text-gray-600 uppercase tracking-wider">Pet Name</th>
                        <th class="p-4 text-sm font-semibold text-gray-600 uppercase tracking-wider">Owner</th>
                        <th class="p-4 text-sm font-semibold text-gray-600 uppercase tracking-wider">Vaccine</th>
                        <th class="p-4 text-sm font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                        <th class="p-4 text-sm font-semibold text-gray-600 uppercase tracking-wider">Date Given</th>
                        <th class="p-4 text-sm font-semibold text-gray-600 uppercase tracking-wider">Next Due</th>
                        <th class="p-4 text-sm font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($vaccinations_list)): ?>
                        <tr>
                            <td colspan="7" class="p-4 text-center text-gray-500">No vaccination records found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($vaccinations_list as $v): 
                            // --- NEW ---
                            // Added an anchor ID to each row so notifications can link to it
                            $row_id = "record-" . $v['id'];
                            
                            $status_class = '';
                            $status_text = htmlspecialchars($v['status']);
                            $today = new DateTime();
                            $next_due_date = !empty($v['next_due']) ? new DateTime($v['next_due']) : null;

                            // --- MODIFIED --- Added 'Pending' status
                            if ($v['status'] == 'Pending') {
                                $status_class = 'bg-blue-100 text-blue-800';
                            } elseif ($v['status'] == 'Scheduled' && $next_due_date && $next_due_date < $today) {
                                $status_text = 'Overdue';
                                $status_class = 'bg-red-100 text-red-800';
                            } elseif ($v['status'] == 'Scheduled') {
                                $status_class = 'bg-yellow-100 text-yellow-800';
                            } elseif ($v['status'] == 'Completed') {
                                $status_class = 'bg-green-100 text-green-800';
                            } elseif ($v['status'] == 'Cancelled') {
                                $status_class = 'bg-gray-100 text-gray-800';
                            }
                        ?>
                        <tr class="hover:bg-gray-50" id="<?php echo $row_id; ?>">
                            <td class="p-4 font-medium text-gray-900"><?php echo htmlspecialchars($v['pet_name']); ?></td>
                            <td class="p-4 text-gray-600"><?php echo htmlspecialchars($v['client_fname'] . ' ' . $v['client_lname']); ?></td>
                            <td class="p-4 text-gray-600"><?php echo htmlspecialchars($v['vaccine_name']); ?></td>
                            <td class="p-4">
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $status_class; ?>">
                                    <?php echo $status_text; ?>
                                </span>
                            </td>
                            <td class="p-4 text-gray-600"><?php echo !empty($v['date_given']) ? date('M d, Y', strtotime($v['date_given'])) : 'N/A'; ?></td>
                            <td class="p-4 text-gray-600"><?php echo !empty($v['next_due']) ? date('M d, Y', strtotime($v['next_due'])) : 'N/A'; ?></td>
                            <td class="p-4 text-gray-600 space-x-2">
                                <button class="edit-btn text-blue-600 hover:text-blue-800"
                                        data-record='<?php echo json_encode($v); ?>'
                                        title="Edit">
                                    <i class="fas fa-pencil-alt"></i>
                                </button>
                                <a href="vaccinations.php?action=delete&id=<?php echo $v['id']; ?>" 
                                   class="text-red-600 hover:text-red-800"
                                   title="Delete"
                                   onclick="return confirm('Are you sure you want to delete this record?');">
                                    <i class="fas fa-trash-alt"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="vaccine-modal" class="modal-overlay fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 hidden opacity-0 z-50">
        <div class="modal-content card w-full max-w-2xl max-h-[90vh] flex flex-col scale-95 opacity-0">
            <div class="flex justify-between items-center p-5 border-b border-gray-200">
                <h3 class="text-xl font-semibold text-gray-900" id="modal-title">Add New Vaccination</h3>
                <button id="close-modal-btn" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <form id="vaccine-form" action="vaccinations.php" method="POST" class="flex-grow p-6 space-y-4 overflow-y-auto thin-scrollbar">
                <input type="hidden" name="action" id="form-action" value="add_vaccine">
                <input type="hidden" name="vaccine_id" id="vaccine-id" value="">

                <div>
                    <label for="pet-id" class="block mb-2 text-sm font-medium text-gray-700">Pet</label>
                    <select id="pet-id" name="pet_id" class="w-full p-2.5 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" required>
                        <option value="" disabled selected>Select a pet...</option>
                        <?php foreach ($pets_list as $pet): ?>
                            <option value="<?php echo $pet['pet_id']; ?>">
                                <?php echo htmlspecialchars($pet['pet_name'] . ' (Owner: ' . $pet['client_fname'] . ' ' . $pet['client_lname'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="vaccine-name" class="block mb-2 text-sm font-medium text-gray-700">Vaccine Name</label>
                    <select id="vaccine-name" name="vaccine_name" class="w-full p-2.5 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" required>
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
                    <label for="status" class="block mb-2 text-sm font-medium text-gray-700">Status</label>
                    <select id="status" name="status" class="w-full p-2.5 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" required>
                        <option value="Pending">Pending (Client Request)</option>
                        <option value="Scheduled">Scheduled</option>
                        <option value="Completed">Completed</option>
                        <option value="Cancelled">Cancelled</option>
                    </select>
                </div>
                
                <div id="completed-fields" class="space-y-4 hidden">
                    <div>
                        <label for="date-given" class="block mb-2 text-sm font-medium text-gray-700">Date Given</label>
                        <input type="date" id="date-given" name="date_given" class="w-full p-2.5 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label for="batch-no" class="block mb-2 text-sm font-medium text-gray-700">Batch No.</label>
                        <input type="text" id="batch-no" name="batch_no" placeholder="e.g., BATCH-12345" class="w-full p-2.5 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label for="administered-by" class="block mb-2 text-sm font-medium text-gray-700">Administered By</label>
                        <input type="text" id="administered-by" name="administered_by" placeholder="e.g., Dr. Juan Dela Cruz" class="w-full p-2.5 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>

                <div>
                    <label for="next-due" class="block mb-2 text-sm font-medium text-gray-700">Next Due Date (if any)</label>
                    <input type="date" id="next-due" name="next_due" class="w-full p-2.5 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                </div>

                <div>
                    <label for="notes" class="block mb-2 text-sm font-medium text-gray-700">Notes</label>
                    <textarea id="notes" name="notes" rows="3" placeholder="e.g., Pet had a slight fever after..." class="w-full p-2.5 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"></textarea>
                </div>
                
                <div class="flex justify-end items-center pt-4 border-t border-gray-200">
                    <button type="button" id="cancel-btn" class="text-gray-600 bg-gray-100 hover:bg-gray-200 font-medium rounded-lg px-5 py-2.5 mr-3">Cancel</button>
                    <button type="submit" id="submit-btn" class="text-white bg-blue-600 hover:bg-blue-700 font-medium rounded-lg px-5 py-2.5">Save Record</button>
                </div>
            </form>
        </div>
    </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('vaccine-modal');
    const modalOverlay = document.querySelector('.modal-overlay');
    const modalContent = document.querySelector('.modal-content');
    const openModalBtn = document.getElementById('open-modal-btn');
    const closeModalBtn = document.getElementById('close-modal-btn');
    const cancelBtn = document.getElementById('cancel-btn');
    const vaccineForm = document.getElementById('vaccine-form');
    const modalTitle = document.getElementById('modal-title');
    const formAction = document.getElementById('form-action');
    const vaccineId = document.getElementById('vaccine-id');
    const submitBtn = document.getElementById('submit-btn');
    const statusSelect = document.getElementById('status');
    const completedFields = document.getElementById('completed-fields');
    
    // --- Modal Toggle Functions ---
    const showModal = () => {
        modal.classList.remove('hidden');
        setTimeout(() => {
            modalOverlay.classList.remove('opacity-0');
            modalContent.classList.remove('scale-95', 'opacity-0');
        }, 10);
    };

    const hideModal = () => {
        modalOverlay.classList.add('opacity-0');
        modalContent.classList.add('scale-95', 'opacity-0');
        setTimeout(() => {
            modal.classList.add('hidden');
        }, 300);
    };
    
    // --- Reset Form for 'Add' mode ---
    const resetForm = () => {
        vaccineForm.reset();
        modalTitle.textContent = 'Add New Vaccination';
        submitBtn.textContent = 'Save Record';
        formAction.value = 'add_vaccine';
        vaccineId.value = '';
        // --- MODIFIED --- Default to 'Pending' to match new dropdown
        toggleCompletedFields('Pending'); 
    };

    // --- Show/Hide 'Completed' fields based on status ---
    const toggleCompletedFields = (status) => {
        const dateGivenInput = document.getElementById('date-given');
        if (status === 'Completed') {
            completedFields.classList.remove('hidden');
            dateGivenInput.setAttribute('required', 'required');
        } else {
            completedFields.classList.add('hidden');
            dateGivenInput.removeAttribute('required');
            // Clear fields when hiding
            document.getElementById('date-given').value = '';
            document.getElementById('batch-no').value = '';
            document.getElementById('administered-by').value = '';
        }
    };

    statusSelect.addEventListener('change', (e) => {
        toggleCompletedFields(e.target.value);
    });

    // --- Event Listeners ---
    openModalBtn.addEventListener('click', () => {
        resetForm();
        showModal();
    });

    closeModalBtn.addEventListener('click', hideModal);
    cancelBtn.addEventListener('click', hideModal);
    
    modalOverlay.addEventListener('click', (e) => {
        if (e.target === modalOverlay) {
            hideModal();
        }
    });

    // --- Populate Form for 'Edit' mode ---
    document.querySelectorAll('.edit-btn').forEach(button => {
        button.addEventListener('click', () => {
            const record = JSON.parse(button.dataset.record);
            
            // Populate form
            modalTitle.textContent = 'Edit Vaccination Record';
            submitBtn.textContent = 'Update Record';
            formAction.value = 'update_vaccine';
            vaccineId.value = record.id;
            
            document.getElementById('pet-id').value = record.pet_id;
            document.getElementById('vaccine-name').value = record.vaccine_name;
            document.getElementById('status').value = record.status;
            document.getElementById('date-given').value = record.date_given;
            document.getElementById('next-due').value = record.next_due;
            document.getElementById('batch-no').value = record.batch_no;
            document.getElementById('administered-by').value = record.administered_by;
            document.getElementById('notes').value = record.notes;

            // Show/hide fields based on status
            toggleCompletedFields(record.status);
            
            showModal();
        });
    });
});

// --- Table Search/Filter Function ---
function filterTable() {
    const filter = document.getElementById('search-bar').value.toUpperCase();
    const table = document.getElementById('vaccinations-table');
    const tr = table.getElementsByTagName('tr');

    for (let i = 1; i < tr.length; i++) { // Start from 1 to skip header row
        const tds = tr[i].getElementsByTagName('td');
        let textValue = '';
        for (let j = 0; j < tds.length - 1; j++) { // Loop through all cells except 'Actions'
            if (tds[j]) {
                textValue += tds[j].textContent || tds[j].innerText;
            }
        }
        
        if (textValue.toUpperCase().indexOf(filter) > -1) {
            tr[i].style.display = '';
        } else {
            tr[i].style.display = 'none';
        }
    }
}

// --- Hide success/error message after 5 seconds ---
setTimeout(() => {
    const alertMessage = document.getElementById('alert-message');
    if (alertMessage) {
        alertMessage.style.transition = 'opacity 0.5s';
        alertMessage.style.opacity = '0';
        setTimeout(() => alertMessage.style.display = 'none', 500);
    }
}, 5000);

</script>

</body>
</html>