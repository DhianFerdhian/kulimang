<?php
require_once 'config.php';

echo "<h1>Cek Struktur Database</h1>";

// Cek struktur tabel pekerjaan
$stmt = $pdo->query("DESCRIBE pekerjaan");
$columns = $stmt->fetchAll();

echo "<h3>Struktur Tabel pekerjaan:</h3>";
echo "<table border='1'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
foreach ($columns as $col) {
    echo "<tr>";
    echo "<td>{$col['Field']}</td>";
    echo "<td>{$col['Type']}</td>";
    echo "<td>{$col['Null']}</td>";
    echo "<td>{$col['Key']}</td>";
    echo "<td>{$col['Default']}</td>";
    echo "<td>{$col['Extra']}</td>";
    echo "</tr>";
}
echo "</table>";

// Cek data yang ada
echo "<h3>Data Status yang Ada:</h3>";
$stmt = $pdo->query("SELECT DISTINCT status FROM pekerjaan");
$statuses = $stmt->fetchAll();

foreach ($statuses as $status) {
    echo "Status: '{$status['status']}'<br>";
}
?>