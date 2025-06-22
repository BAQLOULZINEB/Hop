<?php
require_once '../../backend/auth/session_handler.php';
checkRole('patient');

// Fetch user info from session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header('Location: ../../frontend/Authentification.php');
    exit();
}

// Get user data - handle both session formats
$currentUser = null;
$patient_name = '';
$patient_id = null;

if (isset($_SESSION['user'])) {
    // If user data is stored in session
    $currentUser = $_SESSION['user'];
    $patient_name = htmlspecialchars($currentUser['nom']);
    $patient_id = $currentUser['id'];
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
            $patient_name = htmlspecialchars($currentUser['nom']);
            $patient_id = $currentUser['id'];
        }
    } catch(PDOException $e) {
        // Handle database error
        $error = 'Database error: ' . $e->getMessage();
    }
}

// If we still don't have user data, redirect to login
if (!$currentUser || !$patient_id) {
    header('Location: ../../frontend/Authentification.php');
    exit();
}

// Database connection
try {
    $db = new PDO("mysql:host=localhost;dbname=medical_system", "root", "");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->exec("SET NAMES utf8mb4");
    
    // Get total appointments count
    $query = "SELECT COUNT(*) as total FROM rendez_vous WHERE patient_id = :patient_id";
    $stmt = $db->prepare($query);
    $stmt->execute([':patient_id' => $patient_id]);
    $totalAppointments = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Get upcoming appointments
    $query = "SELECT r.*, u.nom as doctor_name, m.specialite 
              FROM rendez_vous r 
              JOIN medecin m ON r.medecin_id = m.id 
              JOIN utilisateur u ON m.id = u.id 
              WHERE r.patient_id = :patient_id 
              AND r.date_rendezvous >= CURDATE()
              ORDER BY r.date_rendezvous ASC";
    $stmt = $db->prepare($query);
    $stmt->execute([':patient_id' => $patient_id]);
    $upcomingAppointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get today's appointments
    $query = "SELECT COUNT(*) as total FROM rendez_vous 
              WHERE patient_id = :patient_id 
              AND DATE(date_rendezvous) = CURDATE()";
    $stmt = $db->prepare($query);
    $stmt->execute([':patient_id' => $patient_id]);
    $todayAppointments = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    
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
    <title>Patient Dashboard</title>
    <style>
        .profile-dropdown {
            display: none !important;
        }
      
       
      
        .cancel-btn {
            padding: 4px 8px;
            background-color: #e74c3c;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9em;
        }
        .cancel-btn:hover {
            background-color: #c0392b;
        }
        /* Add table text color styles */
        .illness-list table {
            background: #fff;
            color: #222;
            border-radius: 10px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        .illness-list table thead td {
            background: #f2f2f2;
            color: #0e2f44;
            font-weight: bold;
            font-size: 1.08em;
            border-bottom: 2px solid #e0e0e0;
        }
        .illness-list table tbody td {
            color: #222;
            background: #fff;
            font-size: 1.04em;
        }
        .illness-list table input[type="search"] {
            color: #222;
            background: #fff;
            border: 1px solid #bbb;
            border-radius: 6px;
            padding: 6px 12px;
        }
        .illness-list table .b-s {
            color: #fff;
            background: #0e2f44;
            border: none;
            border-radius: 6px;
            padding: 6px 18px;
            font-weight: bold;
            margin-left: 8px;
        }
        .illness-list table .b-s:hover {
            background: #1a5276;
        }
        .illness-list {
            margin-top: 30px;
        }
        .illness-list .table-logo {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 10px;
        }
        .illness-list .table-logo img {
            height: 38px;
            width: auto;
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
                    <a href="patient_dashboard.php">
                        <i class="fa-solid fa-cubes fa-fw"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
               
                <li>
                    <a href="book_appointment.php">
                        <i class="fa-solid fa-calendar-plus fa-fw"></i>
                        <span>Book Appointment</span>
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
                        <h1>Welcome <span id="patient-name"><?php echo $patient_name; ?></span></h1>
                        <span class="subtitle">Patient Dashboard</span>
                    </div>
                </div>
               
                <div class="header-right">
                    <div class="profile-menu">
                        <img src="../images/avatar.jpg" alt="Profile" class="avatar">
                        <span class="profile-name"><?php echo $patient_name; ?></span>
                        <i class="fa-solid fa-chevron-down"></i>
                       
                    </div>
                </div>
            </div>
            
            <div class="dashboard-container" style="display: flex; gap: 24px; padding: 24px;">
                <!-- User Profile Info -->
                <?php if ($currentUser): ?>
                <div class="profile-info" style="flex: 0 0 300px; background: rgba(255,255,255,0.08); border-radius: 16px; padding: 24px; box-shadow: 0 2px 12px rgba(0,0,0,0.07); height: fit-content;">
                    <img src="../images/avatar.jpg" alt="Avatar" style="width: 70px; height: 70px; border-radius: 50%; object-fit: cover; border: 2px solid #fff; margin-bottom: 16px;">
                    <div>
                        <div style="font-size: 1.3em; font-weight: bold; color: #fff;">Name: <?php echo htmlspecialchars($currentUser['nom'] ?? ''); ?></div>
                        <div style="color: #fff; margin-top: 8px;">Email: <?php echo htmlspecialchars($currentUser['email'] ?? ''); ?></div>
                        <div style="color: #fff; margin-top: 8px;">Role: <?php echo htmlspecialchars($currentUser['role'] ?? ''); ?></div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Main Content Container -->
                <div class="main-content" style="flex: 1;">
                    <!-- Dashboard Statistics -->
                    <div class="details">
                        <div class="main-details">
                            <div class="appointments m-d">
                                <i class="fa-solid fa-calendar-check fa-fw"></i>
                                <div class="stat">
                                    <p>Today's Appointments</p>
                                    <p class="number"><?php echo isset($todayAppointments) ? $todayAppointments : '0'; ?></p>
                                </div>
                            </div>
                            <div class="total-appointments m-d">
                                <i class="fa-solid fa-calendar fa-fw"></i>
                                <div class="stat">
                                    <p>Total Appointments</p>
                                    <p class="number"><?php echo isset($totalAppointments) ? $totalAppointments : '0'; ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Upcoming Appointments Table -->
                    <div class="illness-list">
                        <div class="table-logo">
                            <img src="../images/download__15_-removebg-preview.png" alt="HopCare Logo">
                        </div>
                        <table>
                            <thead>
                                <tr>
                                    <td colspan="2">Upcoming Appointments</td>
                                </tr>
                                <tr>
                                    <td>Doctor Name</td>
                                    <td>Date & Time</td>
                                    <td>Department</td>
                                </tr>
                            </thead>
                            <tbody>
                                
                                <?php if (!empty($upcomingAppointments)): ?>
                                    <?php foreach ($upcomingAppointments as $appointment): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($appointment['doctor_name']); ?></td>
                                            <td><?php echo date('d M Y H:i', strtotime($appointment['date_rendezvous'])); ?></td>
                                            <td><?php echo htmlspecialchars($appointment['specialite']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" style="text-align: center;">No upcoming appointments</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="../index.js"></script>
    <script>
    function cancelAppointment(appointmentId) {
        if (confirm('Are you sure you want to cancel this appointment?')) {
            fetch('cancel_appointment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'appointment_id=' + appointmentId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error canceling appointment: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error canceling appointment');
            });
        }
    }
    </script>
</body>
</html>