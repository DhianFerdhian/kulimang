<?php
// Koneksi database
$host = 'localhost';
$dbname = 'kulimang';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}

// Konfigurasi upload file
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_FILE_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'pdf']);

// Pastikan direktori upload ada
$upload_dirs = [
    'uploads/bukti_dp/',
    'uploads/bukti_selesai/'
];

foreach ($upload_dirs as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
    }
}

// Fungsi untuk memastikan kolom bukti_selesai ada di tabel pekerjaan
function ensureBuktiSelesaiColumn($pdo) {
    try {
        // Cek apakah kolom bukti_selesai sudah ada
        $stmt = $pdo->prepare("SHOW COLUMNS FROM pekerjaan LIKE 'bukti_selesai'");
        $stmt->execute();
        
        if ($stmt->rowCount() == 0) {
            // Tambahkan kolom jika belum ada
            $sql = "ALTER TABLE pekerjaan ADD COLUMN bukti_selesai VARCHAR(255) NULL AFTER bukti_dp";
            $pdo->exec($sql);
            error_log("Kolom bukti_selesai berhasil ditambahkan ke tabel pekerjaan");
        }
    } catch (PDOException $e) {
        error_log("Error checking/adding bukti_selesai column: " . $e->getMessage());
    }
}

// Panggil fungsi untuk memastikan kolom ada
ensureBuktiSelesaiColumn($pdo);
?>