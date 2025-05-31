<?php
require_once '../../backend/auth/session_handler.php';
checkRole('admin');

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
            margin-top: 30px;
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
                        <h1>Welcome Admin</h1>
                        <span class="subtitle">Hospital Management Dashboard</span>
                    </div>
                </div>
                <div class="header-center">
                    <form action="" method="post" class="search-bar">
                        <input type="search" name="search_query" placeholder="Search patients, doctors, or departments">
                        <button type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
                    </form>
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

            <!-- Recent Activities Table -->
            <div class="illness-list">
                <table>
                    <thead>
                        <tr>
                            <td colspan="2">Recent Activities</td>
                            <td id="search" colspan="3">
                                <form action="" method="post">
                                    <input type="search" name="search" placeholder="Search activities">
                                    <input class="b-s" type="submit" value="Search">
                                </form>
                            </td>
                            <td id="logo" colspan="2">
                                <img src="../images/download__15_-removebg-preview.png" alt="">
                            </td>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Activity</td>
                            <td>User</td>
                            <td>Date</td>
                            <td>Time</td>
                            <td>Status</td>
                            <td colspan="2">Details</td>
                        </tr>
                        <?php if (isset($recentActivities) && !empty($recentActivities)): ?>
                            <?php foreach ($recentActivities as $activity): ?>
                                <tr>
                                    <td>Appointment</td>
                                    <td><?php echo htmlspecialchars($activity['patient_name']); ?></td>
                                    <td><?php echo date('Y-m-d', strtotime($activity['date_rendezvous'])); ?></td>
                                    <td><?php echo date('H:i', strtotime($activity['date_rendezvous'])); ?></td>
                                    <td><?php echo htmlspecialchars($activity['statut']); ?></td>
                                    <td colspan="2">
                                        <a href="rendezvous.php?id=<?php echo $activity['patient_id']; ?>" class="view-btn">View Details</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7">No recent activities found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
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