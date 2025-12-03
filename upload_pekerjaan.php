<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'user') {
    header("Location: login.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $judul = $_POST['judul'];
    $deskripsi = $_POST['deskripsi'];
    $id_kategori = $_POST['id_kategori'];
    $panjang = $_POST['panjang'];
    $lebar = $_POST['lebar'];
    $lokasi = $_POST['lokasi'];
    $dp_dibayar = $_POST['dp_dibayar'];
    
    // Validasi DP minimal 30%
    // PERBAIKAN: Menggunakan harga_per_meter sesuai dengan struktur database
    $stmt = $pdo->prepare("SELECT harga_per_meter FROM kategori_pekerjaan WHERE id = ?");
    $stmt->execute([$id_kategori]);
    $kategori_data = $stmt->fetch();
    $biaya_per_m2 = $kategori_data['harga_per_meter'];
    
    $luas = $panjang * $lebar;
    $total_biaya = $luas * $biaya_per_m2;
    $dp_minimal = $total_biaya * 0.3;
    
    if ($dp_dibayar < $dp_minimal) {
        $error = "DP minimal 30% dari total biaya (Rp " . number_format($dp_minimal, 0, ',', '.') . ")";
    } else {
        // Handle file upload
        $bukti_dp = '';
        $upload_path = '';
        
        if (isset($_FILES['bukti_dp']) && $_FILES['bukti_dp']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['bukti_dp'];
            
            // Validasi file
            $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
            $max_size = 5 * 1024 * 1024; // 5MB
            
            if (!in_array($file['type'], $allowed_types)) {
                $error = "Format file tidak didukung. Hanya JPEG, JPG, PNG, dan PDF yang diizinkan.";
            } elseif ($file['size'] > $max_size) {
                $error = "Ukuran file terlalu besar. Maksimal 5MB.";
            } else {
                // Generate unique filename
                $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $bukti_dp = 'bukti_dp_' . time() . '_' . uniqid() . '.' . $file_extension;
                $upload_path = 'uploads/bukti_dp/' . $bukti_dp;
                
                // Create directory if not exists
                if (!is_dir('uploads/bukti_dp')) {
                    mkdir('uploads/bukti_dp', 0777, true);
                }
                
                if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
                    $error = "Gagal mengupload bukti transfer.";
                }
            }
        } else {
            $error = "Bukti transfer DP wajib diupload.";
        }
        
        if (empty($error)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO pekerjaan (judul, deskripsi, id_kategori, id_user, panjang, lebar, total_biaya, dp_dibayar, lokasi, bukti_dp) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$judul, $deskripsi, $id_kategori, $_SESSION['user_id'], $panjang, $lebar, $total_biaya, $dp_dibayar, $lokasi, $bukti_dp]);
                
                $success = "Pekerjaan berhasil diupload! Bukti transfer telah diterima.";
            } catch (PDOException $e) {
                $error = "Terjadi kesalahan: " . $e->getMessage();
                
                // Delete uploaded file if database error
                if (!empty($bukti_dp) && file_exists($upload_path)) {
                    unlink($upload_path);
                }
            }
        }
    }
}

// Ambil kategori pekerjaan - PERBAIKAN: menggunakan harga_per_meter
$kategori = $pdo->query("SELECT * FROM kategori_pekerjaan WHERE harga_per_meter > 0 ORDER BY nama_kategori")->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Pekerjaan - Kulimang</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        /* ... (style tetap sama) ... */
    </style>
