<?php
require_once '../../../backend/DB/medical_system.sql';
require_once '../../../backend/auth/session_handler.php';

header('Content-Type: application/json');

// Check if user is logged in and is a doctor
checkRole('medecin');

// Get current user
$current_user = $_SESSION['user'];

// Get date range from request
$start_date = isset($_GET['start']) ? $_GET['start'] : date('Y-m-d');
$end_date = isset($_GET['end']) ? $_GET['end'] : date('Y-m-d', strtotime('+1 month'));

try {
    $db = new PDO("mysql:host=localhost;dbname=medical_system", "root", "");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get appointments for the doctor
    $query = "SELECT rv.*, u.nom as patient_nom, m.specialite 
              FROM rendez_vous rv 
              JOIN utilisateur u ON rv.patient_id = u.id 
              JOIN medecin m ON rv.medecin_id = m.id 
              WHERE rv.medecin_id = :medecin_id 
              AND rv.date_rendezvous BETWEEN :start_date AND :end_date";
    
    $stmt = $db->prepare($query);
    $stmt->execute([
        ':medecin_id' => $current_user['id'],
        ':start_date' => $start_date,
        ':end_date' => $end_date
    ]);
    
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format appointments for calendar
    $calendar_events = array_map(function($apt) {
        return [
            'id' => $apt['patient_id'] . '_' . $apt['medecin_id'] . '_' . strtotime($apt['date_rendezvous']),
            'calendarId' => 'medecin',
            'title' => $apt['patient_nom'],
            'category' => 'time',
            'start' => $apt['date_rendezvous'],
            'end' => date('Y-m-d H:i:s', strtotime($apt['date_rendezvous'] . ' +30 minutes')),
            'isReadOnly' => false,
            'color' => $apt['statut'] == 'confirmé' ? '#27ae60' : '#f1c40f',
            'backgroundColor' => $apt['statut'] == 'confirmé' ? '#27ae60' : '#f1c40f',
            'borderColor' => $apt['statut'] == 'confirmé' ? '#27ae60' : '#f1c40f',
            'customStyle' => 'cursor: pointer;',
            'raw' => [
                'patient_id' => $apt['patient_id'],
                'specialite' => $apt['specialite'],
                'statut' => $apt['statut']
            ]
        ];
    }, $appointments);
    
    echo json_encode($calendar_events);
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?> 