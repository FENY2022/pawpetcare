<?php
session_start();

// Check if user is logged in and is authorized (Healthcare/Admin: 1 or 2)
if (!isset($_SESSION['user_rules']) || $_SESSION['user_rules'] < 1) {
    echo "<p class='text-red-600 p-4'>Access Denied. You must be an administrator or healthcare staff to view this page.</p>";
    exit;
}

// Assumes db.php contains get_db_connection()
include_once 'db.php'; 

// --- 1. HANDLE PET APPROVAL ACTION ---
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'approve' && isset($_GET['pet_id'])) {
    $pet_id_to_approve = (int)$_GET['pet_id'];
    $conn = get_db_connection();

    // SQL to update the pet's approval status
    $update_sql = "UPDATE pets SET is_approved = 1 WHERE pet_id = ?";
    
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("i", $pet_id_to_approve);

    if ($stmt->execute()) {
        $message = "Pet ID $pet_id_to_approve has been successfully approved! ✅";
        $success = true;
    } else {
        $message = "Error approving pet: " . $conn->error;
        $success = false;
    }

    $stmt->close();
    $conn->close();

    // Redirect to clear the GET parameters and prevent re-submission
    header("Location: dashboard.php?action=manage_clients_pets&msg=" . urlencode($message) . "&status=" . ($success ? 'success' : 'error'));
    exit;
}
// --- END APPROVAL HANDLER ---

// --- 2. FETCH PENDING AND APPROVED PETS ---
$conn = get_db_connection();
$pending_pets = [];
$approved_pets = [];

// Query to get pet details and owner name
$sql = "
    SELECT 
        p.*, 
        c.client_fname, 
        c.client_lname 
    FROM 
        pets p
    JOIN 
        clients c ON p.client_id = c.client_id
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Clients & Pets</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="p-6 bg-gray-50 min-h-screen">

    <h2 class="text-3xl font-bold text-gray-800 mb-6">Manage Client & Pet Registrations</h2>

    <?php 
    // Display status messages after redirection
    if (isset($_GET['msg']) && isset($_GET['status'])) {
        $alert_class = $_GET['status'] === 'success' ? 'bg-green-100 border-green-400 text-green-700' : 'bg-red-100 border-red-400 text-red-700';
        echo '<div class="' . $alert_class . ' border px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline">' . htmlspecialchars($_GET['msg']) . '</span>
              </div>';
    }
    if (isset($error_msg)) {
         echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline">' . htmlspecialchars($error_msg) . '</span>
              </div>';
    }
    ?>

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
                        <p class="text-sm text-gray-600 mb-3">Owner: <?php echo htmlspecialchars($pet['client_fname'] . ' ' . $pet['client_lname']); ?></p>
                        
                        <ul class="text-sm text-gray-700 space-y-1">
                            <li><span class="font-medium">Breed:</span> <?php echo htmlspecialchars($pet['pet_breed']); ?></li>
                            <li><span class="font-medium">DOB:</span> <?php echo htmlspecialchars($pet['pet_bday']); ?></li>
                            <li><span class="font-medium">Registration Date:</span> <?php echo date('M d, Y', strtotime($pet['created_at'])); ?></li>
                        </ul>
                        
                        <a href="dashboard.php?action=manage_clients_pets&action=approve&pet_id=<?php echo $pet['pet_id']; ?>" 
                           class="mt-4 block w-full text-center py-2 px-4 rounded-lg text-white font-semibold bg-green-500 hover:bg-green-600 transition duration-150">
                            <i class="fas fa-check-circle mr-1"></i> Approve Pet
                        </a>
                        </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

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
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"><?php echo htmlspecialchars($pet['client_fname'] . ' ' . $pet['client_lname']); ?></td>
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

</body>
</html>