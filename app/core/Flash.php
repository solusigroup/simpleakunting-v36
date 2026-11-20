<?php

class Flash {
    /**
     * Mengatur session flash message.
     * Mendukung 2 argumen: setFlash($pesan, $tipe)
     * Mendukung 3 argumen: setFlash($judul, $pesan, $tipe)
     */
    public static function setFlash($param1, $param2, $param3 = null)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if ($param3 !== null) {
            // Dipanggil dengan 3 argumen: setFlash($judul, $pesan, $tipe)
            $judul = (string)$param1;
            $pesan = (string)$param2;
            $tipe  = (string)$param3;
        } else {
            // Dipanggil dengan 2 argumen: setFlash($pesan, $tipe)
            $judul = '';
            $pesan = (string)$param1;
            $tipe  = (string)$param2;
        }

        // Whitelist tipe alert Bootstrap untuk mencegah CSS/attribute injection
        $allowedTypes = ['success', 'danger', 'warning', 'info', 'primary', 'secondary', 'light', 'dark'];
        $cleanType = in_array(strtolower(trim($tipe)), $allowedTypes) ? strtolower(trim($tipe)) : 'info';

        $_SESSION['flash'] = [
            'judul' => $judul,
            'pesan' => $pesan,
            'tipe'  => $cleanType
        ];
    }

    /**
     * Menampilkan flash message jika ada dan langsung menghapusnya.
     * Menjamin sanitasi htmlspecialchars konsisten pada seluruh output.
     */
    public static function flash()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (isset($_SESSION['flash'])) {
            $tipe = htmlspecialchars($_SESSION['flash']['tipe'] ?? 'info', ENT_QUOTES, 'UTF-8');
            $pesan = htmlspecialchars($_SESSION['flash']['pesan'] ?? '', ENT_QUOTES, 'UTF-8');
            $judul = !empty($_SESSION['flash']['judul']) ? htmlspecialchars($_SESSION['flash']['judul'], ENT_QUOTES, 'UTF-8') : '';

            $judulHtml = $judul !== '' ? '<strong>' . $judul . ':</strong> ' : '';

            echo '<div class="alert alert-' . $tipe . ' alert-dismissible fade show shadow-sm" role="alert">
                    ' . $judulHtml . $pesan . '
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                  </div>';
            // Hapus session setelah ditampilkan
            unset($_SESSION['flash']);
        }
    }
}

