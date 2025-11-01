<?php
session_start();
require_once 'db.php'; // Database connection file

$pet = null; // To hold all pet and owner data
$error_message = null;
$pet_id = null;

// 1. Validate the ID from the URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $error_message = "Invalid pet ID. Please go back and try again.";
} else {
    $pet_id = (int)$_GET['id'];
}

// 2. Fetch data if we have a valid ID
if ($pet_id && !function_exists('get_db_connection')) {
    $error_message = 'Database function get_db_connection() is missing from db.php.';
} 
else if ($pet_id) {
    $conn = get_db_connection();

    if ($conn) {
        // This SQL query selects all columns from 'pets' (aliased as 'p')
        // and joins with 'clients' (aliased as 'c') to get owner details.
        $sql = "SELECT 
                    p.*,  -- Selects all columns from the pets table
                    c.client_fname, 
                    c.client_lname, 
                    c.client_contact AS owner_contact,
                    c.client_email AS owner_email
                FROM 
                    pets AS p
                LEFT JOIN 
                    clients AS c ON p.client_id = c.client_id
                WHERE 
                    p.pet_id = ?"; // Filter by the specific pet ID

        $stmt = $conn->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param("i", $pet_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                // Fetch the single row of data
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

/**
 * Helper function to format boolean (0/1) values into 'Yes' or 'No'
 */
function format_boolean($value) {
    return $value ? 'Yes' : 'No';
}

/**
 * Helper function to safely display data
 */
function display($value, $default = 'N/A') {
    return !empty($value) ? htmlspecialchars($value) : $default;
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
        .details-grid dt { /* This is the "term" or "label" */
            @apply text-sm font-medium text-gray-500;
        }
        .details-grid dd { /* This is the "definition" or "value" */
            @apply mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0;
        }
    </style>
</head>
<body class="bg-gray-100">

    <div class="container mx-auto p-4 py-8 md:p-10">
        <div class="max-w-4xl mx-auto bg-white p-6 md:p-10 rounded-2xl shadow-lg">

            <div class="mb-6">
                <a href="all_pets.php" class="text-sm font-medium text-blue-600 hover:text-blue-800">
                    &larr; Back to all pets
                </a>
            </div>

            <?php if ($error_message): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative mb-6" role="alert">
                    <strong class="font-bold">Error:</strong>
                    <span class="block sm:inline"><?php echo htmlspecialchars($error_message); ?></span>
                </div>
            
            <?php elseif ($pet): ?>

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
                    
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900" style="color: #4361ee;">
                            <?php echo display($pet['pet_name'], 'Unnamed Pet'); ?>
                        </h1>
                        <p class="mt-2 text-lg text-gray-600">
                            <?php echo display($pet['pet_color']); ?> <?php echo display($pet['pet_breed']); ?>
                            (<?php echo display($pet['pet_species']); ?>)
                        </p>
                        <p class="mt-1 text-sm text-gray-500">
                            Sex: <?php echo display($pet['pet_sex']); ?> | 
                            Birthday: <?php echo display($pet['pet_bday']); ?>
                        </p>
                    </div>
                </div>

                <div class="mt-8">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">Pet Information</h2>
                    <dl class="details-grid grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-3">
                        
                        <dt>Habitat</dt>
                        <dd><?php echo display($pet['pet_habitat']); ?></dd>

                        <dt>Origin</dt>
                        <dd><?php echo display($pet['pet_origin']); ?>
                            <?php if(!empty($pet['pet_origin_other'])) echo ' (' . display($pet['pet_origin_other']) . ')'; ?>
                        </dd>
                        
                        <dt>Ownership</dt>
                        <dd><?php echo display($pet['pet_ownership']); ?></dd>

                        <dt>Weight</dt>
                        <dd><?php echo display($pet['pet_weight'], 'N/A'); ?> kgs</dd>
                        
                        <dt>Contact with Animals</dt>
                        <dd><?php echo display($pet['pet_contact']); ?></dd>

                        <dt>Registered On</dt>
                        <dd><?php echo display(date('F j, Y, g:i a', strtotime($pet['created_at']))); ?></dd>

                        <?php if ($pet['pet_sex'] == 'Female'): ?>
                            <dt>Is Pregnant?</dt>
                            <dd><?php echo format_boolean($pet['pet_is_pregnant']); ?></dd>
                            
                            <dt>Is Lactating?</dt>
                            <dd><?php echo format_boolean($pet['pet_is_lactating']); ?></dd>

                            <dt>Number of Puppies</dt>
                            <dd><?php echo display($pet['pet_puppies'], '0'); ?></dd>
                        <?php endif; ?>
                    </dl>
                </div>

                <div class="mt-10 pt-8 border-t border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">Tag Information</h2>
                    <dl class="details-grid grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-3">
                        <dt>Tag No.</dt>
                        <dd><?php echo display($pet['pet_tag_no']); ?></dd>
                        
                        <dt>Collar Tag?</dt>
                        <dd><?php echo format_boolean($pet['tag_type_collar']); ?></dd>

                        <dt>Other Tag?</dt>
                        <dd><?php echo format_boolean($pet['tag_type_other']); ?>
                            <?php if(!empty($pet['tag_type_other_specify'])) echo ' (' . display($pet['tag_type_other_specify']) . ')'; ?>
                        </dd>
                    </dl>
                </div>

                <div class="mt-10 pt-8 border-t border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">Owner Information</h2>
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

</body>
</html>