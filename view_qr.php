<?php
session_start();
require_once 'db.php'; // Database connection file

$pet_name = "Pet";
$pet_id = null;
$error_message = null;
$qrCodeURL = null;

// 1. Get Pet ID from URL
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $pet_id = $_GET['id'];

    // 2. Fetch the pet's name for display
    $conn = get_db_connection();
    if ($conn) {
        $sql = "SELECT pet_name FROM pets WHERE pet_id = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("i", $pet_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $pet = $result->fetch_assoc();
                $pet_name = $pet['pet_name'];
            } else {
                $error_message = "No pet found with this ID.";
            }
            $stmt->close();
        } else {
            $error_message = "Failed to prepare statement.";
        }
        $conn->close();
    } else {
        $error_message = "Database connection failed.";
    }

    // 3. Construct the absolute URL for the QR code
    // This is the public page the QR code will link to.
    if (!$error_message) {
        // Determine protocol (http or https)
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
        
        // Get the host (e.g., www.yourwebsite.com)
        $host = $_SERVER['HTTP_HOST'];
        
        // Get the directory path of the current script, removing the script name
        $path = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
        
        // Create the full URL to the pet_details.php page
        $absolutePetURL = $protocol . '://' . $host . $path . '/pet_details.php?id=' . $pet_id;

        // 4. Generate the QR Code API URL
        // We use api.qrserver.com, a free QR code generator API.
        // We must urlencode() the URL we want to embed in the QR code.
        $qrCodeURL = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($absolutePetURL);
    }

} else {
    $error_message = "No Pet ID provided.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Code for <?php echo htmlspecialchars($pet_name); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        @media print {
            body * { visibility: hidden; }
            #printable-area, #printable-area * { visibility: visible; }
            #printable-area {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                height: 100%;
                display: flex;
                flex-direction: column;
                justify-content: center;
                align-items: center;
            }
            button { display: none; }
        }
    </style>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">

    <div class="container mx-auto p-4">
        <div class="max-w-md mx-auto bg-white p-8 rounded-2xl shadow-lg text-center">

            <?php if ($error_message): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative" role="alert">
                    <strong class="font-bold">Error:</strong>
                    <span class="block sm:inline"><?php echo htmlspecialchars($error_message); ?></span>
                </div>
                <a href="view_all_pets.php" class="mt-6 inline-block px-5 py-2.5 bg-gray-600 text-white font-semibold rounded-lg shadow-md hover:bg-gray-700">
                    &larr; Back to List
                </a>

            <?php elseif ($qrCodeURL): ?>
                <div id="printable-area">
                    <h1 class="text-2xl font-bold text-gray-900 mb-2">
                        <?php echo htmlspecialchars($pet_name); ?>
                    </h1>
                    <p class="text-gray-600 mb-6">
                        Scan this QR code to view the pet's public profile.
                    </p>
                    <div class="flex justify-center p-4 border border-gray-200 rounded-lg">
                        <img src="<?php echo htmlspecialchars($qrCodeURL); ?>" alt="QR Code for <?php echo htmlspecialchars($pet_name); ?>">
                    </div>
                </div>
                
                <div class="mt-8 flex flex-col sm:flex-row sm:justify-center gap-4">
                    <a href="all_pets.php" class="px-5 py-2.5 bg-gray-600 text-white font-semibold rounded-lg shadow-md hover:bg-gray-700">
                        &larr; Back to List
                    </a>
                    <button onclick="window.print()" class="px-5 py-2.5 text-white font-semibold rounded-lg shadow-md" style="background-color: #4361ee; hover:bg-blue-700;">
                        Print QR Code
                    </button>
                </div>

            <?php endif; ?>

        </div>
    </div>

</body>
</html>