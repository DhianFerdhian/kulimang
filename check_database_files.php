<?php
session_start();
require_once 'config.php';

echo "<h2>Check Database and Files</h2>";

// Query untuk melihat semua pekerjaan dengan bukti_selesai
$stmt = $pdo->query("SELECT id, judul, bukti_selesai, tanggal_selesai FROM pekerjaan WHERE bukti_selesai IS NOT NULL");
$results = $stmt->fetchAll();

echo "<h3>Files in Database:</h3>";
if ($results) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Judul</th><th>File Name</th><th>Tanggal Selesai</th><th>File Exists?</th></tr>";
    
    foreach ($results as $row) {
        $file_name = $row['bukti_selesai'];
        $file_path = __DIR__ . '/uploads/bukti_selesai/' . $file_name;
        $web_path = 'uploads/bukti_selesai/' . $file_name;
        $file_exists = file_exists($file_path);
        
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['judul']) . "</td>";
        echo "<td>" . $file_name . "</td>";
        echo "<td>" . $row['tanggal_selesai'] . "</td>";
        echo "<td>" . ($file_exists ? '✓ YES' : '✗ NO') . "</td>";
        echo "</tr>";
        
        if ($file_exists) {
            echo "<tr><td colspan='5'>";
            echo "Full Path: " . $file_path . "<br>";
            echo "Web Path: " . $web_path . "<br>";
            echo "File Size: " . filesize($file_path) . " bytes<br>";
            echo "<img src='" . $web_path . "' style='max-width: 200px; border: 1px solid #ccc;'><br>";
            echo "</td></tr>";
        }
    }
    echo "</table>";
} else {
    echo "Tidak ada data dengan bukti selesai di database.<br>";
}

echo "<hr>";

// Cek aplikasi_pekerjaan
echo "<h3>Status Aplikasi Pekerjaan:</h3>";
$stmt = $pdo->query("SELECT status, COUNT(*) as count FROM aplikasi_pekerjaan GROUP BY status");
$statuses = $stmt->fetchAll();

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Status</th><th>Count</th></tr>";
foreach ($statuses as $status) {
    echo "<tr><td>" . $status['status'] . "</td><td>" . $status['count'] . "</td></tr>";
}
echo "</table>";

echo "<hr>";
echo "<a href='aplikasi_saya.php'>Kembali ke Aplikasi Saya</a>";
?>