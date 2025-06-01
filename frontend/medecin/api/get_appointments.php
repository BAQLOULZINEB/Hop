<?php
require_once '../../../backend/auth/session_handler.php';
checkRole('medecin');
header('Content-Type: application/json');

// Get current user
$current_user = $_SESSION['user'];

// Get date range from request
$start_date = isset($_GET['start']) ? $_GET['start'] : date('Y-m-d');
$end_date = isset($_GET['end']) ? $_GET['end'] : date('Y-m-d', strtotime('+1 month'));

// Color palette for appointments (repeatable)
$colors = [
    ['bg' => '#2ecc71', 'border' => '#27ae60'], // green
    ['bg' => '#3498db', 'border' => '#2980b9'], // blue
    ['bg' => '#e67e22', 'border' => '#d35400'], // orange
    ['bg' => '#e74c3c', 'border' => '#c0392b'], // red
    ['bg' => '#9b59b6', 'border' => '#8e44ad'], // purple
    ['bg' => '#f1c40f', 'border' => '#f39c12'], // yellow
    ['bg' => '#16a085', 'border' => '#138d75'], // teal
    ['bg' => '#34495e', 'border' => '#2c3e50'], // dark blue
];

try {
    $db = new PDO("mysql:host=localhost;dbname=medical_system", "root", "");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get appointments for the doctor (match logic from appointments.php)
    $query = "SELECT 
        u.id as patient_id,
        u.nom as patient_name,
        u.email as patient_email,
        r.date_rendezvous,
        r.statut
        FROM rendez_vous r
        JOIN patient p ON r.patient_id = p.id
        JOIN utilisateur u ON p.id = u.id
        WHERE r.medecin_id = :medecin_id 
        AND r.statut = 'confirmé'
        AND DATE(r.date_rendezvous) BETWEEN :start_date AND :end_date
        ORDER BY r.date_rendezvous ASC";
    
    $stmt = $db->prepare($query);
    $stmt->execute([
        ':medecin_id' => $current_user['id'],
        ':start_date' => $start_date,
        ':end_date' => $end_date
    ]);
    
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format appointments for calendar
    $calendar_events = array_map(function($apt) use ($colors) {
        $start = new DateTime($apt['date_rendezvous']);
        $end = clone $start;
        $end->modify('+30 minutes'); // Default appointment duration
        // Assign color by patient_id
        $colorIdx = $apt['patient_id'] % count($colors);
        $color = $colors[$colorIdx];
        // Info for popup
        $body =
            '<b>Patient:</b> ' . htmlspecialchars($apt['patient_name']) . '<br>' .
            '<b>Email:</b> ' . htmlspecialchars($apt['patient_email']) . '<br>' .
            '<b>Time:</b> ' . $start->format('H:i') . '<br>' .
            '<b>Status:</b> ' . htmlspecialchars($apt['statut']);
        return [
            'id' => $apt['patient_id'] . '_' . $start->format('YmdHi'),
            'calendarId' => 'appointment-consultation',
            'title' => $apt['patient_name'],
            'body' => $body,
            'category' => 'time',
            'start' => $start->format('Y-m-d\TH:i:s'),
            'end' => $end->format('Y-m-d\TH:i:s'),
            'isReadOnly' => true,
            'color' => '#fff',
            'backgroundColor' => $color['bg'],
            'borderColor' => $color['border'],
            'customStyle' => 'cursor: pointer;',
            'raw' => [
                'patient_id' => $apt['patient_id'],
                'patient_email' => $apt['patient_email'],
                'statut' => $apt['statut']
            ]
        ];
    }, $appointments);
    
    echo json_encode($calendar_events);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?> 