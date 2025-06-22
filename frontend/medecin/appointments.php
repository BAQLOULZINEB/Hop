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
            
 

            <!-- Today's Appointments Table -->
            <div class="illness-list">
                <h2>Rendez-vous du Jour</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Patient</th>
                            <th>Email</th>
                            <th>Heure</th>
                            <th>Statut</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        try {
                            $db = new PDO("mysql:host=localhost;dbname=medical_system", "root", "");
                            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                            $db->exec("SET NAMES utf8mb4");
                            $medecin_id = $_SESSION['user']['id'];
                            
                            $sql = "SELECT u.id as patient_id, u.nom, u.email, r.date_rendezvous, r.statut
                                    FROM rendez_vous r
                                    JOIN patient p ON r.patient_id = p.id
                                    JOIN utilisateur u ON p.id = u.id
                                    WHERE r.medecin_id = :medecin_id 
                                    AND r.statut = 'confirmé'
                                    AND DATE(r.date_rendezvous) = CURDATE()
                                    ORDER BY r.date_rendezvous ASC";
                            
                            $stmt = $db->prepare($sql);
                            $stmt->execute([':medecin_id' => $medecin_id]);
                            $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            
                            if (empty($appointments)) {
                                echo '<tr><td colspan="5">Aucun rendez-vous confirmé pour aujourd\'hui</td></tr>';
                            } else {
                                foreach ($appointments as $apt) {
                                    echo '<tr>';
                                    echo '<td>' . htmlspecialchars($apt['nom']) . '</td>';
                                    echo '<td>' . htmlspecialchars($apt['email']) . '</td>';
                                    echo '<td>' . date('H:i', strtotime($apt['date_rendezvous'])) . '</td>';
                                    echo '<td>' . htmlspecialchars($apt['statut']) . '</td>';
                                    echo '<td>
                                            <button onclick="openConsultation(' . $apt['patient_id'] . ', \'' . htmlspecialchars($apt['nom']) . '\')" class="consultation-btn">
                                                Démarrer Consultation
                                            </button>
                                          </td>';
                                    echo '</tr>';
                                }
                            }
                        } catch (PDOException $e) {
                            echo '<tr><td colspan="5">Erreur: ' . htmlspecialchars($e->getMessage()) . '</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>

            <!-- Modal de Consultation -->
            <div id="consultationModal" class="modal" style="display: none;">
                <div class="modal-content">
                    <span class="close">&times;</span>
                    <h2>Consultation - <span id="patientName"></span></h2>
                    <form id="consultationForm" method="POST">
                        <input type="hidden" id="patient_id" name="patient_id">
                        
                        <div class="form-group">
                            <label for="remarques">Remarques de Consultation:</label>
                            <textarea id="remarques" name="remarques" required 
                                    placeholder="Notez ici vos observations médicales, le diagnostic et le traitement recommandé..."></textarea>
                        </div>
                        
                        <div class="form-group checkbox-group">
                            <label>
                                <input type="checkbox" id="hospitalisation" name="hospitalisation">
                                Hospitalisation nécessaire
                            </label>
                        </div>
                        
                        <div class="form-group">
                            <label for="next_rdv">Prochain rendez-vous (si nécessaire):</label>
                            <input type="datetime-local" id="next_rdv" name="next_rdv">
                        </div>
                        
                        <div class="form-group info-text">
                            <small>* Les remarques seront enregistrées avec la date de consultation dans l'historique médical du patient.</small>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="submit-btn">Enregistrer la consultation</button>
                        </div>
                    </form>
                </div>
            </div>

            <style>
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
                    background-color: #fff;
                    margin: 5% auto;
                    padding: 20px;
                    border: 1px solid #888;
                    width: 80%;
                    max-width: 600px;
                    border-radius: 8px;
                    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
                }
                .close {
                    color: #aaa;
                    float: right;
                    font-size: 28px;
                    font-weight: bold;
                    cursor: pointer;
                }
                .close:hover {
                    color: #000;
                }
                .form-group {
                    margin-bottom: 20px;
                }
                .form-group label {
                    display: block;
                    margin-bottom: 8px;
                    font-weight: bold;
                    color: #333;
                }
                .form-group textarea {
                    width: 100%;
                    min-height: 120px;
                    padding: 10px;
                    border: 1px solid #ddd;
                    border-radius: 4px;
                    resize: vertical;
                }
                .checkbox-group {
                    display: flex;
                    align-items: center;
                }
                .checkbox-group input[type="checkbox"] {
                    margin-right: 10px;
                }
                input[type="datetime-local"] {
                    width: 100%;
                    padding: 8px;
                    border: 1px solid #ddd;
                    border-radius: 4px;
                }
                .consultation-btn {
                    background-color: #0e2f44;
                    color: white;
                    padding: 8px 16px;
                    border: none;
                    border-radius: 4px;
                    cursor: pointer;
                    transition: background-color 0.3s;
                }
                .consultation-btn:hover {
                    background-color: #1a5276;
                }
                .submit-btn {
                    background-color: #0e2f44;
                    color: white;
                    padding: 10px 20px;
                    border: none;
                    border-radius: 4px;
                    cursor: pointer;
                    width: 100%;
                    font-size: 16px;
                    transition: background-color 0.3s;
                }
                .submit-btn:hover {
                    background-color: #1a5276;
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
                            const result = await response.json();
                            if (result.success) {
                                alert('Consultation enregistrée avec succès');
                                modal.style.display = "none";
                                location.reload();
                            } else {
                                alert('Erreur: ' + (result.error || 'Erreur inconnue'));
                            }
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