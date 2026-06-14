<?php
require_once __DIR__ . "/../config/conn.php";

if (is_logged_in()) {
    redirect(is_admin() ? 'admin/dashboard.php' : 'index.php');
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        require_csrf();

        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        $roleColumn = HAS_USER_ROLE_COLUMN ? ", role" : "";
        $stmt = mysqli_prepare($conn, "SELECT id, nama, email, password$roleColumn FROM users WHERE email = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);

        if ($user && password_verify($password, $user['password'])) {
            $role = HAS_USER_ROLE_COLUMN ? $user['role'] : 'user';

            session_regenerate_id(true);
            $_SESSION['user'] = [
                'id' => $user['id'],
                'nama' => $user['nama'],
                'email' => $user['email'],
                'role' => $role
            ];

            set_flash('success', 'Selamat datang, ' . $user['nama'] . '.');
            redirect($role === 'admin' ? 'admin/dashboard.php' : 'index.php');
        }

        $error = "Email atau password tidak sesuai.";
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
    <title>Login - Pustakata</title>
    <?= theme_bootstrap_script(); ?>
    <link rel="stylesheet" href="<?= e(asset('css/style.css')); ?>">
</head>
<body>
    <main class="auth-page">
        <section class="auth-card reveal reveal-scale">
            <a href="<?= e(url('index.php')); ?>" class="brand auth-logo"><img src="<?= e(asset('img/logo-pustakata-transparent.png')); ?>" alt="Pustakata"></a>
            <h1>Login</h1>
            <p>Masuk ke akun Pustakata untuk berbelanja.</p>
            <?= flash_messages(); ?>

            <?php if ($error): ?>
                <div class="alert alert-error"><?= e($error); ?></div>
            <?php endif; ?>

            <form method="POST" class="form-stack" data-validate>
                <?= csrf_field(); ?>
                <label>
                    Email
                    <input type="email" name="email" required placeholder="nama@email.com" value="<?= e($_POST['email'] ?? ''); ?>">
                </label>
                <label>
                    Password
                    <span class="password-field">
                        <input type="password" name="password" required placeholder="Masukkan password">
                        <button type="button" class="password-toggle" data-password-toggle>Tampil</button>
                    </span>
                </label>
                <button type="submit" class="btn btn-primary">Masuk</button>
            </form>

            <small>Belum punya akun? <a href="<?= e(url('auth/signup.php')); ?>">Sign Up</a></small>
            <small><a href="<?= e(url('index.php')); ?>">Kembali ke Beranda</a></small>
        </section>
    </main>
    <script src="<?= e(asset('js/script.js')); ?>"></script>
</body>
</html>
