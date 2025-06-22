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
    <title>Admin - Charts</title>
    <link rel="stylesheet" href="../css_files/master.css">
    <link rel="stylesheet" href="../css_files/all.min.css">
</head>
<body style="background-image: url('../images/background_page.jpg'); background-color: rgba(12, 36, 54, 0.55); background-position: center; background-size: cover; background-repeat: no-repeat;">
    <div class="page">
        <!-- Sidebar (You might want to include your sidebar here) -->
        
        <div class="content">
            

            <h1>Charts</h1>
            <p>This page is for viewing charts.</p>
            <!-- Add charts content here -->

        </div>
    </div>
    <script src="../index.js"></script>
</body>
</html> 