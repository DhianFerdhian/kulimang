<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

// Handle add category
if ($_POST && isset($_POST['add_category'])) {
    $nama_kategori = trim($_POST['nama_kategori']);
    $deskripsi = trim($_POST['deskripsi']);
    $biaya_per_m2 = isset($_POST['biaya_per_m2']) ? floatval($_POST['biaya_per_m2']) : 0;
    
    if (!empty($nama_kategori)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO kategori_pekerjaan (nama_kategori, deskripsi, biaya_per_m2) VALUES (?, ?, ?)");
            $stmt->execute([$nama_kategori, $deskripsi, $biaya_per_m2]);
            
            $_SESSION['success'] = "Kategori berhasil ditambahkan";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "Nama kategori tidak boleh kosong";
    }
    
    header("Location: admin_manage_categories.php");
    exit();
}

// Handle delete category
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    
    try {
        // Check if category is used in pekerjaan
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM pekerjaan WHERE id_kategori = ?");
        $stmt->execute([$delete_id]);
        $count = $stmt->fetch()['count'];
        
        if ($count == 0) {
            $stmt = $pdo->prepare("DELETE FROM kategori_pekerjaan WHERE id = ?");
            $stmt->execute([$delete_id]);
            $_SESSION['success'] = "Kategori berhasil dihapus";
        } else {
            $_SESSION['error'] = "Tidak dapat menghapus kategori yang sedang digunakan";
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
    
    header("Location: admin_manage_categories.php");
    exit();
}

// Handle update category
if ($_POST && isset($_POST['update_category'])) {
    $id = $_POST['id'];
    $nama_kategori = trim($_POST['nama_kategori']);
    $deskripsi = trim($_POST['deskripsi']);
    $biaya_per_m2 = isset($_POST['biaya_per_m2']) ? floatval($_POST['biaya_per_m2']) : 0;
    
    if (!empty($nama_kategori)) {
        try {
            $stmt = $pdo->prepare("UPDATE kategori_pekerjaan SET nama_kategori = ?, deskripsi = ?, biaya_per_m2 = ? WHERE id = ?");
            $stmt->execute([$nama_kategori, $deskripsi, $biaya_per_m2, $id]);
            
            $_SESSION['success'] = "Kategori berhasil diupdate";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "Nama kategori tidak boleh kosong";
    }
    
    header("Location: admin_manage_categories.php");
    exit();
}

// First, let's check if the biaya_per_m2 column exists, if not, create it
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM kategori_pekerjaan LIKE 'biaya_per_m2'");
    $column_exists = $stmt->fetch();
    
    if (!$column_exists) {
        // Add the column if it doesn't exist
        $pdo->exec("ALTER TABLE kategori_pekerjaan ADD COLUMN biaya_per_m2 DECIMAL(10,2) DEFAULT 0");
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "Error checking/adding column: " . $e->getMessage();
}

// Get all categories with COALESCE to handle missing biaya_per_m2
$stmt = $pdo->query("SELECT k.*, COUNT(p.id) as jumlah_pekerjaan 
                     FROM kategori_pekerjaan k 
                     LEFT JOIN pekerjaan p ON k.id = p.id_kategori 
                     GROUP BY k.id 
                     ORDER BY k.nama_kategori");
$categories = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Kategori - Kulimang</title>
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

        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-plus"></i> Tambah Kategori Baru</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" id="addCategoryForm">
                            <div class="mb-3">
                                <label class="form-label">Nama Kategori <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="nama_kategori" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Deskripsi</label>
                                <textarea class="form-control" name="deskripsi" rows="3"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Biaya per m² (Rp)</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" class="form-control" name="biaya_per_m2" min="0" step="1000" value="0" required>
                                </div>
                                <div class="form-text">
                                    Isi 0 jika kategori ini tidak menggunakan perhitungan per m²
                                </div>
                            </div>
                            <button type="submit" name="add_category" value="1" class="btn btn-primary w-100">
                                <i class="fas fa-save"></i> Simpan Kategori
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-tags"></i> Daftar Kategori</h5>
                        <span class="badge bg-primary">Total: <?php echo count($categories); ?> Kategori</span>
                    </div>
                    <div class="card-body">
                        <?php if ($categories): ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Nama Kategori</th>
                                            <th>Deskripsi</th>
                                            <th>Biaya per m²</th>
                                            <th>Jumlah Pekerjaan</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($categories as $category): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($category['nama_kategori']); ?></strong>
                                                </td>
                                                <td><?php echo htmlspecialchars($category['deskripsi'] ?? ''); ?></td>
                                                <td>
                                                    <?php 
                                                    // Safe way to get biaya_per_m2 with fallback
                                                    $biaya = $category['biaya_per_m2'] ?? 0;
                                                    if ($biaya > 0): ?>
                                                        <span class="badge bg-success">Rp <?php echo number_format($biaya, 0, ',', '.'); ?></span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">Tidak tersedia</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge bg-info"><?php echo $category['jumlah_pekerjaan']; ?> pekerjaan</span>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <button type="button" class="btn btn-outline-warning" 
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#editCategoryModal"
                                                                data-id="<?php echo $category['id']; ?>"
                                                                data-nama="<?php echo htmlspecialchars($category['nama_kategori']); ?>"
                                                                data-deskripsi="<?php echo htmlspecialchars($category['deskripsi'] ?? ''); ?>"
                                                                data-biaya="<?php echo $category['biaya_per_m2'] ?? 0; ?>">
                                                            <i class="fas fa-edit"></i> Edit
                                                        </button>
                                                        <a href="admin_manage_categories.php?delete_id=<?php echo $category['id']; ?>" 
                                                           class="btn btn-outline-danger" 
                                                           onclick="return confirmDeleteCategory('<?php echo htmlspecialchars($category['nama_kategori']); ?>', <?php echo $category['jumlah_pekerjaan']; ?>)">
                                                            <i class="fas fa-trash"></i> Hapus
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
                                <i class="fas fa-tags fa-3x text-muted mb-3"></i>
                                <p class="text-muted">Belum ada kategori.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Category Modal -->
    <div class="modal fade" id="editCategoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Kategori</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="editCategoryForm">
                    <div class="modal-body">
                        <input type="hidden" name="id" id="editCategoryId">
                        <div class="mb-3">
                            <label class="form-label">Nama Kategori <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="nama_kategori" id="editCategoryNama" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Deskripsi</label>
                            <textarea class="form-control" name="deskripsi" id="editCategoryDeskripsi" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Biaya per m² (Rp)</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" class="form-control" name="biaya_per_m2" id="editCategoryBiaya" min="0" step="1000" required>
                            </div>
                            <div class="form-text">
                                Isi 0 jika kategori ini tidak menggunakan perhitungan per m²
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="update_category" value="1" class="btn btn-primary">Update Kategori</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Edit Category Modal
        const editCategoryModal = document.getElementById('editCategoryModal');
        if (editCategoryModal) {
            editCategoryModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                const id = button.getAttribute('data-id');
                const nama = button.getAttribute('data-nama');
                const deskripsi = button.getAttribute('data-deskripsi');
                const biaya = button.getAttribute('data-biaya');
                
                document.getElementById('editCategoryId').value = id;
                document.getElementById('editCategoryNama').value = nama;
                document.getElementById('editCategoryDeskripsi').value = deskripsi;
                document.getElementById('editCategoryBiaya').value = biaya;
            });
        }

        function confirmDeleteCategory(nama, jumlahPekerjaan) {
            if (jumlahPekerjaan > 0) {
                alert(`Tidak dapat menghapus kategori "${nama}" karena sedang digunakan oleh ${jumlahPekerjaan} pekerjaan.`);
                return false;
            }
            return confirm(`Apakah Anda yakin ingin menghapus kategori "${nama}"?`);
        }

        // Auto hide alerts
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);

        // Form validation
        document.getElementById('addCategoryForm').addEventListener('submit', function(e) {
            const namaInput = this.querySelector('input[name="nama_kategori"]');
            const biayaInput = this.querySelector('input[name="biaya_per_m2"]');
            
            if (namaInput.value.trim() === '') {
                e.preventDefault();
                alert('Nama kategori tidak boleh kosong');
                namaInput.focus();
                return;
            }
            
            if (biayaInput.value === '' || parseFloat(biayaInput.value) < 0) {
                e.preventDefault();
                alert('Biaya per m² harus diisi dengan angka yang valid');
                biayaInput.focus();
            }
        });

        document.getElementById('editCategoryForm').addEventListener('submit', function(e) {
            const namaInput = this.querySelector('input[name="nama_kategori"]');
            const biayaInput = this.querySelector('input[name="biaya_per_m2"]');
            
            if (namaInput.value.trim() === '') {
                e.preventDefault();
                alert('Nama kategori tidak boleh kosong');
                namaInput.focus();
                return;
            }
            
            if (biayaInput.value === '' || parseFloat(biayaInput.value) < 0) {
                e.preventDefault();
                alert('Biaya per m² harus diisi dengan angka yang valid');
                biayaInput.focus();
            }
        });
    </script>
</body>
</html>