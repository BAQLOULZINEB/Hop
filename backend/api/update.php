<?php
header('Content-Type: application/json');
$host = 'localhost';
$dbname = 'medical_system';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if (
        !isset($_POST['patientID']) ||
        !isset($_POST['temperature']) ||
        !isset($_POST['heartRate'])
    ) {
        echo json_encode(['success' => false, 'message' => 'Paramètres manquants']);
        exit;
    }

    $patient_id = intval($_POST['patientID']);
    $temperature = floatval($_POST['temperature']);
    $heartRate = intval($_POST['heartRate']);
    $date = date('Y-m-d H:i:s');

    $stmt = $pdo->prepare("INSERT INTO mesure (patient_id, temperature, pulsation, date_mesure) VALUES (?, ?, ?, ?)");
    $stmt->execute([$patient_id, $temperature, $heartRate, $date]);

    echo json_encode(['success' => true, 'message' => 'Mesure enregistrée']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur BDD: ' . $e->getMessage()]);
}