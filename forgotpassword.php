<?php
session_start(); // Start session
include 'db.php';

// --- INITIALIZE DATABASE CONNECTION ---
// Call the function from db.php to create the connection object
$conn = get_db_connection();

// Initialize variables
$message = '';
$messageType = 'error'; // Can be 'error' or 'success'

// --- Function to send reset email ---
function sendResetEmail($email, $firstName, $resetToken) {
    // !!! IMPORTANT: This link must point to your *actual* resetpassword.php file !!!
    // For local testing: 'http://localhost/pawpetcares/resetpassword.php?token='
    $resetLink = 'https://localhost/pawpetcares/resetpassword.php?token=' . urlencode($resetToken);

    $subject = 'Your Password Reset Request';
    $emailMessage = "Hello " . htmlspecialchars($firstName) . ",\n\n";
    $emailMessage .= "We received a request to reset your password. Please click the link below to set a new password:\n";
    $emailMessage .= $resetLink . "\n\n";
    $emailMessage .= "If you did not request this, please ignore this email. This link is valid for 1 hour.\n\n";
    $emailMessage .= "Thank you,\nPAWPETCARE CANTILAN";

    // This uses your existing email sending service
    $emailUrl = 'https://ict-amsos.e-dats.info/sendemail/send.php';

    $queryParams = http_build_query([
        'send' => 1,
        'email' => $email,
        'Subject' => $subject,
        'message' => $emailMessage,
        'yourname' => 'PAWPETCARE CANTILAN'
    ]);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $emailUrl . '?' . $queryParams);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Note: Disabling SSL verification is insecure for production
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // Note: Disabling SSL verification is insecure for production
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    
    if(curl_errno($ch)){
        // Log cURL errors if they happen
        error_log('cURL error in sendResetEmail: ' . curl_error($ch));
    }
    curl_close($ch);
    return $response;
}

// --- START: BACKEND LOGIC FOR FORM SUBMISSION ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['email']) || empty($_POST['email'])) {
        $message = 'Please enter your email address.';
        $messageType = 'error';
    } else {
        $email = $_POST['email'];

        // 1. Check if email exists in the database
        $stmt = $conn->prepare("SELECT id, first_name FROM users WHERE email = ?");
        
        if ($stmt === false) {
            $message = 'Database error. Please try again later.';
            $messageType = 'error';
            error_log('Prepare failed: ' . $conn->error);
        } else {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                // Email found
                $user = $result->fetch_assoc();
                $userId = $user['id'];
                $firstName = $user['first_name'];

                try {
                    // 2. Generate a cryptographically secure token
                    $resetToken = bin2hex(random_bytes(32));
                    
                    // 3. Set expiry time (1 hour from now)
                    $expiryTime = date('Y-m-d H:i:s', time() + 3600); // 3600 seconds = 1 hour

                    // 4. Store the token and expiry in the database
                    $updateStmt = $conn->prepare("UPDATE users SET reset_token = ?, reset_token_expiry = ? WHERE id = ?");
                    
                    if ($updateStmt === false) {
                        $message = 'Database error. Please try again later.';
                        $messageType = 'error';
                        error_log('Update Prepare failed: ' . $conn->error);
                    } else {
                        $updateStmt->bind_param("ssi", $resetToken, $expiryTime, $userId);
                        
                        if ($updateStmt->execute()) {
                            // 5. Send the password reset email
                            sendResetEmail($email, $firstName, $resetToken);

                            // SECURITY: Use a generic message to prevent email enumeration
                            $message = 'If an account with that email exists, a password reset link has been sent.';
                            $messageType = 'success';
                        } else {
                            $message = 'Failed to update reset token. Please try again.';
                            $messageType = 'error';
                        }
                        $updateStmt->close();
                    }
                } catch (Exception $e) {
                    $message = 'Error generating reset token. Please try again.';
                    $messageType = 'error';
                    error_log('Token generation error: ' . $e->getMessage());
                }
            } else {
                // Email not found
                // SECURITY: Still show a success message to prevent attackers
                // from guessing which emails are registered.
                $message = 'If an account with that email exists, a password reset link has been sent.';
                $messageType = 'success';
            }
            $stmt->close();
        }
    }
    
    // Close the connection after processing
    if ($conn) {
        $conn->close();
    }
}
// --- END: BACKEND LOGIC ---
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - PAWPETCARE Cantilan</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <link rel="icon" type="image/png" href="logo/pawpetcarelogo.png">
    <style>
        :root { --primary: #4361ee; --primary-dark: #3a56d4; --secondary: #7209b7; }
        body { font-family: 'Inter', sans-serif; background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .card { background: white; border-radius: 16px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04); width: 100%; max-width: 450px; }
        .card-header { background: linear-gradient(to right, var(--primary), var(--secondary)); color: white; padding: 24px; border-top-left-radius: 16px; border-top-right-radius: 16px; }
        .card-body { padding: 32px; }
        .btn { padding: 12px 24px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; border: none; display: flex; align-items: center; justify-content: center; width: 100%; }
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: var(--primary-dark); }
        .input-icon { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #64748b; }
        .input-with-icon { padding-left: 40px !important; }
        .loader { border: 3px solid #f3f3f3; border-radius: 50%; border-top: 3px solid var(--primary); width: 20px; height: 20px; animation: spin 1s linear infinite; display: none; margin-left: 8px; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    </style>
</head>
<?php // include 'topbar.php'; // Removed include in body start tag ?>
<body>
    <div class="card">
        <div class="card-header text-center">
            <h1 class="text-2xl font-bold">Forgot Your Password?</h1>
            <p class="text-blue-100 text-sm mt-1">Enter your email to receive a reset link.</p>
        </div>
        
        <div class="card-body">
            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>" id="forgot-password-form">
                <div class="mb-6">
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                    <div class="relative">
                        <i class="fa-solid fa-envelope input-icon"></i>
                        <input type="email" id="email" name="email" class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent input-with-icon" placeholder="you@example.com" required>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary" id="submit-btn">
                    <span>Send Reset Link</span>
                    <div class="loader" id="loader"></div>
                </button>
            </form>
            <div class="text-center mt-6">
                <a href="login.php" class="text-sm font-medium text-blue-600 hover:text-blue-500">
                    <i class="fa-solid fa-arrow-left mr-1"></i> Back to Login
                </a>
            </div>
        </div>
    </div>

    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script>
        document.getElementById('forgot-password-form').addEventListener('submit', function() {
            document.getElementById('submit-btn').disabled = true;
            document.getElementById('submit-btn').querySelector('span').innerText = 'Sending...';
            document.getElementById('loader').style.display = 'inline-block';
        });

        <?php if (!empty($message)): ?>
        document.addEventListener('DOMContentLoaded', function() {
            const messageType = "<?php echo $messageType; ?>";
            Toastify({
                text: "<?php echo addslashes($message); ?>",
                duration: 5000, close: true, gravity: "top", position: "right", stopOnFocus: true,
                style: {
                    background: messageType === 'success'
                        ? "linear-gradient(to right, #00b09b, #96c93d)"
                        : "linear-gradient(to right, #ef476f, #d90429)",
                },
            }).showToast();

            // --- ADDED: Reset button state if it was an error ---
            if (messageType === 'error') {
                document.getElementById('submit-btn').disabled = false;
                document.getElementById('submit-btn').querySelector('span').innerText = 'Send Reset Link';
                document.getElementById('loader').style.display = 'none';
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>