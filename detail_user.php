<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'user') {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: dashboard_user.php");
    exit();
}

$pekerjaan_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

// PROSES KONFIRMASI SELESAI - PASTIKAN INI DI ATAS QUERY SELECT
if (isset($_POST['konfirmasi_selesai'])) {
    try {
        // Update status pekerjaan menjadi selesai
        $stmt = $pdo->prepare("UPDATE pekerjaan SET status = 'selesai', updated_at = NOW() WHERE id = ? AND id_user = ?");
        if ($stmt->execute([$pekerjaan_id, $user_id])) {
            $_SESSION['success'] = "Pekerjaan berhasil dikonfirmasi sebagai selesai!";
            
            // Redirect untuk menghindari resubmission
            header("Location: detail_pekerjaan_user.php?id=" . $pekerjaan_id);
            exit();
        } else {
            $_SESSION['error'] = "Gagal mengkonfirmasi pekerjaan selesai";
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
}

// Ambil data pekerjaan dengan verifikasi kepemilikan - SETELAH PROSES UPDATE
$stmt = $pdo->prepare("SELECT p.*, k.nama_kategori, k.deskripsi as deskripsi_kategori
                      FROM pekerjaan p 
                      JOIN kategori_pekerjaan k ON p.id_kategori = k.id 
                      WHERE p.id = ? AND p.id_user = ?");
$stmt->execute([$pekerjaan_id, $user_id]);
$pekerjaan = $stmt->fetch();

if (!$pekerjaan) {
    $_SESSION['error'] = "Pekerjaan tidak ditemukan atau Anda tidak memiliki akses";
    header("Location: dashboard_user.php");
    exit();
}

// DEBUG: Tampilkan status untuk testing (hapus di production)
// echo "<!-- DEBUG: Status pekerjaan: " . $pekerjaan['status'] . " -->";

// Ambil aplikasi pekerjaan
$stmt = $pdo->prepare("SELECT a.*, u.nama_lengkap, u.email, u.telepon
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

// Hitung progress berdasarkan status
$progress_percentage = 0;
$progress_class = '';
$progress_text = '';

switch ($pekerjaan['status']) {
    case 'menunggu':
        $progress_percentage = 25;
        $progress_class = 'bg-warning';
        $progress_text = 'MENUNGGU KULI';
        break;
    case 'diproses':
        $progress_percentage = 75;
        $progress_class = 'bg-info';
        $progress_text = 'SEDANG DIKERJAKAN';
        break;
    case 'selesai':
        $progress_percentage = 100;
        $progress_class = 'bg-success';
        $progress_text = 'SELESAI';
        break;
    default:
        $progress_percentage = 10;
        $progress_class = 'bg-secondary';
        $progress_text = 'DRAFT';
}

// Hitung sisa hari dengan validasi
$sisa_hari = 0;
$tanggal_selesai = '';
if ($pekerjaan['status'] == 'diproses' && !empty($pekerjaan['tanggal_mulai']) && !empty($pekerjaan['durasi'])) {
    $tanggal_selesai = date('Y-m-d', strtotime($pekerjaan['tanggal_mulai'] . ' + ' . $pekerjaan['durasi'] . ' days'));
    $today = date('Y-m-d');
    $sisa = (strtotime($tanggal_selesai) - strtotime($today)) / (60 * 60 * 24);
    $sisa_hari = max(0, ceil($sisa));
    
    // Update progress berdasarkan sisa hari jika sedang diproses
    if ($sisa_hari > 0) {
        $total_hari = $pekerjaan['durasi'];
        $hari_berjalan = $total_hari - $sisa_hari;
        $progress_hari = min(75, 25 + ($hari_berjalan / $total_hari) * 50);
        $progress_percentage = max(25, $progress_hari);
    }
}

// Cek apakah ada kuli yang diterima
$kuli_diterima = array_filter($aplikasi, function($app) {
    return $app['status'] == 'diterima';
});
$ada_kuli_diterima = !empty($kuli_diterima);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Pekerjaan Saya - Kulimang</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .owner-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
        }
        .application-card {
            border-left: 4px solid #28a745;
            transition: all 0.3s ease;
        }
        .application-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .stat-card-user {
            border-radius: 10px;
            text-align: center;
            padding: 15px;
        }
        .table-fixed {
            table-layout: fixed;
        }
        .table-fixed td {
            word-wrap: break-word;
        }
        .btn-konfirmasi {
            background: linear-gradient(135deg, #28a745, #20c997);
            border: none;
            color: white;
            font-weight: bold;
            padding: 12px 20px;
        }
        .btn-konfirmasi:hover {
            background: linear-gradient(135deg, #218838, #1e9e8a);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
        }
        .btn-konfirmasi:disabled {
            background: #6c757d;
            transform: none;
            box-shadow: none;
        }
        /* Progress bar styles */
        .progress {
            background-color: #e9ecef;
            border-radius: 10px;
            overflow: hidden;
            height: 30px;
        }
        .progress-bar {
            transition: width 0.6s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 14px;
        }
        .progress-steps {
            display: flex;
            justify-content: space-between;
            margin-top: 10px;
        }
        .progress-step {
            text-align: center;
            flex: 1;
            position: relative;
        }
        .progress-step::before {
            content: '';
            position: absolute;
            top: -15px;
            left: 50%;
            transform: translateX(-50%);
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background-color: #e9ecef;
            border: 3px solid #e9ecef;
            z-index: 2;
        }
        .progress-step.active::before {
            background-color: #007bff;
            border-color: #007bff;
        }
        .progress-step.completed::before {
            background-color: #28a745;
            border-color: #28a745;
        }
        .step-label {
            font-size: 12px;
            margin-top: 25px;
            color: #6c757d;
        }
        .progress-step.active .step-label {
            color: #007bff;
            font-weight: bold;
        }
        .progress-step.completed .step-label {
            color: #28a745;
            font-weight: bold;
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
        <div class="card owner-card mb-4">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h2 class="card-title mb-2"><?php echo htmlspecialchars($pekerjaan['judul']); ?></h2>
                        <p class="card-text mb-0"><?php echo htmlspecialchars($pekerjaan['deskripsi']); ?></p>
                    </div>
                    <div class="col-md-4 text-end">
                        <h3 class="mb-1">Rp <?php echo number_format($pekerjaan['total_biaya'], 0, ',', '.'); ?></h3>
                        <span class="badge bg-light text-dark fs-6">
                            <i class="fas fa-tag"></i> <?php echo htmlspecialchars($pekerjaan['nama_kategori']); ?>
                        </span>
                        <!-- Debug indicator -->
                        <div class="mt-2">
                            <small class="badge bg-dark">Status: <?php echo $pekerjaan['status']; ?></small>
                            <?php if ($pekerjaan['status'] == 'diproses' && $sisa_hari > 0): ?>
                                <small class="badge bg-warning">Sisa: <?php echo $sisa_hari; ?> hari</small>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistik -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card stat-card-user border-primary">
                    <div class="card-body">
                        <h4 class="text-primary"><?php echo $total_aplikasi; ?></h4>
                        <small>Total Aplikasi</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card-user border-success">
                    <div class="card-body">
                        <h4 class="text-success"><?php echo count($aplikasi_diterima); ?></h4>
                        <small>Diterima</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card-user border-warning">
                    <div class="card-body">
                        <h4 class="text-warning"><?php echo count($aplikasi_menunggu); ?></h4>
                        <small>Menunggu</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card-user border-info">
                    <div class="card-body">
                        <h4 class="text-info"><?php echo $sisa_hari > 0 ? $sisa_hari : (!empty($pekerjaan['durasi']) ? $pekerjaan['durasi'] : 0); ?></h4>
                        <small><?php echo $sisa_hari > 0 ? 'Hari Tersisa' : 'Total Hari'; ?></small>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8">
                <!-- Informasi Pekerjaan -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-info-circle"></i> Informasi Pekerjaan</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <table class="table table-sm table-fixed">
                                    <tr>
                                        <td width="40%"><strong>Status:</strong></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $pekerjaan['status'] == 'menunggu' ? 'warning' : 
                                                     ($pekerjaan['status'] == 'diproses' ? 'info' : 'success'); 
                                            ?> fs-6">
                                                <i class="fas fa-<?php 
                                                    echo $pekerjaan['status'] == 'menunggu' ? 'clock' : 
                                                         ($pekerjaan['status'] == 'diproses' ? 'tools' : 'check-circle'); 
                                                ?>"></i>
                                                <?php echo ucfirst($pekerjaan['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Luas Area:</strong></td>
                                        <td><?php echo !empty($pekerjaan['luas']) ? $pekerjaan['luas'] . ' m²' : '-'; ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Lokasi:</strong></td>
                                        <td><?php echo !empty($pekerjaan['lokasi']) ? htmlspecialchars($pekerjaan['lokasi']) : '-'; ?></td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-sm table-fixed">
                                    <tr>
                                        <td width="40%"><strong>Tanggal Mulai:</strong></td>
                                        <td><?php echo !empty($pekerjaan['tanggal_mulai']) ? date('d F Y', strtotime($pekerjaan['tanggal_mulai'])) : '-'; ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Durasi:</strong></td>
                                        <td><?php echo !empty($pekerjaan['durasi']) ? $pekerjaan['durasi'] . ' hari' : '-'; ?></td>
                                    </tr>
                                    <?php if ($sisa_hari > 0): ?>
                                    <tr>
                                        <td><strong>Sisa Waktu:</strong></td>
                                        <td><span class="badge bg-warning"><?php echo $sisa_hari; ?> hari</span></td>
                                    </tr>
                                    <?php endif; ?>
                                    <?php if (!empty($tanggal_selesai) && $pekerjaan['status'] == 'diproses'): ?>
                                    <tr>
                                        <td><strong>Estimasi Selesai:</strong></td>
                                        <td><span class="badge bg-info"><?php echo date('d F Y', strtotime($tanggal_selesai)); ?></span></td>
                                    </tr>
                                    <?php endif; ?>
                                </table>
                            </div>
                        </div>

                        <?php if (!empty($pekerjaan['catatan'])): ?>
                        <div class="mt-3">
                            <h6><i class="fas fa-sticky-note text-warning"></i> Catatan Khusus</h6>
                            <div class="alert alert-warning">
                                <?php echo nl2br(htmlspecialchars($pekerjaan['catatan'])); ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Aplikasi dari Kuli -->
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-users"></i> Aplikasi dari Kuli</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($total_aplikasi > 0): ?>
                            <?php foreach ($aplikasi as $app): ?>
                                <div class="card application-card mb-3">
                                    <div class="card-body">
                                        <div class="row align-items-center">
                                            <div class="col-md-8">
                                                <h6 class="mb-1"><?php echo htmlspecialchars($app['nama_lengkap']); ?></h6>
                                                <p class="mb-1 text-muted">
                                                    <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($app['email']); ?>
                                                    <?php if (!empty($app['telepon'])): ?>
                                                        <br><i class="fas fa-phone"></i> <?php echo htmlspecialchars($app['telepon']); ?>
                                                    <?php endif; ?>
                                                </p>
                                                <?php if (!empty($app['pesan'])): ?>
                                                    <p class="mb-1 small">
                                                        <i class="fas fa-comment"></i> "<?php echo htmlspecialchars($app['pesan']); ?>"
                                                    </p>
                                                <?php endif; ?>
                                                <small class="text-muted">
                                                    Diajukan: <?php echo date('d F Y H:i', strtotime($app['created_at'])); ?>
                                                </small>
                                            </div>
                                            <div class="col-md-4 text-end">
                                                <div class="mb-2">
                                                    <span class="badge bg-<?php 
                                                        echo $app['status'] == 'menunggu' ? 'warning' : 
                                                             ($app['status'] == 'diterima' ? 'success' : 'danger'); 
                                                    ?> fs-6">
                                                        <?php echo ucfirst($app['status']); ?>
                                                    </span>
                                                </div>
                                                <?php if ($app['status'] == 'menunggu' && $pekerjaan['status'] == 'menunggu'): ?>
                                                    <div class="btn-group-vertical">
                                                        <a href="terima_aplikasi.php?id=<?php echo $app['id']; ?>&pekerjaan_id=<?php echo $pekerjaan_id; ?>" 
                                                           class="btn btn-success btn-sm mb-1"
                                                           onclick="return confirm('Terima <?php echo htmlspecialchars($app['nama_lengkap']); ?>?')">
                                                            <i class="fas fa-check"></i> Terima
                                                        </a>
                                                        <a href="tolak_aplikasi.php?id=<?php echo $app['id']; ?>" 
                                                           class="btn btn-danger btn-sm"
                                                           onclick="return confirm('Tolak <?php echo htmlspecialchars($app['nama_lengkap']); ?>?')">
                                                            <i class="fas fa-times"></i> Tolak
                                                        </a>
                                                    </div>
                                                <?php elseif ($app['status'] == 'diterima'): ?>
                                                    <span class="text-success">
                                                        <i class="fas fa-check-circle"></i> Diterima
                                                    </span>
                                                    <?php if ($pekerjaan['status'] == 'diproses'): ?>
                                                        <div class="mt-1">
                                                            <small class="text-info">Sedang mengerjakan</small>
                                                        </div>
                                                    <?php endif; ?>
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
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                <p class="text-muted">Belum ada aplikasi dari kuli.</p>
                                <p class="text-muted small">Kuli akan mengajukan aplikasi untuk pekerjaan Anda.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <!-- Action Panel -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-tools"></i> Kelola Pekerjaan</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <?php if ($pekerjaan['status'] == 'menunggu'): ?>
                                <a href="edit_pekerjaan.php?id=<?php echo $pekerjaan['id']; ?>" class="btn btn-warning">
                                    <i class="fas fa-edit"></i> Edit Pekerjaan
                                </a>
                            <?php elseif ($pekerjaan['status'] == 'diproses' && $ada_kuli_diterima): ?>
                                <!-- Tombol Konfirmasi Selesai untuk User -->
                                <form method="POST" id="formKonfirmasiSelesai">
                                    <button type="submit" name="konfirmasi_selesai" value="1" 
                                            class="btn btn-konfirmasi w-100"
                                            onclick="return confirmKonfirmasiSelesai()">
                                        <i class="fas fa-check-circle"></i> Konfirmasi Selesai
                                    </button>
                                </form>
                                <div class="alert alert-info mt-2">
                                    <small>
                                        <i class="fas fa-info-circle"></i> 
                                        Klik untuk verifikasi bahwa pekerjaan telah selesai dengan baik
                                    </small>
                                </div>
                            <?php elseif ($pekerjaan['status'] == 'diproses' && !$ada_kuli_diterima): ?>
                                <div class="alert alert-warning">
                                    <small>
                                        <i class="fas fa-exclamation-triangle"></i> 
                                        Belum ada kuli yang diterima. Terima kuli terlebih dahulu.
                                    </small>
                                </div>
                            <?php elseif ($pekerjaan['status'] == 'selesai'): ?>
                                <div class="alert alert-success text-center">
                                    <i class="fas fa-check-circle fa-2x mb-2"></i>
                                    <p class="mb-0"><strong>Pekerjaan Selesai</strong></p>
                                    <small>Pekerjaan telah dikonfirmasi selesai pada <?php echo date('d F Y H:i', strtotime($pekerjaan['updated_at'] ?: $pekerjaan['created_at'])); ?></small>
                                </div>
                            <?php endif; ?>
                            
                            <a href="pekerjaan_saya.php" class="btn btn-info">
                                <i class="fas fa-list"></i> Semua Pekerjaan
                            </a>
                            <a href="aplikasi_pekerjaan.php" class="btn btn-secondary">
                                <i class="fas fa-bell"></i> Semua Aplikasi
                            </a>
                            <a href="upload_pekerjaan.php" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Pekerjaan Baru
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Progress Bar yang Diperbaiki -->
                <div class="card mb-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-chart-line"></i> Progress Pekerjaan</h5>
                    </div>
                    <div class="card-body">
                        <!-- Progress Bar -->
                        <div class="progress mb-3">
                            <div class="progress-bar <?php echo $progress_class; ?>" 
                                 role="progressbar" 
                                 style="width: <?php echo $progress_percentage; ?>%"
                                 aria-valuenow="<?php echo $progress_percentage; ?>" 
                                 aria-valuemin="0" 
                                 aria-valuemax="100">
                                <strong><?php echo $progress_text; ?> (<?php echo $progress_percentage; ?>%)</strong>
                            </div>
                        </div>
                        
                        <!-- Progress Steps -->
                        <div class="progress-steps">
                            <div class="progress-step <?php echo $pekerjaan['status'] == 'menunggu' ? 'active' : ($pekerjaan['status'] == 'diproses' || $pekerjaan['status'] == 'selesai' ? 'completed' : ''); ?>">
                                <div class="step-label">Menunggu</div>
                            </div>
                            <div class="progress-step <?php echo $pekerjaan['status'] == 'diproses' ? 'active' : ($pekerjaan['status'] == 'selesai' ? 'completed' : ''); ?>">
                                <div class="step-label">Diproses</div>
                            </div>
                            <div class="progress-step <?php echo $pekerjaan['status'] == 'selesai' ? 'active completed' : ''; ?>">
                                <div class="step-label">Selesai</div>
                            </div>
                        </div>
                        
                        <div class="text-center mt-4">
                            <?php if ($pekerjaan['status'] == 'menunggu'): ?>
                                <i class="fas fa-clock fa-2x text-warning mb-2"></i>
                                <p class="mb-1"><strong>Menunggu Kuli</strong></p>
                                <small class="text-muted">Tunggu kuli mengajukan aplikasi</small>
                            <?php elseif ($pekerjaan['status'] == 'diproses'): ?>
                                <i class="fas fa-tools fa-2x text-info mb-2"></i>
                                <p class="mb-1"><strong>Sedang Dikerjakan</strong></p>
                                <small class="text-muted">
                                    Pekerjaan sedang berjalan
                                    <?php if ($sisa_hari > 0): ?>
                                        <br><span class="badge bg-warning"><?php echo $sisa_hari; ?> hari tersisa</span>
                                    <?php endif; ?>
                                    <?php if (!empty($tanggal_selesai)): ?>
                                        <br><small>Estimasi: <?php echo date('d F Y', strtotime($tanggal_selesai)); ?></small>
                                    <?php endif; ?>
                                </small>
                            <?php else: ?>
                                <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                                <p class="mb-1"><strong>Selesai</strong></p>
                                <small class="text-muted">
                                    Pekerjaan telah selesai
                                    <?php if (!empty($pekerjaan['updated_at'])): ?>
                                        <br><small>Pada <?php echo date('d F Y', strtotime($pekerjaan['updated_at'])); ?></small>
                                    <?php endif; ?>
                                </small>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Info Cepat -->
                <div class="card">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0"><i class="fas fa-info-circle"></i> Info Cepat</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm table-borderless">
                            <tr>
                                <td><strong>ID:</strong></td>
                                <td><code>#<?php echo $pekerjaan['id']; ?></code></td>
                            </tr>
                            <tr>
                                <td><strong>Dibuat:</strong></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($pekerjaan['created_at'])); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Update Terakhir:</strong></td>
                                <td>
                                    <?php 
                                    if (!empty($pekerjaan['updated_at']) && $pekerjaan['updated_at'] != '0000-00-00 00:00:00') {
                                        echo date('d/m/Y H:i', strtotime($pekerjaan['updated_at']));
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Kategori:</strong></td>
                                <td><?php echo htmlspecialchars($pekerjaan['nama_kategori']); ?></td>
                            </tr>
                            <?php if ($ada_kuli_diterima): ?>
                            <tr>
                                <td><strong>Kuli Diterima:</strong></td>
                                <td>
                                    <?php 
                                    $kuli_names = array_map(function($app) {
                                        return htmlspecialchars($app['nama_lengkap']);
                                    }, $kuli_diterima);
                                    echo implode(', ', $kuli_names);
                                    ?>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </table>
                    </div>
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

        // Konfirmasi sebelum menyelesaikan pekerjaan
        function confirmKonfirmasiSelesai() {
            return confirm('Apakah Anda yakin pekerjaan ini sudah selesai dengan baik?\n\nSetelah dikonfirmasi, status tidak dapat diubah kembali.');
        }

        // Loading animation untuk tombol konfirmasi selesai
        document.addEventListener('DOMContentLoaded', function() {
            const konfirmasiBtn = document.querySelector('button[name="konfirmasi_selesai"]');
            if (konfirmasiBtn) {
                konfirmasiBtn.addEventListener('click', function(e) {
                    const originalText = this.innerHTML;
                    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';
                    this.disabled = true;
                    
                    // Biarkan form submit
                    setTimeout(() => {
                        this.form.submit();
                    }, 1000);
                });
            }
        });

        // Force reload page to avoid cache issues
        if (performance.navigation.type === 1) {
            // Page was reloaded
            console.log('Page was reloaded');
        }
    </script>
</body>
</html>