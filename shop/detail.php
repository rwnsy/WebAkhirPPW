<?php
require_once __DIR__ . "/../config/conn.php";

$activePage = 'shop';
$id = (int) ($_GET['id'] ?? 0);

if (HAS_REVIEWS_TABLE) {
    $stmt = mysqli_prepare($conn, "SELECT b.*, COUNT(r.id) AS review_count, COALESCE(ROUND(AVG(r.rating), 1), 0) AS avg_rating
        FROM buku2 b
        LEFT JOIN reviews r ON r.buku_id = b.id
        WHERE b.id = ?
        GROUP BY b.id, b.judul, b.penulis, b.penerbit, b.tahun_terbit, b.kategori, b.harga, b.stok, b.gambar, b.deskripsi, b.created_at
        LIMIT 1");
} else {
    $stmt = mysqli_prepare($conn, "SELECT b.*, 0 AS review_count, 0 AS avg_rating
        FROM buku2 b
        WHERE b.id = ?
        LIMIT 1");
}
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$book = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

$reviews = null;
$recommendations = null;
if ($book && HAS_REVIEWS_TABLE) {
    $reviewStmt = mysqli_prepare($conn, "SELECT reviewer_name, rating, komentar, created_at FROM reviews WHERE buku_id = ? ORDER BY id DESC LIMIT 4");
    mysqli_stmt_bind_param($reviewStmt, "i", $id);
    mysqli_stmt_execute($reviewStmt);
    $reviews = mysqli_stmt_get_result($reviewStmt);
}

if ($book) {
    $recommendStmt = mysqli_prepare($conn, "SELECT id, judul, penulis, harga, stok, gambar, kategori
        FROM buku2
        WHERE kategori = ? AND id <> ?
        ORDER BY id DESC
        LIMIT 4");
    mysqli_stmt_bind_param($recommendStmt, "si", $book['kategori'], $id);
    mysqli_stmt_execute($recommendStmt);
    $recommendations = mysqli_stmt_get_result($recommendStmt);
}
?>
<!DOCTYPE html>
<html lang="id" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $book ? e($book['judul']) : 'Buku Tidak Ditemukan'; ?> - Pustakata</title>
    <?= theme_bootstrap_script(); ?>
    <link rel="stylesheet" href="<?= e(asset('css/style.css')); ?>">
</head>
<body>
    <?php include partial_path('public_navbar.php'); ?>
    <div class="container"><?= flash_messages(); ?></div>

    <main class="container detail-page">
        <?php if (!$book): ?>
            <section class="empty-state reveal reveal-up">
                <h1>Buku tidak ditemukan</h1>
                <p>Data buku yang Anda cari tidak tersedia atau sudah dihapus.</p>
                <a href="<?= e(url('shop/')); ?>" class="btn btn-primary">Kembali ke Toko</a>
            </section>
        <?php else: ?>
            <nav class="breadcrumb reveal reveal-up">
                <a href="<?= e(url('index.php')); ?>">Beranda</a>
                <span>/</span>
                <a href="<?= e(url('shop/')); ?>">Toko</a>
                <span>/</span>
                <strong><?= e($book['judul']); ?></strong>
            </nav>

            <section class="detail-grid">
                <div class="detail-cover reveal reveal-left">
                    <?php if (!empty($book['gambar'])): ?>
                        <img src="<?= e(upload_url($book['gambar'])); ?>" alt="<?= e($book['judul']); ?>" decoding="async" fetchpriority="high">
                    <?php else: ?>
                        <div class="book-cover cover-<?= (($book['id'] ?? 1) % 4) + 1; ?>">
                            <span class="book-category"><?= e($book['kategori']); ?></span>
                            <strong><?= e($book['judul']); ?></strong>
                            <small><?= e($book['penulis']); ?></small>
                        </div>
                    <?php endif; ?>
                </div>

                <article class="detail-info reveal reveal-right">
                    <span class="badge"><?= e($book['kategori']); ?></span>
                    <?php $stock = stock_badge($book['stok']); ?>
                    <span class="stock-badge <?= e($stock['class']); ?>"><?= e($stock['label']); ?></span>
                    <h1><?= e($book['judul']); ?></h1>
                    <p class="detail-author"><?= e($book['penulis']); ?></p>

                    <div class="detail-meta" data-reveal-stagger>
                        <div class="reveal reveal-up"><span>Penerbit</span><strong><?= e($book['penerbit']); ?></strong></div>
                        <div class="reveal reveal-up"><span>Tahun Terbit</span><strong><?= e($book['tahun_terbit']); ?></strong></div>
                        <div class="reveal reveal-up"><span>Stok</span><strong><?= e($book['stok']); ?> buku</strong></div>
                        <div class="reveal reveal-up"><span>Harga</span><strong><?= rupiah($book['harga']); ?></strong></div>
                        <div class="reveal reveal-up"><span>Rating</span><strong>★ <?= e($book['avg_rating'] > 0 ? $book['avg_rating'] : 'Baru'); ?></strong></div>
                        <div class="reveal reveal-up"><span>Review</span><strong><?= e($book['review_count']); ?> ulasan</strong></div>
                    </div>

                    <div class="detail-description">
                        <h2>Deskripsi</h2>
                        <p><?= nl2br(e($book['deskripsi'] ?: 'Deskripsi buku belum tersedia.')); ?></p>
                    </div>

                    <div class="detail-actions">
                        <a href="<?= e(url('shop/')); ?>" class="btn btn-outline">Kembali</a>
                        <?php if ((int) $book['stok'] <= 0): ?>
                            <button type="button" class="btn btn-primary" disabled>Stok Habis</button>
                        <?php else: ?>
                            <form method="POST" action="<?= e(url('shop/cart.php')); ?>" class="inline-form">
                                <?= csrf_field(); ?>
                                <input type="hidden" name="action" value="add">
                                <input type="hidden" name="book_id" value="<?= e($book['id']); ?>">
                                <button type="submit" class="btn btn-primary">Tambah ke Keranjang</button>
                            </form>
                            <form method="POST" action="<?= e(url('shop/cart.php')); ?>" class="inline-form">
                                <?= csrf_field(); ?>
                                <input type="hidden" name="action" value="add">
                                <input type="hidden" name="book_id" value="<?= e($book['id']); ?>">
                                <input type="hidden" name="buy_now" value="1">
                                <button type="submit" class="btn btn-outline">Beli Sekarang</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </article>
            </section>

            <section class="detail-reviews reveal reveal-up">
                <div class="section-header compact reveal reveal-up">
                    <div>
                        <h2>Ulasan Pembaca</h2>
                    </div>
                </div>
                <?php if ($reviews && mysqli_num_rows($reviews) > 0): ?>
                    <div class="review-grid" data-reveal-stagger>
                        <?php while ($review = mysqli_fetch_assoc($reviews)): ?>
                            <article class="review-card reveal reveal-up">
                                <strong><?= e($review['reviewer_name']); ?></strong>
                                <span>★ <?= e($review['rating']); ?>/5</span>
                                <p><?= e($review['komentar']); ?></p>
                            </article>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state compact reveal reveal-up">
                        <p>Belum ada ulasan untuk buku ini.</p>
                    </div>
                <?php endif; ?>
            </section>

            <?php if ($recommendations && mysqli_num_rows($recommendations) > 0): ?>
                <section class="detail-recommendations reveal reveal-up">
                    <div class="section-header compact reveal reveal-up">
                        <div>
                            <h2>Rekomendasi Sejenis</h2>
                            <p>Buku lain dari kategori <?= e($book['kategori']); ?>.</p>
                        </div>
                    </div>
                    <div class="mini-book-grid" data-reveal-stagger>
                        <?php while ($item = mysqli_fetch_assoc($recommendations)): ?>
                            <?php $itemStock = stock_badge($item['stok']); ?>
                            <article class="mini-book-card reveal reveal-up">
                                <?php if (!empty($item['gambar'])): ?>
                                    <img src="<?= e(upload_url($item['gambar'])); ?>" alt="<?= e($item['judul']); ?>" loading="lazy" decoding="async">
                                <?php else: ?>
                                    <div class="mini-cover cover-<?= (($item['id'] ?? 1) % 4) + 1; ?>"><?= e(substr($item['judul'], 0, 1)); ?></div>
                                <?php endif; ?>
                                <div>
                                    <span class="stock-badge <?= e($itemStock['class']); ?>"><?= e($itemStock['label']); ?></span>
                                    <h3><a href="<?= e(url('shop/detail.php?id=' . urlencode($item['id']))); ?>"><?= e($item['judul']); ?></a></h3>
                                    <p><?= e($item['penulis']); ?></p>
                                    <strong><?= rupiah($item['harga']); ?></strong>
                                </div>
                            </article>
                        <?php endwhile; ?>
                    </div>
                </section>
            <?php endif; ?>
        <?php endif; ?>
    </main>

    <?php include partial_path('footer.php'); ?>
    <script src="<?= e(asset('js/script.js')); ?>"></script>
</body>
</html>
