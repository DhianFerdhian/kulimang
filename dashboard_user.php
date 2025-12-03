<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'user') {
    header("Location:login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Notifikasi untuk user
$stmt = $pdo->prepare("SELECT COUNT(*) as notif_count FROM aplikasi_pekerjaan ap 
                      JOIN pekerjaan p ON ap.id_pekerjaan = p.id 
                      WHERE p.id_user = ? AND ap.status = 'menunggu'");
$stmt->execute([$user_id]);
$notif_count = $stmt->fetch()['notif_count'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard User - Kulimang</title>
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
        .progress {
            height: 8px;
        }
        /* Fix untuk status badge */
        .status-badge {
            text-transform: capitalize;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand fw-bold" href="dashboard_user.php">
                <i class="fas fa-hammer"></i> KULIMANG
            </a>
            <div class="navbar-nav ms-auto">
                <?php if ($notif_count > 0): ?>
                <div class="nav-item position-relative me-3">
                    <a href="notifications.php" class="nav-link text-white">
                        <i class="fas fa-bell"></i>
                        <span class="notification-badge"><?php echo $notif_count; ?></span>
                    </a>
                </div>
                <?php endif; ?>
                <span class="navbar-text me-3">
                    <i class="fas fa-user"></i> <?php echo $_SESSION['nama_lengkap']; ?>
                    <small class="badge bg-light text-dark ms-1">User</small>
                </span>
                <a href="logout.php" class="btn btn-outline-light btn-sm">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
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

        <!-- Dashboard User -->
        <div class="row mb-4">
            <div class="col-md-8">
                <h3>Dashboard User</h3>
                <p class="text-muted">Kelola pekerjaan konstruksi Anda</p>
            </div>
            <div class="col-md-4 text-end">
                <a href="upload_pekerjaan.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Upload Pekerjaan Baru
                </a>
            </div>
        </div>

        <div class="row">
            <!-- Statistik -->
            <div class="col-md-3 mb-4">
                <div class="card card-hover border-primary">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title text-muted">Total Pekerjaan</h6>
                                <h3 class="text-primary">
                                    <?php
                                    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM pekerjaan WHERE id_user = ?");
                                    $stmt->execute([$user_id]);
                                    echo $stmt->fetch()['total'];
                                    ?>
                                </h3>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-list-alt fa-2x text-primary"></i>
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
                                    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM pekerjaan WHERE id_user = ? AND status = 'menunggu'");
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
                <div class="card card-hover border-info">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title text-muted">Diproses</h6>
                                <h3 class="text-info">
                                    <?php
                                    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM pekerjaan WHERE id_user = ? AND status = 'diproses'");
                                    $stmt->execute([$user_id]);
                                    echo $stmt->fetch()['total'];
                                    ?>
                                </h3>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-tools fa-2x text-info"></i>
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
                                <h6 class="card-title text-muted">Selesai</h6>
                                <h3 class="text-success">
                                    <?php
                                    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM pekerjaan WHERE id_user = ? AND status = 'selesai'");
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
        </div>

        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-tasks"></i> Pekerjaan Saya</h5>
                        <span class="badge bg-primary"><?php
                            $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM pekerjaan WHERE id_user = ?");
                            $stmt->execute([$user_id]);
                            echo $stmt->fetch()['total'];
                        ?> Pekerjaan</span>
                    </div>
                    <div class="card-body">
                        <?php
                        $stmt = $pdo->prepare("SELECT p.*, k.nama_kategori FROM pekerjaan p 
                                            JOIN kategori_pekerjaan k ON p.id_kategori = k.id 
                                            WHERE p.id_user = ? ORDER BY p.created_at DESC LIMIT 10");
                        $stmt->execute([$user_id]);
                        $pekerjaan = $stmt->fetchAll();
                        
                        if ($pekerjaan): ?>
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
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($pekerjaan as $p): 
                                            // PERBAIKAN DISINI: Gunakan ?? untuk menghindari null
                                            $status = $p['status'] ?? 'menunggu';
                                        ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($p['judul'] ?? '-'); ?></td>
                                                <td>
                                                    <span class="badge bg-secondary"><?php echo htmlspecialchars($p['nama_kategori'] ?? '-'); ?></span>
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
                                                <td>Rp <?php echo number_format($p['total_biaya'] ?? 0, 0, ',', '.'); ?></td>
                                                <td><?php echo htmlspecialchars($p['lokasi'] ?? '-'); ?></td>
                                                <td>
                                                    <span class="badge status-badge bg-<?php 
                                                        echo $status == 'menunggu' ? 'warning' : 
                                                            ($status == 'diproses' ? 'info' : 
                                                            ($status == 'selesai' ? 'success' : 'secondary')); 
                                                    ?>">
                                                        <?php echo ucfirst($status); // LINE INI SUDAH DIPERBAIKI ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="detail_pekerjaan_user.php?id=<?php echo $p['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-eye"></i> Detail
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="text-center mt-3">
                                <a href="daftar_pekerjaan_user.php" class="btn btn-outline-primary">Lihat Semua Pekerjaan</a>
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
            
            <div class="col-md-4">
                <!-- Progress Ringkasan -->
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-chart-pie"></i> Ringkasan Progress</h6>
                    </div>
                    <div class="card-body">
                        <?php
                        $stmt = $pdo->prepare("SELECT status, COUNT(*) as total FROM pekerjaan WHERE id_user = ? GROUP BY status");
                        $stmt->execute([$user_id]);
                        $statuses = $stmt->fetchAll();
                        
                        $total = array_sum(array_column($statuses, 'total'));
                        
                        foreach ($statuses as $status): 
                            $percentage = $total > 0 ? ($status['total'] / $total) * 100 : 0;
                            // PERBAIKAN DISINI: Gunakan ?? untuk status
                            $status_name = $status['status'] ?? 'menunggu';
                        ?>
                            <div class="mb-2">
                                <div class="d-flex justify-content-between">
                                    <small><?php echo ucfirst($status_name); // LINE INI SUDAH DIPERBAIKI ?></small>
                                    <small><?php echo $status['total']; ?> (<?php echo round($percentage, 1); ?>%)</small>
                                </div>
                                <div class="progress">
                                    <div class="progress-bar bg-<?php 
                                        echo $status_name == 'menunggu' ? 'warning' : 
                                             ($status_name == 'diproses' ? 'info' : 'success'); 
                                    ?>" style="width: <?php echo $percentage; ?>%"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Info Pekerjaan Terbaru -->
                <div class="card mt-3">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-history"></i> Pekerjaan Terbaru</h6>
                    </div>
                    <div class="card-body">
                        <?php
                        $stmt = $pdo->prepare("SELECT p.judul, p.status, p.created_at 
                                              FROM pekerjaan p 
                                              WHERE p.id_user = ? 
                                              ORDER BY p.created_at DESC 
                                              LIMIT 5");
                        $stmt->execute([$user_id]);
                        $pekerjaan_terbaru = $stmt->fetchAll();
                        
                        if ($pekerjaan_terbaru): 
                            foreach ($pekerjaan_terbaru as $pekerjaan):
                        ?>
                            <div class="d-flex align-items-center mb-3 pb-2 border-bottom">
                                <div class="flex-shrink-0">
                                    <?php
                                    $status = $pekerjaan['status'] ?? 'menunggu';
                                    if ($status == 'selesai') {
                                        echo '<i class="fas fa-check-circle text-success"></i>';
                                    } elseif ($status == 'diproses') {
                                        echo '<i class="fas fa-tools text-info"></i>';
                                    } else {
                                        echo '<i class="fas fa-clock text-warning"></i>';
                                    }
                                    ?>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <small class="d-block fw-bold"><?php echo htmlspecialchars(substr($pekerjaan['judul'] ?? '', 0, 30)) . (strlen($pekerjaan['judul'] ?? '') > 30 ? '...' : ''); ?></small>
                                    <small class="text-muted">
                                        <i class="fas fa-calendar-alt"></i> 
                                        <?php echo date('d M Y', strtotime($pekerjaan['created_at'])); ?>
                                    </small>
                                </div>
                            </div>
                        <?php 
                            endforeach;
                        else: 
                        ?>
                            <p class="text-muted text-center small">Belum ada pekerjaan</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

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
    </script>
</body>
</html>