<?php
require_once '../../backend/auth/session_handler.php';
checkRole('medecin');

$doctor_name = htmlspecialchars($_SESSION['user']['nom']);
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
    <title>Doctor Dashboard</title>
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
                    <a href="doctor_dashboard.php">
                        <i class="fa-solid fa-cubes fa-fw"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="my_patients.php">
                        <i class="fa-solid fa-people-arrows fa-fw"></i>
                        <span>My Patients</span>
                    </a>
                </li>
                <li>
                    <a href="appointments.php">
                        <i class="fa-solid fa-calendar-check fa-fw"></i>
                        <span>Appointments</span>
                    </a>
                </li>
                <li>
                    <a href="prescriptions.php">
                        <i class="fa-solid fa-prescription fa-fw"></i>
                        <span>Prescriptions</span>
                    </a>
                </li>
                <li>
                    <a href="medical_records.php">
                        <i class="fa-solid fa-file-medical fa-fw"></i>
                        <span>Medical Records</span>
                    </a>
                </li>
                <li>
                    <a href="schedule.php">
                        <i class="fa-solid fa-clock fa-fw"></i>
                        <span>Schedule</span>
                    </a>
                </li>
                <li>
                    <a href="messages.php">
                        <i class="fa-solid fa-message fa-fw"></i>
                        <span>Messages</span>
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
                        <h1>Welcome Dr. <span id="doctor-name"><?php echo $doctor_name; ?></span></h1>
                        <span class="subtitle">Doctor Dashboard</span>
                    </div>
                </div>
                <div class="header-center">
                    <form action="" method="post" class="search-bar">
                        <input type="search" name="search_query" placeholder="Search patients or appointments">
                        <button type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
                    </form>
                </div>
                <div class="header-right">
                    <div class="profile-menu">
                        <img src="../images/avatar.jpg" alt="Profile" class="avatar">
                        <span class="profile-name">Dr. <?php echo $doctor_name; ?></span>
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
                    <div class="patients m-d">
                        <i class="fa-solid fa-bed-pulse"></i>
                        <div class="stat">
                            <p>Today's Patients</p>
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
                    <div class="prescriptions m-d">
                        <i class="fa-solid fa-prescription fa-fw"></i>
                        <div class="stat">
                            <p>Prescriptions</p>
                            <p class="number">0</p>
                        </div>
                    </div>
                    <div class="messages m-d">
                        <i class="fa-solid fa-message fa-fw"></i>
                        <div class="stat">
                            <p>Messages</p>
                            <p class="number">0</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Today's Appointments Table -->
            <div class="illness-list">
                <table>
                    <thead>
                        <tr>
                            <td colspan="2">Today's Appointments</td>
                            <td id="search" colspan="3">
                                <form action="" method="post">
                                    <input type="search" name="search" placeholder="Search appointments">
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
                            <td>Patient Name</td>
                            <td>Time</td>
                            <td>Type</td>
                            <td>Status</td>
                            <td>Contact</td>
                            <td colspan="2">Actions</td>
                        </tr>
                        <!-- PHP will populate this section -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <script src="../index.js"></script>
</body>
</html>