<?php
require_once '../../backend/auth/session_handler.php';
checkRole('admin');
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
                    <a class="listted" href="departments.php">
                        <i class="fa-solid fa-people-group fa-fw"></i>
                        <span>Spécialités</span>
                        <i class="fa-solid fa-angle-right tog"></i>
                    </a>
                    <div class="list two" style="display: none;">
                        <a href="departments.php">Voir les spécialités</a>
                        <a href="add_department.php">Ajouter une spécialité</a>
                    </div>
                </li>
                 <li>
                    <a href="patients.php">
                        <i class="fa-solid fa-people-arrows fa-fw"></i>
                        <span>Patients</span>
                    </a>
                </li>
                <li>
                    <a href="appointments.php">
                        <i class="fa-solid fa-calendar-check fa-fw"></i>
                        <span>Rendez-vous</span>
                    </a>
                </li>
                <li>
                    <a href="pharmacy.php">
                        <i class="fa-solid fa-hand-holding-medical fa-fw"></i>
                        <span>Pharmacie</span>
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
                            <p class="number">0</p>
                        </div>
                    </div>
                    <div class="doctors m-d">
                        <i class="fa-solid fa-user-nurse fa-fw"></i>
                        <div class="stat">
                            <p>Total Doctors</p>
                            <p class="number">0</p>
                        </div>
                    </div>
                    <div class="departments m-d">
                        <i class="fa-solid fa-people-group fa-fw"></i>
                        <div class="stat">
                            <p>Departments</p>
                            <p class="number">0</p>
                        </div>
                    </div>
                    <div class="appointments m-d">
                        <i class="fa-solid fa-calendar-check fa-fw"></i>
                        <div class="stat">
                            <p>Appointments</p>
                            <p class="number">0</p>
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
                        <!-- PHP will populate this section -->
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