<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

// Function untuk memastikan kolom ada di tabel
function ensureColumnsExist($pdo) {
    $columns_to_add = [
        'panjang' => "ALTER TABLE pekerjaan ADD COLUMN panjang DECIMAL(10,2) NULL",
        'lebar' => "ALTER TABLE pekerjaan ADD COLUMN lebar DECIMAL(10,2) NULL",
        'dp_dibayar' => "ALTER TABLE pekerjaan ADD COLUMN dp_dibayar DECIMAL(15,2) NULL",
        'bukti_dp' => "ALTER TABLE pekerjaan ADD COLUMN bukti_dp VARCHAR(255) NULL",
        'bukti_selesai' => "ALTER TABLE pekerjaan ADD COLUMN bukti_selesai VARCHAR(255) NULL",
        'tanggal_selesai' => "ALTER TABLE pekerjaan ADD COLUMN tanggal_selesai DATETIME NULL",
        'updated_at' => "ALTER TABLE pekerjaan ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP"
    ];
    
    try {
        $stmt = $pdo->prepare("DESCRIBE pekerjaan");
        $stmt->execute();
        $existing_columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($columns_to_add as $column => $sql) {
            if (!in_array($column, $existing_columns)) {
                $pdo->exec($sql);
                error_log("Kolom $column berhasil ditambahkan ke tabel pekerjaan");
            }
        }
        return true;
    } catch (PDOException $e) {
        error_log("Error checking/adding columns: " . $e->getMessage());
        return false;
    }
}

