<?php
require_once '../../backend/auth/session_handler.php';
checkRole('doctor');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard</title>
    <!-- main css file -->
    <link rel="preload" href="../images/background_page.jpg" as="image">
    <link rel="stylesheet" href="../css_files/master.css">
    <!-- font awesome -->
    <link rel="stylesheet" href="../css_files/all.min.css">
</head>
<body>
    <div class="container">
        <h1>Doctor Dashboard</h1>
        <p>Welcome to your doctor dashboard. Here you can manage your appointments and patient information.</p>
        <a href="appointments.php" class="btn">Manage Appointments</a>
        <a href="patients.php" class="btn">Manage Patients</a>
    </div>
    <img class="logo" src="../images/download__15__14-removebg-preview.png" alt="">
    <img src="../images/download__15__14-removebg-preview.png" alt="Logo" class="header-logo">
    <img src="../images/avatar.jpg" alt="Profile" class="avatar">
    <img src="../images/download__15_-removebg-preview.png" alt="">
    <script src="../index.js"></script>
</body>
</html> 