</head>
<body>
    <!-- ... (navbar tetap sama) ... -->

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Upload Pekerjaan Baru</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>
                        
                        <!-- Informasi Rekening -->
                        <div class="bank-info">
                            <h6 class="fw-bold"><i class="bi bi-info-circle"></i> INSTRUKSI PEMBAYARAN DP</h6>
                            <p class="mb-3">Silakan transfer DP sebesar 30% ke salah satu rekening berikut:</p>
                            
                            <div class="bank-account">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong><i class="bi bi-bank"></i> Bank BSI</strong>
                                        <div>No. Rekening: <strong>7251061232</strong></div>
                                        <div>Atas Nama: <strong>Dhian Kurnia Ferdiansyah Irawan</strong></div>
                                    </div>
                                    <div class="badge bg-light text-dark">Rekomendasi</div>
                                </div>
                            </div>
                            
                            <div class="bank-account">
                                <div>
                                    <strong><i class="bi bi-wallet"></i> DANA</strong>
                                    <div>No. HP: <strong>082281400772</strong></div>
                                    <div>Atas Nama: <strong>Ferdhian</strong></div>
                                </div>
                            </div>
                            
                            <div class="mt-3">
                                <small>
                                    <i class="bi bi-exclamation-triangle"></i>
                                    Setelah transfer, upload bukti transfer pada form di bawah.
                                </small>
                            </div>
                        </div>

                        <form method="POST" id="pekerjaanForm" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="judul" class="form-label">Judul Pekerjaan</label>
                                <input type="text" class="form-control" id="judul" name="judul" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="deskripsi" class="form-label">Deskripsi Pekerjaan</label>
                                <textarea class="form-control" id="deskripsi" name="deskripsi" rows="3" required></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="id_kategori" class="form-label">Kategori Pekerjaan</label>
                                <select class="form-select" id="id_kategori" name="id_kategori" required>
                                    <option value="">Pilih Kategori</option>
                                    <?php foreach ($kategori as $k): ?>
                                        <!-- PERBAIKAN: menggunakan harga_per_meter -->
                                        <option value="<?php echo $k['id']; ?>" data-biaya="<?php echo $k['harga_per_meter']; ?>">
                                            <?php echo $k['nama_kategori']; ?> (Rp <?php echo number_format($k['harga_per_meter'], 0, ',', '.'); ?>/m²)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (empty($kategori)): ?>
                                    <div class="alert alert-warning mt-2">
                                        <small>Tidak ada kategori yang tersedia. Silakan hubungi administrator.</small>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="panjang" class="form-label">Panjang (meter)</label>
                                        <input type="number" class="form-control" id="panjang" name="panjang" step="0.01" min="0.1" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="lebar" class="form-label">Lebar (meter)</label>
                                        <input type="number" class="form-control" id="lebar" name="lebar" step="0.01" min="0.1" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="lokasi" class="form-label">Lokasi Pekerjaan</label>
                                <textarea class="form-control" id="lokasi" name="lokasi" rows="2" required></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="dp_dibayar" class="form-label">DP yang Dibayar (Rp)</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" class="form-control" id="dp_dibayar" name="dp_dibayar" min="0" required>
                                </div>
                                <div class="form-text">Minimal 30% dari total biaya</div>
                            </div>
                            
                            <!-- Upload Bukti DP -->
                            <div class="mb-3">
                                <label class="form-label">Upload Bukti Transfer DP <span class="text-danger">*</span></label>
                                <div class="upload-area" id="uploadArea">
                                    <input type="file" id="bukti_dp" name="bukti_dp" accept=".jpg,.jpeg,.png,.pdf" style="display: none;" required>
                                    <div id="uploadContent">
                                        <i class="bi bi-cloud-upload" style="font-size: 2rem;"></i>
                                        <p class="mt-2 mb-1">Klik atau drag file ke sini untuk upload</p>
                                        <p class="file-info">Format: JPG, PNG, PDF (Maks. 5MB)</p>
                                    </div>
                                    <div id="filePreview" style="display: none;">
                                        <img id="previewImage" src="#" alt="Preview" style="max-width: 200px; max-height: 200px; display: none;">
                                        <div id="fileName" class="mt-2"></div>
                                        <button type="button" class="btn btn-sm btn-outline-danger mt-2" onclick="removeFile()">Hapus File</button>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Perhitungan Otomatis -->
                            <div class="card calculation-card mb-3">
                                <div class="card-body">
                                    <h6><i class="bi bi-calculator"></i> Perhitungan Biaya</h6>
                                    <div id="perhitungan">
                                        <p class="mb-1">Luas: <span id="luas">0</span> m²</p>
                                        <p class="mb-1">Biaya per m²: Rp <span id="biaya_meter">0</span></p>
                                        <p class="mb-1">Total Biaya: Rp <span id="total_biaya">0</span></p>
                                        <p class="mb-0 fw-bold">DP Minimal (30%): Rp <span id="dp_minimal">0</span></p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-upload"></i> Upload Pekerjaan
                                </button>
                                <a href="dashboard.php" class="btn btn-secondary">Batal</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function hitungBiaya() {
            const panjang = parseFloat(document.getElementById('panjang').value) || 0;
            const lebar = parseFloat(document.getElementById('lebar').value) || 0;
            const kategoriSelect = document.getElementById('id_kategori');
            const selectedOption = kategoriSelect.options[kategoriSelect.selectedIndex];
            // PERBAIKAN: menggunakan data-biaya (harga_per_meter)
            const biayaPerMeter = selectedOption ? parseFloat(selectedOption.getAttribute('data-biaya')) || 0 : 0;
            
            const luas = panjang * lebar;
            const totalBiaya = luas * biayaPerMeter;
            const dpMinimal = totalBiaya * 0.3;
            
            document.getElementById('luas').textContent = luas.toFixed(2);
            document.getElementById('biaya_meter').textContent = biayaPerMeter.toLocaleString('id-ID');
            document.getElementById('total_biaya').textContent = totalBiaya.toLocaleString('id-ID');
            document.getElementById('dp_minimal').textContent = dpMinimal.toLocaleString('id-ID');
            
            // Set minimal value untuk DP
            document.getElementById('dp_dibayar').min = dpMinimal;
            
            // Update placeholder untuk DP
            document.getElementById('dp_dibayar').placeholder = 'Minimal: Rp ' + dpMinimal.toLocaleString('id-ID');
        }
        
        // File upload functionality
        const uploadArea = document.getElementById('uploadArea');
        const fileInput = document.getElementById('bukti_dp');
        const uploadContent = document.getElementById('uploadContent');
        const filePreview = document.getElementById('filePreview');
        const previewImage = document.getElementById('previewImage');
        const fileName = document.getElementById('fileName');
        
        uploadArea.addEventListener('click', function() {
            fileInput.click();
        });
        
        uploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });
        
        uploadArea.addEventListener('dragleave', function() {
            uploadArea.classList.remove('dragover');
        });
        
        uploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            if (e.dataTransfer.files.length) {
                fileInput.files = e.dataTransfer.files;
                handleFileSelect(e.dataTransfer.files[0]);
            }
        });
        
        fileInput.addEventListener('change', function(e) {
            if (e.target.files.length) {
                handleFileSelect(e.target.files[0]);
            }
        });
        
        function handleFileSelect(file) {
            if (file) {
                fileName.textContent = file.name;
                
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        previewImage.src = e.target.result;
                        previewImage.style.display = 'block';
                    };
                    reader.readAsDataURL(file);
                } else {
                    previewImage.style.display = 'none';
                }
                
                uploadContent.style.display = 'none';
                filePreview.style.display = 'block';
            }
        }
        
        function removeFile() {
            fileInput.value = '';
            uploadContent.style.display = 'block';
            filePreview.style.display = 'none';
            previewImage.style.display = 'none';
        }
        
        // Event listeners untuk perhitungan otomatis
        document.getElementById('panjang').addEventListener('input', hitungBiaya);
        document.getElementById('lebar').addEventListener('input', hitungBiaya);
        document.getElementById('id_kategori').addEventListener('change', hitungBiaya);
        
        // Validasi form sebelum submit
        document.getElementById('pekerjaanForm').addEventListener('submit', function(e) {
            const dpDibayar = parseFloat(document.getElementById('dp_dibayar').value) || 0;
            const dpMinimal = parseFloat(document.getElementById('dp_dibayar').min) || 0;
            
            if (dpDibayar < dpMinimal) {
                e.preventDefault();
                alert('DP yang dibayar harus minimal 30% dari total biaya (Rp ' + dpMinimal.toLocaleString('id-ID') + ')');
                document.getElementById('dp_dibayar').focus();
            }
        });
        
        // Hitung saat pertama kali load
        hitungBiaya();
    </script>
</body>
</html>