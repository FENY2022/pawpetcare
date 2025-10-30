<?php
// Start the session and include the database connection
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include 'db.php';

// SECURITY: Protect this page for Super Admin access only
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
    
// Fetch all unique barangays for the dropdown
$barangays = [];
$result = $conn->query("SELECT DISTINCT barangay FROM users ORDER BY barangay ASC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $barangays[] = $row['barangay'];
    }
}

// If a barangay is selected from the dropdown, fetch its users
$users = [];
$selected_barangay = $_GET['barangay'] ?? '';

if (!empty($selected_barangay)) {
    $stmt = $conn->prepare("SELECT id, first_name, last_name FROM users WHERE barangay = ? ORDER BY last_name, first_name");
    $stmt->bind_param("s", $selected_barangay);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
    }
    $stmt->close();
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>User Management</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
    * { font-family: 'Poppins', sans-serif; }
    .card:hover { transform: translateY(-5px); box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04); }
    .select-wrapper { position: relative; }
    .select-wrapper::after { content: "▼"; font-size: 12px; position: absolute; right: 15px; top: 50%; transform: translateY(-50%); pointer-events: none; color: #6b7280; }
    select { -webkit-appearance: none; -moz-appearance: none; appearance: none; }
    .modal-iframe { width: 100%; height: 60vh; border: none; }
  </style>
</head>
<body class="bg-gray-100">

    <div class="w-screen h-screen bg-white flex flex-col">
      <div class="bg-indigo-900 text-white p-4 md:p-6 flex items-center justify-between shadow-md">
        <div><h1 class="text-xl md:text-2xl font-bold"><i class="fas fa-users mr-3"></i>User Management</h1></div>
      </div>
      
      <div class="flex-grow overflow-y-auto p-4 md:p-8 bg-gray-50">
        <div class="max-w-7xl mx-auto">
          <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-xl p-4 flex items-center shadow-sm"><div class="bg-indigo-100 p-3 rounded-lg mr-4"><i class="fas fa-map-marker-alt text-indigo-600 text-xl"></i></div><div><p class="text-sm text-indigo-600">Barangays</p><p class="text-2xl font-bold text-gray-800"><?php echo count($barangays); ?></p></div></div>
            <div class="bg-white rounded-xl p-4 flex items-center shadow-sm"><div class="bg-green-100 p-3 rounded-lg mr-4"><i class="fas fa-user-friends text-green-600 text-xl"></i></div><div><p class="text-sm text-green-600">Users in Selection</p><p class="text-2xl font-bold text-gray-800"><?php echo !empty($selected_barangay) ? count($users) : 'N/A'; ?></p></div></div>
            <div class="bg-white rounded-xl p-4 flex items-center shadow-sm"><div class="bg-purple-100 p-3 rounded-lg mr-4"><i class="fas fa-chart-line text-purple-600 text-xl"></i></div><div><p class="text-sm text-purple-600">Active Selection</p><p class="text-2xl font-bold text-gray-800"><?php echo !empty($selected_barangay) ? htmlspecialchars($selected_barangay) : 'None'; ?></p></div></div>
          </div>
          
          <div class="bg-white rounded-xl shadow-lg p-6 mb-8 border border-gray-100">
            <h2 class="text-xl font-semibold text-gray-800 mb-6 flex items-center"><i class="fas fa-user-check mr-2 text-indigo-600"></i>Select User by Barangay</h2>
            
            <form method="GET" action="registeradminaccount.php">
              <div>
                <label class="block text-gray-700 font-medium mb-3"><i class="fas fa-map-marked-alt mr-2 text-indigo-500"></i>Choose Barangay</label>
                <div class="select-wrapper">
                  <select name="barangay" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 py-3 pl-4 pr-10 border-2">
                    <option value="">-- Select a Barangay --</option>
                    <?php foreach ($barangays as $b): ?>
                      <option value="<?= htmlspecialchars($b) ?>" <?= ($selected_barangay == $b) ? 'selected' : '' ?>><?= htmlspecialchars($b) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>
            </form>

            <?php if (!empty($users)): ?>
              <div class="mt-8">
                <label class="block text-gray-700 font-medium mb-3"><i class="fas fa-users mr-2 text-indigo-500"></i>Users in <?= htmlspecialchars($selected_barangay) ?></label>
                <div class="overflow-x-auto rounded-lg border border-gray-200">
                  <table class="min-w-full divide-y-2 divide-gray-200 bg-white text-sm">
                    <thead class="bg-gray-50 text-left">
                      <tr>
                        <th scope="col" class="whitespace-nowrap px-4 py-3 font-medium text-gray-900">Last Name</th>
                        <th scope="col" class="whitespace-nowrap px-4 py-3 font-medium text-gray-900">First Name</th>
                        <th scope="col" class="whitespace-nowrap px-4 py-3 font-medium text-gray-900 text-center">Action</th>
                      </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                      <?php foreach ($users as $u): ?>
                        <tr>
                          <td class="whitespace-nowrap px-4 py-3 font-medium text-gray-900"><?= htmlspecialchars($u['last_name']) ?></td>
                          <td class="whitespace-nowrap px-4 py-3 text-gray-700"><?= htmlspecialchars($u['first_name']) ?></td>
                          <td class="whitespace-nowrap px-4 py-3 text-center">
                            <button type="button" 
                                    class="inline-flex items-center gap-1 rounded bg-indigo-600 px-3 py-1.5 text-xs text-white hover:bg-indigo-700 view-details-btn" 
                                    data-userid="<?= $u['id'] ?>">
                                <i class="fas fa-eye"></i> View Details
                            </button>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              </div>
            
            <?php elseif (!empty($selected_barangay)): ?>
              <div class="mt-6 bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded-lg"><div class="flex"><div class="flex-shrink-0"><i class="fas fa-exclamation-triangle text-yellow-400"></i></div><div class="ml-3"><p class="text-sm text-yellow-700">No users found in <span class="font-medium"><?= htmlspecialchars($selected_barangay) ?></span>.</p></div></div></div>
            <?php else: ?>
              <div class="mt-6 bg-blue-50 border-l-4 border-blue-400 p-4 rounded-lg"><div class="flex"><div class="flex-shrink-0"><i class="fas fa-info-circle text-blue-400"></i></div><div class="ml-3"><p class="text-sm text-blue-700">Please select a barangay to view available users.</p></div></div></div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <div id="user-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex justify-center items-center p-4">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-3xl transform transition-all">
            <div class="flex justify-between items-center p-4 border-b">
                <h3 class="text-lg font-medium text-gray-900"><i class="fas fa-user-circle mr-2"></i>User Profile Details</h3>
                <button id="modal-close-btn" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div class="p-2 sm:p-4">
                <iframe id="modal-iframe" class="modal-iframe" src="about:blank"></iframe>
            </div>
        </div>
    </div>

    <script>
      document.addEventListener('DOMContentLoaded', function() {
        const barangaySelect = document.querySelector('select[name="barangay"]');
        barangaySelect.addEventListener('change', function() {
          if (this.value) {
            window.location.href = `registeradminaccount.php?barangay=${this.value}`;
          } else {
            window.location.href = 'registeradminaccount.php';
          }
        });

        // MODAL LOGIC
        const modal = document.getElementById('user-modal');
        const modalCloseBtn = document.getElementById('modal-close-btn');
        const modalIframe = document.getElementById('modal-iframe');
        const viewDetailButtons = document.querySelectorAll('.view-details-btn');

        viewDetailButtons.forEach(button => {
            button.addEventListener('click', function() {
                const userId = this.dataset.userid;
                // Set iframe src and show the modal
                modalIframe.src = `view_user.php?user_id=${userId}`;
                modal.classList.remove('hidden');
            });
        });

        // Function to close the modal
        const closeModal = () => {
            modal.classList.add('hidden');
            // Reset iframe to prevent old content from flashing
            modalIframe.src = 'about:blank'; 
        };

        // Event listeners for closing the modal
        modalCloseBtn.addEventListener('click', closeModal);
        modal.addEventListener('click', function(event) {
            // Close if clicked on the dark overlay (the modal itself)
            if (event.target === modal) {
                closeModal();
            }
        });
      });
    </script>
</body>
</html>