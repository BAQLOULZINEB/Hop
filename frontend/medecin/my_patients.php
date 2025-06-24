<?php
require_once '../../backend/auth/session_handler.php';
checkRole('medecin');

$doctor_name = htmlspecialchars($_SESSION['user']['nom']);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header('Location: ../../frontend/Authentification.php');
    exit();
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
            

            <!-- Patients Table -->
            <div class="illness-list">
                <div class="table-header">
                    <h2>Mes Patients</h2>
                    <div class="filter-buttons">
                        <button id="allPatientsBtn" class="filter-btn active" onclick="showAllPatients()">Tous les Patients</button>
                        <button id="todayPatientsBtn" class="filter-btn" onclick="showTodayPatients()">Patients d'Aujourd'hui</button>
                    </div>
                </div>
                <table id="patientsTable" class="patient-list-table">
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Email</th>
                            <th>Date de Naissance</th>
                            <th>Dernier RDV</th>
                            <th>Prochain RDV</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="patientsTableBody">
                        <?php
                        try {
                            $db = new PDO("mysql:host=localhost;dbname=medical_system", "root", "");
                            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                            $db->exec("SET NAMES utf8mb4");
                            $medecin_id = $_SESSION['user']['id'];
                            
                            // Get all patients who have had appointments with this doctor
                            $sql = "SELECT DISTINCT 
                                    u.id, 
                                    u.nom, 
                                    u.email, 
                                    p.date_naissance,
                                    (SELECT MAX(r.date_rendezvous) 
                                     FROM rendez_vous r 
                                     WHERE r.patient_id = p.id 
                                     AND r.medecin_id = :medecin_id 
                                     AND r.date_rendezvous < CURDATE()) as dernier_rdv,
                                    (SELECT MIN(r.date_rendezvous) 
                                     FROM rendez_vous r 
                                     WHERE r.patient_id = p.id 
                                     AND r.medecin_id = :medecin_id 
                                     AND r.date_rendezvous >= CURDATE()
                                     AND r.statut = 'confirmé') as prochain_rdv,
                                    (SELECT COUNT(*) 
                                     FROM rendez_vous r 
                                     WHERE r.patient_id = p.id 
                                     AND r.medecin_id = :medecin_id 
                                     AND DATE(r.date_rendezvous) = CURDATE()
                                     AND r.statut = 'confirmé') as rdv_aujourd_hui
                                   FROM rendez_vous r
                                   JOIN patient p ON r.patient_id = p.id
                                   JOIN utilisateur u ON p.id = u.id
                                   WHERE r.medecin_id = :medecin_id 
                                   ORDER BY u.nom";
                            
                            $stmt = $db->prepare($sql);
                            $stmt->execute([':medecin_id' => $medecin_id]);
                            $allPatients = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            
                            if (count($allPatients) === 0) {
                                echo '<tr><td colspan="6">Aucun patient trouvé.</td></tr>';
                            } else {
                                foreach ($allPatients as $patient) {
                                    $hasTodayAppointment = $patient['rdv_aujourd_hui'] > 0;
                                    $rowClass = $hasTodayAppointment ? 'today-patient' : '';
                                    
                                    echo '<tr class="' . $rowClass . '" data-today="' . ($hasTodayAppointment ? '1' : '0') . '">';
                                    echo '<td>' . htmlspecialchars($patient['nom']) . '</td>';
                                    echo '<td>' . htmlspecialchars($patient['email']) . '</td>';
                                    echo '<td>' . htmlspecialchars($patient['date_naissance']) . '</td>';
                                    echo '<td>' . ($patient['dernier_rdv'] ? date('d/m/Y H:i', strtotime($patient['dernier_rdv'])) : 'Aucun') . '</td>';
                                    echo '<td>' . ($patient['prochain_rdv'] ? date('d/m/Y H:i', strtotime($patient['prochain_rdv'])) : 'Aucun') . '</td>';
                                    echo '<td>
                                            <button onclick="openConsultation(' . $patient['id'] . ', \'' . htmlspecialchars($patient['nom']) . '\')" class="consultation-btn">
                                                Consultation
                                            </button>
                                          </td>';
                                    echo '</tr>';
                                }
                            }
                        } catch (PDOException $e) {
                            echo '<tr><td colspan="6">Erreur : ' . htmlspecialchars($e->getMessage()) . '</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>

            <!-- Consultation Modal -->
            <div id="consultationModal" class="modal" style="display: none;">
                <div class="modal-content">
                    <span class="close">&times;</span>
                    <h2>Consultation - <span id="patientName"></span></h2>
                    <form id="consultationForm" method="POST" action="">
                        <input type="hidden" id="patient_id" name="patient_id">
                        <div class="form-group">
                            <label for="remarques">Remarques:</label>
                            <textarea id="remarques" name="remarques" required></textarea>
                        </div>
                        <div class="form-group">
                            <label>
                                <input type="checkbox" id="hospitalisation" name="hospitalisation">
                                Hospitalisation nécessaire
                            </label>
                        </div>
                        <div class="form-group">
                            <label for="next_rdv">Prochain rendez-vous:</label>
                            <input type="date" id="next_rdv" name="next_rdv">
                        </div>
                        <button type="submit" name="save_consultation">Enregistrer</button>
                    </form>
                </div>
            </div>

            <style>
                .illness-list h2 {
                    color: white;
                }
                .table-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    margin-bottom: 20px;
                }
                .filter-buttons {
                    display: flex;
                    gap: 10px;
                }
                .filter-btn {
                    padding: 8px 16px;
                    border: 2px solid white;
                    background: transparent;
                    color: white;
                    border-radius: 5px;
                    cursor: pointer;
                    transition: all 0.3s ease;
                }
                .filter-btn.active,
                .filter-btn:hover {
                    background-color: #0e2f44;
                    border-color: #0e2f44;
                    color: white;
                }

                #patientsTable {
                    width: 100%;
                    border-collapse: collapse;
                    color: white;
                }

                #patientsTable thead th {
                    color: white;
                    border-bottom: 2px solid rgba(255, 255, 255, 0.3);
                    padding: 12px 15px;
                    text-align: left;
                }
                
                #patientsTable tbody tr {
                    background-color: rgba(255, 255, 255, 0.08) !important;
                    border-bottom: 1px solid rgba(255, 255, 255, 0.15);
                    transition: all 0.2s ease-out;
                    position: relative;
                }

                #patientsTable tbody tr:hover {
                    transform: translateY(-3px);
                    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
                    background-color: rgba(255, 255, 255, 0.08) !important;
                }

                #patientsTable td {
                    padding: 12px 15px;
                }
                
                #patientsTable tbody tr:last-child {
                    border-bottom: none;
                }
                
                .today-patient {
                    background-color: rgba(46, 204, 113, 0.15) !important;
                    border-left: 4px solid #2ecc71;
                }

                .modal {
                    display: none;
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background-color: rgba(0,0,0,0.5);
                    z-index: 1000;
                }
                .modal-content {
                    background-color: #fefefe;
                    margin: 15% auto;
                    padding: 20px;
                    border: 1px solid #888;
                    width: 80%;
                    max-width: 600px;
                    border-radius: 5px;
                }
                .close {
                    color: #aaa;
                    float: right;
                    font-size: 28px;
                    font-weight: bold;
                    cursor: pointer;
                }
                .consultation-btn {
                    background-color: #ff9800;
                    color: white;
                    padding: 5px 14px;
                    border: none;
                    border-radius: 3px;
                    cursor: pointer;
                    font-weight: bold;
                    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
                    transition: background 0.2s, box-shadow 0.2s;
                }
                .consultation-btn:hover {
                    background-color: #fb8c00;
                    box-shadow: 0 4px 16px rgba(0,0,0,0.12);
                }
                .form-group {
                    margin-bottom: 15px;
                }
                .form-group label {
                    display: block;
                    margin-bottom: 5px;
                }
                .form-group textarea {
                    width: 100%;
                    min-height: 100px;
                }
            </style>

            <script>
                const modal = document.getElementById("consultationModal");
                const span = document.getElementsByClassName("close")[0];

                function openConsultation(patientId, patientName) {
                    document.getElementById("patientName").textContent = patientName;
                    document.getElementById("patient_id").value = patientId;
                    modal.style.display = "block";
                }

                function showAllPatients() {
                    // Show all rows
                    const rows = document.querySelectorAll('#patientsTableBody tr');
                    rows.forEach(row => {
                        row.style.display = '';
                    });
                    
                    // Update button states
                    document.getElementById('allPatientsBtn').classList.add('active');
                    document.getElementById('todayPatientsBtn').classList.remove('active');
                }

                function showTodayPatients() {
                    // Show only today's patients
                    const rows = document.querySelectorAll('#patientsTableBody tr');
                    rows.forEach(row => {
                        const hasTodayAppointment = row.getAttribute('data-today') === '1';
                        row.style.display = hasTodayAppointment ? '' : 'none';
                    });
                    
                    // Update button states
                    document.getElementById('todayPatientsBtn').classList.add('active');
                    document.getElementById('allPatientsBtn').classList.remove('active');
                }

                span.onclick = function() {
                    modal.style.display = "none";
                }

                window.onclick = function(event) {
                    if (event.target == modal) {
                        modal.style.display = "none";
                    }
                }

                document.getElementById("consultationForm").onsubmit = async function(e) {
                    e.preventDefault();
                    const formData = new FormData(this);
                    
                    try {
                        const response = await fetch('save_consultation.php', {
                            method: 'POST',
                            body: formData
                        });
                        
                        if (response.ok) {
                            alert('Consultation enregistrée avec succès');
                            modal.style.display = "none";
                            location.reload();
                        } else {
                            alert('Erreur lors de l\'enregistrement');
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        alert('Erreur lors de l\'enregistrement');
                    }
                };
            </script>
        </div>
    </div>
    <script src="../index.js"></script>
</body>
</html>