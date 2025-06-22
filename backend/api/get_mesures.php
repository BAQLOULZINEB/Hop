<?php
require_once '../config/database.php';
header('Content-Type: application/json');

if (!isset($_GET['patient_id'])) {
    echo json_encode(['error' => 'patient_id manquant']);
    exit;
}

$patient_id = intval($_GET['patient_id']);

try {
    $stmt = $pdo->prepare('SELECT temperature, pulsation, date_mesure FROM mesure WHERE patient_id = ? ORDER BY date_mesure ASC');
    $stmt->execute([$patient_id]);
    $mesures = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($mesures);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}