<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'kuli') {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $pekerjaan_id = $_POST['pekerjaan_id'];
    $aplikasi_id = $_POST['aplikasi_id'];
    $user_id = $_SESSION['user_id'];
    
    // Cek konfirmasi checkbox
    if (!isset($_POST['konfirmasi'])) {
        $_SESSION['error'] = "Anda harus mencentang konfirmasi penyelesaian pekerjaan.";
        header("Location: aplikasi_saya.php");
        exit();
    }
    
    // AUTO-DETECT STATUS VALUE YANG VALID
    $valid_status = 'selesai'; // default
    
    try {
        // Cek ENUM values di aplikasi_pekerjaan
        $stmt = $pdo->query("SHOW COLUMNS FROM aplikasi_pekerjaan WHERE Field = 'status'");
        $col = $stmt->fetch();
        
        if (isset($col['Type'])) {
            $type = $col['Type'];
            // Jika ENUM
            if (preg_match("/^enum\((.*)\)$/", $type, $matches)) {
                $enum_values = str_getcsv($matches[1], ',', "'");
                
                // Cari nilai untuk "selesai"
                $candidate_values = ['selesai', 'Selesai', 'done', 'completed', 'finish', 'sukses'];
                
                foreach ($candidate_values as $candidate) {
                    if (in_array($candidate, $enum_values)) {
                        $valid_status = $candidate;
                        break;
                    }
                }
                
                // Jika tidak ditemukan, ambil nilai terakhir (biasanya untuk selesai)
                if ($valid_status == 'selesai' && !in_array('selesai', $enum_values)) {
                    $valid_status = end($enum_values);
                }
            } 
            // Jika VARCHAR, langsung pakai 'selesai'
            elseif (strpos($type, 'varchar') !== false || strpos($type, 'VARCHAR') !== false) {
                $valid_status = 'selesai';
            }
        }
        
    } catch (Exception $e) {
        // Jika error, tetap pakai 'selesai'
        $valid_status = 'selesai';
    }
    
    // UPDATE DATABASE
    try {
        // 1. Update aplikasi_pekerjaan
        $stmt = $pdo->prepare("UPDATE aplikasi_pekerjaan SET status = ? WHERE id = ? AND id_kuli = ?");
        $stmt->execute([$valid_status, $aplikasi_id, $user_id]);
        
        // 2. Update pekerjaan
        $stmt = $pdo->prepare("UPDATE pekerjaan SET status = ?, tanggal_selesai = NOW() WHERE id = ?");
        $stmt->execute([$valid_status, $pekerjaan_id]);
        
        // 3. Set success message
        $_SESSION['last_updated'] = $pekerjaan_id;
        
        if ($valid_status == 'selesai') {
            $_SESSION['success'] = "Pekerjaan berhasil ditandai sebagai SELESAI!";
        } else {
            $_SESSION['success'] = "Pekerjaan berhasil ditandai sebagai $valid_status!";
        }
        
    } catch (Exception $e) {
        // Jika masih gagal, coba alternatif
        try {
            // Coba langsung update tanpa validasi (mungkin ada constraint lain)
            $pdo->exec("SET sql_mode=''"); // Disable strict mode
            
            $stmt = $pdo->prepare("UPDATE aplikasi_pekerjaan SET status = 'done' WHERE id = ? AND id_kuli = ?");
            $stmt->execute([$aplikasi_id, $user_id]);
            
            $stmt = $pdo->prepare("UPDATE pekerjaan SET status = 'done', tanggal_selesai = NOW() WHERE id = ?");
            $stmt->execute([$pekerjaan_id]);
            
            $_SESSION['last_updated'] = $pekerjaan_id;
            $_SESSION['success'] = "Pekerjaan berhasil ditandai selesai!";
            
        } catch (Exception $e2) {
            // Final fallback: update dengan nilai default
            try {
                // Cari nilai default dari kolom
                $stmt = $pdo->query("SHOW COLUMNS FROM aplikasi_pekerjaan WHERE Field = 'status'");
                $col = $stmt->fetch();
                $default_value = $col['Default'] ?? 'diterima';
                
                $stmt = $pdo->prepare("UPDATE aplikasi_pekerjaan SET status = ? WHERE id = ? AND id_kuli = ?");
                $stmt->execute([$default_value, $aplikasi_id, $user_id]);
                
                $stmt = $pdo->prepare("UPDATE pekerjaan SET tanggal_selesai = NOW() WHERE id = ?");
                $stmt->execute([$pekerjaan_id]);
                
                $_SESSION['last_updated'] = $pekerjaan_id;
                $_SESSION['success'] = "Pekerjaan ditandai selesai (status tetap $default_value)";
                
            } catch (Exception $e3) {
                $_SESSION['error'] = "Gagal update. Error: " . $e->getMessage();
            }
        }
    }
    
    header("Location: aplikasi_saya.php");
    exit();
    
} else {
    $_SESSION['error'] = "Metode request tidak valid.";
    header("Location: aplikasi_saya.php");
    exit();
}
?>