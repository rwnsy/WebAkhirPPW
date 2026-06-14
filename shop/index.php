<?php
require_once __DIR__ . "/../config/conn.php";

$keyword = trim($_GET['q'] ?? '');
$category = trim($_GET['kategori'] ?? '');
$page = max(1, (int) ($_GET['page'] ?? 1));
$limit = 6;
$offset = ($page - 1) * $limit;

$conditions = [];
$params = [];
$types = "";

if ($keyword !== "") {
    $conditions[] = "(b.judul LIKE ? OR b.penulis LIKE ? OR b.penerbit LIKE ?)";
    $like = "%" . $keyword . "%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= "sss";
}

if ($category !== "") {
    $conditions[] = "b.kategori = ?";
    $params[] = $category;
    $types .= "s";
}

$where = $conditions ? "WHERE " . implode(" AND ", $conditions) : "";

$categoryResult = mysqli_query($conn, "SELECT kategori, COUNT(*) AS total FROM buku2 GROUP BY kategori ORDER BY kategori ASC");

$countStmt = mysqli_prepare($conn, "SELECT COUNT(*) AS total FROM buku2 b $where");
if ($types) {
    mysqli_stmt_bind_param($countStmt, $types, ...$params);
}
mysqli_stmt_execute($countStmt);
$totalRows = (int) mysqli_fetch_assoc(mysqli_stmt_get_result($countStmt))['total'];
$totalPages = max(1, (int) ceil($totalRows / $limit));

if (HAS_REVIEWS_TABLE) {
    $sql = "SELECT b.*, COUNT(r.id) AS review_count, COALESCE(ROUND(AVG(r.rating), 1), 0) AS avg_rating
            FROM buku2 b
            LEFT JOIN reviews r ON r.buku_id = b.id
            $where
            GROUP BY b.id, b.judul, b.penulis, b.penerbit, b.tahun_terbit, b.kategori, b.harga, b.stok, b.gambar, b.deskripsi, b.created_at
            ORDER BY b.id DESC
            LIMIT ? OFFSET ?";
} else {
    $sql = "SELECT b.*, 0 AS review_count, 0 AS avg_rating
            FROM buku2 b
            $where
            ORDER BY b.id DESC
            LIMIT ? OFFSET ?";
}
$stmt = mysqli_prepare($conn, $sql);
if ($types) {
    $listTypes = $types . "ii";
    $listParams = array_merge($params, [$limit, $offset]);
    mysqli_stmt_bind_param($stmt, $listTypes, ...$listParams);
} else {
    mysqli_stmt_bind_param($stmt, "ii", $limit, $offset);
}
mysqli_stmt_execute($stmt);
$books = mysqli_stmt_get_result($stmt);
$activePage = 'shop';
$searchValue = $keyword;
?>
<!DOCTYPE html>
<html lang="id" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Toko Buku - Pustakata</title>
    <?= theme_bootstrap_script(); ?>
    <link rel="stylesheet" href="<?= e(asset('css/style.css')); ?>">
</head>
<body>
    <?php include partial_path('public_navbar.php'); ?>
    <div class="container"><?= flash_messages(); ?></div>

    <main class="container shop-page">
        <aside class="filter-panel reveal reveal-left">
            <h2>Filter Pencarian</h2>
            <form method="GET" data-filter-feedback>
                <input type="hidden" name="q" value="<?= e($keyword); ?>">
                <label class="radio-row">
                    <input type="radio" name="kategori" value="" <?= $category === '' ? 'checked' : ''; ?>>
                    Semua Genre
                </label>
                <?php while ($cat = mysqli_fetch_assoc($categoryResult)): ?>
                    <label class="radio-row">
                        <input type="radio" name="kategori" value="<?= e($cat['kategori']); ?>" <?= $category === $cat['kategori'] ? 'checked' : ''; ?>>
                        <?= e($cat['kategori']); ?> <span><?= e($cat['total']); ?></span>
                    </label>
                <?php endwhile; ?>
                <button class="btn btn-primary" type="submit">Terapkan Filter</button>
                <a href="<?= e(url('shop/')); ?>" class="reset-link">Reset Semua</a>
            </form>
        </aside>

        <section class="reveal reveal-up">
            <div class="section-header reveal reveal-up">
                <div>
                    <h1>Semua Buku</h1>
                    <p>Menampilkan <?= e($totalRows); ?> koleksi buku pilihan</p>
                    <?php if ($keyword !== '' || $category !== ''): ?>
                        <p class="search-feedback">
                            Menampilkan hasil<?= $keyword !== '' ? ' untuk "' . e($keyword) . '"' : ''; ?><?= $category !== '' ? ' dalam kategori ' . e($category) : ''; ?>.
                        </p>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (mysqli_num_rows($books) === 0): ?>
                <section class="empty-state reveal reveal-up">
                    <h2>Koleksi tidak ditemukan</h2>
                    <p>Coba kata kunci lain atau reset filter kategori.</p>
                    <a href="<?= e(url('shop/')); ?>" class="btn btn-primary">Reset Pencarian</a>
                </section>
            <?php else: ?>
                <div class="book-grid shop-grid" data-reveal-stagger>
                    <?php while ($book = mysqli_fetch_assoc($books)): ?>
                    <?php $stock = stock_badge($book['stok'] ?? 0); ?>
                    <article class="book-card reveal reveal-scale">
                        <a href="<?= e(url('shop/detail.php?id=' . urlencode($book['id']))); ?>" class="book-media-link">
                            <?php if (!empty($book['gambar'])): ?>
                                <img class="real-cover" src="<?= e(upload_url($book['gambar'])); ?>" alt="<?= e($book['judul']); ?>" loading="lazy" decoding="async">
                            <?php else: ?>
                                <div class="book-cover cover-<?= (($book['id'] ?? 1) % 4) + 1; ?>">
                                    <span class="book-category"><?= e($book['kategori'] ?? 'Buku'); ?></span>
                                    <strong><?= e($book['judul'] ?? 'Tanpa Judul'); ?></strong>
                                    <small><?= e($book['penulis'] ?? 'Penulis'); ?></small>
                                </div>
                            <?php endif; ?>
                        </a>
                        <div class="book-info">
                            <span class="stock-badge <?= e($stock['class']); ?>"><?= e($stock['label']); ?></span>
                            <h3><a href="<?= e(url('shop/detail.php?id=' . urlencode($book['id']))); ?>"><?= e($book['judul'] ?? 'Tanpa Judul'); ?></a></h3>
                            <p><?= e($book['penulis'] ?? 'Penulis'); ?></p>
                            <div class="rating">★ <?= e($book['avg_rating'] > 0 ? $book['avg_rating'] : 'Baru'); ?> <span>(<?= e($book['review_count']); ?> review, <?= e($book['stok'] ?? 0); ?> stok)</span></div>
                            <div class="price-row">
                                <strong><?= rupiah($book['harga'] ?? 0); ?></strong>
                                <a href="<?= e(url('shop/detail.php?id=' . urlencode($book['id']))); ?>" title="Lihat detail">Detail</a>
                            </div>
                        </div>
                    </article>
                    <?php endwhile; ?>
                </div>
            <?php endif; ?>

            <nav class="pagination reveal reveal-up">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a class="<?= $i === $page ? 'active' : ''; ?>" href="?q=<?= urlencode($keyword); ?>&kategori=<?= urlencode($category); ?>&page=<?= $i; ?>"><?= $i; ?></a>
                <?php endfor; ?>
            </nav>
        </section>
    </main>

    <?php include partial_path('footer.php'); ?>
    <script src="<?= e(asset('js/script.js')); ?>"></script>
</body>
</html>
