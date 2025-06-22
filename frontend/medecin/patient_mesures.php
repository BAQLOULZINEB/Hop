<?php
require_once '../../backend/config/database.php';
// $pdo est déjà prêt à l'emploi ici
$patients = $pdo->query('SELECT p.id, u.nom FROM patient p JOIN utilisateur u ON p.id = u.id')->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mesures Patient</title>
    <link rel="stylesheet" href="../css_files/master.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .container { max-width: 900px; margin: 40px auto; background: #fff; border-radius: 8px; box-shadow: 0 2px 8px #0001; padding: 32px; }
        h2 { color: #2c3e50; }
        label { font-weight: bold; }
        select { margin-bottom: 24px; }
        canvas { margin-bottom: 32px; }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    <div class="container">
        <h2>Visualisation des mesures patient</h2>
        <label for="patientSelect">Choisir un patient :</label>
        <select id="patientSelect">
            <option value="">-- Sélectionner --</option>
            <?php foreach ($patients as $p): ?>
                <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nom']) ?> (ID: <?= $p['id'] ?>)</option>
            <?php endforeach; ?>
        </select>
        <div id="charts" style="display:none;">
            <canvas id="tempChart" height="100"></canvas>
            <canvas id="pulseChart" height="100"></canvas>
        </div>
        <div id="noData" style="color:#c00; display:none;">Aucune mesure trouvée pour ce patient.</div>
    </div>
    <script>
    const select = document.getElementById('patientSelect');
    const chartsDiv = document.getElementById('charts');
    const noDataDiv = document.getElementById('noData');
    let tempChart, pulseChart;

    select.addEventListener('change', async function() {
        const id = this.value;
        if (!id) {
            chartsDiv.style.display = 'none';
            noDataDiv.style.display = 'none';
            return;
        }
        const res = await fetch(`../../backend/api/get_mesures.php?patient_id=${id}`);
        const data = await res.json();
        if (!Array.isArray(data) || data.length === 0) {
            chartsDiv.style.display = 'none';
            noDataDiv.style.display = 'block';
            return;
        }
        noDataDiv.style.display = 'none';
        chartsDiv.style.display = 'block';
        const labels = data.map(m => m.date_mesure);
        const temp = data.map(m => parseFloat(m.temperature));
        const pulse = data.map(m => parseInt(m.pulsation));
        if (tempChart) tempChart.destroy();
        if (pulseChart) pulseChart.destroy();
        tempChart = new Chart(document.getElementById('tempChart').getContext('2d'), {
            type: 'line',
            data: { labels, datasets: [{ label: 'Température (°C)', data: temp, borderColor: '#e67e22', backgroundColor: 'rgba(230,126,34,0.1)', fill: true }] },
            options: { responsive: true, plugins: { legend: { display: true } } }
        });
        pulseChart = new Chart(document.getElementById('pulseChart').getContext('2d'), {
            type: 'line',
            data: { labels, datasets: [{ label: 'Pulsation (bpm)', data: pulse, borderColor: '#2980b9', backgroundColor: 'rgba(41,128,185,0.1)', fill: true }] },
            options: { responsive: true, plugins: { legend: { display: true } } }
        });
    });
    </script>
</body>
</html> 