<?php
session_start();
require_once 'db.php'; // Database connection file

$pet = null; // To hold all pet and owner data
$error_message = null; // For GET errors
$toast_message = null; // For POST results
$pet_id = null;

// Check for a valid Pet ID in the URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $error_message = "Invalid pet ID. Please go back and try again.";
} else {
    $pet_id = (int)$_GET['id'];
}

// Check session rule for editing
$is_admin = (isset($_SESSION['user_rules']) && $_SESSION['user_rules'] == 1);

// --- START: HANDLE FORM SUBMISSION (UPDATE) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Security check: Only admins can POST data
    if (!$is_admin) {
        $toast_message = ['type' => 'error', 'message' => 'You do not have permission to perform this action.'];
    }
    // Check if the pet_id from the form matches the one in the URL
    else if (!isset($_POST['pet_id']) || (int)$_POST['pet_id'] !== $pet_id) {
        $toast_message = ['type' => 'error', 'message' => 'Form data mismatch. Please try again.'];
    }
    else if (!function_exists('get_db_connection')) {
        $toast_message = ['type' => 'error', 'message' => 'Database function missing.'];
    }
    else {
        $conn = get_db_connection();
        if ($conn === null) {
            $toast_message = ['type' => 'error', 'message' => 'Database connection failed.'];
        } else {
            try {
                // Build the UPDATE query
                $sql_update = "UPDATE pets SET 
                    pet_origin = ?, pet_origin_other = ?, pet_ownership = ?, pet_habitat = ?, 
                    pet_species = ?, pet_name = ?, pet_breed = ?, pet_bday = ?, pet_color = ?, 
                    pet_sex = ?, pet_is_pregnant = ?, pet_is_lactating = ?, pet_puppies = ?, 
                    pet_weight = ?, pet_tag_no = ?, tag_type_collar = ?, tag_type_other = ?, 
                    tag_type_other_specify = ?, pet_contact = ?
                WHERE pet_id = ?";
                
                $stmt_update = $conn->prepare($sql_update);
                
                // Prepare checkbox/nullable values
                $pet_is_pregnant = isset($_POST['pet_is_pregnant']) ? 1 : 0;
                $pet_is_lactating = isset($_POST['pet_is_lactating']) ? 1 : 0;
                $pet_puppies = empty($_POST['pet_puppies']) ? null : (int)$_POST['pet_puppies'];
                $pet_weight = empty($_POST['pet_weight']) ? null : (float)$_POST['pet_weight'];
                $tag_type_collar = isset($_POST['tag_type_collar']) ? 1 : 0;
                $tag_type_other = isset($_POST['tag_type_other']) ? 1 : 0;
                
                // Bind all 20 parameters (19 fields + 1 pet_id)
                $stmt_update->bind_param(
                    "ssssssssssiiidisiissis", // Data types
                    $_POST['pet_origin'],
                    $_POST['pet_origin_other'],
                    $_POST['pet_ownership'],
                    $_POST['pet_habitat'],
                    $_POST['pet_species'],
                    $_POST['pet_name'],
                    $_POST['pet_breed'],
                    $_POST['pet_bday'],
                    $_POST['pet_color'],
                    $_POST['pet_sex'],
                    $pet_is_pregnant,
                    $pet_is_lactating,
                    $pet_puppies,
                    $pet_weight,
                    $_POST['pet_tag_no'],
                    $tag_type_collar,
                    $tag_type_other,
                    $_POST['tag_type_other_specify'],
                    $_POST['pet_contact'],
                    $pet_id // The WHERE clause
                );
                
                if ($stmt_update->execute()) {
                    $toast_message = ['type' => 'success', 'message' => 'Pet details updated successfully!'];
                } else {
                    $toast_message = ['type' => 'error', 'message' => 'Update failed: ' . $stmt_update->error];
                }
                
                $stmt_update->close();
                
            } catch (Exception $e) {
                $toast_message = ['type' => 'error', 'message' => 'An error occurred: ' . $e->getMessage()];
            }
            $conn->close();
        }
    }
}
// --- END: HANDLE FORM SUBMISSION ---


