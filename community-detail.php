<?php
require_once __DIR__ . "/config/conn.php";
$activePage = 'community';

$id = (int) ($_GET['id'] ?? 0);
$post = null;

if ($id > 0 && HAS_COMMUNITY_POSTS_TABLE) {
    $stmt = mysqli_prepare($conn, "SELECT cp.id, cp.title, cp.content, cp.status, cp.created_at, cp.updated_at, u.nama
        FROM community_posts cp
        INNER JOIN users u ON u.id = cp.user_id
        WHERE cp.id = ? AND cp.status = 'published'
        LIMIT 1");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $post = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
}

function community_detail_time($datetime)
{
    $timestamp = strtotime((string) $datetime);

    return $timestamp ? date('d M Y H:i', $timestamp) : 'Tanggal tidak tersedia';
}
?>
<!DOCTYPE html>
<html lang="id" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $post ? e($post['title']) : 'Tulisan Tidak Ditemukan'; ?> - Pustakata</title>
    <?= theme_bootstrap_script(); ?>
    <link rel="stylesheet" href="<?= e(asset('css/style.css')); ?>">
</head>
<body>
    <?php include partial_path('public_navbar.php'); ?>
    <div class="container"><?= flash_messages(); ?></div>

    <main class="container community-detail-page">
        <nav class="breadcrumb reveal reveal-up">
            <a href="<?= e(url('index.php')); ?>">Beranda</a>
            <span>/</span>
            <a href="<?= e(url('community.php')); ?>">Komunitas</a>
            <span>/</span>
            <strong><?= $post ? e($post['title']) : 'Tidak ditemukan'; ?></strong>
        </nav>

        <?php if (!$post): ?>
            <section class="empty-state reveal reveal-up">
                <h1>Tulisan tidak ditemukan</h1>
                <p>Tulisan mungkin belum dipublikasikan, disembunyikan admin, atau sudah dihapus.</p>
                <a href="<?= e(url('community.php')); ?>" class="btn btn-primary">Kembali ke Komunitas</a>
            </section>
        <?php else: ?>
            <article class="community-detail-card reveal reveal-up">
                <p class="eyebrow">TULISAN KOMUNITAS</p>
                <h1><?= e($post['title']); ?></h1>
                <div class="community-detail-meta">
                    <div class="avatar"><?= e(strtoupper(substr($post['nama'], 0, 1))); ?></div>
                    <div>
                        <strong><?= e($post['nama']); ?></strong>
                        <span><?= e(community_detail_time($post['created_at'])); ?></span>
                    </div>
                </div>
                <div class="community-detail-content">
                    <?= nl2br(e($post['content'])); ?>
                </div>
                <a href="<?= e(url('community.php')); ?>" class="btn btn-outline">Kembali ke Komunitas</a>
            </article>
        <?php endif; ?>
    </main>

    <?php include partial_path('footer.php'); ?>
    <script src="<?= e(asset('js/script.js')); ?>"></script>
</body>
</html>
