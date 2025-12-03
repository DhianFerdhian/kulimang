<?php
session_start();
require_once 'config.php';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kulimang - Platform Pekerjaan Kuli Terpercaya</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #2c3e50;
            --secondary: #3498db;
            --accent: #e74c3c;
            --light: #ecf0f1;
            --dark: #2c3e50;
        }
        
        .hero-section {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 100px 0;
            position: relative;
            overflow: hidden;
        }
        
        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 100" fill="%23ffffff" opacity="0.1"><polygon points="1000,100 1000,0 0,100"></polygon></svg>');
            background-size: cover;
        }
        
        .feature-icon {
            width: 80px;
            height: 80px;
            background: var(--secondary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }
        
        .category-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: none;
            border-radius: 15px;
            overflow: hidden;
        }
        
        .category-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.1);
        }
        
        .stats-section {
            background: var(--light);
            padding: 80px 0;
        }
        
        .stat-number {
            font-size: 3rem;
            font-weight: bold;
            color: var(--secondary);
        }
        
        .btn-primary {
            background: var(--secondary);
            border: none;
            padding: 12px 30px;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            background: var(--primary);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .btn-outline-primary {
            border: 2px solid var(--secondary);
            color: var(--secondary);
            padding: 12px 30px;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-outline-primary:hover {
            background: var(--secondary);
            color: white;
            transform: translateY(-2px);
        }
        
        .navbar {
            background: rgba(255,255,255,0.95) !important;
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
        }
        
        .nav-link {
            font-weight: 500;
            margin: 0 10px;
            color: var(--dark) !important;
        }
        
        .nav-link:hover {
            color: var(--secondary) !important;
        }
        
        .how-it-works-step {
            position: relative;
            padding-left: 80px;
            margin-bottom: 40px;
        }
        
        .step-number {
            position: absolute;
            left: 0;
            top: 0;
            width: 60px;
            height: 60px;
            background: var(--secondary);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: bold;
        }
        
        .testimonial-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin: 15px;
            border: none;
        }
        
        .testimonial-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 15px;
        }
        
        .footer {
            background: var(--dark);
            color: white;
            padding: 60px 0 30px;
        }
        
        .footer a {
            color: var(--light);
            text-decoration: none;
            transition: color 0.3s ease;
        }
        
        .footer a:hover {
            color: var(--secondary);
        }
        
        .social-icons a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            margin-right: 10px;
            transition: all 0.3s ease;
        }
        
        .social-icons a:hover {
            background: var(--secondary);
            transform: translateY(-3px);
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light fixed-top">
        <div class="container">
            <a class="navbar-brand fw-bold fs-3" href="index.php" style="color: var(--secondary) !important;">
                <i class="fas fa-hammer me-2"></i>KULIMANG
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="#home">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="#layanan">Layanan</a></li>
                    <li class="nav-item"><a class="nav-link" href="#cara-kerja">Cara Kerja</a></li>
                    <li class="nav-item"><a class="nav-link" href="#tentang">Tentang</a></li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item">
                            <a class="nav-link btn btn-primary text-white px-3 ms-2" href="dashboard.php">
                                Dashboard
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link btn btn-outline-primary px-3 ms-2" href="login.php">
                                Login
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link btn btn-primary text-white px-3 ms-2" href="register.php">
                                Daftar
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section id="home" class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="display-4 fw-bold mb-4">Temukan Kuli Profesional untuk Semua Kebutuhan Anda</h1>
                    <p class="lead mb-5">Platform terpercaya yang menghubungkan Anda dengan tenaga kerja profesional untuk berbagai jenis pekerjaan konstruksi dan perbaikan rumah.</p>
                   
                </div>
                <div class="col-lg-6 text-center">
                    <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 600 400'%3E%3Cpath fill='%23ffffff' opacity='0.2' d='M300,200 Q400,100 500,200 Q400,300 300,200 Z'/%3E%3Ccircle cx='300' cy='200' r='80' fill='%23ffffff' opacity='0.3'/%3E%3C/svg%3E" 
                         alt="Construction Workers" class="img-fluid">
                </div>
            </div>
        </div>
    </section>

    <!-- Layanan Section -->
<section id="layanan" class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold">Layanan Kami</h2>
            <p class="text-muted">Berbagai jenis pekerjaan yang dapat Anda pesan melalui platform kami</p>
        </div>
        
        <div class="row g-4">
            <?php
            $kategori = $pdo->query("SELECT * FROM kategori_pekerjaan")->fetchAll();
            foreach ($kategori as $k):
            ?>
            <div class="col-lg-4 col-md-6">
                <div class="category-card card h-100">
                    <div class="card-body text-center p-4">
                        <div class="feature-icon">
                            <i class="fas fa-tools text-white fs-3"></i>
                        </div>
                        <h5 class="card-title fw-bold"><?php echo $k['nama_kategori']; ?></h5>
                        <p class="card-text text-muted"><?php echo $k['deskripsi']; ?></p>
                        <div class="price-tag mb-3">
                            <?php if ($k['biaya_per_m2'] > 0): ?>
                                <span class="h5 text-primary">Rp <?php echo number_format($k['biaya_per_m2'], 0, ',', '.'); ?></span>
                                <small class="text-muted">/m²</small>
                            <?php else: ?>
                                <span class="h6 text-muted">Hubungi untuk harga</span>
                            <?php endif; ?>
                        </div>
                        <a href="<?php echo isset($_SESSION['user_id']) ? 'upload_pekerjaan.php' : 'register.php'; ?>" class="btn btn-outline-primary">
                            Pesan Sekarang
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

    <!-- Cara Kerja Section -->
    <section id="cara-kerja" class="py-5 bg-light">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="fw-bold">Cara Kerja Kulimang</h2>
                <p class="text-muted">Hanya dalam 4 langkah mudah, dapatkan kuli profesional untuk pekerjaan Anda</p>
            </div>
            
            <div class="row">
                <div class="col-lg-6">
                    <div class="how-it-works-step">
                        <div class="step-number">1</div>
                        <h5>Daftar & Login</h5>
                        <p class="text-muted">Buat akun atau login ke platform Kulimang untuk mulai menggunakan layanan kami.</p>
                    </div>
                    
                    <div class="how-it-works-step">
                        <div class="step-number">2</div>
                        <h5>Upload Pekerjaan</h5>
                        <p class="text-muted">Jelaskan detail pekerjaan Anda, termasuk jenis pekerjaan, ukuran, dan lokasi.</p>
                    </div>
                </div>
                
                <div class="col-lg-6">
                    <div class="how-it-works-step">
                        <div class="step-number">3</div>
                        <h5>Bayar DP</h5>
                        <p class="text-muted">Lakukan pembayaran DP minimal 30% untuk memastikan keseriusan pekerjaan.</p>
                    </div>
                    
                    <div class="how-it-works-step">
                        <div class="step-number">4</div>
                        <h5>Tunggu Kuli Apply</h5>
                        <p class="text-muted">Kuli profesional akan mengajukan penawaran untuk mengerjakan proyek Anda.</p>
                    </div>
                </div>
            </div>
            
            <div class="text-center mt-5">
                <a href="<?php echo isset($_SESSION['user_id']) ? 'upload_pekerjaan.php' : 'register.php'; ?>" class="btn btn-light btn-lg px-4">
                    Mulai Sekarang
                </a>
            </div>
        </div>
    </section>


    <!-- CTA Section -->
    <section id="tentang" class="py-5">
    <section class="py-5 bg-primary text-white">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h3 class="fw-bold mb-3">Siap Mengerjakan Proyek Anda?</h3>
                    <p class="mb-0">Bergabunglah dengan ribuan pengguna yang telah mempercayakan pekerjaan mereka pada Kulimang</p>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <a href="<?php echo isset($_SESSION['user_id']) ? 'upload_pekerjaan.php' : 'register.php'; ?>" class="btn btn-light btn-lg px-4">
                        Pasang Pekerjaan Sekarang
                    </a>
                </div>
            </div>
        </div>
    </section>

<!-- Footer -->
<footer class="footer">
    <div class="container">
        <div class="row">
            <div class="col-lg-4 mb-4">
                <h5 class="fw-bold mb-3">
                    <i class="fas fa-hammer me-2"></i>KULIMANG
                </h5>
                <p>Platform terpercaya yang menghubungkan Anda dengan tenaga kerja profesional untuk berbagai jenis pekerjaan konstruksi dan perbaikan rumah.</p>
                <div class="social-icons">
                    <a href="https://wa.me/6282281400772" target="_blank"><i class="fab fa-whatsapp"></i></a>
                    <a href="hhttps://www.instagram.com/fer.dhian_?igsh=MWw1ZTdwMXI2ajJvMg==" target="_blank"><i class="fab fa-instagram"></i></a>
                    <a href="https://www.tiktok.com/@bnana.jmbi?_t=ZS-8w36TnkVgcT&_r=1" target="_blank"><i class="fab fa-tiktok"></i></a>
                    <a href="http://www.linkedin.com/in/dhian-kurnia-ferdiansyah-irawan-489612266" target="_blank"><i class="fab fa-linkedin-in"></i></a>
                </div>
            </div>
            
            <div class="col-lg-2 col-6 mb-4">
                <h6 class="fw-bold mb-3">Tautan Cepat</h6>
                <ul class="list-unstyled">
                    <li class="mb-2"><a href="#home">Home</a></li>
                    <li class="mb-2"><a href="#layanan">Layanan</a></li>
                    <li class="mb-2"><a href="#cara-kerja">Cara Kerja</a></li>
                </ul>
            </div>
            
            <div class="col-lg-2 col-6 mb-4">
                <h6 class="fw-bold mb-3">Layanan</h6>
                <ul class="list-unstyled">
                    <?php foreach ($kategori as $k): ?>
                    <li class="mb-2"><a href="#"><?php echo $k['nama_kategori']; ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            
            <div class="col-lg-4 mb-4">
                <h6 class="fw-bold mb-3">Kontak Kami</h6>
                <ul class="list-unstyled">
                    <li class="mb-2">
                        <i class="fas fa-map-marker-alt me-2"></i>
                        Jl. Amanah Sejahtera, Jambi Luar Kota, Kab. Muaro Jambi
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-phone me-2"></i>
                        +62 822-8140-0772
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-envelope me-2"></i>
                        dhiankurniaf99@gmail.com
                    </li>
                </ul>
            </div>
        </div>
        
        <hr class="my-4" style="border-color: rgba(255,255,255,0.1);">
        
        <div class="text-center">
            <p class="mb-0">&copy; 2025 Kulimang. All rights reserved.</p>
        </div>
    </div>
</footer>
</section>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Smooth scroll untuk navigasi
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Animasi scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        // Terapkan animasi pada elemen
        document.querySelectorAll('.category-card, .how-it-works-step, .testimonial-card').forEach(el => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(30px)';
            el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            observer.observe(el);
        });
    </script>
</body>
</html>