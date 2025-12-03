<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'kuli') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 15;
$offset = ($page - 1) * $limit;

// Filter
$kategori = isset($_GET['kategori']) ? $_GET['kategori'] : '';
$lokasi = isset($_GET['lokasi']) ? $_GET['lokasi'] : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'terbaru';

// Query dinamis
$where = "WHERE p.status = 'menunggu' AND p.id_user != :user_id";
$params = [':user_id' => $user_id];

if (!empty($kategori)) {
    $where .= " AND p.id_kategori = :kategori";
    $params[':kategori'] = $kategori;
}

if (!empty($lokasi)) {
    $where .= " AND p.lokasi LIKE :lokasi";
    $params[':lokasi'] = "%$lokasi%";
}

// Subquery untuk exclude yang sudah diapply
$where .= " AND p.id NOT IN (
    SELECT id_pekerjaan 
    FROM aplikasi_pekerjaan 
    WHERE id_kuli = :user_id2 AND status != 'ditolak'
)";
$params[':user_id2'] = $user_id;

// Order by
$order_by = "ORDER BY p.created_at DESC";
if ($sort == 'biaya_tinggi') {
    $order_by = "ORDER BY p.total_biaya DESC";
} elseif ($sort == 'biaya_rendah') {
    $order_by = "ORDER BY p.total_biaya ASC";
}

// Hitung total
$sql_count = "SELECT COUNT(*) as total 
              FROM pekerjaan p 
              $where";
$stmt_count = $pdo->prepare($sql_count);
foreach ($params as $key => $value) {
    $stmt_count->bindValue($key, $value);
}
$stmt_count->execute();
$total = $stmt_count->fetch()['total'];
$total_pages = ceil($total / $limit);

// Query data
$sql = "SELECT p.*, k.nama_kategori, u.nama_lengkap as pemilik, u.telepon as telepon_pemilik,
               (SELECT COUNT(*) FROM aplikasi_pekerjaan ap WHERE ap.id_pekerjaan = p.id) as jumlah_aplikasi
        FROM pekerjaan p 
        JOIN kategori_pekerjaan k ON p.id_kategori = k.id 
        JOIN users u ON p.id_user = u.id 
        $where 
        $order_by 
        LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$pekerjaan = $stmt->fetchAll();

// Get kategori untuk filter
$kategori_list = $pdo->query("SELECT * FROM kategori_pekerjaan ORDER BY nama_kategori")->fetchAll();

