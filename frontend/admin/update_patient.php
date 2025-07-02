<?php
require_once '../../backend/auth/session_handler.php';
checkRole('admin');

header('Content-Type: application/json');

try {
    $db = new PDO("mysql:host=localhost;dbname=medical_system", "root", "");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Start transaction
    $db->beginTransaction();
    
    $patient_id = $_POST['patient_id'];
    $date_naissance = isset($_POST['date_naissance']) ? trim($_POST['date_naissance']) : '';
    $today = date('Y-m-d');
    if (empty($date_naissance) || $date_naissance === $today) {
        throw new Exception('Date de naissance invalide ou égale à aujourd\'hui.');
    }
    
    // Update utilisateur table
    $query = "UPDATE utilisateur SET 
              nom = :nom,
              email = :email
              WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->execute([
        ':nom' => $_POST['nom'],
        ':email' => $_POST['email'],
        ':id' => $patient_id
    ]);
    
    // Update patient table
    $query = "UPDATE patient SET 
              date_naissance = :date_naissance,
              genre = :genre,
              telephone = :telephone
              WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->execute([
        ':date_naissance' => $date_naissance,
        ':genre' => $_POST['genre'],
        ':telephone' => $_POST['telephone'],
        ':id' => $patient_id
    ]);
    
    // Commit transaction
    $db->commit();
    
    echo json_encode(['success' => true]);
    
} catch(PDOException $e) {
    // Rollback transaction on error
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 