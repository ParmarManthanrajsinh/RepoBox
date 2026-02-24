<?php
// includes/auth.php
session_start();

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: index.php");
        exit;
    }
}

function redirectIfLoggedIn() {
    if (isLoggedIn()) {
        header("Location: dashboard.php");
        exit;
    }
}
?>
