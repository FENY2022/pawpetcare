<?php
require_once 'db.php'; // Database connection file

$pet = null;
$error_message = null;

// 1. Get Pet ID from URL
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $pet_id = $_GET['id'];

    $conn = get_db_connection();
    if ($conn) {
        // 2. SQL Query to fetch this specific pet
        $sql = "SELECT 
                    p.pet_id, p.pet_name, p.pet_species, p.pet_breed,
                    p.pet_sex, p.pet_bday, p.pet_image_path,
                    c.client_fname, c.client_lname
                FROM 
                    pets AS p
                JOIN 
                    clients AS c ON p.client_id = c.client_id
                WHERE 
                    p.pet_id = ?"; 
                
        $stmt = $conn->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param("i", $pet_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $pet = $result->fetch_assoc();
            } else {
                $error_message = "Pet not found.";
            }
            $stmt->close();
        } else {
            $error_message = 'Failed to prepare the SQL statement.';
        }
        $conn->close();
    } else {
        $error_message = 'Database connection failed.';
    }
} else {
    $error_message = 'No pet specified.';
}

/**
 * Function to calculate age from birthday
 */
function get_age($birthday) {
    if (!$birthday || !strtotime($birthday)) {
        return "Unknown";
    }
    $bday = new DateTime($birthday);
    $today = new DateTime();
    $diff = $today->diff($bday);
    
    if ($diff->y > 0) {
        return $diff->y . ' ' . ($diff->y > 1 ? 'years' : 'year') . ' old';
    } elseif ($diff->m > 0) {
        return $diff->m . ' ' . ($diff->m > 1 ? 'months' : 'month') . ' old';
    } else {
        return $diff->d . ' ' . ($diff->d > 1 ? 'days' : 'day') . ' old';
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pet Details</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-100">

    <div class="container mx-auto p-4 py-8 md:p-10">

        <?php if ($error_message): ?>
            <div class="max-w-md mx-auto bg-white p-10 rounded-2xl shadow-lg text-center">
                <h2 class="text-2xl font-bold text-red-600 mb-4">Error</h2>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative" role="alert">
                    <span class="block sm:inline"><?php echo htmlspecialchars($error_message); ?></span>
                </div>
                <a href="index.php" class="mt-6 inline-block px-5 py-2.5 bg-blue-600 text-white font-semibold rounded-lg shadow-md hover:bg-blue-700" style="background-color: #4361ee;">
                    Go Home
                </a>
            </div>

        <?php elseif ($pet): ?>
            <div class="max-w-2xl mx-auto bg-white rounded-2xl shadow-lg overflow-hidden">
                <div class="md:flex">
                    <div class="md:flex-shrink-0 md:w-1/2">
                        <?php if (!empty($pet['pet_image_path']) && file_exists($pet['pet_image_path'])): ?>
                            <img class="h-64 w-full object-cover md:h-full" 
                                 src="<?php echo htmlspecialchars($pet['pet_image_path']); ?>" 
                                 alt="<?php echo htmlspecialchars($pet['pet_name']); ?>">
                        <?php else: ?>
                            <div class="h-64 w-full md:h-full bg-gray-200 flex items-center justify-center">
                                <span class="text-gray-500 text-4xl font-medium">
                                    <?php echo htmlspecialchars(strtoupper(substr($pet['pet_name'], 0, 1))); ?>
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="p-8 md:w-1/2">
                        <h1 class="text-3xl font-bold text-gray-900" style="color: #4361ee;">
                            <?php echo htmlspecialchars($pet['pet_name']); ?>
                        </h1>
                        <p class="text-gray-600 text-lg mt-1">
                            <?php echo htmlspecialchars($pet['pet_breed']); ?>
                        </p>
                        
                        <div class="mt-6 border-t border-gray-200">
                            <dl class="divide-y divide-gray-200">
                                <div class="py-4 grid grid-cols-3 gap-4">
                                    <dt class="text-sm font-medium text-gray-500">Species</dt>
                                    <dd class="text-sm text-gray-900 col-span-2"><?php echo htmlspecialchars($pet['pet_species']); ?></dd>
                                </div>
                                <div class="py-4 grid grid-cols-3 gap-4">
                                    <dt class="text-sm font-medium text-gray-500">Sex</dt>
                                    <dd class="text-sm text-gray-900 col-span-2"><?php echo htmlspecialchars($pet['pet_sex']); ?></dd>
                                </div>
                                <div class="py-4 grid grid-cols-3 gap-4">
                                    <dt class="text-sm font-medium text-gray-500">Birthday</dt>
                                    <dd class="text-sm text-gray-900 col-span-2">
                                        <?php echo $pet['pet_bday'] ? htmlspecialchars(date('M j, Y', strtotime($pet['pet_bday']))) : 'Unknown'; ?>
                                    </dd>
                                </div>
                                <div class="py-4 grid grid-cols-3 gap-4">
                                    <dt class="text-sm font-medium text-gray-500">Age</dt>
                                    <dd class="text-sm text-gray-900 col-span-2"><?php echo get_age($pet['pet_bday']); ?></dd>
                                </div>
                                <div class="py-4 grid grid-cols-3 gap-4">
                                    <dt class="text-sm font-medium text-gray-500">Owner</dt>
                                    <dd class="text-sm text-gray-900 col-span-2"><?php echo htmlspecialchars($pet['client_fname'] . ' ' . substr($pet['client_lname'], 0, 1) . '.'); ?></dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

    </div>

</body>
</html>