<?php
session_start();
require_once '../backend/config/database.php';

// Initialize messages from session and clear them
$error = isset($_SESSION['error']) ? $_SESSION['error'] : '';
unset($_SESSION['error']);

$success = isset($_SESSION['success']) ? $_SESSION['success'] : '';
unset($_SESSION['success']);

// Fetch distinct specialities already existing in the medecin table
$existingSpecialities = [];
try {
    $stmt = $pdo->query("SELECT DISTINCT specialite FROM medecin WHERE specialite IS NOT NULL AND specialite != '' ORDER BY specialite");
    $existingSpecialities = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    // Log error or handle it appropriately
    error_log("Failed to fetch existing specialities: " . $e->getMessage());
}

function validateEmail($email) {
    $role = '';
    $valid = false;
    
    // Check email format and role
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        if (preg_match('/@admin\.com$/', $email)) {
            $role = 'admin';
            $valid = true;
        } elseif (preg_match('/@med\.com$/', $email)) {
            $role = 'medecin';
            $valid = true;
        } elseif (preg_match('/@pat\.com$/', $email)) {
            $role = 'patient';
            $valid = true;
        }
    }
    
    return ['valid' => $valid, 'role' => $role];
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['login'])) {
        $email = $_POST['email'];
        $password = $_POST['password'];

        // First check if email exists in database
        $stmt = $pdo->prepare("SELECT * FROM utilisateur WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            // Debug information
            error_log("Stored password hash: " . $user['mot_de_passe']);
            error_log("Input password: " . $password);
            
            // Check if the stored password is already hashed
            if (strlen($user['mot_de_passe']) < 60) {
                // If password is not hashed, compare directly
                if ($password === $user['mot_de_passe']) {
                    // Password matches, now determine role from email domain
                    $emailValidation = validateEmail($email);
                    if ($emailValidation['valid']) {
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['role'] = $emailValidation['role'];
                        $_SESSION['user'] = $user;
                        
                        // Redirect based on email domain
                        switch($emailValidation['role']) {
                            case 'admin':
                                header('Location: ../frontend/admin/admin_dashboard.php');
                                break;
                            case 'medecin':
                                header('Location: ../frontend/medecin/doctor_dashboard.php');
                                break;
                            case 'patient':
                                header('Location: ../frontend/patient/patient_dashboard.php');
                                break;
                        }
                        exit();
                    }
                }
            } else {
                // If password is hashed, use password_verify
                if (password_verify($password, $user['mot_de_passe'])) {
                    // Password is correct, now determine role from email domain
                    $emailValidation = validateEmail($email);
                    if ($emailValidation['valid']) {
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['role'] = $emailValidation['role'];
                        
                        // Redirect based on email domain
                        switch($emailValidation['role']) {
                            case 'admin':
                                header('Location: ../frontend/admin/admin_dashboard.php');
                                break;
                            case 'medecin':
                                header('Location: ../frontend/medecin/doctor_dashboard.php');
                                break;
                            case 'patient':
                                header('Location: ../frontend/patient/patient_dashboard.php');
                                break;
                        }
                        exit();
                    }
                }
            }
            
            // If we get here, either password was wrong or email format was invalid
            if (!validateEmail($email)['valid']) {
                $_SESSION['error'] = "Invalid email format. Please use @admin.com for admin, @med.com for doctor, or @pat.com for patient";
            } else {
                $_SESSION['error'] = "Invalid password";
            }
            header('Location: Authentification.php');
            exit();
        } else {
            $_SESSION['error'] = "Email not found in database";
            header('Location: Authentification.php');
            exit();
        }
    } elseif (isset($_POST['signup'])) {
        $email = $_POST['email'];
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $nom = $_POST['nom'];
        $selected_specialite = null; // Initialize selected speciality name

        // Validate email format and determine role
        $emailValidation = validateEmail($email);
        if (!$emailValidation['valid']) {
            $_SESSION['error'] = "Invalid email format. Please use @admin.com for admin, @med.com for doctor, or @pat.com for patient";
        } else {
            $role = $emailValidation['role'];

            // If role is medecin, get the selected speciality
            if ($role === 'medecin') {
                if (isset($_POST['specialite']) && !empty($_POST['specialite'])) {
                    $selected_specialite = htmlspecialchars(trim($_POST['specialite']));
                    // Optional: Add validation to check if the selected speciality name is valid if needed
                    // For this approach, we assume dropdown values come from existing data, so they are valid.
                    if (empty($selected_specialite)) {
                        $_SESSION['error'] = "Invalid speciality selected.";
                        header('Location: Authentification.php');
                        exit();
                    }
                } else {
                    $_SESSION['error'] = "Please select a speciality for doctor registration.";
                    header('Location: Authentification.php');
                    exit();
                }
            }

            if ($password !== $confirm_password) {
                $_SESSION['error'] = "Passwords do not match";
            } else {
                // Check if email already exists
                $stmt = $pdo->prepare("SELECT id FROM utilisateur WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->rowCount() > 0) {
                    $_SESSION['error'] = "Email already exists";
                } else {
                    // Insert new user
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO utilisateur (nom, email, mot_de_passe, role) VALUES (?, ?, ?, ?)");
                    
                    try {
                        $stmt->execute([$nom, $email, $hashed_password, $role]);
                        $user_id = $pdo->lastInsertId();

                        // Create corresponding role-specific record
                        switch($role) {
                            case 'admin':
                                $stmt = $pdo->prepare("INSERT INTO admin (id) VALUES (?)");
                                break;
                            case 'medecin':
                                $stmt = $pdo->prepare("INSERT INTO medecin (id, specialite) VALUES (?, ?)");
                                break;
                            case 'patient':
                                $stmt = $pdo->prepare("INSERT INTO patient (id) VALUES (?)");
                                break;
                        }
                        if ($role === 'medecin') {
                            $stmt->execute([$user_id, $selected_specialite]);
                        } else {
                            $stmt->execute([$user_id]);
                        }
                        
                        $_SESSION['success'] = "Registration successful! Please login.";
                    } catch(PDOException $e) {
                        $_SESSION['error'] = "Registration failed: " . $e->getMessage();
                    }
                }
            }
        }

        // Handle saving date_naissance for patients
        if ($role === 'patient') {
            if (isset($_POST['date_naissance']) && !empty($_POST['date_naissance'])) {
                $date_naissance = trim($_POST['date_naissance']);
                // Basic date format validation (YYYY-MM-DD)
                if (!preg_match('/^\\d{4}-\\d{2}-\\d{2}$/', $date_naissance)) {
                    $_SESSION['error'] = "Invalid date format for date of birth.";
                    header('Location: Authentification.php');
                    exit();
                }

                // Assuming the patient table has a date_naissance column
                try {
                    $stmt = $pdo->prepare("UPDATE patient SET date_naissance = ? WHERE id = ?");
                    $stmt->execute([$date_naissance, $user_id]);
                } catch(PDOException $e) {
                    // Handle error (e.g., log it)
                    error_log("Failed to save date_naissance for patient: " . $e->getMessage());
                    // Optionally, set an error message for the user
                    // $_SESSION['error'] = "Failed to save date of birth.";
                }
            } else {
                $_SESSION['error'] = "Date of birth is required for patient registration.";
                header('Location: Authentification.php');
                exit();
            }
        }

        header('Location: Authentification.php');
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preload" href="images/background-login.jpg" as="image">
    <title>Login/Signup Form</title>
    <style>
        @import url('https://fonts.googleapis.com/css?family=Poppins:400,500,600,700&display=swap');
        * {
            font-family: 'Poppins', sans-serif;
        }
        
        html, body {
            display: grid;
            height: 100%;
            width: 100%;
            place-items: center;
            background-image: url('images/background-login.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            opacity: 0;
            animation: fadeIn 1s ease-in forwards;
        }
        @keyframes fadeIn {
            to {
                opacity: 1;
            }
        }

        .wrapper {
            overflow: hidden;
            max-width: 390px;
            background: rgba(255, 255, 255, 0.2);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0px 15px 20px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        
        .wrapper .title-text {
            display: flex;
            width: 200%;
        }
        
        .wrapper .title {
            width: 50%;
            font-size: 35px;
            font-weight: 600;
            text-align: center;
            transition: all 0.6s cubic-bezier(0.68,-0.55,0.265,1.55);
        }
        
        .wrapper .slide-controls {
            position: relative;
            display: flex;
            height: 50px;
            width: 100%;
            overflow: hidden;
            margin: 30px 0 10px 0;
            justify-content: space-between;
            border: 1px solid lightgrey;
            border-radius: 15px;
        }
        
        .slide-controls .slide {
            height: 100%;
            width: 100%;
            color: #000;
            font-size: 18px;
            font-weight: 500;
            text-align: center;
            line-height: 48px;
            cursor: pointer;
            z-index: 1;
            transition: all 0.6s ease;
        }
        
        .slide-controls label.signup {
            color: #000;
        }
        
        .slide-controls .slider-tab {
            position: absolute;
            height: 100%;
            width: 50%;
            left: 0;
            z-index: 0;
            border-radius: 15px;
            background-image: linear-gradient(120deg, #0e2f44, #1a5276, #2e86c1);
            transition: all 0.6s cubic-bezier(0.68,-0.55,0.265,1.55);
        }
        
        input[type="radio"] {
            display: none;
        }
        
        #signup:checked ~ .slider-tab {
            left: 50%;
        }
        
        #signup:checked ~ label.signup {
            color: #fff;
            cursor: default;
            user-select: none;
        }
        
        #signup:checked ~ label.login {
            color: #000;
        }
        
        #login:checked ~ label.signup {
            color: #000;
        }
        
        #login:checked ~ label.login {
            color: #fff;
            cursor: default;
            user-select: none;
        }
        
        .wrapper .form-container {
            width: 100%;
            overflow: hidden;
        }
        
        .form-container .form-inner {
            display: flex;
            width: 200%;
            transition: transform 0.6s cubic-bezier(0.68,-0.55,0.265,1.55);
        }
        
        .form-container .form-inner form {
            width: 50%;
        }
        
        .form-inner form .field {
            height: 50px;
            width: 100%;
            margin-top: 20px;
            position: relative;
            margin-bottom: 25px;
        }
        
        .form-inner form.login .field input {
            width: 95%;
            margin: 0 auto;
            display: block;
        }
        
        .form-inner form.signup .field input {
            width: 95%;
            margin: 0 auto;
            display: block;
        }
        
        .form-inner form .field input {
            height: 100%;
            width: 100%;
            outline: none;
            padding-left: 15px;
            padding-right: 15px;
            box-sizing: border-box;
            border-radius: 15px;
            border: 1px solid rgb(0, 0, 0);
            background: rgba(255, 255, 255, 0.2);
            color: #000000;
            font-size: 17px;
            transition: all 0.3s ease;
            backdrop-filter: blur(5px);
            -webkit-backdrop-filter: blur(5px);
        }

        .form-inner form .field input::placeholder {
            color: #242628;
            transition: all 0.3s ease;
        }

        .form-inner form .field input:focus {
            border-color: black;
            background: rgba(255, 255, 255, 0.3);
        }
        
        form .field input:focus::placeholder {
            color: #2e86c1;
        }
        
        .form-inner form .pass-link {
            margin-top: 5px;
        }
        
        .form-inner form .signup-link {
            text-align: center;
            margin-top: 30px;
        }
        
        .form-inner form .pass-link a,
        .form-inner form .signup-link a {
            color: #234c68;
            text-decoration: none;
        }
        
        .form-inner form .pass-link a:hover,
        .form-inner form .signup-link a:hover {
            text-decoration: underline;
        }
        
        form .btn {
            height: 50px;
            width: 100%;
            border-radius: 15px;
            position: relative;
            overflow: hidden;
            margin-top: 20px;
        }
        
        form .btn .btn-layer {
            height: 100%;
            width: 300%;
            position: absolute;
            left: -100%;
            background-image: linear-gradient(120deg, #0e2f44, #1a5276, #2e86c1);
            border-radius: 15px;
            transition: all 0.4s ease;
        }
        
        form .btn:hover .btn-layer {
            left: 0;
        }
        
        form .btn input[type="submit"] {
            height: 100%;
            width: 100%;
            z-index: 1;
            position: relative;
            background: none;
            border: none;
            color: #fff;
            padding-left: 0;
            border-radius: 15px;
            font-size: 20px;
            font-weight: 500;
            cursor: pointer;
        }

        /* New styles for messages */
        .error-message, .success-message {
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
            text-align: center;
            font-weight: 500;
            font-size: 14px;
        }
        .error-message {
            background-color: rgba(255, 0, 0, 0.1);
            color: #ff0000;
            margin-top: -5px;
            margin-left: 10px;
            text-align: left;
            font-weight: normal;
        }
        .success-message {
            background-color: rgba(0, 255, 0, 0.1);
            color: #008000;
            border: 1px solid #008000;
        }
        .email-hint {
            position: absolute;
            right: -220px;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 10px;
            border-radius: 5px;
            font-size: 12px;
            width: 200px;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease, visibility 0.3s ease;
            z-index: 1000;
        }

        .email-hint::before {
            content: '';
            position: absolute;
            left: -5px;
            top: 50%;
            transform: translateY(-50%);
            border-width: 5px;
            border-style: solid;
            border-color: transparent rgba(0, 0, 0, 0.8) transparent transparent;
        }

        .form-inner form .field:hover .email-hint,
        .form-inner form .field input:focus + .email-hint {
            opacity: 1;
            visibility: visible;
        }

        /* Style for the email guide on the left */
        .email-guide-left {
            font-size: 12px;
            color: #000;
            margin-top: 5px;
            padding: 5px;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 5px;
            position: absolute;
            left: calc(-150px - 10px);
            top: 0;
            width: 150px;
            z-index: 10;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease, visibility 0.3s ease;
        }

        /* Show guide on hover of the parent field */
        .form-inner form .field:hover .email-guide-left {
            opacity: 1;
            visibility: visible;
        }

        /* Style for the speciality dropdown container */
        .speciality-field {
            display: none;
            height: 50px;
            width: 100%;
            margin-top: 20px;
            position: relative;
            margin-bottom: 25px;
        }
        
        .speciality-field select {
            height: 100%;
            width: 100%;
            outline: none;
            padding: 0 15px;
            border-radius: 15px;
            border: 1px solid rgb(0, 0, 0);
            background: rgba(255, 255, 255, 0.2);
            color: #000000;
            font-size: 17px;
            transition: all 0.3s ease;
            backdrop-filter: blur(5px);
            -webkit-backdrop-filter: blur(5px);
            cursor: pointer;
        }

        /* Style for the date of birth input container */
        .date-naissance-field {
            display: none;
            height: 50px;
            width: 100%;
            margin-top: 20px;
            position: relative;
            margin-bottom: 25px;
        }
        
        .date-naissance-field input[type="date"] {
            height: 100%;
            width: 100%;
            outline: none;
            padding: 0 15px;
            border-radius: 15px;
            border: 1px solid rgb(0, 0, 0);
            background: rgba(255, 255, 255, 0.2);
            color: #000000;
            font-size: 17px;
            transition: all 0.3s ease;
            backdrop-filter: blur(5px);
            -webkit-backdrop-filter: blur(5px);
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="title-text">
            <div class="title login">Login</div>
            <div class="title signup">Signup</div>
        </div>
        <?php if ($error): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <div class="form-container">
            <div class="slide-controls">
                <input type="radio" name="slide" id="login" checked>
                <input type="radio" name="slide" id="signup">
                <label for="login" class="slide login">Login</label>
                <label for="signup" class="slide signup">Signup</label>
                <div class="slider-tab"></div>
            </div>
            <div class="form-inner">
                <form action="" method="POST" class="login">
                    <div class="field">
                        <input type="email" name="email" placeholder="Email Address" required>
                    </div>
                    <div class="field">
                        <input type="password" name="password" placeholder="Password" required>
                    </div>
                    <div class="pass-link"><a href="#">Forgot password?</a></div>
                    <div class="field btn">
                        <div class="btn-layer"></div>
                        <input type="submit" name="login" value="Login">
                    </div>
                    <div class="signup-link">Not a member? <a href="#">Signup now</a></div>
                </form>
                <form action="" method="POST" class="signup">
                    <div class="field">
                        <input type="text" name="nom" placeholder="Full Name" required>
                    </div>
                    <div class="field">
                        <input type="email" name="email" placeholder="Email Address" required>
                        <div class="email-guide-left">
                            <b>Email Format Guide:</b><br>
                            - @admin.com: For Administrators<br>
                            - @med.com: For Doctors<br>
                            - @pat.com: For Patients
                        </div>
                        <div class="email-hint">
                            Use one of these email formats:<br>
                            - @admin.com for admin<br>
                            - @med.com for doctor<br>
                            - @pat.com for patient
                        </div>
                    </div>
                    <div class="field speciality-field">
                        <select name="specialite">
                            <option value="">Select Speciality</option>
                            <?php foreach ($existingSpecialities as $specialityName): ?>
                                <option value="<?php echo htmlspecialchars($specialityName); ?>">
                                    <?php echo htmlspecialchars($specialityName); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field date-naissance-field">
                        <input type="date" name="date_naissance" required>
                    </div>
                    <div class="field">
                        <input type="password" name="password" placeholder="Password" required>
                    </div>
                    <div class="field">
                        <input type="password" name="confirm_password" placeholder="Confirm password" required>
                    </div>
                    <div class="field btn">
                        <div class="btn-layer"></div>
                        <input type="submit" name="signup" value="Signup">
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Form elements selection
            const loginForm = document.querySelector('form.login');
            const signupForm = document.querySelector('form.signup');
            const loginInputs = loginForm.querySelectorAll('input[type="email"], input[type="password"]');
            const signupInputs = signupForm.querySelectorAll('input[type="text"], input[type="email"], input[type="password"], input[type="date"], select');
            
            const signupEmailInput = document.querySelector('.signup input[name="email"]');
            const specialityField = document.querySelector('.speciality-field');
            const specialitySelect = document.querySelector('.speciality-field select');
            const dateNaissanceField = document.querySelector('.date-naissance-field');
            const dateNaissanceInput = document.querySelector('.date-naissance-field input[name="date_naissance"]');

            // Form switching functionality
            const formInner = document.querySelector('.form-inner');
            const loginRadio = document.getElementById('login');
            const signupRadio = document.getElementById('signup');
            
            // Function to switch form view
            function switchForm(isSignup) {
                if (isSignup) {
                    formInner.style.transform = 'translateX(-50%)';
                    signupRadio.checked = true;
                } else {
                    formInner.style.transform = 'translateX(0%)';
                    loginRadio.checked = true;
                }
                // Clear errors when switching forms
                loginInputs.forEach(clearError);
                signupInputs.forEach(clearError);
            }

            document.querySelector('.signup-link a').addEventListener('click', function(e) {
                e.preventDefault();
                switchForm(true);
            });

            document.querySelector('.pass-link a').addEventListener('click', function(e) {
                e.preventDefault();
                alert('Forgot password functionality will be implemented soon.');
            });

            // Set initial state
            if (signupRadio.checked) {
                formInner.style.transform = 'translateX(-50%)';
            } else {
                formInner.style.transform = 'translateX(0%)';
            }

            loginRadio.addEventListener('change', function() {
                if (this.checked) {
                    switchForm(false);
                }
            });

            signupRadio.addEventListener('change', function() {
                if (this.checked) {
                    switchForm(true);
                }
            });

            // Field validation functions
            function validateField(input) {
                const value = input.value.trim();
                let isValid = true;
                let errorMessage = '';
                const type = input.type;
                const name = input.name;

                // Handle required attribute validation first
                if (input.hasAttribute('required') && value === '') {
                    isValid = false;
                    errorMessage = 'This field is required';
                    return { isValid, errorMessage };
                }

                switch (type) {
                    case 'email':
                        if (value !== '') {
                            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                            isValid = emailRegex.test(value);
                            errorMessage = 'Please enter a valid email address';
                        }
                        break;
                    case 'password':
                        if (value !== '') {
                            isValid = value.length >= 6;
                            errorMessage = 'Password must be at least 6 characters long';
                        }
                        break;
                    case 'date':
                        if (value !== '') {
                            // Use the browser's built-in validity check for date input
                            isValid = input.validity.valid;
                            errorMessage = 'Please enter a valid date (YYYY-MM-DD)'; // Keep standard message
                        }
                        break;
                    case 'select-one':
                        if (name === 'specialite') {
                            if (input.hasAttribute('required') && value === '') {
                                isValid = false;
                                errorMessage = 'Please select a speciality';
                            }
                        } else {
                            if (input.hasAttribute('required') && value === '') {
                                isValid = false;
                                errorMessage = 'Please make a selection';
                            }
                        }
                        break;
                    case 'text':
                        if (value === '') {
                            isValid = false;
                            errorMessage = 'Full name is required';
                        }
                        break;
                    default:
                        if (input.hasAttribute('required') && value === '') {
                            isValid = false;
                            errorMessage = 'This field is required';
                        }
                        break;
                }

                return { isValid, errorMessage };
            }

            function showError(input, message) {
                const field = input.parentElement;
                const existingError = field.querySelector('.error-message[data-input-name="' + input.name + '"]');
                if (existingError) {
                    existingError.remove();
                }

                const errorDiv = document.createElement('div');
                errorDiv.className = 'error-message';
                errorDiv.textContent = message;
                errorDiv.setAttribute('data-input-name', input.name);

                input.parentNode.insertBefore(errorDiv, input.nextSibling);

                input.style.borderColor = '#ff4444';
            }

            function clearError(input) {
                const field = input.parentElement;
                const errorDiv = field.querySelector('.error-message[data-input-name="' + input.name + '"]');
                if (errorDiv) {
                    errorDiv.remove();
                }
                input.style.borderColor = '';
            }

            // Speciality and Date of Birth field handling
            function toggleSpecialityField() {
                const email = signupEmailInput.value.trim();
                
                specialityField.style.display = 'none';
                specialitySelect.removeAttribute('required');
                clearError(specialitySelect);

                dateNaissanceField.style.display = 'none';
                dateNaissanceInput.removeAttribute('required');
                clearError(dateNaissanceInput);

                if (email.endsWith('@med.com')) {
                    specialityField.style.display = 'block';
                    if (specialitySelect.options.length > 1) {
                        specialitySelect.setAttribute('required', 'required');
                    }
                } else if (email.endsWith('@pat.com')) {
                    dateNaissanceField.style.display = 'block';
                    dateNaissanceInput.setAttribute('required', 'required');
                }
            }

            // Initialize and set up event listeners
            toggleSpecialityField();
            signupEmailInput.addEventListener('input', toggleSpecialityField);
            signupEmailInput.addEventListener('change', toggleSpecialityField);

            specialitySelect.addEventListener('change', function() {
                if (specialitySelect.value !== '') {
                    specialityField.style.display = 'none';
                }
            });

            // Form validation setup - Real-time and on blur
            function setupValidation(inputs) {
                inputs.forEach(input => {
                    input.addEventListener('input', function() {
                        clearError(input);
                    });

                    input.addEventListener('blur', function() {
                        const validation = validateField(input);
                        if (!validation.isValid) {
                            showError(input, validation.errorMessage);
                        } else {
                            clearError(input);
                        }
                    });
                });
            }

            // Apply validation to both forms
            setupValidation(loginInputs);
            setupValidation(signupInputs);

            // Form submission handlers - Prevent submission if validation fails
            loginForm.addEventListener('submit', function(e) {
                let isFormValid = true;

                loginInputs.forEach(input => {
                    const validation = validateField(input);
                    if (!validation.isValid) {
                        showError(input, validation.errorMessage);
                        isFormValid = false;
                    } else {
                        clearError(input);
                    }
                });

                if (!isFormValid) {
                    e.preventDefault();
                }
            });

            signupForm.addEventListener('submit', function(e) {
                let isFormValid = true;

                signupInputs.forEach(input => {
                    if (input.name === 'email') toggleSpecialityField();

                    const validation = validateField(input);
                    if (!validation.isValid) {
                        showError(input, validation.errorMessage);
                        isFormValid = false;
                    } else {
                        clearError(input);
                    }
                });

                const email = signupEmailInput.value.trim();
                if (email.endsWith('@med.com')) {
                    const specialityValidation = validateField(specialitySelect);
                    if (!specialityValidation.isValid) {
                        showError(specialitySelect, specialityValidation.errorMessage);
                        isFormValid = false;
                    } else {
                        clearError(specialitySelect);
                    }
                } else if (email.endsWith('@pat.com')) {
                    const dateValidation = validateField(dateNaissanceInput);
                    if (!dateValidation.isValid) {
                        showError(dateNaissanceInput, dateValidation.errorMessage);
                        isFormValid = false;
                    } else {
                        clearError(dateNaissanceInput);
                    }
                }

                const passwordInput = signupForm.querySelector('input[name="password"]');
                const confirmPasswordInput = signupForm.querySelector('input[name="confirm_password"]');
                if (passwordInput.value !== confirmPasswordInput.value) {
                    showError(confirmPasswordInput, 'Passwords do not match');
                    isFormValid = false;
                } else {
                    clearError(confirmPasswordInput);
                }

                if (!isFormValid) {
                    e.preventDefault();
                }
            });

            // Clear PHP session error/success messages on input focus
            const allInputs = document.querySelectorAll('input, select, textarea');
            allInputs.forEach(input => {
                input.addEventListener('focus', function() {
                    const errorMessageDiv = document.querySelector('.error-message:not([data-input-name])');
                    if (errorMessageDiv) {
                        errorMessageDiv.style.display = 'none';
                    }
                    const successMessageDiv = document.querySelector('.success-message');
                    if (successMessageDiv) {
                        successMessageDiv.style.display = 'none';
                    }
                });
            });
        });
    </script>
</body>
</html>