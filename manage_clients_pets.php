<?php
session_start();

// Check if user is logged in and is authorized (Healthcare/Admin: 1 or 2)
if (!isset($_SESSION['user_rules']) || $_SESSION['user_rules'] < 1) {
    echo "<p class='text-red-600 p-4'>Access Denied. You must be an administrator or healthcare staff to view this page.</p>";
    exit;
}

// Assumes db.php contains get_db_connection()
include_once 'db.php'; 



// --- 1. HANDLE FINAL PET APPROVAL AND CLIENT DATE UPDATE (POST REQUEST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'finalize_approval') {
    // Input validation and sanitization
    $pet_id_to_approve = (int)($_POST['pet_id'] ?? 0);
    $client_id_to_update = (int)($_POST['client_id'] ?? 0);
    $reg_date = trim($_POST['reg_date'] ?? '');
    $valid_until = trim($_POST['valid_until'] ?? '');

    // Basic date and ID checks
    if (empty($reg_date) || empty($valid_until) || $pet_id_to_approve <= 0 || $client_id_to_update <= 0) {
        $message = "Error: Missing required fields (Pet ID, Client ID, Registration Date, or Valid Until Date).";
        header("Location: manage_clients_pets.php?msg=" . urlencode($message) . "&status=error");
        exit;
    }
    
    $conn = get_db_connection();
    $conn->begin_transaction();
    $all_ok = true;
    $message = "";

    try {
        // 1. Update Client Registration Dates
        // The clients table is updated with the dates entered by the admin.
        $sql_client_update = "UPDATE clients SET reg_date = ?, valid_until = ? WHERE client_id = ?";
        $stmt_client = $conn->prepare($sql_client_update);
        $stmt_client->bind_param("ssi", $reg_date, $valid_until, $client_id_to_update);
        if (!$stmt_client->execute()) {
            $all_ok = false;
            $message = "Client date update failed. Database error: " . $stmt_client->error;
        }
        $stmt_client->close();
        
        // 2. Approve Pet
        if ($all_ok) {
            // The pets table is updated to mark the pet as approved.
            $sql_pet_approve = "UPDATE pets SET is_approved = 1 WHERE pet_id = ?";
            $stmt_pet = $conn->prepare($sql_pet_approve);
            $stmt_pet->bind_param("i", $pet_id_to_approve);
            if (!$stmt_pet->execute()) {
                $all_ok = false;
                $message = "Pet approval update failed. Database error: " . $stmt_pet->error;
            }
            $stmt_pet->close();
        }

        if ($all_ok) {
            $conn->commit();
            $message = "Pet ID $pet_id_to_approve has been successfully approved and client dates finalized! ✅";
            $success = true;
        } else {
            $conn->rollback();
            if (empty($message)) $message = "Transaction failed. No specific error recorded.";
            $success = false;
        }

    } catch (Exception $e) {
        $conn->rollback();
        $message = "An unexpected error occurred: " . $e->getMessage();
        $success = false;
    }

    $conn->close();

    // Redirect to clear the POST context
    header("Location: manage_clients_pets.php?msg=" . urlencode($message) . "&status=" . ($success ? 'success' : 'error'));
    exit;
}
// --- END FINAL APPROVAL HANDLER ---

// --- 2. FETCH PET FOR EDITING ---
$pet_to_edit = null;
if (isset($_GET['pet_id_to_edit'])) {
    $edit_pet_id = (int)$_GET['pet_id_to_edit'];
    $conn_edit = get_db_connection();
    
    // Fetch pet and client details, including existing (placeholder) dates
    $edit_sql = "
        SELECT 
            p.*, 
            c.client_fname, c.client_lname, c.reg_date, c.valid_until, c.client_id AS c_client_id
        FROM 
            pets p
        LEFT JOIN 
            users u ON p.client_id = u.id
        LEFT JOIN 
            clients c ON c.client_email = u.email
        WHERE 
            p.pet_id = ? AND p.is_approved = 0"; // Only allow editing of unapproved pets
            
    $stmt_edit = $conn_edit->prepare($edit_sql);
    if ($stmt_edit) {
        $stmt_edit->bind_param("i", $edit_pet_id);
        $stmt_edit->execute();
        $result_edit = $stmt_edit->get_result();
        
        if ($result_edit->num_rows > 0) {
            $pet_to_edit = $result_edit->fetch_assoc();
        }
        
        $stmt_edit->close();
    }
    $conn_edit->close();
}


