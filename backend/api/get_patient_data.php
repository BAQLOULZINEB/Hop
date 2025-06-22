<?php
// api/get_patient_data.php
header('Content-Type: application/json');
$host = 'localhost';
$dbname = 'medical_system';
$username = 'root';
$password = '';
// Database connection
$pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);

// Get patient ID or name from the request
$patient = $_GET['patient'] ?? '';

// Fetch data from the database
$stmt = $pdo->prepare("SELECT timestamp, temperature, heartRate FROM mesure WHERE patientID = ? OR patientName = ? ORDER BY timestamp ASC");
$stmt->execute([$patient, $patient]);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Return data as JSON
echo json_encode($data); 