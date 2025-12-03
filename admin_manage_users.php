<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

// Helper function untuk safe display
function safeDisplay($value, $default = '-') {
    if (empty($value) || $value === null) {
        return $default;
    }
    return htmlspecialchars($value);
}

// Function untuk memastikan kolom ada di tabel
function ensureColumnsExist($pdo) {
    $columns_to_add = [
        'telepon' => "ALTER TABLE users ADD COLUMN telepon VARCHAR(20) NULL AFTER email",
        'alamat' => "ALTER TABLE users ADD COLUMN alamat TEXT NULL AFTER telepon", 
        'pengalaman' => "ALTER TABLE users ADD COLUMN pengalaman DECIMAL(3,1) NULL AFTER alamat"
    ];
    
    try {
        $stmt = $pdo->prepare("DESCRIBE users");
        $stmt->execute();
        $existing_columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($columns_to_add as $column => $sql) {
            if (!in_array($column, $existing_columns)) {
                $pdo->exec($sql);
                error_log("Kolom $column berhasil ditambahkan");
            }
        }
        return true;
    } catch (PDOException $e) {
        error_log("Error checking/adding columns: " . $e->getMessage());
        return false;
    }
}

// Panggil function untuk memastikan kolom ada di awal
ensureColumnsExist($pdo);

// Handle register new user by admin
if ($_POST && isset($_POST['register_user'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $email = $_POST['email'];
    $nama_lengkap = $_POST['nama_lengkap'];
    $telepon = !empty($_POST['telepon']) ? $_POST['telepon'] : null;
    $alamat = !empty($_POST['alamat']) ? $_POST['alamat'] : null;
    $pengalaman = !empty($_POST['pengalaman']) ? floatval($_POST['pengalaman']) : null;
    $role = $_POST['role'];
    
    // Validasi
    $errors = [];
    
    if ($password !== $confirm_password) {
        $errors[] = "Password dan konfirmasi password tidak sama!";
    }
    
    if (strlen($password) < 6) {
        $errors[] = "Password minimal 6 karakter!";
    }
    
    // Cek username sudah ada
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        $errors[] = "Username sudah digunakan!";
    }
    
    // Cek email sudah ada
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        $errors[] = "Email sudah digunakan!";
    }
    
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        try {
            // Pastikan semua kolom sudah ada
            ensureColumnsExist($pdo);
            
            // Insert new user - handle NULL values properly
            $stmt = $pdo->prepare("INSERT INTO users (username, password, email, nama_lengkap, telepon, alamat, pengalaman, role) 
                                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$username, $hashed_password, $email, $nama_lengkap, $telepon, $alamat, $pengalaman, $role]);
            
            $_SESSION['success'] = "User baru berhasil didaftarkan!";
            header("Location: admin_manage_users.php");
            exit();
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error: " . $e->getMessage();
            header("Location: admin_manage_users.php");
            exit();
        }
    } else {
        // Simpan error di session untuk ditampilkan
        $_SESSION['register_errors'] = $errors;
        header("Location: admin_manage_users.php");
        exit();
    }
}

// Handle delete user
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    
    // Prevent admin from deleting themselves
    if ($delete_id != $_SESSION['user_id']) {
        try {
            // Delete related records first
            $pdo->prepare("DELETE FROM aplikasi_pekerjaan WHERE id_kuli = ?")->execute([$delete_id]);
            $pdo->prepare("DELETE FROM pekerjaan WHERE id_user = ?")->execute([$delete_id]);
            
            // Delete user
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$delete_id]);
            
            $_SESSION['success'] = "User berhasil dihapus";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "Tidak dapat menghapus akun sendiri";
    }
    
    header("Location: admin_manage_users.php");
    exit();
}

