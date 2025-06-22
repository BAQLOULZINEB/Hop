<?php
// Test data script for medical system
// This script adds sample data to test the doctor dashboard statistics

try {
    $db = new PDO("mysql:host=localhost;dbname=medical_system", "root", "");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->exec("SET NAMES utf8mb4");
    
    echo "Adding test data to medical system...\n";
    
    // Check if test data already exists
    $stmt = $db->query("SELECT COUNT(*) as count FROM utilisateur WHERE role = 'medecin'");
    $doctorCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($doctorCount > 0) {
        echo "Test data already exists. Skipping...\n";
        exit();
    }
    
    // Add test users
    $users = [
        ['nom' => 'Dr. John Smith', 'email' => 'john.smith@hospital.com', 'mot_de_passe' => password_hash('password123', PASSWORD_DEFAULT), 'role' => 'medecin'],
        ['nom' => 'Dr. Sarah Johnson', 'email' => 'sarah.johnson@hospital.com', 'mot_de_passe' => password_hash('password123', PASSWORD_DEFAULT), 'role' => 'medecin'],
        ['nom' => 'Alice Brown', 'email' => 'alice.brown@email.com', 'mot_de_passe' => password_hash('password123', PASSWORD_DEFAULT), 'role' => 'patient'],
        ['nom' => 'Bob Wilson', 'email' => 'bob.wilson@email.com', 'mot_de_passe' => password_hash('password123', PASSWORD_DEFAULT), 'role' => 'patient'],
        ['nom' => 'Carol Davis', 'email' => 'carol.davis@email.com', 'mot_de_passe' => password_hash('password123', PASSWORD_DEFAULT), 'role' => 'patient'],
        ['nom' => 'David Miller', 'email' => 'david.miller@email.com', 'mot_de_passe' => password_hash('password123', PASSWORD_DEFAULT), 'role' => 'patient'],
        ['nom' => 'Admin User', 'email' => 'admin@hospital.com', 'mot_de_passe' => password_hash('admin123', PASSWORD_DEFAULT), 'role' => 'admin']
    ];
    
    foreach ($users as $user) {
        $stmt = $db->prepare("INSERT INTO utilisateur (nom, email, mot_de_passe, role) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user['nom'], $user['email'], $user['mot_de_passe'], $user['role']]);
    }
    
    // Add doctors
    $stmt = $db->prepare("INSERT INTO medecin (id, specialite, disponibilite) VALUES (?, ?, ?)");
    $stmt->execute([1, 'Cardiology', 1]);
    $stmt->execute([2, 'Neurology', 1]);
    
    // Add patients
    $stmt = $db->prepare("INSERT INTO patient (id, date_naissance) VALUES (?, ?)");
    $stmt->execute([3, '1990-05-15']);
    $stmt->execute([4, '1985-12-20']);
    $stmt->execute([5, '1992-08-10']);
    $stmt->execute([6, '1988-03-25']);
    
    // Add admin
    $stmt = $db->prepare("INSERT INTO admin (id, privileges) VALUES (?, ?)");
    $stmt->execute([7, 'full_access']);
    
    // Add appointments (some for today, some for other days)
    $appointments = [
        [3, 1, 7, '2024-01-15 09:00:00', 'confirmé'], // Today
        [4, 1, 7, '2024-01-15 10:30:00', 'confirmé'], // Today
        [5, 1, 7, '2024-01-15 14:00:00', 'confirmé'], // Today
        [6, 2, 7, '2024-01-15 11:00:00', 'confirmé'], // Today
        [3, 1, 7, '2024-01-16 09:00:00', 'confirmé'], // Tomorrow
        [4, 1, 7, '2024-01-17 10:30:00', 'confirmé'], // Day after
        [5, 2, 7, '2024-01-14 14:00:00', 'confirmé'], // Yesterday
        [6, 2, 7, '2024-01-13 11:00:00', 'confirmé'], // Day before yesterday
    ];
    
    $stmt = $db->prepare("INSERT INTO rendez_vous (patient_id, medecin_id, admin_id, date_rendezvous, statut) VALUES (?, ?, ?, ?, ?)");
    foreach ($appointments as $apt) {
        $stmt->execute($apt);
    }
    
    // Add consultations (prescriptions)
    $consultations = [
        [1, 3, '2024-01-10 09:00:00', 'Regular checkup, patient in good health'],
        [1, 4, '2024-01-11 10:30:00', 'Follow-up consultation, blood pressure monitoring'],
        [1, 5, '2024-01-12 14:00:00', 'Initial consultation, prescribed medication'],
        [2, 6, '2024-01-13 11:00:00', 'Neurological examination, recommended tests'],
        [1, 3, '2024-01-08 09:00:00', 'Previous consultation notes'],
        [2, 4, '2024-01-09 10:30:00', 'Previous consultation notes'],
    ];
    
    $stmt = $db->prepare("INSERT INTO consultation (medecin_id, patient_id, date_consultation, remarques) VALUES (?, ?, ?, ?)");
    foreach ($consultations as $cons) {
        $stmt->execute($cons);
    }
    
    // Add recommendations (additional prescriptions)
    $recommendations = [
        [1, 3, '2024-01-10', 'Regular exercise and healthy diet'],
        [1, 4, '2024-01-11', 'Reduce salt intake, monitor blood pressure'],
        [1, 5, '2024-01-12', 'Take prescribed medication as directed'],
        [2, 6, '2024-01-13', 'Schedule MRI scan for further diagnosis'],
        [1, 3, '2024-01-08', 'Previous recommendations'],
        [2, 4, '2024-01-09', 'Previous recommendations'],
    ];
    
    $stmt = $db->prepare("INSERT INTO recommendation (medecin_id, patient_id, date_recommandation, motif) VALUES (?, ?, ?, ?)");
    foreach ($recommendations as $rec) {
        $stmt->execute($rec);
    }
    
    // Add some measurements
    $measurements = [
        [3, 36.8, 72, '2024-01-15 08:30:00'],
        [4, 37.2, 85, '2024-01-15 10:00:00'],
        [5, 36.9, 68, '2024-01-15 13:30:00'],
        [6, 37.1, 78, '2024-01-15 10:30:00'],
    ];
    
    $stmt = $db->prepare("INSERT INTO mesure (patient_id, temperature, pulsation, date_mesure) VALUES (?, ?, ?, ?)");
    foreach ($measurements as $mes) {
        $stmt->execute($mes);
    }
    
    echo "Test data added successfully!\n";
    echo "You can now login with:\n";
    echo "Doctor: john.smith@hospital.com / password123\n";
    echo "Doctor: sarah.johnson@hospital.com / password123\n";
    echo "Admin: admin@hospital.com / admin123\n";
    echo "Patients: alice.brown@email.com / password123\n";
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?> 