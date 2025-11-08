<?php
session_start();

// Optional: Redirect to login if not logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("location: login.php");
    exit;
}

// Get the user_rules for easier reference (0=Client, 1=Healthcare, 2=Admin)
$user_rules = $_SESSION['user_rules'] ?? 0;

// --- NEW: FETCH NOTIFICATIONS ---
// Assumes db.php contains get_db_connection()
include_once 'db.php'; 
$notifications = [];
$unread_count = 0;

if (isset($_SESSION['user_id'])) {
    $user_id = (int)$_SESSION['user_id'];
    $conn = get_db_connection();
    
    // Fetch up to 10 recent notifications for the user
    $notif_sql = "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 10";
    $notif_stmt = $conn->prepare($notif_sql);
    $notif_stmt->bind_param("i", $user_id);
    $notif_stmt->execute();
    $notif_result = $notif_stmt->get_result();
    while ($row = $notif_result->fetch_assoc()) {
        $notifications[] = $row;
    }
    $notif_stmt->close();
    
    // Fetch the count of unread notifications
    $count_sql = "SELECT COUNT(*) as unread_count FROM notifications WHERE user_id = ? AND is_read = 0";
    $count_stmt = $conn->prepare($count_sql);
    $count_stmt->bind_param("i", $user_id);
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $unread_count = $count_result->fetch_assoc()['unread_count'];
    $count_stmt->close();
    
    $conn->close();
}
// --- END NEW PHP ---
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - PawPetCares | Cantilan</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/png" href="logo/pawpetcarelogo.png">
    <style>
        :root {
            --primary: #4361ee;
            --primary-dark: #3a56d4;
            --light: #f8f9fa;
            --dark: #212529;
            --gray-100: #f1f5f9;
            --gray-400: #94a3b8;
            --gray-500: #64748b;
            --gray-800: #1e293b;
            --danger: #ef476f;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f5f7fa;
            color: var(--gray-800);
        }
        
        .sidebar {
            width: 260px;
            transition: transform 0.3s ease-in-out;
        }
        
        .main-content-wrapper {
            margin-left: 260px;
            transition: margin-left 0.3s ease-in-out;
        }
        
        /* Mobile responsive styles */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                position: fixed;
                z-index: 50;
                height: 100vh;
            }
            .sidebar.active {
                transform: translateX(0);
            }
            .main-content-wrapper {
                margin-left: 0;
            }
            .overlay {
                display: none;
                position: fixed;
                inset: 0;
                background-color: rgba(0, 0, 0, 0.5);
                z-index: 40;
            }
            .overlay.active {
                display: block;
            }
        }

        /* Sidebar Navigation Items */
        .nav-item {
            display: flex;
            align-items: center;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 4px;
            transition: all 0.2s ease;
            cursor: pointer;
            font-weight: 500;
            color: var(--gray-500);
        }
        
        .nav-item:hover {
            background-color: var(--gray-100);
            color: var(--primary);
        }
        
        .nav-item.active {
            background-color: #e0e7ff;
            color: var(--primary);
            font-weight: 600;
        }

        .nav-item i {
            width: 20px;
            margin-right: 12px;
            text-align: center;
        }
        
        /* Dropdown Styles */
        .dropdown-toggle {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
        }

        .dropdown-arrow {
            transition: transform 0.3s ease;
        }

        .submenu {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-in-out;
            padding-left: 12px;
        }

        .submenu-open {
            max-height: 200px; /* Adjust as needed */
        }

        .arrow-open {
            transform: rotate(90deg);
        }
        
        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }
        
        .card:hover {
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }

        /* Thin Scrollbar Styles */
        .thin-scrollbar {
            scrollbar-width: thin;
            scrollbar-color: var(--gray-400) var(--gray-100);
        }
        .thin-scrollbar::-webkit-scrollbar {
            width: 6px;
        }
        .thin-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }
        .thin-scrollbar::-webkit-scrollbar-thumb {
            background-color: var(--gray-400);
            border-radius: 20px;
        }
        .thin-scrollbar::-webkit-scrollbar-thumb:hover {
            background-color: var(--gray-500);
        }

        /* --- NEW: Notification Badge and Dropdown CSS --- */
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            width: 20px;
            height: 20px;
            background-color: var(--danger);
            color: white;
            border-radius: 50%;
            border: 2px solid white;
            font-size: 11px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .notification-dropdown {
            display: none;
            position: absolute;
            top: 100%;
            right: 0;
            width: 320px; /* 80 * 4 */
            margin-top: 8px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            z-index: 100;
            overflow: hidden;
        }
        .notification-dropdown.show {
            display: block;
        }
        .notification-item {
            display: block;
            padding: 12px 16px;
            border-bottom: 1px solid #f1f5f9;
            transition: background-color 0.2s ease;
            text-decoration: none;
            color: inherit;
        }
        .notification-item:hover {
            background-color: #f8f9fa;
        }
        .notification-item.unread {
            background-color: #eef2ff; /* A light blue/indigo bg */
            font-weight: 600;
        }
        /* --- END NEW CSS --- */
        
    </style>
