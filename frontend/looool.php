<?php
session_start();
require_once '../backend/config/database.php';

$error = '';
$success = '';

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

        $stmt = $pdo->prepare("SELECT * FROM utilisateur WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['mot_de_passe'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            
            // Redirect based on role
            switch($user['role']) {
                case 'admin':
                    header('Location: admin/dashboard.php');
                    break;
                case 'medecin':
                    header('Location: medecin/dashboard.php');
                    break;
                case 'patient':
                    header('Location: patient/dashboard.php');
                    break;
            }
            exit();
        } else {
            $error = "Invalid email or password";
        }
    } elseif (isset($_POST['signup'])) {
        $email = $_POST['email'];
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $nom = $_POST['nom'];

        // Validate email format and determine role
        $emailValidation = validateEmail($email);
        if (!$emailValidation['valid']) {
            $error = "Invalid email format. Please use @admin.com for admin, @med.com for doctor, or @pat.com for patient";
        } else {
            $role = $emailValidation['role'];

            if ($password !== $confirm_password) {
                $error = "Passwords do not match";
            } else {
                // Check if email already exists
                $stmt = $pdo->prepare("SELECT id FROM utilisateur WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->rowCount() > 0) {
                    $error = "Email already exists";
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
                                $stmt = $pdo->prepare("INSERT INTO medecin (id) VALUES (?)");
                                break;
                            case 'patient':
                                $stmt = $pdo->prepare("INSERT INTO patient (id) VALUES (?)");
                                break;
                        }
                        $stmt->execute([$user_id]);
                        
                        $success = "Registration successful! Please login.";
                    } catch(PDOException $e) {
                        $error = "Registration failed: " . $e->getMessage();
                    }
                }
            }
        }
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
        }
        
        .form-container .form-inner form {
            width: 50%;
            transition: all 0.6s cubic-bezier(0.68,-0.55,0.265,1.55);
        }
        
        .form-inner form .field {
            height: 50px;
            width: 100%;
            margin-top: 20px;
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

        .error-message {
            color: #ff0000;
            text-align: center;
            margin: 10px 0;
            background: rgba(255, 255, 255, 0.2);
            padding: 10px;
            border-radius: 5px;
            backdrop-filter: blur(5px);
        }

        .success-message {
            color: #008000;
            text-align: center;
            margin: 10px 0;
            background: rgba(255, 255, 255, 0.2);
            padding: 10px;
            border-radius: 5px;
            backdrop-filter: blur(5px);
        }

        .email-hint {
            color: #666;
            font-size: 12px;
            margin-top: 5px;
            text-align: left;
            background: rgba(255, 255, 255, 0.2);
            padding: 5px;
            border-radius: 5px;
            backdrop-filter: blur(5px);
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
                        <div class="email-hint">
                            Use one of these email formats:<br>
                            - @admin.com for admin<br>
                            - @med.com for doctor<br>
                            - @pat.com for patient
                        </div>
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
        // Add JavaScript to handle form switching
        document.querySelector('.signup-link a').addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('signup').checked = true;
        });
    </script>
</body>
</html> 