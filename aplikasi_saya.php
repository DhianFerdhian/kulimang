<?php
session_start();
require_once 'config.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'kuli') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Notifikasi
$stmt = $pdo->prepare("SELECT COUNT(*) as notif_count FROM aplikasi_pekerjaan 
                    WHERE id_kuli = ? AND status = 'diterima'");
$stmt->execute([$user_id]);
$notif_count = $stmt->fetch()['notif_count'];

// Get filter parameter
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'semua';

// Query untuk aplikasi pekerjaan dengan filter - DIPERBAIKI
$sql = "SELECT a.*, p.judul, p.deskripsi, p.lokasi, p.total_biaya, p.status as status_pekerjaan,
               p.bukti_selesai, p.tanggal_selesai, p.updated_at as pekerjaan_updated,
               k.nama_kategori, u.nama_lengkap as pemilik, u.id as id_user_pemilik
        FROM aplikasi_pekerjaan a 
        JOIN pekerjaan p ON a.id_pekerjaan = p.id 
        JOIN kategori_pekerjaan k ON p.id_kategori = k.id
        JOIN users u ON p.id_user = u.id
        WHERE a.id_kuli = ?";

// Tambahkan filter berdasarkan status
if ($filter == 'diterima') {
    $sql .= " AND a.status = 'diterima'";
} elseif ($filter == 'menunggu') {
    $sql .= " AND a.status = 'menunggu'";
} elseif ($filter == 'ditolak') {
    $sql .= " AND a.status = 'ditolak'";
} elseif ($filter == 'selesai') {
    $sql .= " AND a.status = 'selesai'";
}

$sql .= " ORDER BY a.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$aplikasi = $stmt->fetchAll();

// Hitung statistik
$stats = [
    'semua' => 0,
    'diterima' => 0,
    'menunggu' => 0,
    'ditolak' => 0,
    'selesai' => 0
];

$stmt = $pdo->prepare("SELECT status, COUNT(*) as total FROM aplikasi_pekerjaan WHERE id_kuli = ? GROUP BY status");
$stmt->execute([$user_id]);
$statistik = $stmt->fetchAll();

foreach ($statistik as $stat) {
    $status = $stat['status'] ?: 'menunggu';
    $stats[$status] = $stat['total'];
    $stats['semua'] += $stat['total'];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aplikasi Pekerjaan Saya - Kulimang</title>
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
        .status-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
        }
        .filter-active {
            background-color: #0d6efd !important;
            color: white !important;
        }
        .table-responsive {
            max-height: 500px;
            overflow-y: auto;
        }
        .job-status {
            font-size: 0.85rem;
            padding: 0.2rem 0.4rem;
        }
        .table th {
            background-color: #f8f9fa;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        .progress-indicator {
            font-size: 0.8rem;
            color: #6c757d;
        }
        .no-data {
            min-height: 300px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
        }
        .filter-badge {
            font-size: 0.7rem;
            padding: 0.2rem 0.4rem;
        }
        .upload-success {
            background-color: #d4edda;
            border-left: 4px solid #28a745;
        }
        .btn-tandai-selesai {
            min-width: 120px;
            font-weight: bold;
        }
        .aksi-buttons {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .aksi-row {
            display: flex;
            gap: 5px;
            justify-content: center;
        }
        .bukti-info {
            font-size: 0.8rem;
            color: #6c757d;
            font-style: italic;
        }
        .selesai-tanpa-bukti {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            margin-top: 3px;
        }
        .btn-action-sm {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand fw-bold" href="dashboard_kuli.php">
                <i class="fas fa-hammer"></i> KULIMANG
            </a>
            <div class="navbar-nav ms-auto align-items-center">
                <?php if ($notif_count > 0): ?>
                <div class="nav-item position-relative me-3">
                    <a href="notifications.php" class="nav-link text-white">
                        <i class="fas fa-bell"></i>
                        <span class="notification-badge"><?php echo $notif_count; ?></span>
                    </a>
                </div>
                <?php endif; ?>
                <div class="nav-item dropdown me-3">
                    <a class="nav-link dropdown-toggle text-white" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle"></i> <?php echo $_SESSION['nama_lengkap']; ?>
                        <small class="badge bg-light text-dark ms-1">Kuli</small>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="dashboard_kuli.php"><i class="fas fa-home"></i> Dashboard</a></li>
                        <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user"></i> Profil Saya</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="semua_pekerjaan.php"><i class="fas fa-search"></i> Cari Pekerjaan</a></li>
                    </ul>
                </div>
                <div class="nav-item">
                    <a href="logout.php" class="btn btn-outline-light btn-sm">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- MODAL: Selesaikan Tanpa Bukti -->
    <div class="modal fade" id="selesaikanTanpaBuktiModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title text-white">
                        <i class="fas fa-check-circle me-2"></i>Tandai Selesai
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="formSelesaikanTanpaBukti" method="POST" action="selesaikan_tanpa_bukti.php">
                    <div class="modal-body">
                        <input type="hidden" id="pekerjaan_id_tanpa_bukti" name="pekerjaan_id">
                        <input type="hidden" id="aplikasi_id_tanpa_bukti" name="aplikasi_id">
                        
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Perhatian!</strong> Anda akan menandai pekerjaan ini sebagai selesai <strong>TANPA</strong> mengupload bukti foto.
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="konfirmasi_pekerjaan" name="konfirmasi" required>
                                <label class="form-check-label" for="konfirmasi_pekerjaan">
                                    Saya konfirmasi bahwa pekerjaan telah benar-benar selesai dikerjakan
                                </label>
                            </div>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            Status pekerjaan akan berubah menjadi <strong>"Selesai"</strong> setelah konfirmasi.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-check-circle"></i> Ya, Tandai Selesai
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="container mt-4">
        <!-- Notifikasi -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle"></i> <?php 
                    echo $_SESSION['success']; 
                    unset($_SESSION['success']); 
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle"></i> <?php 
                    echo $_SESSION['error']; 
                    unset($_SESSION['error']); 
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Header -->
        <div class="row mb-4">
            <div class="col-md-8">
                <h3><i class="fas fa-tasks"></i> Aplikasi Pekerjaan Saya</h3>
                <p class="text-muted">Lihat dan kelola semua pekerjaan yang telah Anda lamar</p>
            </div>
            <div class="col-md-4 text-end">
                <a href="semua_pekerjaan.php" class="btn btn-primary">
                    <i class="fas fa-search"></i> Cari Pekerjaan Baru
                </a>
                <a href="dashboard_kuli.php" class="btn btn-outline-primary ms-2">
                    <i class="fas fa-home"></i> Dashboard
                </a>
            </div>
        </div>

        <!-- Statistik -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-title mb-3">Statistik Aplikasi</h6>
                        <div class="row text-center">
                            <div class="col-md-2 col-6 mb-2">
                                <div class="p-3 border rounded">
                                    <h5 class="text-primary"><?php echo $stats['semua']; ?></h5>
                                    <small class="text-muted">Semua</small>
                                </div>
                            </div>
                            <div class="col-md-2 col-6 mb-2">
                                <div class="p-3 border rounded">
                                    <h5 class="text-warning"><?php echo $stats['menunggu']; ?></h5>
                                    <small class="text-muted">Menunggu</small>
                                </div>
                            </div>
                            <div class="col-md-2 col-6 mb-2">
                                <div class="p-3 border rounded">
                                    <h5 class="text-success"><?php echo $stats['diterima']; ?></h5>
                                    <small class="text-muted">Diterima</small>
                                </div>
                            </div>
                            <div class="col-md-2 col-6 mb-2">
                                <div class="p-3 border rounded">
                                    <h5 class="text-danger"><?php echo $stats['ditolak']; ?></h5>
                                    <small class="text-muted">Ditolak</small>
                                </div>
                            </div>
                            <div class="col-md-2 col-6 mb-2">
                                <div class="p-3 border rounded">
                                    <h5 class="text-info"><?php echo $stats['selesai']; ?></h5>
                                    <small class="text-muted">Selesai</small>
                                </div>
                            </div>
                            <div class="col-md-2 col-6 mb-2">
                                <div class="p-3 border rounded">
                                    <h5 class="text-secondary"><?php echo $stats['diterima'] + $stats['selesai']; ?></h5>
                                    <small class="text-muted">Total Kerja</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter -->
        <div class="row mb-3">
            <div class="col-12">
                <div class="d-flex flex-wrap gap-2">
                    <a href="?filter=semua" class="btn btn-outline-primary <?php echo $filter == 'semua' ? 'filter-active' : ''; ?>">
                        Semua <span class="badge bg-primary filter-badge"><?php echo $stats['semua']; ?></span>
                    </a>
                    <a href="?filter=menunggu" class="btn btn-outline-warning <?php echo $filter == 'menunggu' ? 'filter-active' : ''; ?>">
                        Menunggu <span class="badge bg-warning filter-badge"><?php echo $stats['menunggu']; ?></span>
                    </a>
                    <a href="?filter=diterima" class="btn btn-outline-success <?php echo $filter == 'diterima' ? 'filter-active' : ''; ?>">
                        Diterima <span class="badge bg-success filter-badge"><?php echo $stats['diterima']; ?></span>
                    </a>
                    <a href="?filter=ditolak" class="btn btn-outline-danger <?php echo $filter == 'ditolak' ? 'filter-active' : ''; ?>">
                        Ditolak <span class="badge bg-danger filter-badge"><?php echo $stats['ditolak']; ?></span>
                    </a>
                    <a href="?filter=selesai" class="btn btn-outline-info <?php echo $filter == 'selesai' ? 'filter-active' : ''; ?>">
                        Selesai <span class="badge bg-info filter-badge"><?php echo $stats['selesai']; ?></span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Daftar Aplikasi -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body p-0">
                        <?php if ($aplikasi): ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th width="50">No</th>
                                            <th>Pekerjaan</th>
                                            <th width="120">Kategori</th>
                                            <th width="120">Pemilik</th>
                                            <th width="150">Lokasi</th>
                                            <th width="120">Biaya</th>
                                            <th width="150">Tanggal Lamar</th>
                                            <th width="120">Status</th>
                                            <th width="200">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($aplikasi as $index => $a): ?>
                                            <?php
                                            // Format status aplikasi
                                            $status_aplikasi = $a['status'] ?? 'menunggu';
                                            $badge_class = 'secondary';
                                            $badge_text = 'Menunggu';
                                            
                                            if ($status_aplikasi == 'menunggu') {
                                                $badge_class = 'warning';
                                                $badge_text = 'Menunggu';
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
                                            
                                            // Format status pekerjaan (dari tabel pekerjaan)
                                            $status_pekerjaan = $a['status_pekerjaan'] ?? 'menunggu';
                                            $pekerjaan_badge_class = 'secondary';
                                            $pekerjaan_badge_text = 'Menunggu';
                                            
                                            if ($status_pekerjaan == 'diproses') {
                                                $pekerjaan_badge_class = 'primary';
                                                $pekerjaan_badge_text = 'Diproses';
                                            } elseif ($status_pekerjaan == 'selesai') {
                                                $pekerjaan_badge_class = 'success';
                                                $pekerjaan_badge_text = 'Selesai';
                                            } elseif ($status_pekerjaan == 'menunggu') {
                                                $pekerjaan_badge_class = 'warning';
                                                $pekerjaan_badge_text = 'Menunggu';
                                            }
                                            
                                            // Format tanggal lamar
                                            $tanggal_lamar = date('d/m/Y H:i', strtotime($a['created_at']));
                                            
                                            // Cek apakah tombol "Selesai" seharusnya muncul
                                            $show_selesai_button = false;
                                            
                                            // LOGIC FIXED: Tombol "Selesai" muncul jika:
                                            // 1. Status aplikasi = 'diterima' DAN
                                            // 2. Status pekerjaan = 'diproses'
                                            if ($status_aplikasi == 'diterima' && $status_pekerjaan == 'diproses') {
                                                $show_selesai_button = true;
                                            }
                                            
                                            // Cek apakah sudah ada bukti selesai
                                            $has_bukti_selesai = !empty($a['bukti_selesai']);
                                            $selesai_tanpa_bukti = ($status_aplikasi == 'selesai' && !$has_bukti_selesai);
                                            
                                            // Tanggal selesai
                                            $tanggal_selesai_display = '-';
                                            if ($status_aplikasi == 'selesai') {
                                                if (!empty($a['tanggal_selesai'])) {
                                                    $tanggal_selesai_display = date('d/m/Y H:i', strtotime($a['tanggal_selesai']));
                                                } elseif (!empty($a['pekerjaan_updated'])) {
                                                    $tanggal_selesai_display = date('d/m/Y H:i', strtotime($a['pekerjaan_updated']));
                                                }
                                            }
                                            ?>
                                            <tr id="row-<?php echo $a['id_pekerjaan']; ?>">
                                                <td class="text-center"><?php echo $index + 1; ?></td>
                                                <td>
                                                    <strong class="d-block mb-1"><?php echo htmlspecialchars($a['judul']); ?></strong>
                                                    <small class="text-muted d-block" style="font-size: 0.8rem;">
                                                        <?php echo htmlspecialchars(substr($a['deskripsi'], 0, 60)) . '...'; ?>
                                                    </small>
                                                    <?php if ($status_aplikasi == 'selesai'): ?>
                                                        <small class="text-success d-block" style="font-size: 0.7rem;">
                                                            <i class="fas fa-calendar-check"></i> Selesai: <?php echo $tanggal_selesai_display; ?>
                                                        </small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge bg-light text-dark job-status">
                                                        <?php echo $a['nama_kategori']; ?>
                                                    </span>
                                                </td>
                                                <td><?php echo $a['pemilik']; ?></td>
                                                <td>
                                                    <small class="text-muted">
                                                        <?php echo htmlspecialchars(substr($a['lokasi'], 0, 20)); ?>
                                                        <?php if (strlen($a['lokasi']) > 20): ?>...<?php endif; ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <strong class="text-success">
                                                        Rp <?php echo number_format($a['total_biaya'], 0, ',', '.'); ?>
                                                    </strong>
                                                </td>
                                                <td>
                                                    <div class="progress-indicator">
                                                        <small><?php echo $tanggal_lamar; ?></small>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="d-flex flex-column gap-1">
                                                        <span class="badge bg-<?php echo $badge_class; ?> status-badge">
                                                            <?php echo $badge_text; ?>
                                                        </span>
                                                        <span class="badge bg-<?php echo $pekerjaan_badge_class; ?> status-badge">
                                                            <?php echo $pekerjaan_badge_text; ?>
                                                        </span>
                                                        <?php if ($selesai_tanpa_bukti): ?>
                                                        <div class="selesai-tanpa-bukti">
                                                            <small><i class="fas fa-info-circle"></i> Tanpa bukti</small>
                                                        </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="aksi-buttons">
                                                        <div class="aksi-row">
                                                            <!-- Tombol Lihat Detail Pekerjaan -->
                                                            <a href="detail_pekerjaan.php?id=<?php echo $a['id_pekerjaan']; ?>" 
                                                               class="btn btn-sm btn-outline-primary btn-action-sm" 
                                                               title="Lihat Detail Pekerjaan">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                            <?php if ($status_aplikasi == 'diterima'): ?>
                                                                <!-- PERBAIKAN: Menggunakan id_user_pemilik, bukan id_user -->
                                                                <a href="chat.php?pekerjaan=<?php echo $a['id_pekerjaan']; ?>&owner=<?php echo $a['id_user_pemilik']; ?>" 
                                                                   class="btn btn-sm btn-outline-success btn-action-sm" 
                                                                   title="Chat Pemilik">
                                                                    <i class="fas fa-comment"></i>
                                                                </a>
                                                            <?php endif; ?>
                                                        </div>
                                                        
                                                        <?php if ($show_selesai_button): ?>
                                                            <!-- Tombol Selesai Tanpa Bukti -->
                                                            <button type="button" 
                                                                    class="btn btn-sm btn-success btn-tandai-selesai" 
                                                                    title="Tandai pekerjaan sebagai selesai"
                                                                    onclick="showSelesaikanTanpaBuktiModal(<?php echo $a['id_pekerjaan']; ?>, <?php echo $a['id']; ?>)">
                                                                <i class="fas fa-check-circle"></i> Selesai
                                                            </button>
                                                            <div class="bukti-info">
                                                                <small>*Tanpa upload bukti</small>
                                                            </div>
                                                        <?php elseif ($status_aplikasi == 'selesai'): ?>
                                                            <div class="text-center">
                                                                <small class="text-success fw-bold">
                                                                    <i class="fas fa-check-circle"></i> Pekerjaan Selesai
                                                                </small>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="no-data p-5">
                                <i class="fas fa-inbox fa-4x text-muted mb-4"></i>
                                <h5 class="text-muted mb-2">Tidak ada aplikasi pekerjaan</h5>
                                <p class="text-muted mb-4">Anda belum melamar pekerjaan apapun.</p>
                                <a href="semua_pekerjaan.php" class="btn btn-primary btn-lg">
                                    <i class="fas fa-search me-2"></i> Cari Pekerjaan
                                </a>
                            </div>
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
        
        // Show modal selesai tanpa bukti
        function showSelesaikanTanpaBuktiModal(pekerjaanId, aplikasiId) {
            document.getElementById('pekerjaan_id_tanpa_bukti').value = pekerjaanId;
            document.getElementById('aplikasi_id_tanpa_bukti').value = aplikasiId;
            
            // Reset form
            document.getElementById('formSelesaikanTanpaBukti').reset();
            
            const modal = new bootstrap.Modal(document.getElementById('selesaikanTanpaBuktiModal'));
            modal.show();
        }
        
        // Handle form submission untuk selesai tanpa bukti
        document.getElementById('formSelesaikanTanpaBukti').addEventListener('submit', function(e) {
            const checkbox = document.getElementById('konfirmasi_pekerjaan');
            
            if (!checkbox.checked) {
                e.preventDefault();
                alert('Anda harus mencentang kotak konfirmasi untuk melanjutkan.');
                checkbox.focus();
                return;
            }
            
            // Show loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';
            submitBtn.disabled = true;
        });
        
        // Auto scroll to updated row if exists
        <?php if (isset($_SESSION['last_updated'])): ?>
        document.addEventListener('DOMContentLoaded', function() {
            const rowId = 'row-<?php echo $_SESSION['last_updated']; ?>';
            const rowElement = document.getElementById(rowId);
            
            if (rowElement) {
                // Scroll ke elemen
                setTimeout(() => {
                    rowElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }, 500);
                
                // Add highlight effect
                rowElement.style.backgroundColor = '#d4edda';
                setTimeout(() => {
                    rowElement.style.transition = 'background-color 1s';
                    rowElement.style.backgroundColor = '';
                }, 2000);
                
                // Remove session
                <?php unset($_SESSION['last_updated']); ?>
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>