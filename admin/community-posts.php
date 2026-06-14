<?php
require_once __DIR__ . "/../config/conn.php";
require_admin();

$adminPage = 'community-posts';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        require_csrf();

        if (!HAS_COMMUNITY_POSTS_TABLE) {
            throw new RuntimeException('Tabel community_posts belum tersedia.');
        }

        $action = $_POST['action'] ?? '';
        $id = (int) ($_POST['id'] ?? 0);

        if ($id <= 0) {
            throw new RuntimeException('Data tulisan tidak valid.');
        }

        if ($action === 'publish' || $action === 'hide') {
            $status = $action === 'publish' ? 'published' : 'hidden';
            $stmt = mysqli_prepare($conn, "UPDATE community_posts SET status = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "si", $status, $id);
            mysqli_stmt_execute($stmt);
            set_flash('success', $status === 'published' ? 'Tulisan berhasil dipublikasikan.' : 'Tulisan berhasil disembunyikan.');
        } elseif ($action === 'delete') {
            $stmt = mysqli_prepare($conn, "DELETE FROM community_posts WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "i", $id);
            mysqli_stmt_execute($stmt);
            set_flash('success', 'Tulisan komunitas berhasil dihapus.');
        } else {
            throw new RuntimeException('Aksi moderasi tidak dikenal.');
        }
    } catch (Throwable $exception) {
        set_flash('error', $exception->getMessage());
    }

    redirect('admin/community-posts.php');
}

$posts = null;
if (HAS_COMMUNITY_POSTS_TABLE) {
    $posts = mysqli_query($conn, "SELECT cp.id, cp.title, cp.content, cp.status, cp.created_at, cp.updated_at, u.nama
        FROM community_posts cp
        INNER JOIN users u ON u.id = cp.user_id
        ORDER BY cp.created_at DESC");
}
?>
<!DOCTYPE html>
<html lang="id" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Moderasi Komunitas - Pustakata</title>
    <?= theme_bootstrap_script(); ?>
    <link rel="stylesheet" href="<?= e(asset('css/style.css')); ?>">
</head>
<body>
    <div class="admin-shell">
        <?php include partial_path('admin_sidebar.php'); ?>

        <main class="admin-main">
            <div class="admin-topbar reveal reveal-up">
                <div>
                    <p class="eyebrow">MODERASI KOMUNITAS</p>
                    <h1>Tulisan Pembaca</h1>
                    <p>Lihat, sembunyikan, publikasikan, atau hapus tulisan komunitas.</p>
                </div>
                <a href="<?= e(url('community.php')); ?>" class="btn btn-outline">Lihat Komunitas</a>
            </div>

            <?= flash_messages(); ?>

            <?php if (!HAS_COMMUNITY_POSTS_TABLE): ?>
                <section class="empty-state compact reveal reveal-up">
                    <h2>Tabel komunitas belum tersedia</h2>
                    <p>Import <strong>database/migrations/migration_add_community_posts.sql</strong> agar admin bisa moderasi tulisan.</p>
                </section>
            <?php elseif (!$posts || mysqli_num_rows($posts) === 0): ?>
                <section class="empty-state compact reveal reveal-up">
                    <h2>Belum ada tulisan</h2>
                    <p>Tulisan komunitas dari user akan muncul di sini.</p>
                </section>
            <?php else: ?>
                <div class="table-card reveal reveal-up">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Judul</th>
                                <th>Penulis</th>
                                <th>Status</th>
                                <th>Tanggal</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($post = mysqli_fetch_assoc($posts)): ?>
                                <tr>
                                    <td>
                                        <strong><?= e($post['title']); ?></strong>
                                        <span><?= e(substr(trim(preg_replace('/\s+/', ' ', $post['content'])), 0, 90)); ?><?= strlen($post['content']) > 90 ? '...' : ''; ?></span>
                                    </td>
                                    <td><?= e($post['nama']); ?></td>
                                    <td><span class="status-badge status-<?= e($post['status']); ?>"><?= e($post['status']); ?></span></td>
                                    <td><?= e(date('d M Y', strtotime($post['created_at']))); ?></td>
                                    <td class="action-cell community-action-cell">
                                        <?php if ($post['status'] === 'published'): ?>
                                            <form method="POST" class="inline-form" data-confirm="Sembunyikan tulisan &quot;<?= e($post['title']); ?>&quot;?">
                                                <?= csrf_field(); ?>
                                                <input type="hidden" name="id" value="<?= e($post['id']); ?>">
                                                <input type="hidden" name="action" value="hide">
                                                <button type="submit" class="link-button">Hide</button>
                                            </form>
                                        <?php else: ?>
                                            <form method="POST" class="inline-form">
                                                <?= csrf_field(); ?>
                                                <input type="hidden" name="id" value="<?= e($post['id']); ?>">
                                                <input type="hidden" name="action" value="publish">
                                                <button type="submit" class="link-button">Publish</button>
                                            </form>
                                        <?php endif; ?>
                                        <form method="POST" class="inline-form" data-confirm="Hapus permanen tulisan &quot;<?= e($post['title']); ?>&quot;?">
                                            <?= csrf_field(); ?>
                                            <input type="hidden" name="id" value="<?= e($post['id']); ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <button type="submit" class="link-button danger">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </main>
    </div>
    <script src="<?= e(asset('js/script.js')); ?>"></script>
</body>
</html>
