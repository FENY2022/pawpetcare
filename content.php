<?php
session_start();

// Include the database connection function (assuming db.php is available in the parent context or current directory)
if (file_exists('db.php')) {
    include_once 'db.php';
} else {
    // This warning helps in debugging if the file is run standalone
    // In the iframe setup of dashboard.php, this error might be hidden, but it's important.
    // If the iframe context doesn't have access to db.php, the parent file (dashboard.php)
    // should pass the connection or data. We'll proceed assuming direct access is possible.
    // Fallback: Use placeholders if connection fails
    $db_error = "Database configuration file 'db.php' not found. Data will be static.";
    // For live code, we must die if db connection is critical
    // die($db_error); 
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "<p class='p-4 text-red-600 font-semibold'>User session not active. Please log in.</p>";
    exit;
}

$client_id = (int)$_SESSION['user_id'];
$dashboard_data = [
    'registered_pets_count' => 0,
    'last_added_pet' => ['name' => 'N/A', 'species' => ''],
    'total_vaccinations' => 0,
    'next_vaccination_due' => 'N/A',
    'upcoming_appointments_count' => 0,
    'pending_actions_count' => 0,
    'my_pets' => []
];

// Data fetching only proceeds if get_db_connection function is available
if (function_exists('get_db_connection')) {
    $conn = get_db_connection();

    // ----------------------------------------------------
    // 1. Fetch Dashboard Statistics
    // ----------------------------------------------------
    $sql_stats = "
        SELECT 
            COUNT(DISTINCT p.pet_id) AS registered_pets_count,
            COUNT(v.id) AS total_vaccinations,
            SUM(CASE WHEN v.status = 'Scheduled' AND v.next_due > CURDATE() THEN 1 ELSE 0 END) AS upcoming_appointments,
            SUM(CASE WHEN v.status = 'Pending' THEN 1 ELSE 0 END) AS pending_actions,
            MIN(CASE WHEN v.status IN ('Scheduled', 'Pending') AND v.next_due > CURDATE() THEN v.next_due ELSE NULL END) AS next_due_date
        FROM pets p
        LEFT JOIN vaccinations v ON p.pet_id = v.pet_id
        WHERE p.client_id = ? AND p.is_approved = 1
    ";
    $stmt_stats = $conn->prepare($sql_stats);
    $stmt_stats->bind_param("i", $client_id);
    $stmt_stats->execute();
    $stats_result = $stmt_stats->get_result();
    $stats_row = $stats_result->fetch_assoc();
    $stmt_stats->close();

    if ($stats_row) {
        $dashboard_data['registered_pets_count'] = $stats_row['registered_pets_count'] ?? 0;
        $dashboard_data['total_vaccinations'] = $stats_row['total_vaccinations'] ?? 0;
        $dashboard_data['upcoming_appointments_count'] = $stats_row['upcoming_appointments'] ?? 0;
        $dashboard_data['pending_actions_count'] = $stats_row['pending_actions'] ?? 0;
        $next_due = $stats_row['next_due_date'];
        $dashboard_data['next_vaccination_due'] = $next_due ? date('M d, Y', strtotime($next_due)) : 'N/A';
    }

    // ----------------------------------------------------
    // 2. Fetch My Pets Details (for the list)
    // ----------------------------------------------------
    $sql_pet_details = "
        SELECT 
            p.pet_id, p.pet_name, p.pet_species, p.pet_breed, p.pet_bday, p.pet_image_path,
            -- Check for any Scheduled/Pending vaccination due in the future
            MAX(CASE WHEN v.status IN ('Scheduled', 'Pending') AND v.next_due > CURDATE() THEN 1 ELSE 0 END) AS is_vaccination_due,
            -- Simple License Check: Has a completed Anti-Rabies vaccine in the last year
            MAX(CASE WHEN v.status = 'Completed' AND v.vaccine_name = 'Anti-Rabies' AND v.date_given >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR) THEN 1 ELSE 0 END) AS is_licensed,
            MAX(v.next_due) as latest_next_due 
        FROM pets p
        LEFT JOIN vaccinations v ON p.pet_id = v.pet_id
        WHERE p.client_id = ? AND p.is_approved = 1
        GROUP BY p.pet_id, p.pet_name, p.pet_species, p.pet_breed, p.pet_bday, p.pet_image_path
        ORDER BY p.pet_name ASC
    ";
    $stmt_details = $conn->prepare($sql_pet_details);
    $stmt_details->bind_param("i", $client_id);
    $stmt_details->execute();
    $result_details = $stmt_details->get_result();

    while ($row = $result_details->fetch_assoc()) {
        $dashboard_data['my_pets'][] = $row;
    }
    $stmt_details->close();

    // Get the last added pet's name for the first stats card
    if (!empty($dashboard_data['my_pets'])) {
        $last_pet_sql = "SELECT pet_name, pet_species FROM pets WHERE client_id = ? AND is_approved = 1 ORDER BY created_at DESC LIMIT 1";
        $last_pet_stmt = $conn->prepare($last_pet_sql);
        $last_pet_stmt->bind_param("i", $client_id);
        $last_pet_stmt->execute();
        $last_pet_result = $last_pet_stmt->get_result();
        if ($last_pet = $last_pet_result->fetch_assoc()) {
            $dashboard_data['last_added_pet']['name'] = htmlspecialchars($last_pet['pet_name']);
            $dashboard_data['last_added_pet']['species'] = htmlspecialchars($last_pet['pet_species']);
        }
        $last_pet_stmt->close();
    }
    
    $conn->close();
}


