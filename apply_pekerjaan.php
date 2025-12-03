<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'kuli') {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['pekerjaan_id'])) {
    $pekerjaan_id = $_POST['pekerjaan_id'];
    $kuli_id = $_SESSION['user_id'];
    $pesan = trim($_POST['pesan'] ?? '');

    try {
        // Validasi data
        if (empty($pekerjaan_id) || empty($kuli_id)) {
            throw new Exception("Data tidak lengkap.");
        }

        // Check if job exists
        $stmt = $pdo->prepare("SELECT id, id_user FROM pekerjaan WHERE id = ? AND status = 'menunggu'");
        $stmt->execute([$pekerjaan_id]);
        $pekerjaan = $stmt->fetch();
        
        if (!$pekerjaan) {
            $_SESSION['error'] = "Pekerjaan tidak ditemukan atau sudah tidak tersedia.";
            header("Location: dashboard_kuli.php");
            exit();
        }

        // Check if user is trying to apply to their own job
        if ($pekerjaan['id_user'] == $kuli_id) {
            $_SESSION['error'] = "Anda tidak dapat melamar pekerjaan yang Anda buat sendiri.";
            header("Location: dashboard_kuli.php");
            exit();
        }

        // Check if already applied
        $stmt = $pdo->prepare("SELECT id FROM aplikasi_pekerjaan 
                               WHERE id_pekerjaan = ? AND id_kuli = ?");
        $stmt->execute([$pekerjaan_id, $kuli_id]);
        
        if ($stmt->fetch()) {
            $_SESSION['error'] = "Anda sudah melamar pekerjaan ini sebelumnya.";
            header("Location: dashboard_kuli.php");
            exit();
        }

        // Apply for job
        $stmt = $pdo->prepare("INSERT INTO aplikasi_pekerjaan 
                               (id_pekerjaan, id_kuli, pesan, status, created_at) 
                               VALUES (?, ?, ?, 'menunggu', NOW())");
        $stmt->execute([$pekerjaan_id, $kuli_id, $pesan]);
        
        $_SESSION['success'] = "Lamaran berhasil dikirim! Tunggu konfirmasi dari pemilik.";
        
        // Redirect based on referer
        if (isset($_SERVER['HTTP_REFERER'])) {
            header("Location: " . $_SERVER['HTTP_REFERER']);
        } else {
            header("Location: dashboard_kuli.php");
        }
        exit();
        
    } catch (PDOException $e) {
        $_SESSION['error'] = "Terjadi kesalahan database: " . $e->getMessage();
        header("Location: dashboard_kuli.php");
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header("Location: dashboard_kuli.php");
        exit();
    }
} else {
    header("Location: dashboard_kuli.php");
    exit();
}