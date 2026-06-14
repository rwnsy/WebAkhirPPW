<?php
require_once __DIR__ . "/../config/conn.php";
require_admin();

// File legacy dari versi tabel lama. Panel aktif sekarang ada di admin/books.php.
set_flash('warning', 'Halaman tabel lama dialihkan ke Kelola Buku agar panel admin tetap konsisten.');
redirect('admin/books.php');
