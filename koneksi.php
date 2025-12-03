<?php
// koneksi.php
$host = "localhost";
$username = "root";
$password = "";
$database = "kulimang"; // Ganti dengan nama database Anda

$koneksi = new mysqli($host, $username, $password, $database);

// Cek koneksi
if ($koneksi->connect_error) {
    die("Koneksi gagal: " . $koneksi->connect_error);
}

// Set charset
$koneksi->set_charset("utf8");
?>