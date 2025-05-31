<?php
require_once '../../backend/auth/session_handler.php';
require_once '../../backend/config/database.php'; // Include database connection
checkRole('admin');

// Common French medical specialties
$commonSpecialties = [
    'Anesthésie-Réanimation',
    'Cardiologie',
    'Chirurgie Cardiaque',
    'Chirurgie Générale',
    'Chirurgie Orthopédique',
    'Chirurgie Pédiatrique',
    'Chirurgie Plastique',
    'Chirurgie Thoracique',
    'Chirurgie Vasculaire',
    'Dermatologie',
    'Endocrinologie',
    'Gastro-entérologie',
    'Gynécologie-Obstétrique',
    'Hématologie',
    'Infectiologie',
    'Médecine Interne',
    'Néphrologie',
    'Neurologie',
    'Neurochirurgie',
    'Ophtalmologie',
    'ORL',
    'Pédiatrie',
    'Pneumologie',
    'Psychiatrie',
    'Radiologie',
    'Rhumatologie',
    'Urologie'
];

// Fetch distinct specialities from the medecin table
$existingSpecialities = [];
try {
    $stmt = $pdo->query("SELECT DISTINCT specialite FROM medecin WHERE specialite IS NOT NULL AND specialite != '' ORDER BY specialite");
    $existingSpecialities = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    // Log error or handle it appropriately in the admin panel
    error_log("Admin Add Doctor: Failed to fetch existing specialities: " . $e->getMessage());
    // Optionally, display an error message on the page
    // $error_message = "Error loading specialties. Please try again later.";
}

// Combine database specialties with common specialties, removing duplicates
$allSpecialties = array_unique(array_merge($existingSpecialities, $commonSpecialties));
sort($allSpecialties); // Sort alphabetically

