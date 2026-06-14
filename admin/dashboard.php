<?php
require_once __DIR__ . "/../config/conn.php";
require_admin();

$adminPage = 'dashboard';

$reviewCountSelect = HAS_REVIEWS_TABLE ? "(SELECT COUNT(*) FROM reviews)" : "0";
$communityCountSelect = HAS_COMMUNITY_POSTS_TABLE ? "(SELECT COUNT(*) FROM community_posts)" : "0";
$dashboardStats = mysqli_fetch_assoc(mysqli_query($conn, "SELECT
        (SELECT COUNT(*) FROM buku2) AS total_books,
        (SELECT COALESCE(SUM(stok), 0) FROM buku2) AS total_stock,
        (SELECT COUNT(DISTINCT kategori) FROM buku2) AS total_categories,
        (SELECT COUNT(*) FROM users) AS total_users,
        $reviewCountSelect AS total_reviews,
        $communityCountSelect AS total_community_posts"));
$totalBooks = (int) ($dashboardStats['total_books'] ?? 0);
$totalStock = (int) ($dashboardStats['total_stock'] ?? 0);
$totalCategories = (int) ($dashboardStats['total_categories'] ?? 0);
$totalUsers = (int) ($dashboardStats['total_users'] ?? 0);
$totalReviews = (int) ($dashboardStats['total_reviews'] ?? 0);
$totalCommunityPosts = (int) ($dashboardStats['total_community_posts'] ?? 0);
$lowStockBooks = mysqli_query($conn, "SELECT id, judul, penulis, stok FROM buku2 WHERE stok <= 5 ORDER BY stok ASC, id DESC LIMIT 5");

if (HAS_REVIEWS_TABLE) {
    $latestBooks = mysqli_query($conn, "SELECT b.id, b.judul, b.penulis, b.kategori, b.stok, COUNT(r.id) AS review_count, COALESCE(ROUND(AVG(r.rating), 1), 0) AS avg_rating
        FROM buku2 b
        LEFT JOIN reviews r ON r.buku_id = b.id
        GROUP BY b.id, b.judul, b.penulis, b.kategori, b.stok
        ORDER BY b.id DESC
        LIMIT 5");
} else {
    $latestBooks = mysqli_query($conn, "SELECT b.id, b.judul, b.penulis, b.kategori, b.stok, 0 AS review_count, 0 AS avg_rating
        FROM buku2 b
        ORDER BY b.id DESC
        LIMIT 5");
}
?>
<!DOCTYPE html>
<html lang="id" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Pustakata</title>
    <?= theme_bootstrap_script(); ?>
    <link rel="stylesheet" href="<?= e(asset('css/style.css')); ?>">
</head>
<body>
    <div class="admin-shell">
        <?php include partial_path('admin_sidebar.php'); ?>

        <main class="admin-main">
            <div class="admin-topbar reveal reveal-up">
                <div>
                    <p class="eyebrow">PUSTAKATA ADMIN</p>
                    <h1>Selamat datang, <?= e(current_user_name()); ?></h1>
                    <p>Kelola koleksi buku, stok, dan katalog toko dari satu panel sederhana.</p>
                </div>
                <a href="<?= e(url('admin/book-form.php')); ?>" class="btn btn-primary">Tambah Buku</a>
            </div>

            <?= flash_messages(); ?>

            <section class="stats-grid" data-reveal-stagger>
                <article class="stat-card reveal reveal-scale">
                    <span>Total Buku</span>
                    <strong><?= e($totalBooks); ?></strong>
                </article>
                <article class="stat-card reveal reveal-scale">
                    <span>Total Stok</span>
                    <strong><?= e($totalStock); ?></strong>
                </article>
                <article class="stat-card reveal reveal-scale">
                    <span>Total Kategori</span>
                    <strong><?= e($totalCategories); ?></strong>
                </article>
                <article class="stat-card reveal reveal-scale">
                    <span>Total User</span>
                    <strong><?= e($totalUsers); ?></strong>
                </article>
                <article class="stat-card reveal reveal-scale">
                    <span>Total Review</span>
                    <strong><?= e($totalReviews); ?></strong>
                </article>
            </section>

            <section class="admin-card query-proof-card reveal reveal-up">
                <div class="section-header compact">
                    <div>
                        <h2>Bukti Query Responsi</h2>
                        <p>Ringkasan query.</p>
                    </div>
                    <a href="<?= e(url('community.php')); ?>" class="text-link">Lihat Komunitas</a>
                </div>

                <div class="query-proof-grid" data-reveal-stagger>
                    <article class="query-proof-item reveal reveal-up">
                        <span>Query 1 Tabel</span>
                        <h3>Total Buku</h3>
                        <p>Dashboard menampilkan <strong><?= e($totalBooks); ?> buku</strong> dari tabel katalog.</p>
                        <code>SELECT COUNT(*) AS total FROM buku2;</code>
                    </article>
                    <article class="query-proof-item reveal reveal-up">
                        <span>JOIN Buku + Review</span>
                        <h3>Rating Buku</h3>
                        <p>Buku terbaru dan katalog memakai rating rata-rata dari <strong><?= e($totalReviews); ?> review</strong>.</p>
                        <code>buku2 LEFT JOIN reviews</code>
                    </article>
                    <article class="query-proof-item reveal reveal-up">
                        <span>JOIN Komunitas</span>
                        <h3>Tulisan + Penulis</h3>
                        <p>Komunitas menampilkan <strong><?= e($totalCommunityPosts); ?> tulisan</strong> bersama nama user pembuatnya.</p>
                        <code>community_posts INNER JOIN users</code>
                    </article>
                    <article class="query-proof-item reveal reveal-up">
                        <span>JOIN Ulasan</span>
                        <h3>Review + Judul Buku</h3>
                        <p>Feed ulasan komunitas menggabungkan komentar pembaca dengan data buku.</p>
                        <code>reviews INNER JOIN buku2</code>
                    </article>
                </div>
            </section>

            <section class="admin-card reveal reveal-up">
                <div class="section-header compact">
                    <div>
                        <h2>Stok Perlu Dicek</h2>
                        <p>Buku dengan stok 5 atau kurang ditampilkan untuk prioritas restock.</p>
                    </div>
                    <a href="<?= e(url('admin/books.php')); ?>" class="text-link">Kelola Buku</a>
                </div>
                <div class="low-stock-list">
                    <?php if (mysqli_num_rows($lowStockBooks) === 0): ?>
                        <div class="empty-state compact">
                            <p>Semua stok masih aman.</p>
                        </div>
                    <?php else: ?>
                        <?php while ($stockBook = mysqli_fetch_assoc($lowStockBooks)): ?>
                            <?php $stock = stock_badge($stockBook['stok']); ?>
                            <a href="<?= e(url('admin/book-form.php?id=' . urlencode($stockBook['id']))); ?>">
                                <div>
                                    <strong><?= e($stockBook['judul']); ?></strong>
                                    <span><?= e($stockBook['penulis']); ?></span>
                                </div>
                                <span class="stock-badge <?= e($stock['class']); ?>"><?= e($stock['label']); ?> · <?= e($stockBook['stok']); ?></span>
                            </a>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </div>
            </section>

            <section class="admin-card reveal reveal-up">
                <div class="section-header compact">
                    <div>
                        <h2>Shortcut Admin</h2>
                        <p>Akses cepat untuk pekerjaan yang paling sering dipakai.</p>
                    </div>
                </div>
                <div class="shortcut-grid" data-reveal-stagger>
                    <a class="reveal reveal-up" href="<?= e(url('admin/books.php')); ?>">Kelola Buku</a>
                    <a class="reveal reveal-up" href="<?= e(url('admin/book-form.php')); ?>">Tambah Buku</a>
                    <a class="reveal reveal-up" href="<?= e(url('admin/community-posts.php')); ?>">Moderasi Komunitas</a>
                    <a class="reveal reveal-up" href="<?= e(url('shop/')); ?>">Lihat Toko</a>
                    <a class="reveal reveal-up" href="<?= e(url('admin/profile.php')); ?>">Profil Admin</a>
                </div>
            </section>

            <section class="admin-card reveal reveal-up">
                <div class="section-header compact">
                    <div>
                        <h2>Buku Terbaru</h2>
                        <p>Lima data buku terakhir yang masuk ke katalog.</p>
                    </div>
                </div>
                <div class="table-card no-hover reveal reveal-up">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Judul</th>
                                <th>Kategori</th>
                                <th>Stok</th>
                                <th>Rating</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($book = mysqli_fetch_assoc($latestBooks)): ?>
                                <tr>
                                    <td>
                                        <strong><?= e($book['judul']); ?></strong>
                                        <span><?= e($book['penulis']); ?></span>
                                    </td>
                                    <td><?= e($book['kategori']); ?></td>
                                    <td><?= e($book['stok']); ?></td>
                                    <td>★ <?= e($book['avg_rating'] > 0 ? $book['avg_rating'] : 'Baru'); ?> <span><?= e($book['review_count']); ?> review</span></td>
                                    <td><a href="<?= e(url('admin/book-form.php?id=' . urlencode($book['id']))); ?>">Edit</a></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>
    <script src="<?= e(asset('js/script.js')); ?>"></script>
</body>
</html>
