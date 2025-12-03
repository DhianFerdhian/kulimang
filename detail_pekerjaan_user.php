<?php
session_start();
require_once 'config.php';

// Cek apakah user sudah login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'user') {
    header("Location: login.php");
    exit();
}

// Ambil ID dari parameter URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: dashboard_user.php");
    exit();
}

$id_pekerjaan = $_GET['id'];
$user_id = $_SESSION['user_id'];

// Validasi ID harus angka
if (!is_numeric($id_pekerjaan)) {
    header("Location: dashboard_user.php");
    exit();
}

// Ambil data pekerjaan berdasarkan ID
try {
    $stmt = $pdo->prepare("
        SELECT p.*, k.nama_kategori, u.nama_lengkap, u.email, u.telepon
        FROM pekerjaan p 
        JOIN kategori_pekerjaan k ON p.id_kategori = k.id 
        JOIN users u ON p.id_user = u.id
        WHERE p.id = ? AND p.id_user = ?
    ");
    $stmt->execute([$id_pekerjaan, $user_id]);
    $pekerjaan = $stmt->fetch();
    
    if (!$pekerjaan) {
        $_SESSION['error'] = "Data pekerjaan tidak ditemukan";
        header("Location: dashboard_user.php");
        exit();
    }
    
} catch (PDOException $e) {
    $_SESSION['error'] = "Terjadi kesalahan: " . $e->getMessage();
    header("Location: dashboard_user.php");
    exit();
}

// Ambil data kuli yang mengerjakan jika ada
$kuli = null;
if ($pekerjaan['status'] == 'diproses' || $pekerjaan['status'] == 'selesai') {
    try {
        $stmt = $pdo->prepare("
            SELECT u.nama_lengkap, u.email, u.telepon, ap.created_at as tanggal_diterima
            FROM aplikasi_pekerjaan ap
            JOIN users u ON ap.id_kuli = u.id
            WHERE ap.id_pekerjaan = ? AND ap.status = 'diterima'
            LIMIT 1
        ");
        $stmt->execute([$id_pekerjaan]);
        $kuli = $stmt->fetch();
    } catch (PDOException $e) {
        // Biarkan kosong jika error
    }
}

// Ambil catatan dan keterangan
$catatan = isset($pekerjaan['catatan']) ? $pekerjaan['catatan'] : '';
$keterangan = isset($pekerjaan['keterangan']) ? $pekerjaan['keterangan'] : '';

// Tentukan apakah selesai
$selesai_tanpa_bukti = ($pekerjaan['status'] == 'selesai' && empty($pekerjaan['bukti_selesai']));
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
        .detail-section {
            background: white;
            border-radius: 10px;
            border: none;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .card-header {
            background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
            color: white;
        }
        
        .status-badge {
            font-size: 0.9em;
            padding: 8px 15px;
        }
        
        .status-completed {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
        }
        
        .status-completed-no-bukti {
            background: linear-gradient(135deg, #20c997 0%, #17a2b8 100%);
            color: white;
        }
        
        .info-label {
            font-weight: 600;
            color: #495057;
        }
        
        .info-value {
            color: #212529;
        }
        
        .timeline-item {
            border-left: 3px solid #0d6efd;
            padding-left: 15px;
            margin-bottom: 10px;
        }
        
        .bukti-section {
            border: 2px solid #28a745;
            border-radius: 10px;
            padding: 20px;
            background-color: #f8fff9;
            margin-bottom: 20px;
        }
        
        .selesai-tanpa-bukti-section {
            border: 2px solid #20c997;
            border-radius: 10px;
            padding: 20px;
            background-color: #f0fdf4;
            margin-bottom: 20px;
        }
        
        .bukti-preview {
            max-width: 300px;
            max-height: 200px;
            border: 2px solid #ddd;
            border-radius: 8px;
            padding: 5px;
            background: white;
        }
        
        .completed-banner {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.2);
        }
        
        .completed-banner-no-bukti {
            background: linear-gradient(135deg, #20c997 0%, #17a2b8 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            box-shadow: 0 4px 15px rgba(32, 201, 151, 0.2);
        }
        
        .file-preview-container {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .file-info {
            flex: 1;
        }
        
        .modal-image-preview {
            max-width: 100%;
            max-height: 70vh;
            border-radius: 8px;
            border: 1px solid #dee2e6;
        }
        
        .pdf-icon-lg {
            font-size: 5rem;
            color: #dc3545;
        }
        
        .file-icon-lg {
            font-size: 5rem;
            color: #6c757d;
        }
        
        .no-bukti-icon {
            font-size: 4rem;
            color: #20c997;
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
                    <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
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

        <!-- Banner Pekerjaan Selesai -->
        <?php if ($pekerjaan['status'] == 'selesai'): ?>
        <div class="<?php echo $selesai_tanpa_bukti ? 'completed-banner-no-bukti' : 'completed-banner'; ?>">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h4>
                        <i class="fas fa-check-circle"></i> 
                        <?php if ($selesai_tanpa_bukti): ?>
                            PEKERJAAN INI TELAH DISELESAIKAN OLEH KULI
                        <?php else: ?>
                            PEKERJAAN INI TELAH SELESAI
                        <?php endif; ?>
                    </h4>
                    <?php if (!empty($pekerjaan['updated_at'])): ?>
                    <p class="mb-0">
                        <i class="fas fa-calendar-check"></i> 
                        <?php if ($selesai_tanpa_bukti): ?>
                            Ditandai selesai: 
                        <?php else: ?>
                            Diselesaikan: 
                        <?php endif; ?>
                        <?php echo date('d M Y H:i', strtotime($pekerjaan['updated_at'])); ?>
                    </p>
                    <?php endif; ?>
                    <?php if ($selesai_tanpa_bukti && $kuli): ?>
                    <p class="mb-0 mt-2">
                        <i class="fas fa-user-hard-hat"></i> 
                        Diselesaikan oleh: <strong><?php echo htmlspecialchars($kuli['nama_lengkap']); ?></strong>
                    </p>
                    <?php endif; ?>
                </div>
                <div class="col-md-4 text-end">
                    <?php if ($selesai_tanpa_bukti): ?>
                        <i class="fas fa-clipboard-check fa-3x"></i>
                    <?php else: ?>
                        <i class="fas fa-check-circle fa-3x"></i>
                        <p class="mt-2 mb-0">Dengan bukti selesai</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Section Bukti Pekerjaan Selesai -->
        <?php if ($pekerjaan['status'] == 'selesai' && !empty($pekerjaan['bukti_selesai'])): ?>
        <div class="bukti-section">
            <h5 class="border-bottom pb-2 mb-3">
                <i class="fas fa-file-check"></i> Bukti Pekerjaan Selesai
            </h5>
            
            <div class="file-preview-container">
                <div>
                    <?php
                    $file_path = 'uploads/bukti_selesai/' . $pekerjaan['bukti_selesai'];
                    $file_ext = strtolower(pathinfo($pekerjaan['bukti_selesai'], PATHINFO_EXTENSION));
                    $is_image = in_array($file_ext, ['jpg', 'jpeg', 'png', 'gif']);
                    $is_pdf = ($file_ext == 'pdf');
                    ?>
                    
                    <?php if ($is_image && file_exists($file_path)): ?>
                        <img src="<?php echo $file_path; ?>" class="bukti-preview img-thumbnail" alt="Bukti Pekerjaan Selesai">
                    <?php elseif ($is_pdf && file_exists($file_path)): ?>
                        <div class="text-center p-3 border rounded bg-light">
                            <i class="fas fa-file-pdf text-danger" style="font-size: 48px;"></i>
                            <p class="mt-2 mb-0">File PDF</p>
                        </div>
                    <?php else: ?>
                        <div class="text-center p-3 border rounded bg-light">
                            <i class="fas fa-file" style="font-size: 48px;"></i>
                            <p class="mt-2 mb-0">File Bukti</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="file-info">
                    <p class="mb-1">
                        <strong>File:</strong> <?php echo htmlspecialchars($pekerjaan['bukti_selesai']); ?>
                    </p>
                    <p class="mb-1">
                        <strong>Tipe:</strong> <?php echo strtoupper($file_ext); ?>
                    </p>
                    <p class="mb-3">
                        <strong>Diupload:</strong> 
                        <?php echo date('d M Y H:i', strtotime($pekerjaan['updated_at'])); ?>
                    </p>
                    
                    <div class="btn-group">
                        <button type="button" class="btn btn-sm btn-outline-primary" 
                                data-bs-toggle="modal" data-bs-target="#modalViewBukti"
                                data-file-path="<?php echo $file_path; ?>"
                                data-file-type="<?php echo $is_image ? 'image' : ($is_pdf ? 'pdf' : 'other'); ?>"
                                data-judul="<?php echo htmlspecialchars($pekerjaan['judul']); ?>">
                            <i class="fas fa-eye"></i> Lihat
                        </button>
                        <a href="<?php echo $file_path; ?>" class="btn btn-sm btn-outline-success" download>
                            <i class="fas fa-download"></i> Download
                        </a>
                    </div>
                </div>
            </div>
            
            <?php if (!empty($catatan)): ?>
            <div class="mt-3">
                <h6><i class="fas fa-sticky-note"></i> Catatan Penyelesaian:</h6>
                <div class="alert alert-light border">
                    <?php echo nl2br(htmlspecialchars($catatan)); ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Section Selesai Tanpa Bukti -->
        <?php if ($selesai_tanpa_bukti): ?>
        <div class="selesai-tanpa-bukti-section">
            <h5 class="border-bottom pb-2 mb-3">
                <i class="fas fa-clipboard-check"></i> Informasi Penyelesaian
            </h5>
            
            <div class="text-center py-3">
                <i class="fas fa-clipboard-check no-bukti-icon mb-3"></i>
                <h5>Pekerjaan Ditandai Selesai oleh Kuli</h5>
                <p class="text-muted">
                    Kuli telah menandai pekerjaan ini sebagai selesai tanpa mengupload bukti foto.
                </p>
                
                <?php if ($kuli): ?>
                <div class="alert alert-info mt-3">
                    <div class="row">
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Nama Kuli:</strong></p>
                            <p><?php echo htmlspecialchars($kuli['nama_lengkap']); ?></p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Diterima Sejak:</strong></p>
                            <p><?php echo date('d M Y', strtotime($kuli['tanggal_diterima'])); ?></p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="alert alert-light mt-3">
                    <p class="mb-0">
                        <i class="fas fa-info-circle text-info"></i>
                        <strong>Informasi:</strong> Kuli telah mengkonfirmasi bahwa pekerjaan telah selesai dikerjakan.
                        <?php if ($pekerjaan['updated_at']): ?>
                        Status diperbarui pada: <?php echo date('d M Y H:i', strtotime($pekerjaan['updated_at'])); ?>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Detail Pekerjaan -->
        <div class="row">
            <div class="col-12">
                <div class="card detail-section">
                    <div class="card-header">
                        <h4 class="mb-0">
                            <i class="fas fa-file-alt"></i> Detail Pekerjaan
                            <span class="float-end">
                                <?php
                                $status = $pekerjaan['status'] ?? 'menunggu';
                                $status_text = str_replace('_', ' ', $status);
                                
                                // Tentukan badge class
                                if ($status == 'selesai' && $selesai_tanpa_bukti) {
                                    $status_badge_class = 'status-completed-no-bukti';
                                } else {
                                    $status_badge_class = [
                                        'menunggu' => 'bg-warning',
                                        'diproses' => 'bg-info',
                                        'selesai' => 'status-completed',
                                        'ditolak' => 'bg-danger'
                                    ][$status] ?? 'bg-secondary';
                                }
                                ?>
                                <span class="badge status-badge <?php echo $status_badge_class; ?>">
                                    <i class="fas fa-circle me-1"></i>
                                    <?php echo ucfirst($status_text); ?>
                                    <?php if ($selesai_tanpa_bukti): ?>
                                    <?php endif; ?>
                                </span>
                            </span>
                        </h4>
                    </div>
                    
                    <div class="card-body">
                        <div class="row">
                            <!-- Informasi Pekerjaan -->
                            <div class="col-md-6">
                                <h5 class="border-bottom pb-2 mb-3">
                                    <i class="fas fa-info-circle"></i> Informasi Pekerjaan
                                </h5>
                                
                                <div class="mb-3">
                                    <span class="info-label">Judul Pekerjaan:</span>
                                    <div class="info-value fw-bold fs-5"><?php echo htmlspecialchars($pekerjaan['judul']); ?></div>
                                </div>
                                
                                <div class="mb-3">
                                    <span class="info-label">Deskripsi:</span>
                                    <div class="info-value p-3 bg-light rounded">
                                        <?php echo nl2br(htmlspecialchars($pekerjaan['deskripsi'] ?? 'Tidak ada deskripsi')); ?>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-6 mb-3">
                                        <span class="info-label">Kategori:</span>
                                        <div class="info-value">
                                            <span class="badge bg-secondary">
                                                <?php echo htmlspecialchars($pekerjaan['nama_kategori'] ?? 'Tidak ada kategori'); ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="col-6 mb-3">
                                        <span class="info-label">Lokasi:</span>
                                        <div class="info-value">
                                            <i class="fas fa-map-marker-alt"></i> 
                                            <?php echo htmlspecialchars($pekerjaan['lokasi'] ?? 'Tidak ditentukan'); ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-6 mb-3">
                                        <span class="info-label">Luas:</span>
                                        <div class="info-value">
                                            <?php echo $pekerjaan['luas']; ?> m²
                                        </div>
                                    </div>
                                    <div class="col-6 mb-3">
                                        <span class="info-label">Budget:</span>
                                        <div class="info-value fw-bold text-success">
                                            <i class="fas fa-money-bill-wave"></i> 
                                            Rp <?php echo number_format($pekerjaan['total_biaya'] ?? 0, 0, ',', '.'); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Informasi Kuli & Timeline -->
                            <div class="col-md-6">
                                <!-- Informasi Kuli -->
                                <?php if ($kuli): ?>
                                <h5 class="border-bottom pb-2 mb-3">
                                    <i class="fas fa-user-hard-hat"></i> Kuli yang Mengerjakan
                                </h5>
                                
                                <div class="mb-3">
                                    <span class="info-label">Nama Kuli:</span>
                                    <div class="info-value fw-bold">
                                        <i class="fas fa-user"></i> 
                                        <?php echo htmlspecialchars($kuli['nama_lengkap'] ?? 'Tidak diketahui'); ?>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <span class="info-label">Email:</span>
                                    <div class="info-value">
                                        <i class="fas fa-envelope"></i> 
                                        <?php echo htmlspecialchars($kuli['email'] ?? 'Tidak ada email'); ?>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <span class="info-label">Telepon:</span>
                                    <div class="info-value">
                                        <i class="fas fa-phone"></i> 
                                        <?php echo htmlspecialchars($kuli['telepon'] ?? 'Tidak ada telepon'); ?>
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <span class="info-label">Diterima Sejak:</span>
                                    <div class="info-value">
                                        <i class="fas fa-calendar-alt"></i> 
                                        <?php echo date('d M Y', strtotime($kuli['tanggal_diterima'])); ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <h5 class="border-bottom pb-2 mb-3 mt-4">
                                    <i class="fas fa-clock-history"></i> Timeline
                                </h5>
                                
                                <div class="timeline-item">
                                    <small class="text-muted">Dibuat</small>
                                    <div class="info-value">
                                        <?php echo date('d M Y H:i', strtotime($pekerjaan['created_at'])); ?>
                                    </div>
                                </div>
                                
                                <?php if (!empty($pekerjaan['updated_at']) && $pekerjaan['updated_at'] != $pekerjaan['created_at']): ?>
                                <div class="timeline-item">
                                    <small class="text-muted">Terakhir Diupdate</small>
                                    <div class="info-value">
                                        <?php echo date('d M Y H:i', strtotime($pekerjaan['updated_at'])); ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($pekerjaan['tanggal_mulai'])): ?>
                                <div class="timeline-item">
                                    <small class="text-muted">Mulai Dikerjakan</small>
                                    <div class="info-value">
                                        <?php echo date('d M Y', strtotime($pekerjaan['tanggal_mulai'])); ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($pekerjaan['tanggal_selesai'])): ?>
                                <div class="timeline-item">
                                    <small class="text-muted">Selesai Dikerjakan</small>
                                    <div class="info-value">
                                        <?php echo date('d M Y H:i', strtotime($pekerjaan['tanggal_selesai'])); ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <!-- Khusus untuk selesai -->
                                <?php if ($selesai_tanpa_bukti && !empty($pekerjaan['updated_at'])): ?>
                                <div class="timeline-item" style="border-left-color: #20c997;">
                                    <small class="text-muted">Ditandai Selesai</small>
                                    <div class="info-value fw-bold text-success">
                                        <i class="fas fa-check-circle"></i> 
                                        <?php echo date('d M Y H:i', strtotime($pekerjaan['updated_at'])); ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Catatan Tambahan -->
                        <?php if (!empty($catatan) || !empty($keterangan)): ?>
                        <div class="row mt-4">
                            <div class="col-12">
                                <h5 class="border-bottom pb-2 mb-3">
                                    <i class="fas fa-sticky-note"></i> Informasi Tambahan
                                </h5>
                                
                                <?php if (!empty($catatan)): ?>
                                <div class="mb-3">
                                    <span class="info-label">Catatan:</span>
                                    <div class="info-value p-3 bg-light rounded">
                                        <?php echo nl2br(htmlspecialchars($catatan)); ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($keterangan)): ?>
                                <div class="mb-3">
                                    <span class="info-label">Keterangan:</span>
                                    <div class="info-value p-3 bg-light rounded">
                                        <?php echo nl2br(htmlspecialchars($keterangan)); ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Informasi Pembayaran DP -->
                        <?php if (!empty($pekerjaan['dp_dibayar'])): ?>
                        <div class="row mt-4">
                            <div class="col-12">
                                <h5 class="border-bottom pb-2 mb-3">
                                    <i class="fas fa-money-check-alt"></i> Informasi Pembayaran
                                </h5>
                                
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <span class="info-label">Total Biaya:</span>
                                        <div class="info-value fw-bold text-success">
                                            Rp <?php echo number_format($pekerjaan['total_biaya'] ?? 0, 0, ',', '.'); ?>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <span class="info-label">DP Dibayar:</span>
                                        <div class="info-value fw-bold text-primary">
                                            Rp <?php echo number_format($pekerjaan['dp_dibayar'] ?? 0, 0, ',', '.'); ?>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <span class="info-label">Persentase DP:</span>
                                        <div class="info-value">
                                            <?php 
                                            $percentage = ($pekerjaan['total_biaya'] > 0) 
                                                ? round(($pekerjaan['dp_dibayar'] / $pekerjaan['total_biaya']) * 100, 1) 
                                                : 0;
                                            echo $percentage . '%';
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Footer dengan Tombol -->
                    <div class="card-footer bg-light">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <a href="dashboard_user.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
                            </a>
                            
                            <div class="d-flex gap-2 flex-wrap">
                                <!-- Tombol Lihat Bukti -->
                                <?php if ($pekerjaan['status'] == 'selesai' && !empty($pekerjaan['bukti_selesai'])): ?>
                                <button type="button" class="btn btn-success" 
                                        data-bs-toggle="modal" data-bs-target="#modalViewBukti"
                                        data-file-path="uploads/bukti_selesai/<?php echo $pekerjaan['bukti_selesai']; ?>"
                                        data-file-type="<?php 
                                            $ext = strtolower(pathinfo($pekerjaan['bukti_selesai'], PATHINFO_EXTENSION));
                                            echo in_array($ext, ['jpg', 'jpeg', 'png', 'gif']) ? 'image' : ($ext == 'pdf' ? 'pdf' : 'other');
                                        ?>"
                                        data-judul="<?php echo htmlspecialchars($pekerjaan['judul']); ?>">
                                    <i class="fas fa-file-check"></i> Lihat Bukti Selesai
                                </button>
                                <?php endif; ?>
                                
                                <!-- Tombol Edit Pekerjaan -->
                                <?php if ($pekerjaan['status'] == 'menunggu'): ?>
                                <a href="edit_pekerjaan_user.php?id=<?php echo $pekerjaan['id']; ?>" class="btn btn-warning">
                                    <i class="fas fa-edit"></i> Edit Pekerjaan
                                </a>
                                <?php endif; ?>
                                
                                <!-- Tombol Lihat Aplikasi -->
                                <?php if ($pekerjaan['status'] == 'menunggu'): ?>
                                <a href="daftar_aplikasi.php?id=<?php echo $pekerjaan['id']; ?>" class="btn btn-primary">
                                    <i class="fas fa-list-check"></i> Lihat Aplikasi Kuli
                                </a>
                                <?php endif; ?>
                                
                                <!-- Tombol khusus untuk selesai  -->
                                <?php if ($selesai_tanpa_bukti): ?>
                                <button type="button" class="btn btn-info" onclick="alert('Pekerjaan ini telah ditandai selesai oleh kuli tanpa mengupload bukti.\\n\\nKuli yang mengerjakan: <?php echo htmlspecialchars(addslashes($kuli['nama_lengkap'] ?? 'Tidak diketahui')); ?>\\nDitandai selesai: <?php echo date('d M Y H:i', strtotime($pekerjaan['updated_at'])); ?>');">
                                    <i class="fas fa-info-circle"></i> Info Penyelesaian
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal untuk melihat bukti -->
    <div class="modal fade" id="modalViewBukti" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-file-check"></i> Bukti Pekerjaan Selesai
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <h6 id="modalJudulBukti" class="mb-3"></h6>
                    
                    <div id="imagePreviewModal" style="display: none;">
                        <img id="modalImageBukti" src="" alt="Bukti Pekerjaan Selesai" class="modal-image-preview img-fluid">
                        <div class="mt-3">
                            <a href="#" id="imageDownloadModal" class="btn btn-primary btn-sm" download>
                                <i class="fas fa-download"></i> Download Gambar
                            </a>
                        </div>
                    </div>
                    
                    <div id="pdfPreviewModal" style="display: none;">
                        <div class="alert alert-info">
                            <i class="fas fa-file-pdf pdf-icon-lg"></i>
                            <h5 class="mt-3">File PDF</h5>
                            <p class="mb-3">Bukti pekerjaan selesai dalam format PDF</p>
                            <div class="btn-group">
                                <a href="#" id="pdfViewModal" class="btn btn-primary" target="_blank">
                                    <i class="fas fa-eye"></i> Lihat PDF
                                </a>
                                <a href="#" id="pdfDownloadModal" class="btn btn-success" download>
                                    <i class="fas fa-download"></i> Download
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div id="otherFilePreviewModal" style="display: none;">
                        <div class="alert alert-warning">
                            <i class="fas fa-file file-icon-lg"></i>
                            <h5 class="mt-3">File Dokumen</h5>
                            <p class="mb-3">File bukti dalam format lain</p>
                            <div class="btn-group">
                                <a href="#" id="otherFileViewModal" class="btn btn-primary" target="_blank">
                                    <i class="fas fa-eye"></i> Lihat File
                                </a>
                                <a href="#" id="otherFileDownloadModal" class="btn btn-success" download>
                                    <i class="fas fa-download"></i> Download
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div id="fileNotFoundModal" style="display: none;">
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle fa-3x"></i>
                            <h5 class="mt-3">File Tidak Ditemukan</h5>
                            <p class="mb-0">File bukti pekerjaan tidak ditemukan di server.</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto hide alerts
        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(alert => {
                bootstrap.Alert.getOrCreateInstance(alert).close();
            });
        }, 5000);

        // Modal untuk bukti
        const modalViewBukti = document.getElementById('modalViewBukti');
        if (modalViewBukti) {
            modalViewBukti.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                const judul = button.getAttribute('data-judul');
                const filePath = button.getAttribute('data-file-path');
                const fileType = button.getAttribute('data-file-type');
                
                // Update modal info
                document.getElementById('modalJudulBukti').textContent = judul;
                
                // Hide all preview sections
                document.getElementById('imagePreviewModal').style.display = 'none';
                document.getElementById('pdfPreviewModal').style.display = 'none';
                document.getElementById('otherFilePreviewModal').style.display = 'none';
                document.getElementById('fileNotFoundModal').style.display = 'none';
                
                // Check if file exists
                fetch(filePath, { method: 'HEAD' })
                    .then(response => {
                        if (response.ok) {
                            if (fileType === 'image') {
                                // Show image
                                const imgElement = document.getElementById('modalImageBukti');
                                const downloadLink = document.getElementById('imageDownloadModal');
                                
                                imgElement.src = filePath + '?t=' + new Date().getTime();
                                downloadLink.href = filePath;
                                
                                document.getElementById('imagePreviewModal').style.display = 'block';
                            } else if (fileType === 'pdf') {
                                // Show PDF
                                const pdfViewLink = document.getElementById('pdfViewModal');
                                const pdfDownloadLink = document.getElementById('pdfDownloadModal');
                                
                                pdfViewLink.href = filePath;
                                pdfDownloadLink.href = filePath;
                                
                                document.getElementById('pdfPreviewModal').style.display = 'block';
                            } else {
                                // Show other file
                                const otherViewLink = document.getElementById('otherFileViewModal');
                                const otherDownloadLink = document.getElementById('otherFileDownloadModal');
                                
                                otherViewLink.href = filePath;
                                otherDownloadLink.href = filePath;
                                
                                document.getElementById('otherFilePreviewModal').style.display = 'block';
                            }
                        } else {
                            // File not found
                            document.getElementById('fileNotFoundModal').style.display = 'block';
                        }
                    })
                    .catch(() => {
                        // Error fetching file
                        document.getElementById('fileNotFoundModal').style.display = 'block';
                    });
            });
        }

        // Clean up modal when closed
        if (modalViewBukti) {
            modalViewBukti.addEventListener('hidden.bs.modal', function () {
                // Clear image source to free memory
                const imgElement = document.getElementById('modalImageBukti');
                if (imgElement) {
                    imgElement.src = '';
                }
            });
        }
    </script>
</body>
</html>