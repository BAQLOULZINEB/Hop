<?php
require_once '../../backend/auth/session_handler.php';
checkRole('admin');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header('Location: ../../frontend/Authentification.php');
    exit();
}

require_once '../../backend/config/database.php';
$patients = $pdo->query('SELECT p.id, u.nom FROM patient p JOIN utilisateur u ON p.id = u.id')->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mesures Patient</title>
    <link rel="preload" href="../images/background_page.jpg" as="image">
    <link rel="stylesheet" href="../css_files/master.css">
    <link rel="stylesheet" href="../css_files/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Arabic:wght@100..900&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Specific styles for patient measures page */
        .container { 
            max-width: 1100px; 
            margin: 20px auto; /* Adjusted margin to fit within content padding */
            background: rgba(255, 255, 255, 0.95); /* A bit less transparent for readability */
            border-radius: 12px; 
            box-shadow: 0 2px 16px rgba(0,0,0,0.08); 
            padding: 32px;
        }
        h2 { color: #2c3e50; margin-bottom: 24px; }
        label { font-weight: bold; color: #34495e; }
        select { margin-bottom: 24px; display: block; width: 100%; padding: 8px; border-radius: 4px; border: 1px solid #ccc; }
        .chart-card {
            background: #f8fafc;
            border-radius: 10px;
            box-shadow: 0 1px 6px rgba(0,0,0,0.05);
            padding: 24px 18px 18px 18px;
            margin-bottom: 32px;
        }
        .chart-title {
            font-size: 1.2em;
            color: #34495e;
            margin-bottom: 10px;
            font-weight: 600;
        }
        canvas { width: 100% !important; height: 350px !important; }
        #noData { color: #c0392b; font-weight: bold; margin-top: 18px; }
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
                    <a href="admin_dashboard.php">
                        <i class="fa-solid fa-cubes fa-fw"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="num num1">
                    <a class="listted" href="doctors.php">
                        <i class="fa-solid fa-user-nurse fa-fw"></i>
                        <span>Médecins</span>
                        <i class="fa-solid fa-angle-right tog"></i>
                    </a>
                    <div class="list one" style="display: none;">
                        <a href="doctors.php">Voir les médecins</a>
                        <a href="add_doctor.php">Ajouter un médecin</a>
                    </div>
                </li>
                <li class="num num2">
                    <a  href="departments.php">
                        <i class="fa-solid fa-people-group fa-fw"></i>
                        <span>Spécialités</span>  
                    </a>
                </li>
                <li class="num num3">
                    <a class="listted" href="#">
                        <i class="fa-solid fa-people-arrows fa-fw"></i>
                        <span>Patients</span>
                        <i class="fa-solid fa-angle-right tog"></i>
                    </a>
                    <div class="list three" style="display: none;">
                        <a href="patients.php">Voir les patients</a>
                        <a href="add_patient.php">Ajouter un patient</a>
                    </div>
                </li>
                <li>
                    <a href="rendezvous.php">
                        <i class="fa-solid fa-calendar-check fa-fw"></i>
                        <span>Rendez-vous</span>
                    </a>
                </li>
                <li>
                    <a href="patient_mesures.php">
                        <i class="fa-regular fa-comments fa-fw"></i>
                        <span>Charts</span>
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
                        <h1>Patient Measures</h1>
                        <span class="subtitle">Real-time patient data visualization</span>
                    </div>
                </div>
                <div class="header-right">
                    <div class="profile-menu">
                        <img src="../images/avatar.jpg" alt="Profile" class="avatar">
                        <span class="profile-name">Admin</span>
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

            <!-- Content from patient_mesures.php -->
            <div class="container">
                <h2>Visualisation des mesures patient</h2>
                <label for="patientSelect">Choisir un patient :</label>
                <select id="patientSelect">
                    <option value="">-- Sélectionner --</option>
                    <?php foreach ($patients as $p): ?>
                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nom']) ?> (ID: <?= $p['id'] ?>)</option>
                    <?php endforeach; ?>
                </select>
                <div id="currentTempBox" style="margin-bottom:24px; font-size:1.5em; color:#e67e22; font-weight:bold;">
                    Température actuelle : <span id="currentTemp">--</span> °C
                </div>
                <div id="charts"></div>
                <button id="loadPrevWeek" style="display:none;margin-bottom:20px;">Charger la semaine précédente</button>
                <div id="noData" style="color:#c00; display:none;">Aucune mesure trouvée pour ce patient.</div>
            </div>
            <!-- End of content from patient_mesures.php -->
        </div>
    </div>
    <script src="../index.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const toggles = document.querySelectorAll(".listted");
            toggles.forEach(function(toggle) {
                toggle.addEventListener("click", function(e) {
                    e.preventDefault();
                    const submenu = toggle.nextElementSibling;
                    if (submenu) {
                        submenu.style.display = submenu.style.display === "block" ? "none" : "block";
                    }
                    toggles.forEach(function(otherToggle) {
                        if (otherToggle !== toggle) {
                            const otherSubmenu = otherToggle.nextElementSibling;
                            if (otherSubmenu) {
                                otherSubmenu.style.display = "none";
                            }
                        }
                    });
                });
            });
        });
    </script>
    <!-- Charting script from patient_mesures.php -->
    <script>
    const select = document.getElementById('patientSelect');
    const chartsDiv = document.getElementById('charts');
    const noDataDiv = document.getElementById('noData');
    const loadPrevWeekBtn = document.getElementById('loadPrevWeek');
    let allData = [];
    let currentWeekStart = null;
    let prevWeekLoaded = false;
    let tempInterval = null;
    let chartInstances = [];

    function getMonday(d) {
        d = new Date(d);
        const day = d.getDay(), diff = d.getDate() - day + (day === 0 ? -6 : 1);
        return new Date(d.setDate(diff));
    }
    function formatDate(dateStr) {
        const d = new Date(dateStr);
        return d.toLocaleDateString('fr-FR', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
    }
    function formatHour(dateStr) {
        const d = new Date(dateStr);
        return d.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
    }
    function groupByDay(data) {
        const days = {};
        data.forEach(m => {
            const day = m.date_mesure.substr(0, 10);
            if (!days[day]) days[day] = [];
            days[day].push(m);
        });
        return days;
    }
    function smoothData(data, windowSize) {
        if (!data || data.length < windowSize) {
            return data;
        }
        const smoothedData = [];
        for (let i = 0; i < data.length; i++) {
            const start = Math.max(0, i - Math.floor(windowSize / 2));
            const end = Math.min(data.length, i + Math.ceil(windowSize / 2));
            let sum = 0;
            for (let j = start; j < end; j++) {
                sum += data[j];
            }
            smoothedData.push(sum / (end - start));
        }
        return smoothedData;
    }
    function getWeekRange(date) {
        const monday = getMonday(date);
        const sunday = new Date(monday);
        sunday.setDate(monday.getDate() + 6);
        return [monday, sunday];
    }
    function isInWeek(dateStr, weekStart) {
        const d = new Date(dateStr);
        const start = new Date(weekStart);
        const end = new Date(start);
        end.setDate(start.getDate() + 6);
        return d >= start && d <= end;
    }
    function clearCharts() {
        chartInstances.forEach(c => c.destroy());
        chartInstances = [];
        chartsDiv.innerHTML = '';
    }
    function renderChartsForWeek(weekStart) {
        clearCharts();
        const weekData = allData.filter(m => isInWeek(m.date_mesure, weekStart));
        if (weekData.length === 0) {
            noDataDiv.style.display = 'block';
            return;
        }
        noDataDiv.style.display = 'none';
        const days = groupByDay(weekData);
        // Trier les jours du plus récent au plus ancien
        const sortedDays = Object.keys(days).sort((a, b) => b.localeCompare(a));
        sortedDays.forEach(day => {
            const measures = days[day];
            const labels = measures.map(m => formatHour(m.date_mesure));
            // Treat 0 or negative values as signal loss (null) to create gaps in the chart
            const pulse = measures.map(m => {
                const val = parseInt(m.pulsation);
                return val > 0 ? val : null;
            });
            const smoothedPulse = smoothData(pulse, 5); // Apply a 5-point moving average filter

            const card = document.createElement('div');
            card.className = 'chart-card';
            card.style.marginBottom = '32px';
            const title = document.createElement('div');
            title.className = 'chart-title';
            title.textContent = `Rythme cardiaque du ${formatDate(day)}`;
            card.appendChild(title);
            const canvas = document.createElement('canvas');
            card.appendChild(canvas);
            chartsDiv.appendChild(card);
            const chart = new Chart(canvas.getContext('2d'), {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Pulsation',
                        data: smoothedPulse,
                        borderColor: '#e74c3c',
                        backgroundColor: 'transparent',
                        fill: false,
                        tension: 0.4,
                        borderWidth: 1.5,
                        pointRadius: 0
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { display: true, labels: { color: '#555', font: { size: 15 } } },
                        title: { display: false }
                    },
                    scales: {
                        x: { ticks: { color: '#888', maxRotation: 45, minRotation: 0, autoSkip: true, maxTicksLimit: 20 } },
                        y: { ticks: { color: '#888' } }
                    }
                }
            });
            chartInstances.push(chart);
        });
    }
    async function fetchData(patientId) {
        const res = await fetch(`../../backend/api/get_mesures.php?patient_id=${patientId}`);
        return await res.json();
    }
    async function updateAll(patientId, weekStart, showPrevBtn = true) {
        allData = await fetchData(patientId);
        if (!Array.isArray(allData) || allData.length === 0) {
            clearCharts();
            document.getElementById('currentTemp').textContent = '--';
            noDataDiv.style.display = 'block';
            loadPrevWeekBtn.style.display = 'none';
            return;
        }
        // Température la plus récente
        const last = allData[allData.length - 1];
        document.getElementById('currentTemp').textContent = parseFloat(last.temperature).toFixed(1);
        // Affichage des graphiques semaine courante
        renderChartsForWeek(weekStart);
        // Afficher le bouton si une semaine précédente existe et pas encore chargée
        if (showPrevBtn) {
            const oldest = allData[0].date_mesure.substr(0, 10);
            const prevMonday = new Date(weekStart);
            prevMonday.setDate(prevMonday.getDate() - 7);
            if (new Date(oldest) <= prevMonday) {
                loadPrevWeekBtn.style.display = 'block';
            } else {
                loadPrevWeekBtn.style.display = 'none';
            }
        }
    }
    // Polling température
    function startTempPolling(patientId) {
        if (tempInterval) clearInterval(tempInterval);
        tempInterval = setInterval(async () => {
            const data = await fetchData(patientId);
            if (Array.isArray(data) && data.length > 0) {
                const last = data[data.length - 1];
                document.getElementById('currentTemp').textContent = parseFloat(last.temperature).toFixed(1);
            }
        }, 5000);
    }
    select.addEventListener('change', async function() {
        const id = this.value;
        if (!id) {
            clearCharts();
            document.getElementById('currentTemp').textContent = '--';
            noDataDiv.style.display = 'none';
            loadPrevWeekBtn.style.display = 'none';
            if (tempInterval) clearInterval(tempInterval);
            return;
        }
        // Semaine courante (lundi)
        const now = new Date();
        currentWeekStart = getMonday(now);
        prevWeekLoaded = false;
        await updateAll(id, currentWeekStart);
        startTempPolling(id);
    });
    loadPrevWeekBtn.addEventListener('click', async function() {
        if (!select.value || prevWeekLoaded) return;
        const prevMonday = new Date(currentWeekStart);
        prevMonday.setDate(prevMonday.getDate() - 7);
        // Afficher la semaine précédente au-dessus
        renderChartsForWeek(prevMonday);
        loadPrevWeekBtn.style.display = 'none';
        prevWeekLoaded = true;
    });
    </script>
</body>
</html> 