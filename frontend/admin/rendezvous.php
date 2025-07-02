<?php
require_once '../../backend/auth/session_handler.php';
checkRole('admin');

// Connexion DB
try {
    $db = new PDO("mysql:host=localhost;dbname=medical_system", "root", "");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->exec("SET NAMES utf8mb4");
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

// Traitement validation/refus
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['action'], $_POST['patient_id'], $_POST['medecin_id'], $_POST['date_rendezvous'])
) {
    if ($_POST['action'] === 'valider') {
        $action = 'confirmé';
    } elseif ($_POST['action'] === 'refuser') {
        $action = 'annulé';
    } else {
        $action = null;
    }
    if ($action) {
        $stmt = $db->prepare("UPDATE rendez_vous SET statut = ? WHERE patient_id = ? AND medecin_id = ? AND date_rendezvous = ?");
        $stmt->execute([$action, $_POST['patient_id'], $_POST['medecin_id'], $_POST['date_rendezvous']]);
    }
}

// Récupérer tous les rendez-vous
$sql = "SELECT rv.*, 
       u.nom AS patient_nom, 
       um.nom AS medecin_nom, 
       m.specialite
FROM rendez_vous rv
JOIN utilisateur u ON rv.patient_id = u.id
JOIN medecin m ON rv.medecin_id = m.id
JOIN utilisateur um ON m.id = um.id
ORDER BY rv.date_rendezvous DESC";
$stmt = $db->query($sql);
$rendezvous = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Arabic:wght@100..900&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <title>Validation des Rendez-vous</title>
    <style>
        /* Only content area styles below, sidebar/header untouched */
        body, input, select, button, label, textarea {
            font-family: 'Montserrat', Arial, sans-serif;
        }
        
        .illness-list {
            margin: 0;
            max-width: none;
            background: rgba(30, 44, 70, 0.85);
            border-radius: 18px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.13);
            padding: 32px 28px 24px 28px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }
        .illness-list h2 {
            color: #fff;
            font-size: 1.7em;
            margin-bottom: 24px;
            font-weight: 700;
        }
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            background: rgba(255,255,255,0.07);
            border-radius: 12px;
            overflow: hidden;
            color: #fff;
        }
        thead th, thead td {
            background: rgba(14, 47, 68, 0.95);
            color: #fff;
            font-weight: 700;
            font-size: 1.08em;
            padding: 16px 10px;
            border-bottom: 2px solid #1a5276;
        }
        tbody td {
            padding: 14px 10px;
            font-size: 1.08em;
            border-bottom: 1px solid rgba(255,255,255,0.08);
            color: #fff;
        }
        tr:last-child td {
            border-bottom: none;
        }
        table th, table td {
            text-align: center;
        }
        table tr {
            transition: background 0.2s;
        }
        table tr:hover {
            background: rgba(255,255,255,0.08);
        }
        button, .illness-list button {
            background: linear-gradient(90deg, #ff9800 60%, #ff5722 100%);
            color: #fff;
            border: none;
            border-radius: 7px;
            padding: 8px 18px;
            font-size: 1em;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.2s, box-shadow 0.2s;
            box-shadow: 0 2px 8px rgba(44,62,80,0.08);
        }
        button:hover, .illness-list button:hover {
            background: linear-gradient(90deg, #ff5722 60%, #ff9800 100%);
        }
        @media (max-width: 900px) {
            .illness-list {
                padding: 18px 8px;
            }
            table th, table td {
                font-size: 0.98em;
                padding: 10px 4px;
            }
        }
        .table-wrapper {
            flex-grow: 1;
            overflow-y: auto;
            position: relative;
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
                    <a href="patient_mesures.php">
                        <i class="fa-regular fa-comments fa-fw"></i>
                        <span>Charts</span>
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
                        <span class="subtitle">Validation des rendez-vous</span>
                    </div>
                </div>
                <div class="header-center">
                    <form action="" method="post" class="search-bar">
                        <input type="search" name="search_query" placeholder="Rechercher un patient, médecin ou spécialité">
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
            <div class="illness-list">
                <h2>Liste des rendez-vous à valider</h2>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Patient</th>
                                <th>Médecin</th>
                                <th>Spécialité</th>
                                <th>Date & Heure</th>
                                <th>Statut</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($rendezvous as $rdv): ?>
                            <tr>
                                <td><?= htmlspecialchars($rdv['patient_nom']) ?></td>
                                <td><?= htmlspecialchars($rdv['medecin_nom']) ?></td>
                                <td><?= htmlspecialchars($rdv['specialite']) ?></td>
                                <td><?= htmlspecialchars($rdv['date_rendezvous']) ?></td>
                                <td><?= htmlspecialchars($rdv['statut']) ?></td>
                                <td>
                                    <?php if ($rdv['statut'] !== 'confirmé'): ?>
                                    <form method="post" style="display:inline;">
                                        <input type="hidden" name="patient_id" value="<?= $rdv['patient_id'] ?>">
                                        <input type="hidden" name="medecin_id" value="<?= $rdv['medecin_id'] ?>">
                                        <input type="hidden" name="date_rendezvous" value="<?= $rdv['date_rendezvous'] ?>">
                                        <button type="submit" name="action" value="valider">Valider</button>
                                    </form>
                                    <?php endif; ?>
                                    <?php if ($rdv['statut'] !== 'annulé'): ?>
                                    <form method="post" style="display:inline;">
                                        <input type="hidden" name="patient_id" value="<?= $rdv['patient_id'] ?>">
                                        <input type="hidden" name="medecin_id" value="<?= $rdv['medecin_id'] ?>">
                                        <input type="hidden" name="date_rendezvous" value="<?= $rdv['date_rendezvous'] ?>">
                                        <button type="submit" name="action" value="refuser">Refuser</button>
                                    </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>