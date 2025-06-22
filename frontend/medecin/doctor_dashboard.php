<?php
require_once '../../backend/auth/session_handler.php';
checkRole('medecin');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get user data - handle both session formats
$currentUser = null;
$doctor_name = '';
$medecin_id = null;

if (isset($_SESSION['user'])) {
    // If user data is stored in session
    $currentUser = $_SESSION['user'];
    $doctor_name = htmlspecialchars($currentUser['nom']);
    $medecin_id = $currentUser['id'];
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
            $doctor_name = htmlspecialchars($currentUser['nom']);
            $medecin_id = $currentUser['id'];
        }
    } catch(PDOException $e) {
        // Handle database error
        $error = 'Database error: ' . $e->getMessage();
    }
}

// If we still don't have user data, redirect to login
if (!$currentUser || !$medecin_id) {
    header('Location: ../../frontend/Authentification.php');
    exit();
}

if (isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header('Location: ../../frontend/Authentification.php');
    exit();
}

// Fetch statistics and appointments data
$stats = [
    'today_patients' => 0,
    'total_appointments' => 0
];
$todayAppointments = [];

try {
    $db = new PDO("mysql:host=localhost;dbname=medical_system", "root", "");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->exec("SET NAMES utf8mb4");
    
    // Get today's patients count (unique patients with appointments today)
    $query = "SELECT COUNT(DISTINCT r.patient_id) as today_patients
              FROM rendez_vous r
              WHERE r.medecin_id = :medecin_id 
              AND DATE(r.date_rendezvous) = CURDATE()
              AND r.statut = 'confirmé'";
    $stmt = $db->prepare($query);
    $stmt->execute([':medecin_id' => $medecin_id]);
    $stats['today_patients'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['today_patients'];
    
    // Get total appointments count for this doctor
    $query = "SELECT COUNT(*) as total_appointments
              FROM rendez_vous r
              WHERE r.medecin_id = :medecin_id 
              AND r.statut = 'confirmé'";
    $stmt = $db->prepare($query);
    $stmt->execute([':medecin_id' => $medecin_id]);
    $stats['total_appointments'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total_appointments'];
    
    // Get today's appointments for the table
    $query = "SELECT 
                u.id as patient_id,
                u.nom as patient_name,
                u.email as patient_email,
                r.date_rendezvous,
                r.statut,
                'Consultation' as appointment_type
              FROM rendez_vous r
              JOIN patient p ON r.patient_id = p.id
              JOIN utilisateur u ON p.id = u.id
              WHERE r.medecin_id = :medecin_id 
              AND DATE(r.date_rendezvous) = CURDATE()
              AND r.statut = 'confirmé'
              ORDER BY r.date_rendezvous ASC";
    $stmt = $db->prepare($query);
    $stmt->execute([':medecin_id' => $medecin_id]);
    $todayAppointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
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
    <title>Doctor Dashboard</title>
    <style>
        .profile-dropdown {
            display: none !important;
        }
        .stat .number {
            font-size: 2.5em;
            font-weight: bold;
            color: #ffffff;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
            margin-top: 10px;
        }
        .patients { background: linear-gradient(135deg, rgba(255, 255, 255, 0.1), rgba(255, 255, 255, 0.2)); }
        .appointments { background: linear-gradient(135deg, rgba(255, 255, 255, 0.1), rgba(255, 255, 255, 0.2)); }
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
        }
        .btn-primary {
            background: #3498db;
            color: white;
        }
        .btn-primary:hover {
            background: #2980b9;
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
                    <div class="patients m-d">
                        <i class="fa-solid fa-bed-pulse"></i>
                        <div class="stat">
                            <p>Today's Patients</p>
                            <p class="number"><?php echo $stats['today_patients']; ?></p>
                        </div>
                    </div>
                    <div class="appointments m-d">
                        <i class="fa-solid fa-calendar-check fa-fw"></i>
                        <div class="stat">
                            <p>Appointments</p>
                            <p class="number"><?php echo $stats['total_appointments']; ?></p>
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
                        <?php if (!empty($todayAppointments)): ?>
                            <?php foreach ($todayAppointments as $appointment): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($appointment['patient_name']); ?></td>
                                    <td><?php echo date('H:i', strtotime($appointment['date_rendezvous'])); ?></td>
                                    <td><?php echo htmlspecialchars($appointment['appointment_type']); ?></td>
                                    <td><?php echo htmlspecialchars($appointment['statut']); ?></td>
                                    <td><?php echo htmlspecialchars($appointment['patient_email']); ?></td>
                                    <td colspan="2">
                                        <a href="medical_records.php?patient_id=<?php echo $appointment['patient_id'] ?? ''; ?>" class="btn btn-primary">View Records</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align: center;">No appointments scheduled for today</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <script src="../index.js"></script>
    <script>
        // Function to refresh statistics
        function refreshStats() {
            fetch('api/get_doctor_stats.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update statistics
                        document.querySelector('.patients .number').textContent = data.stats.today_patients;
                        document.querySelector('.appointments .number').textContent = data.stats.total_appointments;
                        
                        // Update appointments table
                        updateAppointmentsTable(data.today_appointments);
                    } else {
                        console.error('Error fetching stats:', data.error);
                    }
                })
                .catch(error => {
                    console.error('Error refreshing stats:', error);
                });
        }
        
        // Function to update appointments table
        function updateAppointmentsTable(appointments) {
            const tbody = document.querySelector('.illness-list tbody');
            const headerRow = tbody.querySelector('tr:first-child');
            
            // Clear existing appointment rows (keep header)
            const existingRows = tbody.querySelectorAll('tr:not(:first-child)');
            existingRows.forEach(row => row.remove());
            
            if (appointments.length === 0) {
                const noAppointmentsRow = document.createElement('tr');
                noAppointmentsRow.innerHTML = '<td colspan="7" style="text-align: center;">No appointments scheduled for today</td>';
                tbody.appendChild(noAppointmentsRow);
            } else {
                appointments.forEach(appointment => {
                    const row = document.createElement('tr');
                    const time = new Date(appointment.date_rendezvous).toLocaleTimeString('en-US', { 
                        hour: '2-digit', 
                        minute: '2-digit',
                        hour12: false 
                    });
                    
                    row.innerHTML = `
                        <td>${appointment.patient_name}</td>
                        <td>${time}</td>
                        <td>${appointment.appointment_type}</td>
                        <td>${appointment.statut}</td>
                        <td>${appointment.patient_email}</td>
                        <td colspan="2">
                            <a href="medical_records.php?patient_id=${appointment.patient_id}" class="btn btn-primary">View Records</a>
                        </td>
                    `;
                    tbody.appendChild(row);
                });
            }
        }
        
        // Refresh stats every 30 seconds
        setInterval(refreshStats, 30000);
        
        // Initial refresh after page load
        document.addEventListener('DOMContentLoaded', function() {
            // Refresh stats after 5 seconds to ensure page is fully loaded
            setTimeout(refreshStats, 5000);
        });
    </script>
</body>
</html>