// Handle update user role
if ($_POST && isset($_POST['update_role'])) {
    $user_id = $_POST['user_id'];
    $new_role = $_POST['role'];
    
    // Prevent admin from changing their own role
    if ($user_id != $_SESSION['user_id']) {
        try {
            $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
            $stmt->execute([$new_role, $user_id]);
            
            $_SESSION['success'] = "Role user berhasil diupdate";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "Tidak dapat mengubah role sendiri";
    }
    
    header("Location: admin_manage_users.php");
    exit();
}

// Handle edit user data
if ($_POST && isset($_POST['edit_user'])) {
    $user_id = $_POST['user_id'];
    $nama_lengkap = $_POST['nama_lengkap'];
    $email = $_POST['email'];
    $telepon = !empty($_POST['telepon']) ? $_POST['telepon'] : null;
    $alamat = !empty($_POST['alamat']) ? $_POST['alamat'] : null;
    $pengalaman = !empty($_POST['pengalaman']) ? floatval($_POST['pengalaman']) : null;
    
    try {
        // Pastikan semua kolom sudah ada
        ensureColumnsExist($pdo);
        
        // Update data user - handle NULL values properly
        $stmt = $pdo->prepare("UPDATE users SET nama_lengkap = ?, email = ?, telepon = ?, alamat = ?, pengalaman = ? WHERE id = ?");
        $stmt->execute([$nama_lengkap, $email, $telepon, $alamat, $pengalaman, $user_id]);
        
        $_SESSION['success'] = "Data user berhasil diupdate";
    } catch (PDOException $e) {
        // Jika masih error, coba update tanpa kolom tambahan
        try {
            $stmt = $pdo->prepare("UPDATE users SET nama_lengkap = ?, email = ? WHERE id = ?");
            $stmt->execute([$nama_lengkap, $email, $user_id]);
            $_SESSION['success'] = "Data dasar berhasil diupdate (nama dan email)";
        } catch (PDOException $e2) {
            $_SESSION['error'] = "Error update: " . $e2->getMessage();
        }
    }
    
    header("Location: admin_manage_users.php");
    exit();
}

// Handle change password
if ($_POST && isset($_POST['change_password'])) {
    $user_id = $_POST['user_id'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validasi
    if ($new_password !== $confirm_password) {
        $_SESSION['error'] = "Password baru dan konfirmasi password tidak sama!";
        header("Location: admin_manage_users.php");
        exit();
    }
    
    if (strlen($new_password) < 6) {
        $_SESSION['error'] = "Password baru minimal 6 karakter!";
        header("Location: admin_manage_users.php");
        exit();
    }
    
    try {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$hashed_password, $user_id]);
        
        $_SESSION['success'] = "Password user berhasil diubah!";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error mengubah password: " . $e->getMessage();
    }
    
    header("Location: admin_manage_users.php");
    exit();
}

