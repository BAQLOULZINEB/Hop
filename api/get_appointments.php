<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/appointment.php';

header('Content-Type: application/json');

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Initialize auth
$auth = new Auth($db);
$auth->requireLogin();

// Get current user
$current_user = $auth->getCurrentUser();

// Initialize appointment manager
$appointment = new Appointment($db);

// Get date range from request
$start_date = isset($_GET['start']) ? $_GET['start'] : date('Y-m-d');
$end_date = isset($_GET['end']) ? $_GET['end'] : date('Y-m-d', strtotime('+1 month'));

// Get appointments
$appointments = $appointment->getAppointments($current_user['id'], $start_date, $end_date);

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
?> 