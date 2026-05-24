<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SimpleAkunting v3.6 - Kebanggaan Jawa Timur</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;500;700&family=Outfit:wght@400;600;800&display=swap"
        rel="stylesheet">

    <style>
        :root {
            /* Palette Jawa Timur */
            --jatim-blue: #0f4c81;
            /* Biru Lambang Jatim */
            --jatim-gold: #fbbf24;
            /* Emas Lambang Jatim */
            --jatim-red: #be123c;
            /* Merah Berani Batik Madura */
            --jatim-green: #065f46;
            /* Hijau Pesisiran */
            --rawon-black: #09090b;
            /* Hitam Pekat Kluwek */
            --glass-white: rgba(255, 255, 255, 0.9);
            --text-dark: #1e293b;
        }

        body {
            font-family: 'Space Grotesk', sans-serif;
            background-color: #fafafa;
            color: var(--text-dark);
            overflow-x: hidden;
        }

        h1,
        h2,
        h3,
        .navbar-brand {
            font-family: 'Outfit', sans-serif;
        }

        /* Batik Pattern Background */
        .batik-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url("data:image/svg+xml,%3Csvg width='80' height='80' viewBox='0 0 80 80' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='%230f4c81' fill-opacity='0.03' fill-rule='evenodd'%3E%3Cpath d='M11 18c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm48 25c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm-43-7c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm63 31c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM34 90c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm56-76c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM12 86c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zm66-3c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zm-46-45c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zm54 54c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM58 7c0 1.105-.895 2-2 2s-2-.895-2-2 .895-2 2-2 2 .895 2 2zM6 46c0 1.105-.895 2-2 2s-2-.895-2-2 .895-2 2-2 2 .895 2 2zm92 2c0 1.105-.895 2-2 2s-2-.895-2-2 .895-2 2-2 2 .895 2 2zM30 66c0 1.105-.895 2-2 2s-2-.895-2-2 .895-2 2-2 2 .895 2 2zm24-26c0 1.105-.895 2-2 2s-2-.895-2-2 .895-2 2-2 2 .895 2 2zM0 15c0 1.105.895 2 2 2s2-.895 2-2-.895-2-2-2-2 .895-2 2zm100 60c0 1.105-.895 2-2 2s-2-.895-2-2 .895-2 2-2 2 .895 2 2z' /%3E%3C/g%3E%3C/svg%3E");
            z-index: 0;
            pointer-events: none;
        }

        .navbar {
            background: rgba(15, 76, 129, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 3px solid var(--jatim-gold);
            padding: 1rem 0;
        }

        .hero-section {
            padding: 140px 0 100px;
            background: linear-gradient(135deg, var(--rawon-black) 0%, #1e293b 100%);
            color: white;
            position: relative;
            overflow: hidden;
        }

        .hero-section::after {
            content: "";
            position: absolute;
            bottom: -50px;
            right: -50px;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, var(--jatim-blue) 0%, transparent 70%);
            opacity: 0.4;
            filter: blur(50px);
        }

        .hero-badge {
            background: var(--jatim-gold);
            color: var(--rawon-black);
            padding: 5px 20px;
            border-radius: 5px;
            font-weight: 700;
            display: inline-block;
            margin-bottom: 20px;
            text-transform: uppercase;
            letter-spacing: 2px;
            transform: skewX(-15deg);
        }

        .hero-title {
            font-size: 4.5rem;
            font-weight: 800;
            line-height: 1;
            margin-bottom: 30px;
            color: white;
        }

        .hero-title span {
            color: var(--jatim-gold);
        }

        .btn-jatim {
            padding: 15px 40px;
            font-weight: 800;
            border-radius: 0;
            text-transform: uppercase;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }

        .btn-jatim-primary {
            background-color: var(--jatim-blue);
            color: white;
            border: 2px solid var(--jatim-blue);
        }

        .btn-jatim-primary:hover {
            background-color: transparent;
            color: var(--jatim-gold);
            border-color: var(--jatim-gold);
        }

        .btn-jatim-outline {
            border: 2px solid var(--jatim-gold);
            color: var(--jatim-gold);
        }

        .btn-jatim-outline:hover {
            background-color: var(--jatim-gold);
            color: var(--rawon-black);
        }

        .feature-card {
            background: white;
            border: none;
            border-top: 5px solid var(--jatim-blue);
            border-radius: 0;
            padding: 50px 30px;
            transition: all 0.3s;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            position: relative;
        }

        .feature-card:hover {
            transform: translateY(-15px);
            border-top-color: var(--jatim-red);
        }

        .feature-icon {
            font-size: 3rem;
            color: var(--jatim-blue);
            margin-bottom: 20px;
        }

        .feature-card:hover .feature-icon {
            color: var(--jatim-red);
        }

        .section-header {
            margin-bottom: 60px;
        }

        .section-header h2 {
            font-size: 3rem;
            font-weight: 800;
            color: var(--rawon-black);
            position: relative;
            display: inline-block;
        }

        .section-header h2::after {
            content: "";
            position: absolute;
            bottom: -10px;
            left: 0;
            width: 60%;
            height: 6px;
            background: var(--jatim-gold);
        }

        .stats-section {
            background: var(--jatim-blue);
            color: white;
            padding: 80px 0;
            border-top: 10px solid var(--jatim-gold);
        }

        .footer {
            background: var(--rawon-black);
            color: white;
            padding: 80px 0 20px;
            border-top: 5px solid var(--jatim-red);
        }

        .footer h5 {
            color: var(--jatim-gold);
            font-weight: 800;
            margin-bottom: 25px;
        }

        .footer-link {
            color: #cbd5e1;
            text-decoration: none;
            display: block;
            margin-bottom: 15px;
            transition: color 0.3s;
        }

        .footer-link:hover {
            color: var(--jatim-gold);
        }

        /* Culture Elements */
        .motif-corner {
            position: absolute;
            width: 150px;
            height: 150px;
            opacity: 0.1;
            pointer-events: none;
        }

        .motif-tl {
            top: 0;
            left: 0;
            transform: rotate(0deg);
        }

        .motif-br {
            bottom: 0;
            right: 0;
            transform: rotate(180deg);
        }

        @media (max-width: 991px) {
            .hero-title {
                font-size: 3rem;
            }
        }
    </style>
</head>

<body>
    <div class="batik-overlay"></div>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="#">
                <img src="<?= BASEURL ?>/img/logo_jatim.png" alt="Logo" width="45" height="45" class="me-3">
                <div class="lh-1">
                    <span class="d-block fw-bold fs-3">SIMPLE AKUNTING</span>
                    <small class="text-warning fw-bold">Edisi Jawa Timur v3.6</small>
                </div>
            </a>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <i class="bi bi-list fs-1 text-warning"></i>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center gap-2 mt-3 mt-lg-0">
                    <li class="nav-item"><a class="nav-link px-3 fw-bold" href="https://bumdesadigital.my.id/aplikasi_kurasi_bumdesa_simpleakunting_linked.html">KURASI</a></li>
                    <li class="nav-item"><a class="nav-link px-3 fw-bold" href="#fitur">FITUR</a></li>
                    <li class="nav-item"><a class="nav-link px-3 fw-bold" href="#budaya">KEUNGGULAN</a></li>
                    <li class="nav-item ms-lg-3">
                        <a href="<?= BASEURL ?>/login" class="btn btn-jatim btn-jatim-outline">MASUK SISTEM</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero -->
    <section class="hero-section">
        <div class="container position-relative" style="z-index: 1;">
            <div class="row align-items-center">
                <div class="col-lg-7">
                    <div class="hero-badge">Digitalisasi BUMDesa Jawa Timur</div>
                    <h1 class="hero-title">TEGAS, BERANI, <span>PRESISI.</span></h1>
                    <p class="fs-4 text-light opacity-75 mb-5" style="max-width: 600px;">Sistem akuntansi modern dengan
                        semangat ketegasan Arek dan keluhuran Mataraman. Solusi cerdas untuk keuangan bisnis Anda.</p>
                    <div class="d-flex flex-wrap gap-3">
                        <a href="<?= BASEURL ?>/login" class="btn btn-jatim btn-jatim-primary">MULAI SEKARANG</a>
                        <a href="#fitur" class="btn btn-jatim btn-jatim-outline">LIHAT FITUR</a>
                    </div>
                </div>
                <div class="col-lg-5 d-none d-lg-block">
                    <div class="position-relative">
                        <img src="https://images.unsplash.com/photo-1590283603385-17ffb3a7f29f?auto=format&fit=crop&q=80&w=1000"
                            alt="Finance" class="img-fluid rounded-0 shadow-lg border border-warning"
                            style="border-width: 10px !important;">
                        <div class="position-absolute p-4 bg-warning text-dark fw-black fs-2"
                            style="bottom: -20px; left: -20px; transform: skewX(-10deg);">
                            JATIM MAJU!
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features -->
    <section class="py-5" id="fitur">
        <div class="container py-5 position-relative">
            <div class="section-header text-center">
                <h2>TEKNOLOGI TERDEPAN</h2>
                <p class="text-muted mt-3 fs-5">Membangun ekosistem keuangan BUMDesa yang transparan dan akuntabel.</p>
            </div>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="feature-card h-100">
                        <div class="feature-icon"><i class="bi bi-shield-check"></i></div>
                        <h4 class="fw-bold">KEAMANAN DATA</h4>
                        <p class="text-muted">Proteksi database tingkat tinggi memastikan data keuangan Anda aman dari
                            akses yang tidak sah.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card h-100" style="border-top-color: var(--jatim-gold);">
                        <div class="feature-icon" style="color: var(--jatim-gold);"><i class="bi bi-cpu-fill"></i></div>
                        <h4 class="fw-bold">OTOMASI LAPORAN</h4>
                        <p class="text-muted">Neraca dan Laba Rugi dihasilkan secara otomatis secepat kilat, memberikan
                            data real-time untuk keputusan bisnis.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card h-100" style="border-top-color: var(--jatim-red);">
                        <div class="feature-icon" style="color: var(--jatim-red);"><i
                                class="bi bi-gear-wide-connected"></i></div>
                        <h4 class="fw-bold">MULTI-UNIT</h4>
                        <p class="text-muted">Kelola banyak unit atau entitas bisnis dalam satu dashboard terintegrasi
                            dengan akses yang terkontrol.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats -->
    <section class="stats-section">
        <div class="container">
            <div class="row g-4 text-center">
                <div class="col-6 col-md-3">
                    <div class="fs-1 fw-black">500+</div>
                    <div class="text-warning fw-bold">BUMDesa AKTIF</div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="fs-1 fw-black">1M+</div>
                    <div class="text-warning fw-bold">TRANSAKSI/BLN</div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="fs-1 fw-black">38</div>
                    <div class="text-warning fw-bold">KAB/KOTA</div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="fs-1 fw-black">24/7</div>
                    <div class="text-warning fw-bold">SUPPORT SISTEM</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row g-5">
                <div class="col-lg-4">
                    <h5>SIMPLE AKUNTING v3.6</h5>
                    <p class="text-light opacity-50">Implementasi teknologi akuntansi berbasis kearifan lokal Jawa
                        Timur. Membawa BUMDesa melampaui batas tradisional menuju ekosistem digital.</p>
                    <div class="d-flex gap-3 mt-4">
                        <a href="#" class="btn btn-sm btn-outline-warning rounded-0"><i class="bi bi-facebook"></i></a>
                        <a href="#" class="btn btn-sm btn-outline-warning rounded-0"><i class="bi bi-instagram"></i></a>
                        <a href="#" class="btn btn-sm btn-outline-warning rounded-0"><i class="bi bi-twitter-x"></i></a>
                    </div>
                </div>
                <div class="col-lg-2 offset-lg-1">
                    <h5>NAVIGASI</h5>
                    <a href="#" class="footer-link">BERANDA</a>
                    <a href="#fitur" class="footer-link">FITUR</a>
                    <a href="#" class="footer-link">PANDUAN</a>
                </div>
                <div class="col-lg-2">
                    <h5>PERUSAHAAN</h5>
                    <a href="#" class="footer-link">TENTANG KAMI</a>
                    <a href="#" class="footer-link">KONTAK</a>
                    <a href="#" class="footer-link">KARIR</a>
                </div>
                <div class="col-lg-3">
                    <h5>KANTOR PUSAT</h5>
                    <p class="text-light opacity-50 small">
                        Jl. Teknologi Digital No. 110, Mojokerto<br>
                        Jawa Timur, Indonesia<br>
                        Email: support@simpleakunting.id
                    </p>
                    <a href="https://v3.simpleakunting.biz.id/umpan_balik.html"
                        class="btn btn-jatim btn-jatim-primary w-100">DAFTAR SEKARANG</a>
                </div>
            </div>
            <hr class="my-5 opacity-10">
            <div class="row align-items-center">
                <div class="col-md-6 text-center text-md-start small opacity-50">
                    &copy; 2026 Simple Akunting. Seluruh Hak Cipta Dilindungi.
                </div>
                <div class="col-md-6 text-center text-md-end small opacity-50">
                    Sinergi Jawa Timur - Klinik BUMDesa DPMD Prov. Jawa Timur
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        window.addEventListener('scroll', function () {
            const nav = document.querySelector('.navbar');
            if (window.scrollY > 100) {
                nav.style.padding = '0.5rem 0';
            } else {
                nav.style.padding = '1rem 0';
            }
        });
    </script>
</body>

</html>