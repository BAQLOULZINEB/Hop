<?php
require_once '../../backend/auth/session_handler.php';
checkRole('patient');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
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

if (isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header('Location: ../../frontend/Authentification.php');
    exit();
}

// Connect to DB
try {
    $db = new PDO("mysql:host=localhost;dbname=medical_system", "root", "");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->exec('SET NAMES utf8mb4');
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Récupérer les spécialités existantes
$existingSpecialities = [];
try {
    $stmt = $db->query("SELECT DISTINCT specialite FROM medecin WHERE specialite IS NOT NULL AND specialite != '' ORDER BY specialite");
    $existingSpecialities = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    // handle error
}

// Spécialités courantes
$commonSpecialties = [
    'Anesthésie-Réanimation', 'Cardiologie', 'Chirurgie Cardiaque', 'Chirurgie Générale',
    'Chirurgie Orthopédique', 'Chirurgie Pédiatrique', 'Chirurgie Plastique', 'Chirurgie Thoracique',
    'Chirurgie Vasculaire', 'Dermatologie', 'Endocrinologie', 'Gastro-entérologie',
    'Gynécologie-Obstétrique', 'Hématologie', 'Infectiologie', 'Médecine Interne',
    'Néphrologie', 'Neurologie', 'Neurochirurgie', 'Ophtalmologie', 'ORL', 'Pédiatrie',
    'Pneumologie', 'Psychiatrie', 'Radiologie', 'Rhumatologie', 'Urologie'
];
$allSpecialties = array_unique(array_merge($existingSpecialities, $commonSpecialties));
sort($allSpecialties);

// Gestion de la recommandation
$message = '';
if (isset($_POST['reco_medecin_id'], $_POST['note'], $_POST['motif'])) {
    $medecin_id = $_POST['reco_medecin_id'];
    $note = (int)$_POST['note'];
    $motif = trim($_POST['motif']);
    $date_recommandation = date('Y-m-d');
    // Vérifier si le patient a déjà noté ce médecin
    $check = $db->prepare("SELECT COUNT(*) FROM recommendation WHERE patient_id = ? AND medecin_id = ?");
    $check->execute([$patient_id, $medecin_id]);
    if ($check->fetchColumn() == 0) {
        $insert = $db->prepare("INSERT INTO recommendation (medecin_id, patient_id, date_recommandation, motif, note) VALUES (?, ?, ?, ?, ?)");
        if ($insert->execute([$medecin_id, $patient_id, $date_recommandation, $motif, $note])) {
            $message = '<div class=\'success\'>Merci pour votre recommandation !</div>';
        } else {
            $message = '<div class=\'error\'>Erreur lors de l\'enregistrement de la recommandation.</div>';
        }
    } else {
        $message = '<div class=\'error\'>Vous avez déjà noté ce médecin.</div>';
    }
}

// Gestion de la prise de rendez-vous
if (isset($_POST['specialite'], $_POST['medecin_id'], $_POST['date_rendezvous']) && !isset($_POST['note'])) {
    $medecin_id = $_POST['medecin_id'];
    $date_rendezvous = $_POST['date_rendezvous'];
    $conflict = $db->prepare("SELECT COUNT(*) FROM rendez_vous WHERE medecin_id = ? AND date_rendezvous = ? AND statut != 'annulé'");
    $conflict->execute([$medecin_id, $date_rendezvous]);
    if ($conflict->fetchColumn() > 0) {
        $message = '<div class=\'error\'>Ce créneau est déjà réservé. Veuillez choisir un autre horaire.</div>';
    } else {
        $insert = $db->prepare("INSERT INTO rendez_vous (patient_id, medecin_id, date_rendezvous, statut) VALUES (?, ?, ?, 'planifié')");
        if ($insert->execute([$patient_id, $medecin_id, $date_rendezvous])) {
            $message = '<div class=\'success\'>Votre demande de rendez-vous a été envoyée avec succès.</div>';
        } else {
            $message = '<div class=\'error\'>Erreur lors de la demande de rendez-vous.</div>';
        }
    }
}

// Récupérer les médecins selon la spécialité et le nom (filtrage)
$doctors = [];
$filter_nom = isset($_POST['filter_nom']) ? trim($_POST['filter_nom']) : '';
$specialite = isset($_POST['specialite']) ? trim($_POST['specialite']) : '';
$filter_stars = isset($_POST['filter_stars']) ? (int)$_POST['filter_stars'] : 0;

$sql = "SELECT m.id, u.nom, m.specialite, (
    SELECT AVG(note) FROM recommendation r WHERE r.medecin_id = m.id
) as avg_note FROM medecin m JOIN utilisateur u ON m.id = u.id WHERE 1=1";
$params = [];

