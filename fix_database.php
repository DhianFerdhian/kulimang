<?php
require_once 'config.php';

echo "<h1>Perbaiki Struktur Database</h1>";

try {
    // Ubah tipe data kolom status
    $sql = "ALTER TABLE pekerjaan MODIFY status VARCHAR(20) NOT NULL DEFAULT 'menunggu'";
    $pdo->exec($sql);
    echo "<div class='alert alert-success'>✅ Kolom status berhasil diubah ke VARCHAR(20)</div>";
    
    // Update data yang ada
    $update_sql = "UPDATE pekerjaan SET 
        status = CASE 
            WHEN status = '1' THEN 'menunggu'
            WHEN status = '2' THEN 'diproses' 
            WHEN status = '3' THEN 'selesai'
            WHEN status = '4' THEN 'completed'
            ELSE status
        END";
    $affected = $pdo->exec($update_sql);
    echo "<div class='alert alert-success'>✅ $affected data berhasil diupdate</div>";
    
    // Cek hasil
    $stmt = $pdo->query("SELECT DISTINCT status FROM pekerjaan");
    $statuses = $stmt->fetchAll();
    
    echo "<h3>Status setelah perbaikan:</h3>";
    foreach ($statuses as $status) {
        echo "Status: '{$status['status']}'<br>";
    }
    
} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
}
?>