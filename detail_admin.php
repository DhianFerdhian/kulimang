<?php
session_start();
require_once 'config.php'; // Path disesuaikan

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php"); // Path disesuaikan
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: dashboard_admin.php");
    exit();
}

$pekerjaan_id = $_GET['id'];

// Ambil data pekerjaan dengan informasi lebih detail
$stmt = $pdo->prepare("SELECT p.*, k.nama_kategori, k.deskripsi as deskripsi_kategori, 
                              u.nama_lengkap as pemilik, u.email,
                              u.id as id_pemilik
                      FROM pekerjaan p 
                      JOIN kategori_pekerjaan k ON p.id_kategori = k.id 
                      JOIN users u ON p.id_user = u.id 
                      WHERE p.id = ?");
$stmt->execute([$pekerjaan_id]);
$pekerjaan = $stmt->fetch();

if (!$pekerjaan) {
    $_SESSION['error'] = "Pekerjaan tidak ditemukan";
    header("Location: dashboard_admin.php");
    exit();
}

// PROSES TERIMA/TOLAK APLIKASI
if (isset($_POST['action_aplikasi'])) {
    $aplikasi_id = $_POST['aplikasi_id'];
    $action = $_POST['action_aplikasi'];
    
    if ($action == 'terima') {
        // Update status aplikasi menjadi diterima
        $stmt = $pdo->prepare("UPDATE aplikasi_pekerjaan SET status = 'diterima' WHERE id = ?");
        if ($stmt->execute([$aplikasi_id])) {
            
            // Update status pekerjaan menjadi diproses
            $stmt = $pdo->prepare("UPDATE pekerjaan SET status = 'diproses' WHERE id = ?");
            $stmt->execute([$pekerjaan_id]);
            
            $_SESSION['success'] = "Aplikasi berhasil diterima dan pekerjaan telah dimulai";
        } else {
            $_SESSION['error'] = "Gagal menerima aplikasi";
        }
        
    } elseif ($action == 'tolak') {
        // Update status aplikasi menjadi ditolak
        $stmt = $pdo->prepare("UPDATE aplikasi_pekerjaan SET status = 'ditolak' WHERE id = ?");
        if ($stmt->execute([$aplikasi_id])) {
            $_SESSION['success'] = "Aplikasi berhasil ditolak";
        } else {
            $_SESSION['error'] = "Gagal menolak aplikasi";
        }
    }
    
    // Redirect untuk menghindari resubmission
    header("Location: detail_admin.php?id=" . $pekerjaan_id);
    exit();
}

// Ambil semua aplikasi pekerjaan
$stmt = $pdo->prepare("SELECT a.*, u.nama_lengkap, u.email as email_kuli
                      FROM aplikasi_pekerjaan a 
                      JOIN users u ON a.id_kuli = u.id 
                      WHERE a.id_pekerjaan = ?
                      ORDER BY a.created_at DESC");
$stmt->execute([$pekerjaan_id]);
$aplikasi = $stmt->fetchAll();

// Hitung statistik
$total_aplikasi = count($aplikasi);
$aplikasi_diterima = array_filter($aplikasi, function($app) {
    return $app['status'] == 'diterima';
});
$aplikasi_menunggu = array_filter($aplikasi, function($app) {
    return $app['status'] == 'menunggu';
});

// Hitung sisa hari (jika sedang diproses)
$sisa_hari = 0;
if ($pekerjaan['status'] == 'diproses' && !empty($pekerjaan['tanggal_mulai']) && !empty($pekerjaan['durasi'])) {
    $tanggal_selesai = date('Y-m-d', strtotime($pekerjaan['tanggal_mulai'] . ' + ' . $pekerjaan['durasi'] . ' days'));
    $today = date('Y-m-d');
    $sisa = (strtotime($tanggal_selesai) - strtotime($today)) / (60 * 60 * 24);
    $sisa_hari = max(0, ceil($sisa));
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Pekerjaan - Admin - Kulimang</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .stat-card {
            border-radius: 10px;
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-2px);
        }
        .kuli-card {
            border-left: 4px solid #0d6efd;
            transition: all 0.3s ease;
        }
        .kuli-card:hover {
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .progress-admin {
            height: 30px;
            font-size: 14px;
            font-weight: bold;
        }
        .info-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .table-fixed {
            table-layout: fixed;
        }
        .table-fixed td {
            word-wrap: break-word;
        }
        .btn-action {
            min-width: 80px;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand fw-bold" href="dashboard_admin.php">
                <i class="fas fa-hammer"></i> KULIMANG
            </a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3">
                    <i class="fas fa-user-shield"></i> <?php echo $_SESSION['nama_lengkap']; ?>
                </span>
                <a href="dashboard_admin.php" class="btn btn-outline-light btn-sm me-2">
                    <i class="fas fa-arrow-left"></i> Kembali
                </a>
                <a href="../logout.php" class="btn btn-outline-light btn-sm"> <!-- Path disesuaikan -->
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

        <!-- Header dan Statistik -->
        <div class="row mb-4">
            <div class="col-md-8">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h2 class="mb-1"><?php echo htmlspecialchars($pekerjaan['judul']); ?></h2>
                        <div class="mb-3">
                            <?php
                            // PERBAIKAN: Handle status yang mungkin null
                            $status = $pekerjaan['status'] ?? 'menunggu';
                            $status_display = $status ? ucfirst($status) : 'Menunggu';
                            $status_badge_color = 'secondary';
                            $status_icon = 'clock';
                            
                            if ($status == 'menunggu') {
                                $status_badge_color = 'warning';
                                $status_icon = 'clock';
                            } elseif ($status == 'diproses') {
                                $status_badge_color = 'info';
                                $status_icon = 'tools';
                            } elseif ($status == 'selesai') {
                                $status_badge_color = 'success';
                                $status_icon = 'check-circle';
                            } elseif ($status == 'ditolak') {
                                $status_badge_color = 'danger';
                                $status_icon = 'times-circle';
                            }
                            ?>
                            <span class="badge bg-<?php echo $status_badge_color; ?> fs-6">
                                <i class="fas fa-<?php echo $status_icon; ?>"></i>
                                <?php echo $status_display; ?>
                            </span>
                            <span class="badge bg-secondary ms-2 fs-6">
                                <i class="fas fa-tag"></i>
                                <?php echo htmlspecialchars($pekerjaan['nama_kategori']); ?>
                            </span>
                        </div>
                    </div>
                    <div class="text-end">
                        <h3 class="text-primary mb-0">Rp <?php echo number_format($pekerjaan['total_biaya'], 0, ',', '.'); ?></h3>
                        <small class="text-muted">Total Biaya</small>
                    </div>
                </div>
                <p class="lead"><?php echo htmlspecialchars($pekerjaan['deskripsi']); ?></p>
            </div>
            
            <div class="col-md-4">
                <div class="row">
                    <div class="col-6 mb-3">
                        <div class="card stat-card border-primary">
                            <div class="card-body text-center p-3">
                                <h4 class="text-primary mb-0"><?php echo $total_aplikasi; ?></h4>
                                <small class="text-muted">Total Aplikasi</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 mb-3">
                        <div class="card stat-card border-success">
                            <div class="card-body text-center p-3">
                                <h4 class="text-success mb-0"><?php echo count($aplikasi_diterima); ?></h4>
                                <small class="text-muted">Diterima</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="card stat-card border-warning">
                            <div class="card-body text-center p-3">
                                <h4 class="text-warning mb-0"><?php echo count($aplikasi_menunggu); ?></h4>
                                <small class="text-muted">Menunggu</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="card stat-card border-info">
                            <div class="card-body text-center p-3">
                                <h4 class="text-info mb-0"><?php echo !empty($pekerjaan['durasi']) ? $pekerjaan['durasi'] : 0; ?></h4>
                                <small class="text-muted">Hari</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8">
                <!-- Informasi Detail -->
                <div class="info-section">
                    <h4 class="mb-4"><i class="fas fa-info-circle text-primary"></i> Informasi Lengkap</h4>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <h5><i class="fas fa-tasks text-primary"></i> Detail Pekerjaan</h5>
                            <table class="table table-sm table-fixed">
                                <tr>
                                    <td width="40%"><strong>Kategori:</strong></td>
                                    <td><?php echo htmlspecialchars($pekerjaan['nama_kategori']); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Luas Area:</strong></td>
                                    <td>
                                        <?php 
                                        // Hitung luas dari panjang dan lebar jika tidak ada kolom luas
                                        if (!empty($pekerjaan['luas'])) {
                                            echo $pekerjaan['luas'] . ' m²';
                                        } elseif (!empty($pekerjaan['panjang']) && !empty($pekerjaan['lebar'])) {
                                            $luas = $pekerjaan['panjang'] * $pekerjaan['lebar'];
                                            echo number_format($luas, 2) . ' m²';
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Lokasi:</strong></td>
                                    <td><?php echo !empty($pekerjaan['lokasi']) ? htmlspecialchars($pekerjaan['lokasi']) : '-'; ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Tanggal Mulai:</strong></td>
                                    <td><?php echo !empty($pekerjaan['tanggal_mulai']) ? date('d F Y', strtotime($pekerjaan['tanggal_mulai'])) : '-'; ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Durasi:</strong></td>
                                    <td><?php echo !empty($pekerjaan['durasi']) ? $pekerjaan['durasi'] . ' hari' : '-'; ?></td>
                                </tr>
                                <?php if ($pekerjaan['status'] == 'diproses' && $sisa_hari > 0): ?>
                                <tr>
                                    <td><strong>Sisa Waktu:</strong></td>
                                    <td><span class="badge bg-warning"><?php echo $sisa_hari; ?> hari</span></td>
                                </tr>
                                <?php endif; ?>
                            </table>
                        </div>
                        
                        <div class="col-md-6">
                            <h5><i class="fas fa-user text-primary"></i> Informasi Pemilik</h5>
                            <table class="table table-sm table-fixed">
                                <tr>
                                    <td width="40%"><strong>Nama:</strong></td>
                                    <td>
                                        <?php echo htmlspecialchars($pekerjaan['pemilik']); ?>
                                        <a href="admin_manage_users.php#user-<?php echo $pekerjaan['id_pemilik']; ?>" class="btn btn-sm btn-outline-primary ms-2">
                                            <i class="fas fa-external-link-alt"></i>
                                        </a>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Email:</strong></td>
                                    <td><?php echo htmlspecialchars($pekerjaan['email']); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Diposting:</strong></td>
                                    <td><?php echo date('d F Y H:i', strtotime($pekerjaan['created_at'])); ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <?php if (!empty($pekerjaan['catatan'])): ?>
                    <div class="mt-4">
                        <h5><i class="fas fa-sticky-note text-warning"></i> Catatan Khusus</h5>
                        <div class="alert alert-warning">
                            <?php echo nl2br(htmlspecialchars($pekerjaan['catatan'])); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Aplikasi Pekerjaan -->
                <div class="info-section">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4 class="mb-0"><i class="fas fa-users text-primary"></i> Aplikasi Pekerjaan</h4>
                        <span class="badge bg-primary fs-6">Total: <?php echo $total_aplikasi; ?> Aplikasi</span>
                    </div>

                    <?php if ($total_aplikasi > 0): ?>
                        <form method="POST" id="formAplikasi">
                            <?php foreach ($aplikasi as $app): ?>
                                <div class="card kuli-card mb-3">
                                    <div class="card-body">
                                        <div class="row align-items-center">
                                            <div class="col-md-2 text-center">
                                                <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                                    <i class="fas fa-user fa-2x"></i>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <h6 class="mb-1"><?php echo htmlspecialchars($app['nama_lengkap']); ?></h6>
                                                <p class="mb-1 text-muted">
                                                    <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($app['email_kuli']); ?>
                                                </p>
                                                <?php if (!empty($app['pesan'])): ?>
                                                    <p class="mb-1 small">
                                                        <i class="fas fa-comment"></i> "<?php echo htmlspecialchars($app['pesan']); ?>"
                                                    </p>
                                                <?php endif; ?>
                                                <small class="text-muted">
                                                    <i class="fas fa-clock"></i> <?php echo date('d F Y H:i', strtotime($app['created_at'])); ?>
                                                </small>
                                            </div>
                                            <div class="col-md-4 text-end">
                                                <div class="mb-2">
                                                    <?php
                                                    // PERBAIKAN: Handle status aplikasi yang mungkin null
                                                    $app_status = $app['status'] ?? 'menunggu';
                                                    $app_status_display = $app_status ? ucfirst($app_status) : 'Menunggu';
                                                    $app_badge_color = 'secondary';
                                                    
                                                    if ($app_status == 'menunggu') $app_badge_color = 'warning';
                                                    elseif ($app_status == 'diterima') $app_badge_color = 'success';
                                                    elseif ($app_status == 'ditolak') $app_badge_color = 'danger';
                                                    ?>
                                                    <span class="badge bg-<?php echo $app_badge_color; ?> fs-6 p-2">
                                                        <?php echo $app_status_display; ?>
                                                    </span>
                                                </div>
                                                <?php if ($app_status == 'menunggu'): ?>
                                                    <div class="btn-group">
                                                        <button type="submit" name="action_aplikasi" value="terima" 
                                                               class="btn btn-success btn-sm btn-action"
                                                               onclick="setAplikasiId(<?php echo $app['id']; ?>, 'terima', '<?php echo htmlspecialchars($app['nama_lengkap']); ?>')">
                                                            <i class="fas fa-check"></i> Terima
                                                        </button>
                                                        <button type="submit" name="action_aplikasi" value="tolak" 
                                                               class="btn btn-danger btn-sm btn-action"
                                                               onclick="setAplikasiId(<?php echo $app['id']; ?>, 'tolak', '<?php echo htmlspecialchars($app['nama_lengkap']); ?>')">
                                                            <i class="fas fa-times"></i> Tolak
                                                        </button>
                                                    </div>
                                                <?php elseif ($app_status == 'diterima'): ?>
                                                    <span class="text-success">
                                                        <i class="fas fa-check-circle"></i> Diterima
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-danger">
                                                        <i class="fas fa-times-circle"></i> Ditolak
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <input type="hidden" name="aplikasi_id" id="aplikasi_id">
                        </form>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                            <p class="text-muted">Belum ada aplikasi yang masuk.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-md-4">
                <!-- Action Panel -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-cogs"></i> Panel Admin</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="admin_edit_pekerjaan.php?id=<?php echo $pekerjaan['id']; ?>" class="btn btn-warning">
                                <i class="fas fa-edit"></i> Edit Pekerjaan
                            </a>
                            <a href="admin_delete_pekerjaan.php?id=<?php echo $pekerjaan['id']; ?>" class="btn btn-danger" onclick="return confirm('Yakin ingin menghapus pekerjaan ini?')">
                                <i class="fas fa-trash"></i> Hapus Pekerjaan
                            </a>
                            <a href="dashboard_admin.php" class="btn btn-secondary">
                                <i class="fas fa-list"></i> Kembali ke Dashboard
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Progress Status -->
                <div class="card mb-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-chart-line"></i> Status Pekerjaan</h5>
                    </div>
                    <div class="card-body">
                        <div class="progress progress-admin mb-3">
                            <div class="progress-bar 
                                <?php 
                                if ($status == 'menunggu') echo 'w-25 bg-warning';
                                elseif ($status == 'diproses') echo 'w-75 bg-info';
                                else echo 'w-100 bg-success';
                                ?>" 
                                role="progressbar">
                                <?php echo strtoupper($status_display); ?>
                            </div>
                        </div>
                        
                        <div class="text-center">
                            <?php if ($status == 'menunggu'): ?>
                                <i class="fas fa-clock fa-2x text-warning mb-2"></i>
                                <p class="mb-1"><strong>Menunggu Kuli</strong></p>
                                <small class="text-muted">Pekerjaan sedang menunggu aplikasi dari kuli</small>
                            <?php elseif ($status == 'diproses'): ?>
                                <i class="fas fa-tools fa-2x text-info mb-2"></i>
                                <p class="mb-1"><strong>Sedang Diproses</strong></p>
                                <small class="text-muted">
                                    Pekerjaan sedang dikerjakan
                                    <?php if ($sisa_hari > 0): ?>
                                        <br><span class="badge bg-warning"><?php echo $sisa_hari; ?> hari tersisa</span>
                                    <?php endif; ?>
                                </small>
                            <?php else: ?>
                                <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                                <p class="mb-1"><strong>Selesai</strong></p>
                                <small class="text-muted">Pekerjaan telah berhasil diselesaikan</small>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Informasi Sistem -->
                <div class="card">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0"><i class="fas fa-database"></i> Informasi Sistem</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm table-borderless">
                            <tr>
                                <td><strong>ID Pekerjaan:</strong></td>
                                <td><code>#<?php echo $pekerjaan['id']; ?></code></td>
                            </tr>
                            <tr>
                                <td><strong>Dibuat:</strong></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($pekerjaan['created_at'])); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Diupdate:</strong></td>
                                <td>
                                    <?php 
                                    // Perbaikan untuk menghindari error pada updated_at
                                    if (!empty($pekerjaan['updated_at']) && $pekerjaan['updated_at'] != '0000-00-00 00:00:00') {
                                        echo date('d/m/Y H:i', strtotime($pekerjaan['updated_at']));
                                    } else {
                                        echo date('d/m/Y H:i', strtotime($pekerjaan['created_at']));
                                    }
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Kategori ID:</strong></td>
                                <td><code>#<?php echo $pekerjaan['id_kategori']; ?></code></td>
                            </tr>
                            <tr>
                                <td><strong>User ID:</strong></td>
                                <td><code>#<?php echo $pekerjaan['id_user']; ?></code></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Fungsi untuk set aplikasi_id dan konfirmasi
        function setAplikasiId(id, action, nama) {
            document.getElementById('aplikasi_id').value = id;
            
            if (action === 'terima') {
                return confirm('Terima aplikasi dari ' + nama + '?');
            } else {
                return confirm('Tolak aplikasi dari ' + nama + '?');
            }
        }

        // Auto hide alerts
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);

        // Add loading animation for action buttons
        document.querySelectorAll('.btn-action').forEach(button => {
            button.addEventListener('click', function(e) {
                const originalText = this.innerHTML;
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';
                this.classList.add('disabled');
                
                setTimeout(() => {
                    this.innerHTML = originalText;
                    this.classList.remove('disabled');
                }, 3000);
            });
        });
    </script>
</body>
</html>