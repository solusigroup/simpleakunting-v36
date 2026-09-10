<?php

class Kuis extends Controller {
    public function index() {
        $paths = [
            APPROOT . '/kuis_evaluasi_bumdesa.html',
            APPROOT . '/public/kuis_evaluasi_bumdesa.html'
        ];

        foreach ($paths as $path) {
            if (file_exists($path)) {
                header('Content-Type: text/html; charset=UTF-8');
                readfile($path);
                exit;
            }
        }

        echo "Halaman kuis evaluasi tidak ditemukan.";
        exit;
    }
}
