<?php
require_once '../config/database.php';
header('Content-Type: application/json');

if (!isset($_GET['patient_id'])) {
    echo json_encode(['error' => 'patient_id manquant']);
    exit;
}

$patient_id = intval($_GET['patient_id']);

// If dates_only is set, return unique dates only
if (isset($_GET['dates_only'])) {
    try {
        $stmt = $pdo->prepare('SELECT DISTINCT DATE(date_mesure) as date_only FROM mesure WHERE patient_id = ? ORDER BY date_only DESC');
        $stmt->execute([$patient_id]);
        $dates = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo json_encode($dates);
        exit;
    } catch (PDOException $e) {
        echo json_encode(['error' => $e->getMessage()]);
        exit;
    }
}

try {
    $stmt = $pdo->prepare('SELECT temperature, pulsation, date_mesure FROM mesure WHERE patient_id = ? ORDER BY date_mesure ASC');
    $stmt->execute([$patient_id]);
    $mesures = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($mesures);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}