// --- Form Submission Handling for Adding Doctor and Specialty ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_doctor'])) {
    // Get doctor details
    $doc_name = $_POST['doc_name'];
    $doc_department = $_POST['doc_department']; // This is the specialty entered by the user
    $doc_mail = $_POST['doc_mail'];
    $doc_password = $_POST['doc_password'];
    $doc_re_password = $_POST['doc_re_password'];

    // Basic validation (you should add more robust validation)
    if ($doc_password !== $doc_re_password) {
        // Handle password mismatch error
        echo "<script>alert('Passwords do not match!');</script>";
    } else {
        // Check if specialty exists
        $speciality_exists = in_array($doc_department, $existingSpecialities);

        if (!$speciality_exists) {
            // --- ORTHOGRAPHY CHECK REQUIRED HERE ---
            // Before inserting a new specialty, you would implement your orthography check logic here.
            // If the orthography is incorrect, you might prompt the user to correct it or select from suggestions.
            // For now, we will proceed assuming the orthography is correct or skip the check.
            echo "<script>alert('Specialty \'" . htmlspecialchars($doc_department) . "\' is new. Orthography check needed here.');</script>";

            // If it's a new specialty and orthography is verified (or skipped):
            try {
                // Insert the new specialty into the medecin table (or a dedicated specialties table)
                // Note: This simple insert into 'medecin' assumes you are okay with specialties
                // being added this way. A separate 'specialties' table and foreign key
                // relationship is a better database design practice.
                $stmt_insert_specialty = $pdo->prepare("INSERT INTO medecin (specialite) VALUES (?)"); // Assuming medecin table can store just specialty
                $stmt_insert_specialty->execute([$doc_department]);
                // Note: If using a separate specialties table, you'd insert here and get the new specialty_id
            } catch (PDOException $e) {
                error_log("Admin Add Doctor: Failed to insert new specialty: " . $e->getMessage());
                echo "<script>alert('Error adding new specialty.');</script>";
                // Handle error (e.g., display user message)
            }
        }

        // --- Proceed with adding the doctor ---
        // Note: You should hash the password before storing it
        $hashed_password = password_hash($doc_password, PASSWORD_DEFAULT); // Hash the password

        try {
            // Prepare and execute the INSERT statement for the doctor
            // Adjust table and column names as per your database schema
            $stmt_add_doctor = $pdo->prepare("INSERT INTO medecin (nom, specialite, email, mot_de_passe) VALUES (?, ?, ?, ?)");
            // Note: If you used a separate specialties table, you'd link using the specialty_id here instead of the name
            $stmt_add_doctor->execute([$doc_name, $doc_department, $doc_mail, $hashed_password]);

            // Doctor added successfully
            echo "<script>alert('Doctor added successfully!');</script>";
            // Redirect to doctors list page or clear form
            // header('Location: doctors.php');
            // exit();

        } catch (PDOException $e) {
            error_log("Admin Add Doctor: Failed to add doctor: " . $e->getMessage());
            // Check if the error is due to duplicate email (if email is unique)
            if ($e->getCode() == 23000) { // SQLSTATE 23000 is for integrity constraint violation
                 echo "<script>alert('Error: Email already exists.');</script>";
            } else {
                 echo "<script>alert('Error adding doctor. Please try again.');</script>";
            }
            // Handle error (e.g., display user message)
        }
    }
}
// --- End of Form Submission Handling ---

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Add Doctor</title>
    <link rel="stylesheet" href="../css_files/master.css">
    <link rel="stylesheet" href="../css_files/all.min.css">
    <style>
        /* Styles for form inputs in add doctor page */
        .content .add-doctor .forms input {
            border: 1px solid black; /* Thin black border */
            height: 30px; /* Adjusted height based on image */
            /* Add other styles as needed */
        }

        /* Add other form-specific styles here if necessary */
         .content .add-doctor .forms form div {
            display: flex;
            align-items: center;
            margin-bottom: 15px; /* Spacing between form rows */
        }

        .content .add-doctor .forms form div label {
          font-weight: bold;
          color: white; /* Label color */
          margin-bottom: 0; /* Remove default margin-bottom */
          width: 180px; /* Fixed width for labels */
          transition: color 0.3s ease;
          margin-right: 15px; /* Space between label and input */
          text-align: left; /* Align label text to the left */
          flex-shrink: 0; /* Prevent label from shrinking */
        }

        .content .add-doctor .forms form div input[type="text"],
        .content .add-doctor .forms form div input[type="email"],
        .content .add-doctor .forms form div input[type="password"] {
          flex-grow: 1; /* Allow input to take remaining space */
          padding: 8px 10px; /* Reduced vertical padding, consistent horizontal padding */
          border: 1px solid #ccc; /* Standard border */
          border-radius: 5px; /* Rounded corners */
          box-sizing: border-box; /* Include padding and border in element's total width and height */
          margin-bottom: 0; /* Remove default margin-bottom */
          font-size: 16px; /* Readable font size */
          background-color: rgba(255, 255, 255, 0.8); /* Semi-transparent white background */
          color: #333; /* Dark text color */
          transition: border-color 0.3s ease, box-shadow 0.3s ease; /* Smooth transitions */
          height: 35px; /* Set a fixed height for the input fields */
          line-height: 1.5; /* Improve text vertical alignment */
        }

        .content .add-doctor .forms form div input[type="text"]:focus,
        .content .add-doctor .forms form div input[type="email"]:focus,
        .content .add-doctor .forms form div input[type="password"]:focus {
          outline: none;
          border-color: #0e2f44; /* Highlight color on focus */
          box-shadow: 0 0 5px rgba(14, 47, 68, 0.5); /* Subtle shadow on focus */
        }

        .content .add-doctor .forms form .save-button {
            display: flex;
            justify-content: center;
            margin-top: 30px;
        }

        .content .add-doctor .forms form .save-button button {
            width: 150px;
            padding: 10px 20px;
            border: none;
            background-color: #0e2f44;
            color: white;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            font-size: 16px;
            text-transform: uppercase;
            font-weight: bold;
        }

        .content .add-doctor .forms form .save-button button:hover {
            background-color: #1a5276;
        }

        /* Enhanced autocomplete styles */
        .content .add-doctor .forms form div input[type="text"] {
            position: relative;
        }

        .content .add-doctor .forms form div input[type="text"]:focus {
            outline: none;
            border-color: #0e2f44;
            box-shadow: 0 0 5px rgba(14, 47, 68, 0.5);
        }

        /* Style for the datalist dropdown */
        #specialtySuggestions {
            max-height: 200px;
            overflow-y: auto;
        }

        #specialtySuggestions option {
            padding: 8px;
            cursor: pointer;
        }

        #specialtySuggestions option:hover {
            background-color: #f0f0f0;
        }

        /* Add a custom style for the specialty input */
        #doc_department {
            text-transform: capitalize;
            font-size: 16px;
            padding: 8px 12px;
            width: 100%;
            border: 1px solid #ccc;
            border-radius: 4px;
            background-color: white;
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
                    <a class="listted" href="departments.php">
                        <i class="fa-solid fa-people-group fa-fw"></i>
                        <span>Spécialités</span>
                        <i class="fa-solid fa-angle-right tog"></i>
                    </a>
                    <div class="list two" style="display: none;">
                        <a href="departments.php">Voir les spécialités</a>
                        <a href="add_department.php">Ajouter une spécialité</a>
                    </div>
                </li>
                 <li>
                    <a href="patients.php">
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
            <div class="header pro-header">
                <div class="header-left">
                    <img src="../images/download__15__14-removebg-preview.png" alt="Logo" class="header-logo">
                    <div class="welcome">
                        <h1>Admin Panel</h1>
                        <span class="subtitle">Add New Doctor</span>
                    </div>
                </div>
                <div class="header-center">
                    <form action="" method="post" class="search-bar">
                        <input type="search" name="search_query" placeholder="Search...">
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
            
            <div class="add-doctor">
                <div class="title">
                    <h2>Ajouter un docteur</h2>
                    <img src="../images/download__15_-removebg-preview.png" alt="">
                </div>
                <div class="forms">
                    <form action="" method="post">
                        <div class="name">
                            <label for="doc_name">Nom du docteur</label>
                            <input type="text" name="doc_name" id="doc_name" placeholder="Nom" required>
                        </div>
                        <div class="department">
                            <label for="doc_department">Spécialité</label>
                            <input type="text" 
                                   name="doc_department" 
                                   id="doc_department" 
                                   list="specialtySuggestions" 
                                   placeholder="Entrez ou sélectionnez une spécialité" 
                                   autocomplete="off"
                                   required>
                            <datalist id="specialtySuggestions">
                                <?php foreach ($allSpecialties as $specialty): ?>
                                    <option value="<?php echo htmlspecialchars($specialty); ?>">
                                        <?php echo htmlspecialchars($specialty); ?>
                                    </option>
                                <?php endforeach; ?>
                            </datalist>
                        </div>
                        <div class="email">
                            <label for="doc_mail">Email</label>
                            <input type="email" name="doc_mail" id="doc_mail" placeholder="mail address" required>
                        </div>
                        <div class="password">
                            <label for="doc_password">Mot de passe</label>
                            <input type="password" name="doc_password" id="doc_password" placeholder="password" required>
                        </div>
                        <div class="re_password">
                            <label for="doc_re_password">Confirmer le mot de passe</label>
                            <input type="password" name="doc_re_password" id="doc_re_password" placeholder="Confirm password" required>
                        </div>
                        <div class="save-button">
                            <button type="submit" name="add_doctor">Enregistrer</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script src="../index.js"></script>
</body>
</html> 