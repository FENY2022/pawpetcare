<?php
// Include your database connection file
include 'db.php';

// Initialize variables
$message = '';
$redirectEmail = ''; // To hold email for redirection

// --- Function to send verification email (to avoid repeating code) ---
function sendVerificationEmail($email, $firstName, $verificationCode) {
    $subject = 'Your Account Verification Code';
    $emailMessage = "Hello " . htmlspecialchars($firstName) . ",\n\nThank you for registering with PAWPETCARE CANTILAN. Your verification code is: " . $verificationCode;

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
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // <-- important

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        $message = "cURL Error: " . curl_error($ch);
    } else {
        $message = "Response from send.php: " . $response;
    }

    curl_close($ch);

    return $message; // return instead of echo
}




// Check if the form has been submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and retrieve form data
    $firstName = trim($_POST['first_name']);
    $middleName = trim($_POST['middle_name']);
    $lastName = trim($_POST['last_name']);
    $contactNumber = trim($_POST['contact_number']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $street = trim($_POST['street']);
    $barangay = trim($_POST['barangay']);
    $city = trim($_POST['city']);
    $province = trim($_POST['province']);
    $postal_code = trim($_POST['postal_code']);
    $country = trim($_POST['country']);

    // Generate a random 6-digit verification code
    $verificationCode = random_int(100000, 999999);

    if ($conn) {
        // --- STEP 1: Check if the email already exists ---
        $sql_check = "SELECT first_name, is_verified FROM users WHERE email = ? LIMIT 1";
        if ($stmt_check = $conn->prepare($sql_check)) {
            $stmt_check->bind_param("s", $email);
            $stmt_check->execute();
            $result = $stmt_check->get_result();
            
            if ($result->num_rows > 0) {
                // --- Email EXISTS ---
                $user = $result->fetch_assoc();
                
                if ($user['is_verified'] == 1) {
                    // Case 1: User exists and is already verified
                    $message = "Error: This email address is already registered and verified.";
                } else {
                    // Case 2: User exists but is NOT verified. Resend code.
                    $sql_update = "UPDATE users SET verification_code = ? WHERE email = ?";
                    if ($stmt_update = $conn->prepare($sql_update)) {
                        $stmt_update->bind_param("ss", $verificationCode, $email);
                        if ($stmt_update->execute()) {
                            // Send new verification code
                            sendVerificationEmail($email, $user['first_name'], $verificationCode);
                            $message = "Account not verified. A new code has been sent to your email.";
                            $redirectEmail = $email;
                        } else {
                             $message = "Error: Could not update verification code.";
                        }
                        $stmt_update->close();
                    }
                }

            } else {
                // --- Email does NOT exist, proceed with new registration ---
                $sql_insert = "INSERT INTO users (first_name, middle_name, last_name, contact_number, email, password, street, barangay, city, province, postal_code, country, verification_code) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                
                if ($stmt_insert = $conn->prepare($sql_insert)) {
                    // Note: 'is_verified' is not in the insert list, assuming it defaults to 0 in your database
                    $stmt_insert->bind_param("sssssssssssss", $firstName, $middleName, $lastName, $contactNumber, $email, $password, $street, $barangay, $city, $province, $postal_code, $country, $verificationCode);
                    
                    if ($stmt_insert->execute()) {
                        // Registration successful, send email
                        sendVerificationEmail($email, $firstName, $verificationCode);
                        $message = "Registration successful! A verification code has been sent.";
                        $redirectEmail = $email;
                    } else {
                        $message = "Error: Could not execute the registration query.";
                    }
                    $stmt_insert->close();
                } else {
                    $message = "Error: Could not prepare the registration query: " . $conn->error;
                }
            }
            $stmt_check->close();
        } else {
            $message = "Error: Could not prepare the email check query: " . $conn->error;
        }
        $conn->close();
    } else {
        $message = "Error: Database connection failed.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PAWPETCARE Cantilan - User Registration</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.tailwindcss.com/3.4.1?plugins=typography"></script> <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <style>
        :root{--primary:#4361ee;--primary-dark:#3a56d4;--secondary:#7209b7;--success:#06d6a0;--danger:#ef476f;--warning:#ffd166;--light:#f8f9fa;--dark:#212529}body{font-family:'Inter',sans-serif;background:linear-gradient(135deg,#f5f7fa 0%,#c3cfe2 100%);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}.card{background:white;border-radius:16px;box-shadow:0 20px 25px -5px rgba(0,0,0,0.1),0 10px 10px -5px rgba(0,0,0,0.04);overflow:hidden;width:100%;max-width:1000px}.card-header{background:linear-gradient(to right,var(--primary),var(--secondary));color:white;padding:24px 32px}.card-body{padding:32px}.form-step{display:none}.form-step.active{display:block;animation:fadeIn 0.5s ease-in-out}.progress-bar{display:flex;margin-bottom:32px;justify-content:space-between;position:relative}.progress-bar::before{content:'';position:absolute;top:50%;left:0;transform:translateY(-50%);height:4px;width:100%;background:#e2e8f0;z-index:1}.progress-bar::after{content:'';position:absolute;top:50%;left:0;transform:translateY(-50%);height:4px;width:var(--progress-width,0%);background:var(--primary);z-index:1;transition:width 0.5s ease}.step{width:40px;height:40px;border-radius:50%;background:white;display:flex;align-items:center;justify-content:center;border:2px solid #e2e8f0;z-index:2;font-weight:600;color:#64748b;position:relative}.step.active{background:var(--primary);color:white;border-color:var(--primary)}.step.completed{background:var(--success);color:white;border-color:var(--success)}.step-label{position:absolute;top:100%;left:50%;transform:translateX(-50%);margin-top:8px;font-size:12px;font-weight:500;color:#64748b;white-space:nowrap}.step.active .step-label{color:var(--primary);font-weight:600}.btn{padding:12px 24px;border-radius:8px;font-weight:600;cursor:pointer;transition:all 0.3s ease;border:none;display:inline-flex;align-items:center;justify-content:center}.btn-primary{background:var(--primary);color:white}.btn-primary:hover{background:var(--primary-dark)}.btn-outline{background:transparent;color:var(--primary);border:1px solid var(--primary)}.btn-outline:hover{background:#f1f5f9}.password-toggle{position:absolute;right:12px;top:50%;transform:translateY(-50%);color:#64748b;cursor:pointer}.loader{border:3px solid #f3f3f3;border-radius:50%;border-top:3px solid var(--primary);width:20px;height:20px;animation:spin 1s linear infinite;display:inline-block;margin-left:8px}@keyframes spin{0%{transform:rotate(0deg)}100%{transform:rotate(360deg)}}@keyframes fadeIn{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:translateY(0)}}.input-icon{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#64748b}.input-with-icon{padding-left:40px !important}.suggestion-item{padding:12px 16px;cursor:pointer;border-bottom:1px solid #f1f5f9;display:flex;align-items:center}.suggestion-item:hover{background:#f8fafc}.suggestion-item i{margin-right:12px;color:var(--primary)}.password-strength{height:6px;margin-top:8px;border-radius:3px;background:#e2e8f0;overflow:hidden}.password-strength-bar{height:100%;width:0%;transition:width 0.3s ease;border-radius:3px}.requirement-list{margin-top:8px}.requirement-item{display:flex;align-items:center;margin-bottom:4px;font-size:13px;color:#64748b}.requirement-item i{margin-right:8px;font-size:12px}.requirement-item.valid{color:var(--success)}@media (max-width:768px){.card{border-radius:12px}.card-body{padding:24px}.progress-bar{margin-bottom:24px}.step-label{display:none}}
    </style>
</head>
<body>
<?php include 'topbar.php'; ?>

    <div class="card">
        <div class="card-header">
            <div class="flex items-center justify-center mb-2">
                <img src="logo/pawpetcarelogo.png" alt="PAWPETCARE Cantilan Logo" class="h-16 w-16 mr-3">
                <h1 class="text-3xl font-bold text-white">PAWPETCARE Cantilan</h1>
            </div>
            <p class="text-blue-100 text-center">Join our community by filling out the information below</p>
        </div>
        
        <div class="card-body">
            <div class="progress-bar">
                <div class="step active" id="step1">
                    <span>1</span>
                    <span class="step-label">Agreement</span>
                </div>
                <div class="step" id="step2">
                    <span>2</span>
                    <span class="step-label">Personal Info</span>
                </div>
                <div class="step" id="step3">
                    <span>3</span>
                    <span class="step-label">Address</span>
                </div>
                <div class="step" id="step4">
                    <span>4</span>
                    <span class="step-label">Credentials</span>
                </div>
            </div>
            
            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>" id="registration-form">
                <div class="form-step active" id="form-step-1">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">Data Privacy Agreement</h2>
                    <div class="prose prose-sm max-w-none h-64 overflow-y-auto border p-4 rounded-lg bg-gray-50 mb-6">
                        <h4>1. Introduction</h4>
                        <p>Welcome to PAWPETCARE Cantilan. We are committed to protecting your privacy. This Data Privacy Agreement explains how we collect, use, disclose, and safeguard your information when you use our services.</p>
                        
                        <h4>2. Information We Collect</h4>
                        <p>We may collect personal identification information from you in various ways, including, but not limited to, when you register on the site, book an appointment, or fill out a form. The information we collect includes:</p>
                        <ul>
                            <li><strong>Personal Data:</strong> Your name, email address, contact number, and address.</li>
                            <li><strong>Pet Information:</strong> Details about your pets required for our services.</li>
                        </ul>

                        <h4>3. How We Use Your Information</h4>
                        <p>We use the information we collect to:</p>
                        <ul>
                            <li>Provide, operate, and maintain our services.</li>
                            <li>Process your transactions and manage your appointments.</li>
                            <li>Communicate with you, including sending verification codes, appointment reminders, and promotional materials.</li>
                            <li>Improve our website and services.</li>
                        </ul>

                        <h4>4. Your Consent</h4>
                        <p>By using our services and providing us with your personal information, you consent to the collection, use, and processing of your data as described in this agreement. You have the right to withdraw your consent at any time by contacting us.</p>
                        
                        <p class="font-semibold">By checking the box below, you confirm that you have read, understood, and agree to the terms outlined in this Data Privacy Agreement.</p>
                    </div>
                    <div class="flex items-center">
                        <input type="checkbox" id="privacy-agreement" name="privacy_agreement" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="privacy-agreement" class="ml-2 block text-sm text-gray-900">I have read and agree to the Data Privacy Agreement.</label>
                    </div>
                    <div class="flex justify-end mt-8">
                        <button type="button" class="btn btn-primary next-step" data-next="2">Continue <i class="fa-solid fa-arrow-right ml-2"></i></button>
                    </div>
                </div>
                
                <div class="form-step" id="form-step-2">
                    <h2 class="text-xl font-semibold text-gray-800 mb-6">Personal Information</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="relative"><label for="first_name" class="block text-sm font-medium text-gray-700 mb-1">First Name</label><div class="relative"><i class="fa-solid fa-user input-icon"></i><input type="text" id="first_name" name="first_name" class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent input-with-icon" required></div></div>
                        <div class="relative"><label for="middle_name" class="block text-sm font-medium text-gray-700 mb-1">Middle Name <span class="text-gray-500">(Optional)</span></label><div class="relative"><i class="fa-solid fa-user input-icon"></i><input type="text" id="middle_name" name="middle_name" class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent input-with-icon"></div></div>
                        <div class="relative"><label for="last_name" class="block text-sm font-medium text-gray-700 mb-1">Last Name</label><div class="relative"><i class="fa-solid fa-user input-icon"></i><input type="text" id="last_name" name="last_name" class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent input-with-icon" required></div></div>
                        <div class="relative"><label for="contact_number" class="block text-sm font-medium text-gray-700 mb-1">Contact Number</label><div class="relative"><i class="fa-solid fa-phone input-icon"></i><input type="tel" id="contact_number" name="contact_number" placeholder="09xxxxxxxxx" class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent input-with-icon" required></div></div>
                    </div>
                    <div class="flex justify-between mt-8">
                        <button type="button" class="btn btn-outline prev-step" data-prev="1"><i class="fa-solid fa-arrow-left mr-2"></i> Back</button>
                        <button type="button" class="btn btn-primary next-step" data-next="3">Continue <i class="fa-solid fa-arrow-right ml-2"></i></button>
                    </div>
                </div>
                
                <div class="form-step" id="form-step-3">
                    <h2 class="text-xl font-semibold text-gray-800 mb-6">Address Information</h2>
                    <div class="relative mb-6"><label for="address-search" class="block text-sm font-medium text-gray-700 mb-1">Search for an address</label><div class="relative"><i class="fa-solid fa-magnifying-glass input-icon"></i><input type="text" id="address-search" placeholder="Search for an address in Caraga Region" class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent input-with-icon"><div id="address-spinner" class="loader absolute right-3 top-3 hidden"></div></div><div id="address-suggestions" class="absolute z-10 w-full bg-white border border-gray-300 rounded-lg mt-1 shadow-xl max-h-60 overflow-y-auto hidden"></div></div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div><label for="street" class="block text-sm font-medium text-gray-700 mb-1">Street Address</label><div class="relative"><i class="fa-solid fa-road input-icon"></i><input type="text" id="street" name="street" class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent input-with-icon" required></div></div>
                        <div><label for="barangay" class="block text-sm font-medium text-gray-700 mb-1">Barangay</label><div class="relative"><i class="fa-solid fa-map-marker-alt input-icon"></i><input type="text" id="barangay" name="barangay" class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent input-with-icon"></div></div>
                        <div><label for="city" class="block text-sm font-medium text-gray-700 mb-1">City</label><div class="relative"><i class="fa-solid fa-city input-icon"></i><input type="text" id="city" name="city" class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent input-with-icon" required></div></div>
                        <div><label for="province" class="block text-sm font-medium text-gray-700 mb-1">Province / State</label><div class="relative"><i class="fa-solid fa-globe input-icon"></i><input type="text" id="province" name="province" class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent input-with-icon" required></div></div>
                        <div><label for="postal_code" class="block text-sm font-medium text-gray-700 mb-1">Postal Code</label><div class="relative"><i class="fa-solid fa-envelope input-icon"></i><input type="text" id="postal_code" name="postal_code" class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent input-with-icon"></div></div>
                        <div><label for="country" class="block text-sm font-medium text-gray-700 mb-1">Country</label><div class="relative"><i class="fa-solid fa-flag input-icon"></i><input type="text" id="country" name="country" class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent input-with-icon" required></div></div>
                    </div>
                    <div class="flex justify-between mt-8"><button type="button" class="btn btn-outline prev-step" data-prev="2"><i class="fa-solid fa-arrow-left mr-2"></i> Back</button><button type="button" class="btn btn-primary next-step" data-next="4">Continue <i class="fa-solid fa-arrow-right ml-2"></i></button></div>
                </div>
                
                <div class="form-step" id="form-step-4">
                    <h2 class="text-xl font-semibold text-gray-800 mb-6">Account Credentials</h2>
                    <div class="grid grid-cols-1 gap-6">
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                            <div class="relative">
                                <i class="fa-solid fa-envelope input-icon"></i>
                                <input type="email" id="email" name="email" class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent input-with-icon" required>
                            </div>
                        </div>
                        <div class="relative">
                            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                            <div class="relative">
                                <i class="fa-solid fa-lock input-icon"></i>
                                <input type="password" id="password" name="password" class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent input-with-icon" required>
                                <span class="password-toggle" id="password-toggle"><i class="fa-solid fa-eye"></i></span>
                            </div>
                            <div class="password-strength mt-2">
                                <div class="password-strength-bar" id="password-strength-bar"></div>
                            </div>
                            <div class="requirement-list">
                                <div class="requirement-item" id="length-req"><i class="fa-solid fa-circle"></i><span>At least 8 characters</span></div>
                                <div class="requirement-item" id="uppercase-req"><i class="fa-solid fa-circle"></i><span>Contains uppercase letter</span></div>
                                <div class="requirement-item" id="lowercase-req"><i class="fa-solid fa-circle"></i><span>Contains lowercase letter</span></div>
                                <div class="requirement-item" id="number-req"><i class="fa-solid fa-circle"></i><span>Contains number</span></div>
                                <div class="requirement-item" id="special-req"><i class="fa-solid fa-circle"></i><span>Contains special character</span></div>
                            </div>
                        </div>
                        <div class="relative">
                            <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                            <div class="relative">
                                <i class="fa-solid fa-lock input-icon"></i>
                                <input type="password" id="confirm_password" name="confirm_password" class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent input-with-icon" required>
                                <span class="password-toggle" id="confirm-password-toggle"><i class="fa-solid fa-eye"></i></span>
                            </div>
                        </div>
                    </div>
                    <div class="flex justify-between mt-8"><button type="button" class="btn btn-outline prev-step" data-prev="3"><i class="fa-solid fa-arrow-left mr-2"></i> Back</button><button type="submit" class="btn btn-primary" id="submit-btn">Register <i class="fa-solid fa-user-plus ml-2"></i></button></div>
                </div>
            </form>
        </div>
    </div>

    <script>
        // All the form navigation and validation JS
        const formSteps = document.querySelectorAll(".form-step");
        const steps = document.querySelectorAll(".step");
        const nextButtons = document.querySelectorAll(".next-step");
        const prevButtons = document.querySelectorAll(".prev-step");
        const progressBar = document.querySelector(".progress-bar");
        let currentStep = 1;

        function updateProgress() {
            const progress = (currentStep - 1) / (steps.length - 1) * 100;
            progressBar.style.setProperty("--progress-width", `${progress}%`);
            steps.forEach((step, index) => {
                if (index + 1 < currentStep) {
                    step.classList.add("completed");
                    step.classList.remove("active");
                } else if (index + 1 === currentStep) {
                    step.classList.add("active");
                    step.classList.remove("completed");
                } else {
                    step.classList.remove("active", "completed");
                }
            });
        }

        nextButtons.forEach(button => {
            button.addEventListener("click", () => {
                const nextStep = parseInt(button.getAttribute("data-next"));
                if (validateStep(currentStep)) {
                    formSteps.forEach(step => {
                        step.classList.remove("active");
                    });
                    document.getElementById(`form-step-${nextStep}`).classList.add("active");
                    currentStep = nextStep;
                    updateProgress();
                }
            });
        });

        prevButtons.forEach(button => {
            button.addEventListener("click", () => {
                const prevStep = parseInt(button.getAttribute("data-prev"));
                formSteps.forEach(step => {
                    step.classList.remove("active");
                });
                document.getElementById(`form-step-${prevStep}`).classList.add("active");
                currentStep = prevStep;
                updateProgress();
            });
        });

        // UPDATED VALIDATION FUNCTION
        function validateStep(step) {
            let isValid = true;
            if (step === 1) { // New: Validate Data Privacy Agreement
                const agreementCheckbox = document.getElementById("privacy-agreement");
                if (!agreementCheckbox.checked) {
                    Toastify({
                        text: "You must agree to the Data Privacy Agreement to continue.",
                        duration: 3500,
                        gravity: "top",
                        position: "right",
                        style: { background: "linear-gradient(to right, #ef476f, #d90429)" }
                    }).showToast();
                    isValid = false;
                }
            } else if (step === 2) { // Formerly step 1
                const firstName = document.getElementById("first_name");
                const lastName = document.getElementById("last_name");
                const contactNumber = document.getElementById("contact_number");
                
                if (!firstName.value.trim()) {
                    showError(firstName, "First name is required");
                    isValid = false;
                } else {
                    clearError(firstName);
                }

                if (!lastName.value.trim()) {
                    showError(lastName, "Last name is required");
                    isValid = false;
                } else {
                    clearError(lastName);
                }

                if (!contactNumber.value.trim()) {
                    showError(contactNumber, "Contact number is required");
                    isValid = false;
                } else if (!/^09\d{9}$/.test(contactNumber.value.trim())) {
                    showError(contactNumber, "Please enter a valid 11-digit mobile number (e.g., 09xxxxxxxxx)");
                    isValid = false;
                } else {
                    clearError(contactNumber);
                }
            }
            // You can add validation for step 3 (Address) here if needed
            return isValid;
        }

        function showError(element, message) {
            const parent = element.parentElement;
            const grandparent = parent.parentElement;
            let errorDiv = grandparent.querySelector(".text-red-500");
            if (!errorDiv) {
                errorDiv = document.createElement("div");
                errorDiv.className = "text-red-500 text-xs mt-1 pl-1";
                grandparent.appendChild(errorDiv);
            }
            errorDiv.innerText = message;
            element.classList.add("border-red-500");
        }

        function clearError(element) {
            const parent = element.parentElement;
            const grandparent = parent.parentElement;
            const errorDiv = grandparent.querySelector(".text-red-500");
            if (errorDiv) {
                grandparent.removeChild(errorDiv);
            }
            element.classList.remove("border-red-500");
        }

        const searchInput = document.getElementById("address-search");
        const suggestionsContainer = document.getElementById("address-suggestions");
        const spinner = document.getElementById("address-spinner");
        const geoapifyKey = "1bb88d414e5c47c89903f9a886f54d1d"; // This key is public, please replace it with your own if needed
        const streetInput = document.getElementById("street");
        const barangayInput = document.getElementById("barangay");
        const cityInput = document.getElementById("city");
        const provinceInput = document.getElementById("province");
        const postalCodeInput = document.getElementById("postal_code");
        const countryInput = document.getElementById("country");
        let debounceTimer;

        searchInput.addEventListener("input", () => {
            clearTimeout(debounceTimer);
            spinner.classList.remove("hidden");
            debounceTimer = setTimeout(() => {
                const url = `https://api.geoapify.com/v1/geocode/autocomplete?text=${encodeURIComponent(searchInput.value)}&filter=countrycode:ph&bias=proximity:125.7,9.2&apiKey=${geoapifyKey}`;
                fetch(url)
                    .then(response => response.json())
                    .then(data => {
                        spinner.classList.add("hidden");
                        suggestionsContainer.innerHTML = "";
                        if (data.features && data.features.length > 0) {
                            suggestionsContainer.classList.remove("hidden");
                            data.features.forEach(feature => {
                                const suggestionItem = document.createElement("div");
                                suggestionItem.className = "suggestion-item";
                                suggestionItem.innerHTML = `<i class="fa-solid fa-location-dot"></i><div>${feature.properties.formatted}</div>`;
                                suggestionItem.addEventListener("click", () => {
                                    fillAddressFields(feature);
                                    suggestionsContainer.classList.add("hidden");
                                    searchInput.value = feature.properties.formatted;
                                });
                                suggestionsContainer.appendChild(suggestionItem);
                            });
                        } else {
                            suggestionsContainer.classList.add("hidden");
                        }
                    })
                    .catch(error => {
                        spinner.classList.add("hidden");
                        console.error("Error fetching address data:", error);
                    });
            }, 500);
        });

        document.addEventListener("click", function(event) {
            if (!suggestionsContainer.contains(event.target) && event.target !== searchInput) {
                suggestionsContainer.classList.add("hidden");
            }
        });

        function fillAddressFields(feature) {
            streetInput.value = "";
            barangayInput.value = "";
            cityInput.value = "";
            provinceInput.value = "";
            postalCodeInput.value = "";
            countryInput.value = "";
            const props = feature.properties;
            let street = props.street || "";
            if (props.housenumber) {
                street = `${props.housenumber} ${street}`.trim();
            }
            streetInput.value = street;
            barangayInput.value = props.suburb || "";
            cityInput.value = props.city || "";
            provinceInput.value = props.state || "";
            postalCodeInput.value = props.postcode || "";
            countryInput.value = props.country || "";
            [streetInput, barangayInput, cityInput, provinceInput, postalCodeInput, countryInput].forEach(input => {
                input.readOnly = false;
                input.classList.remove("bg-gray-50");
            });
        }

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
        
        const confirmPasswordToggle = document.getElementById("confirm-password-toggle");
        const confirmPasswordInput = document.getElementById("confirm_password");
        confirmPasswordToggle.addEventListener("click", () => {
            if (confirmPasswordInput.type === "password") {
                confirmPasswordInput.type = "text";
                confirmPasswordToggle.innerHTML = '<i class="fa-solid fa-eye-slash"></i>';
            } else {
                confirmPasswordInput.type = "password";
                confirmPasswordToggle.innerHTML = '<i class="fa-solid fa-eye"></i>';
            }
        });

        const strengthBar = document.getElementById("password-strength-bar");
        const requirements = {
            length: document.getElementById("length-req"),
            uppercase: document.getElementById("uppercase-req"),
            lowercase: document.getElementById("lowercase-req"),
            number: document.getElementById("number-req"),
            special: document.getElementById("special-req")
        };

        passwordInput.addEventListener("input", checkPasswordStrength);

        function checkPasswordStrength() {
            const password = passwordInput.value;
            let score = 0;

            if (password.length >= 8) {
                score += 20;
                requirements.length.classList.add("valid");
                requirements.length.innerHTML = '<i class="fa-solid fa-check-circle"></i> At least 8 characters';
            } else {
                requirements.length.classList.remove("valid");
                requirements.length.innerHTML = '<i class="fa-solid fa-circle"></i> At least 8 characters';
            }
            if (/[A-Z]/.test(password)) {
                score += 20;
                requirements.uppercase.classList.add("valid");
                requirements.uppercase.innerHTML = '<i class="fa-solid fa-check-circle"></i> Contains uppercase letter';
            } else {
                requirements.uppercase.classList.remove("valid");
                requirements.uppercase.innerHTML = '<i class="fa-solid fa-circle"></i> Contains uppercase letter';
            }
            if (/[a-z]/.test(password)) {
                score += 20;
                requirements.lowercase.classList.add("valid");
                requirements.lowercase.innerHTML = '<i class="fa-solid fa-check-circle"></i> Contains lowercase letter';
            } else {
                requirements.lowercase.classList.remove("valid");
                requirements.lowercase.innerHTML = '<i class="fa-solid fa-circle"></i> Contains lowercase letter';
            }
            if (/[0-9]/.test(password)) {
                score += 20;
                requirements.number.classList.add("valid");
                requirements.number.innerHTML = '<i class="fa-solid fa-check-circle"></i> Contains number';
            } else {
                requirements.number.classList.remove("valid");
                requirements.number.innerHTML = '<i class="fa-solid fa-circle"></i> Contains number';
            }
            if (/[^A-Za-z0-9]/.test(password)) {
                score += 20;
                requirements.special.classList.add("valid");
                requirements.special.innerHTML = '<i class="fa-solid fa-check-circle"></i> Contains special character';
            } else {
                requirements.special.classList.remove("valid");
                requirements.special.innerHTML = '<i class="fa-solid fa-circle"></i> Contains special character';
            }
            
            strengthBar.style.width = `${score}%`;
            if (score < 40) {
                strengthBar.style.backgroundColor = "#ef476f";
            } else if (score < 80) {
                strengthBar.style.backgroundColor = "#ffd166";
            } else {
                strengthBar.style.backgroundColor = "#06d6a0";
            }
        }
        
        // Form submission validation for password match and strength
        document.getElementById('registration-form').addEventListener('submit', function(event) {
            let isValid = true;
            
            // 1. Check if passwords match
            if (passwordInput.value !== confirmPasswordInput.value) {
                isValid = false;
                showError(confirmPasswordInput, 'Passwords do not match.');
            } else {
                clearError(confirmPasswordInput);
            }

            // 2. Check if password meets all requirements
            const passwordValue = passwordInput.value;
            const requirementsMet = 
                passwordValue.length >= 8 &&
                /[A-Z]/.test(passwordValue) &&
                /[a-z]/.test(passwordValue) &&
                /[0-9]/.test(passwordValue) &&
                /[^A-Za-z0-9]/.test(passwordValue);

            if (!requirementsMet) {
                isValid = false;
                Toastify({
                    text: "Password does not meet all requirements.",
                    duration: 3500,
                    gravity: "top",
                    position: "right",
                    style: { background: "linear-gradient(to right, #ef476f, #d90429)" }
                }).showToast();
            }

            // 3. Prevent form submission if anything is invalid
            if (!isValid) {
                event.preventDefault();
            }
        });
        
        updateProgress();
    </script>
    
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>

    <?php if (!empty($message)): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const isError = <?php echo (strpos($message, 'Error') !== false) ? 'true' : 'false'; ?>;
            const msg = "<?php echo addslashes($message); ?>";

            Toastify({
                text: msg,
                duration: isError ? 5000 : 4000,
                close: true,
                gravity: "top",
                position: "right",
                stopOnFocus: true,
                style: {
                    background: isError 
                        ? "linear-gradient(to right, #ef476f, #d90429)"
                        : "linear-gradient(to right, #00b09b, #96c93d)",
                },
            }).showToast();

            // If registration was successful, redirect to the verification page
            if (!isError && "<?php echo $redirectEmail; ?>") {
                const emailForRedirect = "<?php echo urlencode($redirectEmail); ?>";
                setTimeout(() => {
                    window.location.href = `verify.php?email=${emailForRedirect}`;
                }, 4000); // 4-second delay to allow user to read the toast
            }
        });
    </script>
    <?php endif; ?>

</body>
</html>