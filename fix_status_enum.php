<?php
require_once 'config.php';

echo "<h1>Perbaiki Enum Status</h1>";

try {
    // Tambah value 'completed' ke enum
    $sql = "ALTER TABLE pekerjaan MODIFY status ENUM('menunggu','diproses','selesai','completed') NOT NULL DEFAULT 'menunggu'";
    $pdo->exec($sql);
    echo "<div class='alert alert-success'>✅ Enum status berhasil ditambah value 'completed'</div>";
    
    // Cek hasil
    $stmt = $pdo->query("SHOW COLUMNS FROM pekerjaan LIKE 'status'");
    $column = $stmt->fetch();
    
    echo "<h3>Struktur kolom status setelah perbaikan:</h3>";
    echo "<p>Type: {$column['Type']}</p>";
    
    // Test insert data dengan status completed
    $stmt = $pdo->prepare("UPDATE pekerjaan SET status = 'completed' WHERE id = ?");
    $stmt->execute([1]); // Ganti dengan ID yang ada
    echo "<div class='alert alert-success'>✅ Test update status ke 'completed' berhasil</div>";
    
} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
    
    // Alternative solution - ubah ke VARCHAR
    echo "<h3>Alternative Solution:</h3>";
    echo "<p><a href='fix_to_varchar.php'>Ubah ke VARCHAR (recommended)</a></p>";
}
?>