<?php
session_start();
include '../../backend/config/database.php'; // Adjust path as needed

$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $macAddress = $_POST['mac_address'];
    $patientId = $_POST['patient_id'];
    $host = 'localhost';
    $dbname = 'medical_system';
    $username = 'root';
    $password = '';
    if (empty($macAddress) || empty($patientId)) {
        $error = "All fields are required.";
    } else {
        $conn = new mysqli("host=$host,dbname=$dbname", $username, $password); 
        if ($conn->connect_error) {
            $error = "Connection failed: " . $conn->connect_error;
        } else {
            // Use INSERT ... ON DUPLICATE KEY UPDATE to handle both new and existing assignments
            $stmt = $conn->prepare("
                INSERT INTO patient_devices (mac_address, patient_id) 
                VALUES (?, ?) 
                ON DUPLICATE KEY UPDATE patient_id = ?
            ");
            $stmt->bind_param("sii", $macAddress, $patientId, $patientId);

            if ($stmt->execute()) {
                $message = "Device " . htmlspecialchars($macAddress) . " successfully assigned to patient ID " . htmlspecialchars($patientId) . ".";
            } else {
                $error = "Error: " . $stmt->error;
            }
            $stmt->close();
            $conn->close();
        }
    }
}

// Fetch all patients to populate the dropdown
$patients = [];
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if (!$conn->connect_error) {
    $result = $conn->query("SELECT id, nom, prenom FROM patients ORDER BY nom, prenom");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $patients[] = $row;
        }
    }
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assign Device to Patient</title>
    <link rel="stylesheet" href="../css_files/master.css" />
    <style>
        .container { max-width: 800px; margin: 20px auto; padding: 20px; background: #fff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"], select { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; }
        .btn { padding: 10px 15px; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; }
        .message { padding: 10px; margin-bottom: 15px; border-radius: 4px; color: #155724; background-color: #d4edda; }
        .error { padding: 10px; margin-bottom: 15px; border-radius: 4px; color: #721c24; background-color: #f8d7da; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Assign IoT Device to Patient</h2>
        <p>
            Enter the MAC Address printed by the IoT device in the serial monitor and select the patient to assign it to.
        </p>

        <?php if ($message): ?>
            <div class="message"><?php echo $message; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>

        <form action="assign_device.php" method="POST">
            <div class="form-group">
                <label for="mac_address">Device MAC Address</label>
                <input type="text" id="mac_address" name="mac_address" required placeholder="e.g., A8:03:2A:D6:89:C0">
            </div>
            <div class="form-group">
                <label for="patient_id">Select Patient</label>
                <select id="patient_id" name="patient_id" required>
                    <option value="">-- Choose a patient --</option>
                    <?php foreach ($patients as $patient): ?>
                        <option value="<?php echo htmlspecialchars($patient['id']); ?>">
                            <?php echo htmlspecialchars($patient['prenom'] . ' ' . $patient['nom'] . ' (ID: ' . $patient['id'] . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn">Assign Device</button>
        </form>
    </div>
</body>
</html> 