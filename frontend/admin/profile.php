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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Profile</title>
    <link rel="stylesheet" href="../css_files/master.css">
    <link rel="stylesheet" href="../css_files/all.min.css">
</head>
<body style="background-image: url('../images/background_page.jpg'); background-color: rgba(12, 36, 54, 0.55); background-position: center; background-size: cover; background-repeat: no-repeat;">
    <div class="page">
        <?php include 'admin_sidebar.php'; // Example: include a sidebar file ?>

        <div class="content">
            <?php include 'admin_header.php'; // Example: include a header file ?>

            <h1>Admin Profile</h1>
            <p>This page displays the admin profile information.</p>
            <!-- Add profile content here -->

        </div>
    </div>
    <script src="../index.js"></script>
</body>
</html> 