</head>
<body class="flex bg-gray-100">
    <aside class="sidebar bg-white shadow-lg fixed h-full flex flex-col">
        <div class="p-6 border-b border-gray-100">
            <div class="flex items-center space-x-3">
                <img src="logo/pawpetcarelogo.png" alt="Logo" class="h-10 w-10">
                <h1 class="text-xl font-bold text-gray-800">PawPet<span style="color: var(--primary);">Cares</span> <span class="text-sm font-semibold text-gray-500">Cantilan</span></h1>
            </div>
        </div>
        
        <nav class="p-4 flex-grow overflow-y-auto thin-scrollbar" id="sidebar-nav">
            <div class="mb-8">
                <h3 class="text-xs uppercase tracking-wider text-gray-500 font-semibold mb-4 px-2">Main Menu</h3>
                <ul>
                    <li><a href="dashboard.php?action=dashboard" data-action="dashboard" class="nav-item active"><i class="fas fa-home"></i> Dashboard</a></li>
                    
                    <?php if ($user_rules == 0): // CLIENT ONLY LINKS (0) ?>
                    <li class="dropdown">
                        <div class="nav-item dropdown-toggle">
                            <div class="flex items-center"><i class="fas fa-paw"></i> My Pets</div>
                            <i class="fas fa-chevron-right dropdown-arrow"></i>
                        </div>
                        <ul class="submenu">
                            <li><a href="dashboard.php?action=all_pets" data-action="all_pets" class="nav-item">All Pets</a></li>
                            <li><a href="dashboard.php?action=add_pet" data-action="add_pet" class="nav-item">Add New Pet</a></li>
                        </ul>
                    </li>
                    <li><a href="dashboard.php?action=myvaccinations" data-action="myvaccinations" class="nav-item"><i class="fas fa-syringe"></i>My Vaccinations</a></li>
                    <li><a href="dashboard.php?action=license" data-action="license" class="nav-item"><i class="fas fa-id-card"></i> Pet License</a></li>
                    <li><a href="dashboard.php?action=appointments" data-action="appointments" class="nav-item"><i class="fas fa-calendar"></i> Appointments</a></li>
                    <?php endif; ?>

                    <?php if ($user_rules == 1 || $user_rules == 2): // HEALTHCARE (1) & ADMIN (2) LINKS ?>
                    <li><a href="dashboard.php?action=admin_vaccinations" data-action="admin_vaccinations" class="nav-item"><i class="fas fa-syringe"></i>Manage Vaccinations</a></li>
                    <li><a href="dashboard.php?action=appointments" data-action="appointments" class="nav-item"><i class="fas fa-calendar-check"></i> Manage Appointments</a></li>
                    <li><a href="dashboard.php?action=all_pets" data-action="all_pets" class="nav-item"><i class="fas fa-users-cog"></i> Manage Clients & Pets</a></li>
                    <li><a href="dashboard.php?action=payments" data-action="payments" class="nav-item"><i class="fas fa-money-check-alt"></i> Payments/Billing</a></li>
                    <?php endif; ?>
                </ul>
            </div>
           

            <div>
                <h3 class="text-xs uppercase tracking-wider text-gray-500 font-semibold mb-4 px-2">Account</h3>
                <ul>
                    <li><a href="dashboard.php?action=profile" data-action="profile" class="nav-item"><i class="fas fa-user"></i> Profile</a></li>
                    
                    <?php if ($user_rules == 2): // ADMIN ONLY LINKS (2) ?>
                    <li class="dropdown">
                        <div class="nav-item dropdown-toggle">
                            <div class="flex items-center"><i class="fas fa-cog"></i> Admin Settings</div>
                            <i class="fas fa-chevron-right dropdown-arrow"></i>
                        </div>
                        <ul class="submenu">
                            <li><a href="dashboard.php?action=registeradminaccount" data-action="registeradminaccount" class="nav-item">Register Admin Account</a></li>
                            <li><a href="dashboard.php?action=system_settings" data-action="system_settings" class="nav-item">System Settings</a></li>
                        </ul>
                    </li>
                    <?php endif; ?>

                    <li><a href="logout.php" class="nav-item"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </div>
        </nav>
    </aside>

    <div class="overlay" id="overlay"></div>

    <div class="main-content-wrapper flex-1 flex flex-col h-screen overflow-y-hidden">
        
        <header class="bg-white shadow-sm p-4 flex items-center justify-between z-10">
            <div class="flex items-center space-x-4">
                <button id="menu-toggle" class="md:hidden p-2 rounded-md text-gray-600">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <div class="hidden md:flex items-center relative">
                    <i class="fas fa-search absolute left-3 text-gray-400"></i>
                    <input type="text" placeholder="Search pets, appointments..." class="pl-10 pr-4 py-2 rounded-lg bg-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500 w-80">
                </div>
            </div>

            <div class="flex items-center space-x-6">
            
                <div class="relative">
                    <button id="notification-bell-btn" class="relative">
                        <i class="fas fa-bell text-xl text-gray-500 cursor-pointer"></i>
                        <?php if ($unread_count > 0): ?>
                            <span class="notification-badge">
                                <?php echo $unread_count > 9 ? '9+' : $unread_count; ?>
                            </span>
                        <?php endif; ?>
                    </button>
                    
                    <div id="notification-dropdown" class="notification-dropdown">
                        <div class="p-3 border-b border-gray-200">
                            <h4 class="font-semibold text-gray-800">Notifications</h4>
                        </div>
                        <div class="max-h-80 overflow-y-auto thin-scrollbar">
                            <?php if (empty($notifications)): ?>
                                <p class="text-gray-500 text-center p-4">No notifications yet.</p>
                            <?php else: ?>
                                <?php foreach ($notifications as $notif): 
                                    // The link uses the handler script
                                    $link = "handle_notification_click.php?id=" . $notif['id'] . "&redirect=" . urlencode($notif['link']);
                                ?>
                                    <a href="<?php echo $link; ?>" 
                                       class="notification-item <?php echo $notif['is_read'] ? '' : 'notification-item unread'; ?>">
                                        <p class="text-sm text-gray-800 font-semibold"><?php echo htmlspecialchars($notif['title']); ?></p>
                                        <p class="text-xs text-gray-600 <?php echo $notif['is_read'] ? '' : 'font-medium'; ?>"><?php echo htmlspecialchars($notif['message']); ?></p>
                                        <p class="text-xs text-blue-500 mt-1 font-normal">
                                            <?php echo date('M d, Y h:i A', strtotime($notif['created_at'])); ?>
                                        </p>
                                    </a>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <div class="p-2 bg-gray-50 border-t border-gray-200 text-center">
                            <a href="#" class="text-sm font-medium text-blue-600 hover:underline">View All Notifications</a>
                        </div>
                    </div>
                </div>
                
                <div class="flex items-center space-x-3">
                    <?php
                    $profileImage = $_SESSION['profile_image'] ?? '';
                    if (!empty($profileImage)) {
                        $imgSrc = 'uploads/profile_pictures/' . htmlspecialchars($profileImage);
                    } else {
                        $imgSrc = 'https://i.pravatar.cc/150?u=a042581f4e29026704d';
                    }
                    ?>
                    <img src="<?php echo $imgSrc; ?>" alt="User Avatar" class="h-10 w-10 rounded-full object-cover">
                    <div>
                        <h4 class="font-semibold text-sm text-gray-700"><?php echo htmlspecialchars($_SESSION['first_name'] ?? 'User'); ?></h4>
                        
                        <?php
                        $userRole = 'Client'; // Default role
                        if (isset($_SESSION['user_rules'])) {
                            switch ($_SESSION['user_rules']) {
                                case 0: $userRole = 'Client'; break;
                                case 1: $userRole = 'PawPet Healthcare'; break;
                                case 2: $userRole = 'Admin'; break;
                                default: $userRole = 'Client';
                            }
                        }
                        ?>
                        <p class="text-xs text-gray-500"><?php echo htmlspecialchars($userRole); ?></p>
                    </div>
                </div>
            </div>
        </header>

        <main class="flex-grow p-0 m-0">
            <?php
            $action = $_GET['action'] ?? 'dashboard'; 

            $allowed_pages = [
                'dashboard' => 'content.php',
                'registeradminaccount' => 'registeradminaccount.php',
                'all_pets' => 'all_pets.php',
                'add_pet' => 'add_pet.php',
                'vaccinations' => 'vaccinations.php',
                'myvaccinations' => 'my_vaccinations.php',
                'license' => 'license.php',
                'appointments' => 'appointments.php',
                'profile' => 'profile.php',
                'payments' => 'payments.php',
                'system_settings' => 'system_settings.php',
                'admin_vaccinations' => 'admin_vaccinations.php' 
            ];

            $page_to_load = $allowed_pages[$action] ?? $allowed_pages['dashboard'];

            echo '<iframe src="' . htmlspecialchars($page_to_load) . '" frameborder="0" style="width:100%; height:100%; border:none;"></iframe>';
            ?>
        </main>
        
        <footer class="bg-white border-t border-gray-200 p-4 text-center text-sm text-gray-500">
            &copy; <?php echo date('Y'); ?> PawPetCares Cantilan. All Rights Reserved.
            <div class="mt-2">
                <a href="#" class="hover:text-blue-600">Privacy Policy</a>
                <span class="mx-2">|</span>
                <a href="#" class="hover:text-blue-600">Terms of Service</a>
            </div>
        </footer>
        </div>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const menuToggle = document.getElementById('menu-toggle');
        const overlay = document.getElementById('overlay');
        const sidebar = document.querySelector('.sidebar');
        const sidebarNav = document.getElementById('sidebar-nav');
        const dropdownToggles = document.querySelectorAll('.dropdown-toggle');
        const navItems = document.querySelectorAll('.nav-item');

        // --- State Restoration ---
        function setActiveNavItem() {
            const currentAction = new URLSearchParams(window.location.search).get('action') || 'dashboard';
            navItems.forEach(item => {
                item.classList.remove('active');
                if (item.tagName === 'A' && item.dataset.action === currentAction) {
                    item.classList.add('active');
                    const parentDropdown = item.closest('.dropdown');
                    if (parentDropdown) {
                        parentDropdown.querySelector('.dropdown-toggle').classList.add('active');
                    }
                }
            });
        }

        function restoreDropdowns() {
            const openDropdowns = JSON.parse(localStorage.getItem('openDropdowns')) || {};
            dropdownToggles.forEach(toggle => {
                const dropdownId = toggle.querySelector('div').innerText; 
                if (openDropdowns[dropdownId]) {
                    toggle.nextElementSibling.classList.add('submenu-open');
                    toggle.querySelector('.dropdown-arrow').classList.add('arrow-open');
                }
            });
        }

        function restoreScrollPosition() {
            const savedScroll = localStorage.getItem('sidebarScroll');
            if (savedScroll && sidebarNav) {
                sidebarNav.scrollTop = savedScroll;
            }
        }
        
        setActiveNavItem();
        restoreDropdowns();
        restoreScrollPosition();

        // --- Event Listeners ---
        if (menuToggle) {
            menuToggle.addEventListener('click', () => {
                sidebar.classList.toggle('active');
                overlay.classList.toggle('active');
            });
        }

        if (overlay) {
            overlay.addEventListener('click', () => {
                sidebar.classList.remove('active');
                overlay.classList.remove('active');
            });
        }

        dropdownToggles.forEach(toggle => {
            toggle.addEventListener('click', () => {
                const submenu = toggle.nextElementSibling;
                const arrow = toggle.querySelector('.dropdown-arrow');
                submenu.classList.toggle('submenu-open');
                arrow.classList.toggle('arrow-open');
                
                const openDropdowns = JSON.parse(localStorage.getItem('openDropdowns')) || {};
                const dropdownId = toggle.querySelector('div').innerText;
                if (submenu.classList.contains('submenu-open')) {
                    openDropdowns[dropdownId] = true;
                } else {
                    delete openDropdowns[dropdownId];
                }
                localStorage.setItem('openDropdowns', JSON.stringify(openDropdowns));
            });
        });

        if (sidebarNav) {
            sidebarNav.addEventListener('scroll', () => {
                localStorage.setItem('sidebarScroll', sidebarNav.scrollTop);
            });
        }

        navItems.forEach(item => {
            if (!item.classList.contains('dropdown-toggle')) {
                item.addEventListener('click', () => {
                    if (sidebar.classList.contains('active')) {
                        sidebar.classList.remove('active');
                        overlay.classList.remove('active');
                    }
                });
            }
        });

        // --- Notification Dropdown Toggle ---
        const bellBtn = document.getElementById('notification-bell-btn');
        const notificationDropdown = document.getElementById('notification-dropdown');

        if (bellBtn) {
            bellBtn.addEventListener('click', (event) => {
                event.stopPropagation(); // Prevent click from bubbling up to document
                notificationDropdown.classList.toggle('show');
            });
        }

        // Close dropdown if clicked outside
        document.addEventListener('click', (event) => {
            if (notificationDropdown && !notificationDropdown.contains(event.target) && !bellBtn.contains(event.target)) {
                notificationDropdown.classList.remove('show');
            }
        });
        // --- END JavaScript ---

    });
    </script>
</body>
</html>