<?php
session_start();
require_once 'db.php'; // Database connection file

$pets = []; // Initialize an empty array to hold pet data
$error_message = null; // Initialize error message
$client_id = null;

// 1. Check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['user_id'])) {
    $error_message = "You are not logged in. Please log in to view your pets.";
} else {
    $client_id = $_SESSION['user_id'];
}

// 2. Only proceed if we have a client_id
if ($client_id) {
    $conn = get_db_connection();
    if ($conn) {
        // 3. SQL Query to fetch pets FOR THE LOGGED-IN CLIENT
        $sql = "SELECT 
                    pet_id,
                    pet_name,
                    pet_species,
                    pet_breed,
                    pet_image_path
                FROM 
                    pets
                WHERE 
                    client_id = ?
                ORDER BY 
                    pet_name ASC";

        $stmt = $conn->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param("i", $client_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    // --- SIMULATED LICENSE STATUS ---
                    // This is placeholder logic because the 'pets' table doesn't have license info.
                    // We use the pet_id to create different statuses for demonstration.
                    if ($row['pet_id'] % 3 == 0) {
                        $row['license_status'] = 'Active';
                        $row['license_expiry'] = date('M j, Y', strtotime('+'.($row['pet_id'] % 12 + 1).' months'));
                        $row['license_action'] = 'View License';
                        $row['license_action_color'] = 'bg-blue-600 hover:bg-blue-700';
                        $row['license_status_color'] = 'bg-green-100 text-green-800';
                    } elseif ($row['pet_id'] % 3 == 1) {
                        $row['license_status'] = 'Pending';
                        $row['license_expiry'] = 'N/A';
                        $row['license_action'] = 'View Application';
                        $row['license_action_color'] = 'bg-yellow-500 hover:bg-yellow-600';
                        $row['license_status_color'] = 'bg-yellow-100 text-yellow-800';
                    } else {
                        $row['license_status'] = 'Not Licensed';
                        $row['license_expiry'] = 'N/A';
                        $row['license_action'] = 'Apply for License';
                        $row['license_action_color'] = 'bg-gray-600 hover:bg-gray-700';
                        $row['license_status_color'] = 'bg-red-100 text-red-800';
                    }
                    // --- END SIMULATION ---
                    
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
    <title>Pet Licenses - PawPetCares</title>
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
        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            overflow: hidden;
        }
        .card:hover {
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.08);
            transform: translateY(-4px);
        }
    </style>
</head>
<body class="bg-gray-100">

    <div class="container mx-auto p-4 py-8 md:p-10">
        <div class="max-w-7xl mx-auto">

            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900" style="color: var(--primary);">
                    <i class="fas fa-id-card mr-2"></i>Pet License Management
                </h1>
                <p class="text-gray-600 mt-2">View license status, apply for new licenses, or renew existing ones for your pets.</p>
            </div>

            <?php if ($error_message): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative mb-6" role="alert">
                    <strong class="font-bold">Error:</strong>
                    <span class="block sm:inline"><?php echo htmlspecialchars($error_message); ?></span>
                </div>
            <?php endif; ?>

            <!-- Pet License Cards Grid -->
            <?php if (count($pets) > 0): ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($pets as $pet): ?>
                        <div class="card flex flex-col">
                            <!-- Pet Info Section -->
                            <div class="flex items-center p-6">
                                <div class="flex-shrink-0 h-16 w-16">
                                    <?php 
                                    $image_path = htmlspecialchars($pet['pet_image_path']);
                                    if (!empty($image_path) && file_exists(trim($image_path, '/'))): 
                                    ?>
                                        <img class="h-16 w-16 rounded-full object-cover" 
                                             src="<?php echo $image_path; ?>" 
                                             alt="<?php echo htmlspecialchars($pet['pet_name']); ?>">
                                    <?php else: ?>
                                        <div class="h-16 w-16 rounded-full bg-gray-200 flex items-center justify-center text-gray-500 font-medium text-3xl">
                                            <i class="fas fa-<?php echo strtolower($pet['pet_species']) == 'cat' ? 'cat' : 'dog'; ?>"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="ml-4">
                                    <h2 class="text-xl font-bold text-gray-900"><?php echo htmlspecialchars($pet['pet_name']); ?></h2>
                                    <p class="text-sm text-gray-600"><?php echo htmlspecialchars($pet['pet_species']) . ' - ' . htmlspecialchars($pet['pet_breed']); ?></p>
                                </div>
                            </div>
                            
                            <!-- License Info Section -->
                            <div class="bg-gray-50 p-6 border-t border-gray-100 flex-grow">
                                <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">License Details</h3>
                                
                                <div class="flex justify-between items-center mb-4">
                                    <span class="text-sm font-medium text-gray-700">Status</span>
                                    <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        <?php echo $pet['license_status_color']; ?>">
                                        <?php echo htmlspecialchars($pet['license_status']); ?>
                                    </span>
                                </div>
                                
                                <div class="flex justify-between items-center">
                                    <span class="text-sm font-medium text-gray-700">Expiry Date</span>
                                    <span class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($pet['license_expiry']); ?></span>
                                </div>
                            </div>

                            <!-- Action Button -->
                            <div class="p-6 bg-gray-50 border-t border-gray-100">
                                <a href="#" class="block w-full text-center px-4 py-3 text-sm font-medium text-white <?php echo $pet['license_action_color']; ?> rounded-lg shadow-md transition-colors duration-200">
                                    <?php echo htmlspecialchars($pet['license_action']); ?>
                                    <i class="fas fa-arrow-right ml-1 opacity-75"></i>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            
            <?php elseif (!$error_message): ?>
                <div class="text-center bg-white p-12 rounded-lg shadow-md">
                    <i class="fas fa-paw text-6xl text-gray-300 mb-4"></i>
                    <h2 class="text-xl font-medium text-gray-700">No Pets Found</h2>
                    <p class="text-gray-500 mt-2 mb-6">You haven't registered any pets yet. Please add a pet to manage their license.</p>
                    <a href="dashboard.php?action=add_pet" class="px-5 py-2.5 bg-blue-600 text-white font-semibold rounded-lg shadow-md hover:bg-blue-700" style="background-color: var(--primary);">
                        <i class="fas fa-plus mr-1"></i> Add New Pet
                    </a>
                </div>
            <?php endif; ?>
            
        </div>
    </div>

</body>
</html>