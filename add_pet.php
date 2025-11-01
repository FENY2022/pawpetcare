<?php
session_start();
require_once 'db.php'; // Database connection file

$toast_message = null; // Initialize message variable

// --- START: NEW CODE TO FETCH USER DATA ---

// 1. Initialize an array to hold user data
$user_data = [
    'first_name' => '',
    'middle_name' => '',
    'last_name' => '',
    'email' => ''
];

// 1.1. Check if the database function exists before trying to use it
if (!function_exists('get_db_connection')) {
    // Set a toast message if the function is missing, so the user sees it on the page
    $toast_message = ['type' => 'error', 'message' => 'Database function get_db_connection() is missing from db.php.'];
} 
// Only proceed if the function exists AND the user is logged in
else if (isset($_SESSION['user_id'])) { 
    $user_id = $_SESSION['user_id'];
    $conn_user = get_db_connection();

    if ($conn_user) {
// This is the corrected line 28
$sql_user = "SELECT first_name, middle_name, last_name, email FROM users WHERE id = ?";
        $stmt_user = $conn_user->prepare($sql_user);
        
        if ($stmt_user) {
            $stmt_user->bind_param("i", $user_id);
            $stmt_user->execute();
            $result_user = $stmt_user->get_result();
            
            if ($result_user->num_rows > 0) {
                $row = $result_user->fetch_assoc();
                $user_data['first_name'] = $row['first_name'];
                $user_data['middle_name'] = $row['middle_name'];
                $user_data['last_name'] = $row['last_name'];
                $user_data['email'] = $row['email'];
            }
            $stmt_user->close();
        }
        $conn_user->close();
    } else if (!$toast_message) {
        // Only set this if another error hasn't already been set
        $toast_message = ['type' => 'error', 'message' => 'Could not connect to database to fetch user data.'];
    }
}
// --- END: NEW CODE ---


