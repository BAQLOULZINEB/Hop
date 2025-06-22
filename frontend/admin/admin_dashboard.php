<?php
require_once '../../backend/auth/session_handler.php';
checkRole('admin');

// Fetch user info from session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get user data - handle both session formats
$currentUser = null;
$admin_name = '';
$admin_id = null;

if (isset($_SESSION['user'])) {
    // If user data is stored in session
    $currentUser = $_SESSION['user'];
    $admin_name = htmlspecialchars($currentUser['nom']);
    $admin_id = $currentUser['id'];
} elseif (isset($_SESSION['user_id'])) {
    // If only user_id is stored, fetch user data from database
    try {
        $db = new PDO("mysql:host=localhost;dbname=medical_system", "root", "");
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->exec("SET NAMES utf8mb4");
        
        $stmt = $db->prepare("SELECT * FROM utilisateur WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $currentUser = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($currentUser) {
            $admin_name = htmlspecialchars($currentUser['nom']);
            $admin_id = $currentUser['id'];
        }
    } catch(PDOException $e) {
        // Handle database error
        $error = 'Database error: ' . $e->getMessage();
    }
}

// If we still don't have user data, redirect to login
if (!$currentUser || !$admin_id) {
    header('Location: ../../frontend/Authentification.php');
    exit();
}

if (isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header('Location: ../../frontend/Authentification.php');
    exit();
}

// Database connection
try {
    $db = new PDO("mysql:host=localhost;dbname=medical_system", "root", "");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get total patients count
    $query = "SELECT COUNT(*) as total FROM patient";
    $stmt = $db->query($query);
    $totalPatients = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Get total doctors count
    $query = "SELECT COUNT(*) as total FROM medecin";
    $stmt = $db->query($query);
    $totalDoctors = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Get total departments count (specialties)
    $query = "SELECT COUNT(DISTINCT specialite) as total FROM medecin";
    $stmt = $db->query($query);
    $totalDepartments = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Get total appointments count
    $query = "SELECT COUNT(*) as total FROM rendez_vous";
    $stmt = $db->query($query);
    $totalAppointments = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Get recent activities (last 5 appointments)
    $query = "SELECT rv.*, u.nom as patient_name, m.nom as doctor_name 
              FROM rendez_vous rv 
              JOIN utilisateur u ON rv.patient_id = u.id 
              JOIN medecin m ON rv.medecin_id = m.id 
              ORDER BY rv.date_rendezvous DESC 
              LIMIT 5";
    $stmt = $db->query($query);
    $recentActivities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    // Handle database error
    $error = 'Database error: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html dir="ltr" lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- main css file -->
    <link rel="preload" href="../images/background_page.jpg" as="image">
    <link rel="stylesheet" href="../css_files/master.css">
    <!-- font awesome -->
    <link rel="stylesheet" href="../css_files/all.min.css">
    <!-- fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Arabic:wght@100..900&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <title>Admin Dashboard</title>
    <style>
        .details {
            margin-top: 10px;
            display: flex;
            justify-content: center;
           
           
            padding: 40px;
        }
        .main-details {
            display: flex;
            justify-content: center;
            gap: 40px;
            flex-wrap: wrap;
            max-width: 1400px;
            margin: 0 auto;
        }
        .m-d {
            background: rgba(255, 255, 255, 0.15);
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
            min-width: 300px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .m-d:hover {
            transform: translateY(-10px);
            background: rgba(255, 255, 255, 0.25);
        }
        .m-d i {
            font-size: 3.5em;
            margin-bottom: 20px;
            color: #ffffff;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }
        .stat p {
            margin: 8px 0;
            color: #ffffff;
            font-size: 1.2em;
            text-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }
        .stat .number {
            font-size: 2.5em;
            font-weight: bold;
            color: #ffffff;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
            margin-top: 10px;
        }
        .illnes { background: linear-gradient(135deg, rgba(255, 255, 255, 0.1), rgba(255, 255, 255, 0.2)); }
        .doctors { background: linear-gradient(135deg, rgba(255, 255, 255, 0.1), rgba(255, 255, 255, 0.2)); }
        .departments { background: linear-gradient(135deg, rgba(255, 255, 255, 0.1), rgba(255, 255, 255, 0.2)); }
        .appointments { background: linear-gradient(135deg, rgba(255, 255, 255, 0.1), rgba(255, 255, 255, 0.2)); }
        .profile-dropdown {
            display: none !important;
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
                    <a href="patient_mesures.php">
                        <i class="fa-regular fa-comments fa-fw"></i>
                        <span>Charts</span>
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
                        <h1>Welcome Admin</h1>
                        <span class="subtitle">Hospital Management Dashboard</span>
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
            
            <!-- User Profile Info -->
            <?php if ($currentUser): ?>
            <div class="profile-info" style="display: flex; align-items: center; gap: 24px; background: rgba(255,255,255,0.08); border-radius: 16px; padding: 24px 32px; margin: 24px 0; box-shadow: 0 2px 12px rgba(0,0,0,0.07); max-width: 500px;">
                <img src="../images/avatar.jpg" alt="Avatar" style="width: 70px; height: 70px; border-radius: 50%; object-fit: cover; border: 2px solid #fff;">
                <div>
                    <div style="font-size: 1.3em; font-weight: bold; color: #fff;">Name: <?php echo htmlspecialchars($currentUser['nom'] ?? ''); ?></div>
                    <div style="color: #fff; margin-top: 4px;">Email: <?php echo htmlspecialchars($currentUser['email'] ?? ''); ?></div>
                    <div style="color: #fff; margin-top: 4px;">Role: <?php echo htmlspecialchars($currentUser['role'] ?? ''); ?></div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Dashboard Statistics -->
            <div class="details">
                <div class="main-details">
                    <div class="illnes m-d">
                        <i class="fa-solid fa-bed-pulse"></i>
                        <div class="stat">
                            <p>Total Patients</p>
                            <p class="number"><?php echo isset($totalPatients) ? $totalPatients : '0'; ?></p>
                        </div>
                    </div>
                    <div class="doctors m-d">
                        <i class="fa-solid fa-user-nurse fa-fw"></i>
                        <div class="stat">
                            <p>Total Doctors</p>
                            <p class="number"><?php echo isset($totalDoctors) ? $totalDoctors : '0'; ?></p>
                        </div>
                    </div>
                    <div class="departments m-d">
                        <i class="fa-solid fa-people-group fa-fw"></i>
                        <div class="stat">
                            <p>Departments</p>
                            <p class="number"><?php echo isset($totalDepartments) ? $totalDepartments : '0'; ?></p>
                        </div>
                    </div>
                    <div class="appointments m-d">
                        <i class="fa-solid fa-calendar-check fa-fw"></i>
                        <div class="stat">
                            <p>Appointments</p>
                            <p class="number"><?php echo isset($totalAppointments) ? $totalAppointments : '0'; ?></p>
                        </div>
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