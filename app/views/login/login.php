<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $data['judul']; ?> - SIMPLE AKUNTING JATIM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;700&family=Space+Grotesk:wght@500;700&display=swap"
        rel="stylesheet">

    <!-- PWA Support -->
    <link rel="manifest" href="<?php echo BASEURL; ?>/manifest.json?v=2">

    <meta name="theme-color" content="#0f4c81">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="SimpleAkunting">
    <link rel="apple-touch-icon" href="<?php echo BASEURL; ?>/img/icon-512.png">
    <link rel="icon" type="image/png" href="<?php echo BASEURL; ?>/img/icon-512.png">

    <style>
        :root {
            --jatim-blue: #0f4c81;
            --jatim-gold: #fbbf24;
            --jatim-red: #be123c;
            --rawon-black: #09090b;
            --accent-cream: #fefce8;
        }

        body,
        html {
            height: 100%;
            margin: 0;
            font-family: 'Space Grotesk', sans-serif;
            background-color: var(--rawon-black);
            background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23fbbf24' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }

        .main-container {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 2rem;
        }

        .login-wrapper {
            display: flex;
            width: 100%;
            max-width: 1100px;
            min-height: 650px;
            background-color: #fff;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.5);
            border-radius: 0;
            overflow: hidden;
            border: 4px solid var(--jatim-gold);
            position: relative;
        }

        /* Decorative Stripe */
        .login-wrapper::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 10px;
            background: linear-gradient(90deg, var(--jatim-blue), var(--jatim-red), var(--jatim-gold), var(--jatim-blue));
            z-index: 10;
        }

        .illustration-side {
            background: linear-gradient(135deg, var(--jatim-blue) 0%, #063156 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: white;
            padding: 60px;
            text-align: center;
            position: relative;
        }

        .illustration-side img {
            width: 80%;
            max-width: 320px;
            height: auto;
            margin-bottom: 30px;
            filter: drop-shadow(0 10px 20px rgba(0, 0, 0, 0.3));
            animation: float 6s infinite ease-in-out;
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-20px);
            }
        }

        .login-form-side {
            padding: 60px;
            background: #ffffff;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .form-control {
            border-radius: 0;
            padding: 15px;
            border: 2px solid #e2e8f0;
            font-weight: 500;
        }

        .form-control:focus {
            box-shadow: none;
            border-color: var(--jatim-blue);
        }

        .input-group-text {
            background-color: #f8fafc;
            border: 2px solid #e2e8f0;
            border-right: none;
            border-radius: 0;
            color: var(--jatim-blue);
        }

        .btn-jatim {
            background-color: var(--jatim-blue);
            color: white;
            border: none;
            border-radius: 0;
            padding: 15px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s;
        }

        .btn-jatim:hover {
            background-color: var(--rawon-black);
            color: var(--jatim-gold);
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }

        .nav-pills .nav-link {
            color: var(--jatim-blue);
            border-radius: 0;
            font-weight: 700;
            border: 1px solid #e2e8f0;
            margin: 0 5px;
        }

        .nav-pills .nav-link.active {
            background-color: var(--jatim-blue);
            border-color: var(--jatim-blue);
        }

        .badge-jatim {
            background-color: var(--jatim-gold);
            color: var(--rawon-black);
            padding: 8px 15px;
            font-weight: 800;
            text-transform: uppercase;
            font-size: 0.75rem;
        }

        .login-footer {
            color: #94a3b8;
            font-size: 0.9rem;
        }

        .login-footer a {
            color: var(--jatim-gold);
            text-decoration: none;
            font-weight: 700;
        }

        @media (max-width: 991px) {
            .illustration-side {
                display: none;
            }

            .login-wrapper {
                max-width: 500px;
            }
        }
    </style>
</head>