// --- 3. FETCH PENDING AND APPROVED PETS (Main list display) ---
$conn = get_db_connection();
$pending_pets = [];
$approved_pets = [];

// Query to get pet details and owner name
$sql = "
    SELECT 
        p.*, 
        c.client_fname, 
        c.client_lname,
        c.client_id AS c_client_id
    FROM 
        pets p
    LEFT JOIN 
        users u ON p.client_id = u.id
    LEFT JOIN 
        clients c ON c.client_email = u.email
    ORDER BY p.created_at DESC
";

$result = $conn->query($sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        // Check the new 'is_approved' column added to the table
        if ($row['is_approved'] == 0) {
            $pending_pets[] = $row;
        } else {
            $approved_pets[] = $row;
        }
    }
} else {
    // Handle query error
    $error_msg = "Database query error: " . $conn->error;
}

$conn->close();

// Prepare toast data if set via GET parameters (after redirect)
$toast_message = null;
if (isset($_GET['msg']) && isset($_GET['status'])) {
    $toast_message = [
        'type' => $_GET['status'],
        'message' => htmlspecialchars($_GET['msg'])
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Clients & Pets</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* --- Toast Notification Styles --- */
        .toast {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 100;
            opacity: 0;
            visibility: hidden;
            transform: translateX(100%);
            transition: all 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        }
        .toast.show {
            opacity: 1;
            visibility: visible;
            transform: translateX(0);
        }
    </style>
</head>
<body class="p-6 bg-gray-50 min-h-screen">

    <div id="toast-notification" class="toast max-w-xs p-4 rounded-lg shadow-lg text-white" role="alert">
        <div class="flex items-center">
            <div id="toast-icon" class="mr-3 text-xl flex-shrink-0 w-6 h-6 flex items-center justify-center">
                </div>
            <div class="text-sm font-medium" id="toast-message">
                </div>
            <button type="button" class="ml-auto -mx-1.5 -my-1.5 p-1.5 rounded-lg inline-flex h-8 w-8 text-white/70 hover:text-white hover:bg-white/20" onclick="hideToast()">
                <span class="sr-only">Close</span>
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
            </button>
        </div>
    </div>
    <h2 class="text-3xl font-bold text-gray-800 mb-6">Manage Client & Pet Registrations</h2>

    <?php 
    // Display error message from query error
    if (isset($error_msg)) {
         echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline">' . htmlspecialchars($error_msg) . '</span>
              </div>';
    }
    ?>
    
    <?php if ($pet_to_edit): ?>
        <div class="fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-3xl p-8 max-h-[90vh] overflow-y-auto">
                <h3 class="text-2xl font-bold text-gray-800 border-b pb-3 mb-6">
                    Approve & Finalize Registration
                </h3>
                
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                    <div class="lg:col-span-2">
                        <h4 class="text-xl font-semibold mb-3 text-blue-700"><i class="fas fa-paw mr-2"></i> Pet Details</h4>
                        <div class="bg-blue-50 p-4 rounded-lg space-y-2">
                            <p><strong>Name:</strong> <?php echo htmlspecialchars($pet_to_edit['pet_name']); ?> (<?php echo htmlspecialchars($pet_to_edit['pet_species']); ?>)</p>
                            <p><strong>Breed:</strong> <?php echo htmlspecialchars($pet_to_edit['pet_breed']); ?></p>
                            <p><strong>DOB:</strong> <?php echo htmlspecialchars($pet_to_edit['pet_bday']); ?></p>
                            <p><strong>Color/Sex:</strong> <?php echo htmlspecialchars($pet_to_edit['pet_color']); ?> / <?php echo htmlspecialchars($pet_to_edit['pet_sex']); ?></p>
                            <p><strong>Weight:</strong> <?php echo htmlspecialchars($pet_to_edit['pet_weight'] ?? 'N/A'); ?> kgs</p>
                            <p><strong>Habitat:</strong> <?php echo htmlspecialchars($pet_to_edit['pet_habitat']); ?></p>
                        </div>
                        
                        <h4 class="text-xl font-semibold mt-6 mb-3 text-blue-700"><i class="fas fa-user-circle mr-2"></i> Client Details</h4>
                        <div class="bg-blue-50 p-4 rounded-lg space-y-2">
                            <?php 
                            $client_name = trim($pet_to_edit['client_fname'] . ' ' . $pet_to_edit['client_lname']);
                            $display_owner = htmlspecialchars($client_name) ?: "Client ID " . htmlspecialchars($pet_to_edit['c_client_id']) . " (Name Missing)";
                            ?>
                            <p><strong>Client:</strong> <?php echo $display_owner; ?></p>
                            <p><strong>Client ID:</strong> <?php echo htmlspecialchars($pet_to_edit['c_client_id']); ?></p>
                            <p><strong>Submission Date:</strong> <?php echo date('M d, Y', strtotime($pet_to_edit['created_at'])); ?></p>
                        </div>
                    </div>
                    
                    <div class="lg:col-span-1 flex flex-col">
                        <h4 class="text-xl font-semibold mb-3 text-blue-700">Pet Image</h4>
                        <div class="w-full h-40 bg-gray-200 flex items-center justify-center rounded-lg mb-4 overflow-hidden">
                            <?php if (!empty($pet_to_edit['pet_image_path']) && file_exists($pet_to_edit['pet_image_path'])): ?>
                                <img src="<?php echo htmlspecialchars($pet_to_edit['pet_image_path']); ?>" alt="Pet Image" class="w-full h-full object-cover">
                            <?php else: ?>
                                <i class="fas fa-camera text-4xl text-gray-500"></i>
                            <?php endif; ?>
                        </div>
                        
                            <form method="POST" class="mt-auto">
                            <input type="hidden" name="action" value="finalize_approval">
                            <input type="hidden" name="pet_id" value="<?php echo htmlspecialchars($pet_to_edit['pet_id']); ?>">
                            <input type="hidden" name="client_id" value="<?php echo htmlspecialchars($pet_to_edit['c_client_id']); ?>">

                            <div class="mb-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg text-sm">
                                <p class="font-semibold text-yellow-800">
                                    <i class="fas fa-exclamation-triangle mr-1"></i> Placeholder Dates:
                                    **<?php echo htmlspecialchars($pet_to_edit['reg_date']); ?>** to **<?php echo htmlspecialchars($pet_to_edit['valid_until']); ?>**
                                </p>
                            </div>
                            
                            <div class="space-y-4">
                                <div>
                                    <label for="reg_date" class="block text-sm font-medium text-gray-700 mb-1">Final Registration Date</label>
                                    <input type="date" name="reg_date" id="reg_date" 
                                        value="<?php echo date('Y-m-d'); ?>" 
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" required>
                                </div>
                                <div>
                                    <label for="valid_until" class="block text-sm font-medium text-gray-700 mb-1">Final Valid Until Date</label>
                                    <input type="date" name="valid_until" id="valid_until" 
                                        value="<?php echo date('Y-m-d', strtotime('+1 year')); ?>" 
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" required>
                                </div>
                            </div>

                            <div class="flex justify-end space-x-3 mt-6">
                                <a href="manage_clients_pets" class="px-4 py-2 text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300">
                                    Cancel
                                </a>
                                <button type="submit" class="px-6 py-2 text-white font-semibold bg-green-600 rounded-lg hover:bg-green-700">
                                    <i class="fas fa-check-double mr-1"></i> Approve & Save
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="mb-10">
        <h3 class="text-xl font-semibold text-orange-600 border-b pb-2 mb-4">
            <i class="fas fa-clock mr-2"></i> Pending Pet Approvals (<?php echo count($pending_pets); ?>)
        </h3>
        
        <?php if (empty($pending_pets)): ?>
            <div class="text-gray-500 bg-white p-4 rounded-lg shadow-md">
                No new pet registrations pending approval. 🎉
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($pending_pets as $pet): ?>
                    <div class="card bg-white p-5 rounded-xl shadow-lg border border-orange-200">
                        <h4 class="text-lg font-bold text-gray-800 mb-1"><?php echo htmlspecialchars($pet['pet_name']); ?> (<?php echo htmlspecialchars($pet['pet_species']); ?>)</h4>
                        
                        <?php 
                        $owner_name = trim($pet['client_fname'] . ' ' . $pet['client_lname']);
                        $display_owner = htmlspecialchars($owner_name) ?: "Client ID " . htmlspecialchars($pet['c_client_id']) . " (Name Missing)";
                        ?>
                        <p class="text-sm text-gray-600 mb-3">Owner: <?php echo $display_owner; ?></p>
                        
                        <ul class="text-sm text-gray-700 space-y-1">
                            <li><span class="font-medium">Breed:</span> <?php echo htmlspecialchars($pet['pet_breed']); ?></li>
                            <li><span class="font-medium">DOB:</span> <?php echo htmlspecialchars($pet['pet_bday']); ?></li>
                            <li><span class="font-medium">Registration Date:</span> <?php echo date('M d, Y', strtotime($pet['created_at'])); ?></li>
                        </ul>
                        
                        <a href="manage_clients_pets.php?pet_id_to_edit=<?php echo $pet['pet_id']; ?>" 
                           class="mt-4 block w-full text-center py-2 px-4 rounded-lg text-white font-semibold bg-orange-500 hover:bg-orange-600 transition duration-150">
                            <i class="fas fa-edit mr-1"></i> View/Edit Details
                        </a>
                        </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <hr class="my-8">

    <div class="mt-10">
        <h3 class="text-xl font-semibold text-blue-600 border-b pb-2 mb-4">
            <i class="fas fa-paw mr-2"></i> All Approved Pets (<?php echo count($approved_pets); ?>)
        </h3>
        
        <?php if (empty($approved_pets)): ?>
            <div class="text-gray-500 bg-white p-4 rounded-lg shadow-md">
                No pets have been approved yet.
            </div>
        <?php else: ?>
             <div class="overflow-x-auto bg-white rounded-lg shadow-md">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pet ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pet Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Owner</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Breed / Species</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reg. Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach (array_slice($approved_pets, 0, 10) as $pet): // Limit to 10 for brevity ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo $pet['pet_id']; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($pet['pet_name']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                    <?php 
                                    $client_name = trim($pet['client_fname'] . ' ' . $pet['client_lname']);
                                    echo htmlspecialchars($client_name) ?: "Client ID " . htmlspecialchars($pet['c_client_id']) . " (Name Missing)";
                                    ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"><?php echo htmlspecialchars($pet['pet_breed']) . ' / ' . htmlspecialchars($pet['pet_species']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"><?php echo date('Y-m-d', strtotime($pet['created_at'])); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <a href="#" class="text-indigo-600 hover:text-indigo-900">View/Edit</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <script>
        const toast = document.getElementById('toast-notification');
        const toastMessage = document.getElementById('toast-message');
        const toastIcon = document.getElementById('toast-icon');
        let toastTimeout;

        /**
         * Shows the toast notification
         * @param {'success' | 'error'} type - The type of toast
         * @param {string} message - The message to display
         */
        function showToast(type, message) {
            if (toastTimeout) clearTimeout(toastTimeout);
            toastMessage.textContent = message;
            toast.classList.remove('bg-green-500', 'bg-red-500');
            
            if (type === 'success') {
                toast.classList.add('bg-green-500');
                toastIcon.innerHTML = `<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>`;
            } else if (type === 'error') {
                toast.classList.add('bg-red-500');
                toastIcon.innerHTML = `<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099a.75.75 0 011.486 0l5.25 10.5a.75.75 0 01-.643 1.051H3.643a.75.75 0 01-.643-1.051l5.25-10.5zM9 9a1 1 0 00-1 1v3a1 1 0 002 0v-3a1 1 0 00-1-1zm1 6a1 1 0 10-2 0 1 1 0 002 0z" clip-rule="evenodd"></path></svg>`;
            }
            toast.classList.add('show');
            toastTimeout = setTimeout(() => hideToast(), 5000);
        }

        function hideToast() {
            toast.classList.remove('show');
        }
        
        // --- PHP TOAST TRIGGER ---
        <?php
        if ($toast_message) {
            $message_json = json_encode($toast_message['message']);
            // The status is either 'success' or 'error' (from URL parameter)
            echo "document.addEventListener('DOMContentLoaded', function() {
                showToast('{$toast_message['type']}', $message_json);
            });";
        }
        ?>
    </script>

</body>
</html>