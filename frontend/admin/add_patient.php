<?php
require_once '../../backend/auth/session_handler.php';
checkRole('admin');

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db = new PDO("mysql:host=localhost;dbname=medical_system", "root", "");
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Start transaction
        $db->beginTransaction();
        
        // Insert into utilisateur table first
        $query = "INSERT INTO utilisateur (nom, email, mot_de_passe, role) VALUES (:nom, :email, :password, 'patient')";
        $stmt = $db->prepare($query);
        $stmt->execute([
            ':nom' => $_POST['nom'],
            ':email' => $_POST['email'],
            ':password' => password_hash($_POST['password'], PASSWORD_DEFAULT)
        ]);
        
        $user_id = $db->lastInsertId();
        
        // Insert into patient table
        $query = "INSERT INTO patient (id, date_naissance) 
                 VALUES (:id, :date_naissance)";
        $stmt = $db->prepare($query);
        $stmt->execute([
            ':id' => $user_id,
            ':date_naissance' => $_POST['date_naissance']
        ]);
        
        // Commit transaction
        $db->commit();
        
        $success = "Patient added successfully!";
        
    } catch(PDOException $e) {
        // Rollback transaction on error
        $db->rollBack();
        $error = 'Error adding patient: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html dir="ltr" lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preload" href="../images/background_page.jpg" as="image">
    <title>Admin - Add Patient</title>
    <link rel="stylesheet" href="../css_files/master.css">
    <link rel="stylesheet" href="../css_files/all.min.css">
    <style>
        /* Form only, ne touche pas à la structure globale du dashboard/header */
        .add-patient .forms input {
            border: 1px solid black;
            height: 30px;
        }
        .add-patient .forms form div {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        .add-patient .forms form div label {
            font-weight: bold;
            color: white;
            margin-bottom: 0;
            width: 180px;
            transition: color 0.3s ease;
            margin-right: 15px;
            text-align: left;
            flex-shrink: 0;
        }
        .add-patient .forms form div input[type="text"],
        .add-patient .forms form div input[type="email"],
        .add-patient .forms form div input[type="password"],
        .add-patient .forms form div input[type="date"] {
            flex-grow: 1;
            padding: 8px 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
            margin-bottom: 0;
            font-size: 16px;
            background-color: rgba(255, 255, 255, 0.8);
            color: #333;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
            height: 35px;
            line-height: 1.5;
        }
        .add-patient .forms form div input[type="text"]:focus,
        .add-patient .forms form div input[type="email"]:focus,
        .add-patient .forms form div input[type="password"]:focus,
        .add-patient .forms form div input[type="date"]:focus {
            outline: none;
            border-color: #0e2f44;
            box-shadow: 0 0 5px rgba(14, 47, 68, 0.5);
        }
        .add-patient .forms form .save-button {
            display: flex;
            justify-content: center;
            margin-top: 30px;
        }
        .add-patient .forms form .save-button button {
            width: 150px;
            padding: 10px 20px;
            border: none;
            background-color: #0e2f44;
            color: white;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            font-size: 16px;
            text-transform: uppercase;
            font-weight: bold;
        }
        .add-patient .forms form .save-button button:hover {
            background-color: #1a5276;
        }
    </style>
</head>
<body style="background-image: url('../images/background_page.jpg'); background-color: rgba(12, 36, 54, 0.55); background-position: center; background-size: cover; background-repeat: no-repeat;">   
    <div class="page">
    <div class="dashboard">
            <div class="title">
                <img class="logo" src="../images/download__15__14-removebg-preview.png" alt="">
                <h2>HopCare</h2>
                <i class="fa-solid fa-bars toggle"></i>
            </div>
            <ul class="links">
                <li>
                    <a href="admin_dashboard.php">
                        <i class="fa-solid fa-cubes fa-fw"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="num num1">
                    <a class="listted" href="doctors.php">
                        <i class="fa-solid fa-user-nurse fa-fw"></i>
                        <span>Médecins</span>
                        <i class="fa-solid fa-angle-right tog"></i>
                    </a>
                    <div class="list one" style="display: none;">
                        <a href="doctors.php">Voir les médecins</a>
                        <a href="add_doctor.php">Ajouter un médecin</a>
                    </div>
                </li>
                <li class="num num2">
                    <a  href="departments.php">
                        <i class="fa-solid fa-people-group fa-fw"></i>
                        <span>Spécialités</span>  
                    </a>
                    
                </li>
                <li class="num num3">
                    <a class="listted" href="#">
                        <i class="fa-solid fa-people-arrows fa-fw"></i>
                        <span>Patients</span>
                        <i class="fa-solid fa-angle-right tog"></i>
                    </a>
                    <div class="list three" style="display: none;">
                        <a href="patients.php">Voir les patients</a>
                        <a href="add_patient.php">Ajouter un patient</a>
                    </div>
                </li>
                <li>
                    <a href="rendezvous.php">
                        <i class="fa-solid fa-calendar-check fa-fw"></i>
                        <span>Rendez-vous</span>
                    </a>
                </li>
               
                <li>
                    <a href="reports.php">
                        <i class="fa-solid fa-file-signature fa-fw"></i>
                        <span>Rapports</span>
                    </a>
                </li>
                 <li>
                    <a href="charts.php">
                        <i class="fa-regular fa-comments fa-fw"></i>
                        <span>Charts</span>
                    </a>
                </li>
                <li>
                    <a href="settings.php">
                        <i class="fa-solid fa-gear fa-fw"></i>
                        <span>Paramètres</span>
                    </a>
                </li>
            </ul>
            <form method="post" class="log-out">
                <button type="submit" name="logout">
                    <i class="fa-solid fa-arrow-right-from-bracket fa-fw"></i>
                    <span>Logout</span>
                </button>
            </form>
        </div>
        <div class="content">
            <div class="header pro-header">
                <div class="header-left">
                    <img src="../images/download__15__14-removebg-preview.png" alt="Logo" class="header-logo">
                    <div class="welcome">
                        <h1>Ajouter un patient</h1>
                        <span class="subtitle">Créer un nouveau compte patient</span>
                    </div>
                </div>
                <div class="header-right">
                    <div class="profile-menu">
                        <img src="../images/avatar.jpg" alt="Profile" class="avatar">
                        <span class="profile-name">Admin</span>
                        <i class="fa-solid fa-chevron-down"></i>
                        <div class="profile-dropdown">
                            <ul>
                                <li><a href="profile.php">My Profile</a></li>
                                <li><a href="settings.php">Settings</a></li>
                                <li>
                                    <form method="post">
                                        <button type="submit" name="logout">Logout</button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <div class="add-patient">
                <div class="forms">
                    <?php if ($error): ?>
                        <div class="error-message">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="success-message">
                            <?php echo htmlspecialchars($success); ?>
                        </div>
                    <?php endif; ?>
                    <form method="POST" action="">
                        <div>
                            <label for="nom">Nom complet</label>
                            <input type="text" id="nom" name="nom" required>
                        </div>
                        <div>
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" required>
                        </div>
                        <div>
                            <label for="password">Mot de passe</label>
                            <input type="password" id="password" name="password" required>
                        </div>
                        <div>
                            <label for="date_naissance">Date de naissance</label>
                            <input type="date" id="date_naissance" name="date_naissance" required>
                        </div>
                        <div class="save-button">
                            <button type="submit">
                                <i class="fa-solid fa-plus"></i> Ajouter le patient
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script src="../index.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const toggles = document.querySelectorAll(".listted");

            toggles.forEach(function(toggle) {
                toggle.addEventListener("click", function(e) {
                    e.preventDefault();
                    const submenu = toggle.nextElementSibling;

                    if (submenu) {
                        submenu.style.display = submenu.style.display === "block" ? "none" : "block";
                    }

                    toggles.forEach(function(otherToggle) {
                        if (otherToggle !== toggle) {
                            const otherSubmenu = otherToggle.nextElementSibling;
                            if (otherSubmenu) {
                                otherSubmenu.style.display = "none";
                            }
                        }
                    });
                });
            });
        });
    </script>
</body>
</html>