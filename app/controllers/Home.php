<?php

class Home extends Controller {
    public function index()
    {
        $data['judul'] = 'Selamat Datang';
        $this->view('home/index', $data);
    }
}