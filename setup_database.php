<?php
// setup_database.php
require_once 'config.php';

try {
    echo "Memulai update database...<br>";
    
    // Cek kolom status
    $stmt = $pdo->query("SHOW COLUMNS FROM pekerjaan LIKE 'status'");
    $status_exists = $stmt->fetch();
    
    if (!$status_exists) {
        echo "Menambahkan kolom status...<br>";
        $pdo->exec("ALTER TABLE pekerjaan ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'menunggu'");
    }
    
    // Cek kolom tanggal_selesai
    $stmt = $pdo->query("SHOW COLUMNS FROM pekerjaan LIKE 'tanggal_selesai'");
    $tanggal_selesai_exists = $stmt->fetch();
    
    if (!$tanggal_selesai_exists) {
        echo "Menambahkan kolom tanggal_selesai...<br>";
        $pdo->exec("ALTER TABLE pekerjaan ADD COLUMN tanggal_selesai DATETIME NULL");
    }
    
    // Update status berdasarkan aplikasi_pekerjaan
    echo "Update status pekerjaan...<br>";
    $sql = "
        UPDATE pekerjaan p
        LEFT JOIN aplikasi_pekerjaan a ON p.id = a.id_pekerjaan 
        SET p.status = 
            CASE 
                WHEN a.status = 'diterima' THEN 'diproses'
                WHEN a.status = 'selesai' THEN 'selesai'
                WHEN a.status = 'completed' THEN 'completed'
                ELSE 'menunggu'
            END,
        p.tanggal_selesai = CASE WHEN a.status = 'completed' THEN NOW() ELSE NULL END
        WHERE a.id IS NOT NULL
    ";
    
    $affected = $pdo->exec($sql);
    echo "Berhasil update {$affected} records<br>";
    
    echo "<br><strong>Setup database selesai!</strong><br>";
    echo "<a href='dashboard_kuli.php'>Kembali ke Dashboard</a>";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>