<?php
session_start();
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $email = $_POST['email'];
    $nama_lengkap = $_POST['nama_lengkap'];
    $no_telepon = $_POST['no_telepon'];
    $alamat = $_POST['alamat'];
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
            $stmt = $pdo->prepare("INSERT INTO users (username, password, email, nama_lengkap, no_telepon, alamat, role) 
                                 VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$username, $hashed_password, $email, $nama_lengkap, $no_telepon, $alamat, $role]);
            
            $_SESSION['success'] = "Registrasi berhasil! Silakan login.";
            header("Location: login.php");
            exit();
        } catch (PDOException $e) {
            $errors[] = "Terjadi kesalahan: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar - Kulimang</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            height: 100vh;
            display: flex;
            align-items: center;
        }
        .register-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .role-option {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .role-option:hover {
            border-color: #007bff;
        }
        .role-option.selected {
            border-color: #007bff;
            background-color: #f8f9fa;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="register-card p-5">
                    <div class="text-center mb-4">
                        <h2 class="fw-bold text-primary">Daftar Kulimang</h2>
                        <p class="text-muted">Bergabunglah dengan platform kami</p>
                    </div>
                    
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <?php foreach ($errors as $error): ?>
                                <div><?php echo $error; ?></div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" id="registerForm">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="nama_lengkap" class="form-label">Nama Lengkap</label>
                                    <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="username" class="form-label">Username</label>
                                    <input type="text" class="form-control" id="username" name="username" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="password" class="form-label">Password</label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Konfirmasi Password</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="no_telepon" class="form-label">No. Telepon</label>
                                    <input type="tel" class="form-control" id="no_telepon" name="no_telepon">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="alamat" class="form-label">Alamat</label>
                                    <textarea class="form-control" id="alamat" name="alamat" rows="1"></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label">Daftar Sebagai</label>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="role-option text-center" data-role="user">
                                        <i class="fas fa-user fa-2x mb-2 text-primary"></i>
                                        <h6>Pemilik Proyek</h6>
                                        <small class="text-muted">Ingin memesan jasa kuli</small>
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <div class="role-option text-center" data-role="kuli">
                                        <i class="fas fa-hammer fa-2x mb-2 text-success"></i>
                                        <h6>Kuli Profesional</h6>
                                        <small class="text-muted">Mencari pekerjaan</small>
                                    </div>
                                </div>

                            </div>
                            <input type="hidden" name="role" id="selectedRole" required>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100 py-2">Daftar</button>
                    </form>
                    
                    <div class="text-center mt-3">
                        <small class="text-muted">Sudah punya akun? 
                            <a href="login.php" class="text-decoration-none">Login di sini</a>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    <script>
        // Pilih role
        document.querySelectorAll('.role-option').forEach(option => {
            option.addEventListener('click', function() {
                document.querySelectorAll('.role-option').forEach(opt => {
                    opt.classList.remove('selected');
                });
                this.classList.add('selected');
                document.getElementById('selectedRole').value = this.getAttribute('data-role');
            });
        });

        // Validasi form
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const role = document.getElementById('selectedRole').value;
            if (!role) {
                e.preventDefault();
                alert('Pilih peran Anda terlebih dahulu!');
                return false;
            }
        });
    </script>
</body>
</html>