// Notifikasi
$stmt_notif = $pdo->prepare("SELECT COUNT(*) as notif_count FROM aplikasi_pekerjaan 
                            WHERE id_kuli = ? AND status = 'diterima'");
$stmt_notif->execute([$user_id]);
$notif_count = $stmt_notif->fetch()['notif_count'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Semua Pekerjaan Tersedia - Kulimang</title>
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
        .filter-card {
            position: sticky;
            top: 20px;
        }
        .badge-new {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.7; }
            100% { opacity: 1; }
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
                    <small class="badge bg-light text-dark ms-1">Kuli</small>
                </span>
                <a href="dashboard_kuli.php" class="btn btn-outline-light btn-sm me-2">
                    <i class="fas fa-home"></i> Dashboard
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

        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard_kuli.php">Dashboard</a></li>
                <li class="breadcrumb-item active">Semua Pekerjaan Tersedia</li>
            </ol>
        </nav>

        <div class="row">
            <!-- Sidebar Filter -->
            <div class="col-lg-3 mb-4">
                <div class="card filter-card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-filter"></i> Filter Pekerjaan</h5>
                    </div>
                    <div class="card-body">
                        <form method="GET" action="">
                            <div class="mb-3">
                                <label class="form-label">Kategori</label>
                                <select name="kategori" class="form-select">
                                    <option value="">Semua Kategori</option>
                                    <?php foreach ($kategori_list as $kat): ?>
                                        <option value="<?php echo $kat['id']; ?>" 
                                                <?php echo ($kategori == $kat['id']) ? 'selected' : ''; ?>>
                                            <?php echo $kat['nama_kategori']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Lokasi</label>
                                <input type="text" name="lokasi" class="form-control" 
                                       placeholder="Cari lokasi..." value="<?php echo htmlspecialchars($lokasi); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Urutkan</label>
                                <select name="sort" class="form-select">
                                    <option value="terbaru" <?php echo ($sort == 'terbaru') ? 'selected' : ''; ?>>Terbaru</option>
                                    <option value="biaya_tinggi" <?php echo ($sort == 'biaya_tinggi') ? 'selected' : ''; ?>>Biaya Tertinggi</option>
                                    <option value="biaya_rendah" <?php echo ($sort == 'biaya_rendah') ? 'selected' : ''; ?>>Biaya Terendah</option>
                                </select>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search"></i> Terapkan Filter
                            </button>
                            <a href="pekerjaan_tersedia.php" class="btn btn-outline-secondary w-100 mt-2">
                                <i class="fas fa-redo"></i> Reset Filter
                            </a>
                        </form>
                        
                        <hr>
                        
                        <div class="mt-3">
                            <h6><i class="fas fa-info-circle"></i> Statistik</h6>
                            <div class="small">
                                <div class="d-flex justify-content-between">
                                    <span>Total Pekerjaan:</span>
                                    <span class="fw-bold"><?php echo $total; ?></span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>Halaman:</span>
                                    <span class="fw-bold"><?php echo $page; ?> / <?php echo $total_pages; ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Daftar Pekerjaan -->
            <div class="col-lg-9">
                <div class="card">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0"><i class="fas fa-briefcase"></i> Semua Pekerjaan Tersedia</h5>
                            <p class="mb-0 small">Temukan pekerjaan yang sesuai dengan keahlian Anda</p>
                        </div>
                        <span class="badge bg-light text-dark">
                            <?php echo $total; ?> Pekerjaan
                        </span>
                    </div>
                    
                    <div class="card-body">
                        <?php if ($pekerjaan): ?>
                            <?php foreach ($pekerjaan as $p): 
                                // Cek apakah sudah apply
                                $stmt_check = $pdo->prepare("SELECT * FROM aplikasi_pekerjaan 
                                                           WHERE id_pekerjaan = ? AND id_kuli = ?");
                                $stmt_check->execute([$p['id'], $user_id]);
                                $sudah_apply = $stmt_check->fetch();
                            ?>
                                <div class="card mb-3 card-hover job-card">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                <h5 class="mb-2">
                                                    <?php echo htmlspecialchars($p['judul']); ?>
                                                    <?php 
                                                    // Tandai pekerjaan baru (kurang dari 24 jam)
                                                    $created_time = strtotime($p['created_at']);
                                                    $now = time();
                                                    if (($now - $created_time) < 86400): // 24 jam
                                                    ?>
                                                        <span class="badge bg-danger badge-new ms-2">BARU!</span>
                                                    <?php endif; ?>
                                                </h5>
                                                
                                                <div class="row mb-2">
                                                    <div class="col-md-4">
                                                        <small class="text-muted">
                                                            <i class="fas fa-tag"></i> 
                                                            <span class="badge bg-secondary"><?php echo $p['nama_kategori']; ?></span>
                                                        </small>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <small class="text-muted">
                                                            <i class="fas fa-user"></i> <?php echo $p['pemilik']; ?>
                                                        </small>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <small class="text-muted">
                                                            <i class="fas fa-map-marker-alt"></i> <?php echo $p['lokasi']; ?>
                                                        </small>
                                                    </div>
                                                </div>
                                                
                                                <p class="mb-3">
                                                    <?php echo htmlspecialchars(substr($p['deskripsi'], 0, 200)); ?>...
                                                </p>
                                                
                                                <div class="row">
                                                    <div class="col-md-3">
                                                        <div class="text-center p-2 border rounded">
                                                            <small class="text-muted d-block">Biaya</small>
                                                            <strong class="text-success">
                                                                Rp <?php echo number_format($p['total_biaya'], 0, ',', '.'); ?>
                                                            </strong>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div class="text-center p-2 border rounded">
                                                            <small class="text-muted d-block">Luas</small>
                                                            <strong>
                                                                <?php 
                                                                if (!empty($p['luas'])) {
                                                                    echo $p['luas'];
                                                                } elseif (!empty($p['panjang']) && !empty($p['lebar'])) {
                                                                    $luas = $p['panjang'] * $p['lebar'];
                                                                    echo number_format($luas, 2);
                                                                } else {
                                                                    echo '-';
                                                                }
                                                                ?> m²
                                                            </strong>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div class="text-center p-2 border rounded">
                                                            <small class="text-muted d-block">Tanggal Upload</small>
                                                            <strong><?php echo date('d/m/Y', strtotime($p['created_at'])); ?></strong>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div class="text-center p-2 border rounded">
                                                            <small class="text-muted d-block">Pemohon</small>
                                                            <strong><?php echo $p['jumlah_aplikasi']; ?> Kuli</strong>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="mt-3 d-flex justify-content-between align-items-center">
                                            <?php if ($sudah_apply): 
                                                $status = $sudah_apply['status'];
                                                $badge_color = ($status == 'menunggu') ? 'warning' : 
                                                              (($status == 'diterima') ? 'success' : 'secondary');
                                            ?>
                                                <div class="alert alert-<?php echo $badge_color; ?> mb-0 py-1">
                                                    <i class="fas fa-info-circle"></i> 
                                                    Anda sudah mengajukan lamaran (Status: <?php echo ucfirst($status); ?>)
                                                </div>
                                            <?php else: ?>
                                                <div>
                                                    <a href="detail_kuli.php?id=<?php echo $p['id']; ?>" 
                                                       class="btn btn-outline-primary btn-sm">
                                                        <i class="fas fa-eye"></i> Lihat Detail
                                                    </a>
                                                </div>
                                                <div>
                                                    <button type="button" class="btn btn-primary btn-sm" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#applyModal<?php echo $p['id']; ?>">
                                                        <i class="fas fa-paper-plane"></i> Apply Sekarang
                                                    </button>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Modal Apply -->
                                <?php if (!$sudah_apply): ?>
                                <div class="modal fade" id="applyModal<?php echo $p['id']; ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Ajukan Lamaran</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form action="apply_pekerjaan.php" method="POST">
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <h6><?php echo htmlspecialchars($p['judul']); ?></h6>
                                                        <div class="row small text-muted">
                                                            <div class="col-6">
                                                                <i class="fas fa-user"></i> <?php echo $p['pemilik']; ?>
                                                            </div>
                                                            <div class="col-6">
                                                                <i class="fas fa-map-marker-alt"></i> <?php echo $p['lokasi']; ?>
                                                            </div>
                                                            <div class="col-6">
                                                                <i class="fas fa-money-bill-wave"></i> Rp <?php echo number_format($p['total_biaya'], 0, ',', '.'); ?>
                                                            </div>
                                                            <div class="col-6">
                                                                <i class="fas fa-users"></i> <?php echo $p['jumlah_aplikasi']; ?> pemohon
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <label for="pesan<?php echo $p['id']; ?>" class="form-label">
                                                            <i class="fas fa-comment"></i> Pesan Anda
                                                        </label>
                                                        <textarea class="form-control" id="pesan<?php echo $p['id']; ?>" 
                                                                  name="pesan" rows="4" 
                                                                  placeholder="Tulis pesan singkat tentang pengalaman dan alasan Anda cocok untuk pekerjaan ini..."></textarea>
                                                        <small class="text-muted">Pesan yang baik akan meningkatkan peluang Anda diterima</small>
                                                    </div>
                                                    
                                                    <div class="alert alert-info">
                                                        <i class="fas fa-info-circle"></i> 
                                                        Lamaran akan ditinjau oleh pemilik pekerjaan. Anda akan mendapatkan notifikasi saat ada update.
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <input type="hidden" name="pekerjaan_id" value="<?php echo $p['id']; ?>">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                    <button type="submit" class="btn btn-primary">
                                                        <i class="fas fa-paper-plane"></i> Kirim Lamaran
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                            <?php endforeach; ?>
                            
                            <!-- Pagination -->
                            <?php if ($total_pages > 1): ?>
                            <nav aria-label="Page navigation" class="mt-4">
                                <ul class="pagination justify-content-center">
                                    <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $page-1; ?>&kategori=<?php echo $kategori; ?>&lokasi=<?php echo urlencode($lokasi); ?>&sort=<?php echo $sort; ?>">
                                            <i class="fas fa-chevron-left"></i> Sebelumnya
                                        </a>
                                    </li>
                                    
                                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                        <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?>&kategori=<?php echo $kategori; ?>&lokasi=<?php echo urlencode($lokasi); ?>&sort=<?php echo $sort; ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $page+1; ?>&kategori=<?php echo $kategori; ?>&lokasi=<?php echo urlencode($lokasi); ?>&sort=<?php echo $sort; ?>">
                                            Selanjutnya <i class="fas fa-chevron-right"></i>
                                        </a>
                                    </li>
                                </ul>
                            </nav>
                            <?php endif; ?>
                            
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fas fa-search fa-4x text-muted mb-3"></i>
                                <h4 class="text-muted">Tidak ada pekerjaan ditemukan</h4>
                                <p class="text-muted">Coba ubah filter pencarian atau coba lagi nanti.</p>
                                <a href="pekerjaan_tersedia.php" class="btn btn-primary">
                                    <i class="fas fa-redo"></i> Reset Pencarian
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
        // Auto close alerts
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
        
        // Highlight new jobs
        document.addEventListener('DOMContentLoaded', function() {
            const newBadges = document.querySelectorAll('.badge-new');
            newBadges.forEach(badge => {
                setInterval(() => {
                    badge.style.opacity = badge.style.opacity === '0.7' ? '1' : '0.7';
                }, 1000);
            });
        });
    </script>
</body>
</html>