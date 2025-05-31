<?php
require_once '../../backend/auth/session_handler.php';
checkRole('admin');

header('Content-Type: application/json');

try {
    $db = new PDO("mysql:host=localhost;dbname=medical_system", "root", "");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get distinct specialties
    $query = "SELECT DISTINCT specialite FROM medecin WHERE specialite IS NOT NULL AND specialite != '' ORDER BY specialite";
    $stmt = $db->query($query);
    $specialties = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo json_encode($specialties);
} catch (Exception $e) {
    echo json_encode([
        'error' => true,
        'message' => $e->getMessage()
    ]);
} 