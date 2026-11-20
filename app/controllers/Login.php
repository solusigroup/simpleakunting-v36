<?php

class Login extends Controller {
    /**
     * Constructor ini sangat penting.
     * Ia memastikan koneksi database ($this->db) dibuat dengan memanggil
     * constructor dari Controller induk sebelum method lain di kelas ini dijalankan.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Menampilkan halaman login.
     * Jika pengguna sudah login, ia akan diarahkan ke dashboard.
     */
    public function index() {
        if (Auth::isLoggedIn()) {
            header('Location: ' . BASEURL . '/dashboard');
            exit;
        }
        $data['judul'] = 'Login';
        $this->view('login/login', $data);
    }

    /**
     * Memproses data yang dikirim dari form login.
     */
    public function process() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASEURL . '/login');
            exit;
        }

        $login_type = $_POST['login_type'] ?? 'tenant';
        $nama_user = trim($_POST['nama_user'] ?? '');
        $password = $_POST['password'] ?? '';

        // --- RATE LIMITING PERCOBAAN LOGIN ---
        $clientIp = RateLimiter::getClientIp();
        $rateLimitIpKey = 'login_ip_' . $clientIp;
        $rateLimitUserKey = 'login_user_' . strtolower($nama_user) . '_' . $clientIp;

        $ipCheck = RateLimiter::check($rateLimitIpKey, 5, 900);
        $userCheck = !empty($nama_user) ? RateLimiter::check($rateLimitUserKey, 5, 900) : ['is_blocked' => false, 'retry_after' => 0];

        if ($ipCheck['is_blocked'] || $userCheck['is_blocked']) {
            $retryAfter = max($ipCheck['retry_after'], $userCheck['retry_after']);
            $minutes = ceil($retryAfter / 60);
            Flash::setFlash("Terlalu banyak percobaan login yang gagal. Akses diblokir sementara. Silakan coba lagi dalam {$minutes} menit.", 'danger');
            header('Location: ' . BASEURL . '/login');
            exit;
        }

        if (empty($nama_user) || empty($password)) {
            Flash::setFlash('Nama pengguna dan kata sandi wajib diisi.', 'warning');
            header('Location: ' . BASEURL . '/login');
            exit;
        }

        // Helper penanganan kegagalan autentikasi dengan pencatatan rate limiter
        $handleAuthFailure = function($pesanKustom = null) use ($rateLimitIpKey, $rateLimitUserKey) {
            RateLimiter::recordFailure($rateLimitIpKey, 900);
            RateLimiter::recordFailure($rateLimitUserKey, 900);
            $check = RateLimiter::check($rateLimitUserKey, 5, 900);
            $remaining = $check['remaining_attempts'];

            if ($check['is_blocked'] || $remaining <= 0) {
                Flash::setFlash('Terlalu banyak percobaan login yang gagal. Akses diblokir sementara selama 15 menit.', 'danger');
            } else {
                $pesan = $pesanKustom ? $pesanKustom . " Sisa percobaan: {$remaining} kali." : "Nama pengguna atau sandi salah. Sisa percobaan: {$remaining} kali.";
                Flash::setFlash($pesan, 'danger');
            }
            header('Location: ' . BASEURL . '/login');
            exit;
        };

        // 1. Ambil data user
        $user = $this->model('User')->getUserByUsername($nama_user);

        // 2. Verifikasi Password
        if (!$user || !password_verify($password, $user['password_hash'])) {
            $handleAuthFailure('Nama pengguna atau sandi salah.');
        }

        // 3. Verifikasi berdasarkan jenis login
        if ($login_type === 'central') {
            // Login Central harus role Superadmin atau Penyelia Wilayah
            if ($user['role'] !== 'Superadmin' && $user['role'] !== 'Penyelia Wilayah') {
                $handleAuthFailure('Akses Ditolak! Akun Anda tidak memiliki otoritas Central.');
            }
            // Pastikan login central tidak membawa context tenant
            $user['tenant_id'] = null;
            $user['tenant_name'] = 'SYSTEM CENTRAL';
            $user['database_type'] = 'dagang'; // Default dashboard type for central
        } else {
            // Login Tenant harus menyertakan kode bisnis yang benar
            $tenant_code = $_POST['tenant_code'] ?? '';
            $tenant = $this->model('Tenants')->getTenantByCode($tenant_code);

            if (!$tenant) {
                $handleAuthFailure('Kode Bisnis tidak ditemukan atau tidak aktif.');
            }

            if ($user['tenant_id'] != $tenant['id']) {
                $handleAuthFailure('Pengguna tidak terdaftar di bisnis ' . $tenant['name'] . '.');
            }
            
            // Simpan info tenant ke user session array
            $user['tenant_name'] = $tenant['name'];
            $user['database_type'] = $tenant['database_type'];
        }

        // Berhasil login: bersihkan batas rate limiter
        RateLimiter::clear($rateLimitIpKey);
        RateLimiter::clear($rateLimitUserKey);

        // 4. Ambil Izin Akses jika ada custom role
        $permissions = [];
        if (!empty($user['role_id'])) {
            $roleModel = $this->model('Role');
            $permissions = $roleModel->getRolePermissions($user['role_id']);
        }

        // Ambil info nama kluster wilayah jika Penyelia Wilayah
        if ($user['role'] === 'Penyelia Wilayah' && !empty($user['kluster_wilayah_id'])) {
            $kw = $this->model('KlusterWilayah')->getKlusterById($user['kluster_wilayah_id']);
            $user['kluster_wilayah_nama'] = $kw['nama_kabupaten'] ?? null;
        }

        // Jika semua lolos, atur sesi
        Auth::setUser($user, $permissions);
        Logger::log('LOGIN', 'Authentication', 'User successfully logged in.');
        header('Location: ' . BASEURL . '/dashboard');
        exit;
    }

    /**
     * Menangani proses logout.
     */
    public function logout() {
        Auth::logout();
        header('Location: ' . BASEURL);
        exit;
    }
}

