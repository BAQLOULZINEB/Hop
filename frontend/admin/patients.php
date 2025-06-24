<?php
require_once '../../backend/auth/session_handler.php';
require_once '../../backend/config/database.php';
checkRole('admin');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header('Location: ../../frontend/Authentification.php');
    exit();
}

$error = '';
$success = '';

try {
    $db = new PDO("mysql:host=localhost;dbname=medical_system", "root", "");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Handle delete operation
    if(isset($_POST['delete_patient'])) {
        $patient_id = $_POST['patient_id'];
        
        // Start transaction
        $db->beginTransaction();
        
        // Delete from patient table
        $query = "DELETE FROM patient WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->execute([':id' => $patient_id]);
        
        // Delete from utilisateur table
        $query = "DELETE FROM utilisateur WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->execute([':id' => $patient_id]);
        
        // Commit transaction
        $db->commit();
        
        $success = "Patient deleted successfully!";
    }

    // Search functionality
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $where_clause = "";
    $params = [];
    
    if(!empty($search)) {
        $where_clause = "WHERE u.nom LIKE :search OR u.email LIKE :search";
        $params[':search'] = "%$search%";
    }

    // Get all patients with their user information
    $query = "SELECT p.*, u.nom, u.email, u.id as user_id 
              FROM patient p 
              JOIN utilisateur u ON p.id = u.id 
              $where_clause
              ORDER BY u.nom ASC";
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    $error = 'Database error: ' . $e->getMessage();
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
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Arabic:wght@100..900&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <title>Patients Management</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; }
        .illness-list { 
            width: 100%; 
            margin: 0 auto; 
            padding-bottom: 0; 
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            background: #fff; 
            border-radius: 10px; 
            overflow: hidden; 
            margin-bottom: 0;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
        }
        th, td { 
            padding: 12px 10px; 
            text-align: left; 
            font-size: 1.05em;
            color: #222;
            background: #fff;
            border-bottom: 1px solid #e0e0e0;
        }
        thead tr { 
            background: #f2f2f2; 
            color: #0e2f44; 
            font-weight: bold;
        }
        tbody tr:last-child td {
            border-bottom: none;
        }
        .action-buttons { 
            display: flex; 
            gap: 10px; 
        }
        .edit-btn, .delete-btn, .save-btn, .cancel-btn {
            padding: 6px 14px; 
            border: none; 
            border-radius: 5px; 
            cursor: pointer; 
            font-size: 15px; 
            transition: all 0.3s;
        }
        .edit-btn { 
            background: #3498db; 
            color: #fff; 
        }
        .delete-btn { 
            background: #e74c3c; 
            color: #fff; 
        }
        .save-btn { 
            background: #27ae60; 
            color: #fff; 
        }
        .cancel-btn { 
            background: #95a5a6; 
            color: #fff; 
        }
        .edit-btn:hover { 
            background: #217dbb; 
        }
        .delete-btn:hover { 
            background: #c0392b; 
        }
        .save-btn:hover { 
            background: #219a52; 
        }
        .cancel-btn:hover { 
            background: #7f8c8d; 
        }
        
        .editable { 
            cursor: pointer; 
            border-radius: 3px; 
        }
        .editable:hover { 
            background: #f0f0f0; 
        }
        .editing { 
            background: #fff; 
            border: 1px solid #3498db; 
        }
        .editing input, .editing select { 
            width: 100%; 
            padding: 5px; 
            border: 1px solid #ddd; 
            border-radius: 3px; 
        }
        .add-patient-btn { 
            background: linear-gradient(120deg, #0e2f44, #1a5276); 
            color: white; 
            padding: 10px 20px; 
            border: none; 
            border-radius: 5px; 
            cursor: pointer; 
            margin: 0 0 12px 0; 
            transition: all 0.3s; 
            display: block; 
        }
        .add-patient-btn:hover { 
            background: linear-gradient(120deg, #1a5276, #0e2f44); 
        }
        .header-search-bar-container { 
            display: flex; 
            align-items: center; 
            gap: 18px; 
        }
        .header-search-bar { 
            display: flex; 
            gap: 0; 
        }
        .search-bar input[type="search"] {
            color: #222;
            background: #fff;
            border: 1px solid #bbb;
            border-radius: 6px;
            padding: 6px 12px;
        }
        .search-bar button {
            color: #fff;
            background: #0e2f44;
            border: none;
            border-radius: 6px;
            padding: 6px 18px;
            font-weight: bold;
            margin-left: 8px;
        }
        .search-bar button:hover {
            background: #1a5276;
        }
        .stop-edit-btn {
            background: #f39c12;
            color: #fff;
            padding: 6px 14px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 15px;
            transition: all 0.3s;
            margin-left: 5px;
        }
        .stop-edit-btn:hover {
            background: #e67e22;
        }
    </style>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            let editingRow = null;
            let originalValues = {};

            // Function to make a cell editable
            function makeEditable(cell) {
                const value = $(cell).text().trim();
                const field = $(cell).data('field');
                originalValues[field] = value;

                if (field === 'genre') {
                    $(cell).html(`
                        <select class="editing">
                            <option value="M" ${value === 'M' ? 'selected' : ''}>Male</option>
                            <option value="F" ${value === 'F' ? 'selected' : ''}>Female</option>
                            <option value="O" ${value === 'O' ? 'selected' : ''}>Other</option>
                        </select>
                    `);
                } else {
                    $(cell).html(`<input type="text" class="editing" value="${value}">`);
                }
                $(cell).find('input, select').focus();
            }

            // Function to save changes
            function saveChanges(row) {
                const patientId = $(row).data('id');
                const data = {};
                
                $(row).find('.editable').each(function() {
                    const field = $(this).data('field');
                    const value = $(this).find('input, select').val();
                    data[field] = value;
                });

                if (confirm('Are you sure you want to save these changes?')) {
                    $.ajax({
                        url: 'update_patient.php',
                        method: 'POST',
                        data: {
                            patient_id: patientId,
                            ...data
                        },
                        success: function(response) {
                            const result = JSON.parse(response);
                            if (result.success) {
                                // Update the row with new values
                                Object.keys(data).forEach(field => {
                                    $(row).find(`[data-field="${field}"]`).html(data[field]);
                                });
                                alert('Patient information updated successfully!');
                            } else {
                                alert('Error updating patient: ' + result.message);
                                // Revert changes
                                Object.keys(originalValues).forEach(field => {
                                    $(row).find(`[data-field="${field}"]`).html(originalValues[field]);
                                });
                            }
                        },
                        error: function() {
                            alert('Error updating patient. Please try again.');
                            // Revert changes
                            Object.keys(originalValues).forEach(field => {
                                $(row).find(`[data-field="${field}"]`).html(originalValues[field]);
                            });
                        }
                    });
                }
                
                // Reset editing state
                $(row).removeClass('editing');
                editingRow = null;
                originalValues = {};
                updateActionButtons(row);
            }

            // Function to cancel editing
            function cancelEditing(row) {
                // Restore original values
                Object.keys(originalValues).forEach(field => {
                    $(row).find(`[data-field="${field}"]`).html(originalValues[field]);
                });
                
                // Reset editing state
                $(row).removeClass('editing');
                editingRow = null;
                originalValues = {};
                updateActionButtons(row);
            }

            // Function to update action buttons
            function updateActionButtons(row) {
                const isEditing = $(row).hasClass('editing');
                const buttons = $(row).find('.action-buttons');
                const patientId = $(row).data('id');
                
                if (isEditing) {
                    buttons.html(`
                        <button class="save-btn" onclick="saveChanges($(this).closest('tr'))">
                            <i class="fa-solid fa-check"></i> Save
                        </button>
                        <button class="cancel-btn" onclick="cancelEditing($(this).closest('tr'))">
                            <i class="fa-solid fa-times"></i> Cancel
                        </button>
                    `);
                } else {
                    buttons.html(`
                        <button class="edit-btn" onclick="startEditing($(this).closest('tr'))">
                            <i class="fa-solid fa-pen-to-square"></i> Edit
                        </button>
                        <button class="stop-edit-btn" onclick="stopEdit($(this).closest('tr'))">
                            <i class="fa-solid fa-check-double"></i> Terminer
                        </button>
                        <form method="post" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this patient?');">
                            <input type="hidden" name="patient_id" value="${patientId}">
                            <button type="submit" name="delete_patient" class="delete-btn">
                                <i class="fa-solid fa-trash"></i> Delete
                            </button>
                        </form>
                    `);
                }
            }

            // Make these functions available globally
            window.startEditing = function(row) {
                if (editingRow) {
                    if (confirm('You have unsaved changes. Do you want to discard them?')) {
                        cancelEditing(editingRow);
                    } else {
                        return;
                    }
                }
                
                editingRow = row;
                $(row).addClass('editing-row');
                $(row).find('.editable').each(function() {
                    makeEditable(this);
                });
                updateActionButtons(row);
            };

            window.saveChanges = saveChanges;
            window.cancelEditing = cancelEditing;
            window.stopEdit = function(row) {
                if ($(row).hasClass('editing')) {
                    saveChanges(row);
                }
            };
        });
    </script>
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
                    <a href="charts.php">
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
                        <h1>Patients Management</h1>
                        <span class="subtitle">View and manage all patients</span>
                    </div>
                </div>
                <div class="header-center">
                    <form action="" method="get" class="search-bar">
                        <input type="search" name="search" placeholder="Search patients">
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

            <button class="add-patient-btn" onclick="window.location.href='add_patient.php'">
                <i class="fa-solid fa-plus"></i> Add New Patient
            </button>

            <?php if (isset($error)): ?>
                <div class="error-message" style="color: #e74c3c; margin-bottom: 20px;">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($success)): ?>
                <div class="success-message" style="color: #2ecc71; margin-bottom: 20px;">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <div class="illness-list">
                <table class="patients-table">
                    <thead>
                        <tr>
                            <td>ID</td>
                            <td>Nom</td>
                            <td>Email</td>
                            <td>Date de naissance</td>
                            <td colspan="2">Actions</td>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (isset($patients) && !empty($patients)): ?>
                            <?php foreach ($patients as $patient): ?>
                                <tr data-id="<?php echo $patient['user_id']; ?>">
                                    <td><?php echo $patient['user_id']; ?></td>
                                    <td class="editable" data-field="nom"><?php echo htmlspecialchars($patient['nom']); ?></td>
                                    <td class="editable" data-field="email"><?php echo htmlspecialchars($patient['email']); ?></td>
                                    <td class="editable" data-field="date_naissance"><?php echo htmlspecialchars($patient['date_naissance']); ?></td>
                                    <td colspan="2" class="action-buttons">
                                        <button class="edit-btn" onclick="startEditing($(this).closest('tr'))">
                                            <i class="fa-solid fa-pen-to-square"></i> Edit
                                        </button>
                                        <button class="stop-edit-btn" onclick="stopEdit($(this).closest('tr'))">
                                            <i class="fa-solid fa-check-double"></i> Terminer
                                        </button>
                                        <form method="post" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this patient?');">
                                            <input type="hidden" name="patient_id" value="<?php echo $patient['user_id']; ?>">
                                            <button type="submit" name="delete_patient" class="delete-btn">
                                                <i class="fa-solid fa-trash"></i> Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5">No patients found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <script src="../index.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const toggles = document.querySelectorAll(".listted");

            toggles.forEach(function(toggle) {
                toggle.addEventListener("click", function(e) {
                    e.preventDefault();
                    const submenu = toggle.nextElementSibling;

                    if (submenu) {
                        submenu.style.display = submenu.style.display === "block" ? "none" : "block";
                    }

                    toggles.forEach(function(otherToggle) {
                        if (otherToggle !== toggle) {
                            const otherSubmenu = otherToggle.nextElementSibling;
                            if (otherSubmenu) {
                                otherSubmenu.style.display = "none";
                            }
                        }
                    });
                });
            });
        });
    </script>
</body>
</html> 