// Helper to determine the animal icon
function get_species_icon($species) {
    return strtolower($species) === 'dog' ? 'fa-dog' : (strtolower($species) === 'cat' ? 'fa-cat' : 'fa-paw');
}

// Helper to calculate age (simple version)
function calculate_age($bday) {
    if (empty($bday) || $bday === '0000-00-00') return 'Age Unknown';
    $birthDate = new DateTime($bday);
    $today = new DateTime('today');
    $age = $birthDate->diff($today);

    if ($age->y > 0) return $age->y . ' year' . ($age->y > 1 ? 's' : '') . ' old';
    if ($age->m > 0) return $age->m . ' month' . ($age->m > 1 ? 's' : '') . ' old';
    return 'Less than a month old';
}

$client_name = htmlspecialchars($_SESSION['first_name'] ?? 'Client');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PetCare Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#4361ee', // Using the primary color from dashboard.php
                        secondary: '#7c73d9',
                        accent: '#f97316',
                        light: '#f8fafc',
                        dark: '#1e293b'
                    }
                }
            }
        }
    </script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f1f5f9;
        }
        
        .card {
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
        }
        
        .stats-card {
            /* Dynamic border color set inline below */
            border-left: 5px solid; 
        }
        
        .pet-avatar {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            object-fit: cover;
            border: 2px solid #e5e7eb;
        }
        
        .qr-code img {
            width: 50px;
            height: 50px;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
        }
        
        .btn-primary {
            background-color: #4361ee;
            color: white;
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.2s ease;
        }
        
        .btn-primary:hover {
            background-color: #3a56d4;
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="flex-1 p-6 md:p-8">
        <header class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8">
            <div>
                <h2 class="text-2xl font-bold text-dark">Client Dashboard</h2>
                <p class="text-sm text-gray-500">Welcome back, <?php echo $client_name; ?>! Here's your pet overview.</p>
            </div>
            
            <!-- User profile area - Keeping static for content.php, dashboard.php handles it -->
            <div class="flex items-center space-x-4 mt-4 md:mt-0">
                <div class="flex items-center bg-white pl-1 pr-4 py-1 rounded-full shadow-sm border border-gray-200">
                    <img src="https://i.pravatar.cc/150?u=<?php echo $client_id; ?>" alt="User" class="h-10 w-10 rounded-full object-cover">
                    <span class="ml-2 text-gray-700 font-medium"><?php echo $client_name; ?></span>
                </div>
            </div>
        </header>

        <!-- Dynamic Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            
            <!-- 1. Registered Pets -->
            <div class="stats-card card p-6 bg-white" style="border-left-color: #4361ee;">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-gray-500 text-sm font-medium">Registered Pets</p>
                        <h3 class="text-3xl font-bold text-dark mt-1"><?php echo $dashboard_data['registered_pets_count']; ?></h3>
                    </div>
                    <div class="bg-blue-100 p-3 rounded-xl">
                        <i class="fas fa-paw text-blue-600 text-xl"></i>
                    </div>
                </div>
                <p class="text-xs text-gray-500 mt-4 flex items-center">
                    <span class="w-2 h-2 bg-blue-500 rounded-full mr-2"></span>
                    Last added: <?php echo $dashboard_data['last_added_pet']['name']; ?> (<?php echo $dashboard_data['last_added_pet']['species']; ?>)
                </p>
            </div>
            
            <!-- 2. Total Vaccinations (Total History) -->
            <div class="stats-card card p-6 bg-white" style="border-left-color: #10b981;">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-gray-500 text-sm font-medium">Total Vaccinations</p>
                        <h3 class="text-3xl font-bold text-dark mt-1"><?php echo $dashboard_data['total_vaccinations']; ?></h3>
                    </div>
                    <div class="bg-green-100 p-3 rounded-xl">
                        <i class="fas fa-syringe text-green-600 text-xl"></i>
                    </div>
                </div>
                <p class="text-xs text-gray-500 mt-4 flex items-center">
                    <span class="w-2 h-2 bg-green-500 rounded-full mr-2"></span>
                    Next Due: <?php echo $dashboard_data['next_vaccination_due']; ?>
                </p>
            </div>
            
            <!-- 3. Upcoming Appointments (Scheduled Future Vaccinations) -->
            <div class="stats-card card p-6 bg-white" style="border-left-color: #f59e0b;">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-gray-500 text-sm font-medium">Upcoming Appointments</p>
                        <h3 class="text-3xl font-bold text-dark mt-1"><?php echo $dashboard_data['upcoming_appointments_count']; ?></h3>
                    </div>
                    <div class="bg-yellow-100 p-3 rounded-xl">
                        <i class="fas fa-calendar-alt text-yellow-600 text-xl"></i>
                    </div>
                </div>
                <p class="text-xs text-gray-500 mt-4 flex items-center">
                    <span class="w-2 h-2 bg-yellow-500 rounded-full mr-2"></span>
                    Next scheduled: <?php echo $dashboard_data['next_vaccination_due']; ?>
                </p>
            </div>
            
            <!-- 4. Pending Actions (Pending Vaccination Requests) -->
            <div class="stats-card card p-6 bg-white" style="border-left-color: #ef4444;">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-gray-500 text-sm font-medium">Pending Actions</p>
                        <h3 class="text-3xl font-bold text-dark mt-1"><?php echo $dashboard_data['pending_actions_count']; ?></h3>
                    </div>
                    <div class="bg-red-100 p-3 rounded-xl">
                        <i class="fas fa-exclamation-circle text-red-600 text-xl"></i>
                    </div>
                </div>
                <p class="text-xs text-gray-500 mt-4 flex items-center">
                    <span class="w-2 h-2 bg-red-500 rounded-full mr-2"></span>
                    Pending vaccination requests
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                <!-- My Pets List -->
                <div class="card p-6 bg-white">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-xl font-semibold text-dark">My Registered Pets (<?php echo $dashboard_data['registered_pets_count']; ?>)</h3>
                        <a href="add_pet.php" class="btn-primary text-sm">
                            <i class="fas fa-plus mr-2"></i> Add Pet
                        </a>
                    </div>
                    
                    <div class="space-y-4">
                        <?php if (empty($dashboard_data['my_pets'])): ?>
                            <div class="p-6 bg-gray-50 rounded-lg text-center text-gray-500 border border-dashed border-gray-300">
                                <i class="fas fa-box-open text-3xl mb-3 text-gray-400"></i>
                                <p class="font-medium">No approved pets registered yet.</p>
                                <p class="text-sm">Click 'Add Pet' to register your companion!</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($dashboard_data['my_pets'] as $pet): 
                                $pet_id = htmlspecialchars($pet['pet_id']);
                                $pet_name = htmlspecialchars($pet['pet_name']);
                                $pet_species = htmlspecialchars($pet['pet_species']);
                                $pet_breed = htmlspecialchars($pet['pet_breed']);
                                $pet_age = calculate_age($pet['pet_bday']);
                                $pet_icon = get_species_icon($pet_species);
                                $imgSrc = empty($pet['pet_image_path']) ? 'https://placehold.co/150x150/4361ee/ffffff?text=' . substr($pet_name, 0, 1) : htmlspecialchars($pet['pet_image_path']);
                                
                                $is_licensed = $pet['is_licensed'];
                                $vaccination_due = $pet['is_vaccination_due'];

                                // Status Badges
                                $license_badge = $is_licensed 
                                    ? '<span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full font-medium">Licensed</span>'
                                    : '<span class="bg-red-100 text-red-800 text-xs px-2 py-1 rounded-full font-medium">License Due</span>';
                                
                                $vaccine_badge = $vaccination_due 
                                    ? '<span class="bg-yellow-100 text-yellow-800 text-xs px-2 py-1 rounded-full font-medium ml-2">Vaccine Due</span>'
                                    : '<span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full font-medium ml-2">Up-to-date</span>';
                            ?>
                                <div class="flex items-center justify-between p-4 border border-gray-100 rounded-xl bg-white hover:bg-blue-50 transition-colors">
                                    <div class="flex items-center">
                                        <div class="relative">
                                            <img src="<?php echo $imgSrc; ?>" onerror="this.onerror=null;this.src='https://placehold.co/60x60/4361ee/ffffff?text=<?php echo substr($pet_name, 0, 1); ?>';" alt="Pet" class="pet-avatar mr-4">
                                            <span class="absolute -bottom-1 -right-1 bg-primary text-white rounded-full text-xs w-5 h-5 flex items-center justify-center">
                                                <i class="fas <?php echo $pet_icon; ?> text-xs"></i>
                                            </span>
                                        </div>
                                        <div>
                                            <h4 class="font-bold text-dark text-lg"><?php echo $pet_name; ?></h4>
                                            <p class="text-sm text-gray-500"><?php echo $pet_breed; ?> • <?php echo $pet_age; ?></p>
                                            <div class="flex items-center mt-1">
                                                <?php echo $license_badge; ?>
                                                <?php echo $vaccine_badge; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-4">
                                        <a href="all_pets.php?action=view_pet&id=<?php echo $pet_id; ?>" class="text-primary hover:text-blue-700 font-medium text-sm">View Details</a>
                                        <div class="qr-code hidden sm:block">
                                            <!-- Example QR code for pet ID -->
                                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=50x50&data=PetID-<?php echo $pet_id; ?>" alt="QR Code">
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Alerts & Reminders Section (Can be further customized based on your `notifications` table) -->
                <div class="card p-6 bg-white">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-xl font-semibold text-dark">Alerts & Reminders</h3>
                        <a href="myvaccinations.php" class="text-sm text-primary font-medium hover:text-secondary">View All</a>
                    </div>
                    
                    <div class="space-y-4">
                        <?php if ($dashboard_data['pending_actions_count'] > 0): ?>
                        <div class="flex items-start p-4 bg-red-50 rounded-lg border-l-4 border-red-500">
                            <div class="bg-red-100 p-2 rounded-lg mr-3">
                                <i class="fas fa-bell text-red-600"></i>
                            </div>
                            <div>
                                <h4 class="font-medium text-dark">Action Required: Pending Requests</h4>
                                <p class="text-sm text-gray-600">You have **<?php echo $dashboard_data['pending_actions_count']; ?>** pending requests for vaccinations waiting for staff approval.</p>
                                <a href="dashboard.php?action=myvaccinations" class="text-xs text-red-600 mt-1 hover:underline">Review requests</a>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if ($dashboard_data['upcoming_appointments_count'] > 0): ?>
                        <div class="flex items-start p-4 bg-yellow-50 rounded-lg border-l-4 border-yellow-500">
                            <div class="bg-yellow-100 p-2 rounded-lg mr-3">
                                <i class="fas fa-calendar-check text-yellow-600"></i>
                            </div>
                            <div>
                                <h4 class="font-medium text-dark">Upcoming Appointment Reminder</h4>
                                <p class="text-sm text-gray-600">You have **<?php echo $dashboard_data['upcoming_appointments_count']; ?>** scheduled services. Next due is **<?php echo $dashboard_data['next_vaccination_due']; ?>**.</p>
                                <a href="dashboard.php?action=appointments" class="text-xs text-yellow-600 mt-1 hover:underline">View appointment details</a>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if ($dashboard_data['pending_actions_count'] === 0 && $dashboard_data['upcoming_appointments_count'] === 0): ?>
                            <div class="flex items-start p-4 bg-green-50 rounded-lg border-l-4 border-green-500">
                                <div class="bg-green-100 p-2 rounded-lg mr-3">
                                    <i class="fas fa-check-circle text-green-600"></i>
                                </div>
                                <div>
                                    <h4 class="font-medium text-dark">All Clear!</h4>
                                    <p class="text-sm text-gray-600">No pending actions or immediate appointments needed right now. Keep up the great work!</p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="space-y-6">
                <!-- Quick Actions -->
                <div class="card p-6 bg-white">
                    <h3 class="text-xl font-semibold text-dark mb-6">Quick Actions</h3>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <a href="license.php" class="flex flex-col items-center justify-center p-4 border border-gray-100 rounded-xl hover:bg-gray-50 transition-colors shadow-sm">
                            <div class="bg-blue-100 p-3 rounded-full mb-2">
                                <i class="fas fa-id-card text-blue-600 text-xl"></i>
                            </div>
                            <span class="text-sm font-semibold text-gray-700 text-center">Pet License</span>
                        </a>
                        
                        <a href="my_vaccinations.php" class="flex flex-col items-center justify-center p-4 border border-gray-100 rounded-xl hover:bg-gray-50 transition-colors shadow-sm">
                            <div class="bg-green-100 p-3 rounded-full mb-2">
                                <i class="fas fa-syringe text-green-600 text-xl"></i>
                            </div>
                            <span class="text-sm font-semibold text-gray-700 text-center">Request Vaccine</span>
                        </a>
                        
                        <a href="payments.php" class="flex flex-col items-center justify-center p-4 border border-gray-100 rounded-xl hover:bg-gray-50 transition-colors shadow-sm">
                            <div class="bg-purple-100 p-3 rounded-full mb-2">
                                <i class="fas fa-credit-card text-purple-600 text-xl"></i>
                            </div>
                            <span class="text-sm font-semibold text-gray-700 text-center">Make Payment</span>
                        </a>
                        
                        <a href="appointments.php" class="flex flex-col items-center justify-center p-4 border border-gray-100 rounded-xl hover:bg-gray-50 transition-colors shadow-sm">
                            <div class="bg-red-100 p-3 rounded-full mb-2">
                                <i class="fas fa-calendar-plus text-red-600 text-xl"></i>
                            </div>
                            <span class="text-sm font-semibold text-gray-700 text-center">Book Appointment</span>
                        </a>
                    </div>
                </div>
                
                <!-- Community Events (Keeping static as it's not requested to be dynamic) -->
                <div class="card p-6 bg-white">
                    <h3 class="text-xl font-semibold text-dark mb-6">Community Events</h3>
                    
                    <div class="bg-blue-50 p-4 rounded-xl mb-4 border-l-4 border-blue-500 shadow-sm">
                        <div class="flex items-center justify-between mb-2">
                            <span class="font-bold text-blue-800">Community Vaccination Day</span>
                            <span class="bg-blue-200 text-blue-900 text-xs px-2 py-1 rounded-full font-semibold">Free</span>
                        </div>
                        <p class="text-sm text-blue-700">October 28, 2025 • 9:00 AM - 4:00 PM</p>
                        <p class="text-sm text-blue-600 mt-1 font-medium">Butuan City Hall Grounds</p>
                        <button class="mt-3 text-xs bg-blue-500 text-white px-3 py-1 rounded-full hover:bg-blue-600 transition-colors font-semibold">
                            Register Now
                        </button>
                    </div>
                    
                    <div class="bg-green-50 p-4 rounded-xl border-l-4 border-green-500 shadow-sm">
                        <div class="flex items-center justify-between mb-2">
                            <span class="font-bold text-green-800">Anti-Rabies Campaign</span>
                            <span class="bg-green-200 text-green-900 text-xs px-2 py-1 rounded-full font-semibold">Free</span>
                        </div>
                        <p class="text-sm text-green-700">November 15, 2025 • 8:00 AM - 3:00 PM</p>
                        <p class="text-sm text-green-600 mt-1 font-medium">Butuan City Public Market</p>
                        <button class="mt-3 text-xs bg-green-500 text-white px-3 py-1 rounded-full hover:bg-green-600 transition-colors font-semibold">
                            Learn More
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>