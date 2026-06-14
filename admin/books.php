<?php
require_once __DIR__ . "/../config/conn.php";
require_admin();

$keyword = trim($_GET['q'] ?? '');
$page = max(1, (int) ($_GET['page'] ?? 1));
$limit = 5;
$offset = ($page - 1) * $limit;

$where = "";
$params = [];
$types = "";

if ($keyword !== "") {
    $where = "WHERE judul LIKE ? OR penulis LIKE ? OR kategori LIKE ?";
    $like = "%" . $keyword . "%";
    $params = [$like, $like, $like];
    $types = "sss";
}

$countSql = "SELECT COUNT(*) AS total FROM buku2 $where";
$countStmt = mysqli_prepare($conn, $countSql);
if ($types) {
    mysqli_stmt_bind_param($countStmt, $types, ...$params);
}
mysqli_stmt_execute($countStmt);
$totalRows = (int) mysqli_fetch_assoc(mysqli_stmt_get_result($countStmt))['total'];
$totalPages = max(1, (int) ceil($totalRows / $limit));

$sql = "SELECT * FROM buku2 $where ORDER BY id DESC LIMIT ? OFFSET ?";
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
$adminPage = 'books';
?>
<!DOCTYPE html>
<html lang="id" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Buku - Pustakata</title>
    <?= theme_bootstrap_script(); ?>
    <link rel="stylesheet" href="<?= e(asset('css/style.css')); ?>">
</head>
<body>
    <div class="admin-shell">
        <?php include partial_path('admin_sidebar.php'); ?>

        <main class="admin-main">
        <div class="admin-topbar reveal reveal-up">
            <div>
                <p class="eyebrow">KOLEKSI BUKU</p>
                <h1>Kelola Data Buku</h1>
                <p>CRUD buku, upload gambar cover, pencarian, dan pagination.</p>
                <?php if ($keyword !== ''): ?>
                    <p class="search-feedback">Menampilkan <?= e($totalRows); ?> hasil untuk "<?= e($keyword); ?>".</p>
                <?php endif; ?>
            </div>
            <a href="<?= e(url('admin/book-form.php')); ?>" class="btn btn-primary">Tambah Buku</a>
        </div>

        <?= flash_messages(); ?>

        <form method="GET" class="toolbar-form reveal reveal-up" data-search-feedback>
            <input type="text" name="q" placeholder="Cari judul, penulis, atau kategori..." value="<?= e($keyword); ?>">
            <button type="submit" class="btn btn-primary">Cari</button>
            <a href="<?= e(url('admin/books.php')); ?>" class="btn btn-outline">Reset</a>
        </form>

        <?php if (mysqli_num_rows($books) === 0): ?>
            <section class="empty-state compact reveal reveal-up">
                <h2>Data buku kosong</h2>
                <p>Belum ada buku yang cocok dengan pencarian.</p>
                <a href="<?= e(url('admin/book-form.php')); ?>" class="btn btn-primary">Tambah Buku</a>
            </section>
        <?php else: ?>
            <div class="table-card reveal reveal-up">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Cover</th>
                            <th>Judul</th>
                            <th>Kategori</th>
                            <th>Harga</th>
                            <th>Stok</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($book = mysqli_fetch_assoc($books)): ?>
                            <?php $stock = stock_badge($book['stok']); ?>
                            <tr>
                                <td>
                                    <?php if ($book['gambar']): ?>
                                        <img class="thumb" src="<?= e(upload_url($book['gambar'])); ?>" alt="<?= e($book['judul']); ?>" loading="lazy" decoding="async">
                                    <?php else: ?>
                                        <div class="thumb thumb-empty">B</div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?= e($book['judul']); ?></strong>
                                    <span><?= e($book['penulis']); ?>, <?= e($book['tahun_terbit']); ?></span>
                                </td>
                                <td><?= e($book['kategori']); ?></td>
                                <td><?= rupiah($book['harga']); ?></td>
                                <td><span class="stock-badge <?= e($stock['class']); ?>"><?= e($stock['label']); ?> · <?= e($book['stok']); ?></span></td>
                                <td class="action-cell">
                                    <a href="<?= e(url('admin/book-form.php?id=' . urlencode($book['id']))); ?>">Edit</a>
                                    <form method="POST" action="<?= e(url('admin/book-delete.php')); ?>" class="inline-form" data-confirm="Hapus buku &quot;<?= e($book['judul']); ?>&quot; dari katalog?">
                                        <?= csrf_field(); ?>
                                        <input type="hidden" name="id" value="<?= e($book['id']); ?>">
                                        <button type="submit" class="link-button danger">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <nav class="pagination reveal reveal-up">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a class="<?= $i === $page ? 'active' : ''; ?>" href="?q=<?= urlencode($keyword); ?>&page=<?= $i; ?>"><?= $i; ?></a>
            <?php endfor; ?>
        </nav>
        </main>
    </div>
    <script src="<?= e(asset('js/script.js')); ?>"></script>
</body>
</html>
