<?php
require_once '../../backend/auth/session_handler.php';
checkRole('admin');

// Connexion DB
try {
    $db = new PDO("mysql:host=localhost;dbname=medical_system", "root", "");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

// Traitement validation/refus
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['patient_id'], $_POST['medecin_id'], $_POST['date_rendezvous'])) {
    $action = $_POST['action'] === 'valider' ? 'confirmé' : 'refusé';
    $stmt = $db->prepare("UPDATE rendez_vous SET statut = ? WHERE patient_id = ? AND medecin_id = ? AND date_rendezvous = ?");
    $stmt->execute([$action, $_POST['patient_id'], $_POST['medecin_id'], $_POST['date_rendezvous']]);
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
                <li><a href="admin_dashboard.php"><i class="fa-solid fa-cubes fa-fw"></i><span>Dashboard</span></a></li>
                <li class="num num1">
                    <a class="listted" href="doctors.php"><i class="fa-solid fa-user-nurse fa-fw"></i><span>Médecins</span><i class="fa-solid fa-angle-right tog"></i></a>
                    <div class="list one" style="display: none;"><a href="doctors.php">Voir les médecins</a><a href="add_doctor.php">Ajouter un médecin</a></div>
                </li>
                <li class="num num2">
                    <a class="listted" href="departments.php"><i class="fa-solid fa-people-group fa-fw"></i><span>Spécialités</span><i class="fa-solid fa-angle-right tog"></i></a>
                    <div class="list two" style="display: none;"><a href="departments.php">Voir les spécialités</a></div>
                </li>
                <li><a href="patients.php"><i class="fa-solid fa-people-arrows fa-fw"></i><span>Patients</span></a></li>
                <li><a href="rendezvous.php"><i class="fa-solid fa-calendar-check fa-fw"></i><span>Rendez-vous</span></a></li>
                <li><a href="pharmacy.php"><i class="fa-solid fa-hand-holding-medical fa-fw"></i><span>Pharmacie</span></a></li>
                <li><a href="reports.php"><i class="fa-solid fa-file-signature fa-fw"></i><span>Rapports</span></a></li>
                <li><a href="charts.php"><i class="fa-regular fa-comments fa-fw"></i><span>Charts</span></a></li>
                <li><a href="settings.php"><i class="fa-solid fa-gear fa-fw"></i><span>Paramètres</span></a></li>
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
                                <?php if ($rdv['statut'] !== 'refusé'): ?>
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
</body>
</html> 