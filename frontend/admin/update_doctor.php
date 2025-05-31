<?php
require_once '../../backend/auth/session_handler.php';
checkRole('admin');

header('Content-Type: application/json');

try {
    $db = new PDO("mysql:host=localhost;dbname=medical_system", "root", "");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $doctor_id = $_POST['doctor_id'];
        $nom = $_POST['nom'];
        $email = $_POST['email'];
        $specialite = $_POST['specialite'];
        $disponibilite = $_POST['disponibilite'] === 'Available' ? 1 : 0;
        
        // Start transaction
        $db->beginTransaction();
        
        // Update utilisateur table
        $query = "UPDATE utilisateur SET nom = :nom, email = :email WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->execute([
            ':nom' => $nom,
            ':email' => $email,
            ':id' => $doctor_id
        ]);
        
        // Update medecin table
        $query = "UPDATE medecin SET specialite = :specialite, disponibilite = :disponibilite WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->execute([
            ':specialite' => $specialite,
            ':disponibilite' => $disponibilite,
            ':id' => $doctor_id
        ]);
        
        // Commit transaction
        $db->commit();
        
        echo json_encode(['success' => true]);
    } else {
        throw new Exception('Invalid request method');
    }
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 