<?php
require_once '../../backend/auth/session_handler.php';
checkRole('medecin');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header('Location: ../../frontend/Authentification.php');
    exit();
}
header('Content-Type: application/json');

try {
    $db = new PDO("mysql:host=localhost;dbname=medical_system", "root", "");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->exec("SET NAMES utf8mb4");

    // Check if remarques column exists, if not add it
    $checkColumn = $db->query("SHOW COLUMNS FROM consultation LIKE 'remarques'");
    if ($checkColumn->rowCount() == 0) {
        $db->exec("ALTER TABLE consultation ADD COLUMN remarques TEXT");
    }

    // Start transaction
    $db->beginTransaction();

    // Insert into consultation table with remarques
    $sql = "INSERT INTO consultation (patient_id, medecin_id, date_consultation, remarques) 
            VALUES (:patient_id, :medecin_id, NOW(), :remarques)";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':patient_id' => $_POST['patient_id'],
        ':medecin_id' => $_SESSION['user']['id'],
        ':remarques' => $_POST['remarques']
    ]);

    // If next appointment date is provided, create a new rendez_vous
    if (!empty($_POST['next_rdv'])) {
        $sql = "INSERT INTO rendez_vous (patient_id, medecin_id, date_rendezvous, statut) 
                VALUES (:patient_id, :medecin_id, :date_rendezvous, 'planifié')";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':patient_id' => $_POST['patient_id'],
            ':medecin_id' => $_SESSION['user']['id'],
            ':date_rendezvous' => $_POST['next_rdv']
        ]);
    }

    $db->commit();
    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    if (isset($db)) {
        $db->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
