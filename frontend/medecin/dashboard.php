<?php
require_once '../../backend/auth/session_handler.php';
checkRole('medecin');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard</title>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .logout-btn {
            padding: 10px 20px;
            background: #2e86c1;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
        }
        .logout-btn:hover {
            background: #1a5276;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="header">
            <h1>Doctor Dashboard</h1>
            <a href="../../backend/auth/logout.php" class="logout-btn">Logout</a>
        </div>
        <div class="content">
            <h2>Welcome, Doctor!</h2>
            <p>This is your doctor dashboard where you can manage your patients and consultations.</p>
            <!-- Add doctor-specific features here -->
        </div>
    </div>
</body>
</html> 