<?php
require_once '../../backend/auth/session_handler.php';
require_once '../../backend/config/database.php';
checkRole('admin');

// Handle delete operation
if(isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $sql = "DELETE FROM patient WHERE id = ?";
    if($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $id);
        if(mysqli_stmt_execute($stmt)) {
            header("location: patients.php");
            exit();
        }
        mysqli_stmt_close($stmt);
    }
}

// Search functionality
$search = isset($_GET['search']) ? $_GET['search'] : '';
$where_clause = "";
if(!empty($search)) {
    $search = "%" . $search . "%";
    $where_clause = "WHERE u.nom LIKE ? OR u.email LIKE ?";
}

// Fetch patients
$sql = "SELECT p.*, u.nom, u.email, u.role 
        FROM patient p 
        JOIN utilisateur u ON p.id = u.id 
        $where_clause 
        ORDER BY p.id DESC";

if($stmt = mysqli_prepare($conn, $sql)) {
    if(!empty($search)) {
        mysqli_stmt_bind_param($stmt, "ss", $search, $search);
    }
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    mysqli_stmt_close($stmt);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Patients - Système de Gestion Hospitalière</title>
    <link rel="stylesheet" href="../css_files/master.css">
    <link rel="stylesheet" href="../css_files/all.min.css">
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
                        <span>Tableau de Bord</span>
                    </a>
                </li>
                <li class="num num1">
                    <a class="listted" href="doctors.php">
                        <i class="fa-solid fa-user-nurse fa-fw"></i>
                        <span>Médecins</span>
                        <i class="fa-solid fa-angle-right tog"></i>
                    </a>
                    <div class="list one">
                        <a href="doctors.php">Voir les médecins</a>
                        <a href="add_doctor.php">Ajouter un médecin</a>
                    </div>
                </li>
                <li>
                    <a href="patients.php" class="active">
                        <i class="fa-solid fa-people-arrows fa-fw"></i>
                        <span>Patients</span>
                    </a>
                </li>
                <li>
                    <a href="appointments.php">
                        <i class="fa-solid fa-calendar-check fa-fw"></i>
                        <span>Rendez-vous</span>
                    </a>
                </li>
                <li>
                    <a href="departments.php">
                        <i class="fa-solid fa-people-group fa-fw"></i>
                        <span>Spécialités</span>
                    </a>
                </li>
                <li>
                    <a href="pharmacy.php">
                        <i class="fa-solid fa-hand-holding-medical fa-fw"></i>
                        <span>Pharmacie</span>
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
                        <span>Graphiques</span>
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
                    <span>Déconnexion</span>
                </button>
            </form>
        </div>
        
        <div class="content">
            <div class="header pro-header">
                <div class="header-left">
                    <img src="../images/download__15__14-removebg-preview.png" alt="Logo" class="header-logo">
                    <div class="welcome">
                        <h1>Gestion des Patients</h1>
                        <span class="subtitle">Système de Gestion Hospitalière</span>
                    </div>
                </div>
                <div class="header-center">
                    <form action="" method="get" class="search-bar">
                        <input type="search" name="search" placeholder="Rechercher un patient..." value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
                    </form>
                </div>
                <div class="header-right">
                    <a href="add_patient.php" class="btn-add">
                        <i class="fa-solid fa-plus"></i> Ajouter un patient
                    </a>
                </div>
            </div>

            <div class="details">
                <div class="illness-list">
                    <table>
                        <thead>
                            <tr>
                                <td>ID</td>
                                <td>Nom</td>
                                <td>Email</td>
                                <td>Date de naissance</td>
                                <td>Actions</td>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td><?php echo $row['id']; ?></td>
                                <td><?php echo htmlspecialchars($row['nom']); ?></td>
                                <td><?php echo htmlspecialchars($row['email']); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($row['date_naissance'])); ?></td>
                                <td>
                                    <a href="view_patient.php?id=<?php echo $row['id']; ?>" class="btn-view">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>
                                    <a href="edit_patient.php?id=<?php echo $row['id']; ?>" class="btn-edit">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </a>
                                    <a href="patients.php?delete=<?php echo $row['id']; ?>" class="btn-delete" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce patient ?');">
                                        <i class="fa-solid fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <script src="../index.js"></script>
    <script>
        // Toggle submenus
        document.querySelectorAll('.listted').forEach(function(toggle) {
            toggle.addEventListener('click', function(e) {
                e.preventDefault();
                const submenu = this.nextElementSibling;
                if (submenu) {
                    submenu.style.display = submenu.style.display === 'block' ? 'none' : 'block';
                }
            });
        });
    </script>
</body>
</html> 