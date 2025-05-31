<?php
require_once '../../../backend/DB/medical_system.sql';
require_once '../../../backend/auth/session_handler.php';

header('Content-Type: application/json');

// Check if user is logged in and is a doctor
checkRole('medecin');

// Get current user
$current_user = $_SESSION['user'];

// Validate input
if (!isset($_POST['patient_id']) || !isset($_POST['date_rendezvous'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Champs requis manquants']);
    exit;
}

try {
    $db = new PDO("mysql:host=localhost;dbname=medical_system", "root", "");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check for appointment conflicts
    $check_sql = "SELECT COUNT(*) FROM rendez_vous 
                  WHERE medecin_id = :medecin_id 
                  AND date_rendezvous = :date_rendezvous 
                  AND statut != 'annulé'";
    
    $check_stmt = $db->prepare($check_sql);
    $check_stmt->execute([
        ':medecin_id' => $current_user['id'],
        ':date_rendezvous' => $_POST['date_rendezvous']
    ]);
    
    if ($check_stmt->fetchColumn() > 0) {
        http_response_code(409);
        echo json_encode(['error' => 'Ce créneau horaire est déjà réservé']);
        exit;
    }
    
    // Insert new appointment
    $sql = "INSERT INTO rendez_vous (patient_id, medecin_id, date_rendezvous, statut) 
            VALUES (:patient_id, :medecin_id, :date_rendezvous, 'en attente')";
    
    $stmt = $db->prepare($sql);
    $result = $stmt->execute([
        ':patient_id' => $_POST['patient_id'],
        ':medecin_id' => $current_user['id'],
        ':date_rendezvous' => $_POST['date_rendezvous']
    ]);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Rendez-vous créé avec succès']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Échec de la création du rendez-vous']);
    }
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?> 