// Get all users with detailed information
$stmt = $pdo->query("SELECT u.*, 
                    (SELECT COUNT(*) FROM pekerjaan p WHERE p.id_user = u.id) as total_pekerjaan,
                    (SELECT COUNT(*) FROM aplikasi_pekerjaan ap WHERE ap.id_kuli = u.id) as total_aplikasi
                    FROM users u 
                    ORDER BY u.created_at DESC");
$users = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola User - Kulimang</title>
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
        
        <?php if (isset($_SESSION['register_errors'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle"></i> 
                <?php 
                    foreach ($_SESSION['register_errors'] as $error) {
                        echo "<div>$error</div>";
                    }
                    unset($_SESSION['register_errors']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="mb-0"><i class="fas fa-users"></i> Kelola User</h4>
                <div>
                    <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#registerUserModal">
                        <i class="fas fa-user-plus"></i> Tambah User Baru
                    </button>
                    <span class="badge bg-primary ms-2">Total: <?php echo count($users); ?> User</span>
                </div>
            </div>
            <div class="card-body">
                <?php if ($users): ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>Nama Lengkap</th>
                                    <th>Kontak</th>
                                    <th>Role</th>
                                    <th>Statistik</th>
                                    <th>Tanggal Daftar</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo safeDisplay($user['nama_lengkap']); ?></strong>
                                            <?php if ($user['id'] == $_SESSION['user_id']): ?>
                                                <span class="badge bg-warning">Anda</span>
                                            <?php endif; ?>
                                            <br>
                                            <small class="text-muted"><?php echo safeDisplay($user['email']); ?></small>
                                        </td>
                                        <td>
                                            <div>
                                                <small><strong>Telp:</strong> <?php echo safeDisplay($user['telepon'] ?? 'Belum diisi'); ?></small>
                                            </div>
                                            <?php if (!empty($user['alamat']) && $user['alamat'] !== null): ?>
                                            <div>
                                                <small><strong>Alamat:</strong> <?php echo safeDisplay(substr($user['alamat'], 0, 50)); ?>...</small>
                                            </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <select name="role" class="form-select form-select-sm" onchange="this.form.submit()" <?php echo $user['id'] == $_SESSION['user_id'] ? 'disabled' : ''; ?>>
                                                    <option value="user" <?php echo $user['role'] == 'user' ? 'selected' : ''; ?>>User</option>
                                                    <option value="kuli" <?php echo $user['role'] == 'kuli' ? 'selected' : ''; ?>>Kuli</option>
                                                    <option value="admin" <?php echo $user['role'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
                                                </select>
                                                <input type="hidden" name="update_role" value="1">
                                            </form>
                                        </td>
                                        <td>
                                            <?php if ($user['role'] == 'user'): ?>
                                                <span class="badge bg-info"><?php echo $user['total_pekerjaan']; ?> Pekerjaan</span>
                                            <?php elseif ($user['role'] == 'kuli'): ?>
                                                <span class="badge bg-success"><?php echo $user['total_aplikasi']; ?> Aplikasi</span>
                                            <?php endif; ?>
                                            <?php if (!empty($user['pengalaman']) && $user['role'] == 'kuli'): ?>
                                                <br>
                                                <small class="text-muted"><?php echo $user['pengalaman']; ?> tahun pengalaman</small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($user['created_at'])); ?></td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" class="btn btn-outline-primary" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#userDetailModal"
                                                        data-user='<?php echo htmlspecialchars(json_encode($user), ENT_QUOTES, 'UTF-8'); ?>'>
                                                    <i class="fas fa-eye"></i> Detail
                                                </button>
                                                <button type="button" class="btn btn-outline-warning" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#editUserModal"
                                                        data-user='<?php echo htmlspecialchars(json_encode($user), ENT_QUOTES, 'UTF-8'); ?>'>
                                                    <i class="fas fa-edit"></i> Edit
                                                </button>
                                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                    <button type="button" class="btn btn-outline-info" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#changePasswordModal"
                                                            data-user-id="<?php echo $user['id']; ?>"
                                                            data-user-name="<?php echo htmlspecialchars($user['nama_lengkap']); ?>">
                                                        <i class="fas fa-key"></i> Ganti Password
                                                    </button>
                                                    <a href="admin_manage_users.php?delete_id=<?php echo $user['id']; ?>" 
                                                       class="btn btn-outline-danger" 
                                                       onclick="return confirmDeleteUser('<?php echo htmlspecialchars($user['nama_lengkap']); ?>')">
                                                        <i class="fas fa-trash"></i> Hapus
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-users fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Belum ada user terdaftar.</p>
                        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#registerUserModal">
                            <i class="fas fa-user-plus"></i> Tambah User Pertama
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Register User Modal -->
    <div class="modal fade" id="registerUserModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-plus"></i> Tambah User Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label"><strong>Username *</strong></label>
                                    <input type="text" class="form-control" name="username" required>
                                    <div class="form-text">Username unik untuk login</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label"><strong>Nama Lengkap *</strong></label>
                                    <input type="text" class="form-control" name="nama_lengkap" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label"><strong>Email *</strong></label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label"><strong>Password *</strong></label>
                                    <input type="password" class="form-control" name="password" required minlength="6">
                                    <div class="form-text">Minimal 6 karakter</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label"><strong>Konfirmasi Password *</strong></label>
                                    <input type="password" class="form-control" name="confirm_password" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label"><strong>Telepon</strong></label>
                                    <input type="text" class="form-control" name="telepon" placeholder="Contoh: 081234567890">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label"><strong>Role *</strong></label>
                                    <select class="form-select" name="role" required>
                                        <option value="">Pilih Role</option>
                                        <option value="user">User (Pemilik Proyek)</option>
                                        <option value="kuli">Kuli (Pekerja)</option>
                                        <option value="admin">Admin</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label"><strong>Alamat</strong></label>
                            <textarea class="form-control" name="alamat" rows="2" placeholder="Masukkan alamat lengkap"></textarea>
                        </div>
                        
                        <div class="mb-3" id="pengalamanField" style="display: none;">
                            <label class="form-label"><strong>Pengalaman (tahun)</strong></label>
                            <input type="number" class="form-control" name="pengalaman" min="0" max="50" step="0.1" placeholder="Masukkan tahun pengalaman">
                            <div class="form-text">Hanya untuk role Kuli. Kosongkan jika tidak ada.</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="register_user" class="btn btn-primary">Daftarkan User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Change Password Modal -->
    <div class="modal fade" id="changePasswordModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-key"></i> Ganti Password User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="" id="changePasswordForm">
                    <div class="modal-body">
                        <input type="hidden" name="user_id" id="passwordUserId">
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            Anda akan mengganti password untuk user: <strong id="userNameDisplay"></strong>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label"><strong>Password Baru *</strong></label>
                            <input type="password" class="form-control" name="new_password" id="newPassword" required minlength="6">
                            <div class="form-text">Minimal 6 karakter</div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label"><strong>Konfirmasi Password Baru *</strong></label>
                            <input type="password" class="form-control" name="confirm_password" id="confirmPassword" required>
                            <div class="invalid-feedback" id="passwordError">
                                Password tidak cocok!
                            </div>
                        </div>
                        
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Perhatian:</strong> User harus login ulang dengan password baru ini.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="change_password" class="btn btn-primary" id="submitPasswordBtn">Ganti Password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- User Detail Modal -->
    <div class="modal fade" id="userDetailModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detail User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="userDetailContent">
                    <!-- Content will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Data User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body" id="editUserContent">
                        <!-- Content will be loaded here -->
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="edit_user" class="btn btn-primary">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmDeleteUser(nama) {
            return confirm(`Apakah Anda yakin ingin menghapus user "${nama}"? Tindakan ini tidak dapat dibatalkan!`);
        }

        // Tampilkan field pengalaman hanya untuk role kuli
        document.addEventListener('DOMContentLoaded', function() {
            const roleSelect = document.querySelector('select[name="role"]');
            const pengalamanField = document.getElementById('pengalamanField');
            
            if (roleSelect && pengalamanField) {
                roleSelect.addEventListener('change', function() {
                    if (this.value === 'kuli') {
                        pengalamanField.style.display = 'block';
                    } else {
                        pengalamanField.style.display = 'none';
                    }
                });
            }
            
            // Validasi password pada form ganti password
            const newPassword = document.getElementById('newPassword');
            const confirmPassword = document.getElementById('confirmPassword');
            const passwordError = document.getElementById('passwordError');
            const submitPasswordBtn = document.getElementById('submitPasswordBtn');
            
            if (newPassword && confirmPassword) {
                function validatePassword() {
                    if (newPassword.value !== confirmPassword.value) {
                        confirmPassword.classList.add('is-invalid');
                        passwordError.style.display = 'block';
                        submitPasswordBtn.disabled = true;
                        return false;
                    } else {
                        confirmPassword.classList.remove('is-invalid');
                        passwordError.style.display = 'none';
                        submitPasswordBtn.disabled = false;
                        return true;
                    }
                }
                
                newPassword.addEventListener('input', validatePassword);
                confirmPassword.addEventListener('input', validatePassword);
            }
        });

        // Change Password Modal
        const changePasswordModal = document.getElementById('changePasswordModal');
        if (changePasswordModal) {
            changePasswordModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                const userId = button.getAttribute('data-user-id');
                const userName = button.getAttribute('data-user-name');
                
                document.getElementById('passwordUserId').value = userId;
                document.getElementById('userNameDisplay').textContent = userName;
                
                // Reset form
                document.getElementById('changePasswordForm').reset();
                document.getElementById('confirmPassword').classList.remove('is-invalid');
                document.getElementById('passwordError').style.display = 'none';
                document.getElementById('submitPasswordBtn').disabled = false;
            });
        }

        // User Detail Modal
        const userDetailModal = document.getElementById('userDetailModal');
        if (userDetailModal) {
            userDetailModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                const user = JSON.parse(button.getAttribute('data-user'));
                
                let content = `
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Informasi Pribadi</h6>
                            <table class="table table-sm">
                                <tr>
                                    <td><strong>Nama Lengkap:</strong></td>
                                    <td>${user.nama_lengkap}</td>
                                </tr>
                                <tr>
                                    <td><strong>Username:</strong></td>
                                    <td>${user.username}</td>
                                </tr>
                                <tr>
                                    <td><strong>Email:</strong></td>
                                    <td>${user.email}</td>
                                </tr>
                                <tr>
                                    <td><strong>Telepon:</strong></td>
                                    <td>${user.telepon ? user.telepon : '-'}</td>
                                </tr>
                                <tr>
                                    <td><strong>Role:</strong></td>
                                    <td><span class="badge bg-primary">${user.role}</span></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6>Informasi Tambahan</h6>
                            <table class="table table-sm">
                                <tr>
                                    <td><strong>Alamat:</strong></td>
                                    <td>${user.alamat ? user.alamat : '-'}</td>
                                </tr>
                                <tr>
                                    <td><strong>Pengalaman:</strong></td>
                                    <td>${user.pengalaman ? user.pengalaman + ' tahun' : '-'}</td>
                                </tr>
                                <tr>
                                    <td><strong>Total Pekerjaan:</strong></td>
                                    <td>${user.total_pekerjaan || 0}</td>
                                </tr>
                                <tr>
                                    <td><strong>Total Aplikasi:</strong></td>
                                    <td>${user.total_aplikasi || 0}</td>
                                </tr>
                                <tr>
                                    <td><strong>Tanggal Daftar:</strong></td>
                                    <td>${new Date(user.created_at).toLocaleDateString('id-ID')} ${new Date(user.created_at).toLocaleTimeString('id-ID')}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                `;
                
                document.getElementById('userDetailContent').innerHTML = content;
            });
        }

        // Edit User Modal
        const editUserModal = document.getElementById('editUserModal');
        if (editUserModal) {
            editUserModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                const user = JSON.parse(button.getAttribute('data-user'));
                
                let content = `
                    <input type="hidden" name="user_id" value="${user.id}">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label"><strong>Nama Lengkap *</strong></label>
                                <input type="text" class="form-control" name="nama_lengkap" value="${user.nama_lengkap}" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label"><strong>Email *</strong></label>
                                <input type="email" class="form-control" name="email" value="${user.email}" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label"><strong>Telepon</strong></label>
                                <input type="text" class="form-control" name="telepon" value="${user.telepon || ''}" placeholder="Contoh: 081234567890">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label"><strong>Alamat</strong></label>
                                <textarea class="form-control" name="alamat" rows="3" placeholder="Masukkan alamat lengkap">${user.alamat || ''}</textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label"><strong>Pengalaman (tahun)</strong></label>
                                <input type="number" class="form-control" name="pengalaman" value="${user.pengalaman || ''}" min="0" max="50" step="0.1" placeholder="Masukkan tahun pengalaman">
                                <div class="form-text">Hanya untuk role Kuli. Kosongkan jika tidak ada.</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label"><strong>Role</strong></label>
                                <input type="text" class="form-control" value="${user.role}" disabled>
                                <div class="form-text">Untuk mengubah role, gunakan dropdown di tabel</div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> 
                                <strong>Informasi:</strong> Tanggal daftar: ${new Date(user.created_at).toLocaleDateString('id-ID')}
                                <br><small>* Field wajib diisi. Field lainnya bisa dikosongkan.</small>
                                <br><small>Untuk mengganti password, klik tombol "Ganti Password" di tabel.</small>
                            </div>
                        </div>
                    </div>
                `;
                
                document.getElementById('editUserContent').innerHTML = content;
            });
        }

        // Auto hide alerts
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