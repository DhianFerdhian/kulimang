<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'kuli') {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pekerjaan_id = $_POST['pekerjaan_id'];
    $aplikasi_id = $_POST['aplikasi_id'];
    $catatan = $_POST['catatan'] ?? '';
    $user_id = $_SESSION['user_id'];
    
    // Validasi apakah pekerjaan ini milik kuli yang bersangkutan
    $stmt = $pdo->prepare("SELECT * FROM aplikasi_pekerjaan WHERE id = ? AND id_kuli = ?");
    $stmt->execute([$aplikasi_id, $user_id]);
    $aplikasi = $stmt->fetch();
    
    if (!$aplikasi) {
       $_SESSION['last_uploaded_pekerjaan'] = $pekerjaan_id;
header("Location: aplikasi_saya.php");
exit();
    }
    
    // Validasi status harus diterima
    if ($aplikasi['status'] != 'diterima') {
        $_SESSION['error'] = "Pekerjaan belum diterima atau sudah selesai.";
        header("Location: aplikasi_saya.php");
        exit();
    }
    
    // Handle file upload
    if (isset($_FILES['bukti_foto']) && $_FILES['bukti_foto']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/uploads/bukti_selesai/';
        
        // Pastikan folder uploads ada
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        // Generate unique filename
        $fileExtension = pathinfo($_FILES['bukti_foto']['name'], PATHINFO_EXTENSION);
        $fileName = 'bukti_' . $pekerjaan_id . '_' . $user_id . '_' . time() . '.' . $fileExtension;
        $filePath = $uploadDir . $fileName;
        
        // Validasi file (hanya gambar)
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $fileType = mime_content_type($_FILES['bukti_foto']['tmp_name']);
        
        if (!in_array($fileType, $allowedTypes)) {
            $_SESSION['error'] = "Format file tidak didukung. Hanya gambar (JPG, PNG, GIF) yang diperbolehkan.";
            header("Location: aplikasi_saya.php");
            exit();
        }
        
        // Validasi ukuran file (max 5MB)
        if ($_FILES['bukti_foto']['size'] > 5 * 1024 * 1024) {
            $_SESSION['error'] = "Ukuran file terlalu besar. Maksimal 5MB.";
            header("Location: aplikasi_saya.php");
            exit();
        }
        
        // Pindahkan file ke folder uploads
        if (move_uploaded_file($_FILES['bukti_foto']['tmp_name'], $filePath)) {
            // Update database - tambahkan bukti dan ubah status
            try {
                $pdo->beginTransaction();
                
                // Update tabel pekerjaan dengan bukti selesai
                $stmt = $pdo->prepare("UPDATE pekerjaan SET 
                                      bukti_selesai = ?, 
                                      tanggal_selesai = NOW(),
                                      status = 'selesai'
                                      WHERE id = ?");
                $stmt->execute([$fileName, $pekerjaan_id]);
                
                // Update tabel aplikasi_pekerjaan dengan status selesai dan catatan
                $stmt = $pdo->prepare("UPDATE aplikasi_pekerjaan SET 
                                      status = 'selesai',
                                      catatan_selesai = ?,
                                      updated_at = NOW()
                                      WHERE id = ?");
                $stmt->execute([$catatan, $aplikasi_id]);
                
                $pdo->commit();
                
                $_SESSION['success'] = "Bukti pekerjaan berhasil diupload! Status pekerjaan telah berubah menjadi selesai.";
                
            } catch (Exception $e) {
                $pdo->rollBack();
                // Hapus file jika gagal update database
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
                $_SESSION['error'] = "Terjadi kesalahan: " . $e->getMessage();
            }
        } else {
            $_SESSION['error'] = "Gagal mengupload file. Silakan coba lagi.";
        }
    } else {
        $_SESSION['error'] = "Silakan pilih file bukti terlebih dahulu.";
    }
}

header("Location: aplikasi_saya.php");
exit();
?>