<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
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

// Ambil data pekerjaan
$stmt = $pdo->prepare("SELECT p.*, k.nama_kategori 
                      FROM pekerjaan p 
                      JOIN kategori_pekerjaan k ON p.id_kategori = k.id 
                      WHERE p.id = ? AND p.id_user = ?");
$stmt->execute([$pekerjaan_id, $user_id]);
$pekerjaan = $stmt->fetch();

if (!$pekerjaan) {
    $_SESSION['error'] = "Pekerjaan tidak ditemukan";
    header("Location: dashboard_user.php");
    exit();
}

// Pastikan status adalah 'selesai' (dari kuli)
if ($pekerjaan['status'] != 'selesai') {
    $_SESSION['error'] = "Pekerjaan belum diselesaikan oleh pekerja. Status saat ini: " . $pekerjaan['status'];
    header("Location: detail_pekerjaan_user.php?id=" . $pekerjaan_id);
    exit();
}

// Ambil data kuli yang mengerjakan dan foto bukti
$stmt = $pdo->prepare("SELECT u.id, u.nama_lengkap, u.email, ap.foto_bukti, ap.catatan_penyelesaian 
                      FROM aplikasi_pekerjaan ap 
                      JOIN users u ON ap.id_kuli = u.id 
                      WHERE ap.id_pekerjaan = ? AND ap.status = 'selesai'");
$stmt->execute([$pekerjaan_id]);
$kuli = $stmt->fetch();

if (!$kuli) {
    $_SESSION['error'] = "Data pekerja tidak ditemukan";
    header("Location: detail_pekerjaan_user.php?id=" . $pekerjaan_id);
    exit();
}

// Cek apakah foto bukti ada
if (empty($kuli['foto_bukti'])) {
    $_SESSION['error'] = "Pekerja belum mengupload foto bukti penyelesaian";
    header("Location: detail_pekerjaan_user.php?id=" . $pekerjaan_id);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['konfirmasi_final'])) {
    
    try {
        $pdo->beginTransaction();
        
        // Update status pekerjaan menjadi 'selesai' (final - sudah dikonfirmasi user)
        // Note: Kita tetap menggunakan 'selesai' karena enum tidak support status baru
        $stmt = $pdo->prepare("UPDATE pekerjaan 
                             SET status = 'selesai', 
                                 tanggal_selesai = NOW(), 
                                 updated_at = NOW() 
                             WHERE id = ?");
        $stmt->execute([$pekerjaan_id]);

        // Update aplikasi_pekerjaan - tetap 'selesai' karena sudah final
        $stmt = $pdo->prepare("UPDATE aplikasi_pekerjaan 
                             SET updated_at = NOW() 
                             WHERE id_pekerjaan = ? AND status = 'selesai'");
        $stmt->execute([$pekerjaan_id]);
        
        // Jika ada rating, simpan rating
        if (!empty($_POST['rating']) && $kuli) {
            // Cek apakah sudah ada rating untuk pekerjaan ini
            $stmt = $pdo->prepare("SELECT id FROM rating_pekerjaan WHERE id_pekerjaan = ?");
            $stmt->execute([$pekerjaan_id]);
            
            if ($stmt->fetch()) {
                // Update rating yang sudah ada
                $stmt = $pdo->prepare("UPDATE rating_pekerjaan 
                                     SET rating = ?, ulasan = ?, updated_at = NOW() 
                                     WHERE id_pekerjaan = ?");
            } else {
                // Insert rating baru
                $stmt = $pdo->prepare("INSERT INTO rating_pekerjaan 
                                     (id_pekerjaan, id_kuli, rating, ulasan, created_at) 
                                     VALUES (?, ?, ?, ?, NOW())");
            }
            
            $stmt->execute([
                $pekerjaan_id,
                $kuli['id'],
                $_POST['rating'],
                $_POST['ulasan'] ?? ''
            ]);
        }
        
        $pdo->commit();
        
        $_SESSION['success'] = "Pekerjaan telah dikonfirmasi selesai! Terima kasih atas konfirmasinya.";
        header("Location: detail_pekerjaan_user.php?id=" . $pekerjaan_id);
        exit();
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Error sistem: " . $e->getMessage();
        header("Location: konfirmasi_selesai_user.php?id=" . $pekerjaan_id);
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Konfirmasi Penyelesaian - Kulimang</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .verification-card {
            border-left: 4px solid #28a745;
            background: #f8fff9;
        }
        .star-rating {
            font-size: 24px;
            color: #ffc107;
        }
        .bukti-image {
            max-width: 100%;
            max-height: 400px;
            border: 2px solid #dee2e6;
            border-radius: 8px;
            padding: 5px;
            background: white;
        }
        .bukti-container {
            text-align: center;
            margin: 15px 0;
        }
        .progress-preview {
            height: 20px;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="dashboard_user.php">
                <i class="fas fa-hammer"></i> KULIMANG
            </a>
            <div class="navbar-nav ms-auto">
                <a href="detail_pekerjaan_user.php?id=<?php echo $pekerjaan_id; ?>" class="btn btn-outline-light btn-sm">
                    <i class="fas fa-arrow-left"></i> Kembali
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Notifikasi -->
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h4 class="mb-0"><i class="fas fa-check-circle"></i> Konfirmasi Penyelesaian Pekerjaan</h4>
                    </div>
                    <div class="card-body">
                        <!-- Informasi Pekerjaan -->
                        <div class="alert alert-info">
                            <h5><i class="fas fa-info-circle"></i> Informasi Pekerjaan</h5>
                            <p><strong>Judul:</strong> <?php echo htmlspecialchars($pekerjaan['judul']); ?></p>
                            <p><strong>Pekerja:</strong> <?php echo htmlspecialchars($kuli['nama_lengkap']); ?></p>
                            <p><strong>Email Pekerja:</strong> <?php echo htmlspecialchars($kuli['email']); ?></p>
                            <p><strong>Status Saat Ini:</strong> <span class="badge bg-warning">Menunggu Konfirmasi Anda</span></p>
                        </div>

                        <!-- Foto Bukti dari Kuli -->
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h5 class="mb-0"><i class="fas fa-camera"></i> Foto Bukti Penyelesaian dari Pekerja</h5>
                            </div>
                            <div class="card-body">
                                <div class="bukti-container">
                                    <?php if (!empty($kuli['foto_bukti']) && file_exists($kuli['foto_bukti'])): ?>
                                        <img src="<?php echo $kuli['foto_bukti']; ?>" 
                                             alt="Foto Bukti Penyelesaian" 
                                             class="bukti-image mb-3"
                                             onclick="openModal('<?php echo $kuli['foto_bukti']; ?>')"
                                             style="cursor: pointer;">
                                        <br>
                                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="openModal('<?php echo $kuli['foto_bukti']; ?>')">
                                            <i class="fas fa-expand"></i> Lihat Full Size
                                        </button>
                                    <?php else: ?>
                                        <div class="alert alert-warning">
                                            <i class="fas fa-exclamation-triangle"></i> 
                                            Foto bukti tidak tersedia atau file tidak ditemukan.
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if (!empty($kuli['catatan_penyelesaian'])): ?>
                                    <div class="mt-3">
                                        <strong><i class="fas fa-sticky-note"></i> Catatan dari Pekerja:</strong>
                                        <p class="mt-2 p-3 bg-light rounded"><?php echo nl2br(htmlspecialchars($kuli['catatan_penyelesaian'])); ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <form method="POST" id="formKonfirmasi">
                            <!-- Verifikasi -->
                            <div class="verification-card p-3 mb-4">
                                <h5><i class="fas fa-clipboard-check text-success"></i> Verifikasi Pekerjaan</h5>
                                <p class="text-muted">Berdasarkan foto bukti dan hasil pekerjaan, pastikan:</p>
                                
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="verify1" required>
                                    <label class="form-check-label" for="verify1">
                                        <strong>Kualitas Pekerjaan:</strong> Pekerjaan telah sesuai dengan standar yang diharapkan
                                    </label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="verify2" required>
                                    <label class="form-check-label" for="verify2">
                                        <strong>Kelengkapan:</strong> Semua pekerjaan telah diselesaikan sesuai deskripsi
                                    </label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="verify3" required>
                                    <label class="form-check-label" for="verify3">
                                        <strong>Foto Bukti Valid:</strong> Foto menunjukkan pekerjaan telah selesai dengan baik
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="verify4" required>
                                    <label class="form-check-label" for="verify4">
                                        <strong>Kepuasan:</strong> Saya puas dengan hasil pekerjaan yang dilakukan
                                    </label>
                                </div>
                            </div>

                            <!-- Rating -->
                            <div class="mb-4">
                                <label class="form-label"><strong><i class="fas fa-star"></i> Beri Rating untuk Pekerja (Opsional):</strong></label>
                                <div class="mb-2">
                                    <select class="form-select" id="rating" name="rating" onchange="showStars(this.value)">
                                        <option value="">Pilih rating</option>
                                        <option value="5">⭐️⭐️⭐️⭐️⭐️ - Sangat Memuaskan</option>
                                        <option value="4">⭐️⭐️⭐️⭐️ - Memuaskan</option>
                                        <option value="3">⭐️⭐️⭐️ - Cukup</option>
                                        <option value="2">⭐️⭐️ - Kurang</option>
                                        <option value="1">⭐️ - Sangat Kurang</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Ulasan -->
                            <div class="mb-4">
                                <label for="ulasan" class="form-label"><strong><i class="fas fa-comment"></i> Ulasan untuk Pekerja (Opsional):</strong></label>
                                <textarea class="form-control" id="ulasan" name="ulasan" rows="4" 
                                          placeholder="Berikan ulasan tentang kualitas pekerjaan, sikap pekerja, atau pengalaman overall..."></textarea>
                                <div class="form-text">Ulasan Anda akan membantu pekerja lain dan meningkatkan kualitas platform.</div>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" name="konfirmasi_final" value="1" class="btn btn-success btn-lg py-3">
                                    <i class="fas fa-check-circle"></i> KONFIRMASI SELESAI & BERI PENILAIAN
                                </button>
                                <a href="detail_pekerjaan_user.php?id=<?php echo $pekerjaan_id; ?>" class="btn btn-secondary btn-lg py-3">
                                    <i class="fas fa-times"></i> Batalkan
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal untuk foto full size -->
    <div class="modal fade" id="imageModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Foto Bukti Penyelesaian</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="modalImage" src="" alt="Foto Bukti" class="img-fluid">
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function openModal(imageSrc) {
            document.getElementById('modalImage').src = imageSrc;
            new bootstrap.Modal(document.getElementById('imageModal')).show();
        }

        document.getElementById('formKonfirmasi').addEventListener('submit', function(e) {
            const checkboxes = document.querySelectorAll('.verification-card .form-check-input');
            const allChecked = Array.from(checkboxes).every(checkbox => checkbox.checked);
            
            if (!allChecked) {
                e.preventDefault();
                alert('Harap centang semua checklist verifikasi sebelum mengkonfirmasi!');
                return;
            }

            if (!confirm('Apakah Anda yakin pekerjaan sudah selesai dan memuaskan? Tindakan ini tidak dapat dibatalkan.')) {
                e.preventDefault();
                return;
            }
        });
    </script>
</body>
</html>