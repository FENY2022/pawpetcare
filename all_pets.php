<?php
session_start();
require_once 'db.php'; // Database connection file

$pets = []; // Initialize an empty array to hold pet data
$error_message = null; // Initialize error message
$client_id = null;

// 1. Check if user is logged in and has a user_id in the session
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['user_id'])) {
    $error_message = "You are not logged in. Please log in to view your pets.";
} else {
    $client_id = $_SESSION['user_id'];
}

// 2. Check if the database function exists and if the user is logged in
if ($client_id && !function_exists('get_db_connection')) {
    $error_message = 'Database function get_db_connection() is missing from db.php.';
} 
// 3. Only proceed if we have a client_id and no other errors
elseif ($client_id) {
    $conn = get_db_connection();

    if ($conn) {
        // 4. SQL Query to fetch pets FOR THE LOGGED-IN CLIENT
        // We no longer need to JOIN clients table since we are only showing pets for this user
        $sql = "SELECT 
                    pet_id,
                    pet_name,
                    pet_species,
                    pet_breed,
                    pet_sex,
                    pet_bday,
                    pet_image_path
                FROM 
                    pets
                WHERE 
                    client_id = ?
                ORDER BY 
                    created_at DESC"; // Show newest pets first

        $stmt = $conn->prepare($sql);
        
        if ($stmt) {
            // 5. Bind the client_id from the session
            $stmt->bind_param("i", $client_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            // 6. Fetch all pets into the $pets array
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $pets[] = $row;
                }
            }
            $stmt->close();
        } else {
            $error_message = 'Failed to prepare the SQL statement: ' . $conn->error;
        }
        $conn->close();
    } else {
        $error_message = 'Database connection failed.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Pets - Pet Registration</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f5f7fa;
        }
         /* Custom primary color */
        :root {
            --primary: #4361ee;
        }
    </style>
</head>
<body class="bg-gray-100">

    <div class="container mx-auto p-4 py-8 md:p-10">
        <div class="max-w-7xl mx-auto bg-white p-6 md:p-10 rounded-2xl shadow-lg">

            <div class="flex flex-col md:flex-row justify-between items-center mb-8">
                <h1 class="text-3xl font-bold text-gray-900" style="color: var(--primary);">
                    <i class="fas fa-paw mr-2"></i>My Pets
                </h1>
                <a href="add_pet.php" class="mt-4 md:mt-0 px-5 py-2.5 bg-blue-600 text-white font-semibold rounded-lg shadow-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-75" style="background-color: var(--primary);">
                    <i class="fas fa-plus mr-1"></i> Add New Pet
                </a>
            </div>

            <?php if ($error_message): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative mb-6" role="alert">
                    <strong class="font-bold">Error:</strong>
                    <span class="block sm:inline"><?php echo htmlspecialchars($error_message); ?></span>
                </div>
            <?php endif; ?>

            <div class="shadow-sm border border-gray-200 rounded-lg overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Pet
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Species
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Breed
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Sex
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Birthday
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Details
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                QR Code
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        
                        <?php if (count($pets) > 0): ?>
                            <?php foreach ($pets as $pet): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-12 w-12">
                                                <?php 
                                                // Check if image path exists and the file is accessible
                                                $image_path = htmlspecialchars($pet['pet_image_path']);
                                                if (!empty($image_path) && file_exists(trim($image_path, '/'))): 
                                                ?>
                                                    <img class="h-12 w-12 rounded-full object-cover" 
                                                         src="<?php echo $image_path; ?>" 
                                                         alt="<?php echo htmlspecialchars($pet['pet_name']); ?>">
                                                <?php else: ?>
                                                    <!-- Placeholder Icon -->
                                                    <div class="h-12 w-12 rounded-full bg-gray-200 flex items-center justify-center text-gray-500 font-medium text-xl">
                                                        <i class="fas fa-<?php echo strtolower($pet['pet_species']) == 'cat' ? 'cat' : 'dog'; ?>"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900">
                                                    <?php echo htmlspecialchars($pet['pet_name']); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            <?php echo $pet['pet_species'] == 'Dog' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800'; ?>">
                                            <?php echo htmlspecialchars($pet['pet_species']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo htmlspecialchars($pet['pet_breed']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo htmlspecialchars($pet['pet_sex']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php
                                            $bday = $pet['pet_bday'];
                                            if ($bday && strtotime($bday)) {
                                                echo htmlspecialchars(date('M j, Y', strtotime($bday)));
                                            } else {
                                                echo '—';
                                            }
                                        ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <a href="pet_details.php?id=<?php echo $pet['pet_id']; ?>" class="text-blue-600 hover:text-blue-900">
                                            View
                                        </a>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <a href="view_qr.php?id=<?php echo $pet['pet_id']; ?>" class="text-indigo-600 hover:text-indigo-900">
                                            Generate
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        
                        <?php elseif (!$error_message): ?>
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                    You have not registered any pets yet.
                                    <a href="add_pet.php" class="text-blue-600 hover:underline font-medium ml-1">Click here to add one!</a>
                                </td>
                            </tr>
                        <?php endif; ?>

                    </tbody>
                </table>
            </div>
            
        </div>
    </div>

</body>
</html>