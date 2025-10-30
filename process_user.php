<?php
// Start the session and include the database connection
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include 'db.php';

// SECURITY: Protect this page just like the selection page
if (
    !isset($_SESSION['loggedin']) || 
    $_SESSION['loggedin'] !== true || 
    ($_SESSION['user_rules'] ?? '') != 2
) {
    session_unset();
    session_destroy();
    echo '<script>window.location.href = "login.php";</script>';
    exit();
}

$user_details = null;
$error_message = '';

// Check if a user ID was submitted
if (isset($_GET['user_id']) && !empty($_GET['user_id'])) {
    $selectedUserId = $_GET['user_id'];

    // Use a prepared statement to prevent SQL injection
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $selectedUserId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $user_details = $result->fetch_assoc();
    } else {
        $error_message = "User not found. The selected user may have been deleted.";
    }
    $stmt->close();
} else {
    $error_message = "No user was selected. Please go back and make a selection.";
}

$conn->close();
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
    .gradient-bg { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
    .detail-row { border-bottom: 1px solid #e5e7eb; }
  </style>
</head>
<body class="min-h-screen gradient-bg flex items-center justify-center p-4">

  <div class="w-full max-w-2xl bg-white rounded-2xl shadow-2xl overflow-hidden">
    <div class="bg-indigo-900 text-white p-6">
      <h1 class="text-2xl font-bold"><i class="fas fa-user-circle mr-3"></i>User Profile Details</h1>
      <p class="text-indigo-200 mt-1">Information for the selected user.</p>
    </div>
    
    <div class="p-8">
      <?php if ($user_details): ?>
        <div class="bg-gray-50 rounded-xl p-6 border border-gray-200">
          <div class="flex items-center mb-6">
            <div class="bg-indigo-100 p-4 rounded-full mr-5">
              <i class="fas fa-user text-3xl text-indigo-600"></i>
            </div>
            <div>
              <h2 class="text-2xl font-bold text-gray-800">
                <?= htmlspecialchars($user_details['first_name'] . ' ' . $user_details['last_name']) ?>
              </h2>
              <p class="text-gray-500"><?= htmlspecialchars($user_details['username']) ?></p>
            </div>
          </div>
          
          <div class="space-y-4">
            <div class="flex justify-between items-center detail-row py-3">
              <span class="font-medium text-gray-600"><i class="fas fa-id-card mr-2 text-indigo-400"></i>User ID</span>
              <span class="text-gray-800 font-semibold"><?= htmlspecialchars($user_details['id']) ?></span>
            </div>
            <div class="flex justify-between items-center detail-row py-3">
              <span class="font-medium text-gray-600"><i class="fas fa-envelope mr-2 text-indigo-400"></i>Email</span>
              <span class="text-gray-800 font-semibold"><?= htmlspecialchars($user_details['email']) ?></span>
            </div>
            <div class="flex justify-between items-center detail-row py-3">
              <span class="font-medium text-gray-600"><i class="fas fa-map-marker-alt mr-2 text-indigo-400"></i>Barangay</span>
              <span class="text-gray-800 font-semibold"><?= htmlspecialchars($user_details['barangay']) ?></span>
            </div>
             </div>
        </div>
      <?php else: ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-lg" role="alert">
          <p class="font-bold">Error</p>
          <p><?= htmlspecialchars($error_message) ?></p>
        </div>
      <?php endif; ?>
      
      <div class="mt-8 text-center">
        <a href="user_selection.php" class="inline-block bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-6 rounded-lg transition duration-300">
          <i class="fas fa-arrow-left mr-2"></i>
          Back to Selection
        </a>
      </div>
    </div>
  </div>

</body>
</html>