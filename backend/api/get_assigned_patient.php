<?php
header("Content-Type: text/plain");

include '../config/database.php';

if (!isset($_GET['mac'])) {
    http_response_code(400); // Bad Request
    echo "0";
    exit;
}

$macAddress = $_GET['mac'];
$host = 'localhost';
$dbname = 'medical_system';
$username = 'root';
$password = '';
$conn = new mysqli("host=$host,dbname=$dbname", $username, $password);

if ($conn->connect_error) {
    http_response_code(500); // Internal Server Error
    echo "0";
    exit;
}

$stmt = $conn->prepare("SELECT patient_id FROM patient_devices WHERE mac_address = ?");
$stmt->bind_param("s", $macAddress);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo $row['patient_id'];
} else {
    echo "0"; // Indicates no patient assigned
}

$stmt->close();
$conn->close();
?> 