<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

// Ambil kategori untuk dropdown
$kategori = $pdo->query("SELECT * FROM kategori_pekerjaan ORDER BY nama_kategori")->fetchAll();

// Ambil users untuk dropdown
$users = $pdo->query("SELECT id, nama_lengkap FROM users WHERE role = 'user' ORDER BY nama_lengkap")->fetchAll();

if ($_POST) {
    try {
        $id_user = $_POST['id_user'] ?: $_SESSION['user_id'];
        
        $stmt = $pdo->prepare("INSERT INTO pekerjaan (judul, deskripsi, id_kategori, luas, total_biaya, lokasi, tanggal_mulai, durasi, catatan, id_user, status) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $_POST['judul'],
            $_POST['deskripsi'],
            $_POST['id_kategori'],
            $_POST['luas'],
            $_POST['total_biaya'],
            $_POST['lokasi'],
            $_POST['tanggal_mulai'],
            $_POST['durasi'],
            $_POST['catatan'],
            $id_user,
            $_POST['status']
        ]);
        
        $_SESSION['success'] = "Pekerjaan berhasil ditambahkan";
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
    <title>Tambah Pekerjaan - Kulimang</title>
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

        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0"><i class="fas fa-plus"></i> Tambah Pekerjaan Baru</h4>
                    </div>
                    <div class="card-body">
                        <form method="POST" id="pekerjaanForm">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Judul Pekerjaan <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="judul" required id="judul">
                                        <div class="form-text">Masukkan judul pekerjaan yang jelas dan deskriptif</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Pemilik Pekerjaan</label>
                                        <select class="form-select" name="id_user">
                                            <option value="">-- Pilih User --</option>
                                            <?php foreach ($users as $user): ?>
                                                <option value="<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['nama_lengkap']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="form-text">Kosongkan jika pekerjaan untuk sistem</div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Deskripsi <span class="text-danger">*</span></label>
                                <textarea class="form-control" name="deskripsi" rows="3" required id="deskripsi"></textarea>
                                <div class="form-text">Jelaskan detail pekerjaan yang perlu dilakukan</div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Kategori <span class="text-danger">*</span></label>
                                        <select class="form-select" name="id_kategori" required id="kategori">
                                            <option value="">Pilih Kategori</option>
                                            <?php foreach ($kategori as $k): ?>
                                                <option value="<?php echo $k['id']; ?>"><?php echo htmlspecialchars($k['nama_kategori']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Status <span class="text-danger">*</span></label>
                                        <select class="form-select" name="status" required>
                                            <option value="menunggu">Menunggu</option>
                                            <option value="diproses">Diproses</option>
                                            <option value="selesai">Selesai</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Luas (m²) <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" name="luas" step="0.01" required id="luas" min="0">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Total Biaya (Rp) <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" name="total_biaya" required id="biaya" min="0">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Lokasi <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="lokasi" required id="lokasi">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Tanggal Mulai <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control" name="tanggal_mulai" required id="tanggal_mulai">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Durasi (hari) <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" name="durasi" required id="durasi" min="1">
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Catatan Tambahan</label>
                                <textarea class="form-control" name="catatan" rows="2" id="catatan"></textarea>
                                <div class="form-text">Masukkan catatan khusus jika ada</div>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="dashboard_admin.php" class="btn btn-secondary me-md-2">Batal</a>
                                <button type="submit" class="btn btn-primary" id="submitBtn">
                                    <i class="fas fa-save"></i> Simpan Pekerjaan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('pekerjaanForm');
            const submitBtn = document.getElementById('submitBtn');

            // Set minimum date to today
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('tanggal_mulai').min = today;

            form.addEventListener('submit', function(e) {
                let isValid = true;
                const requiredFields = form.querySelectorAll('[required]');
                
                // Reset previous validations
                requiredFields.forEach(field => {
                    field.classList.remove('is-invalid');
                });

                // Validate required fields
                requiredFields.forEach(field => {
                    if (!field.value.trim()) {
                        field.classList.add('is-invalid');
                        isValid = false;
                    }
                });

                // Validate numbers
                const luas = document.getElementById('luas');
                const biaya = document.getElementById('biaya');
                const durasi = document.getElementById('durasi');

                if (luas.value <= 0) {
                    luas.classList.add('is-invalid');
                    isValid = false;
                }

                if (biaya.value <= 0) {
                    biaya.classList.add('is-invalid');
                    isValid = false;
                }

                if (durasi.value <= 0) {
                    durasi.classList.add('is-invalid');
                    isValid = false;
                }

                if (!isValid) {
                    e.preventDefault();
                    showNotification('Harap isi semua field yang wajib diisi dengan benar!', 'error');
                    // Scroll to first error
                    const firstError = form.querySelector('.is-invalid');
                    if (firstError) {
                        firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        firstError.focus();
                    }
                } else {
                    // Show loading state
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';
                    submitBtn.disabled = true;
                }
            });

            // Format biaya input
            document.getElementById('biaya').addEventListener('input', function(e) {
                const value = e.target.value.replace(/\D/g, '');
                e.target.value = value;
            });

            // Auto hide alerts
            setTimeout(function() {
                const alerts = document.querySelectorAll('.alert');
                alerts.forEach(alert => {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                });
            }, 5000);
        });

        function showNotification(message, type) {
            // Create notification element
            const notification = document.createElement('div');
            notification.className = `alert alert-${type === 'error' ? 'danger' : 'success'} alert-dismissible fade show`;
            notification.innerHTML = `
                <i class="fas fa-${type === 'error' ? 'exclamation-triangle' : 'check-circle'}"></i> 
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            // Insert at the top of container
            const container = document.querySelector('.container');
            container.insertBefore(notification, container.firstChild);
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 5000);
        }
    </script>

    <style>
        .is-invalid {
            border-color: #dc3545;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23dc3545'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath d='m5.8 3.6.4.4.4-.4'/%3e%3cpath d='M6 7v1'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right calc(0.375em + 0.1875rem) center;
            background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
        }
    </style>
</body>
</html>