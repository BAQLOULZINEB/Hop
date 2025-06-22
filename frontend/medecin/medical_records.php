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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .chart-card {
            background: #f8fafc;
            border-radius: 10px;
            box-shadow: 0 1px 6px #0001;
            padding: 24px 18px 18px 18px;
            margin-bottom: 32px;
        }
        .chart-title {
            font-size: 1.2em;
            color: #34495e;
            margin-bottom: 10px;
            font-weight: 600;
        }
        canvas {
            width: 100% !important;
            height: 350px !important;
        }
        .current-temp-box {
            margin-bottom: 24px;
            font-size: 1.5em;
            color: #e67e22;
            font-weight: bold;
            background: #fff;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }
        .no-data-message {
             color: #c00;
             font-weight: bold;
             margin-top: 18px;
             padding: 15px;
             background-color: #fff3f3;
             border-radius: 8px;
             text-align: center;
        }
        .vitals-title {
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            background-color: #f1f1f1;
            border-radius: 5px;
            margin-top: 10px;
        }
        .vitals-title:hover {
            background-color: #e9e9e9;
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
                            $sql = "SELECT id, date_consultation, remarques 
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
                                    echo '<button class="edit-btn" data-id="' . $consult['id'] . '" data-remarques="' . htmlspecialchars($consult['remarques'] ?? '', ENT_QUOTES) . '" data-date="' . date('d/m/Y H:i', strtotime($consult['date_consultation'])) . '">';
                                    echo '<i class="fa-solid fa-edit"></i> Modifier';
                                    echo '</button>';
                                    echo '</div>';
                                    if (!empty($consult['remarques'])) {
                                        echo '<div class="consultation-details">';
                                        echo '<p class="remarks">' . nl2br(htmlspecialchars($consult['remarques'])) . '</p>';
                                        echo '</div>';
                                    } else {
                                        echo '<div class="consultation-details">';
                                        echo '<p class="remarks no-remarks">Aucune remarque enregistrée</p>';
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
                            echo '<h4 class="vitals-title">';
                            echo '<a href="vital_signs.php?patient_id=' . $patient['patient_id'] . '" style="text-decoration: none; color: inherit; display: flex; justify-content: space-between; align-items: center; width: 100%;">';
                            echo '<span>Mesures Vitales</span>';
                            echo '<i class="fa-solid fa-external-link-alt"></i>';
                            echo '</a>';
                            echo '</h4>';
                            echo '</div>';

                            echo '</div>'; // End medical-history
                            echo '</div>'; // End records-content
                            echo '</div>'; // End drawer

                            // Add styles for consultation remarks
                            echo '<style>
                                .consultation-header {
                                    display: flex;
                                    justify-content: space-between;
                                    align-items: center;
                                    margin-bottom: 8px;
                                    flex-wrap: wrap;
                                    gap: 10px;
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

                                /* Edit button styles */
                                .edit-btn {
                                    background-color: #0e2f44;
                                    color: white;
                                    border: none;
                                    padding: 5px 10px;
                                    border-radius: 4px;
                                    cursor: pointer;
                                    font-size: 0.9em;
                                    transition: background-color 0.3s;
                                    display: flex;
                                    align-items: center;
                                    gap: 5px;
                                }

                                .edit-btn:hover {
                                    background-color: #1a5276;
                                }

                                .edit-btn i {
                                    font-size: 0.8em;
                                }

                                /* Modal styles */
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
                                    padding: 25px;
                                    border: 1px solid #888;
                                    width: 90%;
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
                                    line-height: 1;
                                }

                                .close:hover {
                                    color: #000;
                                }

                                .consultation-date {
                                    color: #666;
                                    font-style: italic;
                                    margin-bottom: 20px;
                                    padding: 10px;
                                    background-color: #f8f9fa;
                                    border-radius: 4px;
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
                                    min-height: 150px;
                                    padding: 12px;
                                    border: 1px solid #ddd;
                                    border-radius: 4px;
                                    resize: vertical;
                                    font-family: inherit;
                                    line-height: 1.5;
                                }

                                .form-group textarea:focus {
                                    outline: none;
                                    border-color: #0e2f44;
                                    box-shadow: 0 0 0 2px rgba(14, 47, 68, 0.2);
                                }

                                .form-group input[type="datetime-local"] {
                                    width: 100%;
                                    padding: 12px;
                                    border: 1px solid #ddd;
                                    border-radius: 4px;
                                    font-family: inherit;
                                    font-size: 14px;
                                }

                                .form-group input[type="datetime-local"]:focus {
                                    outline: none;
                                    border-color: #0e2f44;
                                    box-shadow: 0 0 0 2px rgba(14, 47, 68, 0.2);
                                }

                                .form-actions {
                                    display: flex;
                                    gap: 10px;
                                    justify-content: flex-end;
                                    margin-top: 20px;
                                }

                                .cancel-btn {
                                    background-color: #6c757d;
                                    color: white;
                                    padding: 10px 20px;
                                    border: none;
                                    border-radius: 4px;
                                    cursor: pointer;
                                    font-size: 14px;
                                    transition: background-color 0.3s;
                                }

                                .cancel-btn:hover {
                                    background-color: #5a6268;
                                }

                                .submit-btn {
                                    background-color: #0e2f44;
                                    color: white;
                                    padding: 10px 20px;
                                    border: none;
                                    border-radius: 4px;
                                    cursor: pointer;
                                    font-size: 14px;
                                    transition: background-color 0.3s;
                                }

                                .submit-btn:hover {
                                    background-color: #1a5276;
                                }

                                .no-remarks {
                                    color: #999;
                                    font-style: italic;
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

            <!-- Edit Consultation Modal -->
            <div id="editModal" class="modal" style="display: none;">
                <div class="modal-content">
                    <span class="close" onclick="closeEditModal()">&times;</span>
                    <h2>Modifier la Consultation</h2>
                    <form id="editForm">
                        <input type="hidden" id="editConsultationId" name="consultation_id">
                        <div class="form-group">
                            <label for="editConsultationDate">Date et Heure de Consultation:</label>
                            <input type="datetime-local" id="editConsultationDate" name="date_consultation" required>
                        </div>
                        <div class="form-group">
                            <label for="editRemarques">Remarques de Consultation:</label>
                            <textarea id="editRemarques" name="remarques" 
                                    placeholder="Notez ici vos observations médicales, le diagnostic et le traitement recommandé..."></textarea>
                        </div>
                        <div class="form-actions">
                            <button type="button" onclick="closeEditModal()" class="cancel-btn">Annuler</button>
                            <button type="submit" class="submit-btn">Enregistrer les modifications</button>
                        </div>
                    </form>
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

                // Add event listeners for edit buttons
                document.addEventListener('DOMContentLoaded', function() {
                    // Add click event listeners to all edit buttons
                    document.addEventListener('click', function(e) {
                        if (e.target.closest('.edit-btn')) {
                            const button = e.target.closest('.edit-btn');
                            const consultationId = button.getAttribute('data-id');
                            const remarques = button.getAttribute('data-remarques');
                            const consultationDate = button.getAttribute('data-date');
                            
                            console.log('Edit button clicked:', { consultationId, remarques, consultationDate });
                            openEditModal(consultationId, remarques, consultationDate);
                        }
                    });
                });

                // Edit modal functions
                function openEditModal(consultationId, remarques, consultationDate) {
                    console.log('Opening edit modal:', { consultationId, remarques, consultationDate });
                    console.log('Remarques type:', typeof remarques);
                    console.log('Remarques value:', remarques);
                    
                    // Validate parameters
                    if (!consultationId) {
                        alert('Erreur: ID de consultation manquant');
                        return;
                    }
                    
                    // Format date for datetime-local input (YYYY-MM-DDTHH:MM)
                    let formattedDate = '';
                    if (consultationDate) {
                        // Convert from DD/MM/YYYY HH:MM to YYYY-MM-DDTHH:MM
                        const dateParts = consultationDate.split(' ')[0].split('/');
                        const timeParts = consultationDate.split(' ')[1] || '00:00';
                        if (dateParts.length === 3) {
                            formattedDate = dateParts[2] + '-' + 
                                           dateParts[1].padStart(2, '0') + '-' + 
                                           dateParts[0].padStart(2, '0') + 'T' + 
                                           timeParts;
                        }
                    }
                    
                    // Set form values
                    document.getElementById('editConsultationId').value = consultationId;
                    document.getElementById('editConsultationDate').value = formattedDate;
                    
                    // Handle remarques - ensure it's a string and not null/undefined
                    const remarquesValue = (remarques && remarques !== 'null' && remarques !== 'undefined') ? remarques : '';
                    document.getElementById('editRemarques').value = remarquesValue;
                    
                    console.log('Form values set:', {
                        consultationId: document.getElementById('editConsultationId').value,
                        date: document.getElementById('editConsultationDate').value,
                        remarques: document.getElementById('editRemarques').value
                    });
                    
                    // Show modal
                    document.getElementById('editModal').style.display = 'block';
                }

                function closeEditModal() {
                    document.getElementById('editModal').style.display = 'none';
                    document.getElementById('editForm').reset();
                }

                // Handle form submission
                document.getElementById('editForm').addEventListener('submit', async function(e) {
                    e.preventDefault();
                    
                    const formData = new FormData(this);
                    const submitBtn = this.querySelector('.submit-btn');
                    const originalText = submitBtn.textContent;
                    
                    // Log form data for debugging
                    console.log('Submitting form data:', {
                        consultation_id: formData.get('consultation_id'),
                        date_consultation: formData.get('date_consultation'),
                        remarques: formData.get('remarques')
                    });
                    
                    // Disable button and show loading state
                    submitBtn.disabled = true;
                    submitBtn.textContent = 'Enregistrement...';
                    
                    try {
                        const response = await fetch('api/update_consultation.php', {
                            method: 'POST',
                            body: formData
                        });
                        
                        console.log('Response status:', response.status);
                        
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                        
                        const result = await response.json();
                        console.log('Response result:', result);
                        
                        if (result.success) {
                            alert('Consultation modifiée avec succès!');
                            closeEditModal();
                            // Reload the page to show updated data
                            location.reload();
                        } else {
                            alert('Erreur: ' + (result.error || 'Erreur inconnue'));
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        alert('Erreur lors de la modification de la consultation: ' + error.message);
                    } finally {
                        // Re-enable button
                        submitBtn.disabled = false;
                        submitBtn.textContent = originalText;
                    }
                });

                // Close modal when clicking outside
                window.addEventListener('click', function(event) {
                    const modal = document.getElementById('editModal');
                    if (event.target === modal) {
                        closeEditModal();
                    }
                });

                // Close modal with Escape key
                document.addEventListener('keydown', function(event) {
                    if (event.key === 'Escape') {
                        closeEditModal();
                    }
                });

                document.addEventListener('DOMContentLoaded', function() {
                    const profileMenu = document.querySelector('.profile-menu');
                    if (profileMenu) {
                        profileMenu.addEventListener('click', function() {
                            const dropdown = this.querySelector('.profile-dropdown');
                            if (dropdown) {
                                dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
                            }
                        });
                    }
                });

                // Function to toggle main records visibility
                const recordsDiv = document.getElementById('records-' + patientId);
                const drawer = recordsDiv.closest('.drawer');
                const icon = drawer.querySelector('.drawer-icon');

                if (recordsDiv.style.display === 'block') {
                    recordsDiv.style.display = 'none';
                    icon.classList.remove('fa-chevron-up');
                    icon.classList.add('fa-chevron-down');
                } else {
                    recordsDiv.style.display = 'block';
                    icon.classList.remove('fa-chevron-down');
                    icon.classList.add('fa-chevron-up');
                }
            </script>
        </div>
    </div>
    <script src="../index.js"></script>
</body>
</html>