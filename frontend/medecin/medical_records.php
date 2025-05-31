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
            

            <div class="illness-list">
                <h2>Dossiers Médicaux</h2>
                <div class="file-cabinet">
                <?php
                try {
                    $db = new PDO("mysql:host=localhost;dbname=medical_system", "root", "");
                    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    $db->exec("SET NAMES utf8mb4");
                    $medecin_id = $_SESSION['user']['id'];
                    
                    // Get all patients who have had consultations with this doctor
                    $sql = "SELECT DISTINCT 
                            u.id as patient_id,
                            u.nom,
                            u.email,
                            p.date_naissance
                           FROM consultation c 
                           JOIN patient p ON c.patient_id = p.id
                           JOIN utilisateur u ON p.id = u.id
                           WHERE c.medecin_id = :medecin_id
                           ORDER BY u.nom";
                    
                    $stmt = $db->prepare($sql);
                    $stmt->execute([':medecin_id' => $medecin_id]);
                    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    if (empty($patients)) {
                        echo '<p>Aucun dossier médical trouvé.</p>';
                    } else {
                        foreach ($patients as $patient) {
                            echo '<div class="drawer">';
                            echo '<div class="drawer-front" onclick="toggleRecords(' . $patient['patient_id'] . ')">';
                            echo '<div class="drawer-label">';
                            echo '<h3>' . htmlspecialchars($patient['nom']) . '</h3>';
                            echo '<div class="patient-info">';
                            echo '<span><i class="fa-solid fa-envelope"></i> ' . htmlspecialchars($patient['email']) . '</span>';
                            echo '<span><i class="fa-solid fa-calendar"></i> ' . htmlspecialchars($patient['date_naissance']) . '</span>';
                            echo '</div>';
                            echo '</div>';
                            echo '<i class="fa-solid fa-chevron-down drawer-icon"></i>';
                            echo '</div>';

                            echo '<div id="records-' . $patient['patient_id'] . '" class="drawer-content">';
                            
                            // Get consultations with remarks
                            $sql = "SELECT date_consultation, remarques 
                                   FROM consultation 
                                   WHERE patient_id = :patient_id AND medecin_id = :medecin_id 
                                   ORDER BY date_consultation DESC";
                            $stmt = $db->prepare($sql);
                            $stmt->execute([':patient_id' => $patient['patient_id'], ':medecin_id' => $medecin_id]);
                            $consultations = $stmt->fetchAll(PDO::FETCH_ASSOC);

                            // Display medical history
                            echo '<div class="medical-history">';
                            
                            // Display consultations with remarks
                            echo '<div class="section">';
                            echo '<h4>Historique des Consultations</h4>';
                            if (empty($consultations)) {
                                echo '<div class="record-item">Aucune consultation enregistrée</div>';
                            } else {
                                foreach ($consultations as $consult) {
                                    echo '<div class="record-item">';
                                    echo '<div class="consultation-header">';
                                    echo '<span class="date">' . date('d/m/Y H:i', strtotime($consult['date_consultation'])) . '</span>';
                                    echo '</div>';
                                    if (!empty($consult['remarques'])) {
                                        echo '<div class="consultation-details">';
                                        echo '<p class="remarks">' . nl2br(htmlspecialchars($consult['remarques'])) . '</p>';
                                        echo '</div>';
                                    }
                                    echo '</div>';
                                }
                            }
                            echo '</div>';

                            // Get measurements
                            $sql = "SELECT temperature, pulsation, date_mesure 
                                   FROM mesure 
                                   WHERE patient_id = :patient_id 
                                   ORDER BY date_mesure DESC";
                            $stmt = $db->prepare($sql);
                            $stmt->execute([':patient_id' => $patient['patient_id']]);
                            $measurements = $stmt->fetchAll(PDO::FETCH_ASSOC);

                            // Display measurements
                            echo '<div class="section">';
                            echo '<h4>Mesures Vitales</h4>';
                            if (empty($measurements)) {
                                echo '<div class="record-item">Aucune mesure enregistrée</div>';
                            } else {
                                foreach ($measurements as $measure) {
                                    echo '<div class="record-item">';
                                    echo '<span class="date">' . date('d/m/Y H:i', strtotime($measure['date_mesure'])) . '</span>';
                                    echo '<div class="measure-details">';
                                    echo '<span>Température: ' . htmlspecialchars($measure['temperature']) . '°C</span>';
                                    echo '<span>Pulsation: ' . htmlspecialchars($measure['pulsation']) . ' bpm</span>';
                                    echo '</div>';
                                    echo '</div>';
                                }
                            }
                            echo '</div>';

                            echo '</div>'; // End medical-history
                            echo '</div>'; // End records-content
                            echo '</div>'; // End medical-record

                            // Add styles for consultation remarks
                            echo '<style>
                                .consultation-header {
                                    display: flex;
                                    justify-content: space-between;
                                    align-items: center;
                                    margin-bottom: 8px;
                                }
                                .consultation-details {
                                    background-color: #f8f9fa;
                                    padding: 10px;
                                    border-radius: 4px;
                                    margin: 5px 0;
                                }
                                .remarks {
                                    margin: 0;
                                    color: #333;
                                    line-height: 1.5;
                                    white-space: pre-line;
                                }
                                .record-item {
                                    background: white;
                                    margin-bottom: 10px;
                                    padding: 15px;
                                    border-radius: 4px;
                                    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                                }
                                .date {
                                    font-weight: bold;
                                    color: #0e2f44;
                                }
                                .measure-details {
                                    margin-top: 8px;
                                    display: grid;
                                    grid-template-columns: 1fr 1fr;
                                    gap: 15px;
                                }
                                .section h4 {
                                    color: #0e2f44;
                                    font-size: 1.1em;
                                    margin: 20px 0 15px;
                                    padding-bottom: 8px;
                                    border-bottom: 2px solid #0e2f44;
                                }
                            </style>';
                        }
                    }
                } catch (PDOException $e) {
                    echo '<p>Erreur: ' . htmlspecialchars($e->getMessage()) . '</p>';
                }
                ?>
                </div>
            </div>

            <style>
                .content {
                    display: flex;
                    flex-direction: column;
                    height: 100vh;
                }

                .illness-list {
                    flex: 1;
                    display: flex;
                    flex-direction: column;
                    padding: 0;
                    margin: 0;
                }

                .illness-list h2 {
                    margin: 20px;
                    color:rgb(217, 232, 241);
                    text-align: left;
                }

                .file-cabinet {
                    flex: 1;
                    overflow-y: auto;
                    padding: 0 20px 20px 20px;
                    perspective: 2000px;
                }

                .file-cabinet::-webkit-scrollbar {
                    width: 12px;
                }

                .file-cabinet::-webkit-scrollbar-track {
                    background: rgba(255, 255, 255, 0.1);
                }

                .file-cabinet::-webkit-scrollbar-thumb {
                    background: #0e2f44;
                    border-radius: 6px;
                    border: 3px solid rgba(255, 255, 255, 0.1);
                }

                .drawer {
                    background: #ffffff;
                    margin: 15px 0;
                    border-radius: 8px;
                    transform-style: preserve-3d;
                    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
                    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                    position: relative;
                }

                .drawer-front {
                    padding: 20px;
                    cursor: pointer;
                    display: flex;
                    align-items: center;
                    border: 1px solid rgba(0,0,0,0.1);
                    border-radius: 8px;
                    position: relative;
                    transform-origin: center;
                    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
                    background: white;
                }

                .drawer-front:hover {
                    transform: translateY(-5px);
                    box-shadow: 0 8px 16px rgba(0,0,0,0.1);
                }

                .drawer.open .drawer-front {
                    border-radius: 8px 8px 0 0;
                }

                .drawer-content {
                    background: white;
                    padding: 0;
                    max-height: 0;
                    overflow: hidden;
                    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
                    transform-origin: top;
                    transform: rotateX(-90deg);
                    opacity: 0;
                }

                .drawer-content.open {
                    padding: 20px;
                    max-height: 1000px;
                    transform: rotateX(0);
                    opacity: 1;
                    border: 1px solid rgba(0,0,0,0.1);
                    border-top: none;
                    border-radius: 0 0 6px 6px;
                    box-shadow: inset 0 2px 5px rgba(0,0,0,0.05);
                }

                .drawer-label {
                    flex-grow: 1;
                }

                .drawer-label h3 {
                    margin: 0;
                    color: #0e2f44;
                    font-size: 1.2em;
                }

                .drawer-icon {
                    color: #0e2f44;
                    transition: transform 0.3s ease;
                }

                .drawer.open .drawer-icon {
                    transform: rotate(180deg);
                }

                .patient-info {
                    display: flex;
                    gap: 20px;
                    font-size: 0.9em;
                    color: #666;
                    margin-top: 5px;
                }

                .patient-info i {
                    margin-right: 5px;
                    color: #0e2f44;
                }

                .section {
                    margin-bottom: 15px;
                    border-radius: 4px;
                }

                .record-item {
                    margin-bottom: 10px;
                    padding: 15px;
                    background: #f8f9fa;
                    border-radius: 4px;
                    border-left: 3px solid #0e2f44;
                    transition: transform 0.2s ease;
                }

                .record-item:hover {
                    transform: translateX(5px);
                }

                .section h4 {
                    color: #0e2f44;
                    font-size: 1.1em;
                    margin: 20px 0 15px;
                    padding-bottom: 8px;
                    border-bottom: 2px solid #0e2f44;
                }

                /* Update existing record styles */
                .section {
                    background: #fff;
                    padding: 15px;
                    margin-bottom: 15px;
                    border-radius: 4px;
                    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                }

                .record-item {
                    margin-bottom: 10px;
                    padding: 10px;
                    background: #f8f9fa;
                    border-left: 3px solid #0e2f44;
                }

                /* Add 3D lighting effect */
                .drawer::before {
                    content: '';
                    position: absolute;
                    top: 0;
                    left: 0;
                    right: 0;
                    height: 1px;
                    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.8), transparent);
                    z-index: 1;
                }
            </style>

            <script>
                function toggleRecords(patientId) {
                    const drawer = document.getElementById('records-' + patientId);
                    const parentDrawer = drawer.parentElement;
                    const allDrawers = document.querySelectorAll('.drawer-content');
                    const allParentDrawers = document.querySelectorAll('.drawer');
                    
                    // Close all other drawers
                    allDrawers.forEach(d => {
                        if (d !== drawer) {
                            d.classList.remove('open');
                            d.style.display = 'none';
                        }
                    });
                    
                    allParentDrawers.forEach(d => {
                        if (d !== parentDrawer) {
                            d.classList.remove('open');
                        }
                    });

                    // Toggle current drawer
                    if (drawer.style.display === 'none' || !drawer.style.display) {
                        drawer.style.display = 'block';
                        setTimeout(() => {
                            drawer.classList.add('open');
                            parentDrawer.classList.add('open');
                        }, 10);
                    } else {
                        drawer.classList.remove('open');
                        parentDrawer.classList.remove('open');
                        setTimeout(() => {
                            drawer.style.display = 'none';
                        }, 300);
                    }
                }
            </script>
        </div>
    </div>
    <script src="../index.js"></script>
</body>
</html>