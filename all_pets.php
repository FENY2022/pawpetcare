<?php
session_start();
require_once 'db.php'; // Database connection file

$pets = []; // Initialize an empty array to hold pet data
$error_message = null; // Initialize error message

// 1. Check if the database function exists
if (!function_exists('get_db_connection')) {
    $error_message = 'Database function get_db_connection() is missing from db.php.';
} else {
    $conn = get_db_connection();

    if ($conn) {
        // 2. SQL Query to fetch pets and join with client names
        // We use a JOIN to get the owner's name from the 'clients' table
        $sql = "SELECT 
                    p.pet_id,
                    p.pet_name,
                    p.pet_species,
                    p.pet_breed,
                    p.pet_sex,
                    p.pet_bday,
                    p.pet_image_path,
                    c.client_fname,
                    c.client_lname
                FROM 
                    pets AS p
                JOIN 
                    clients AS c ON p.client_id = c.client_id
                ORDER BY 
                    p.created_at DESC"; // Show newest pets first

        $stmt = $conn->prepare($sql);
        
        if ($stmt) {
            $stmt->execute();
            $result = $stmt->get_result();
            
            // 3. Fetch all pets into the $pets array
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $pets[] = $row;
                }
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View All Pets - Pet Registration</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>
<body class="bg-gray-100">

    <div class="container mx-auto p-4 py-8 md:p-10">
        <div class="max-w-7xl mx-auto bg-white p-6 md:p-10 rounded-2xl shadow-lg">

            <div class="flex flex-col md:flex-row justify-between items-center mb-8">
                <h1 class="text-3xl font-bold text-gray-900" style="color: #4361ee;">
                    Registered Pets
                </h1>
                <a href="add_pet.php" class="mt-4 md:mt-0 px-5 py-2.5 bg-blue-600 text-white font-semibold rounded-lg shadow-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-75" style="background-color: #4361ee;">
                    + Add New Pet
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
                                Owner
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
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        
                        <?php if (count($pets) > 0): ?>
                            <?php foreach ($pets as $pet): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-12 w-12">
                                                <?php if (!empty($pet['pet_image_path']) && file_exists($pet['pet_image_path'])): ?>
                                                    <img class="h-12 w-12 rounded-full object-cover" 
                                                         src="<?php echo htmlspecialchars($pet['pet_image_path']); ?>" 
                                                         alt="<?php echo htmlspecialchars($pet['pet_name']); ?>">
                                                <?php else: ?>
                                                    <div class="h-12 w-12 rounded-full bg-gray-200 flex items-center justify-center text-gray-500 font-medium text-xl">
                                                        <?php echo htmlspecialchars(strtoupper(substr($pet['pet_name'], 0, 1))); ?>
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
                                        <div class="text-sm text-gray-900">
                                            <?php echo htmlspecialchars($pet['client_fname'] . ' ' . $pet['client_lname']); ?>
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
                                            // Format birthday as "Month Day, Year" (e.g., Jan 1, 2020)
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
                                </tr>
                            <?php endforeach; ?>
                        
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                                    No pets have been registered yet.
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
