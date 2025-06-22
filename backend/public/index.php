<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Heart Rate & Temperature Monitor</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f0f0f0;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .chart-container {
            margin: 20px 0;
            height: 400px;
        }
        h1 {
            color: #333;
            text-align: center;
        }
        .status {
            text-align: center;
            margin: 10px 0;
            padding: 10px;
            border-radius: 5px;
        }
        .connected {
            background-color: #d4edda;
            color: #155724;
        }
        .disconnected {
            background-color: #f8d7da;
            color: #721c24;
        }
        .temperature-display {
            text-align: center;
            margin: 30px 0;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .temperature-value {
            font-size: 72px;
            font-weight: bold;
            color: #007bff;
            margin: 10px 0;
        }
        .temperature-label {
            font-size: 24px;
            color: #6c757d;
            margin-bottom: 10px;
        }
        .temperature-unit {
            font-size: 36px;
            color: #6c757d;
            margin-left: 10px;
        }
        .patient-info {
            background-color: #e9ecef;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .patient-info h2 {
            margin: 0 0 10px 0;
            color: #495057;
        }
        .patient-info p {
            margin: 5px 0;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Heart Rate & Temperature Monitor</h1>
        
        <!-- Patient Search Form -->
        <form id="patientSearchForm" style="text-align:center; margin-bottom:20px;">
            <input type="text" id="patientInput" placeholder="Enter Patient ID or Name" required>
            <button type="submit">Search</button>
        </form>

        <!-- Patient Information -->
        <div class="patient-info">
            <h2>Patient Information</h2>
            <p>Patient ID: <span id="patientId">--</span></p>
        </div>

        <div id="status" class="status disconnected">Disconnected</div>
        
        <!-- Temperature Display -->
        <div class="temperature-display">
            <div class="temperature-label">Current Temperature</div>
            <div class="temperature-value">
                <span id="temperature">--</span>
                <span class="temperature-unit">°C</span>
            </div>
        </div>

        <!-- Heart Rate Chart -->
        <div class="chart-container">
            <canvas id="heartRateChart"></canvas>
        </div>
    </div>

    <script>
        // Chart.js setup
        const maxDataPoints = 50;
        const heartRateData = {
            labels: [],
            datasets: [{
                label: 'Heart Rate',
                data: [],
                borderColor: 'rgb(255, 99, 132)',
                tension: 0.1
            }]
        };

        const heartRateChart = new Chart(
            document.getElementById('heartRateChart'),
            {
                type: 'line',
                data: heartRateData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: { beginAtZero: false }
                    }
                }
            }
        );

        // Handle patient search
        document.getElementById('patientSearchForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const patientInput = document.getElementById('patientInput').value.trim();
            if (!patientInput) return;

            // Display patient info
            document.getElementById('patientId').textContent = patientInput;

            // Fetch data from backend
            fetch(`api/get_patient_data.php?patient=${encodeURIComponent(patientInput)}`)
                .then(response => response.json())
                .then(data => {
                    // Update temperature display
                    if (data.length > 0) {
                        const last = data[data.length - 1];
                        document.getElementById('temperature').textContent = last.temperature.toFixed(1);
                    } else {
                        document.getElementById('temperature').textContent = '--';
                    }

                    // Update heart rate chart
                    heartRateData.labels = data.map(row => row.timestamp);
                    heartRateData.datasets[0].data = data.map(row => row.heartRate);
                    heartRateChart.update();
                })
                .catch(err => {
                    alert('No data found for this patient.');
                    document.getElementById('temperature').textContent = '--';
                    heartRateData.labels = [];
                    heartRateData.datasets[0].data = [];
                    heartRateChart.update();
                });
        });
    </script>
</body>
</html> 