if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Check DB connection function
    if (!function_exists('get_db_connection')) {
        // This 'die' is fine here, as it's for form submission, not page load
        die("Database function 'get_db_connection()' not found.");
    }

    $conn = get_db_connection();

    if ($conn === null) {
        $toast_message = ['type' => 'error', 'message' => 'Database connection failed. Check credentials.'];
    } else {
        $conn->begin_transaction();
        $all_ok = true;
        $error_msg = "";

        try {
            // --- 1. Insert Client ---
            $sql_client = "INSERT INTO clients 
                (reg_date, valid_until, client_lname, client_fname, client_mname, client_sex, client_bday, client_contact, client_email, addr_purok, addr_brgy, addr_mun, addr_prov) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt_client = $conn->prepare($sql_client);
            $stmt_client->bind_param(
                "sssssssssssss",
                $_POST['reg_date'],
                $_POST['valid_until'],
                $_POST['client_lname'],
                $_POST['client_fname'],
                $_POST['client_mname'],
                $_POST['client_sex'],
                $_POST['client_bday'],
                $_POST['client_contact'],
                $_POST['client_email'],
                $_POST['addr_purok'],
                $_POST['addr_brgy'],
                $_POST['addr_mun'],
                $_POST['addr_prov']
            );

            if (!$stmt_client->execute()) {
                $all_ok = false;
                $error_msg = $stmt_client->error;
            }

            $client_id = $conn->insert_id;
            $stmt_client->close();

            // --- 2. Insert Pet ---
            if ($all_ok) {
                $sql_pet = "INSERT INTO pets 
                    (client_id, pet_origin, pet_origin_other, pet_ownership, pet_habitat, pet_species, pet_name, pet_breed, pet_bday, pet_color, pet_sex, pet_is_pregnant, pet_is_lactating, pet_puppies, pet_weight, pet_tag_no, tag_type_collar, tag_type_other, tag_type_other_specify, pet_contact) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

                $stmt_pet = $conn->prepare($sql_pet);

                $pet_is_pregnant = isset($_POST['pet_is_pregnant']) ? 1 : 0;
                $pet_is_lactating = isset($_POST['pet_is_lactating']) ? 1 : 0;
                $pet_puppies = empty($_POST['pet_puppies']) ? null : (int)$_POST['pet_puppies'];
                $pet_weight = empty($_POST['pet_weight']) ? null : (float)$_POST['pet_weight'];
                $tag_type_collar = isset($_POST['tag_type_collar']) ? 1 : 0;
                $tag_type_other = isset($_POST['tag_type_other']) ? 1 : 0;

                $stmt_pet->bind_param(
                    "issssssssssiiidiiiss",
                    $client_id,
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
                    $_POST['pet_contact']
                );

                if (!$stmt_pet->execute()) {
                    $all_ok = false;
                    $error_msg = $stmt_pet->error;
                }

                $stmt_pet->close();
            }

            // --- 3. Commit or Rollback ---
            if ($all_ok) {
                $conn->commit();
                $toast_message = ['type' => 'success', 'message' => 'Pet registered successfully!'];
            } else {
                $conn->rollback();
                $toast_message = ['type' => 'error', 'message' => 'Registration failed: ' . htmlspecialchars($error_msg)];
            }

        } catch (Exception $e) {
            $conn->rollback();
            $toast_message = ['type' => 'error', 'message' => 'An unexpected error occurred: ' . $e->getMessage()];
        }

        $conn->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Pet - Pet Registration</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Apply Inter font to the body */
        body {
            font-family: 'Inter', sans-serif;
        }
        /* Custom styles for radio/checkbox */
        .form-radio, .form-checkbox {
            color: #4361ee; /* Using the primary color from your dashboard's CSS */
        }
        
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
<body class="bg-gray-100">

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

    <div class="container mx-auto p-4 py-8 md:p-10">
        <div class="max-w-5xl mx-auto bg-white p-6 md:p-10 rounded-2xl shadow-lg">

            <div class="text-center mb-10">
                <div class="flex justify-center mb-4">
                    <img src="logo/pawpetcarelogo.png" alt="Official Seal" class="h-20 w-20">
                </div>
                <p class="text-sm font-medium text-gray-700">Republic of the Philippines</p>
                <p class="text-sm font-medium text-gray-700">Province of Surigao del Sur</p>
                <p class="text-sm font-medium text-gray-700">Municipality of Cantilan</p>
                <h1 class="text-3xl font-bold text-gray-900 mt-4" style="color: #4361ee;">PET REGISTRATION</h1>
            </div>

            <form action="add_pet.php" method="POST">

                <fieldset class="mb-10">
                    <legend class="text-2xl font-semibold text-gray-800 border-b-2 border-gray-200 pb-2 mb-6 w-full">
                        Client Information
                    </legend>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                        <div>
                            <label for="reg_date" class="block text-sm font-medium text-gray-700 mb-1">Registration Date</label>
                            <input type="date" name="reg_date" id="reg_date" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div>
                            <label for="valid_until" class="block text-sm font-medium text-gray-700 mb-1">Valid Until</label>
                            <input type="date" name="valid_until" id="valid_until" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" required>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="md:col-span-3">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Name of Client</label>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                
                                <input type="text" name="client_lname" placeholder="Last Name" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" 
                                       value="<?php echo htmlspecialchars($user_data['last_name']); ?>" required>
                                
                                <input type="text" name="client_fname" placeholder="First Name" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" 
                                       value="<?php echo htmlspecialchars($user_data['first_name']); ?>" required>
                                
                                <input type="text" name="client_mname" placeholder="Middle Name" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                       value="<?php echo htmlspecialchars($user_data['middle_name']); ?>">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Sex</label>
                            <div class="flex items-center gap-6">
                                <label class="flex items-center">
                                    <input type="radio" name="client_sex" value="Male" class="h-4 w-4 form-radio" required>
                                    <span class="ml-2 text-sm text-gray-700">Male</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="radio" name="client_sex" value="Female" class="h-4 w-4 form-radio" required>
                                    <span class="ml-2 text-sm text-gray-700">Female</span>
                                </label>
                            </div>
                        </div>

                        <div>
                            <label for="client_bday" class="block text-sm font-medium text-gray-700 mb-1">Birthday</label>
                                <label for="client_bday" class="block text-sm font-medium text-gray-700">Birthday</label>
                                <input 
                                type="date" 
                                name="client_bday" 
                                id="client_bday" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" 
                                required
                                >

                                <script>
                                // Get today's date
                                const today = new Date();

                                // Subtract 18 years
                                const adultYear = today.getFullYear() - 18;
                                const adultMonth = String(today.getMonth() + 1).padStart(2, '0');
                                const adultDay = String(today.getDate()).padStart(2, '0');

                                // Format: YYYY-MM-DD
                                const maxDate = `${adultYear}-${adultMonth}-${adultDay}`;

                                // Set the max attribute to disallow minors
                                document.getElementById("client_bday").setAttribute("max", maxDate);
                                </script>
                        </div>
                        
                        <div>
                            <label for="client_contact" class="block text-sm font-medium text-gray-700 mb-1">Contact No.</label>
                            <input type="tel" name="client_contact" id="client_contact" placeholder="09xxxxxxxxx" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" required>
                        </div>

                        <div class="md:col-span-1">
                            <label for="client_email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                            
                            <input type="email" name="client_email" id="client_email" placeholder="example@email.com" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                   value="<?php echo htmlspecialchars($user_data['email']); ?>">
                        </div>

                        <div class="md:col-span-3">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                                <input type="text" name="addr_purok" placeholder="Purok/Sitio/Street" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" required>
                                <input type="text" name="addr_brgy" placeholder="Barangay" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" required>
                                <input type="text" name="addr_mun" placeholder="Municipality" value="Cantilan" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" required>
                                <input type="text" name="addr_prov" placeholder="Province" value="Surigao del Sur" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" required>
                            </div>
                        </div>
                    </div>
                </fieldset>

                <fieldset class="mb-10">
                    <legend class="text-2xl font-semibold text-gray-800 border-b-2 border-gray-200 pb-2 mb-6 w-full">
                        Pet Information
                    </legend>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-x-6 gap-y-8">
                        <div class="md:col-span-1">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Pet Origin</label>
                            <div class="flex flex-wrap items-center gap-x-6 gap-y-2">
                                <label class="flex items-center">
                                    <input type="radio" name="pet_origin" value="Local" class="h-4 w-4 form-radio" required>
                                    <span class="ml-2 text-sm text-gray-700">Local</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="radio" name="pet_origin" value="Others" class="h-4 w-4 form-radio" required>
                                    <span class="ml-2 text-sm text-gray-700">Others</span>
                                </label>
                                <input type="text" name="pet_origin_other" placeholder="Specify" class="mt-1 md:mt-0 flex-1 min-w-[120px] px-2 py-1 border border-gray-300 rounded-md shadow-sm text-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>

                        <div class="md:col-span-1">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Ownership</label>
                            <div class="flex flex-wrap items-center gap-x-6 gap-y-2">
                                <label class="flex items-center">
                                    <input type="radio" name="pet_ownership" value="Household" class="h-4 w-4 form-radio" required>
                                    <span class="ml-2 text-sm text-gray-700">Household</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="radio" name="pet_ownership" value="Community" class="h-4 w-4 form-radio" required>
                                    <span class="ml-2 text-sm text-gray-700">Community</span>
                                </label>
                            </div>
                        </div>
                        
                        <div class="md:col-span-3">
                             <label class="block text-sm font-medium text-gray-700 mb-2">Habitat</label>
                            <div class="flex flex-wrap items-center gap-x-6 gap-y-2">
                                <label class="flex items-center">
                                    <input type="radio" name="pet_habitat" value="Caged" class="h-4 w-4 form-radio" required>
                                    <span class="ml-2 text-sm text-gray-700">Caged</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="radio" name="pet_habitat" value="Free Roaming" class="h-4 w-4 form-radio" required>
                                    <span class="ml-2 text-sm text-gray-700">Free Roaming</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="radio" name="pet_habitat" value="Leash" class="h-4 w-4 form-radio" required>
                                    <span class="ml-2 text-sm text-gray-700">Leash</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="radio" name="pet_habitat" value="House Only" class="h-4 w-4 form-radio" required>
                                    <span class="ml-2 text-sm text-gray-700">House Only</span>
                                </label>
                            </div>
                        </div>

                        <hr class="md:col-span-3 my-2 border-gray-200">

                        <div class="md:col-span-1">
                             <label class="block text-sm font-medium text-gray-700 mb-2">Species</label>
                            <div class="flex flex-wrap items-center gap-x-6 gap-y-2">
                                <label class="flex items-center">
                                    <input type="radio" name="pet_species" value="Dog" class="h-4 w-4 form-radio" required>
                                    <span class="ml-2 text-sm text-gray-700">Dog</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="radio" name="pet_species" value="Cat" class="h-4 w-4 form-radio" required>
                                    <span class="ml-2 text-sm text-gray-700">Cat</span>
                                </label>
                            </div>
                        </div>

                        <div class="md:col-span-1">
                            <label for="pet_name" class="block text-sm font-medium text-gray-700 mb-1">Pet's Name</label>
                            <input type="text" name="pet_name" id="pet_name" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" required>
                        </div>

                        <div class="md:col-span-1">
                            <label for="pet_breed" class="block text-sm font-medium text-gray-700 mb-1">Breed</label>
                            <input type="text" name="pet_breed" id="pet_breed" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" required>
                        </div>

                        <div class="md:col-span-1">
                            <label for="pet_bday" class="block text-sm font-medium text-gray-700 mb-1">Date of Birth</label>
                            <input type="date" name="pet_bday" id="pet_bday" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" required>
                        </div>

                        <div class="md:col-span-1">
                            <label for="pet_color" class="block text-sm font-medium text-gray-700 mb-1">Animal Color</label>
                            <input type="text" name="pet_color" id="pet_color" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" required>
                        </div>

                        <div class="md:col-span-1">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Sex</label>
                            <div class="flex items-center gap-6">
                                <label class="flex items-center">
                                    <input type="radio" name="pet_sex" value="Male" class="h-4 w-4 form-radio" required>
                                    <span class="ml-2 text-sm text-gray-700">Male</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="radio" name="pet_sex" value="Female" class="h-4 w-4 form-radio" required>
                                    <span class="ml-2 text-sm text-gray-700">Female</span>
                                </label>
                            </div>
                        </div>
                        
                        <fieldset class="md:col-span-3 border border-gray-200 rounded-md p-4">
                            <legend class="text-sm font-medium text-gray-600 px-2">If Female:</legend>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div class="md:col-span-2 flex flex-wrap items-center gap-x-6 gap-y-2">
                                    <label class="flex items-center">
                                        <input type="checkbox" name="pet_is_pregnant" value="1" class="h-4 w-4 form-checkbox">
                                        <span class="ml-2 text-sm text-gray-700">Pregnant</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input type="checkbox" name="pet_is_lactating" value="1" class="h-4 w-4 form-checkbox">
                                        <span class="ml-2 text-sm text-gray-700">Lactating with puppies</span>
                                    </label>
                                </div>
                                <div>
                                    <label for="pet_puppies" class="block text-sm font-medium text-gray-700 mb-1">No. of Puppies</label>
                                    <input type="number" name="pet_puppies" id="pet_puppies" min="0" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                </div>
                            </div>
                        </fieldset>
                        
                        <div class="md:col-span-1">
                            <label for="pet_weight" class="block text-sm font-medium text-gray-700 mb-1">Weight (kgs)</label>
                            <input type="number" name="pet_weight" id="pet_weight" min="0" step="0.1" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <div class="md:col-span-1">
                            <label for="pet_tag_no" class="block text-sm font-medium text-gray-700 mb-1">Tag No.</label>
                            <input type="text" name="pet_tag_no" id="pet_tag_no" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        
                        <div class="md:col-span-3">
                             <div class="flex flex-wrap items-center gap-x-6 gap-y-2">
                                <label class="flex items-center">
                                    <input type="checkbox" name="tag_type_collar" value="1" class="h-4 w-4 form-checkbox">
                                    <span class="ml-2 text-sm text-gray-Two-HUNDRED">Collar Tag</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="tag_type_other" value="1" class="h-4 w-4 form-checkbox">
                                    <span class="ml-2 text-sm text-gray-700">Others</span>
                                </label>
                                <input type="text" name="tag_type_other_specify" placeholder="Specify" class="mt-1 md:mt-0 flex-1 min-w-[120px] px-2 py-1 border border-gray-300 rounded-md shadow-sm text-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>
                        
                        <div class="md:col-span-3">
                             <label class="block text-sm font-medium text-gray-700 mb-2">Contact with Other Animals</label>
                            <div class="flex flex-wrap items-center gap-x-6 gap-y-2">
                                <label class="flex items-center">
                                    <input type="radio" name="pet_contact" value="Frequent" class="h-4 w-4 form-radio" required>
                                    <span class="ml-2 text-sm text-gray-700">Frequent</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="radio" name="pet_contact" value="Seldom" class="h-4 w-4 form-radio" required>
                                    <span class="ml-2 text-sm text-gray-700">Seldom</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="radio" name="pet_contact" value="Never" class="h-4 w-4 form-radio" required>
                                    <span class="ml-2 text-sm text-gray-700">Never</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </fieldset>

                <div class="mt-12 pt-8 border-t border-gray-200">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
                        <div>
                            <div class="border-b border-gray-400 h-8 mb-2"></div>
                            <p class="text-sm font-medium text-gray-700 text-center">Barangay Rabies Control Officer</p>

                        </div>
                        <div>
                            <div class="border-b border-gray-400 h-8 mb-2"></div>
                            <p class="text-sm font-medium text-gray-700 text-center">Municipal Rabies Control Officer/AT</p>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end mt-10">
                    <button type="submit" class="px-8 py-3 bg-blue-600 text-white font-semibold rounded-lg shadow-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-75" style="background-color: #4361ee;">
                        Register Pet
                    </button>
                </div>

            </form>
        </div>
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
            // Clear any existing timeout
            if (toastTimeout) clearTimeout(toastTimeout);

            // Set content
            toastMessage.textContent = message;

            // Reset classes
            toast.classList.remove('bg-green-500', 'bg-red-500');
            
            // Set style based on type
            if (type === 'success') {
                toast.classList.add('bg-green-500');
                // Checkmark icon
                toastIcon.innerHTML = `<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>`;
            } else if (type === 'error') {
                toast.classList.add('bg-red-500');
                // Warning icon
                toastIcon.innerHTML = `<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099a.75.75 0 011.486 0l5.25 10.5a.75.75 0 01-.643 1.051H3.643a.75.75 0 01-.643-1.051l5.25-10.5zM9 9a1 1 0 00-1 1v3a1 1 0 002 0v-3a1 1 0 00-1-1zm1 6a1 1 0 10-2 0 1 1 0 002 0z" clip-rule="evenodd"></path></svg>`;
            }

            // Show toast
            toast.classList.add('show');

            // Hide after 5 seconds
            toastTimeout = setTimeout(() => {
                hideToast();
            }, 5000);
        }

        function hideToast() {
            toast.classList.remove('show');
        }
    </script>
    
    <?php
    // --- PHP TOAST TRIGGER ---
    // This script block will only be rendered if the form was processed
    // OR if there was an error on page load (like the missing function)
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