<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'user') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get all user's pekerjaan
$stmt = $pdo->prepare("SELECT p.*, k.nama_kategori FROM pekerjaan p 
                    JOIN kategori_pekerjaan k ON p.id_kategori = k.id 
                    WHERE p.id_user = ? ORDER BY p.created_at DESC");
$stmt->execute([$user_id]);
$pekerjaan = $stmt->fetchAll();

// Statistics
$stmt = $pdo->prepare("SELECT status, COUNT(*) as total FROM pekerjaan WHERE id_user = ? GROUP BY status");
$stmt->execute([$user_id]);
$statistics = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pekerjaan Saya - Kulimang</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand fw-bold" href="dashboard_user.php">
                <i class="fas fa-hammer"></i> KULIMANG
            </a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3">
                    <i class="fas fa-user"></i> <?php echo $_SESSION['nama_lengkap']; ?>
                </span>
                <a href="dashboard_user.php" class="btn btn-outline-light btn-sm me-2">
                    <i class="fas fa-arrow-left"></i> Kembali
                </a>
                <a href="logout.php" class="btn btn-outline-light btn-sm">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row mb-4">
            <div class="col-md-8">
                <h3><i class="fas fa-tasks"></i> Semua Pekerjaan Saya</h3>
                <p class="text-muted">Kelola semua pekerjaan konstruksi Anda</p>
            </div>
            <div class="col-md-4 text-end">
                <a href="upload_pekerjaan.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Upload Pekerjaan Baru
                </a>
            </div>
        </div>

        <!-- Statistics -->
        <div class="row mb-4">
            <?php foreach ($statistics as $stat): ?>
            <div class="col-md-3 mb-3">
                <div class="card border-<?php 
                    echo $stat['status'] == 'menunggu' ? 'warning' : 
                         ($stat['status'] == 'diproses' ? 'info' : 'success'); 
                ?>">
                    <div class="card-body text-center">
                        <h4 class="text-<?php 
                            echo $stat['status'] == 'menunggu' ? 'warning' : 
                                 ($stat['status'] == 'diproses' ? 'info' : 'success'); 
                        ?>"><?php echo $stat['total']; ?></h4>
                        <small class="text-muted"><?php echo ucfirst($stat['status']); ?></small>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Daftar Pekerjaan</h5>
                <span class="badge bg-primary">Total: <?php echo count($pekerjaan); ?> Pekerjaan</span>
            </div>
            <div class="card-body">
                <?php if ($pekerjaan): ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Judul</th>
                                    <th>Kategori</th>
                                    <th>Luas</th>
                                    <th>Total Biaya</th>
                                    <th>Lokasi</th>
                                    <th>Status</th>
                                    <th>Tanggal</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pekerjaan as $p): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($p['judul']); ?></strong>
                                            <?php if (strtotime($p['created_at']) > strtotime('-1 day')): ?>
                                                <span class="badge bg-success ms-1">Baru</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary"><?php echo htmlspecialchars($p['nama_kategori']); ?></span>
                                        </td>
                                        <td>
                                            <?php 
                                            // Hitung luas dari panjang dan lebar jika tidak ada kolom luas
                                            if (!empty($p['luas'])) {
                                                echo $p['luas'];
                                            } elseif (!empty($p['panjang']) && !empty($p['lebar'])) {
                                                $luas = $p['panjang'] * $p['lebar'];
                                                echo number_format($luas, 2);
                                            } else {
                                                echo '0';
                                            }
                                            ?> m²
                                        </td>
                                        <td>Rp <?php echo number_format($p['total_biaya'], 0, ',', '.'); ?></td>
                                        <td><?php echo htmlspecialchars($p['lokasi']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $p['status'] == 'menunggu' ? 'warning' : 
                                                    ($p['status'] == 'diproses' ? 'info' : 'success'); 
                                            ?>">
                                                <?php echo ucfirst($p['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('d/m/Y', strtotime($p['created_at'])); ?></td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="detail_pekerjaan.php?id=<?php echo $p['id']; ?>" class="btn btn-outline-primary">
                                                    <i class="fas fa-eye"></i> Detail
                                                </a>
                                                <?php if ($p['status'] == 'menunggu'): ?>
                                                    <a href="edit_pekerjaan.php?id=<?php echo $p['id']; ?>" class="btn btn-outline-warning">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-tasks fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Belum ada pekerjaan yang diupload.</p>
                        <a href="upload_pekerjaan.php" class="btn btn-primary">Upload Pekerjaan Pertama</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>