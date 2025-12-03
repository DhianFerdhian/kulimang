<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'kuli') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Notifikasi untuk kuli
$stmt = $pdo->prepare("SELECT COUNT(*) as notif_count FROM aplikasi_pekerjaan 
                    WHERE id_kuli = ? AND status = 'diterima'");
$stmt->execute([$user_id]);
$notif_count = $stmt->fetch()['notif_count'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Kuli - Kulimang</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #dc3545;
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .card-hover:hover {
            transform: translateY(-2px);
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .job-card {
            border-left: 4px solid #0d6efd;
        }
    </style>
</head>
<body>
    <!-- NAVBAR BARU -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <!-- Logo/Brand -->
            <a class="navbar-brand fw-bold" href="dashboard_kuli.php">
                <i class="fas fa-hammer"></i> KULIMANG
            </a>
            
            <!-- Toggle button untuk mobile -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <!-- Navbar items -->
            <div class="collapse navbar-collapse" id="navbarNav">
                <div class="navbar-nav ms-auto align-items-center">
                    <!-- Notifikasi Bell -->
                    
                    <!-- Dropdown User Menu -->
                    <div class="nav-item dropdown me-3">
                        <a class="nav-link dropdown-toggle text-white" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($_SESSION['nama_lengkap']); ?>
                            <small class="badge bg-light text-dark ms-1">Kuli</small>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user"></i> Profil Saya</a></li>
                            <li><a class="dropdown-item" href="aplikasi_saya.php"><i class="fas fa-file-alt"></i> Aplikasi Saya</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="semua_pekerjaan.php"><i class="fas fa-search"></i> Cari Pekerjaan</a></li>
                        </ul>
                    </div>
                    
                    <!-- Tombol Logout di navbar -->
                    <div class="nav-item">
                        <a href="logout.php" class="btn btn-outline-light btn-sm">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Notifikasi -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Dashboard Kuli -->
        <div class="row mb-4">
            <div class="col-md-8">
                <h3>Dashboard Kuli</h3>
                <p class="text-muted">Temukan dan lamar pekerjaan yang sesuai dengan keahlian Anda</p>
            </div>
            <div class="col-md-4 text-end">
                <a href="semua_pekerjaan.php" class="btn btn-primary">
                    <i class="fas fa-search"></i> Cari Semua Pekerjaan
                </a>
                <a href="aplikasi_saya.php" class="btn btn-outline-primary ms-2">
                    <i class="fas fa-file-alt"></i> Aplikasi Saya
                </a>
            </div>
        </div>

        <div class="row">
            <!-- Statistik Kuli -->
            <div class="col-md-3 mb-4">
                <div class="card card-hover border-primary">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title text-muted">Total Aplikasi</h6>
                                <h3 class="text-primary">
                                    <?php
                                    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM aplikasi_pekerjaan WHERE id_kuli = ?");
                                    $stmt->execute([$user_id]);
                                    echo $stmt->fetch()['total'];
                                    ?>
                                </h3>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-paper-plane fa-2x text-primary"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-4">
                <div class="card card-hover border-success">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title text-muted">Diterima</h6>
                                <h3 class="text-success">
                                    <?php
                                    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM aplikasi_pekerjaan WHERE id_kuli = ? AND status = 'diterima'");
                                    $stmt->execute([$user_id]);
                                    echo $stmt->fetch()['total'];
                                    ?>
                                </h3>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-check-circle fa-2x text-success"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-4">
                <div class="card card-hover border-warning">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title text-muted">Menunggu</h6>
                                <h3 class="text-warning">
                                    <?php
                                    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM aplikasi_pekerjaan WHERE id_kuli = ? AND status = 'menunggu'");
                                    $stmt->execute([$user_id]);
                                    echo $stmt->fetch()['total'];
                                    ?>
                                </h3>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-clock fa-2x text-warning"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-4">
                <div class="card card-hover border-danger">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title text-muted">Ditolak</h6>
                                <h3 class="text-danger">
                                    <?php
                                    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM aplikasi_pekerjaan WHERE id_kuli = ? AND status = 'ditolak'");
                                    $stmt->execute([$user_id]);
                                    echo $stmt->fetch()['total'];
                                    ?>
                                </h3>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-times-circle fa-2x text-danger"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-briefcase"></i> Pekerjaan Tersedia</h5>
                        <a href="semua_pekerjaan.php" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-arrow-right"></i> Lihat Semua
                        </a>
                    </div>
                    <div class="card-body">
                        <?php
                        // Query untuk pekerjaan yang belum dilamar oleh kuli ini
                        $stmt = $pdo->prepare("
                            SELECT DISTINCT p.*, k.nama_kategori, u.nama_lengkap as pemilik 
                            FROM pekerjaan p 
                            JOIN kategori_pekerjaan k ON p.id_kategori = k.id 
                            JOIN users u ON p.id_user = u.id 
                            WHERE p.status = 'menunggu' 
                            AND p.id NOT IN (
                                SELECT id_pekerjaan 
                                FROM aplikasi_pekerjaan 
                                WHERE id_kuli = ?
                            )
                            AND p.id_user != ?
                            ORDER BY p.created_at DESC 
                            LIMIT 5
                        ");
                        $stmt->execute([$user_id, $user_id]);
                        $pekerjaan = $stmt->fetchAll();
                        
                        if ($pekerjaan): ?>
                            <?php foreach ($pekerjaan as $p): ?>
                                <div class="card mb-3 card-hover job-card">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                <h6 class="text-primary"><?php echo htmlspecialchars($p['judul']); ?></h6>
                                                <p class="text-muted mb-2 small"><?php echo htmlspecialchars(substr($p['deskripsi'], 0, 120)); ?>...</p>
                                                <div class="row g-2 mb-2">
                                                    <div class="col-md-6">
                                                        <small><i class="fas fa-tag"></i> <strong>Kategori:</strong> <?php echo $p['nama_kategori']; ?></small>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <small><i class="fas fa-expand-arrows-alt"></i> <strong>Luas:</strong> 
                                                            <?php 
                                                            if (!empty($p['luas'])) {
                                                                echo number_format($p['luas'], 2);
                                                            } elseif (!empty($p['panjang']) && !empty($p['lebar'])) {
                                                                $luas = $p['panjang'] * $p['lebar'];
                                                                echo number_format($luas, 2);
                                                            } else {
                                                                echo '0';
                                                            }
                                                            ?> m²
                                                        </small>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <small><i class="fas fa-money-bill-wave"></i> <strong>Biaya:</strong> Rp <?php echo number_format($p['total_biaya'], 0, ',', '.'); ?></small>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <small><i class="fas fa-map-marker-alt"></i> <strong>Lokasi:</strong> <?php echo htmlspecialchars($p['lokasi']); ?></small>
                                                    </div>
                                                </div>
                                                <div class="mt-2">
                                                    <small><i class="fas fa-user-tie"></i> <strong>Pemilik:</strong> <?php echo $p['pemilik']; ?></small>
                                                    <small class="ms-3"><i class="fas fa-calendar"></i> <strong>Post:</strong> <?php echo date('d/m/Y', strtotime($p['created_at'])); ?></small>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mt-3 d-flex justify-content-between">
                                            <div>
                                                <a href="detail_pekerjaan.php?id=<?php echo $p['id']; ?>" class="btn btn-outline-primary btn-sm">
                                                    <i class="fas fa-eye"></i> Detail
                                                </a>
                                                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#applyModal<?php echo $p['id']; ?>">
                                                    <i class="fas fa-paper-plane"></i> Lamar Sekarang
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Modal untuk Apply -->
                                <div class="modal fade" id="applyModal<?php echo $p['id']; ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Lamar Pekerjaan</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form action="apply_pekerjaan.php" method="POST">
                                                <div class="modal-body">
                                                    <h6><?php echo htmlspecialchars($p['judul']); ?></h6>
                                                    <p class="text-muted small"><?php echo $p['pemilik']; ?> • <?php echo $p['lokasi']; ?></p>
                                                    
                                                    <div class="mb-3">
                                                        <label for="pesan<?php echo $p['id']; ?>" class="form-label">Pesan Lamaran</label>
                                                        <textarea class="form-control" id="pesan<?php echo $p['id']; ?>" 
                                                                  name="pesan" rows="3" 
                                                                  placeholder="Tulis pesan singkat mengapa Anda cocok untuk pekerjaan ini..."></textarea>
                                                    </div>
                                                    
                                                    <div class="alert alert-info">
                                                        <i class="fas fa-info-circle"></i> Lamaran Anda akan dikirim kepada pemilik pekerjaan untuk ditinjau.
                                                    </div>
                                                    
                                                    <input type="hidden" name="pekerjaan_id" value="<?php echo $p['id']; ?>">
                                                    <input type="hidden" name="kuli_id" value="<?php echo $user_id; ?>">
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                    <button type="submit" class="btn btn-primary">Kirim Lamaran</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-briefcase fa-3x text-muted mb-3"></i>
                                <p class="text-muted">Tidak ada pekerjaan tersedia saat ini.</p>
                                <p class="text-muted small">Semua pekerjaan sudah Anda lamar atau belum ada pekerjaan baru.</p>
                                <a href="semua_pekerjaan.php" class="btn btn-primary">Cari Pekerjaan Lain</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <!-- Aplikasi Terbaru -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><i class="fas fa-history"></i> Aplikasi Terbaru</h6>
                        <a href="aplikasi_saya.php" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                    <div class="card-body">
                        <?php
                        $stmt = $pdo->prepare("
                            SELECT a.*, p.judul, p.lokasi 
                            FROM aplikasi_pekerjaan a 
                            JOIN pekerjaan p ON a.id_pekerjaan = p.id 
                            WHERE a.id_kuli = ? 
                            ORDER BY a.created_at DESC 
                            LIMIT 6
                        ");
                        $stmt->execute([$user_id]);
                        $aplikasi = $stmt->fetchAll();
                        
                        if ($aplikasi): ?>
                            <?php foreach ($aplikasi as $a): ?>
                                <div class="mb-3 pb-2 border-bottom">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="flex-grow-1">
                                            <small><strong><?php echo htmlspecialchars($a['judul']); ?></strong></small>
                                            <br>
                                            <small class="text-muted"><?php echo htmlspecialchars($a['lokasi']); ?></small>
                                        </div>
                                        <div>
                                            <?php
                                            $status = $a['status'] ?? 'menunggu';
                                            $status_display = $status ? ucfirst($status) : 'Menunggu';
                                            $badge_color = 'secondary';
                                            
                                            if ($status == 'menunggu') $badge_color = 'warning';
                                            elseif ($status == 'diterima') $badge_color = 'success';
                                            elseif ($status == 'ditolak') $badge_color = 'danger';
                                            ?>
                                            <span class="badge bg-<?php echo $badge_color; ?>">
                                                <?php echo $status_display; ?>
                                            </span>
                                        </div>
                                    </div>
                                    
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-muted text-center">Belum ada aplikasi pekerjaan.</p>
                            <div class="text-center">
                                <a href="semua_pekerjaan.php" class="btn btn-sm btn-outline-primary">Cari Pekerjaan</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Pekerjaan Aktif -->
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-tools"></i> Pekerjaan Aktif</h6>
                    </div>
                    <div class="card-body">
                        <?php
                        $stmt = $pdo->prepare("
                            SELECT a.*, p.judul, p.lokasi, p.total_biaya 
                            FROM aplikasi_pekerjaan a 
                            JOIN pekerjaan p ON a.id_pekerjaan = p.id 
                            WHERE a.id_kuli = ? AND a.status = 'diterima' 
                            ORDER BY a.created_at DESC 
                            LIMIT 3
                        ");
                        $stmt->execute([$user_id]);
                        $pekerjaan_aktif = $stmt->fetchAll();
                        
                        if ($pekerjaan_aktif): ?>
                            <?php foreach ($pekerjaan_aktif as $pa): ?>
                                <div class="mb-3 pb-2 border-bottom">
                                    <small><strong><?php echo htmlspecialchars($pa['judul']); ?></strong></small>
                                    <br>
                                    <small class="text-muted">
                                        <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($pa['lokasi']); ?>
                                    </small>
                                    <br>
                                    <small>
                                        <i class="fas fa-money-bill-wave"></i> Rp <?php echo number_format($pa['total_biaya'], 0, ',', '.'); ?>
                                    </small>
                                    <div class="mt-1">
                                        <a href="detail_pekerjaan.php?id=<?php echo $pa['id_pekerjaan']; ?>" class="btn btn-sm btn-outline-success">
                                            <i class="fas fa-info-circle"></i> Detail Kerja
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-muted text-center small">Belum ada pekerjaan aktif</p>
                            <div class="text-center">
                                <a href="semua_pekerjaan.php" class="btn btn-sm btn-outline-primary">Cari Pekerjaan</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-chart-bar"></i> Ringkasan Minggu Ini</h6>
                    </div>
                    <div class="card-body">
                        <?php
                        // Aplikasi minggu ini
                        $stmt = $pdo->prepare("
                            SELECT COUNT(*) as count 
                            FROM aplikasi_pekerjaan 
                            WHERE id_kuli = ? 
                            AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                        ");
                        $stmt->execute([$user_id]);
                        $weekly_apps = $stmt->fetch()['count'];
                        
                        // Pekerjaan diterima minggu ini
                        $stmt = $pdo->prepare("
                            SELECT COUNT(*) as count 
                            FROM aplikasi_pekerjaan 
                            WHERE id_kuli = ? 
                            AND status = 'diterima'
                            AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                        ");
                        $stmt->execute([$user_id]);
                        $weekly_accepted = $stmt->fetch()['count'];
                        ?>
                        <div class="row text-center">
                            <div class="col-6">
                                <h4 class="text-primary"><?php echo $weekly_apps; ?></h4>
                                <small class="text-muted">Aplikasi</small>
                            </div>
                            <div class="col-6">
                                <h4 class="text-success"><?php echo $weekly_accepted; ?></h4>
                                <small class="text-muted">Diterima</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-light mt-5 py-3 border-top">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-0 text-muted">
                        <i class="fas fa-hammer"></i> <strong>KULIMANG</strong> &copy; <?php echo date('Y'); ?>
                    </p>
                </div>
                <div class="col-md-6 text-end">
                    <p class="mb-0 text-muted">
                        <?php echo $_SESSION['nama_lengkap']; ?> | 
                        <span class="badge bg-primary">Kuli</span>
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);

        // Handle modal form submission
        document.addEventListener('DOMContentLoaded', function() {
            const applyForms = document.querySelectorAll('form[action="apply_pekerjaan.php"]');
            applyForms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    const textarea = this.querySelector('textarea[name="pesan"]');
                    if (textarea && textarea.value.trim().length > 200) {
                        e.preventDefault();
                        alert('Pesan maksimal 200 karakter.');
                        return false;
                    }
                    return true;
                });
            });
        });
    </script>
</body>
</html>