<?php
require_once __DIR__ . "/config/conn.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php');
}

$redirectTo = safe_redirect_path($_POST['redirect_to'] ?? 'index.php');

try {
    require_csrf();

    if (!HAS_NEWSLETTER_SUBSCRIBERS_TABLE) {
        throw new RuntimeException('Tabel newsletter_subscribers belum tersedia. Import database/schema.sql terlebih dahulu.');
    }

    $email = strtolower(trim($_POST['email'] ?? ''));

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('Format email newsletter belum valid.');
    }

    if (strlen($email) > 160) {
        throw new RuntimeException('Email newsletter terlalu panjang.');
    }

    $stmt = mysqli_prepare($conn, "INSERT IGNORE INTO newsletter_subscribers (email) VALUES (?)");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);

    if (mysqli_stmt_affected_rows($stmt) > 0) {
        set_flash('success', 'Email berhasil berlangganan newsletter Pustakata.');
    } else {
        set_flash('warning', 'Email tersebut sudah terdaftar di newsletter Pustakata.');
    }
} catch (Throwable $exception) {
    set_flash('error', $exception->getMessage());
}

redirect($redirectTo);
