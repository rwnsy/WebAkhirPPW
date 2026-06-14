<?php
require_once __DIR__ . "/../config/conn.php";

$_SESSION = [];
session_regenerate_id(true);
set_flash('success', 'Anda berhasil logout.');
redirect('auth/login.php');
