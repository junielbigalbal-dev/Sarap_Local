<?php
// simple_check.php - Simple session check for dashboard pages
session_start();

function checkLogin($required_role = null) {
    // Check if logged in
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || !isset($_SESSION['logged_in'])) {
        header('Location: simple_login.php');
        exit();
    }
    
    // Check role if specified
    if ($required_role && $_SESSION['role'] !== $required_role) {
        header('Location: simple_login.php');
        exit();
    }
    
    return true;
}
