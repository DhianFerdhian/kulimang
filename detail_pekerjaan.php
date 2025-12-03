<?php
session_start();
require_once 'config.php';

// Redirect jika belum login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get pekerjaan ID from URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: semua_pekerjaan.php");
    exit();
}

$pekerjaan_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

// Get pekerjaan details
$stmt = $pdo->prepare("
    SELECT p.*, 
           k.nama_kategori, 
           u.nama_lengkap as pemilik_nama,
           u.telepon as pemilik_telepon,
           u.email as pemilik_email
    FROM pekerjaan p
    JOIN kategori_pekerjaan k ON p.id_kategori = k.id
    JOIN users u ON p.id_user = u.id
    WHERE p.id = ?
");
$stmt->execute([$pekerjaan_id]);
$pekerjaan = $stmt->fetch();

if (!$pekerjaan) {
    header("Location: semua_pekerjaan.php");
    exit();
}

// Check if user has already applied
$stmt = $pdo->prepare("
    SELECT * FROM aplikasi_pekerjaan 
    WHERE id_pekerjaan = ? AND id_kuli = ?
");
$stmt->execute([$pekerjaan_id, $user_id]);
$has_applied = $stmt->fetch();

// For kuli: get application status
if ($user_role == 'kuli' && $has_applied) {
    $application_status = $has_applied['status'] ?? 'menunggu';
}

// Format tanggal
$created_date = date('d F Y', strtotime($pekerjaan['created_at']));
$updated_date = date('d F Y', strtotime($pekerjaan['updated_at']));

// Calculate luas if not set
$luas = $pekerjaan['luas'] ?? 0;
if (!$luas && isset($pekerjaan['panjang']) && isset($pekerjaan['lebar'])) {
    $luas = $pekerjaan['panjang'] * $pekerjaan['lebar'];
}
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
        .job-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            padding: 30px;
            margin-bottom: 30px;
        }
        .detail-card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .detail-card .card-header {
            background-color: #f8f9fa;
            border-bottom: 2px solid #e9ecef;
            font-weight: 600;
        }
        .info-item {
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #f0f0f0;
        }
        .info-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        .badge-status {
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
        }
        .status-menunggu { background-color: #fff3cd; color: #856404; }
        .status-berlangsung { background-color: #cff4fc; color: #055160; }
        .status-selesai { background-color: #d4edda; color: #155724; }
        .status-batal { background-color: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand fw-bold" href="<?php echo ($user_role == 'kuli') ? 'dashboard_kuli.php' : 'dashboard.php'; ?>">
                <i class="fas fa-hammer"></i> KULIMANG
            </a>
            <div class="navbar-nav ms-auto">
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle text-white" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['nama_lengkap'] ?? 'User'); ?>
                        <small class="badge bg-light text-dark ms-1"><?php echo htmlspecialchars($user_role); ?></small>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="<?php echo ($user_role == 'kuli') ? 'dashboard_kuli.php' : 'dashboard.php'; ?>">
                            <i class="fas fa-home"></i> Dashboard
                        </a></li>
                        <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user-circle"></i> Profil</a></li>
                        <?php if ($user_role == 'kuli'): ?>
                        <li><a class="dropdown-item" href="aplikasi_saya.php"><i class="fas fa-file-alt"></i> Aplikasi Saya</a></li>
                        <?php endif; ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="logout.php" onclick="return confirm('Apakah Anda yakin ingin logout?')">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Back Button -->
        <div class="mb-4">
            <a href="javascript:history.back()" class="btn btn-outline-primary">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>

        <!-- Job Header -->
        <div class="job-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h2 class="mb-3"><?php echo htmlspecialchars($pekerjaan['judul'] ?? 'Pekerjaan'); ?></h2>
                    <div class="d-flex flex-wrap gap-3">
                        <span class="badge bg-light text-dark fs-6">
                            <i class="fas fa-tag"></i> <?php echo htmlspecialchars($pekerjaan['nama_kategori'] ?? 'Kategori'); ?>
                        </span>
                        <span class="badge bg-light text-dark fs-6">
                            <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($pekerjaan['lokasi'] ?? 'Lokasi'); ?>
                        </span>
                        <span class="badge bg-light text-dark fs-6">
                            <i class="fas fa-money-bill-wave"></i> Rp <?php echo number_format($pekerjaan['total_biaya'] ?? 0, 0, ',', '.'); ?>
                        </span>
                    </div>
                </div>
                <div class="col-md-4 text-end">
                    <?php
                    $status = $pekerjaan['status'] ?? 'menunggu';
                    $status_display = ucfirst($status);
                    $status_class = 'status-menunggu';
                    
                    switch($status) {
                        case 'menunggu':
                            $status_class = 'status-menunggu';
                            break;
                        case 'berlangsung':
                            $status_class = 'status-berlangsung';
                            break;
                        case 'selesai':
                            $status_class = 'status-selesai';
                            break;
                        case 'batal':
                            $status_class = 'status-batal';
                            break;
                    }
                    ?>
                    <span class="badge-status <?php echo $status_class; ?>">
                        <?php echo $status_display; ?>
                    </span>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Left Column: Job Details -->
            <div class="col-lg-8">
                <!-- Deskripsi Pekerjaan -->
                <div class="card detail-card">
                    <div class="card-header">
                        <i class="fas fa-file-alt me-2"></i> Deskripsi Pekerjaan
                    </div>
                    <div class="card-body">
                        <p><?php echo nl2br(htmlspecialchars($pekerjaan['deskripsi'] ?? 'Tidak ada deskripsi')); ?></p>
                    </div>
                </div>

                <!-- Detail Informasi -->
                <div class="card detail-card">
                    <div class="card-header">
                        <i class="fas fa-info-circle me-2"></i> Detail Informasi
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="info-item">
                                    <h6><i class="fas fa-ruler-combined text-primary"></i> Luas Area</h6>
                                    <p class="mb-0"><?php echo number_format($luas, 2); ?> m²</p>
                                    <?php if (isset($pekerjaan['panjang']) && isset($pekerjaan['lebar'])): ?>
                                    <small class="text-muted">
                                        (Panjang: <?php echo $pekerjaan['panjang']; ?>m × Lebar: <?php echo $pekerjaan['lebar']; ?>m)
                                    </small>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-item">
                                    <h6><i class="fas fa-money-bill-wave text-success"></i> Biaya Pekerjaan</h6>
                                    <p class="mb-0 fs-5 fw-bold text-success">
                                        Rp <?php echo number_format($pekerjaan['total_biaya'] ?? 0, 0, ',', '.'); ?>
                                    </p>
                                    <?php if (!empty($pekerjaan['biaya_per_meter'])): ?>
                                    <small class="text-muted">
                                        (Rp <?php echo number_format($pekerjaan['biaya_per_meter'], 0, ',', '.'); ?>/m²)
                                    </small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="info-item">
                                    <h6><i class="fas fa-map-marker-alt text-danger"></i> Lokasi</h6>
                                    <p class="mb-0"><?php echo htmlspecialchars($pekerjaan['lokasi'] ?? 'Tidak ditentukan'); ?></p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-item">
                                    <h6><i class="fas fa-calendar-alt text-info"></i> Timeline</h6>
                                    <p class="mb-0">
                                        <?php if (!empty($pekerjaan['estimasi_waktu'])): ?>
                                        Estimasi: <?php echo htmlspecialchars($pekerjaan['estimasi_waktu']); ?> hari
                                        <?php else: ?>
                                        Estimasi waktu tidak ditentukan
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <?php if (!empty($pekerjaan['catatan_khusus'])): ?>
                        <div class="info-item">
                            <h6><i class="fas fa-exclamation-triangle text-warning"></i> Catatan Khusus</h6>
                            <p class="mb-0"><?php echo nl2br(htmlspecialchars($pekerjaan['catatan_khusus'])); ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Informasi Pemilik -->
                <div class="card detail-card">
                    <div class="card-header">
                        <i class="fas fa-user-tie me-2"></i> Informasi Pemilik
                    </div>
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="flex-grow-1">
                                <h5 class="mb-1"><?php echo htmlspecialchars($pekerjaan['pemilik_nama'] ?? 'Tidak diketahui'); ?></h5>
                                <p class="text-muted mb-0">
                                    <i class="fas fa-user-circle"></i> Pemilik Pekerjaan
                                </p>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="info-item">
                                    <h6><i class="fas fa-phone text-primary"></i> Kontak</h6>
                                    <p class="mb-0">
                                        <?php if (!empty($pekerjaan['pemilik_telepon'])): ?>
                                        <?php echo htmlspecialchars($pekerjaan['pemilik_telepon']); ?>
                                        <?php else: ?>
                                        <span class="text-muted">Tidak tersedia</span>
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-item">
                                    <h6><i class="fas fa-envelope text-primary"></i> Email</h6>
                                    <p class="mb-0">
                                        <?php if (!empty($pekerjaan['pemilik_email'])): ?>
                                        <?php echo htmlspecialchars($pekerjaan['pemilik_email']); ?>
                                        <?php else: ?>
                                        <span class="text-muted">Tidak tersedia</span>
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Job Metadata -->
            <div class="col-lg-4">
                <!-- Job Metadata -->
                <div class="card detail-card">
                    <div class="card-header">
                        <i class="fas fa-calendar me-2"></i> Informasi Waktu
                    </div>
                    <div class="card-body">
                        <div class="info-item">
                            <h6><i class="fas fa-plus-circle text-success"></i> Diposting</h6>
                            <p class="mb-0"><?php echo $created_date; ?></p>
                        </div>
                        <div class="info-item">
                            <h6><i class="fas fa-sync-alt text-primary"></i> Terakhir Update</h6>
                            <p class="mb-0"><?php echo $updated_date; ?></p>
                        </div>
                    </div>
                </div>

                <!-- Status Info -->
                <div class="card detail-card mt-4">
                    <div class="card-header">
                        <i class="fas fa-info-circle me-2"></i> Status Pekerjaan
                    </div>
                    <div class="card-body">
                        <div class="text-center">
                            <?php
                            $icon = 'fa-clock';
                            $color = 'warning';
                            $description = 'Pekerjaan sedang menunggu kuli';
                            
                            switch($status) {
                                case 'menunggu':
                                    $icon = 'fa-clock';
                                    $color = 'warning';
                                    $description = 'Pekerjaan sedang menunggu kuli';
                                    break;
                                case 'berlangsung':
                                    $icon = 'fa-play-circle';
                                    $color = 'info';
                                    $description = 'Pekerjaan sedang berlangsung';
                                    break;
                                case 'selesai':
                                    $icon = 'fa-check-circle';
                                    $color = 'success';
                                    $description = 'Pekerjaan telah selesai';
                                    break;
                                case 'batal':
                                    $icon = 'fa-times-circle';
                                    $color = 'danger';
                                    $description = 'Pekerjaan telah dibatalkan';
                                    break;
                            }
                            ?>
                            <i class="fas <?php echo $icon; ?> fa-3x text-<?php echo $color; ?> mb-3"></i>
                            <h5><?php echo $status_display; ?></h5>
                            <p class="text-muted"><?php echo $description; ?></p>
                        </div>
                    </div>
                </div>

                <!-- Apply Button for Kuli -->
                <?php if ($user_role == 'kuli'): ?>
                <div class="card detail-card mt-4">
                   
                    <div class="card-body">
                        <?php if ($has_applied): ?>
                            <div class="text-center">
                            
                        <?php else: ?>
                            <?php if ($pekerjaan['id_user'] != $user_id && $pekerjaan['status'] == 'menunggu'): ?>
                            <div class="text-center">
                                <i class="fas fa-briefcase fa-3x text-primary mb-3"></i>
                                <h5>Tertarik dengan pekerjaan ini?</h5>
                                <p class="text-muted">Kirim lamaran Anda sekarang</p>
                                
                                <button type="button" class="btn btn-primary btn-lg w-100" 
                                        data-bs-toggle="modal" data-bs-target="#applyModal">
                                    <i class="fas fa-paper-plane"></i> Lamar Sekarang
                                </button>
                            </div>
                            <?php elseif ($pekerjaan['id_user'] == $user_id): ?>
                            <div class="alert alert-info text-center">
                                <i class="fas fa-info-circle"></i>
                                <p class="mb-0">Ini adalah pekerjaan yang Anda buat sendiri</p>
                                <a href="edit_pekerjaan.php?id=<?php echo $pekerjaan_id; ?>" 
                                   class="btn btn-sm btn-outline-primary mt-2">
                                    <i class="fas fa-edit"></i> Edit Pekerjaan
                                </a>
                            </div>
                            <?php elseif ($pekerjaan['status'] != 'menunggu'): ?>
                            <div class="alert alert-warning text-center">
                                <i class="fas fa-exclamation-triangle"></i>
                                <p class="mb-0">Pekerjaan ini sudah <?php echo $pekerjaan['status']; ?></p>
                            </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Apply Modal (for Kuli) -->
    <?php if ($user_role == 'kuli' && !$has_applied && $pekerjaan['id_user'] != $user_id && $pekerjaan['status'] == 'menunggu'): ?>
    <div class="modal fade" id="applyModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Lamar Pekerjaan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="apply_pekerjaan.php" method="POST">
                    <div class="modal-body">
                        <h6><?php echo htmlspecialchars($pekerjaan['judul']); ?></h6>
                        <p class="text-muted small"><?php echo htmlspecialchars($pekerjaan['pemilik_nama']); ?> • <?php echo htmlspecialchars($pekerjaan['lokasi']); ?></p>
                        
                        <div class="mb-3">
                            <label for="pesan" class="form-label">Pesan Lamaran</label>
                            <textarea class="form-control" id="pesan" name="pesan" rows="4" 
                                      placeholder="Tulis pesan singkat mengapa Anda cocok untuk pekerjaan ini..."></textarea>
                            <div class="form-text">Maksimal 500 karakter</div>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> 
                            Lamaran Anda akan dikirim kepada pemilik pekerjaan untuk ditinjau.
                        </div>
                        
                        <input type="hidden" name="pekerjaan_id" value="<?php echo $pekerjaan_id; ?>">
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
    <?php endif; ?>

   
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form validation for apply modal
        document.addEventListener('DOMContentLoaded', function() {
            const applyForm = document.querySelector('form[action="apply_pekerjaan.php"]');
            if (applyForm) {
                applyForm.addEventListener('submit', function(e) {
                    const textarea = this.querySelector('textarea[name="pesan"]');
                    if (textarea && textarea.value.trim().length > 500) {
                        e.preventDefault();
                        alert('Pesan maksimal 500 karakter.');
                        textarea.focus();
                        return false;
                    }
                    if (!textarea.value.trim()) {
                        e.preventDefault();
                        alert('Harap tulis pesan lamaran.');
                        textarea.focus();
                        return false;
                    }
                    return true;
                });
            }
            
            // Auto hide alerts after 5 seconds
            setTimeout(function() {
                const alerts = document.querySelectorAll('.alert');
                alerts.forEach(alert => {
                    if (alert.classList.contains('alert-dismissible')) {
                        const bsAlert = new bootstrap.Alert(alert);
                        bsAlert.close();
                    }
                });
            }, 5000);
        });
    </script>
</body>
</html>