<?php
session_start();

function checkAuth() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: /hospital_management_v1/frontend/Authentification.php');
        exit();
    }
}

function checkRole($required_role) {
    checkAuth();
    if ($_SESSION['role'] !== $required_role) {
        header('Location: /hospital_management_v1/frontend/Authentification.php');
        exit();
    }
}
?> 