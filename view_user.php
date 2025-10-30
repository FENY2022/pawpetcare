<?php
// Start the session and include the database connection
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include 'db.php';

// Basic security check for viewing the page
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    exit('Access Denied.');
}

// Generate a CSRF token if one doesn't exist
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// === HANDLE FORM SUBMISSION (UPDATE LOGIC) ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Security Checks
    // Check if user is an admin
    if (!isset($_SESSION['user_rules']) || $_SESSION['user_rules'] < 1) {
        exit('You do not have permission to perform this action.');
    }
    // Check CSRF token
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        exit('Invalid CSRF token. Please try again.');
    }

    // 2. Get and Sanitize Data
    $userIdToUpdate = filter_input(INPUT_POST, 'user_id_to_update', FILTER_SANITIZE_NUMBER_INT);
    $userRules = filter_input(INPUT_POST, 'user_rules', FILTER_SANITIZE_NUMBER_INT);
    $contactNumber = filter_input(INPUT_POST, 'contact_number', FILTER_SANITIZE_STRING);
    $street = filter_input(INPUT_POST, 'street', FILTER_SANITIZE_STRING);
    $barangay = filter_input(INPUT_POST, 'barangay', FILTER_SANITIZE_STRING);
    $city = filter_input(INPUT_POST, 'city', FILTER_SANITIZE_STRING);
    $province = filter_input(INPUT_POST, 'province', FILTER_SANITIZE_STRING);

    // 3. Prepare and Execute Update Query
    $sql = "UPDATE users SET user_rules = ?, contact_number = ?, street = ?, barangay = ?, city = ?, province = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    // 'isssssi' means integer, string, string, string, string, string, integer
    $stmt->bind_param("isssssi", $userRules, $contactNumber, $street, $barangay, $city, $province, $userIdToUpdate);

    if ($stmt->execute()) {
        $_SESSION['update_message'] = ['text' => 'User details updated successfully!', 'type' => 'success'];
    } else {
        $_SESSION['update_message'] = ['text' => 'Error updating record: ' . $stmt->error, 'type' => 'error'];
    }
    $stmt->close();

    // 4. Redirect to prevent form re-submission on refresh
    header("Location: " . $_SERVER['PHP_SELF'] . "?user_id=" . $userIdToUpdate);
    exit();
}


// === FETCH USER DETAILS FOR DISPLAY ===
$user_details = null;
$error_message = '';
$update_message = null;

// Display and clear any update message from the session
if (isset($_SESSION['update_message'])) {
    $update_message = $_SESSION['update_message'];
    unset($_SESSION['update_message']);
}


// Check if a user ID is provided
if (isset($_GET['user_id']) && !empty($_GET['user_id'])) {
    $selectedUserId = $_GET['user_id'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $selectedUserId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $user_details = $result->fetch_assoc();
    } else {
        $error_message = "User not found.";
    }
    $stmt->close();
} else {
    $error_message = "No user specified.";
}
$conn->close();

