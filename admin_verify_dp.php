<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['pekerjaan_id'])) {
    $pekerjaan_id = $_POST['pekerjaan_id'];
    
    try {
        // Debug: Cek data pekerjaan sebelum update
        $stmt = $pdo->prepare("SELECT id, status, bukti_dp FROM pekerjaan WHERE id = ?");
        $stmt->execute([$pekerjaan_id]);
        $pekerjaan = $stmt->fetch();
        
        if ($pekerjaan) {
            error_log("DEBUG: Pekerjaan ID $pekerjaan_id ditemukan");
            error_log("DEBUG: Status: " . $pekerjaan['status']);
            error_log("DEBUG: Bukti DP: " . ($pekerjaan['bukti_dp'] ? 'Ada' : 'Tidak ada'));
            
            // Update status pekerjaan menjadi diproses hanya jika status masih menunggu
            $update_stmt = $pdo->prepare("UPDATE pekerjaan SET status = 'diproses' WHERE id = ? AND status = 'menunggu'");
            $update_stmt->execute([$pekerjaan_id]);
            
            $rowCount = $update_stmt->rowCount();
            error_log("DEBUG: Row affected: $rowCount");
            
            if ($rowCount > 0) {
                $_SESSION['success'] = "DP berhasil diverifikasi. Pekerjaan sekarang dalam status diproses.";
                
                // Optionally, tambahkan notifikasi atau log
                $log_stmt = $pdo->prepare("INSERT INTO notifikasi (id_user, pesan, tipe) VALUES (?, ?, ?)");
                $log_stmt->execute([$pekerjaan_id, "DP Anda telah diverifikasi oleh admin. Pekerjaan akan segera diproses.", "info"]);
                
            } else {
                // Cek alasan kenapa tidak terupdate
                if ($pekerjaan['status'] != 'menunggu') {
                    $_SESSION['error'] = "Pekerjaan sudah dalam status: " . ucfirst($pekerjaan['status']);
                } else {
                    $_SESSION['error'] = "Gagal memverifikasi DP. Silakan coba lagi.";
                }
            }
        } else {
            $_SESSION['error'] = "Pekerjaan tidak ditemukan.";
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
        error_log("ERROR: " . $e->getMessage());
    }
} else {
    $_SESSION['error'] = "Permintaan tidak valid.";
}

header("Location: dashboard_admin.php");
exit();
?>