if ($specialite !== '') {
    $sql .= " AND m.specialite = ?";
    $params[] = $specialite;
}
if ($filter_nom !== '') {
    $sql .= " AND u.nom LIKE ?";
    $params[] = $filter_nom . "%";
}
if ($filter_stars > 0) {
    $sql .= " HAVING avg_note >= ?";
    $params[] = $filter_stars;
}
$sql .= " ORDER BY avg_note DESC, u.nom";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Pour chaque médecin, récupérer la note moyenne et les motifs
$doctor_ratings = [];
$doctor_motifs = [];
if (!empty($doctors)) {
    foreach ($doctors as $doc) {
        // Note moyenne
        $stmt = $db->prepare("SELECT AVG(note) as avg_note FROM recommendation WHERE medecin_id = ?");
        $stmt->execute([$doc['id']]);
        $doctor_ratings[$doc['id']] = round($stmt->fetchColumn(), 1);
        // Derniers motifs
        $stmt = $db->prepare("SELECT motif FROM recommendation WHERE medecin_id = ? ORDER BY date_recommandation DESC LIMIT 3");
        $stmt->execute([$doc['id']]);
        $doctor_motifs[$doc['id']] = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}
?>
<!DOCTYPE html>
<html dir="ltr" lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preload" href="../images/background_page.jpg" as="image">
    <link rel="stylesheet" href="../css_files/master.css">
    <link rel="stylesheet" href="../css_files/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <title>Demander un rendez-vous</title>
    <style>
        body, input, select, button, label, textarea {
            font-family: 'Montserrat', Arial, sans-serif;
        }
        .page {
            display: flex;
            height: 100vh;
            overflow: hidden;
        }
        .dashboard {
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            z-index: 1000;
        }
        .content {
            flex: 1;
            margin-left: 260px;
            height: 100vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        .header {
            position: sticky;
            top: 0;
            z-index: 100;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .main-content {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            background: transparent;
            display: block;
            min-height: 80vh;
        }
        .container {
            max-width: 1100px;
            margin: 0 auto;
            padding: 20px;
            width: 100%;
        }
        .search-container {
            background: rgba(255, 255, 255, 0.95);
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin: 0 32px 40px 32px;
            position: relative;
            z-index: 90;
            width: calc(100% - 64px);
        }
        .search-row {
            display: flex;
            gap: 20px;
            align-items: flex-start;
            flex-wrap: wrap;
        }
        .search-field {
            flex: 1;
            min-width: 250px;
            position: relative;
        }
        .search-field label {
            display: block;
            margin-bottom: 10px;
            color: #0e2f44;
            font-weight: 600;
            font-size: 1.08em;
        }
        .search-field input {
            width: 100%;
            padding: 12px 16px;
            border: 1.5px solid #bbb;
            border-radius: 8px;
            font-size: 1.1em;
            transition: border-color 0.3s, box-shadow 0.3s;
            background: white;
        }
        .search-field input:focus {
            border-color: #0e2f44;
            box-shadow: 0 0 0 3px rgba(14, 47, 68, 0.1);
            outline: none;
        }
        .loading {
            display: none;
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
            font-size: 0.98em;
        }
        .loading.active {
            display: inline-block;
        }
        .doctor-card {
            border-radius: 18px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.13);
            padding: 36px 40px 28px 40px;
            margin-bottom: 38px;
            background: #fff;
            transition: box-shadow 0.3s, transform 0.3s;
            position: relative;
            width: 100%;
            min-width: unset;
            max-width: unset;
            margin-left: 0;
            margin-right: 0;
            margin-top: 24px;
        }
        .doctor-card:hover {
            box-shadow: 0 8px 32px rgba(44,62,80,0.18);
            transform: translateY(-4px) scale(1.01);
        }
        .doctor-card strong {
            font-size: 1.45em;
            color: #0e2f44;
        }
        .stars {
            color: #f1c40f;
            font-size: 1.7em;
            letter-spacing: 2px;
            cursor: pointer;
            user-select: none;
            display: inline-block;
            vertical-align: middle;
        }
        .stars .star {
            transition: color 0.2s, transform 0.2s;
            font-size: 1.8em;
            padding: 0 2px;
        }
        .stars .star.selected,
        .stars .star.hovered {
            color:rgb(255, 201, 85);
            transform: scale(1.18);
            text-shadow: 0 2px 8px #ffeaa7;
        }
        .stars-value {
            font-size: 1.1em;
            color: #0e2f44;
            font-weight: 600;
            margin-left: 10px;
            min-width: 40px;
            display: inline-block;
        }
        .motif { font-size: 1.08em; color: #555; margin-bottom: 4px; }
        .doctor-card form {
            margin-top: 14px;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 14px;
        }
        .doctor-card input[type="text"],
        .doctor-card input[type="datetime-local"] {
            border: 1.5px solid #bbb;
            border-radius: 7px;
            padding: 10px 16px;
            font-size: 1.08em;
            background: #f8f8f8;
            width: 320px;
            max-width: 100%;
        }
        .doctor-card input[type="datetime-local"] {
            min-width: 220px;
        }
        .doctor-card button {
            background: linear-gradient(90deg, #0e2f44 60%, #1a5276 100%);
            color: #fff;
            border: none;
            border-radius: 7px;
            padding: 10px 28px;
            font-size: 1.08em;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.2s, box-shadow 0.2s;
            box-shadow: 0 2px 8px rgba(44,62,80,0.08);
        }
        .doctor-card button:hover {
            background: linear-gradient(90deg, #1a5276 60%, #0e2f44 100%);
        }
        .success, .error {
            padding: 14px 22px;
            border-radius: 9px;
            margin-bottom: 22px;
            font-weight: bold;
            font-size: 1.13em;
            box-shadow: 0 2px 8px rgba(44,62,80,0.08);
            animation: fadeIn 0.7s;
        }
        .success { background: #eafaf1; color: #27ae60; border: 1.5px solid #27ae60; }
        .error { background: #fff6f6; color: #c0392b; border: 1.5px solid #c0392b; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-10px);} to { opacity: 1; transform: none; } }
        @media (max-width: 768px) {
            .dashboard {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            .dashboard.active {
                transform: translateX(0);
            }
            .content {
                margin-left: 0;
            }
            .search-row {
                flex-direction: column;
                gap: 15px;
            }
            .search-field {
                width: 100%;
            }
            .doctor-card {
                min-width: unset;
                padding: 20px;
            }
        }
        /* Custom scrollbar */
        .main-content::-webkit-scrollbar {
            width: 8px;
        }
        .main-content::-webkit-scrollbar-track {
            background: rgba(0,0,0,0.05);
            border-radius: 4px;
        }
        .main-content::-webkit-scrollbar-thumb {
            background: rgba(14, 47, 68, 0.3);
            border-radius: 4px;
        }
        .main-content::-webkit-scrollbar-thumb:hover {
            background: rgba(14, 47, 68, 0.5);
        }
        .dashboard .log-out {
            width: 80%;
            position: absolute;
            bottom: 5px;
            left: 50%;
            transform: translateX(-50%);
            text-align: center;
        }
        .dashboard .log-out button {
            padding: 5px;
            border-radius: 10px;
            width: 100%;
            background-color: transparent;
            border: none;
            outline: none;
            cursor: pointer;
            transition: .3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .dashboard .log-out button:hover {
            background-color: #eb51518a;
        }
        .dashboard .log-out button span {
            margin-right: 10px;
            font-size: 14px;
            font-weight: bold;
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
                        <span class="subtitle">Demander un rendez-vous</span>
                    </div>
                </div>
                <div class="header-right">
                    <div class="profile-menu">
                        <img src="../images/avatar.jpg" alt="Profile" class="avatar">
                        <span class="profile-name"><?php echo $patient_name; ?></span>
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
            <div class="search-container">
                <form id="searchForm" method="post" action="">
                    <div class="search-row">
                        <div class="search-field">
                            <label for="specialite">Spécialité :</label>
                            <input type="text" 
                                   name="specialite" 
                                   id="specialite" 
                                   list="specialtySuggestions" 
                                   placeholder="Entrez ou sélectionnez une spécialité" 
                                   autocomplete="off"
                                   value="<?php echo htmlspecialchars($specialite); ?>">
                            <datalist id="specialtySuggestions">
                                <?php foreach ($allSpecialties as $specialty): ?>
                                    <option value="<?php echo htmlspecialchars($specialty); ?>">
                                        <?php echo htmlspecialchars($specialty); ?>
                                    </option>
                                <?php endforeach; ?>
                            </datalist>
                        </div>
                        <div class="search-field">
                            <label for="filter_nom">Rechercher un médecin :</label>
                            <input type="text" 
                                   name="filter_nom" 
                                   id="filter_nom" 
                                   placeholder="Commencez à taper le nom du médecin..."
                                   value="<?php echo htmlspecialchars($filter_nom); ?>"
                                   autocomplete="off">
                            <span class="loading">Recherche en cours...</span>
                        </div>
                        <div class="search-field">
                            <label for="filter_stars">Note minimale :</label>
                            <select name="filter_stars" id="filter_stars">
                                <option value="0" <?php if($filter_stars==0) echo 'selected'; ?>>Toutes</option>
                                <option value="1" <?php if($filter_stars==1) echo 'selected'; ?>>1+ ★</option>
                                <option value="2" <?php if($filter_stars==2) echo 'selected'; ?>>2+ ★★</option>
                                <option value="3" <?php if($filter_stars==3) echo 'selected'; ?>>3+ ★★★</option>
                                <option value="4" <?php if($filter_stars==4) echo 'selected'; ?>>4+ ★★★★</option>
                                <option value="5" <?php if($filter_stars==5) echo 'selected'; ?>>5 ★★★★★</option>
                            </select>
                        </div>
                    </div>
                </form>
            </div>
            <div class="main-content">
                <div class="container">
                    <?php if (isset($message) && $message) echo $message; ?>
                    
                    <div id="doctorsList">
                        <?php if (!empty($doctors)): ?>
                            <?php foreach ($doctors as $doc): ?>
                                <div class="doctor-card">
                                    <strong><?php echo htmlspecialchars($doc['nom']); ?></strong>
                                    <div style="color: #666; margin-top: 4px;"><?php echo htmlspecialchars($doc['specialite']); ?></div>
                                    <br>
                                    Note moyenne :
                                    <span class="stars">
                                        <?php
                                        $note = isset($doctor_ratings[$doc['id']]) ? $doctor_ratings[$doc['id']] : 0;
                                        for ($i = 1; $i <= 5; $i++) {
                                            echo $i <= round($note) ? '★' : '☆';
                                        }
                                        echo $note ? " ({$note}/5)" : " (pas encore de note)";
                                        ?>
                                    </span><br>
                                    <?php if (!empty($doctor_motifs[$doc['id']])): ?>
                                        <div>Dernières recommandations :</div>
                                        <?php foreach ($doctor_motifs[$doc['id']] as $motif): ?>
                                            <div class="motif">- <?php echo htmlspecialchars($motif); ?></div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                    <form method="post" action="" class="rating-form" style="margin-top:8px;">
                                        <input type="hidden" name="reco_medecin_id" value="<?php echo $doc['id']; ?>">
                                        <label>Votre note :</label>
                                        <div class="stars-input" data-for="<?php echo $doc['id']; ?>">
                                            <?php for ($i=1; $i<=5; $i++): ?>
                                                <span class="star" data-value="<?php echo $i; ?>">&#9733;</span>
                                            <?php endfor; ?>
                                            <input type="hidden" name="note" value="">
                                            <span class="stars-value"></span>
                                        </div>
                                        <input type="text" name="motif" placeholder="Motif (optionnel)" maxlength="255">
                                        <button type="submit">Noter</button>
                                    </form>
                                    <form method="post" action="" style="margin-top:8px;">
                                        <input type="hidden" name="specialite" value="<?php echo htmlspecialchars($specialite); ?>">
                                        <input type="hidden" name="medecin_id" value="<?php echo $doc['id']; ?>">
                                        <label for="date_rendezvous_<?php echo $doc['id']; ?>">Date et heure :</label>
                                        <input type="datetime-local" name="date_rendezvous" id="date_rendezvous_<?php echo $doc['id']; ?>" required>
                                        <button type="submit">Demander ce rendez-vous</button>
                                    </form>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="no-results" style="text-align: center; padding: 20px; background: white; border-radius: 8px; margin-top: 20px;">
                                Aucun médecin trouvé pour votre recherche.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
    function initStarRatings() {
        document.querySelectorAll('.stars-input').forEach(function(starInput) {
            const stars = starInput.querySelectorAll('.star');
            const hiddenInput = starInput.querySelector('input[type="hidden"][name="note"]');
            const valueDisplay = starInput.querySelector('.stars-value');
            let selected = 0;
            stars.forEach((star, idx) => {
                star.addEventListener('mouseenter', function() {
                    highlightStars(idx+1);
                    if (valueDisplay) valueDisplay.textContent = (idx+1) + ' / 5';
                });
                star.addEventListener('mouseleave', function() {
                    highlightStars(selected);
                    if (valueDisplay) valueDisplay.textContent = selected ? (selected + ' / 5') : '';
                });
                star.addEventListener('click', function() {
                    selected = idx+1;
                    hiddenInput.value = selected;
                    highlightStars(selected);
                    if (valueDisplay) valueDisplay.textContent = selected + ' / 5';
                });
            });
            starInput.addEventListener('click', function(e) {
                if (e.target.classList.contains('star')) return;
                const rect = starInput.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const percent = x / rect.width;
                const n = Math.ceil(percent * 5);
                selected = Math.min(Math.max(n, 1), 5);
                hiddenInput.value = selected;
                highlightStars(selected);
                if (valueDisplay) valueDisplay.textContent = selected + ' / 5';
            });
            function highlightStars(n) {
                stars.forEach((star, i) => {
                    if (i < n) {
                        star.classList.add('selected');
                    } else {
                        star.classList.remove('selected');
                    }
                });
            }
        });
    }
    // Call on page load
    initStarRatings();

    // Real-time search functionality
    let searchTimeout;
    const searchForm = document.getElementById('searchForm');
    const filterInput = document.getElementById('filter_nom');
    const specialiteInput = document.getElementById('specialite');
    const loadingIndicator = document.querySelector('.loading');
    const filterStarsInput = document.getElementById('filter_stars');

    function performSearch() {
        loadingIndicator.classList.add('active');
        const formData = new FormData(searchForm);
        
        // Convert the search term to lowercase for case-insensitive search
        const searchTerm = formData.get('filter_nom').toLowerCase();
        formData.set('filter_nom', searchTerm);
        
        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newDoctorsList = doc.getElementById('doctorsList');
            document.getElementById('doctorsList').innerHTML = newDoctorsList.innerHTML;
            loadingIndicator.classList.remove('active');
            // Re-initialize star ratings after AJAX update
            initStarRatings();
        })
        .catch(error => {
            console.error('Error:', error);
            loadingIndicator.classList.remove('active');
        });
    }

    filterInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(performSearch, 300);
    });

    specialiteInput.addEventListener('change', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(performSearch, 300);
    });

    filterStarsInput.addEventListener('change', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(performSearch, 100);
    });

    // Add placeholder text to show search behavior
    filterInput.placeholder = "Commencez à taper le nom du médecin...";
    </script>
    <script src="../index.js"></script>
</body>
</html>