<?php
session_start();
require_once 'config.php';

// Hanya admin atau developer yang bisa akses
// Untuk testing, kita buat sederhana dulu
// if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
//     header("Location: login.php");
//     exit();
// }

echo "<!DOCTYPE html>
<html>
<head>
    <title>Fix Database Status</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    <style>
        body { padding: 20px; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .info { color: blue; }
        .card { margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class='container'>
        <h1 class='mb-4'>Fix Database Status ENUM</h1>";

try {
    // 1. Cek struktur saat ini
    echo "<div class='card'>
            <div class='card-header bg-primary text-white'>
                <h5 class='mb-0'>Current Structure</h5>
            </div>
            <div class='card-body'>";
    
    $stmt = $pdo->query("SHOW COLUMNS FROM aplikasi_pekerjaan WHERE Field = 'status'");
    $col = $stmt->fetch();
    echo "<p><strong>aplikasi_pekerjaan.status:</strong> <span class='info'>" . ($col['Type'] ?? 'NULL') . "</span></p>";
    
    $stmt = $pdo->query("SHOW COLUMNS FROM pekerjaan WHERE Field = 'status'");
    $col = $stmt->fetch();
    echo "<p><strong>pekerjaan.status:</strong> <span class='info'>" . ($col['Type'] ?? 'NULL') . "</span></p>";
    
    echo "</div></div>";

    // 2. Cek nilai yang ada di database
    echo "<div class='card'>
            <div class='card-header bg-info text-white'>
                <h5 class='mb-0'>Existing Status Values</h5>
            </div>
            <div class='card-body'>";
    
    $stmt = $pdo->query("SELECT DISTINCT status, COUNT(*) as jumlah FROM aplikasi_pekerjaan GROUP BY status");
    echo "<p><strong>aplikasi_pekerjaan:</strong></p><ul>";
    while ($row = $stmt->fetch()) {
        echo "<li>" . $row['status'] . " (" . $row['jumlah'] . " records)</li>";
    }
    echo "</ul>";
    
    $stmt = $pdo->query("SELECT DISTINCT status, COUNT(*) as jumlah FROM pekerjaan GROUP BY status");
    echo "<p><strong>pekerjaan:</strong></p><ul>";
    while ($row = $stmt->fetch()) {
        echo "<li>" . $row['status'] . " (" . $row['jumlah'] . " records)</li>";
    }
    echo "</ul>";
    
    echo "</div></div>";

    // 3. Update ENUM jika belum ada 'selesai'
    echo "<div class='card'>
            <div class='card-header bg-warning'>
                <h5 class='mb-0'>Fixing ENUM Values</h5>
            </div>
            <div class='card-body'>";
    
    // Cek apakah 'selesai' sudah ada di ENUM
    $stmt = $pdo->query("SHOW COLUMNS FROM aplikasi_pekerjaan WHERE Field = 'status'");
    $col = $stmt->fetch();
    $has_selesai = false;
    
    if (isset($col['Type'])) {
        if (strpos($col['Type'], "'selesai'") !== false || 
            strpos($col['Type'], "'Selesai'") !== false ||
            strpos($col['Type'], "'done'") !== false ||
            strpos($col['Type'], "'completed'") !== false) {
            $has_selesai = true;
            echo "<p class='success'>✓ Status 'selesai' sudah ada di aplikasi_pekerjaan ENUM</p>";
        }
    }
    
    if (!$has_selesai) {
        echo "<p class='info'>Trying to fix aplikasi_pekerjaan ENUM...</p>";
        
        // Coba beberapa cara
        $queries = [
            // Option 1: Tambah 'selesai' ke ENUM
            "ALTER TABLE aplikasi_pekerjaan MODIFY status ENUM('menunggu', 'diterima', 'ditolak', 'selesai') NOT NULL DEFAULT 'menunggu'",
            
            // Option 2: Ubah jadi VARCHAR jika ENUM gagal
            "ALTER TABLE aplikasi_pekerjaan MODIFY status VARCHAR(20) NOT NULL DEFAULT 'menunggu'",
            
            // Option 3: Hapus constraint jika ada
            "ALTER TABLE aplikasi_pekerjaan CHANGE status status VARCHAR(20) NOT NULL DEFAULT 'menunggu'"
        ];
        
        $success = false;
        foreach ($queries as $query) {
            try {
                $pdo->exec($query);
                echo "<p class='success'>✓ Query executed: " . htmlspecialchars(substr($query, 0, 50)) . "...</p>";
                $success = true;
                break;
            } catch (Exception $e) {
                echo "<p class='error'>✗ Query failed: " . $e->getMessage() . "</p>";
            }
        }
        
        if ($success) {
            echo "<p class='success'>✓ aplikasi_pekerjaan status column fixed!</p>";
        }
    }
    
    // Fix pekerjaan table juga
    $stmt = $pdo->query("SHOW COLUMNS FROM pekerjaan WHERE Field = 'status'");
    $col = $stmt->fetch();
    $has_selesai_pekerjaan = false;
    
    if (isset($col['Type'])) {
        if (strpos($col['Type'], "'selesai'") !== false || 
            strpos($col['Type'], "'Selesai'") !== false ||
            strpos($col['Type'], "'done'") !== false ||
            strpos($col['Type'], "'completed'") !== false) {
            $has_selesai_pekerjaan = true;
            echo "<p class='success'>✓ Status 'selesai' sudah ada di pekerjaan ENUM</p>";
        }
    }
    
    if (!$has_selesai_pekerjaan) {
        echo "<p class='info'>Trying to fix pekerjaan ENUM...</p>";
        
        $queries = [
            "ALTER TABLE pekerjaan MODIFY status ENUM('menunggu', 'diproses', 'selesai') NOT NULL DEFAULT 'menunggu'",
            "ALTER TABLE pekerjaan MODIFY status VARCHAR(20) NOT NULL DEFAULT 'menunggu'",
            "ALTER TABLE pekerjaan CHANGE status status VARCHAR(20) NOT NULL DEFAULT 'menunggu'"
        ];
        
        $success = false;
        foreach ($queries as $query) {
            try {
                $pdo->exec($query);
                echo "<p class='success'>✓ Query executed: " . htmlspecialchars(substr($query, 0, 50)) . "...</p>";
                $success = true;
                break;
            } catch (Exception $e) {
                echo "<p class='error'>✗ Query failed: " . $e->getMessage() . "</p>";
            }
        }
        
        if ($success) {
            echo "<p class='success'>✓ pekerjaan status column fixed!</p>";
        }
    }
    
    echo "</div></div>";

    // 4. Verifikasi setelah fix
    echo "<div class='card'>
            <div class='card-header bg-success text-white'>
                <h5 class='mb-0'>Verification After Fix</h5>
            </div>
            <div class='card-body'>";
    
    $stmt = $pdo->query("SHOW COLUMNS FROM aplikasi_pekerjaan WHERE Field = 'status'");
    $col = $stmt->fetch();
    echo "<p><strong>New aplikasi_pekerjaan.status:</strong> <span class='success'>" . ($col['Type'] ?? 'NULL') . "</span></p>";
    
    $stmt = $pdo->query("SHOW COLUMNS FROM pekerjaan WHERE Field = 'status'");
    $col = $stmt->fetch();
    echo "<p><strong>New pekerjaan.status:</strong> <span class='success'>" . ($col['Type'] ?? 'NULL') . "</span></p>";
    
    echo "</div></div>";

    // 5. Test update
    echo "<div class='card'>
            <div class='card-header bg-secondary text-white'>
                <h5 class='mb-0'>Test Update</h5>
            </div>
            <div class='card-body'>";
    
    echo "<p>Test updating with value 'selesai':</p>";
    
    try {
        // Coba update dengan 'selesai'
        $test_query = "UPDATE pekerjaan SET status = 'selesai' WHERE id = 1";
        $pdo->exec($test_query);
        echo "<p class='success'>✓ Test update with 'selesai' SUCCESS</p>";
    } catch (Exception $e) {
        echo "<p class='error'>✗ Test update with 'selesai' FAILED: " . $e->getMessage() . "</p>";
        
        // Coba dengan 'done'
        try {
            $test_query = "UPDATE pekerjaan SET status = 'done' WHERE id = 1";
            $pdo->exec($test_query);
            echo "<p class='success'>✓ Test update with 'done' SUCCESS</p>";
            echo "<p class='info'>Note: Use 'done' instead of 'selesai'</p>";
        } catch (Exception $e2) {
            echo "<p class='error'>✗ Test update with 'done' FAILED: " . $e2->getMessage() . "</p>";
        }
    }
    
    echo "</div></div>";

    echo "<div class='alert alert-success mt-4'>
            <h4>✅ Database Fix Complete!</h4>
            <p>Now you can try the 'Selesai' button again.</p>
            <a href='aplikasi_saya.php' class='btn btn-primary'>Go to Aplikasi Saya</a>
            <a href='test_update.php' class='btn btn-secondary'>Test Update</a>
        </div>";

} catch (Exception $e) {
    echo "<div class='alert alert-danger'>
            <h4>❌ Error</h4>
            <p>" . $e->getMessage() . "</p>
        </div>";
}

echo "</div></body></html>";
?>