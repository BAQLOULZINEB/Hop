<?php
require_once '../../../backend/auth/session_handler.php';
checkRole('medecin');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit();
}

try {
    $db = new PDO("mysql:host=localhost;dbname=medical_system", "root", "");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->exec("SET NAMES utf8mb4");

    $consultation_id = $_POST['consultation_id'] ?? null;
    $remarques = $_POST['remarques'] ?? '';
    $date_consultation = $_POST['date_consultation'] ?? null;
    $medecin_id = $_SESSION['user']['id'];

    // Debug logging
    error_log("Update consultation request: " . json_encode($_POST));

    if (!$consultation_id) {
        throw new Exception('Consultation ID is required');
    }

    // Validate date format
    if ($date_consultation) {
        $date_obj = DateTime::createFromFormat('Y-m-d\TH:i', $date_consultation);
        if (!$date_obj) {
            throw new Exception('Invalid date format');
        }
        $formatted_date = $date_obj->format('Y-m-d H:i:s');
    }

    // First, check if the remarques column exists, if not add it
    $checkColumn = $db->query("SHOW COLUMNS FROM consultation LIKE 'remarques'");
    if ($checkColumn->rowCount() == 0) {
        $db->exec("ALTER TABLE consultation ADD COLUMN remarques TEXT");
    }

    // Update the consultation
    if ($date_consultation) {
        $sql = "UPDATE consultation 
                SET remarques = :remarques, date_consultation = :date_consultation 
                WHERE id = :consultation_id 
                AND medecin_id = :medecin_id";
        
        $stmt = $db->prepare($sql);
        $result = $stmt->execute([
            ':remarques' => $remarques,
            ':date_consultation' => $formatted_date,
            ':consultation_id' => $consultation_id,
            ':medecin_id' => $medecin_id
        ]);
    } else {
        $sql = "UPDATE consultation 
                SET remarques = :remarques 
                WHERE id = :consultation_id 
                AND medecin_id = :medecin_id";
        
        $stmt = $db->prepare($sql);
        $result = $stmt->execute([
            ':remarques' => $remarques,
            ':consultation_id' => $consultation_id,
            ':medecin_id' => $medecin_id
        ]);
    }

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Consultation updated successfully']);
    } else {
        echo json_encode(['success' => false, 'error' => 'No consultation found or you are not authorized to edit this consultation']);
    }

} catch (PDOException $e) {
    error_log("Database error in update_consultation.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("Error in update_consultation.php: " . $e->getMessage());
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?> 