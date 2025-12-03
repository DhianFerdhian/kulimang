<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'kuli') {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: dashboard_kuli.php");
    exit();
}

$pekerjaan_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

// Get pekerjaan details
$stmt = $pdo->prepare("SELECT p.*, k.nama_kategori, u.nama_lengkap as pemilik, 
                              u.telepon as telepon_pemilik
                       FROM pekerjaan p 
                       JOIN kategori_pekerjaan k ON p.id_kategori = k.id
                       JOIN users u ON p.id_user = u.id
                       WHERE p.id = ?");
$stmt->execute([$pekerjaan_id]);
$pekerjaan = $stmt->fetch();

if (!$pekerjaan) {
    header("Location: dashboard_kuli.php");
    exit();
}

// Check if already applied
$stmt = $pdo->prepare("SELECT * FROM aplikasi_pekerjaan 
                       WHERE id_pekerjaan = ? AND id_kuli = ?");
$stmt->execute([$pekerjaan_id, $user_id]);
$sudah_apply = $stmt->fetch();

// Notifikasi
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
    <title>Detail Pekerjaan - Kulimang</title>
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
        .job-detail-card {
            border-left: 4px solid #0d6efd;
        }
        .owner-info {
            background-color: #f8f9fa;
            border-radius: 8px;
        }
        .status-badge {
            font-size: 0.8rem;
            padding: 0.35rem 0.65rem;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand fw-bold" href="dashboard_kuli.php">
                <i class="fas fa-hammer"></i> KULIMANG
            </a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3">
                    <i class="fas fa-user"></i> <?php echo $_SESSION['nama_lengkap']; ?>
                    <small class="badge bg-light text-dark ms-1">Kuli</small>
                </span>
                <a href="dashboard_kuli.php" class="btn btn-outline-light btn-sm me-2">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <a href="aplikasi_saya.php" class="btn btn-outline-light btn-sm me-2">
                    <i class="fas fa-tasks"></i> Aplikasi Saya
                </a>
                <a href="logout.php" class="btn btn-outline-light btn-sm">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4 mb-5">
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

        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard_kuli.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="aplikasi_saya.php">Aplikasi Saya</a></li>
                <li class="breadcrumb-item active">Detail Pekerjaan</li>
            </ol>
        </nav>

        <div class="row">
            <div class="col-lg-8">
                <div class="card job-detail-card mb-4">
                    <div class="card-header">
                        <h4 class="mb-0"><?php echo htmlspecialchars($pekerjaan['judul']); ?></h4>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <h6><i class="fas fa-info-circle text-primary"></i> Deskripsi Pekerjaan</h6>
                                <p class="text-muted"><?php echo nl2br(htmlspecialchars($pekerjaan['deskripsi'])); ?></p>
                            </div>
                            <div class="col-md-6">
                                <h6><i class="fas fa-map-marker-alt text-primary"></i> Lokasi</h6>
                                <p class="text-muted"><?php echo htmlspecialchars($pekerjaan['lokasi']); ?></p>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <h6><i class="fas fa-layer-group text-primary"></i> Kategori</h6>
                                <span class="badge bg-primary"><?php echo $pekerjaan['nama_kategori']; ?></span>
                            </div>
                            <div class="col-md-4">
                                <h6><i class="fas fa-ruler-combined text-primary"></i> Luas Area</h6>
                                <p class="text-muted">
                                    <?php 
                                    if (!empty($pekerjaan['luas'])) {
                                        echo $pekerjaan['luas'] . ' m²';
                                    } elseif (!empty($pekerjaan['panjang']) && !empty($pekerjaan['lebar'])) {
                                        $luas = $pekerjaan['panjang'] * $pekerjaan['lebar'];
                                        echo number_format($luas, 2) . ' m²';
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </p>
                            </div>
                            <div class="col-md-4">
                                <h6><i class="fas fa-money-bill-wave text-primary"></i> Total Biaya</h6>
                                <h5 class="text-success">Rp <?php echo number_format($pekerjaan['total_biaya'], 0, ',', '.'); ?></h5>
                            </div>
                        </div>

                        <?php if (!empty($pekerjaan['keterangan_tambahan'])): ?>
                        <div class="mb-3">
                            <h6><i class="fas fa-sticky-note text-primary"></i> Keterangan Tambahan</h6>
                            <p class="text-muted"><?php echo nl2br(htmlspecialchars($pekerjaan['keterangan_tambahan'])); ?></p>
                        </div>
                        <?php endif; ?>

                        <div class="row">
                            <div class="col-md-6">
                                <h6><i class="fas fa-calendar-alt text-primary"></i> Tanggal Dibuat</h6>
                                <p class="text-muted"><?php echo date('d F Y H:i', strtotime($pekerjaan['created_at'])); ?></p>
                            </div>
                            <div class="col-md-6">
                                <h6><i class="fas fa-hourglass-half text-primary"></i> Status Pekerjaan</h6>
                                <?php
                                $status_badge_class = 'secondary';
                                $status_text = 'Menunggu';
                                
                                if ($pekerjaan['status'] == 'dikerjakan') {
                                    $status_badge_class = 'primary';
                                    $status_text = 'Sedang Dikerjakan';
                                } elseif ($pekerjaan['status'] == 'selesai') {
                                    $status_badge_class = 'success';
                                    $status_text = 'Selesai';
                                } elseif ($pekerjaan['status'] == 'dibatalkan') {
                                    $status_badge_class = 'danger';
                                    $status_text = 'Dibatalkan';
                                }
                                ?>
                                <span class="badge bg-<?php echo $status_badge_class; ?> status-badge"><?php echo $status_text; ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <!-- Info Pemilik -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-user-tie"></i> Informasi Pemilik</h5>
                    </div>
                    <div class="card-body owner-info">
                        <div class="text-center mb-3">
                            <i class="fas fa-user-circle fa-3x text-muted"></i>
                        </div>
                        <h5 class="text-center"><?php echo $pekerjaan['pemilik']; ?></h5>
                        <?php if (!empty($pekerjaan['telepon_pemilik'])): ?>
                            <p class="text-center text-muted">
                                <i class="fas fa-phone"></i> 
                                <?php echo $pekerjaan['telepon_pemilik']; ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Status Aplikasi -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-clipboard-check"></i> Status Aplikasi Anda</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($sudah_apply): ?>
                            <?php
                            $status_aplikasi = $sudah_apply['status'] ?? 'menunggu';
                            $badge_class = 'secondary';
                            $badge_text = 'Menunggu';
                            
                            if ($status_aplikasi == 'menunggu') {
                                $badge_class = 'warning';
                                $badge_text = 'Menunggu Review';
                            } elseif ($status_aplikasi == 'diterima') {
                                $badge_class = 'success';
                                $badge_text = 'Diterima';
                            } elseif ($status_aplikasi == 'ditolak') {
                                $badge_class = 'danger';
                                $badge_text = 'Ditolak';
                            } elseif ($status_aplikasi == 'selesai') {
                                $badge_class = 'info';
                                $badge_text = 'Selesai';
                            }
                            ?>
                            <div class="text-center">
                                <span class="badge bg-<?php echo $badge_class; ?> p-2 mb-3" style="font-size: 1rem;">
                                    <?php echo $badge_text; ?>
                                </span>
                                <p class="text-muted">
                                    Tanggal Lamar:<br>
                                    <strong><?php echo date('d F Y H:i', strtotime($sudah_apply['created_at'])); ?></strong>
                                </p>
                                <?php if (!empty($sudah_apply['pesan'])): ?>
                                    <div class="alert alert-info mt-3">
                                        <h6><i class="fas fa-comment"></i> Pesan:</h6>
                                        <p><?php echo nl2br(htmlspecialchars($sudah_apply['pesan'])); ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php elseif ($pekerjaan['status'] == 'menunggu'): ?>
                            <div class="text-center">
                                <p class="text-muted mb-3">Anda belum melamar pekerjaan ini</p>
                                <form action="apply_pekerjaan.php" method="POST">
                                    <input type="hidden" name="pekerjaan_id" value="<?php echo $pekerjaan_id; ?>">
                                    <div class="mb-3">
                                        <label for="pesan" class="form-label">Pesan (Opsional)</label>
                                        <textarea class="form-control" id="pesan" name="pesan" rows="3" 
                                                  placeholder="Tulis pesan atau penawaran Anda..."></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-lg w-100">
                                        <i class="fas fa-paper-plane"></i> Lamar Pekerjaan Ini
                                    </button>
                                </form>
                            </div>
                        <?php else: ?>
                            <div class="text-center">
                                <p class="text-muted">Pekerjaan ini sudah tidak tersedia untuk dilamar</p>
                                <button class="btn btn-secondary btn-lg w-100" disabled>
                                    <i class="fas fa-ban"></i> Tidak Dapat Dilamar
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Tombol Aksi -->
                <div class="d-grid gap-2">
                    <a href="aplikasi_saya.php" class="btn btn-outline-primary">
                        <i class="fas fa-arrow-left"></i> Kembali ke Aplikasi Saya
                    </a>
                    <a href="dashboard_kuli.php" class="btn btn-outline-secondary">
                        <i class="fas fa-home"></i> Kembali ke Dashboard
                    </a>
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