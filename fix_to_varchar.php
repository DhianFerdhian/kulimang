<?php
require_once 'config.php';

echo "<h1>Ubah Status ke VARCHAR</h1>";

try {
    // Ubah dari ENUM ke VARCHAR
    $sql = "ALTER TABLE pekerjaan MODIFY status VARCHAR(20) NOT NULL DEFAULT 'menunggu'";
    $pdo->exec($sql);
    echo "<div class='alert alert-success'>✅ Kolom status berhasil diubah ke VARCHAR(20)</div>";
    
    // Cek hasil
    $stmt = $pdo->query("SHOW COLUMNS FROM pekerjaan LIKE 'status'");
    $column = $stmt->fetch();
    
    echo "<h3>Struktur kolom status setelah perbaikan:</h3>";
    echo "<p>Type: {$column['Type']}</p>";
    echo "<p>Default: {$column['Default']}</p>";
    
    echo "<div class='alert alert-info'>✅ Sekarang bisa pakai status: menunggu, diproses, selesai, completed</div>";
    
} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
}
?>