// --- START: FETCH PET DATA (FOR DISPLAY) ---
// This runs on every page load, including after a POST, to get the fresh data
if ($pet_id && !$error_message) { // Only fetch if we have an ID and no prior errors
    if (!function_exists('get_db_connection')) {
        $error_message = 'Database function get_db_connection() is missing from db.php.';
    } else {
        $conn = get_db_connection();
        if ($conn) {
            $sql = "SELECT 
                        p.*,  -- Selects all columns from the pets table
                        c.client_fname, c.client_lname, 
                        c.client_contact AS owner_contact,
                        c.client_email AS owner_email
                    FROM pets AS p
                    LEFT JOIN clients AS c ON p.client_id = c.client_id
                    WHERE p.pet_id = ?";

            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("i", $pet_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows === 1) {
                    $pet = $result->fetch_assoc();
                } else {
                    $error_message = "No pet found with this ID.";
                }
                $stmt->close();
            } else {
                $error_message = 'Failed to prepare the SQL statement.';
            }
            $conn->close();
        } else {
            $error_message = 'Database connection failed.';
        }
    }
}
// --- END: FETCH PET DATA ---

/**
 * Helper function to safely display data in HTML attributes
 */
function attr($value) {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

/**
 * Helper function to safely display data in HTML content
 */
function display($value, $default = 'N/A') {
    return !empty($value) ? htmlspecialchars($value) : $default;
}

/**
 * Helper function to select a radio/select option
 */
function is_selected($value, $expected) {
    return $value == $expected ? 'selected' : '';
}

/**
 * Helper function to check a checkbox
 */
function is_checked($value) {
    return $value ? 'checked' : '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pet Details - Pet Registration</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        /* Style for the definition list (dl) */
        .details-grid dt { @apply text-sm font-medium text-gray-500; }
        .details-grid dd { @apply mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0; }
        
        /* --- Toast Notification Styles --- */
        .toast {
            position: fixed; top: 20px; right: 20px; z-index: 100;
            opacity: 0; visibility: hidden;
            transform: translateX(100%);
            transition: all 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        }
        .toast.show {
            opacity: 1; visibility: visible;
            transform: translateX(0);
        }
        
        /* --- Form Input Styles --- */
        /* Base style for form inputs */
        .form-input-base {
            @apply w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm;
            @apply focus:outline-none focus:ring-blue-500 focus:border-blue-500;
        }
        /* Style for inputs when they are "disabled" */
        .form-input-base:disabled, .form-input-base[readonly] {
            @apply bg-gray-50 text-gray-500 border-gray-200 cursor-not-allowed;
        }
        /* Remove borders when readonly to make it look like text */
        .form-input-base[readonly] {
            @apply border-transparent shadow-none px-0 py-0;
        }
        /* Checkbox/radio styles */
        .form-check-base {
            @apply h-4 w-4 text-blue-600 border-gray-300 rounded;
            @apply focus:ring-blue-500;
        }
        .form-check-base:disabled {
            @apply bg-gray-50 text-gray-400 cursor-not-allowed;
        }
    </style>
</head>
<body class="bg-gray-100">

    <div id="toast-notification" class="toast max-w-xs p-4 rounded-lg shadow-lg text-white" role="alert">
        <div class="flex items-center">
            <div id="toast-icon" class="mr-3 text-xl flex-shrink-0 w-6 h-6 flex items-center justify-center"></div>
            <div class="text-sm font-medium" id="toast-message"></div>
            <button type="button" class="ml-auto -mx-1.5 -my-1.5 p-1.5 rounded-lg inline-flex h-8 w-8 text-white/70 hover:text-white hover:bg-white/20" onclick="hideToast()">
                <span class="sr-only">Close</span>
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
            </button>
        </div>
    </div>
    <div class="container mx-auto p-4 py-8 md:p-10">
        <div class="max-w-4xl mx-auto bg-white p-6 md:p-10 rounded-2xl shadow-lg">

            <div class="flex justify-between items-center mb-6">
                <a href="all_pets.php" class="text-sm font-medium text-blue-600 hover:text-blue-800">
                    &larr; Back to all pets
                </a>
                
                <?php if ($is_admin): ?>
                <div class="flex space-x-3">
                    <button type="button" id="edit-btn" class="px-5 py-2 bg-blue-600 text-white font-semibold rounded-lg shadow-md hover:bg-blue-700" style="background-color: #4361ee;">
                        Edit
                    </button>
                    <button type="submit" id="save-btn" form="pet-form" class="hidden px-5 py-2 bg-green-600 text-white font-semibold rounded-lg shadow-md hover:bg-green-700">
                        Save Changes
                    </button>
                    <button type="button" id="cancel-btn" class="hidden px-5 py-2 bg-gray-500 text-white font-semibold rounded-lg shadow-md hover:bg-gray-600">
                        Cancel
                    </button>
                </div>
                <?php endif; ?>
            </div>

            <?php if ($error_message): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative mb-6" role="alert">
                    <strong class="font-bold">Error:</strong>
                    <span class="block sm:inline"><?php echo htmlspecialchars($error_message); ?></span>
                </div>
            
            <?php elseif ($pet): ?>
            
            <form id="pet-form" method="POST" action="pet_details.php?id=<?php echo $pet_id; ?>">
                <input type="hidden" name="pet_id" value="<?php echo $pet_id; ?>">

                <div class="flex flex-col sm:flex-row items-center sm:items-start space-y-4 sm:space-y-0 sm:space-x-6 pb-6 border-b border-gray-200">
                    <div class="flex-shrink-0">
                        <?php if (!empty($pet['pet_image_path']) && file_exists($pet['pet_image_path'])): ?>
                            <img class="h-32 w-32 rounded-full object-cover shadow-md" 
                                 src="<?php echo htmlspecialchars($pet['pet_image_path']); ?>" 
                                 alt="<?php echo display($pet['pet_name']); ?>">
                        <?php else: ?>
                            <div class="h-32 w-32 rounded-full bg-gray-200 flex items-center justify-center text-gray-500 font-bold text-6xl shadow-inner">
                                <?php echo display(strtoupper(substr($pet['pet_name'], 0, 1)), '?'); ?>
                            </div>
                        <?php endif; ?>
                        </div>
                    
                    <div class="w-full">
                        <label for="pet_name" class="text-xs font-medium text-gray-500">Pet Name</label>
                        <input type="text" name="pet_name" id="pet_name" class="pet-input form-input-base text-3xl font-bold text-gray-900 p-0" style="color: #4361ee;" value="<?php echo attr($pet['pet_name']); ?>" readonly>
                        
                        <label for="pet_breed" class="mt-2 text-xs font-medium text-gray-500">Breed / Color / Species</label>
                        <div class="flex flex-col sm:flex-row sm:space-x-2 text-lg text-gray-600">
                            <input type="text" name="pet_color" class="pet-input form-input-base" value="<?php echo attr($pet['pet_color']); ?>" readonly>
                            <input type="text" name="pet_breed" class="pet-input form-input-base" value="<?php echo attr($pet['pet_breed']); ?>" readonly>
                            <select name="pet_species" class="pet-input form-input-base" disabled>
                                <option value="Dog" <?php echo is_selected($pet['pet_species'], 'Dog'); ?>>Dog</option>
                                <option value="Cat" <?php echo is_selected($pet['pet_species'], 'Cat'); ?>>Cat</option>
                            </select>
                        </div>
                        
                        <label for="pet_sex" class="mt-2 text-xs font-medium text-gray-500">Sex / Birthday</label>
                        <div class="flex flex-col sm:flex-row sm:space-x-2 text-sm text-gray-500">
                            <select name="pet_sex" class="pet-input form-input-base" disabled>
                                <option value="Male" <?php echo is_selected($pet['pet_sex'], 'Male'); ?>>Male</option>
                                <option value="Female" <?php echo is_selected($pet['pet_sex'], 'Female'); ?>>Female</option>
                            </select>
                            <input type="date" name="pet_bday" class="pet-input form-input-base" value="<?php echo attr($pet['pet_bday']); ?>" readonly>
                        </div>
                    </div>
                </div>

                <div class="mt-8">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">Pet Information</h2>
                    <dl class="details-grid grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-3">
                        
                        <dt>Habitat</dt>
                        <dd>
                            <select name="pet_habitat" class="pet-input form-input-base" disabled>
                                <option value="Caged" <?php echo is_selected($pet['pet_habitat'], 'Caged'); ?>>Caged</option>
                                <option value="Free Roaming" <?php echo is_selected($pet['pet_habitat'], 'Free Roaming'); ?>>Free Roaming</option>
                                <option value="Leash" <?php echo is_selected($pet['pet_habitat'], 'Leash'); ?>>Leash</option>
                                <option value="House Only" <?php echo is_selected($pet['pet_habitat'], 'House Only'); ?>>House Only</option>
                            </select>
                        </dd>

                        <dt>Origin</dt>
                        <dd>
                            <select name="pet_origin" class="pet-input form-input-base mb-2" disabled>
                                <option value="Local" <?php echo is_selected($pet['pet_origin'], 'Local'); ?>>Local</option>
                                <option value="Others" <?php echo is_selected($pet['pet_origin'], 'Others'); ?>>Others</option>
                            </select>
                            <input type="text" name="pet_origin_other" placeholder="Specify if Other" class="pet-input form-input-base" value="<?php echo attr($pet['pet_origin_other']); ?>" readonly>
                        </dd>
                        
                        <dt>Ownership</dt>
                        <dd>
                            <select name="pet_ownership" class="pet-input form-input-base" disabled>
                                <option value="Household" <?php echo is_selected($pet['pet_ownership'], 'Household'); ?>>Household</option>
                                <option value="Community" <?php echo is_selected($pet['pet_ownership'], 'Community'); ?>>Community</option>
                            </select>
                        </dd>

                        <dt>Weight (kgs)</dt>
                        <dd>
                            <input type="number" step="0.1" name="pet_weight" class="pet-input form-input-base" value="<?php echo attr($pet['pet_weight']); ?>" readonly>
                        </dd>
                        
                        <dt>Contact with Animals</dt>
                        <dd>
                             <select name="pet_contact" class="pet-input form-input-base" disabled>
                                <option value="Frequent" <?php echo is_selected($pet['pet_contact'], 'Frequent'); ?>>Frequent</option>
                                <option value="Seldom" <?php echo is_selected($pet['pet_contact'], 'Seldom'); ?>>Seldom</option>
                                <option value="Never" <?php echo is_selected($pet['pet_contact'], 'Never'); ?>>Never</option>
                            </select>
                        </dd>

                        <dt>Registered On</dt>
                        <dd class="text-gray-500"><?php echo display(date('F j, Y, g:i a', strtotime($pet['created_at']))); ?></dd>

                        <?php if ($pet['pet_sex'] == 'Female'): ?>
                            <dt>Is Pregnant?</dt>
                            <dd class="flex items-center">
                                <input type="checkbox" name="pet_is_pregnant" value="1" class="pet-input form-check-base" <?php echo is_checked($pet['pet_is_pregnant']); ?> disabled>
                                <span class="ml-2 text-sm text-gray-700">Yes</span>
                            </dd>
                            
                            <dt>Is Lactating?</dt>
                            <dd class="flex items-center">
                                <input type="checkbox" name="pet_is_lactating" value="1" class="pet-input form-check-base" <?php echo is_checked($pet['pet_is_lactating']); ?> disabled>
                                <span class="ml-2 text-sm text-gray-700">Yes</span>
                            </dd>

                            <dt>Number of Puppies</dt>
                            <dd>
                                <input type="number" name="pet_puppies" class="pet-input form-input-base" value="<?php echo attr($pet['pet_puppies']); ?>" readonly>
                            </dd>
                        <?php endif; ?>
                    </dl>
                </div>

                <div class="mt-10 pt-8 border-t border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">Tag Information</h2>
                    <dl class="details-grid grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-3">
                        <dt>Tag No.</dt>
                        <dd>
                            <input type="text" name="pet_tag_no" class="pet-input form-input-base" value="<?php echo attr($pet['pet_tag_no']); ?>" readonly>
                        </dd>
                        
                        <dt>Collar Tag?</dt>
                        <dd class="flex items-center">
                            <input type="checkbox" name="tag_type_collar" value="1" class="pet-input form-check-base" <?php echo is_checked($pet['tag_type_collar']); ?> disabled>
                            <span class="ml-2 text-sm text-gray-700">Yes</span>
                        </dd>

                        <dt>Other Tag?</dt>
                        <dd>
                            <div class="flex items-center mb-2">
                                <input type="checkbox" name="tag_type_other" value="1" class="pet-input form-check-base" <?php echo is_checked($pet['tag_type_other']); ?> disabled>
                                <span class="ml-2 text-sm text-gray-700">Yes</span>
                            </div>
                            <input type="text" name="tag_type_other_specify" placeholder="Specify if Other" class="pet-input form-input-base" value="<?php echo attr($pet['tag_type_other_specify']); ?>" readonly>
                        </dd>
                    </dl>
                </div>
            </form>
            <div class="mt-10 pt-8 border-t border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">Owner Information (Read-Only)</h2>
                    <dl class="details-grid grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-3">
                        <dt>Owner Name</dt>
                        <dd><?php echo display($pet['client_fname']); ?> <?php echo display($pet['client_lname']); ?></dd>
                        
                        <dt>Owner Contact</dt>
                        <dd><?php echo display($pet['owner_contact']); ?></dd>

                        <dt>Owner Email</dt>
                        <dd><?php echo display($pet['owner_email']); ?></dd>
                    </dl>
                </div>

            <?php else: ?>
                <p class="text-center text-gray-500">Could not load pet data.</p>
            <?php endif; ?>

        </div>
    </div>
    
    <script>
        // --- Toast Notification Script ---
        const toast = document.getElementById('toast-notification');
        const toastMessage = document.getElementById('toast-message');
        const toastIcon = document.getElementById('toast-icon');
        let toastTimeout;

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

        
        // --- Edit Mode Toggle Script ---
        document.addEventListener('DOMContentLoaded', function() {
            const editBtn = document.getElementById('edit-btn');
            const saveBtn = document.getElementById('save-btn');
            const cancelBtn = document.getElementById('cancel-btn');
            const formInputs = document.querySelectorAll('.pet-input');

            if (editBtn) {
                // When "Edit" is clicked
                editBtn.addEventListener('click', function() {
                    // Enable all form fields
                    formInputs.forEach(input => {
                        input.removeAttribute('readonly');
                        input.removeAttribute('disabled');
                        // Add border back to text inputs
                        if(input.type === 'text' || input.type === 'date' || input.type === 'number') {
                            input.classList.remove('border-transparent', 'shadow-none', 'px-0', 'py-0');
                        }
                    });
                    
                    // Toggle button visibility
                    editBtn.classList.add('hidden');
                    saveBtn.classList.remove('hidden');
                    cancelBtn.classList.remove('hidden');
                });
            }
            
            if (cancelBtn) {
                // When "Cancel" is clicked
                cancelBtn.addEventListener('click', function() {
                    // Easiest way to reset changes is to reload the page
                    window.location.reload();
                });
            }
        });
    </script>
    
    <?php
    // --- PHP TOAST TRIGGER ---
    // This script block will only be rendered if the form was processed
    if ($toast_message) {
        $message_json = json_encode($toast_message['message']);
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                showToast('{$toast_message['type']}', $message_json);
            });
        </script>";
    }
    ?>
    </body>
</html>