<body>
    <div class="main-container">
        <div class="login-wrapper">
            <!-- Sisi Ilustrasi -->
            <div class="col-lg-6 illustration-side">
                <img src="<?php echo BASEURL; ?>/img/logo_jatim.png" alt="Logo Provinsi Jawa Timur">
                <h2 class="fw-bold mt-4">JER BASUKI MAWA BEYA</h2>
                <p class="opacity-75 mt-2">Kesuksesan membutuhkan pengorbanan dan kerja keras. Sistem Akuntansi
                    Terintegrasi untuk BUMDesa Jawa Timur yang Tangguh.</p>
                <div class="mt-4 d-flex gap-2">
                    <span class="badge-jatim">Tegas</span>
                    <span class="badge-jatim">Berani</span>
                    <span class="badge-jatim">Presisi</span>
                </div>
            </div>

            <!-- Sisi Form Login -->
            <div class="col-12 col-lg-6 login-form-side">
                <div class="text-center mb-5">
                    <h2 class="fw-bold" style="color: var(--jatim-blue);">SIMPLE AKUNTING</h2>
                    <p class="text-muted fw-bold">Pusat Digitalisasi Keuangan Jatim</p>
                </div>

                <?php Flash::flash(); ?>

                <ul class="nav nav-pills nav-fill mb-4" id="loginTab" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active" id="tenant-tab" data-bs-toggle="pill" data-bs-target="#tenant"
                            type="button">
                            <i class="bi bi-shop me-2"></i>TENANT
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" id="central-tab" data-bs-toggle="pill" data-bs-target="#central"
                            type="button">
                            <i class="bi bi-shield-lock me-2"></i>CENTRAL
                        </button>
                    </li>
                </ul>

                <div class="tab-content">
                    <!-- Tenant Login -->
                    <div class="tab-pane fade show active" id="tenant">
                        <form action="<?php echo BASEURL; ?>/login/process" method="post">
                            <input type="hidden" name="login_type" value="tenant">
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-uppercase">Kode Bisnis</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-buildings"></i></span>
                                    <input type="text" class="form-control" name="tenant_code"
                                        placeholder="CONTOH: JATIMJAYA" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-uppercase">Nama Pengguna</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-person-fill"></i></span>
                                    <input type="text" class="form-control" name="nama_user" placeholder="USERNAME"
                                        required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-uppercase">Kata Sandi</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-key-fill"></i></span>
                                    <input type="password" class="form-control" name="password" id="pass-tenant"
                                        placeholder="******" required>
                                    <button class="btn btn-outline-secondary border-2 border-start-0 rounded-0"
                                        type="button" onclick="togglePass('pass-tenant')">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="d-grid mt-4">
                                <button type="submit" class="btn btn-jatim">MASUK KE DASHBOARD</button>
                            </div>
                        </form>
                    </div>

                    <!-- Central Login -->
                    <div class="tab-pane fade" id="central">
                        <form action="<?php echo BASEURL; ?>/login/process" method="post">
                            <input type="hidden" name="login_type" value="central">
                            <div class="text-center mb-4">
                                <span class="badge bg-danger p-2 px-3 fw-bold"><i
                                        class="bi bi-exclamation-octagon me-2"></i>AREA SUPERADMIN</span>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-uppercase">Username Central</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-shield-shaded"></i></span>
                                    <input type="text" class="form-control" name="nama_user" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-uppercase">Kata Sandi</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                                    <input type="password" class="form-control" name="password" id="pass-central"
                                        required>
                                </div>
                            </div>
                            <div class="d-grid mt-4">
                                <button type="submit" class="btn btn-dark rounded-0 py-3 fw-bold">OTORISASI
                                    CENTRAL</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="login-footer text-center mt-5">
                    <p class="mb-1">&copy; 2026 Simple Akunting v3.6</p>
                    <p class="small">Sinergi <a href="#">Klinik BUMDesa DPMD Prov. Jawa Timur</a></p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePass(id) {
            const input = document.getElementById(id);
            input.type = input.type === 'password' ? 'text' : 'password';
        }
    </script>
</body>

</html>