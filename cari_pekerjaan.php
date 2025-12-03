<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config.php';

echo "<h1>Cari Semua Pekerjaan</h1>";

// Cari semua pekerjaan
$stmt = $pdo->prepare("SELECT id, judul, status FROM pekerjaan ORDER BY id DESC LIMIT 10");
$stmt->execute();
$pekerjaan_list = $stmt->fetchAll();

if ($pekerjaan_list) {
    echo "<h3>Daftar Pekerjaan:</h3>";
    foreach ($pekerjaan_list as $p) {
        echo "<p>";
        echo "<strong>ID: {$p['id']}</strong> - ";
        echo "Judul: {$p['judul']} - ";
        echo "Status: <span style='color:";
        echo $p['status'] == 'selesai' ? 'blue' : 
             ($p['status'] == 'completed' ? 'green' : 'orange');
        echo "'>{$p['status']}</span> ";
        echo "<a href='test_status.php?pid={$p['id']}'>Test</a> ";
        echo "<a href='detail_pekerjaan_user.php?id={$p['id']}'>Detail</a>";
        echo "</p>";
    }
} else {
    echo "<p>Tidak ada pekerjaan di database</p>";
}

// Cek aplikasi_pekerjaan
echo "<h3>Data Aplikasi Pekerjaan:</h3>";
$stmt = $pdo->prepare("SELECT ap.*, p.judul, u.nama_lengkap 
                      FROM aplikasi_pekerjaan ap 
                      JOIN pekerjaan p ON ap.id_pekerjaan = p.id 
                      JOIN users u ON ap.id_kuli = u.id 
                      ORDER BY ap.id DESC LIMIT 10");
$stmt->execute();
$aplikasi_list = $stmt->fetchAll();

if ($aplikasi_list) {
    foreach ($aplikasi_list as $app) {
        echo "ID Aplikasi: {$app['id']} | ";
        echo "Pekerjaan: {$app['judul']} | ";
        echo "Kuli: {$app['nama_lengkap']} | ";
        echo "Status: {$app['status']}<br>";
    }
} else {
    echo "Tidak ada aplikasi pekerjaan<br>";
}
?>