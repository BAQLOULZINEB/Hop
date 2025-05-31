<?php
require_once '../../../backend/DB/medical_system.sql';
require_once '../../../backend/auth/session_handler.php';

header('Content-Type: application/json');

// Check if user is logged in and is a doctor
checkRole('medecin');

// Get search query
$query = isset($_GET['q']) ? $_GET['q'] : '';

if (strlen($query) < 2) {
    echo json_encode([]);
    exit;
}

try {
    $db = new PDO("mysql:host=localhost;dbname=medical_system", "root", "");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Search patients
    $sql = "SELECT u.id, u.nom, p.date_naissance, u.email 
            FROM utilisateur u 
            JOIN patient p ON u.id = p.id 
            WHERE u.role = 'patient' 
            AND (u.nom LIKE :query OR u.email LIKE :query) 
            LIMIT 10";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([':query' => "%$query%"]);
    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format response
    $formatted_patients = array_map(function($patient) {
        $age = date_diff(date_create($patient['date_naissance']), date_create('today'))->y;
        return [
            'id' => $patient['id'],
            'full_name' => $patient['nom'],
            'age' => $age,
            'email' => $patient['email']
        ];
    }, $patients);
    
    echo json_encode($formatted_patients);
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?> 