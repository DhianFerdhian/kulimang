<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: dashboard_admin.php");
    exit();
}

$id = $_GET['id'];

try {
    // Hapus aplikasi pekerjaan terkait terlebih dahulu
    $stmt = $pdo->prepare("DELETE FROM aplikasi_pekerjaan WHERE id_pekerjaan = ?");
    $stmt->execute([$id]);
    
    // Hapus pekerjaan
    $stmt = $pdo->prepare("DELETE FROM pekerjaan WHERE id = ?");
    $stmt->execute([$id]);
    
    $_SESSION['success'] = "Pekerjaan berhasil dihapus";
} catch (PDOException $e) {
    $_SESSION['error'] = "Error: " . $e->getMessage();
}

header("Location: dashboard_admin.php");
exit();
?>