// Panggil function untuk memastikan kolom ada
ensureColumnsExist($pdo);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Kulimang</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .card-hover:hover {
            transform: translateY(-2px);
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .table-hover tbody tr:hover {
            background-color: rgba(0, 123, 255, 0.05);
        }
        .bukti-dp-badge, .bukti-selesai-badge {
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .bukti-dp-badge:hover, .bukti-selesai-badge:hover {
            transform: scale(1.05);
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        .modal-image {
            max-width: 100%;
            max-height: 80vh;
        }
        .pdf-icon {
            font-size: 3rem;
            color: #dc3545;
        }
        .file-icon {
            font-size: 3rem;
            color: #6c757d;
        }
        .badge.bg-success {
            font-weight: 500;
        }
        .status-badge-sm {
            font-size: 0.8em;
            padding: 4px 8px;
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
                    <small class="badge bg-warning text-dark ms-1">Admin</small>
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

        <!-- Dashboard Admin -->
        <div class="row mb-3">
            <div class="col-md-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3>Dashboard Admin</h3>
                        <p class="text-muted">Kelola seluruh sistem Kulimang</p>
                    </div>
                    <div>
                        <a href="admin_manage_users.php" class="btn btn-info btn-sm">
                            <i class="fas fa-users"></i> Kelola User
                        </a>
                        <a href="admin_manage_categories.php" class="btn btn-secondary btn-sm">
                            <i class="fas fa-tags"></i> Kelola Kategori
                        </a>
                        <a href="admin_create_pekerjaan.php" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> Tambah Pekerjaan
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistik Admin -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card card-hover border-primary">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title text-muted">Total User</h6>
                                <h3 class="text-primary">
                                    <?php
                                    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role != 'admin'");
                                    echo $stmt->fetch()['total'];
                                    ?>
                                </h3>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-users fa-2x text-primary"></i>
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
                                <h6 class="card-title text-muted">Total Pekerjaan</h6>
                                <h3 class="text-success">
                                    <?php
                                    $stmt = $pdo->query("SELECT COUNT(*) as total FROM pekerjaan");
                                    echo $stmt->fetch()['total'];
                                    ?>
                                </h3>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-tasks fa-2x text-success"></i>
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
                                <h6 class="card-title text-muted">Sudah Upload DP</h6>
                                <h3 class="text-warning">
                                    <?php
                                    $stmt = $pdo->query("SELECT COUNT(*) as total FROM pekerjaan WHERE bukti_dp IS NOT NULL");
                                    echo $stmt->fetch()['total'];
                                    ?>
                                </h3>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-file-invoice-dollar fa-2x text-warning"></i>
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
                                <h6 class="card-title text-muted">Selesai dengan Bukti</h6>
                                <h3 class="text-info">
                                    <?php
                                    $stmt = $pdo->query("SELECT COUNT(*) as total FROM pekerjaan WHERE status = 'selesai' AND bukti_selesai IS NOT NULL");
                                    echo $stmt->fetch()['total'];
                                    ?>
                                </h3>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-file-check fa-2x text-info"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-list"></i> Semua Pekerjaan</h5>
                        <div>
                            <span class="badge bg-primary me-2">
                                Total: <?php
                                $stmt = $pdo->query("SELECT COUNT(*) as total FROM pekerjaan");
                                echo $stmt->fetch()['total'];
                                ?>
                            </span>
                            <a href="admin_create_pekerjaan.php" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus"></i> Tambah Pekerjaan
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php
                        $stmt = $pdo->query("SELECT p.*, k.nama_kategori, u.nama_lengkap as pemilik 
                                           FROM pekerjaan p 
                                           JOIN kategori_pekerjaan k ON p.id_kategori = k.id 
                                           JOIN users u ON p.id_user = u.id 
                                           ORDER BY p.created_at DESC");
                        $pekerjaan = $stmt->fetchAll();
                        
                        if ($pekerjaan): ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Judul</th>
                                            <th>Pemilik</th>
                                            <th>Kategori</th>
                                            <th>Luas</th>
                                            <th>Total Biaya / DP</th>
                                            <th>Status</th>
                                            <th>Bukti DP</th>
                                            <th>Bukti Selesai</th>
                                            <th>Tanggal</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($pekerjaan as $p): 
                                            // Hitung luas dari panjang dan lebar
                                            $luas = ($p['panjang'] ?? 0) * ($p['lebar'] ?? 0);
                                            $dp_dibayar = $p['dp_dibayar'] ?? 0;
                                            $total_biaya = $p['total_biaya'] ?? 0;
                                            
                                            // Data untuk bukti DP
                                            $bukti_dp = $p['bukti_dp'] ?? null;
                                            $has_bukti_dp = !empty($bukti_dp);
                                            $bukti_dp_path = $has_bukti_dp ? 'uploads/bukti_dp/' . $bukti_dp : '';
                                            $dp_file_exists = $has_bukti_dp ? file_exists($bukti_dp_path) : false;
                                            
                                            // Data untuk bukti selesai
                                            $bukti_selesai = $p['bukti_selesai'] ?? null;
                                            $has_bukti_selesai = !empty($bukti_selesai);
                                            $bukti_selesai_path = $has_bukti_selesai ? 'uploads/bukti_selesai/' . $bukti_selesai : '';
                                            $selesai_file_exists = $has_bukti_selesai ? file_exists($bukti_selesai_path) : false;
                                        ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($p['judul']); ?></strong>
                                                    <?php if (strtotime($p['created_at']) > strtotime('-1 day')): ?>
                                                        <span class="badge bg-success ms-1 status-badge-sm">Baru</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($p['pemilik']); ?></td>
                                                <td>
                                                    <span class="badge bg-secondary"><?php echo htmlspecialchars($p['nama_kategori']); ?></span>
                                                </td>
                                                <td>
                                                    <?php echo number_format($luas, 2); ?> m²
                                                    <br>
                                                    <small class="text-muted"><?php echo ($p['panjang'] ?? 0); ?>m x <?php echo ($p['lebar'] ?? 0); ?>m</small>
                                                </td>
                                                <td>
                                                    <div class="small">
                                                        <strong>Total:</strong> Rp <?php echo number_format($total_biaya, 0, ',', '.'); ?>
                                                        <br>
                                                        <strong>DP:</strong> Rp <?php echo number_format($dp_dibayar, 0, ',', '.'); ?>
                                                        <?php if ($dp_dibayar > 0 && $total_biaya > 0): ?>
                                                            <br>
                                                            <small class="text-muted">(<?php echo round(($dp_dibayar / $total_biaya) * 100, 1); ?>%)</small>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <?php
                                                    $status = $p['status'] ?? 'menunggu';
                                                    $status_display = $status ? ucfirst(str_replace('_', ' ', $status)) : 'Menunggu';
                                                    $badge_color = 'secondary';
                                                    
                                                    if ($status == 'menunggu') $badge_color = 'warning';
                                                    elseif ($status == 'diproses') $badge_color = 'info';
                                                    elseif ($status == 'dalam_pengerjaan') $badge_color = 'primary';
                                                    elseif ($status == 'selesai') $badge_color = 'success';
                                                    elseif ($status == 'ditolak') $badge_color = 'danger';
                                                    ?>
                                                    <span class="badge bg-<?php echo $badge_color; ?>">
                                                        <?php echo $status_display; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($has_bukti_dp && $dp_file_exists): 
                                                        $dp_file_extension = strtolower(pathinfo($bukti_dp, PATHINFO_EXTENSION));
                                                        $dp_is_pdf = $dp_file_extension === 'pdf';
                                                    ?>
                                                        <span class="badge bg-success bukti-dp-badge" 
                                                              data-bs-toggle="modal" 
                                                              data-bs-target="#buktiDpModal"
                                                              data-file-path="<?php echo $bukti_dp_path; ?>"
                                                              data-file-type="<?php echo $dp_is_pdf ? 'pdf' : 'image'; ?>"
                                                              data-judul="<?php echo htmlspecialchars($p['judul']); ?>"
                                                              data-pemilik="<?php echo htmlspecialchars($p['pemilik']); ?>"
                                                              title="Klik untuk lihat bukti DP">
                                                            <i class="fas fa-file-invoice"></i> Lihat DP
                                                        </span>
                                                    <?php elseif ($has_bukti_dp && !$dp_file_exists): ?>
                                                        <span class="badge bg-warning" title="File tidak ditemukan">
                                                            <i class="fas fa-exclamation-triangle"></i> File Hilang
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger">
                                                            <i class="fas fa-times-circle"></i> Belum DP
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($has_bukti_selesai && $selesai_file_exists): 
                                                        $selesai_file_extension = strtolower(pathinfo($bukti_selesai, PATHINFO_EXTENSION));
                                                        $selesai_is_image = in_array($selesai_file_extension, ['jpg', 'jpeg', 'png', 'gif']);
                                                    ?>
                                                        <span class="badge bg-success bukti-selesai-badge" 
                                                              data-bs-toggle="modal" 
                                                              data-bs-target="#buktiSelesaiModal"
                                                              data-file-path="<?php echo $bukti_selesai_path; ?>"
                                                              data-file-type="<?php echo $selesai_is_image ? 'image' : 'document'; ?>"
                                                              data-judul="<?php echo htmlspecialchars($p['judul']); ?>"
                                                              data-pemilik="<?php echo htmlspecialchars($p['pemilik']); ?>"
                                                              data-tanggal="<?php echo !empty($p['tanggal_selesai']) ? date('d/m/Y H:i', strtotime($p['tanggal_selesai'])) : date('d/m/Y H:i', strtotime($p['updated_at'])); ?>"
                                                              title="Klik untuk lihat bukti pekerjaan selesai">
                                                            <i class="fas fa-file-check"></i> Bukti Selesai
                                                        </span>
                                                    <?php elseif ($has_bukti_selesai && !$selesai_file_exists): ?>
                                                        <span class="badge bg-warning" title="File tidak ditemukan">
                                                            <i class="fas fa-exclamation-triangle"></i> File Hilang
                                                        </span>
                                                    <?php elseif ($status == 'selesai'): ?>
                                                        <span class="badge bg-secondary" title="Pekerjaan selesai tanpa bukti">
                                                            <i class="fas fa-exclamation-circle"></i> Tanpa Bukti
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge bg-light text-dark">
                                                            <i class="fas fa-clock"></i> Belum Selesai
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo date('d/m/Y', strtotime($p['created_at'])); ?></td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="detail_admin.php?id=<?php echo $p['id']; ?>" class="btn btn-outline-primary" title="Detail">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <a href="admin_edit_pekerjaan.php?id=<?php echo $p['id']; ?>" class="btn btn-outline-warning" title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <a href="admin_delete_pekerjaan.php?id=<?php echo $p['id']; ?>" class="btn btn-outline-danger" title="Hapus" onclick="return confirmDelete()">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-tasks fa-3x text-muted mb-3"></i>
                                <p class="text-muted">Belum ada pekerjaan.</p>
                                <a href="admin_create_pekerjaan.php" class="btn btn-primary">Tambah Pekerjaan Pertama</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal untuk menampilkan bukti DP -->
    <div class="modal fade" id="buktiDpModal" tabindex="-1" aria-labelledby="buktiDpModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="buktiDpModalLabel">
                        <i class="fas fa-file-invoice-dollar"></i> Bukti Pembayaran DP
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <h6 id="modalJudulDp" class="mb-2"></h6>
                    <p class="text-muted" id="modalPemilikDp"></p>
                    
                    <div id="imagePreviewDp" style="display: none;">
                        <img id="modalImageDp" src="" alt="Bukti DP" class="modal-image img-fluid rounded border">
                    </div>
                    
                    <div id="pdfPreviewDp" style="display: none;">
                        <div class="alert alert-info">
                            <i class="fas fa-file-pdf pdf-icon"></i>
                            <h5 class="mt-2">File PDF - Bukti Pembayaran DP</h5>
                            <div class="btn-group mt-3">
                                <a href="#" id="pdfViewLinkDp" class="btn btn-primary" target="_blank">
                                    <i class="fas fa-eye"></i> Lihat PDF
                                </a>
                                <a href="#" id="pdfDownloadLinkDp" class="btn btn-success" download>
                                    <i class="fas fa-download"></i> Download
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div id="fileNotFoundDp" style="display: none;">
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle fa-3x"></i>
                            <h5 class="mt-2">File Tidak Ditemukan</h5>
                            <p>File bukti pembayaran DP tidak ditemukan di server.</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal untuk melihat bukti selesai -->
    <div class="modal fade" id="buktiSelesaiModal" tabindex="-1" aria-labelledby="buktiSelesaiModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="buktiSelesaiModalLabel">
                        <i class="fas fa-check-circle"></i> Bukti Pekerjaan Selesai
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <h6 id="modalJudulSelesaiAdmin" class="mb-2"></h6>
                    <p class="text-muted" id="modalPemilikSelesai"></p>
                    <p class="text-muted" id="modalTanggalSelesaiAdmin"></p>
                    
                    <div id="imagePreviewSelesaiAdmin" style="display: none;">
                        <img id="modalImageSelesaiAdmin" src="" alt="Bukti Pekerjaan Selesai" class="modal-image img-fluid rounded border">
                        <div class="mt-3">
                            <a href="#" id="imageDownloadSelesaiAdmin" class="btn btn-primary btn-sm" download>
                                <i class="fas fa-download"></i> Download Gambar
                            </a>
                        </div>
                    </div>
                    
                    <div id="pdfPreviewSelesaiAdmin" style="display: none;">
                        <div class="alert alert-info">
                            <i class="fas fa-file-pdf pdf-icon"></i>
                            <h5 class="mt-2">File PDF - Bukti Pekerjaan Selesai</h5>
                            <div class="btn-group mt-3">
                                <a href="#" id="pdfViewLinkSelesaiAdmin" class="btn btn-primary" target="_blank">
                                    <i class="fas fa-eye"></i> Lihat PDF
                                </a>
                                <a href="#" id="pdfDownloadSelesaiAdmin" class="btn btn-success" download>
                                    <i class="fas fa-download"></i> Download
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div id="otherFilePreviewSelesaiAdmin" style="display: none;">
                        <div class="alert alert-warning">
                            <i class="fas fa-file file-icon"></i>
                            <h5 class="mt-2">File Dokumen - Bukti Pekerjaan Selesai</h5>
                            <div class="btn-group mt-3">
                                <a href="#" id="otherFileViewSelesaiAdmin" class="btn btn-primary" target="_blank">
                                    <i class="fas fa-eye"></i> Lihat File
                                </a>
                                <a href="#" id="otherFileDownloadSelesaiAdmin" class="btn btn-success" download>
                                    <i class="fas fa-download"></i> Download
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div id="fileNotFoundSelesaiAdmin" style="display: none;">
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle fa-3x"></i>
                            <h5 class="mt-2">File Tidak Ditemukan</h5>
                            <p>File bukti pekerjaan selesai tidak ditemukan di server.</p>
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
        function confirmDelete() {
            return confirm('Apakah Anda yakin ingin menghapus pekerjaan ini?');
        }

        // Modal untuk menampilkan bukti DP
        const buktiDpModal = document.getElementById('buktiDpModal');
        if (buktiDpModal) {
            buktiDpModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                const judul = button.getAttribute('data-judul');
                const pemilik = button.getAttribute('data-pemilik');
                const filePath = button.getAttribute('data-file-path');
                const fileType = button.getAttribute('data-file-type');
                
                // Update modal info
                document.getElementById('modalJudulDp').textContent = judul;
                document.getElementById('modalPemilikDp').textContent = 'Pemilik: ' + pemilik;
                
                // Hide all preview sections
                document.getElementById('imagePreviewDp').style.display = 'none';
                document.getElementById('pdfPreviewDp').style.display = 'none';
                document.getElementById('fileNotFoundDp').style.display = 'none';
                
                // Check if file exists
                fetch(filePath, { method: 'HEAD' })
                    .then(response => {
                        if (response.ok) {
                            if (fileType === 'image') {
                                // Show image
                                const imgElement = document.getElementById('modalImageDp');
                                imgElement.src = filePath + '?t=' + new Date().getTime();
                                document.getElementById('imagePreviewDp').style.display = 'block';
                            } else if (fileType === 'pdf') {
                                // Show PDF
                                const pdfViewLink = document.getElementById('pdfViewLinkDp');
                                const pdfDownloadLink = document.getElementById('pdfDownloadLinkDp');
                                
                                pdfViewLink.href = filePath;
                                pdfDownloadLink.href = filePath;
                                
                                document.getElementById('pdfPreviewDp').style.display = 'block';
                            }
                        } else {
                            // File not found
                            document.getElementById('fileNotFoundDp').style.display = 'block';
                        }
                    })
                    .catch(() => {
                        // Error fetching file
                        document.getElementById('fileNotFoundDp').style.display = 'block';
                    });
            });
        }

        // Modal untuk bukti selesai di admin
        const buktiSelesaiModal = document.getElementById('buktiSelesaiModal');
        if (buktiSelesaiModal) {
            buktiSelesaiModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                const judul = button.getAttribute('data-judul');
                const pemilik = button.getAttribute('data-pemilik');
                const tanggal = button.getAttribute('data-tanggal');
                const filePath = button.getAttribute('data-file-path');
                const fileType = button.getAttribute('data-file-type');
                
                // Update modal info
                document.getElementById('modalJudulSelesaiAdmin').textContent = judul;
                document.getElementById('modalPemilikSelesai').textContent = 'Pemilik: ' + pemilik;
                document.getElementById('modalTanggalSelesaiAdmin').textContent = 'Tanggal Selesai: ' + tanggal;
                
                // Hide all preview sections
                document.getElementById('imagePreviewSelesaiAdmin').style.display = 'none';
                document.getElementById('pdfPreviewSelesaiAdmin').style.display = 'none';
                document.getElementById('otherFilePreviewSelesaiAdmin').style.display = 'none';
                document.getElementById('fileNotFoundSelesaiAdmin').style.display = 'none';
                
                // Check if file exists
                fetch(filePath, { method: 'HEAD' })
                    .then(response => {
                        if (response.ok) {
                            if (fileType === 'image') {
                                // Show image
                                const imgElement = document.getElementById('modalImageSelesaiAdmin');
                                const downloadLink = document.getElementById('imageDownloadSelesaiAdmin');
                                
                                imgElement.src = filePath + '?t=' + new Date().getTime();
                                downloadLink.href = filePath;
                                
                                document.getElementById('imagePreviewSelesaiAdmin').style.display = 'block';
                            } else if (fileType === 'document') {
                                const fileExt = filePath.split('.').pop().toLowerCase();
                                
                                if (fileExt === 'pdf') {
                                    // Show PDF
                                    const pdfViewLink = document.getElementById('pdfViewLinkSelesaiAdmin');
                                    const pdfDownloadLink = document.getElementById('pdfDownloadSelesaiAdmin');
                                    
                                    pdfViewLink.href = filePath;
                                    pdfDownloadLink.href = filePath;
                                    
                                    document.getElementById('pdfPreviewSelesaiAdmin').style.display = 'block';
                                } else {
                                    // Show other file
                                    const otherViewLink = document.getElementById('otherFileViewSelesaiAdmin');
                                    const otherDownloadLink = document.getElementById('otherFileDownloadSelesaiAdmin');
                                    
                                    otherViewLink.href = filePath;
                                    otherDownloadLink.href = filePath;
                                    
                                    document.getElementById('otherFilePreviewSelesaiAdmin').style.display = 'block';
                                }
                            }
                        } else {
                            // File not found
                            document.getElementById('fileNotFoundSelesaiAdmin').style.display = 'block';
                        }
                    })
                    .catch(() => {
                        // Error fetching file
                        document.getElementById('fileNotFoundSelesaiAdmin').style.display = 'block';
                    });
            });
        }

        // Clean up modals when closed
        function cleanupModal(modalId, imageElementId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.addEventListener('hidden.bs.modal', function () {
                    const imgElement = document.getElementById(imageElementId);
                    if (imgElement) {
                        imgElement.src = '';
                    }
                });
            }
        }

        cleanupModal('buktiDpModal', 'modalImageDp');
        cleanupModal('buktiSelesaiModal', 'modalImageSelesaiAdmin');

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