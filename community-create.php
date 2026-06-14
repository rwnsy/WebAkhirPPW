<?php
require_once __DIR__ . "/config/conn.php";
require_login();

$activePage = 'community';
$error = '';
$title = '';
$content = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        require_csrf();

        if (!HAS_COMMUNITY_POSTS_TABLE) {
            throw new RuntimeException('Tabel community_posts belum tersedia. Import migration komunitas terlebih dahulu.');
        }

        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $userId = (int) ($_SESSION['user']['id'] ?? 0);

        if ($userId <= 0) {
            throw new RuntimeException('Sesi user tidak valid. Silakan login ulang.');
        }

        if ($title === '' || strlen($title) < 5) {
            throw new RuntimeException('Judul wajib diisi minimal 5 karakter.');
        }

        if ($content === '' || strlen($content) < 20) {
            throw new RuntimeException('Isi tulisan wajib diisi minimal 20 karakter.');
        }

        $stmt = mysqli_prepare($conn, "INSERT INTO community_posts (user_id, title, content, status) VALUES (?, ?, ?, 'published')");
        mysqli_stmt_bind_param($stmt, "iss", $userId, $title, $content);
        mysqli_stmt_execute($stmt);

        set_flash('success', 'Tulisan komunitas berhasil dipublikasikan.');
        redirect('community.php');
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
    <title>Tulis Cerita - Pustakata</title>
    <?= theme_bootstrap_script(); ?>
    <link rel="stylesheet" href="<?= e(asset('css/style.css')); ?>">
</head>
<body>
    <?php include partial_path('public_navbar.php'); ?>

    <main class="container community-detail-page">
        <nav class="breadcrumb reveal reveal-up">
            <a href="<?= e(url('index.php')); ?>">Beranda</a>
            <span>/</span>
            <a href="<?= e(url('community.php')); ?>">Komunitas</a>
            <span>/</span>
            <strong>Tulis Cerita</strong>
        </nav>

        <section class="community-write-grid">
            <div class="community-write-copy reveal reveal-left">
                <p class="eyebrow">TULISAN KOMUNITAS</p>
                <h1>Bagikan Cerita Membaca Anda</h1>
                <p>Tulis catatan membaca, rekomendasi, atau pengalaman menemukan buku favorit. Tulisan akan langsung tampil sebagai posting komunitas.</p>
                <a href="<?= e(url('community.php')); ?>" class="btn btn-outline">Kembali ke Komunitas</a>
            </div>

            <form method="POST" class="form-card community-write-card reveal reveal-right" data-validate>
                <?= csrf_field(); ?>
                <?php if ($error): ?>
                    <div class="alert alert-error"><?= e($error); ?></div>
                <?php endif; ?>

                <?php if (!HAS_COMMUNITY_POSTS_TABLE): ?>
                    <div class="alert alert-warning">Import <strong>database/migrations/migration_add_community_posts.sql</strong> sebelum membuat tulisan.</div>
                <?php endif; ?>

                <label>Judul Tulisan
                    <input type="text" name="title" required minlength="5" maxlength="150" value="<?= e($title); ?>" placeholder="Misal: Buku yang mengubah cara saya membaca">
                </label>

                <label>Isi Tulisan
                    <textarea name="content" rows="9" required minlength="20" maxlength="1800" data-character-counter="#communityContentCounter" placeholder="Tulis pengalaman membaca Anda..."><?= e($content); ?></textarea>
                    <small class="field-hint" id="communityContentCounter">0/1800 karakter</small>
                </label>

                <div class="form-actions">
                    <a href="<?= e(url('community.php')); ?>" class="btn btn-outline">Batal</a>
                    <button type="submit" class="btn btn-primary">Publish</button>
                </div>
            </form>
        </section>
    </main>

    <?php include partial_path('footer.php'); ?>
    <script src="<?= e(asset('js/script.js')); ?>"></script>
</body>
</html>
