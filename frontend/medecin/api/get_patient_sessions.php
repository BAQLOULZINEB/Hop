<?php
// api/get_patient_sessions.php
header('Content-Type: application/json');
session_start();

// Security checks
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'medecin') {
    http_response_code(403); // Forbidden
    echo json_encode(['error' => 'Accès non autorisé.']);
    exit();
}

if (!isset($_GET['patient_id'])) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'ID du patient manquant.']);
    exit();
}

$patient_id = $_GET['patient_id'];
$medecin_id = $_SESSION['user_id'];

require_once '../../../backend/config/database.php';

try {
    $conn = new PDO($dsn, $user, $pass, $options);
    
    // Security enhancement: Verify the doctor is allowed to see this patient's data.
    // A doctor can see a patient if they have an appointment together.
    $checkStmt = $conn->prepare("
        SELECT 1 FROM rendezvous 
        WHERE id_medecin = :medecin_id AND id_patient = :patient_id
        LIMIT 1
    ");
    $checkStmt->execute([':medecin_id' => $medecin_id, ':patient_id' => $patient_id]);
    
    if ($checkStmt->fetchColumn() === false) {
        http_response_code(403);
        echo json_encode(['error' => 'Vous n\'êtes pas autorisé à voir les données de ce patient.']);
        exit();
    }

    // Fetch distinct recording days (sessions)
    $stmt = $conn->prepare("
        SELECT DISTINCT DATE(horodatage) as recording_day 
        FROM mesures 
        WHERE id_patient = :patient_id 
        ORDER BY recording_day DESC
    ");
    $stmt->execute([':patient_id' => $patient_id]);
    
    $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['sessions' => $sessions]);

} catch (PDOException $e) {
    http_response_code(500); // Internal Server Error
    error_log("Session Fetch Error: " . $e->getMessage());
    echo json_encode(['error' => 'Erreur de base de données lors de la récupération des sessions.']);
} 