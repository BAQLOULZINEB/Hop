<?php
require_once '../../../backend/auth/session_handler.php';
checkRole('medecin');

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get current user data
$currentUser = null;
$medecin_id = null;

if (isset($_SESSION['user'])) {
    $currentUser = $_SESSION['user'];
    $medecin_id = $currentUser['id'];
} elseif (isset($_SESSION['user_id'])) {
    try {
        $db = new PDO("mysql:host=localhost;dbname=medical_system", "root", "");
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->exec("SET NAMES utf8mb4");
        
        $stmt = $db->prepare("SELECT * FROM utilisateur WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $currentUser = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($currentUser) {
            $medecin_id = $currentUser['id'];
        }
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
        exit();
    }
}

if (!$currentUser || !$medecin_id) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

try {
    $db = new PDO("mysql:host=localhost;dbname=medical_system", "root", "");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->exec("SET NAMES utf8mb4");
    
    // Get today's patients count (unique patients with appointments today)
    $query = "SELECT COUNT(DISTINCT r.patient_id) as today_patients
              FROM rendez_vous r
              WHERE r.medecin_id = :medecin_id 
              AND DATE(r.date_rendezvous) = CURDATE()
              AND r.statut = 'confirmé'";
    $stmt = $db->prepare($query);
    $stmt->execute([':medecin_id' => $medecin_id]);
    $todayPatients = $stmt->fetch(PDO::FETCH_ASSOC)['today_patients'];
    
    // Get total appointments count for this doctor
    $query = "SELECT COUNT(*) as total_appointments
              FROM rendez_vous r
              WHERE r.medecin_id = :medecin_id 
              AND r.statut = 'confirmé'";
    $stmt = $db->prepare($query);
    $stmt->execute([':medecin_id' => $medecin_id]);
    $totalAppointments = $stmt->fetch(PDO::FETCH_ASSOC)['total_appointments'];
    
    // Get today's appointments for the table
    $query = "SELECT 
                u.nom as patient_name,
                u.email as patient_email,
                r.date_rendezvous,
                r.statut,
                'Consultation' as appointment_type
              FROM rendez_vous r
              JOIN patient p ON r.patient_id = p.id
              JOIN utilisateur u ON p.id = u.id
              WHERE r.medecin_id = :medecin_id 
              AND DATE(r.date_rendezvous) = CURDATE()
              AND r.statut = 'confirmé'
              ORDER BY r.date_rendezvous ASC";
    $stmt = $db->prepare($query);
    $stmt->execute([':medecin_id' => $medecin_id]);
    $todayAppointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $response = [
        'success' => true,
        'stats' => [
            'today_patients' => (int)$todayPatients,
            'total_appointments' => (int)$totalAppointments
        ],
        'today_appointments' => $todayAppointments
    ];
    
    echo json_encode($response);
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?> 