// Helper function to map role ID to role name
function getRoleName($roleId) {
    switch ($roleId) {
        case 0: return 'User';
        case 1: return 'Admin';
        case 2: return 'Super Admin';
        default: return 'Unknown';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Details</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        * { font-family: 'Poppins', sans-serif; }
        .detail-row { border-bottom: 1px solid #e5e7eb; }
        .detail-row:last-child { border-bottom: none; }
        .edit-mode-input {
            width: 50%;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            padding: 0.25rem 0.5rem;
            text-align: right;
        }
    </style>
</head>
<body class="bg-gray-50">

    <div class="p-4 md:p-6">
        <div class="max-w-2xl mx-auto">
            
            <?php if ($update_message): ?>
                <div class="mb-4 p-4 rounded-lg <?= $update_message['type'] === 'success' ? 'bg-green-100 border-green-500 text-green-700' : 'bg-red-100 border-red-500 text-red-700' ?>" role="alert">
                    <p><?= htmlspecialchars($update_message['text']) ?></p>
                </div>
            <?php endif; ?>

            <?php if ($user_details): ?>
                <div class="bg-white rounded-xl p-6 border border-gray-200">
                    <div class="flex flex-col sm:flex-row items-center mb-6 text-center sm:text-left">
                        <div class="bg-indigo-100 p-4 rounded-full mb-4 sm:mb-0 sm:mr-5">
                            <i class="fas fa-user text-3xl text-indigo-600"></i>
                        </div>
                        <div>
                            <h2 class="text-2xl font-bold text-gray-800"><?= htmlspecialchars($user_details['first_name'] . ' ' . $user_details['last_name']) ?></h2>
                            <p class="text-gray-500"><?= htmlspecialchars($user_details['email']) ?></p>
                        </div>
                    </div>
                    
                    <?php if (isset($_SESSION['user_rules']) && $_SESSION['user_rules'] >= 1): ?>
                    <div class="text-right mb-4 view-mode">
                        <button id="editBtn" class="bg-indigo-600 text-white font-bold py-2 px-4 rounded-lg hover:bg-indigo-700 transition duration-300">
                            <i class="fas fa-pencil-alt mr-2"></i>Edit User
                        </button>
                    </div>
                    <?php endif; ?>

                    <form action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>?user_id=<?= $user_details['id'] ?>" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="hidden" name="user_id_to_update" value="<?= $user_details['id'] ?>">
                        
                        <div class="space-y-4">
                            <div class="flex justify-between items-center detail-row py-3">
                                <span class="font-medium text-gray-600"><i class="fas fa-id-card mr-2 text-indigo-400"></i>User ID</span>
                                <span class="text-gray-800 font-semibold"><?= htmlspecialchars($user_details['id']) ?></span>
                            </div>

                            <div class="flex justify-between items-center detail-row py-3">
                                <label for="user_rules" class="font-medium text-gray-600"><i class="fas fa-user-shield mr-2 text-indigo-400"></i>User Role</label>
                                <span class="text-gray-800 font-semibold view-mode"><?= htmlspecialchars(getRoleName($user_details['user_rules'])) ?></span>
                                <select name="user_rules" id="user_rules" class="edit-mode-input edit-mode hidden">
                                    <option value="0" <?= $user_details['user_rules'] == 0 ? 'selected' : '' ?>>User</option>
                                    <option value="1" <?= $user_details['user_rules'] == 1 ? 'selected' : '' ?>>Admin</option>
                                    <?php // Only Super Admins can assign the Super Admin role
                                    if ($_SESSION['user_rules'] == 2): ?>
                                    <option value="2" <?= $user_details['user_rules'] == 2 ? 'selected' : '' ?>>Super Admin</option>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <div class="flex justify-between items-center detail-row py-3">
                                <label for="contact_number" class="font-medium text-gray-600"><i class="fas fa-phone mr-2 text-indigo-400"></i>Contact Number</label>
                                <span class="text-gray-800 font-semibold view-mode"><?= htmlspecialchars($user_details['contact_number']) ?></span>
                                <input type="text" name="contact_number" id="contact_number" class="edit-mode-input edit-mode hidden" value="<?= htmlspecialchars($user_details['contact_number']) ?>">
                            </div>
                            
                            <div class="flex justify-between items-center detail-row py-3">
                                <label for="street" class="font-medium text-gray-600"><i class="fas fa-road mr-2 text-indigo-400"></i>Street</label>
                                <span class="text-gray-800 font-semibold view-mode"><?= htmlspecialchars($user_details['street']) ?></span>
                                <input type="text" name="street" id="street" class="edit-mode-input edit-mode hidden" value="<?= htmlspecialchars($user_details['street']) ?>">
                            </div>
                            <div class="flex justify-between items-center detail-row py-3">
                                <label for="barangay" class="font-medium text-gray-600"><i class="fas fa-map-marker-alt mr-2 text-indigo-400"></i>Barangay</label>
                                <span class="text-gray-800 font-semibold view-mode"><?= htmlspecialchars($user_details['barangay']) ?></span>
                                <input type="text" name="barangay" id="barangay" class="edit-mode-input edit-mode hidden" value="<?= htmlspecialchars($user_details['barangay']) ?>">
                            </div>
                            <div class="flex justify-between items-center detail-row py-3">
                                <label for="city" class="font-medium text-gray-600"><i class="fas fa-city mr-2 text-indigo-400"></i>City</label>
                                <span class="text-gray-800 font-semibold view-mode"><?= htmlspecialchars($user_details['city']) ?></span>
                                <input type="text" name="city" id="city" class="edit-mode-input edit-mode hidden" value="<?= htmlspecialchars($user_details['city']) ?>">
                            </div>
                             <div class="flex justify-between items-center detail-row py-3">
                                <label for="province" class="font-medium text-gray-600"><i class="fas fa-map-marked-alt mr-2 text-indigo-400"></i>Province</label>
                                <span class="text-gray-800 font-semibold view-mode"><?= htmlspecialchars($user_details['province']) ?></span>
                                <input type="text" name="province" id="province" class="edit-mode-input edit-mode hidden" value="<?= htmlspecialchars($user_details['province']) ?>">
                            </div>
                        </div>

                        <div class="mt-6 text-right edit-mode hidden">
                            <button type="button" id="cancelBtn" class="bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded-lg hover:bg-gray-400 transition duration-300 mr-2">
                                Cancel
                            </button>
                            <button type="submit" class="bg-green-500 text-white font-bold py-2 px-4 rounded-lg hover:bg-green-600 transition duration-300">
                                Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            <?php else: ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-lg" role="alert">
                    <p class="font-bold">Error</p>
                    <p><?= htmlspecialchars($error_message) ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>

<script>
    // Only run script if the edit button exists
    const editBtn = document.getElementById('editBtn');
    if (editBtn) {
        const cancelBtn = document.getElementById('cancelBtn');
        const viewModeElements = document.querySelectorAll('.view-mode');
        const editModeElements = document.querySelectorAll('.edit-mode');

        // Function to enter Edit Mode
        const enterEditMode = () => {
            viewModeElements.forEach(el => el.classList.add('hidden'));
            editModeElements.forEach(el => el.classList.remove('hidden'));
        };

        // Function to exit Edit Mode
        const exitEditMode = () => {
            viewModeElements.forEach(el => el.classList.remove('hidden'));
            editModeElements.forEach(el => el.classList.add('hidden'));
        };

        editBtn.addEventListener('click', enterEditMode);
        cancelBtn.addEventListener('click', exitEditMode);
    }
</script>

</body>
</html>