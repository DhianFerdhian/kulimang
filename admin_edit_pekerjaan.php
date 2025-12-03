<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: dashboard_admin.php");
    exit();
}

$id = $_GET['id'];
$kategori = $pdo->query("SELECT * FROM kategori_pekerjaan ORDER BY nama_kategori")->fetchAll();

$stmt = $pdo->prepare("SELECT * FROM pekerjaan WHERE id = ?");
$stmt->execute([$id]);
$pekerjaan = $stmt->fetch();

if (!$pekerjaan) {
    $_SESSION['error'] = "Pekerjaan tidak ditemukan";
    header("Location: dashboard_admin.php");
    exit();
}

// Debug: Tampilkan kolom yang ada di tabel pekerjaan
$columns = $pdo->query("SHOW COLUMNS FROM pekerjaan")->fetchAll();
// echo "<pre>"; print_r($columns); echo "</pre>";

if ($_POST) {
    try {
        // Sesuaikan dengan kolom yang ada di tabel Anda
        $stmt = $pdo->prepare("UPDATE pekerjaan SET judul=?, deskripsi=?, id_kategori=?, luas=?, total_biaya=?, lokasi=?, durasi=?, status=? WHERE id=?");
        $stmt->execute([
            $_POST['judul'],
            $_POST['deskripsi'],
            $_POST['id_kategori'],
            $_POST['luas'],
            $_POST['total_biaya'],
            $_POST['lokasi'],
            $_POST['durasi'],
            $_POST['status'],
            $id
        ]);
        
        $_SESSION['success'] = "Pekerjaan berhasil diupdate";
        header("Location: dashboard_admin.php");
        exit();
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Pekerjaan - Kulimang</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
                <a href="logout.php" class="btn btn-outline-light btn-sm">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
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

        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0"><i class="fas fa-edit"></i> Edit Pekerjaan</h4>
                    </div>
                    <div class="card-body">
                        <form method="POST" id="editPekerjaanForm">
                            <div class="mb-3">
                                <label class="form-label">Judul Pekerjaan</label>
                                <input type="text" class="form-control" name="judul" value="<?php echo htmlspecialchars($pekerjaan['judul']); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Deskripsi</label>
                                <textarea class="form-control" name="deskripsi" rows="3" required><?php echo htmlspecialchars($pekerjaan['deskripsi']); ?></textarea>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Kategori</label>
                                        <select class="form-select" name="id_kategori" required>
                                            <?php foreach ($kategori as $k): ?>
                                                <option value="<?php echo $k['id']; ?>" <?php echo $k['id'] == $pekerjaan['id_kategori'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($k['nama_kategori']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Status</label>
                                        <select class="form-select" name="status" required>
                                            <option value="menunggu" <?php echo $pekerjaan['status'] == 'menunggu' ? 'selected' : ''; ?>>Menunggu</option>
                                            <option value="diproses" <?php echo $pekerjaan['status'] == 'diproses' ? 'selected' : ''; ?>>Diproses</option>
                                            <option value="selesai" <?php echo $pekerjaan['status'] == 'selesai' ? 'selected' : ''; ?>>Selesai</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Luas (m²)</label>
                                        <input type="number" class="form-control" name="luas" step="0.01" value="<?php echo $pekerjaan['luas']; ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Total Biaya (Rp)</label>
                                        <input type="number" class="form-control" name="total_biaya" value="<?php echo $pekerjaan['total_biaya']; ?>" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Lokasi</label>
                                        <input type="text" class="form-control" name="lokasi" value="<?php echo htmlspecialchars($pekerjaan['lokasi']); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Durasi (hari)</label>
                                        <input type="number" class="form-control" name="durasi" value="<?php echo $pekerjaan['durasi']; ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex">
                                <button type="submit" class="btn btn-primary me-md-2">Update Pekerjaan</button>
                                <a href="dashboard_admin.php" class="btn btn-secondary">Batal</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>