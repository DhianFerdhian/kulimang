<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'user') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get completed pekerjaan
$stmt = $pdo->prepare("SELECT p.*, k.nama_kategori,
                              (SELECT u.nama_lengkap FROM aplikasi_pekerjaan ap 
                               JOIN users u ON ap.id_kuli = u.id 
                               WHERE ap.id_pekerjaan = p.id AND ap.status = 'diterima' LIMIT 1) as kuli,
                              (SELECT r.rating FROM rating_pekerjaan r 
                               WHERE r.id_pekerjaan = p.id LIMIT 1) as rating
                      FROM pekerjaan p 
                      JOIN kategori_pekerjaan k ON p.id_kategori = k.id 
                      WHERE p.id_user = ? AND p.status = 'selesai'
                      ORDER BY p.updated_at DESC");
$stmt->execute([$user_id]);
$riwayat = $stmt->fetchAll();

// Hitung statistik
$total_selesai = count($riwayat);
$total_biaya = array_sum(array_column($riwayat, 'total_biaya'));
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Pekerjaan - Kulimang</title>
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
            <div class="col-md-12">
                <h3><i class="fas fa-history"></i> Riwayat Pekerjaan Selesai</h3>
                <p class="text-muted">Daftar pekerjaan yang telah berhasil diselesaikan</p>
            </div>
        </div>

        <!-- Statistik -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card border-success">
                    <div class="card-body text-center">
                        <h4 class="text-success"><?php echo $total_selesai; ?></h4>
                        <small>Total Pekerjaan Selesai</small>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-primary">
                    <div class="card-body text-center">
                        <h4 class="text-primary">Rp <?php echo number_format($total_biaya, 0, ',', '.'); ?></h4>
                        <small>Total Pengeluaran</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Daftar Pekerjaan Selesai</h5>
                <span class="badge bg-success">Total: <?php echo $total_selesai; ?> Pekerjaan</span>
            </div>
            <div class="card-body">
                <?php if ($riwayat): ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Judul</th>
                                    <th>Kuli</th>
                                    <th>Kategori</th>
                                    <th>Biaya</th>
                                    <th>Rating</th>
                                    <th>Tanggal Selesai</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($riwayat as $r): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($r['judul']); ?></td>
                                        <td><?php echo !empty($r['kuli']) ? htmlspecialchars($r['kuli']) : '-'; ?></td>
                                        <td><?php echo htmlspecialchars($r['nama_kategori']); ?></td>
                                        <td>Rp <?php echo number_format($r['total_biaya'], 0, ',', '.'); ?></td>
                                        <td>
                                            <?php if (!empty($r['rating'])): ?>
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <i class="fas fa-star <?php echo $i <= $r['rating'] ? 'text-warning' : 'text-muted'; ?>"></i>
                                                <?php endfor; ?>
                                            <?php else: ?>
                                                <span class="text-muted">Belum ada rating</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo date('d/m/Y', strtotime($r['updated_at'])); ?></td>
                                        <td>
                                            <a href="detail_user.php?id=<?php echo $r['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-eye"></i> Detail
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-history fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Belum ada riwayat pekerjaan yang selesai.</p>
                        <a href="upload_pekerjaan.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Buat Pekerjaan Baru
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>