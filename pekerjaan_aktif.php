<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'user') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Query untuk mendapatkan pekerjaan aktif (status diproses) - TANPA tanggal_mulai
$sql = "SELECT p.*, k.nama_kategori, 
               (SELECT COUNT(*) FROM aplikasi_pekerjaan ap 
                WHERE ap.id_pekerjaan = p.id AND ap.status = 'diterima') as jumlah_kuli_diterima,
               (SELECT GROUP_CONCAT(u.nama_lengkap SEPARATOR ', ') 
                FROM aplikasi_pekerjaan ap 
                JOIN users u ON ap.id_kuli = u.id 
                WHERE ap.id_pekerjaan = p.id AND ap.status = 'diterima') as nama_kuli
        FROM pekerjaan p 
        JOIN kategori_pekerjaan k ON p.id_kategori = k.id
        WHERE p.id_user = ? AND p.status = 'diproses'
        ORDER BY p.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$pekerjaan_aktif = $stmt->fetchAll();

// Query untuk statistik
$sql_stats = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'diproses' THEN 1 ELSE 0 END) as diproses,
    SUM(CASE WHEN status = 'selesai' THEN 1 ELSE 0 END) as selesai,
    SUM(CASE WHEN status = 'menunggu' THEN 1 ELSE 0 END) as menunggu
    FROM pekerjaan 
    WHERE id_user = ?";

