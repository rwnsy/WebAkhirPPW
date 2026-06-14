<?php
require_once __DIR__ . "/../config/conn.php";
require_admin();

$adminPage = 'profile';
$message = "";
$error = "";
$userId = (int) ($_SESSION['user']['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        require_csrf();

        $nama = trim($_POST['nama'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($nama === '' || $email === '') {
            throw new RuntimeException("Nama dan email wajib diisi.");
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException("Format email belum valid.");
        }

        if ($password !== '') {
            if (strlen($password) < 6) {
                throw new RuntimeException("Password minimal 6 karakter.");
            }

            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = mysqli_prepare($conn, "UPDATE users SET nama = ?, email = ?, password = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "sssi", $nama, $email, $hash, $userId);
        } else {
            $stmt = mysqli_prepare($conn, "UPDATE users SET nama = ?, email = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "ssi", $nama, $email, $userId);
        }

        mysqli_stmt_execute($stmt);
        $_SESSION['user']['nama'] = $nama;
        $_SESSION['user']['email'] = $email;
        $message = "Profil admin berhasil diperbarui.";
    } catch (Throwable $exception) {
        $error = $exception->getMessage() ?: "Profil gagal diperbarui.";
    }
}
?>
<!DOCTYPE html>
<html lang="id" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Admin - Pustakata</title>
    <?= theme_bootstrap_script(); ?>
    <link rel="stylesheet" href="<?= e(asset('css/style.css')); ?>">
</head>
<body>
    <div class="admin-shell">
        <?php include partial_path('admin_sidebar.php'); ?>

        <main class="admin-main">
            <div class="admin-topbar reveal reveal-up">
                <div>
                    <p class="eyebrow">PROFIL ADMIN</p>
                    <h1>Pengaturan Akun</h1>
                    <p>Perbarui nama, email, atau password akun admin Pustakata.</p>
                </div>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-success"><?= e($message); ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-error"><?= e($error); ?></div>
            <?php endif; ?>

            <form method="POST" class="form-card profile-form reveal reveal-up" data-validate>
                <?= csrf_field(); ?>
                <label>Nama
                    <input type="text" name="nama" required value="<?= e($_SESSION['user']['nama'] ?? ''); ?>">
                </label>
                <label>Email
                    <input type="email" name="email" required value="<?= e($_SESSION['user']['email'] ?? ''); ?>">
                </label>
                <label>Password Baru
                    <span class="password-field">
                        <input type="password" name="password" minlength="6" placeholder="Kosongkan jika tidak ingin diganti">
                        <button type="button" class="password-toggle" data-password-toggle>Tampil</button>
                    </span>
                </label>
                <button type="submit" class="btn btn-primary">Simpan Profil</button>
            </form>
        </main>
    </div>
    <script src="<?= e(asset('js/script.js')); ?>"></script>
</body>
</html>
