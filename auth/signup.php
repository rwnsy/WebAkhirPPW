<?php
require_once __DIR__ . "/../config/conn.php";

if (is_logged_in()) {
    redirect(is_admin() ? 'admin/dashboard.php' : 'index.php');
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        require_csrf();

        $nama = trim($_POST['nama'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($nama === '' || $email === '' || $password === '') {
            throw new RuntimeException("Nama, email, dan password wajib diisi.");
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException("Format email belum valid.");
        }

        if (strlen($password) < 6) {
            throw new RuntimeException("Password minimal 6 karakter.");
        }

        if (!HAS_USER_ROLE_COLUMN) {
            throw new RuntimeException("Database belum dimigrasi. Tambahkan kolom role sebelum membuka signup publik.");
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $role = 'user';
        $stmt = mysqli_prepare($conn, "INSERT INTO users (nama, email, password, role) VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "ssss", $nama, $email, $hash, $role);

        mysqli_stmt_execute($stmt);
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id' => mysqli_insert_id($conn),
            'nama' => $nama,
            'email' => $email,
            'role' => $role
        ];

        set_flash('success', 'Akun berhasil dibuat. Selamat membaca di Pustakata.');
        redirect('index.php');
    } catch (mysqli_sql_exception $exception) {
        $error = "Email sudah terdaftar atau pendaftaran sedang belum tersedia.";
    } catch (Throwable $exception) {
        $error = $exception->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="id" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Pustakata</title>
    <?= theme_bootstrap_script(); ?>
    <link rel="stylesheet" href="<?= e(asset('css/style.css')); ?>">
</head>
<body>
    <main class="auth-page">
        <section class="auth-card reveal reveal-scale">
            <a href="<?= e(url('index.php')); ?>" class="brand auth-logo"><img src="<?= e(asset('img/logo-pustakata-transparent.png')); ?>" alt="Pustakata"></a>
            <h1>Sign Up</h1>
            <p>Buat akun pembaca untuk berbelanja di Pustakata.</p>
            <?= flash_messages(); ?>

            <?php if ($error): ?>
                <div class="alert alert-error"><?= e($error); ?></div>
            <?php endif; ?>

            <form method="POST" class="form-stack" data-validate>
                <?= csrf_field(); ?>
                <label>
                    Nama
                    <input type="text" name="nama" required value="<?= e($_POST['nama'] ?? ''); ?>">
                </label>
                <label>
                    Email
                    <input type="email" name="email" required value="<?= e($_POST['email'] ?? ''); ?>">
                </label>
                <label>
                    Password
                    <span class="password-field">
                        <input type="password" name="password" required minlength="6">
                        <button type="button" class="password-toggle" data-password-toggle>Tampil</button>
                    </span>
                </label>
                <button type="submit" class="btn btn-primary">Daftar</button>
            </form>

            <small>Sudah punya akun? <a href="<?= e(url('auth/login.php')); ?>">Login</a></small>
            <small><a href="<?= e(url('index.php')); ?>">Kembali ke Beranda</a></small>
        </section>
    </main>
    <script src="<?= e(asset('js/script.js')); ?>"></script>
</body>
</html>
