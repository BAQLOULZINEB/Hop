<?php
require_once '../../backend/auth/session_handler.php';
checkRole('medecin');

$doctor_name = htmlspecialchars($_SESSION['user']['nom']);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header('Location: ../../frontend/Authentification.php');
    exit();
}

// Get patient ID from URL parameter
$patient_id = isset($_GET['patient_id']) ? intval($_GET['patient_id']) : null;
$selected_date = isset($_GET['date']) ? $_GET['date'] : null;

// Get patient information if patient_id is provided
$patient_info = null;
if ($patient_id) {
    try {
        $db = new PDO("mysql:host=localhost;dbname=medical_system", "root", "");
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->exec("SET NAMES utf8mb4");
        
        $sql = "SELECT u.id, u.nom, u.email, p.date_naissance 
                FROM patient p 
                JOIN utilisateur u ON p.id = u.id 
                WHERE u.id = :patient_id";
        $stmt = $db->prepare($sql);
        $stmt->execute([':patient_id' => $patient_id]);
        $patient_info = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error = 'Erreur: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html dir="ltr" lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- main css file -->
    <link rel="preload" href="../images/background_page.jpg" as="image">
    <link rel="stylesheet" href="../css_files/master.css">
    <!-- font awesome -->
    <link rel="stylesheet" href="../css_files/all.min.css">
    <!-- fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Arabic:wght@100..900&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <title>Signes Vitaux - HopCare</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .vital-signs-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            margin: 20px;
            padding: 30px;
            min-height: calc(100vh - 200px);
        }

        .patient-header {
            background: linear-gradient(135deg, #0e2f44 0%, #1a5276 100%);
            color: white;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .patient-info h2 {
            margin: 0 0 10px 0;
            font-size: 1.8em;
            font-weight: 600;
        }

        .patient-details {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }

        .patient-detail {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.95em;
        }

        .patient-detail i {
            color: #3498db;
        }

        .back-button {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.3);
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .back-button:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }

        .date-selection {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            border: 1px solid #e9ecef;
        }

        .date-selection h3 {
            margin: 0 0 15px 0;
            color: #0e2f44;
            font-size: 1.2em;
        }

        .date-controls {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }

        .date-select {
            padding: 10px 15px;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            background: white;
            min-width: 200px;
            transition: border-color 0.3s ease;
        }

        .date-select:focus {
            outline: none;
            border-color: #0e2f44;
            box-shadow: 0 0 0 3px rgba(14, 47, 68, 0.1);
        }

        .load-btn {
            background: #0e2f44;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s ease;
        }

        .load-btn:hover {
            background: #1a5276;
        }

        .load-btn:disabled {
            background: #6c757d;
            cursor: not-allowed;
        }

        .current-vitals {
            background: linear-gradient(135deg, #e67e22 0%, #d35400 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            text-align: center;
        }

        .current-vitals h3 {
            margin: 0 0 15px 0;
            font-size: 1.3em;
        }

        .vital-stats {
            display: flex;
            justify-content: space-around;
            gap: 20px;
            flex-wrap: wrap;
        }

        .vital-stat {
            text-align: center;
        }

        .vital-value {
            font-size: 2.5em;
            font-weight: bold;
            display: block;
            margin-bottom: 5px;
        }

        .vital-label {
            font-size: 0.9em;
            opacity: 0.9;
        }

        .charts-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
            margin-bottom: 25px;
        }

        .chart-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 20px;
            border: 1px solid #e9ecef;
        }

        .chart-title {
            font-size: 1.2em;
            color: #0e2f44;
            margin-bottom: 15px;
            font-weight: 600;
            text-align: center;
        }

        .chart-container {
            position: relative;
            height: 300px;
        }

        .no-data-message {
            background: #fff3cd;
            color: #856404;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            border: 1px solid #ffeaa7;
            margin: 20px 0;
        }

        .loading {
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }

        .loading i {
            font-size: 2em;
            margin-bottom: 15px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 6px;
            border: 1px solid #f5c6cb;
            margin: 15px 0;
        }

        /* Expandable Chart Styles */
        .expand-btn {
            background: #0e2f44;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .expand-btn:hover {
            background: #1a5276;
            transform: scale(1.05);
        }

        .expand-btn i {
            font-size: 12px;
        }

        /* Chart Modal Styles */
        .chart-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.8);
            z-index: 1000;
            backdrop-filter: blur(5px);
        }

        .chart-modal-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            width: 90%;
            max-width: 1200px;
            height: 85%;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .chart-modal-header {
            background: linear-gradient(135deg, #0e2f44 0%, #1a5276 100%);
            color: white;
            padding: 20px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-radius: 12px 12px 0 0;
        }

        .chart-modal-header h3 {
            margin: 0;
            font-size: 1.4em;
            font-weight: 600;
        }

        .close-modal-btn {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .close-modal-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: scale(1.1);
        }

        .chart-modal-body {
            flex: 1;
            padding: 25px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .chart-modal-body canvas {
            max-width: 100%;
            max-height: 100%;
            width: auto !important;
            height: auto !important;
        }

        /* Animation for modal */
        .chart-modal.show {
            display: block;
            animation: fadeIn 0.3s ease;
        }

        .chart-modal-content.show {
            animation: slideIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideIn {
            from { 
                opacity: 0;
                transform: translate(-50%, -50%) scale(0.9);
            }
            to { 
                opacity: 1;
                transform: translate(-50%, -50%) scale(1);
            }
        }

        @media (max-width: 768px) {
            .charts-section {
                grid-template-columns: 1fr;
            }
            
            .patient-header {
                flex-direction: column;
                text-align: center;
            }
            
            .date-controls {
                flex-direction: column;
                align-items: stretch;
            }
            
            .vital-stats {
                flex-direction: column;
                gap: 15px;
            }
        }
    </style>
</head>
<body style="background-image: url('../images/background_page.jpg'); background-color: rgba(12, 36, 54, 0.55); background-position: center; background-size: cover; background-repeat: no-repeat;">   
    <div class="page">
        <div class="dashboard">
            <div class="title">
                <img class="logo" src="../images/download__15__14-removebg-preview.png" alt="">
                <h2>HopCare</h2>
                <i class="fa-solid fa-bars toggle"></i>
            </div>
            <ul class="links">
                <li>
                    <a href="doctor_dashboard.php">
                        <i class="fa-solid fa-cubes fa-fw"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="my_patients.php">
                        <i class="fa-solid fa-people-arrows fa-fw"></i>
                        <span>My Patients</span>
                    </a>
                </li>
                <li>
                    <a href="appointments.php">
                        <i class="fa-solid fa-calendar-check fa-fw"></i>
                        <span>Appointments</span>
                    </a>
                </li>
                <li>
                    <a href="medical_records.php">
                        <i class="fa-solid fa-file-medical fa-fw"></i>
                        <span>Medical Records</span>
                    </a>
                </li>
                <li>
                    <a href="schedule.php">
                        <i class="fa-solid fa-clock fa-fw"></i>
                        <span>Schedule</span>
                    </a>
                </li>
            </ul>
            <form method="post" class="log-out">
                <button type="submit" name="logout">
                    <i class="fa-solid fa-arrow-right-from-bracket fa-fw"></i>
                    <span>Logout</span>
                </button>
            </form>
        </div>
        <div class="content">
            <div class="header pro-header">
                <div class="header-left">
                    <img src="../images/download__15__14-removebg-preview.png" alt="Logo" class="header-logo">
                    <div class="welcome">
                        <h1>Signes Vitaux</h1>
                        <span class="subtitle">Surveillance des paramètres vitaux</span>
                    </div>
                </div>
                <div class="header-right">
                    <div class="profile-menu">
                        <img src="../images/avatar.jpg" alt="Profile" class="avatar">
                        <span class="profile-name">Dr. <?php echo $doctor_name; ?></span>
                        <i class="fa-solid fa-chevron-down"></i>
                        <div class="profile-dropdown">
                            <ul>
                                <li><a href="profile.php">My Profile</a></li>
                                <li><a href="settings.php">Settings</a></li>
                                <li>
                                    <form method="post">
                                        <button type="submit" name="logout">Logout</button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <div class="vital-signs-container">
                <?php if ($patient_info): ?>
                    <!-- Patient Header -->
                    <div class="patient-header">
                        <div class="patient-info">
                            <h2><?php echo htmlspecialchars($patient_info['nom']); ?></h2>
                            <div class="patient-details">
                                <div class="patient-detail">
                                    <i class="fa-solid fa-envelope"></i>
                                    <span><?php echo htmlspecialchars($patient_info['email']); ?></span>
                                </div>
                                <div class="patient-detail">
                                    <i class="fa-solid fa-calendar"></i>
                                    <span>Né(e) le <?php echo htmlspecialchars($patient_info['date_naissance']); ?></span>
                                </div>
                                <div class="patient-detail">
                                    <i class="fa-solid fa-id-card"></i>
                                    <span>ID: <?php echo $patient_info['id']; ?></span>
                                </div>
                            </div>
                        </div>
                        <a href="medical_records.php" class="back-button">
                            <i class="fa-solid fa-arrow-left"></i>
                            Retour aux dossiers
                        </a>
                    </div>

                    <!-- Date Selection -->
                    <div class="date-selection">
                        <h3><i class="fa-solid fa-calendar-days"></i> Sélection de la date</h3>
                        <div class="date-controls">
                            <select id="dateSelect" class="date-select">
                                <option value="">Chargement des dates disponibles...</option>
                            </select>
                            <button id="loadDataBtn" class="load-btn" disabled>
                                <i class="fa-solid fa-chart-line"></i>
                                Charger les données
                            </button>
                        </div>
                    </div>

                    <!-- Current Vitals Display -->
                    <div class="current-vitals">
                        <h3><i class="fa-solid fa-heart-pulse"></i> Dernière Mesure</h3>
                        <div class="vital-stats">
                            <div class="vital-stat">
                                <span class="vital-value" id="currentTemp">--</span>
                                <span class="vital-label">Température (°C)</span>
                            </div>
                            <div class="vital-stat">
                                <span class="vital-value" id="currentPulse">--</span>
                                <span class="vital-label">Pulsation (bpm)</span>
                            </div>
                        </div>
                        <div style="margin-top: 10px; font-size: 0.9em; opacity: 0.8;">
                            <i class="fa-solid fa-clock"></i>
                            <span id="lastMeasurementTime">--</span>
                        </div>
                    </div>

                    <!-- Charts Section -->
                    <div class="charts-section">
                        <div class="chart-card">
                            <div class="chart-title">
                                <i class="fa-solid fa-thermometer-half"></i>
                                Évolution de la Température
                            </div>
                            <div class="chart-container">
                                <canvas id="temperatureChart"></canvas>
                            </div>
                        </div>
                        <div class="chart-card">
                            <div class="chart-title">
                                <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                                    <span><i class="fa-solid fa-heart"></i> Évolution du Rythme Cardiaque</span>
                                    <button id="expandHeartChart" class="expand-btn" title="Agrandir le graphique">
                                        <i class="fa-solid fa-expand"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="chart-container">
                                <canvas id="pulseChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Expanded Heart Rate Chart Modal -->
                    <div id="heartChartModal" class="chart-modal">
                        <div class="chart-modal-content">
                            <div class="chart-modal-header">
                                <h3><i class="fa-solid fa-heart"></i> Évolution du Rythme Cardiaque - Vue Détaillée</h3>
                                <button id="closeHeartChart" class="close-modal-btn">
                                    <i class="fa-solid fa-times"></i>
                                </button>
                            </div>
                            <div class="chart-modal-body">
                                <canvas id="expandedPulseChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Loading and Error Messages -->
                    <div id="loadingMessage" class="loading" style="display: none;">
                        <i class="fa-solid fa-spinner"></i>
                        <p>Chargement des données...</p>
                    </div>

                    <div id="noDataMessage" class="no-data-message" style="display: none;">
                        <i class="fa-solid fa-exclamation-triangle"></i>
                        <p>Aucune mesure trouvée pour la date sélectionnée.</p>
                    </div>

                    <div id="errorMessage" class="error-message" style="display: none;">
                        <i class="fa-solid fa-exclamation-circle"></i>
                        <p id="errorText">Une erreur s'est produite lors du chargement des données.</p>
                    </div>

                <?php else: ?>
                    <!-- No Patient Selected -->
                    <div class="no-data-message">
                        <i class="fa-solid fa-user-slash"></i>
                        <h3>Aucun patient sélectionné</h3>
                        <p>Veuillez sélectionner un patient depuis la page des dossiers médicaux pour voir ses signes vitaux.</p>
                        <a href="medical_records.php" class="load-btn" style="text-decoration: none; display: inline-block; margin-top: 15px;">
                            <i class="fa-solid fa-arrow-left"></i>
                            Retour aux dossiers médicaux
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Global variables
        let temperatureChart = null;
        let pulseChart = null;
        let expandedPulseChart = null;
        let availableDates = [];
        let currentChartData = null; // Store current chart data for expansion
        const patientId = <?php echo $patient_id ?: 'null'; ?>;

        // Initialize the page
        document.addEventListener('DOMContentLoaded', function() {
            if (patientId) {
                loadAvailableDates();
                setupEventListeners();
                setupChartExpansion();
                // Start real-time updates for current vitals
                startRealTimeUpdates();
            }
        });

        // Load available dates for the patient
        async function loadAvailableDates() {
            try {
                const response = await fetch(`../../backend/api/get_mesures.php?patient_id=${patientId}`);
                const data = await response.json();
                
                if (Array.isArray(data) && data.length > 0) {
                    // Extract unique dates
                    const dates = [...new Set(data.map(item => item.date_mesure.split(' ')[0]))];
                    availableDates = dates.sort((a, b) => new Date(b) - new Date(a)); // Sort descending
                    
                    // Populate date select
                    const dateSelect = document.getElementById('dateSelect');
                    dateSelect.innerHTML = '<option value="">Sélectionner une date</option>';
                    
                    availableDates.forEach(date => {
                        const option = document.createElement('option');
                        option.value = date;
                        option.textContent = formatDateForDisplay(date);
                        dateSelect.appendChild(option);
                    });
                    
                    // Enable load button
                    document.getElementById('loadDataBtn').disabled = false;
                    
                    // Auto-select today's date if available
                    const today = new Date().toISOString().split('T')[0];
                    if (availableDates.includes(today)) {
                        dateSelect.value = today;
                        loadVitalSignsData(today);
                    } else if (availableDates.length > 0) {
                        // Select the most recent date
                        dateSelect.value = availableDates[0];
                        loadVitalSignsData(availableDates[0]);
                    }
                } else {
                    showNoDataMessage();
                }
            } catch (error) {
                console.error('Error loading dates:', error);
                showErrorMessage('Erreur lors du chargement des dates disponibles');
            }
        }

        // Setup event listeners
        function setupEventListeners() {
            document.getElementById('loadDataBtn').addEventListener('click', function() {
                const selectedDate = document.getElementById('dateSelect').value;
                if (selectedDate) {
                    loadVitalSignsData(selectedDate);
                }
            });

            document.getElementById('dateSelect').addEventListener('change', function() {
                const selectedDate = this.value;
                if (selectedDate) {
                    loadVitalSignsData(selectedDate);
                }
            });
        }

        // Load vital signs data for a specific date
        async function loadVitalSignsData(date) {
            showLoading();
            hideMessages();
            
            try {
                const response = await fetch(`../../backend/api/get_mesures.php?patient_id=${patientId}`);
                const allData = await response.json();
                
                if (Array.isArray(allData) && allData.length > 0) {
                    // Update current vitals with the latest measurement from ALL data
                    const latestMeasurement = allData[allData.length - 1];
                    updateCurrentVitals(latestMeasurement);
                    
                    // Filter data for the selected date
                    const dateData = allData.filter(item => item.date_mesure.startsWith(date));
                    
                    if (dateData.length > 0) {
                        // Create charts for the selected date
                        createCharts(dateData, date);
                        hideLoading();
                    } else {
                        showNoDataMessage();
                    }
                } else {
                    showNoDataMessage();
                }
            } catch (error) {
                console.error('Error loading vital signs data:', error);
                showErrorMessage('Erreur lors du chargement des données');
            }
        }

        // Update current vitals display
        function updateCurrentVitals(measurement) {
            document.getElementById('currentTemp').textContent = parseFloat(measurement.temperature).toFixed(1);
            document.getElementById('currentPulse').textContent = measurement.pulsation;
            
            // Format the measurement time
            const measurementDate = new Date(measurement.date_mesure);
            const formattedTime = measurementDate.toLocaleString('fr-FR', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
            document.getElementById('lastMeasurementTime').textContent = formattedTime;
        }

        // Create temperature and pulse charts
        function createCharts(data, date) {
            // Prepare data for charts
            const labels = data.map(item => formatTime(item.date_mesure));
            const temperatures = data.map(item => parseFloat(item.temperature));
            const pulses = data.map(item => parseInt(item.pulsation));

            // Store current chart data for expansion
            currentChartData = { labels, pulses, date };

            // Create temperature chart
            createTemperatureChart(labels, temperatures, date);
            
            // Create pulse chart
            createPulseChart(labels, pulses, date);
        }

        // Create temperature chart
        function createTemperatureChart(labels, temperatures, date) {
            const ctx = document.getElementById('temperatureChart').getContext('2d');
            
            if (temperatureChart) {
                temperatureChart.destroy();
            }
            
            temperatureChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Température (°C)',
                        data: temperatures,
                        borderColor: '#e67e22',
                        backgroundColor: 'rgba(230, 126, 34, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 4,
                        pointHoverRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: `Température - ${formatDateForDisplay(date)}`,
                            color: '#0e2f44',
                            font: {
                                size: 16,
                                weight: 'bold'
                            }
                        },
                        legend: {
                            display: true,
                            labels: {
                                color: '#0e2f44',
                                font: {
                                    size: 12
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            title: {
                                display: true,
                                text: 'Heure',
                                color: '#0e2f44'
                            },
                            ticks: {
                                color: '#666',
                                maxRotation: 45
                            }
                        },
                        y: {
                            title: {
                                display: true,
                                text: 'Température (°C)',
                                color: '#0e2f44'
                            },
                            ticks: {
                                color: '#666'
                            },
                            beginAtZero: false
                        }
                    }
                }
            });
        }

        // Create pulse chart
        function createPulseChart(labels, pulses, date) {
            const ctx = document.getElementById('pulseChart').getContext('2d');
            
            if (pulseChart) {
                pulseChart.destroy();
            }
            
            // Apply the same advanced logic as admin version
            // Treat 0 or negative values as signal loss (null) to create gaps in the chart
            const processedPulses = pulses.map(val => {
                const pulseVal = parseInt(val);
                return pulseVal > 0 ? pulseVal : null;
            });
            
            // Apply smoothing with 5-point moving average filter
            const smoothedPulses = smoothData(processedPulses, 5);
            
            pulseChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Pulsation (bpm)',
                        data: smoothedPulses,
                        borderColor: '#e74c3c',
                        backgroundColor: 'transparent',
                        borderWidth: 1.5,
                        fill: false,
                        tension: 0.4,
                        pointRadius: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: `Rythme Cardiaque - ${formatDateForDisplay(date)}`,
                            color: '#0e2f44',
                            font: {
                                size: 16,
                                weight: 'bold'
                            }
                        },
                        legend: {
                            display: true,
                            labels: {
                                color: '#0e2f44',
                                font: {
                                    size: 12
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            title: {
                                display: true,
                                text: 'Heure',
                                color: '#0e2f44'
                            },
                            ticks: {
                                color: '#666',
                                maxRotation: 45,
                                minRotation: 0,
                                autoSkip: true,
                                maxTicksLimit: 20
                            }
                        },
                        y: {
                            title: {
                                display: true,
                                text: 'Pulsation (bpm)',
                                color: '#0e2f44'
                            },
                            ticks: {
                                color: '#666'
                            },
                            beginAtZero: false
                        }
                    }
                }
            });
        }

        // Add the smoothing function from admin version
        function smoothData(data, windowSize) {
            if (!data || data.length < windowSize) {
                return data;
            }
            const smoothedData = [];
            for (let i = 0; i < data.length; i++) {
                const start = Math.max(0, i - Math.floor(windowSize / 2));
                const end = Math.min(data.length, i + Math.ceil(windowSize / 2));
                let sum = 0;
                let count = 0;
                for (let j = start; j < end; j++) {
                    if (data[j] !== null) {
                        sum += data[j];
                        count++;
                    }
                }
                smoothedData.push(count > 0 ? sum / count : null);
            }
            return smoothedData;
        }

        // Real-time updates for current vitals
        function startRealTimeUpdates() {
            setInterval(async () => {
                try {
                    const response = await fetch(`../../backend/api/get_mesures.php?patient_id=${patientId}`);
                    const data = await response.json();
                    
                    if (Array.isArray(data) && data.length > 0) {
                        // Always show the most recent measurement from all data
                        const latestMeasurement = data[data.length - 1];
                        updateCurrentVitals(latestMeasurement);
                    }
                } catch (error) {
                    console.error('Error updating real-time data:', error);
                }
            }, 10000); // Update every 10 seconds
        }

        // Utility functions
        function formatDateForDisplay(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('fr-FR', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        }

        function formatTime(dateTimeString) {
            const date = new Date(dateTimeString);
            return date.toLocaleTimeString('fr-FR', {
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        // UI helper functions
        function showLoading() {
            document.getElementById('loadingMessage').style.display = 'block';
        }

        function hideLoading() {
            document.getElementById('loadingMessage').style.display = 'none';
        }

        function showNoDataMessage() {
            hideLoading();
            document.getElementById('noDataMessage').style.display = 'block';
        }

        function showErrorMessage(message) {
            hideLoading();
            document.getElementById('errorText').textContent = message;
            document.getElementById('errorMessage').style.display = 'block';
        }

        function hideMessages() {
            document.getElementById('loadingMessage').style.display = 'none';
            document.getElementById('noDataMessage').style.display = 'none';
            document.getElementById('errorMessage').style.display = 'none';
        }

        // Profile menu functionality
        document.addEventListener('DOMContentLoaded', function() {
            const profileMenu = document.querySelector('.profile-menu');
            if (profileMenu) {
                profileMenu.addEventListener('click', function() {
                    const dropdown = this.querySelector('.profile-dropdown');
                    if (dropdown) {
                        dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
                    }
                });
            }
        });

        // Setup chart expansion functionality
        function setupChartExpansion() {
            const expandBtn = document.getElementById('expandHeartChart');
            const closeBtn = document.getElementById('closeHeartChart');
            const modal = document.getElementById('heartChartModal');

            if (expandBtn) {
                expandBtn.addEventListener('click', function() {
                    if (currentChartData) {
                        openExpandedChart();
                    }
                });
            }

            if (closeBtn) {
                closeBtn.addEventListener('click', function() {
                    closeExpandedChart();
                });
            }

            // Close modal when clicking outside
            if (modal) {
                modal.addEventListener('click', function(e) {
                    if (e.target === modal) {
                        closeExpandedChart();
                    }
                });
            }

            // Close modal with Escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    closeExpandedChart();
                }
            });
        }

        // Open expanded chart modal
        function openExpandedChart() {
            const modal = document.getElementById('heartChartModal');
            const modalContent = modal.querySelector('.chart-modal-content');
            
            modal.classList.add('show');
            modalContent.classList.add('show');
            
            // Create expanded chart after modal is visible
            setTimeout(() => {
                createExpandedPulseChart();
            }, 100);
        }

        // Close expanded chart modal
        function closeExpandedChart() {
            const modal = document.getElementById('heartChartModal');
            const modalContent = modal.querySelector('.chart-modal-content');
            
            modal.classList.remove('show');
            modalContent.classList.remove('show');
            
            // Destroy expanded chart
            if (expandedPulseChart) {
                expandedPulseChart.destroy();
                expandedPulseChart = null;
            }
        }

        // Create expanded pulse chart
        function createExpandedPulseChart() {
            if (!currentChartData) return;
            
            const ctx = document.getElementById('expandedPulseChart').getContext('2d');
            
            if (expandedPulseChart) {
                expandedPulseChart.destroy();
            }
            
            const { labels, pulses, date } = currentChartData;
            
            // Apply the same advanced logic as the regular chart
            const processedPulses = pulses.map(val => {
                const pulseVal = parseInt(val);
                return pulseVal > 0 ? pulseVal : null;
            });
            
            const smoothedPulses = smoothData(processedPulses, 5);
            
            expandedPulseChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Pulsation (bpm)',
                        data: smoothedPulses,
                        borderColor: '#e74c3c',
                        backgroundColor: 'transparent',
                        borderWidth: 2.5,
                        fill: false,
                        tension: 0.4,
                        pointRadius: 2,
                        pointHoverRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: `Rythme Cardiaque - ${formatDateForDisplay(date)} - Vue Détaillée`,
                            color: '#0e2f44',
                            font: {
                                size: 18,
                                weight: 'bold'
                            }
                        },
                        legend: {
                            display: true,
                            labels: {
                                color: '#0e2f44',
                                font: {
                                    size: 14
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            title: {
                                display: true,
                                text: 'Heure',
                                color: '#0e2f44',
                                font: {
                                    size: 14,
                                    weight: 'bold'
                                }
                            },
                            ticks: {
                                color: '#666',
                                maxRotation: 45,
                                minRotation: 0,
                                autoSkip: true,
                                maxTicksLimit: 30,
                                font: {
                                    size: 12
                                }
                            }
                        },
                        y: {
                            title: {
                                display: true,
                                text: 'Pulsation (bpm)',
                                color: '#0e2f44',
                                font: {
                                    size: 14,
                                    weight: 'bold'
                                }
                            },
                            ticks: {
                                color: '#666',
                                font: {
                                    size: 12
                                }
                            },
                            beginAtZero: false
                        }
                    },
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    },
                    plugins: {
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleColor: 'white',
                            bodyColor: 'white',
                            borderColor: '#e74c3c',
                            borderWidth: 1,
                            cornerRadius: 6,
                            displayColors: false
                        }
                    }
                }
            });
        }
    </script>
    <script src="../index.js"></script>
</body>
</html> 