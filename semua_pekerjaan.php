<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'kuli') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Filter handling
$filter_kategori = isset($_GET['kategori']) ? $_GET['kategori'] : '';
$filter_lokasi = isset($_GET['lokasi']) ? $_GET['lokasi'] : '';
$filter_biaya_min = isset($_GET['biaya_min']) ? $_GET['biaya_min'] : '';
$filter_biaya_max = isset($_GET['biaya_max']) ? $_GET['biaya_max'] : '';

// Build query
$query = "SELECT p.*, k.nama_kategori, u.nama_lengkap as pemilik 
          FROM pekerjaan p 
          JOIN kategori_pekerjaan k ON p.id_kategori = k.id 
          JOIN users u ON p.id_user = u.id 
          WHERE p.status = 'menunggu' 
          AND p.id NOT IN (SELECT id_pekerjaan FROM aplikasi_pekerjaan WHERE id_kuli = ?)";

$params = [$user_id];

if ($filter_kategori) {
    $query .= " AND p.id_kategori = ?";
    $params[] = $filter_kategori;
}

if ($filter_lokasi) {
    $query .= " AND p.lokasi LIKE ?";
    $params[] = "%$filter_lokasi%";
}

if ($filter_biaya_min) {
    $query .= " AND p.total_biaya >= ?";
    $params[] = $filter_biaya_min;
}

if ($filter_biaya_max) {
    $query .= " AND p.total_biaya <= ?";
    $params[] = $filter_biaya_max;
}

$query .= " ORDER BY p.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$pekerjaan = $stmt->fetchAll();

// Get all categories for filter
$stmt = $pdo->query("SELECT * FROM kategori_pekerjaan ORDER BY nama_kategori");
$kategori = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Semua Pekerjaan - Kulimang</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .job-card {
            transition: all 0.3s ease;
        }
        .job-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        .filter-card {
            position: sticky;
            top: 20px;
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
                <a href="dashboard_kuli.php" class="nav-link text-white"><i class="fas fa-home"></i> Dashboard</a>
                <a href="aplikasi_saya.php" class="nav-link text-white"><i class="fas fa-history"></i> Aplikasi Saya</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-3">
                <!-- Filter -->
                <div class="card filter-card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-filter"></i> Filter Pekerjaan</h5>
                    </div>
                    <div class="card-body">
                        <form method="GET" action="">
                            <div class="mb-3">
                                <label class="form-label">Kategori</label>
                                <select name="kategori" class="form-select">
                                    <option value="">Semua Kategori</option>
                                    <?php foreach ($kategori as $k): ?>
                                    <option value="<?php echo $k['id']; ?>" <?php echo $filter_kategori == $k['id'] ? 'selected' : ''; ?>>
                                        <?php echo $k['nama_kategori']; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Lokasi</label>
                                <input type="text" name="lokasi" class="form-control" 
                                       placeholder="Cari lokasi..." value="<?php echo htmlspecialchars($filter_lokasi); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Rentang Biaya</label>
                                <div class="row">
                                    <div class="col">
                                        <input type="number" name="biaya_min" class="form-control" 
                                               placeholder="Min" value="<?php echo htmlspecialchars($filter_biaya_min); ?>">
                                    </div>
                                    <div class="col">
                                        <input type="number" name="biaya_max" class="form-control" 
                                               placeholder="Max" value="<?php echo htmlspecialchars($filter_biaya_max); ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Terapkan Filter
                                </button>
                                <a href="semua_pekerjaan.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-redo"></i> Reset Filter
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-9">
                <!-- Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h3>Semua Pekerjaan Tersedia</h3>
                        <p class="text-muted">Temukan pekerjaan yang sesuai dengan keahlian Anda</p>
                    </div>
                    <div>
                        <a href="dashboard_kuli.php" class="btn btn-outline-primary">
                            <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
                        </a>
                    </div>
                </div>

                <!-- Job List -->
                <?php if ($pekerjaan): ?>
                    <div class="row">
                        <?php foreach ($pekerjaan as $p): ?>
                        <div class="col-md-6 mb-4">
                            <div class="card job-card h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <h5><?php echo htmlspecialchars($p['judul']); ?></h5>
                                            <span class="badge bg-primary"><?php echo $p['nama_kategori']; ?></span>
                                        </div>
                                        <span class="badge bg-warning">Rp <?php echo number_format($p['total_biaya'], 0, ',', '.'); ?></span>
                                    </div>
                                    
                                    <p class="text-muted small mb-3"><?php echo htmlspecialchars(substr($p['deskripsi'], 0, 150)); ?>...</p>
                                    
                                    <div class="row mb-3">
                                        <div class="col-6">
                                            <small><i class="fas fa-user-tie"></i> <?php echo $p['pemilik']; ?></small>
                                        </div>
                                        <div class="col-6">
                                            <small><i class="fas fa-calendar"></i> <?php echo date('d/m/Y', strtotime($p['tanggal_mulai'])); ?></small>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <small><i class="fas fa-map-marker-alt text-danger"></i> <?php echo $p['lokasi']; ?></small>
                                    </div>
                                    
                                    <div class="d-grid gap-2">
                                        <a href="detail_pekerjaan.php?id=<?php echo $p['id']; ?>" class="btn btn-outline-primary btn-sm">
                                            <i class="fas fa-eye"></i> Lihat Detail
                                        </a>
                                        <a href="apply_pekerjaan.php?id=<?php echo $p['id']; ?>" class="btn btn-primary btn-sm" 
                                           onclick="return confirm('Yakin ingin melamar pekerjaan ini?')">
                                            <i class="fas fa-paper-plane"></i> Lamar Sekarang
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Pagination (optional) -->
                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <div class="text-muted">
                            Menampilkan <?php echo count($pekerjaan); ?> pekerjaan
                        </div>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-briefcase fa-4x text-muted mb-3"></i>
                        <h4 class="text-muted">Tidak ada pekerjaan tersedia</h4>
                        <p class="text-muted">Saat ini tidak ada pekerjaan yang sesuai dengan filter Anda</p>
                        <a href="semua_pekerjaan.php" class="btn btn-primary">
                            <i class="fas fa-redo"></i> Reset Filter
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>