$stmt_stats = $pdo->prepare($sql_stats);
$stmt_stats->execute([$user_id]);
$stats = $stmt_stats->fetch();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pekerjaan Aktif - Kulimang</title>
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
        .kuli-badge {
            display: inline-block;
            background: #e9ecef;
            padding: 3px 8px;
            margin: 2px;
            border-radius: 15px;
            font-size: 0.8rem;
        }
        .progress-custom {
            height: 8px;
        }
        .bukti-thumb {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 5px;
            cursor: pointer;
            border: 1px solid #dee2e6;
        }
        .bukti-thumb:hover {
            transform: scale(1.5);
            z-index: 1000;
            position: relative;
            border: 2px solid #0d6efd;
        }
        .modal-image {
            max-width: 100%;
            max-height: 80vh;
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
                <span class="navbar-text me-3">
                    <i class="fas fa-user"></i> <?php echo $_SESSION['nama_lengkap']; ?>
                    <small class="badge bg-light text-dark ms-1">User</small>
                </span>
                <a href="dashboard_user.php" class="btn btn-outline-light btn-sm me-2">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <a href="logout.php" class="btn btn-outline-light btn-sm">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <!-- Modal Preview Image -->
    <div class="modal fade" id="imagePreviewModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Preview Bukti</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="previewImage" src="" alt="Preview" class="modal-image">
                </div>
                <div class="modal-footer">
                    <a id="downloadBtn" href="#" class="btn btn-success" download>
                        <i class="fas fa-download"></i> Download
                    </a>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

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

        <!-- Header -->
        <div class="row mb-4">
            <div class="col-md-8">
                <h3><i class="fas fa-tools"></i> Pekerjaan Aktif</h3>
                <p class="text-muted">Pekerjaan yang sedang dalam proses pengerjaan</p>
            </div>
            <div class="col-md-4 text-end">
                <a href="upload_pekerjaan.php" class="btn btn-primary me-2">
                    <i class="fas fa-plus"></i> Pekerjaan Baru
                </a>
                <a href="daftar_pekerjaan_user.php" class="btn btn-outline-primary">
                    <i class="fas fa-list"></i> Semua Pekerjaan
                </a>
            </div>
        </div>

        <!-- Statistik -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card card-hover border-primary">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title text-muted">Total Aktif</h6>
                                <h3 class="text-primary"><?php echo $stats['diproses'] ?? 0; ?></h3>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-tools fa-2x text-primary"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card card-hover border-success">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title text-muted">Selesai</h6>
                                <h3 class="text-success"><?php echo $stats['selesai'] ?? 0; ?></h3>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-check-circle fa-2x text-success"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card card-hover border-warning">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title text-muted">Menunggu</h6>
                                <h3 class="text-warning"><?php echo $stats['menunggu'] ?? 0; ?></h3>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-clock fa-2x text-warning"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card card-hover border-info">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title text-muted">Total Semua</h6>
                                <h3 class="text-info"><?php echo $stats['total'] ?? 0; ?></h3>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-list-alt fa-2x text-info"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Daftar Pekerjaan Aktif -->
        <div class="card">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-tasks"></i> Daftar Pekerjaan Aktif</h5>
                <span class="badge bg-light text-dark"><?php echo count($pekerjaan_aktif); ?> Pekerjaan</span>
            </div>
            <div class="card-body">
                <?php if ($pekerjaan_aktif): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Judul Pekerjaan</th>
                                    <th>Kategori</th>
                                    <th>Kuli</th>
                                    <th>Biaya</th>
                                    <th>Status</th>
                                    <th>Bukti</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pekerjaan_aktif as $index => $p): ?>
                                <?php
                                // Cek apakah ada bukti selesai
                                $has_bukti = !empty($p['bukti_selesai']);
                                $bukti_path = '';
                                $file_exists = false;
                                
                                if ($has_bukti) {
                                    $bukti_file = $p['bukti_selesai'];
                                    $bukti_web_path = 'uploads/bukti_selesai/' . $bukti_file;
                                    $bukti_full_path = __DIR__ . '/uploads/bukti_selesai/' . $bukti_file;
                                    
                                    if (file_exists($bukti_full_path)) {
                                        $bukti_path = $bukti_web_path;
                                        $file_exists = true;
                                        chmod($bukti_full_path, 0644);
                                    }
                                }
                                
                                // Format kuli yang diterima
                                $kuli_list = $p['nama_kuli'] ?? '';
                                $kuli_count = $p['jumlah_kuli_diterima'] ?? 0;
                                ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($p['judul']); ?></strong><br>
                                        <small class="text-muted">
                                            <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($p['lokasi']); ?>
                                        </small>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary"><?php echo $p['nama_kategori']; ?></span>
                                    </td>
                                    <td>
                                        <?php if ($kuli_count > 0 && !empty($kuli_list)): ?>
                                            <div class="d-flex flex-wrap">
                                                <?php 
                                                $kuli_names = explode(', ', $kuli_list);
                                                foreach ($kuli_names as $kuli_name): 
                                                    if (!empty(trim($kuli_name))):
                                                ?>
                                                <span class="kuli-badge">
                                                    <i class="fas fa-user-hard-hat"></i> <?php echo htmlspecialchars($kuli_name); ?>
                                                </span>
                                                <?php 
                                                    endif;
                                                endforeach; 
                                                ?>
                                            </div>
                                            <small class="text-muted">Total: <?php echo $kuli_count; ?> kuli</small>
                                        <?php else: ?>
                                            <span class="text-muted">Belum ada kuli</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong class="text-success">Rp <?php echo number_format($p['total_biaya'], 0, ',', '.'); ?></strong>
                                    </td>
                                    <td>
                                        <span class="badge bg-info">Diproses</span>
                                        <?php if (!empty($p['created_at'])): ?>
                                        <br>
                                        <small class="text-muted">
                                            <?php echo date('d/m/Y', strtotime($p['created_at'])); ?>
                                        </small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($has_bukti && $file_exists): ?>
                                            <img src="<?php echo $bukti_path; ?>" 
                                                 alt="Bukti" 
                                                 class="bukti-thumb"
                                                 onclick="previewImage('<?php echo $bukti_path; ?>', '<?php echo htmlspecialchars($p['judul']); ?>')"
                                                 data-bs-toggle="tooltip"
                                                 title="Klik untuk melihat">
                                        <?php elseif ($has_bukti && !$file_exists): ?>
                                            <span class="badge bg-warning" title="File tidak ditemukan">
                                                <i class="fas fa-exclamation-triangle"></i>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">
                                                <i class="fas fa-clock"></i> Belum ada
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="detail_pekerjaan_user.php?id=<?php echo $p['id']; ?>" 
                                               class="btn btn-outline-primary" title="Detail">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="edit_pekerjaan.php?id=<?php echo $p['id']; ?>" 
                                               class="btn btn-outline-warning" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <?php if ($has_bukti && $file_exists): ?>
                                            <a href="<?php echo $bukti_path; ?>" 
                                               class="btn btn-outline-success" 
                                               title="Download Bukti"
                                               download="<?php echo $p['bukti_selesai']; ?>">
                                                <i class="fas fa-download"></i>
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
                    <div class="text-center py-5">
                        <i class="fas fa-tools fa-4x text-muted mb-3"></i>
                        <h5 class="text-muted">Tidak ada pekerjaan aktif</h5>
                        <p class="text-muted mb-4">Semua pekerjaan Anda sudah selesai atau belum dimulai.</p>
                        <a href="upload_pekerjaan.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Upload Pekerjaan Baru
                        </a>
                        <a href="daftar_pekerjaan_user.php" class="btn btn-outline-primary ms-2">
                            <i class="fas fa-list"></i> Lihat Semua Pekerjaan
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Informasi -->
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="alert alert-info">
                    <h6><i class="fas fa-info-circle"></i> Informasi Pekerjaan Aktif:</h6>
                    <ul class="mb-0">
                        <li>Pekerjaan dengan status <span class="badge bg-info">Diproses</span> sedang dikerjakan oleh kuli</li>
                        <li>Kuli dapat mengupload bukti foto setelah menyelesaikan pekerjaan</li>
                        <li>Setelah bukti diupload, status akan berubah menjadi <span class="badge bg-success">Selesai</span></li>
                        <li>Klik thumbnail bukti untuk melihat gambar full size</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto hide alerts
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
        
        // Initialize tooltips
        const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
        
        // Function to preview image
        function previewImage(imageSrc, title) {
            const modal = new bootstrap.Modal(document.getElementById('imagePreviewModal'));
            const previewImage = document.getElementById('previewImage');
            const downloadBtn = document.getElementById('downloadBtn');
            
            previewImage.src = imageSrc;
            downloadBtn.href = imageSrc;
            downloadBtn.download = title + '.jpg';
            
            modal.show();
        }
        
        // Clean up modal
        document.getElementById('imagePreviewModal').addEventListener('hidden.bs.modal', function () {
            document.getElementById('previewImage').src = '';
        });
    </script>
</body>
</html>