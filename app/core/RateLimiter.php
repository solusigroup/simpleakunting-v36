<?php

class RateLimiter {
    private static $storageDir = null;

    /**
     * Inisialisasi direktori penyimpanan rate limit.
     */
    private static function initStorage() {
        if (self::$storageDir === null) {
            $candidateDir = sys_get_temp_dir() . '/simpleak_rate_limits';
            if (!is_dir($candidateDir)) {
                @mkdir($candidateDir, 0700, true);
            }
            if (is_dir($candidateDir) && is_writable($candidateDir)) {
                self::$storageDir = $candidateDir;
            } else {
                $localDir = APPROOT . '/scratch/rate_limits';
                if (!is_dir($localDir)) {
                    @mkdir($localDir, 0700, true);
                }
                self::$storageDir = $localDir;
            }
        }
    }

    /**
     * Dapatkan IP address klien yang aman.
     */
    public static function getClientIp() {
        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            return trim($_SERVER['HTTP_CF_CONNECTING_IP']);
        }
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            return trim($ips[0]);
        }
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }

    /**
     * Cek apakah key tertentu sedang diblokir karena terlalu banyak percobaan gagal.
     * @param string $key Kunci pengenal (misal IP atau username+IP)
     * @param int $maxAttempts Maksimal batas percobaan (default: 5)
     * @param int $decaySeconds Jendela waktu pembatasan dalam detik (default: 900 / 15 menit)
     * @return array
     */
    public static function check($key, $maxAttempts = 5, $decaySeconds = 900) {
        self::initStorage();
        $safeKey = md5('rl_' . $key);
        $now = time();

        $data = null;
        if (self::$storageDir && is_dir(self::$storageDir)) {
            $file = self::$storageDir . '/' . $safeKey . '.json';
            if (file_exists($file)) {
                $content = @file_get_contents($file);
                if (!empty($content)) {
                    $data = json_decode($content, true);
                }
            }
        }

        // Fallback session
        if (!$data && isset($_SESSION['rate_limits'][$safeKey])) {
            $data = $_SESSION['rate_limits'][$safeKey];
        }

        if (!$data || !isset($data['attempts']) || !isset($data['first_attempt'])) {
            return [
                'is_blocked' => false,
                'retry_after' => 0,
                'remaining_attempts' => $maxAttempts,
                'attempts' => 0
            ];
        }

        $elapsed = $now - (int)$data['first_attempt'];

        // Jika periode blokir/decay telah lewat, reset otomatis
        if ($elapsed > $decaySeconds) {
            self::clear($key);
            return [
                'is_blocked' => false,
                'retry_after' => 0,
                'remaining_attempts' => $maxAttempts,
                'attempts' => 0
            ];
        }

        $attempts = (int)$data['attempts'];
        if ($attempts >= $maxAttempts) {
            $retryAfter = $decaySeconds - $elapsed;
            return [
                'is_blocked' => true,
                'retry_after' => max(1, $retryAfter),
                'remaining_attempts' => 0,
                'attempts' => $attempts
            ];
        }

        return [
            'is_blocked' => false,
            'retry_after' => 0,
            'remaining_attempts' => max(0, $maxAttempts - $attempts),
            'attempts' => $attempts
        ];
    }

    /**
     * Catat percobaan gagal dan perbarui hitungan.
     * @param string $key
     * @param int $decaySeconds
     * @return int Total percobaan gagal saat ini
     */
    public static function recordFailure($key, $decaySeconds = 900) {
        self::initStorage();
        $safeKey = md5('rl_' . $key);
        $now = time();

        $data = ['attempts' => 1, 'first_attempt' => $now];

        if (self::$storageDir && is_dir(self::$storageDir)) {
            $file = self::$storageDir . '/' . $safeKey . '.json';
            if (file_exists($file)) {
                $content = @file_get_contents($file);
                $existing = json_decode($content, true);
                if ($existing && isset($existing['attempts']) && isset($existing['first_attempt'])) {
                    if (($now - (int)$existing['first_attempt']) <= $decaySeconds) {
                        $data['attempts'] = (int)$existing['attempts'] + 1;
                        $data['first_attempt'] = (int)$existing['first_attempt'];
                    }
                }
            }
            @file_put_contents($file, json_encode($data), LOCK_EX);
        }

        // Simpan juga di session sebagai fallback
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        $_SESSION['rate_limits'][$safeKey] = $data;

        return $data['attempts'];
    }

    /**
     * Bersihkan riwayat percobaan gagal setelah login berhasil.
     * @param string $key
     */
    public static function clear($key) {
        self::initStorage();
        $safeKey = md5('rl_' . $key);

        if (self::$storageDir && is_dir(self::$storageDir)) {
            $file = self::$storageDir . '/' . $safeKey . '.json';
            if (file_exists($file)) {
                @unlink($file);
            }
        }

        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        if (isset($_SESSION['rate_limits'][$safeKey])) {
            unset($_SESSION['rate_limits'][$safeKey]);
        }
    }
}
