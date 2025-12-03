<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$role = $_SESSION['role'];

// Redirect ke dashboard berdasarkan role
switch ($role) {
    case 'admin':
        header("Location: dashboard_admin.php");
        break;
    case 'kuli':
        header("Location: dashboard_kuli.php");
        break;
    case 'user':
        header("Location: dashboard_user.php");
        break;
    default:
        header("Location: login.php");
        break;
}
exit();
?>