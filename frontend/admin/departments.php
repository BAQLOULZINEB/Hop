<?php
require_once '../../backend/auth/session_handler.php';
checkRole('admin');

// Fetch all unique specialties from the medecin table
$specialties = [];
try {
    $db = new PDO("mysql:host=localhost;dbname=medical_system;charset=utf8", "root", "");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->exec("SET NAMES 'utf8'");
    $query = "SELECT DISTINCT specialite FROM medecin WHERE specialite IS NOT NULL AND specialite != '' ORDER BY specialite";
    $stmt = $db->query($query);
    $specialties = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $error = 'Erreur de base de données : ' . $e->getMessage();
}
// Define a palette of colors for the boxes
$colors = [
    '#3498db', '#e67e22', '#e74c3c', '#1abc9c', '#9b59b6', '#f1c40f', '#16a085', '#2ecc71', '#34495e', '#fd79a8', '#00b894', '#fdcb6e', '#6c5ce7', '#00cec9', '#d35400', '#636e72', '#b2bec3', '#fab1a0', '#0984e3', '#00b894', '#fdcb6e', '#6c5ce7', '#00cec9', '#d35400', '#636e72', '#b2bec3', '#fab1a0', '#0984e3'
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Spécialités</title>
    <link rel="stylesheet" href="../css_files/master.css">
    <link rel="stylesheet" href="../css_files/all.min.css">
    <style>
        .departments-boxes {
            display: flex;
            flex-wrap: wrap;
            gap: 28px;
            margin: 40px 0 0 0;
            justify-content: flex-start;
        }
        .department-box {
            min-width: 210px;
            max-width: 260px;
            min-height: 120px;
            flex: 1 1 210px;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            justify-content: center;
            border-radius: 18px;
            box-shadow: 0 2px 16px rgba(0,0,0,0.10);
            color: #fff;
            font-size: 1.25em;
            font-weight: 600;
            padding: 28px 28px 22px 28px;
            position: relative;
            transition: transform 0.18s, box-shadow 0.18s;
            cursor: pointer;
        }
        .department-box:hover {
            transform: translateY(-6px) scale(1.04);
            box-shadow: 0 8px 32px rgba(0,0,0,0.13);
        }
        .department-box .icon {
            font-size: 2.2em;
            opacity: 0.18;
            position: absolute;
            top: 18px;
            right: 22px;
        }
        .departments-title {
            font-size: 2rem;
            font-weight: 700;
            color:rgb(203, 235, 255);
            margin-bottom: 18px;
            margin-top: 18px;
        }
        @media (max-width: 900px) {
            .departments-boxes { gap: 16px; }
            .department-box { min-width: 140px; max-width: 98vw; font-size: 1em; padding: 18px 10px 14px 18px; }
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
                    <a href="reports.php">
                        <i class="fa-solid fa-file-signature fa-fw"></i>
                        <span>Rapports</span>
                    </a>
                </li>
                 <li>
                    <a href="charts.php">
                        <i class="fa-regular fa-comments fa-fw"></i>
                        <span>Charts</span>
                    </a>
                </li>
                <li>
                    <a href="settings.php">
                        <i class="fa-solid fa-gear fa-fw"></i>
                        <span>Paramètres</span>
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
            <div class="departments-title">Toutes les spécialités</div>
            <?php if (!empty($error)): ?>
                <div class="error-message" style="color: #e74c3c; margin-bottom: 20px;">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            <div class="departments-boxes">
                <?php foreach ($specialties as $i => $spec): ?>
                    <div class="department-box" style="background: <?php echo $colors[$i % count($colors)]; ?>;">
                        <span><?php echo htmlspecialchars($spec, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></span>
                        <span class="icon"><i class="fa-solid fa-people-group"></i></span>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($specialties)): ?>
                    <div style="color:#0e2f44; font-weight:600;">Aucune spécialité trouvée.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script src="../index.js"></script>
</body>
</html> 