<?php
// Start the session to store user data upon successful login
session_start();

// Include your database connection file
include 'db.php';

// Initialize variables
$message = '';
$messageType = 'error'; // Can be 'error' or 'success'

// --- THIS IS THE FIX ---
// Call the function from db.php to get the connection object
$conn = get_db_connection();
// --- END OF FIX ---

// Check if the form has been submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Basic validation
    if (empty($email) || empty($password)) {
        $message = "Error: Email and password are required.";
    } else {
        // --- MODIFIED CHECK ---
        // Now $conn will be an object if successful, or null/false if not
        if ($conn) {
            // Prepare a statement to prevent SQL injection
            // This is Line 21, which will now work correctly
            $sql = "SELECT id, first_name, password, is_verified, user_rules, profile_image FROM users WHERE email = ? LIMIT 1";

            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows == 1) {
                    // User found, fetch the data
                    $user = $result->fetch_assoc();

                    // 1. Verify the password
                    if (password_verify($password, $user['password'])) {
                        
                        // 2. Check if the account is verified
                        if ($user['is_verified'] == 1) {
                            // Password is correct and account is verified - LOGIN SUCCESS
                            
                            // Store data in session variables
                            $_SESSION['loggedin']   = true;
                            $_SESSION['user_id']    = $user['id'];
                            $_SESSION['first_name'] = $user['first_name'];
                            $_SESSION['user_rules'] = $user['user_rules']; // store role/permissions
                            $_SESSION['profile_image'] = $user['profile_image']; 
                            $_SESSION['user_rules'] = $user['user_rules']; 


                            // Redirect to the user dashboard or home page
                            header("location: dashboard.php"); // <-- CHANGE 'dashboard.php' TO YOUR DESTINATION
                            exit;

                        } else {
                            // Account exists but is not verified
                            $message = "Account not verified. Please check your email for the verification code.";
                            // Optional: Add a link to resend verification
                            // $message .= ' <a href="resend_verification.php?email=' . urlencode($email) . '">Resend code?</a>';
                        }
                    } else {
                        // Password is not valid
                        $message = "Error: The email or password you entered is incorrect.";
                    }
                } else {
                    // No user found with that email address
                    $message = "Error: The email or password you entered is incorrect.";
                }
                $stmt->close();
            } else {
                $message = "Error: Could not prepare the query.";
            }
            $conn->close();
        } else {
            $message = "Error: Database connection failed.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - PAWPETCARES Cantilan</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <link rel="icon" type="image/png" href="logo/pawpetcarelogo.png">

    <style>
        /* Reusing styles from your registration page for consistency */
        :root {
            --primary: #4361ee;
            --primary-dark: #3a56d4;
            --secondary: #7209b7;
            --success: #06d6a0;
            --danger: #ef476f;
            --warning: #ffd166;
            --light: #f8f9fa;
            --dark: #212529;
        }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            overflow: hidden;
            width: 100%;
            max-width: 480px; /* Adjusted max-width for a login form */
            animation: fadeIn 0.5s ease-in-out;
        }
        .card-header {
            background: linear-gradient(to right, var(--primary), var(--secondary));
            color: white;
            padding: 24px 32px;
        }
        .card-body {
            padding: 32px;
        }
        .btn {
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 100%; /* Make button full width */
        }
        .btn-primary {
            background: var(--primary);
            color: white;
        }
        .btn-primary:hover {
            background: var(--primary-dark);
        }
        .input-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
        }
        .input-with-icon {
            padding-left: 40px !important;
        }
        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
            cursor: pointer;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
<?php // include 'topbar.php'; // <-- This was in your original file, uncomment if needed ?>

    <div class="card">
        <div class="card-header">
            <div class="flex items-center justify-center mb-2">
                <img src="logo/pawpetcarelogo.png" alt="PAWPETCARES Cantilan Logo" class="h-16 w-16 mr-3">
                <h1 class="text-3xl font-bold text-white">Welcome Back!</h1>
            </div>
            <p class="text-blue-100 text-center">Sign in to continue to PAWPETCARES</p>
        </div>
        
        <div class="card-body">
            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>" id="login-form">
                <div class="space-y-6">
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                        <div class="relative">
                            <i class="fa-solid fa-envelope input-icon"></i>
                            <input type="email" id="email" name="email" class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent input-with-icon" placeholder="you@example.com" required>
                        </div>
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-1">
                             <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                             <a href="forgotpassword.php" class="text-sm font-medium text-blue-600 hover:text-blue-500">Forgot Password?</a>
                        </div>
                        <div class="relative">
                            <i class="fa-solid fa-lock input-icon"></i>
                            <input type="password" id="password" name="password" class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent input-with-icon" placeholder="Enter your password" required>
                            <span class="password-toggle" id="password-toggle"><i class="fa-solid fa-eye"></i></span>
                        </div>
                    </div>
                </div>

                <div class="mt-8">
                    <button type="submit" class="btn btn-primary">
                        Login <i class="fa-solid fa-arrow-right-to-bracket ml-2"></i>
                    </button>
                </div>

                <div class="text-center mt-6">
                    <p class="text-sm text-gray-600">
                        Don't have an account? 
                        <a href="register.php" class="font-medium text-blue-600 hover:text-blue-500">
                            Sign Up
                        </a>
                    </p>
                </div>
            </form>
        </div>
    </div>

    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>

    <script>
        // Password visibility toggle
        const passwordToggle = document.getElementById("password-toggle");
        const passwordInput = document.getElementById("password");
        passwordToggle.addEventListener("click", () => {
            if (passwordInput.type === "password") {
                passwordInput.type = "text";
                passwordToggle.innerHTML = '<i class="fa-solid fa-eye-slash"></i>';
            } else {
                passwordInput.type = "password";
                passwordToggle.innerHTML = '<i class="fa-solid fa-eye"></i>';
            }
        });
    </script>
    
    <?php if (!empty($message)): ?>
    <script>
        // Display toast notification for PHP messages
        document.addEventListener('DOMContentLoaded', function() {
            const isError = <?php echo ($messageType === 'error') ? 'true' : 'false'; ?>;
            const msg = "<?php echo addslashes($message); ?>";

            Toastify({
                text: msg,
                duration: 5000,
                close: true,
                gravity: "top",
                position: "right",
                stopOnFocus: true,
                style: {
                    background: isError 
                        ? "linear-gradient(to right, #ef476f, #d90429)" // Error color
                        : "linear-gradient(to right, #00b09b, #96c93d)", // Success color
                },
            }).showToast();
        });
    </sCRIPT>
    <?php endif; ?>

</body>
</html>